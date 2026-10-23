<?php

namespace App\Services;

use App\Models\DetailNotaDistributor;
use App\Models\Distributor;
use App\Models\KasPusat;
use App\Models\NotaDistributor;
use App\Models\OperasionalPusat;
use App\Models\PembayaranDistributor;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DistributorService
{
    /**
     * Create new distributor profile.
     */
    public function createDistributor(array $data): Distributor
    {
        return Distributor::create([
            'nama_distributor' => $data['nama_distributor'],
            'kontak_person' => $data['kontak_person'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'total_utang' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Input invoice/nota from distributor (Admin Pusat).
     * Item names are decoupled from store catalog to match supplier paper invoice.
     */
    public function inputNota(array $data, int $userId): NotaDistributor
    {
        return DB::transaction(function () use ($data, $userId) {
            $idDistributor = (int) $data['id_distributor'];
            $distributor = Distributor::lockForUpdate()->findOrFail($idDistributor);

            $nomorNota = trim($data['nomor_nota']);
            if (NotaDistributor::where('nomor_nota', $nomorNota)->exists()) {
                throw new InvalidArgumentException("Nomor nota [{$nomorNota}] sudah pernah diinput sebelumnya.");
            }

            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw new InvalidArgumentException('Daftar barang pada nota supplier tidak boleh kosong.');
            }

            $totalTagihan = 0;
            $preparedDetails = [];

            foreach ($items as $item) {
                $qty = (int) $item['jumlah'];
                $hargaBeli = (float) $item['harga_beli_satuan'];
                if ($qty <= 0 || $hargaBeli < 0) {
                    throw new InvalidArgumentException('Jumlah dan harga beli barang pada nota harus valid.');
                }

                $subtotal = $qty * $hargaBeli;
                $totalTagihan += $subtotal;

                $preparedDetails[] = [
                    'nama_barang_nota' => $item['nama_barang_nota'],
                    'jumlah' => $qty,
                    'satuan' => $item['satuan'] ?? 'Pcs',
                    'harga_beli_satuan' => $hargaBeli,
                    'subtotal' => $subtotal,
                ];
            }

            $nota = NotaDistributor::create([
                'nomor_nota' => $nomorNota,
                'id_distributor' => $idDistributor,
                'tanggal_nota' => $data['tanggal_nota'] ?? now()->toDateString(),
                'total_tagihan' => $totalTagihan,
                'total_dibayar' => 0,
                'sisa_utang' => $totalTagihan,
                'status' => 'Belum Lunas',
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($preparedDetails as $pd) {
                $pd['id_nota_distributor'] = $nota->id;
                DetailNotaDistributor::create($pd);
            }

            $distributor->total_utang += $totalTagihan;
            $distributor->save();

            return $nota->load(['distributor', 'details']);
        });
    }

    /**
     * Pay supplier invoice.
     * Automatically and atomically deducts Kas Pusat!
     */
    public function bayarNota(
        int $idNota,
        float $jumlahBayar,
        string $metode,
        int $userId,
        ?string $bukti = null,
        ?string $catatan = null
    ): PembayaranDistributor {
        if ($jumlahBayar <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran distributor harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($idNota, $jumlahBayar, $metode, $userId, $bukti, $catatan) {
            $nota = NotaDistributor::lockForUpdate()->findOrFail($idNota);
            $distributor = Distributor::lockForUpdate()->findOrFail($nota->id_distributor);

            if ($nota->status === 'Lunas') {
                throw new RuntimeException("Nota [{$nota->nomor_nota}] sudah berstatus Lunas.");
            }

            if ($jumlahBayar > (float) $nota->sisa_utang) {
                throw new InvalidArgumentException("Nominal pembayaran melebihi sisa utang nota (sisa: {$nota->sisa_utang}, bayar: {$jumlahBayar}).");
            }

            // 1. Check and deduct Kas Pusat
            $kasPusat = KasPusat::firstOrCreate(['id' => 1], ['saldo' => 0]);
            $kasPusat = KasPusat::where('id', $kasPusat->id)->lockForUpdate()->first();

            if ((float) $kasPusat->saldo < $jumlahBayar) {
                throw new RuntimeException("Saldo Kas Pusat tidak mencukupi untuk pembayaran ini (tersedia: {$kasPusat->saldo}, diminta: {$jumlahBayar}).");
            }

            $kasPusat->saldo -= $jumlahBayar;
            $kasPusat->save();

            // 2. Record Central Cash Outflow
            OperasionalPusat::create([
                'id_user' => $userId,
                'kategori' => 'Pembayaran Distributor',
                'keterangan' => "Bayar Hutang Nota {$nota->nomor_nota} [{$distributor->nama_distributor}]",
                'jumlah' => $jumlahBayar,
                'status' => 'Keluar',
                'bukti_foto' => $bukti,
            ]);

            // 3. Update Nota and Distributor Balances
            $nota->total_dibayar += $jumlahBayar;
            $nota->sisa_utang -= $jumlahBayar;
            if ($nota->sisa_utang <= 0) {
                $nota->status = 'Lunas';
                $nota->paid_at = now();
            } else {
                $nota->status = 'Sebagian';
            }
            $nota->save();

            $distributor->total_utang = max(0, $distributor->total_utang - $jumlahBayar);
            $distributor->save();

            // 4. Record Payment Receipt
            $pembayaran = PembayaranDistributor::create([
                'id_nota_distributor' => $nota->id,
                'id_user' => $userId,
                'jumlah_bayar' => $jumlahBayar,
                'tanggal_bayar' => now()->toDateString(),
                'metode_pembayaran' => $metode,
                'bukti_bayar' => $bukti,
                'catatan' => $catatan,
            ]);

            return $pembayaran->load(['nota.distributor', 'user']);
        });
    }
}
