<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\DetailRetur;
use App\Models\DetailTransaksi;
use App\Models\KasHarian;
use App\Models\KlaimBarangRusak;
use App\Models\Pembeli;
use App\Models\Retur;
use App\Models\RiwayatStok;
use App\Models\StokBarang;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ReturService
{
    /**
     * Process item return (Murni Retur or Tukar Barang).
     */
    public function prosesRetur(array $data, int $idCabang, int $idKasir): Retur
    {
        return DB::transaction(function () use ($data, $idCabang, $idKasir) {
            $idTransaksi = (int) $data['id_transaksi'];
            $tipeRetur = $data['tipe_retur'] ?? 'Tukar Barang';
            $itemsRetur = $data['items_retur'] ?? [];
            $itemsPengganti = $data['items_pengganti'] ?? [];
            $keterangan = $data['keterangan'] ?? null;

            if (empty($itemsRetur)) {
                throw new InvalidArgumentException('Daftar barang yang diretur tidak boleh kosong.');
            }

            $transaksi = Transaksi::lockForUpdate()->findOrFail($idTransaksi);
            $kodeRetur = 'RET-' . $idCabang . '-' . date('YmdHis') . '-' . strtoupper(Str::random(4));

            // 1. Process Returned Items
            $totalNilaiRetur = 0;
            $processedDetailReturs = [];

            foreach ($itemsRetur as $retItem) {
                $idBarang = (int) $retItem['id_barang'];
                $qty = (int) $retItem['jumlah'];
                $kondisi = $retItem['kondisi_barang'] ?? 'Layak Jual';

                if ($qty <= 0) {
                    throw new InvalidArgumentException('Jumlah barang retur harus lebih besar dari 0.');
                }

                // Check original invoice detail
                $origDetail = DetailTransaksi::where('id_transaksi', $transaksi->id)
                    ->where('id_barang', $idBarang)
                    ->lockForUpdate()
                    ->first();

                if (!$origDetail) {
                    throw new InvalidArgumentException("Barang #{$idBarang} tidak ada dalam nota transaksi {$transaksi->kode_transaksi}.");
                }

                $availableToRefund = $origDetail->jumlah - $origDetail->jumlah_direfund;
                if ($qty > $availableToRefund) {
                    throw new InvalidArgumentException("Jumlah retur melebihi sisa pembelian (sisa: {$availableToRefund}, diminta: {$qty}).");
                }

                $origDetail->jumlah_direfund += $qty;
                $origDetail->save();

                $hargaSatuan = (float) $origDetail->harga_satuan;
                $subtotal = $qty * $hargaSatuan;
                $totalNilaiRetur += $subtotal;

                $barang = Barang::findOrFail($idBarang);

                if ($kondisi === 'Layak Jual') {
                    // Re-enter branch stock
                    $stokRow = StokBarang::firstOrCreate(
                        ['id_barang' => $idBarang, 'id_cabang' => $idCabang, 'is_gudang_pusat' => false],
                        ['stok' => 0]
                    );
                    $stokRow = StokBarang::where('id', $stokRow->id)->lockForUpdate()->first();
                    $stokRow->stok += $qty;
                    $stokRow->save();

                    RiwayatStok::create([
                        'id_barang' => $idBarang,
                        'id_cabang' => $idCabang,
                        'id_user' => $idKasir,
                        'jumlah' => $qty,
                        'stok_akhir' => $stokRow->stok,
                        'tipe' => 'Retur Masuk',
                        'alasan' => "Retur Layak Jual nota {$transaksi->kode_transaksi}",
                        'referensi_kode' => $kodeRetur,
                    ]);
                } else {
                    // Damaged / defective item: route to Klaim Distributor (DO NOT add to branch stock!)
                    KlaimBarangRusak::create([
                        'kode_klaim' => 'KLM-' . date('YmdHis') . '-' . strtoupper(Str::random(4)),
                        'id_cabang' => $idCabang,
                        'id_barang' => $idBarang,
                        'jumlah' => $qty,
                        'status' => 'Dilaporkan Cabang',
                        'catatan' => "Barang rusak retur dari nota {$transaksi->kode_transaksi} ({$kodeRetur})",
                    ]);

                    RiwayatStok::create([
                        'id_barang' => $idBarang,
                        'id_cabang' => $idCabang,
                        'id_user' => $idKasir,
                        'jumlah' => 0,
                        'stok_akhir' => StokBarang::where('id_cabang', $idCabang)->where('id_barang', $idBarang)->value('stok') ?? 0,
                        'tipe' => 'Retur Rusak',
                        'alasan' => "Retur Rusak dialihkan ke Klaim Distributor ({$kodeRetur})",
                        'referensi_kode' => $kodeRetur,
                    ]);
                }

                $processedDetailReturs[] = [
                    'id_barang' => $idBarang,
                    'jumlah' => $qty,
                    'harga_satuan' => $hargaSatuan,
                    'subtotal' => $subtotal,
                    'kondisi_barang' => $kondisi,
                ];
            }

            // 2. Process Replacement Items (if Tukar Barang)
            $totalNilaiPengganti = 0;
            if ($tipeRetur === 'Tukar Barang' && !empty($itemsPengganti)) {
                foreach ($itemsPengganti as $repItem) {
                    $idBarang = (int) $repItem['id_barang'];
                    $qty = (int) $repItem['jumlah'];
                    $hargaSatuan = (float) $repItem['harga_satuan'];

                    if ($qty <= 0) {
                        continue;
                    }

                    $stokRow = StokBarang::where('id_cabang', $idCabang)
                        ->where('id_barang', $idBarang)
                        ->lockForUpdate()
                        ->first();

                    $currentStok = $stokRow ? (int) $stokRow->stok : 0;
                    if ($currentStok < $qty) {
                        $brg = Barang::find($idBarang);
                        throw new RuntimeException("Stok barang pengganti [{$brg->nama_barang}] tidak mencukupi (sisa: {$currentStok}, diminta: {$qty}).");
                    }

                    $stokRow->stok -= $qty;
                    $stokRow->save();

                    RiwayatStok::create([
                        'id_barang' => $idBarang,
                        'id_cabang' => $idCabang,
                        'id_user' => $idKasir,
                        'jumlah' => -$qty,
                        'stok_akhir' => $stokRow->stok,
                        'tipe' => 'Penjualan',
                        'alasan' => "Barang Pengganti Retur {$kodeRetur}",
                        'referensi_kode' => $kodeRetur,
                    ]);

                    $totalNilaiPengganti += ($qty * $hargaSatuan);
                }
            }

            // 3. Difference Calculation:
            // Selisih = Nilai Pengganti - Nilai Retur
            // If > 0: Customer owes store additional money
            // If < 0: Store owes customer refund / store credit
            $selisihNominal = $totalNilaiPengganti - $totalNilaiRetur;
            $statusPenyelesaian = 'Selesai';

            $retur = Retur::create([
                'kode_retur' => $kodeRetur,
                'id_transaksi' => $transaksi->id,
                'id_cabang' => $idCabang,
                'id_user' => $idKasir,
                'tipe_retur' => $tipeRetur,
                'total_nilai_retur' => $totalNilaiRetur,
                'total_nilai_pengganti' => $totalNilaiPengganti,
                'selisih_nominal' => $selisihNominal,
                'status_penyelesaian' => $statusPenyelesaian,
                'keterangan' => $keterangan,
            ]);

            foreach ($processedDetailReturs as $pdr) {
                $pdr['id_retur'] = $retur->id;
                DetailRetur::create($pdr);
            }

            // 4. Financial & Cash Handling
            if ($selisihNominal < 0) {
                // Store owes refund: absolute value
                $nominalRefund = abs($selisihNominal);
                $caraRefund = $data['cara_refund'] ?? 'Tunai'; // Tunai, Saldo, Potong Piutang

                if ($caraRefund === 'Saldo' && $transaksi->id_pembeli) {
                    $pembeli = Pembeli::lockForUpdate()->find($transaksi->id_pembeli);
                    if ($pembeli) {
                        $pembeli->saldo += $nominalRefund;
                        $pembeli->save();
                    }
                } elseif ($caraRefund === 'Potong Piutang' && $transaksi->sisa_piutang > 0) {
                    $potongan = min($nominalRefund, (float) $transaksi->sisa_piutang);
                    $transaksi->sisa_piutang -= $potongan;
                    if ($transaksi->sisa_piutang == 0) {
                        $transaksi->status = 'Lunas';
                    }
                    $transaksi->save();
                } else {
                    // Physical cash refund out of branch petty cash
                    KasHarian::create([
                        'id_cabang' => $idCabang,
                        'id_user' => $idKasir,
                        'kategori' => 'Operasional Lainnya',
                        'tipe' => 'Keluar',
                        'jumlah' => $nominalRefund,
                        'keterangan' => "Pengembalian Uang Retur Barang ({$kodeRetur})",
                    ]);
                }
            } elseif ($selisihNominal > 0) {
                // Customer paid extra cash into store
                KasHarian::create([
                    'id_cabang' => $idCabang,
                    'id_user' => $idKasir,
                    'kategori' => 'Penjualan Tunai',
                    'tipe' => 'Masuk',
                    'jumlah' => $selisihNominal,
                    'keterangan' => "Tambahan Biaya Tukar Barang Retur ({$kodeRetur})",
                ]);
            }

            return $retur->load(['details.barang', 'transaksi', 'cabang', 'user']);
        });
    }
}
