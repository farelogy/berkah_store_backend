<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\MutasiStok;
use App\Models\RiwayatStok;
use App\Models\StokBarang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class MutasiStokService
{
    /**
     * Step 1: Branch cashier or admin requests stock.
     */
    public function requestMutasi(int $idCabangTujuan, int $idBarang, int $qtyMinta, ?string $catatan, int $userId): MutasiStok
    {
        if ($qtyMinta <= 0) {
            throw new InvalidArgumentException('Jumlah permintaan mutasi harus lebih besar dari 0.');
        }

        $barang = Barang::findOrFail($idBarang);
        $kodeMutasi = 'MUT-' . date('YmdHis') . '-' . strtoupper(Str::random(4));

        return MutasiStok::create([
            'kode_mutasi' => $kodeMutasi,
            'id_cabang_tujuan' => $idCabangTujuan,
            'id_user_request' => $userId,
            'id_barang' => $barang->id,
            'jumlah_minta' => $qtyMinta,
            'status' => 'Requested',
            'catatan' => $catatan,
        ])->load(['barang', 'cabangTujuan', 'userRequest']);
    }

    /**
     * Step 2: Admin Pusat decides source (Gudang Pusat vs another branch), approves, and dispatches.
     * Deducts source stock immediately. Does NOT increment destination stock yet!
     */
    public function dispatchMutasi(
        int $idMutasi,
        string $sumberTipe,
        ?int $idCabangAsal,
        int $qtyKirim,
        int $dispatcherUserId,
        ?string $catatan = null
    ): MutasiStok {
        if (!in_array($sumberTipe, ['Gudang Pusat', 'Cabang Lain'])) {
            throw new InvalidArgumentException('Sumber tipe harus "Gudang Pusat" atau "Cabang Lain".');
        }

        if ($qtyKirim <= 0) {
            throw new InvalidArgumentException('Jumlah kirim harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($idMutasi, $sumberTipe, $idCabangAsal, $qtyKirim, $dispatcherUserId, $catatan) {
            $mutasi = MutasiStok::lockForUpdate()->findOrFail($idMutasi);

            if ($mutasi->status !== 'Requested') {
                throw new RuntimeException("Mutasi tidak dapat dikirim karena berstatus [{$mutasi->status}].");
            }

            if ($sumberTipe === 'Gudang Pusat') {
                // Deduct Central Warehouse Stock
                $sourceStok = StokBarang::where('id_barang', $mutasi->id_barang)
                    ->where('is_gudang_pusat', true)
                    ->lockForUpdate()
                    ->first();

                $currentQty = $sourceStok ? (int) $sourceStok->stok : 0;
                if ($currentQty < $qtyKirim) {
                    throw new RuntimeException("Stok Gudang Pusat tidak mencukupi (sisa: {$currentQty}, diminta kirim: {$qtyKirim}).");
                }

                $sourceStok->stok -= $qtyKirim;
                $sourceStok->save();

                RiwayatStok::create([
                    'id_barang' => $mutasi->id_barang,
                    'id_cabang' => null,
                    'id_user' => $dispatcherUserId,
                    'jumlah' => -$qtyKirim,
                    'stok_akhir' => $sourceStok->stok,
                    'tipe' => 'Mutasi Keluar',
                    'alasan' => "Kirim ke Cabang #{$mutasi->id_cabang_tujuan} (Mutasi {$mutasi->kode_mutasi})",
                    'referensi_kode' => $mutasi->kode_mutasi,
                ]);

                $idCabangAsal = null;
            } else {
                // Deduct from another Branch
                if (!$idCabangAsal) {
                    throw new InvalidArgumentException('Harap tentukan Cabang Asal sumber barang.');
                }
                if ($idCabangAsal === $mutasi->id_cabang_tujuan) {
                    throw new InvalidArgumentException('Cabang asal tidak boleh sama dengan cabang tujuan.');
                }

                $sourceStok = StokBarang::where('id_barang', $mutasi->id_barang)
                    ->where('id_cabang', $idCabangAsal)
                    ->where('is_gudang_pusat', false)
                    ->lockForUpdate()
                    ->first();

                $currentQty = $sourceStok ? (int) $sourceStok->stok : 0;
                if ($currentQty < $qtyKirim) {
                    throw new RuntimeException("Stok Cabang Asal #{$idCabangAsal} tidak mencukupi (sisa: {$currentQty}, diminta kirim: {$qtyKirim}).");
                }

                $sourceStok->stok -= $qtyKirim;
                $sourceStok->save();

                RiwayatStok::create([
                    'id_barang' => $mutasi->id_barang,
                    'id_cabang' => $idCabangAsal,
                    'id_user' => $dispatcherUserId,
                    'jumlah' => -$qtyKirim,
                    'stok_akhir' => $sourceStok->stok,
                    'tipe' => 'Mutasi Keluar',
                    'alasan' => "Oper stok ke Cabang #{$mutasi->id_cabang_tujuan} (Mutasi {$mutasi->kode_mutasi})",
                    'referensi_kode' => $mutasi->kode_mutasi,
                ]);
            }

            $mutasi->sumber_tipe = $sumberTipe;
            $mutasi->id_cabang_asal = $idCabangAsal;
            $mutasi->jumlah_kirim = $qtyKirim;
            $mutasi->id_user_dispatcher = $dispatcherUserId;
            $mutasi->status = 'Dispatched';
            $mutasi->dispatched_at = now();
            if ($catatan) {
                $mutasi->catatan = ($mutasi->catatan ? $mutasi->catatan . ' | ' : '') . $catatan;
            }
            $mutasi->save();

            return $mutasi->load(['barang', 'cabangTujuan', 'cabangAsal', 'userDispatcher']);
        });
    }

    /**
     * Step 3: Destination branch verifies physical arrival and clicks "Konfirmasi Terima".
     * Destination stock increments ONLY at this moment!
     */
    public function confirmReceive(
        int $idMutasi,
        int $qtyDiterima,
        int $penerimaUserId,
        ?string $catatan = null
    ): MutasiStok {
        if ($qtyDiterima < 0) {
            throw new InvalidArgumentException('Jumlah barang diterima tidak boleh negatif.');
        }

        return DB::transaction(function () use ($idMutasi, $qtyDiterima, $penerimaUserId, $catatan) {
            $mutasi = MutasiStok::lockForUpdate()->findOrFail($idMutasi);

            if ($mutasi->status !== 'Dispatched') {
                throw new RuntimeException("Mutasi tidak dapat dikonfirmasi karena berstatus [{$mutasi->status}].");
            }

            // Find or create destination branch stock record
            $destStok = StokBarang::firstOrCreate(
                [
                    'id_barang' => $mutasi->id_barang,
                    'id_cabang' => $mutasi->id_cabang_tujuan,
                    'is_gudang_pusat' => false,
                ],
                ['stok' => 0]
            );

            // Row lock
            $destStok = StokBarang::where('id', $destStok->id)->lockForUpdate()->first();

            $destStok->stok += $qtyDiterima;
            $destStok->save();

            RiwayatStok::create([
                'id_barang' => $mutasi->id_barang,
                'id_cabang' => $mutasi->id_cabang_tujuan,
                'id_user' => $penerimaUserId,
                'jumlah' => $qtyDiterima,
                'stok_akhir' => $destStok->stok,
                'tipe' => 'Mutasi Masuk',
                'alasan' => "Terima fisik mutasi {$mutasi->kode_mutasi} dari {$mutasi->sumber_tipe}",
                'referensi_kode' => $mutasi->kode_mutasi,
            ]);

            $mutasi->jumlah_diterima = $qtyDiterima;
            $mutasi->id_user_penerima = $penerimaUserId;
            $mutasi->status = 'Completed';
            $mutasi->completed_at = now();
            if ($catatan) {
                $mutasi->catatan = ($mutasi->catatan ? $mutasi->catatan . ' | Penerima: ' : 'Penerima: ') . $catatan;
            }
            $mutasi->save();

            return $mutasi->load(['barang', 'cabangTujuan', 'cabangAsal', 'userPenerima']);
        });
    }

    /**
     * Reject a mutasi request.
     * If already dispatched, safely reverse source stock.
     */
    public function rejectMutasi(int $idMutasi, int $userId, string $alasan): MutasiStok
    {
        return DB::transaction(function () use ($idMutasi, $userId, $alasan) {
            $mutasi = MutasiStok::lockForUpdate()->findOrFail($idMutasi);

            if ($mutasi->status === 'Completed') {
                throw new RuntimeException('Mutasi yang sudah selesai tidak dapat ditolak.');
            }

            if ($mutasi->status === 'Dispatched') {
                // Return stock to sender
                if ($mutasi->sumber_tipe === 'Gudang Pusat') {
                    $sourceStok = StokBarang::where('id_barang', $mutasi->id_barang)
                        ->where('is_gudang_pusat', true)
                        ->lockForUpdate()
                        ->first();
                    if ($sourceStok) {
                        $sourceStok->stok += $mutasi->jumlah_kirim;
                        $sourceStok->save();
                    }
                } else {
                    $sourceStok = StokBarang::where('id_barang', $mutasi->id_barang)
                        ->where('id_cabang', $mutasi->id_cabang_asal)
                        ->lockForUpdate()
                        ->first();
                    if ($sourceStok) {
                        $sourceStok->stok += $mutasi->jumlah_kirim;
                        $sourceStok->save();
                    }
                }

                RiwayatStok::create([
                    'id_barang' => $mutasi->id_barang,
                    'id_cabang' => $mutasi->id_cabang_asal,
                    'id_user' => $userId,
                    'jumlah' => $mutasi->jumlah_kirim,
                    'stok_akhir' => $sourceStok ? $sourceStok->stok : 0,
                    'tipe' => 'Restock',
                    'alasan' => "Rollback mutasi {$mutasi->kode_mutasi} yang ditolak: {$alasan}",
                    'referensi_kode' => $mutasi->kode_mutasi,
                ]);
            }

            $mutasi->status = 'Rejected';
            $mutasi->catatan = ($mutasi->catatan ? $mutasi->catatan . ' | Ditolak: ' : 'Ditolak: ') . $alasan;
            $mutasi->save();

            return $mutasi;
        });
    }
}
