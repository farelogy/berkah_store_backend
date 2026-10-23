<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Cabang;
use App\Models\Distributor;
use App\Models\KasHarian;
use App\Models\KasPusat;
use App\Models\Pembeli;
use App\Models\StokBarang;
use App\Models\Transaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DireksiAnalyticsService
{
    /**
     * Get high-level executive financial metrics for Direksi / Owner.
     */
    public function getExecutiveOverview(): array
    {
        $today = Carbon::today()->toDateString();

        // 1. Kas Pusat
        $saldoKasPusat = (float) (KasPusat::value('saldo') ?? 0);

        // 2. Real Cash per Branch (Current Cash on Hand)
        $cabangs = Cabang::where('is_active', true)->get();
        $kasPerCabang = [];
        $totalKasCabang = 0;

        foreach ($cabangs as $cabang) {
            $totalMasuk = (float) KasHarian::where('id_cabang', $cabang->id)->where('tipe', 'Masuk')->sum('jumlah');
            $totalKeluar = (float) KasHarian::where('id_cabang', $cabang->id)->where('tipe', 'Keluar')->sum('jumlah');
            $saldoKas = max(0, $totalMasuk - $totalKeluar);

            $penjualanHariIni = (float) Transaksi::where('id_cabang', $cabang->id)
                ->whereDate('created_at', $today)
                ->where('status', '!=', 'Batal')
                ->sum('total_tagihan_akhir');

            $kasPerCabang[] = [
                'id_cabang' => $cabang->id,
                'nama_cabang' => $cabang->nama_cabang,
                'saldo_kas_saat_ini' => $saldoKas,
                'penjualan_hari_ini' => $penjualanHariIni,
            ];

            $totalKasCabang += $saldoKas;
        }

        // 3. Receivables & Payables
        $totalPiutang = (float) Pembeli::sum('sisa_piutang');
        $totalUtangSupplier = (float) Distributor::sum('total_utang');

        // 4. Inventory Asset Valuation (stok * modal)
        $totalNilaiAsetStok = (float) DB::table('stok_barangs')
            ->join('barangs', 'stok_barangs.id_barang', '=', 'barangs.id')
            ->selectRaw('SUM(stok_barangs.stok * barangs.modal) as total_aset')
            ->value('total_aset') ?? 0;

        // 5. Today's Performance (Sales & Net Profit Snapshot)
        $penjualanHariIni = (float) Transaksi::whereDate('created_at', $today)
            ->where('status', '!=', 'Batal')
            ->sum('total_tagihan_akhir');

        // Accurate Gross Profit using immutable snapshot
        $labaKotorHariIni = (float) DB::table('detail_transaksis')
            ->join('transaksis', 'detail_transaksis.id_transaksi', '=', 'transaksis.id')
            ->whereDate('transaksis.created_at', $today)
            ->where('transaksis.status', '!=', 'Batal')
            ->selectRaw('SUM((detail_transaksis.harga_satuan - detail_transaksis.modal_satuan_snapshot) * (detail_transaksis.jumlah - detail_transaksis.jumlah_direfund)) as gross_profit')
            ->value('gross_profit') ?? 0;

        // Operating petty cash expenses (excluding internal setoran kas)
        $biayaOperasionalHariIni = (float) KasHarian::whereDate('created_at', $today)
            ->where('tipe', 'Keluar')
            ->where('kategori', '!=', 'Setoran Kas')
            ->sum('jumlah');

        $labaBersihHariIni = $labaKotorHariIni - $biayaOperasionalHariIni;

        return [
            'tanggal' => $today,
            'ringkasan_kas' => [
                'total_likuiditas_toko' => $totalKasCabang + $saldoKasPusat,
                'kas_pusat' => $saldoKasPusat,
                'total_kas_cabang' => $totalKasCabang,
                'detail_kas_per_cabang' => $kasPerCabang,
            ],
            'piutang_dan_utang' => [
                'total_piutang_pelanggan' => $totalPiutang,
                'total_utang_distributor' => $totalUtangSupplier,
                'net_posisi_keuangan' => ($totalKasCabang + $saldoKasPusat + $totalPiutang) - $totalUtangSupplier,
            ],
            'aset' => [
                'total_nilai_aset_stok' => $totalNilaiAsetStok,
            ],
            'performa_hari_ini' => [
                'omset_penjualan' => $penjualanHariIni,
                'laba_kotor' => $labaKotorHariIni,
                'biaya_operasional' => $biayaOperasionalHariIni,
                'laba_bersih' => $labaBersihHariIni,
            ],
        ];
    }

    /**
     * Get Top 5 debtors with highest outstanding balances for immediate follow-up.
     */
    public function getTopDebtors(int $limit = 5): array
    {
        $topDebtors = Pembeli::with('cabang')
            ->where('sisa_piutang', '>', 0)
            ->orderBy('sisa_piutang', 'desc')
            ->limit($limit)
            ->get();

        $results = [];
        foreach ($topDebtors as $d) {
            $oldestInvoice = Transaksi::where('id_pembeli', $d->id)
                ->where('status', 'Belum Lunas')
                ->where('sisa_piutang', '>', 0)
                ->orderBy('created_at', 'asc')
                ->first();

            $hariMenunggak = $oldestInvoice ? Carbon::parse($oldestInvoice->created_at)->diffInDays(now()) : 0;
            $jumlahNotaUnpaid = Transaksi::where('id_pembeli', $d->id)
                ->where('status', 'Belum Lunas')
                ->where('sisa_piutang', '>', 0)
                ->count();

            $results[] = [
                'id_pembeli' => $d->id,
                'nama_pembeli' => $d->nama_pembeli,
                'no_hp' => $d->no_hp,
                'alamat' => $d->alamat,
                'cabang_terdaftar' => $d->cabang ? $d->cabang->nama_cabang : 'Pusat',
                'total_piutang' => (float) $d->sisa_piutang,
                'saldo_toko' => (float) $d->saldo,
                'jumlah_nota_belum_lunas' => $jumlahNotaUnpaid,
                'tanggal_nota_tertua' => $oldestInvoice ? $oldestInvoice->created_at->format('Y-m-d') : null,
                'hari_menunggak' => $hariMenunggak,
                'urgensi' => $hariMenunggak > 30 ? 'Kritis (>30 Hari)' : ($hariMenunggak > 14 ? 'Perlu Ditagih (>14 Hari)' : 'Normal'),
            ];
        }

        return $results;
    }

    /**
     * Generate dynamic, actionable executive smart insights in narrative form.
     */
    public function getSmartInsights(): array
    {
        $overview = $this->getExecutiveOverview();
        $topDebtors = $this->getTopDebtors(1);
        $insights = [];

        // 1. Debt collection priority
        if (!empty($topDebtors)) {
            $top = $topDebtors[0];
            $formattedNominal = 'Rp ' . number_format($top['total_piutang'], 0, ',', '.');
            $insights[] = [
                'kategori' => 'Piutang',
                'tipe' => 'Warning',
                'pesan' => "Penagihan paling prioritas adalah pelanggan {$top['nama_pembeli']} dengan tunggakan {$formattedNominal} ({$top['hari_menunggak']} hari menunggak).",
            ];
        } else {
            $insights[] = [
                'kategori' => 'Piutang',
                'tipe' => 'Success',
                'pesan' => 'Semua piutang pelanggan dalam kondisi lunas / nihil risiko.',
            ];
        }

        // 2. Branch Cash concentration
        $kasCabangList = $overview['ringkasan_kas']['detail_kas_per_cabang'];
        if (!empty($kasCabangList)) {
            usort($kasCabangList, fn($a, $b) => $b['saldo_kas_saat_ini'] <=> $a['saldo_kas_saat_ini']);
            $highestCashBranch = $kasCabangList[0];
            if ($highestCashBranch['saldo_kas_saat_ini'] > 2000000) {
                $formattedKas = 'Rp ' . number_format($highestCashBranch['saldo_kas_saat_ini'], 0, ',', '.');
                $insights[] = [
                    'kategori' => 'Kas Cabang',
                    'tipe' => 'Info',
                    'pesan' => "Saldo kas fisik di {$highestCashBranch['nama_cabang']} cukup tinggi ({$formattedKas}). Admin cabang disarankan menyetorkan kas ke Pusat.",
                ];
            }
        }

        // 3. Profitability insight
        $laba = $overview['performa_hari_ini']['laba_bersih'];
        $omset = $overview['performa_hari_ini']['omset_penjualan'];
        $formattedLaba = 'Rp ' . number_format($laba, 0, ',', '.');
        $formattedOmset = 'Rp ' . number_format($omset, 0, ',', '.');

        if ($omset > 0) {
            $margin = round(($laba / $omset) * 100, 1);
            $insights[] = [
                'kategori' => 'Laba Bersih',
                'tipe' => 'Success',
                'pesan' => "Omset toko hari ini mencapai {$formattedOmset} dengan estimasi laba bersih {$formattedLaba} (margin ~{$margin}%).",
            ];
        }

        // 4. Asset valuation
        $aset = $overview['aset']['total_nilai_aset_stok'];
        $formattedAset = 'Rp ' . number_format($aset, 0, ',', '.');
        $insights[] = [
            'kategori' => 'Aset Stok',
            'tipe' => 'Neutral',
            'pesan' => "Total modal persediaan barang yang tersebar di seluruh cabang & gudang bernilai {$formattedAset}.",
        ];

        return $insights;
    }
}
