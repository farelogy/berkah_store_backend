<?php

namespace App\Services;

use App\Models\KasHarian;
use App\Models\KasPusat;
use App\Models\OperasionalPusat;
use App\Models\SetoranKas;
use App\Models\TutupKasir;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class KasOperasionalService
{
    /**
     * Record miscellaneous non-sales cash in.
     */
    public function catatKasMasuk(
        int $idCabang,
        int $userId,
        float $jumlah,
        string $kategori,
        string $keterangan,
        ?string $fotoBukti = null
    ): KasHarian {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException('Nominal kas masuk harus lebih besar dari 0.');
        }

        return KasHarian::create([
            'id_cabang' => $idCabang,
            'id_user' => $userId,
            'kategori' => $kategori,
            'tipe' => 'Masuk',
            'jumlah' => $jumlah,
            'keterangan' => $keterangan,
            'foto_bukti' => $fotoBukti,
        ]);
    }

    /**
     * Record operational cash out (petty cash).
     */
    public function catatKasKeluar(
        int $idCabang,
        int $userId,
        float $jumlah,
        string $kategori,
        string $keterangan,
        ?int $idKaryawan = null,
        ?string $fotoBukti = null
    ): KasHarian {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException('Nominal kas keluar harus lebih besar dari 0.');
        }

        return KasHarian::create([
            'id_cabang' => $idCabang,
            'id_user' => $userId,
            'id_karyawan' => $idKaryawan,
            'kategori' => $kategori,
            'tipe' => 'Keluar',
            'jumlah' => $jumlah,
            'keterangan' => $keterangan,
            'foto_bukti' => $fotoBukti,
        ]);
    }

    /**
     * Record employee meal allowance (Global list of employees).
     */
    public function catatUangMakan(
        int $idCabang,
        int $userId,
        int $idKaryawan,
        float $jumlah,
        ?string $keterangan = null
    ): KasHarian {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException('Nominal uang makan harus lebih besar dari 0.');
        }

        return KasHarian::create([
            'id_cabang' => $idCabang,
            'id_user' => $userId,
            'id_karyawan' => $idKaryawan,
            'kategori' => 'Uang Makan',
            'tipe' => 'Keluar',
            'jumlah' => $jumlah,
            'keterangan' => $keterangan ?: 'Uang makan karyawan',
        ])->load('karyawan');
    }

    /**
     * Branch creates a cash deposit to HQ.
     * Decrements branch petty cash immediately. Does NOT increment Kas Pusat yet.
     */
    public function setorKasKePusat(
        int $idCabang,
        int $userId,
        float $jumlah,
        string $metode = 'Tunai',
        ?string $buktiTransfer = null,
        ?string $catatan = null
    ): SetoranKas {
        if ($jumlah <= 0) {
            throw new InvalidArgumentException('Nominal setoran harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($idCabang, $userId, $jumlah, $metode, $buktiTransfer, $catatan) {
            $kodeSetoran = 'STR-' . $idCabang . '-' . date('YmdHis') . '-' . strtoupper(Str::random(4));

            $setoran = SetoranKas::create([
                'kode_setoran' => $kodeSetoran,
                'id_cabang' => $idCabang,
                'id_user_penyetor' => $userId,
                'jumlah' => $jumlah,
                'metode' => $metode,
                'bukti_transfer' => $buktiTransfer,
                'status' => 'Menunggu Konfirmasi',
                'catatan' => $catatan,
            ]);

            // Decrement branch cash
            KasHarian::create([
                'id_cabang' => $idCabang,
                'id_user' => $userId,
                'kategori' => 'Setoran Kas',
                'tipe' => 'Keluar',
                'jumlah' => $jumlah,
                'keterangan' => "Setoran kas cabang ke pusat ({$kodeSetoran})",
                'foto_bukti' => $buktiTransfer,
            ]);

            return $setoran->load(['cabang', 'userPenyetor']);
        });
    }

    /**
     * Admin Pusat verifies and confirms branch cash deposit.
     * Only at this moment is Kas Pusat incremented!
     */
    public function konfirmasiSetoranKas(
        int $idSetoran,
        int $adminPusatUserId,
        bool $isApproved,
        ?string $catatan = null
    ): SetoranKas {
        return DB::transaction(function () use ($idSetoran, $adminPusatUserId, $isApproved, $catatan) {
            $setoran = SetoranKas::lockForUpdate()->findOrFail($idSetoran);

            if ($setoran->status !== 'Menunggu Konfirmasi') {
                throw new RuntimeException("Setoran kas sudah berstatus [{$setoran->status}].");
            }

            if ($isApproved) {
                // Increment Kas Pusat
                $kasPusat = KasPusat::firstOrCreate(['id' => 1], ['saldo' => 0]);
                $kasPusat = KasPusat::where('id', $kasPusat->id)->lockForUpdate()->first();
                $kasPusat->saldo += $setoran->jumlah;
                $kasPusat->save();

                // Log into operasional pusats
                OperasionalPusat::create([
                    'id_user' => $adminPusatUserId,
                    'kategori' => 'Setoran Kas Cabang',
                    'keterangan' => "Penerimaan setoran dari Cabang #{$setoran->id_cabang} ({$setoran->kode_setoran})",
                    'jumlah' => $setoran->jumlah,
                    'status' => 'Masuk',
                    'bukti_foto' => $setoran->bukti_transfer,
                ]);

                $setoran->status = 'Terverifikasi';
                $setoran->confirmed_at = now();
                $setoran->id_user_penerima = $adminPusatUserId;
            } else {
                // Rejected: reverse branch cash decrement
                KasHarian::create([
                    'id_cabang' => $setoran->id_cabang,
                    'id_user' => $adminPusatUserId,
                    'kategori' => 'Operasional Lainnya',
                    'tipe' => 'Masuk',
                    'jumlah' => $setoran->jumlah,
                    'keterangan' => "Koreksi: Setoran {$setoran->kode_setoran} ditolak pusat" . ($catatan ? " ({$catatan})" : ''),
                ]);

                $setoran->status = 'Ditolak';
            }

            if ($catatan) {
                $setoran->catatan = ($setoran->catatan ? $setoran->catatan . ' | Pusat: ' : 'Pusat: ') . $catatan;
            }
            $setoran->save();

            return $setoran->load(['cabang', 'userPenyetor', 'userPenerima']);
        });
    }

    /**
     * Daily branch cashier closing and physical cash count reconciliation.
     */
    public function tutupKasirHarian(
        int $idCabang,
        int $userId,
        float $saldoFisikRiil,
        ?string $catatan = null
    ): TutupKasir {
        $today = Carbon::today()->toDateString();

        return DB::transaction(function () use ($idCabang, $userId, $saldoFisikRiil, $catatan, $today) {
            // Find previous closing balance as saldo awal
            $lastClosing = TutupKasir::where('id_cabang', $idCabang)
                ->where('tanggal', '<', $today)
                ->orderBy('tanggal', 'desc')
                ->first();

            $saldoAwal = $lastClosing ? (float) $lastClosing->saldo_fisik_riil : 0;

            // Calculate today's in and out
            $totalMasuk = (float) KasHarian::where('id_cabang', $idCabang)
                ->whereDate('created_at', $today)
                ->where('tipe', 'Masuk')
                ->sum('jumlah');

            $totalKeluar = (float) KasHarian::where('id_cabang', $idCabang)
                ->whereDate('created_at', $today)
                ->where('tipe', 'Keluar')
                ->sum('jumlah');

            $saldoSistem = $saldoAwal + $totalMasuk - $totalKeluar;
            $selisih = $saldoFisikRiil - $saldoSistem;

            return TutupKasir::updateOrCreate(
                [
                    'id_cabang' => $idCabang,
                    'tanggal' => $today,
                ],
                [
                    'id_user' => $userId,
                    'saldo_awal' => $saldoAwal,
                    'total_masuk' => $totalMasuk,
                    'total_keluar' => $totalKeluar,
                    'saldo_sistem' => $saldoSistem,
                    'saldo_fisik_riil' => $saldoFisikRiil,
                    'selisih' => $selisih,
                    'catatan_selisih' => $catatan,
                ]
            );
        });
    }

    /**
     * Get branch real-time cash summary for today.
     */
    public function getRingkasanKasCabang(int $idCabang, ?string $tanggal = null): array
    {
        $date = $tanggal ?: Carbon::today()->toDateString();

        $lastClosing = TutupKasir::where('id_cabang', $idCabang)
            ->where('tanggal', '<', $date)
            ->orderBy('tanggal', 'desc')
            ->first();

        $saldoAwal = $lastClosing ? (float) $lastClosing->saldo_fisik_riil : 0;

        $totalMasuk = (float) KasHarian::where('id_cabang', $idCabang)
            ->whereDate('created_at', $date)
            ->where('tipe', 'Masuk')
            ->sum('jumlah');

        $totalKeluar = (float) KasHarian::where('id_cabang', $idCabang)
            ->whereDate('created_at', $date)
            ->where('tipe', 'Keluar')
            ->sum('jumlah');

        $saldoSekarang = $saldoAwal + $totalMasuk - $totalKeluar;

        $closingHariIni = TutupKasir::where('id_cabang', $idCabang)
            ->where('tanggal', $date)
            ->first();

        return [
            'tanggal' => $date,
            'id_cabang' => $idCabang,
            'saldo_awal' => $saldoAwal,
            'total_masuk_hari_ini' => $totalMasuk,
            'total_keluar_hari_ini' => $totalKeluar,
            'saldo_kas_saat_ini' => $saldoSekarang,
            'status_closing' => $closingHariIni ? 'Sudah Tutup Kasir' : 'Belum Tutup Kasir',
            'detail_closing' => $closingHariIni,
        ];
    }
}
