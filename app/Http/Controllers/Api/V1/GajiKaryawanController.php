<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AbsensiKaryawan;
use App\Models\GajiKaryawan;
use App\Models\Karyawan;
use App\Models\KasHarian;
use App\Models\KasbonKaryawan;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GajiKaryawanController extends Controller
{
    use ApiResponse;

    /**
     * Calculate live payroll drafts or list existing payrolls for a given month and year.
     */
    public function calculateDraft(Request $request): JsonResponse
    {
        $bulan = (int) $request->input('bulan', date('n'));
        $tahun = (int) $request->input('tahun', date('Y'));
        $idCabang = $request->filled('id_cabang') ? (int) $request->id_cabang : null;

        $karyawanQuery = Karyawan::with(['cabang'])->where('is_active', true);
        if ($idCabang) {
            $karyawanQuery->where('id_cabang', $idCabang);
        }

        $karyawans = $karyawanQuery->orderBy('nama_karyawan', 'asc')->get();

        $results = [];
        $grandTotalKotor = 0;
        $grandTotalUangMakan = 0;
        $grandTotalKasbon = 0;
        $grandTotalGajiBersih = 0;

        foreach ($karyawans as $k) {
            // Check if already finalized
            $existing = GajiKaryawan::with(['userAdmin', 'cabang'])
                ->where('id_karyawan', $k->id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->first();

            if ($existing && $existing->status === 'Dibayarkan') {
                $results[] = [
                    'id_gaji' => $existing->id,
                    'kode_gaji' => $existing->kode_gaji,
                    'id_karyawan' => $k->id,
                    'nama_karyawan' => $k->nama_karyawan,
                    'jabatan' => $k->jabatan,
                    'id_cabang' => $existing->id_cabang ?: $k->id_cabang,
                    'nama_cabang' => $existing->cabang?->nama_cabang ?: ($k->cabang?->nama_cabang ?: 'Sentral / Umum'),
                    'gaji_harian' => (float) $existing->gaji_harian,
                    'total_hari_kerja' => (float) $existing->total_hari_kerja,
                    'total_gaji_kotor' => (float) $existing->total_gaji_kotor,
                    'total_uang_makan' => (float) $existing->total_uang_makan,
                    'total_kasbon' => (float) $existing->total_kasbon,
                    'bonus_tambahan' => (float) $existing->bonus_tambahan,
                    'total_gaji_bersih' => (float) $existing->total_gaji_bersih,
                    'status' => 'Dibayarkan',
                    'tanggal_bayar' => $existing->tanggal_bayar?->format('Y-m-d H:i:s'),
                    'metode_bayar' => $existing->metode_bayar,
                    'catatan' => $existing->catatan,
                ];

                $grandTotalKotor += (float) $existing->total_gaji_kotor;
                $grandTotalUangMakan += (float) $existing->total_uang_makan;
                $grandTotalKasbon += (float) $existing->total_kasbon;
                $grandTotalGajiBersih += (float) $existing->total_gaji_bersih;
                continue;
            }

            // Calculate live draft
            $gajiHarian = (float) $k->gaji_harian;

            // 1. Akumulasi hari kerja dari absensi
            $totalHariKerja = (float) AbsensiKaryawan::where('id_karyawan', $k->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->sum('nilai_hari');

            $totalGajiKotor = $totalHariKerja * $gajiHarian;

            // 2. Akumulasi uang makan dari kas harian cabang
            $totalUangMakan = (float) KasHarian::where('id_karyawan', $k->id)
                ->where('kategori', 'Uang Makan')
                ->whereMonth('created_at', $bulan)
                ->whereYear('created_at', $tahun)
                ->sum('jumlah');

            // 3. Akumulasi sisa kasbon yang aktif
            $totalKasbon = (float) KasbonKaryawan::where('id_karyawan', $k->id)
                ->where('status', 'Belum Lunas')
                ->sum('sisa_kasbon');

            // Maximum deduction of kasbon cannot exceed remainder of gross minus meal allowance
            $potonganKasbon = min($totalKasbon, max(0, $totalGajiKotor - $totalUangMakan));

            $bonusTambahan = $existing ? (float) $existing->bonus_tambahan : 0;
            $totalGajiBersih = max(0, $totalGajiKotor - $totalUangMakan - $potonganKasbon + $bonusTambahan);

            $results[] = [
                'id_gaji' => $existing?->id,
                'kode_gaji' => $existing?->kode_gaji,
                'id_karyawan' => $k->id,
                'nama_karyawan' => $k->nama_karyawan,
                'jabatan' => $k->jabatan,
                'id_cabang' => $k->id_cabang,
                'nama_cabang' => $k->cabang?->nama_cabang ?: 'Sentral / Umum',
                'gaji_harian' => $gajiHarian,
                'total_hari_kerja' => $totalHariKerja,
                'total_gaji_kotor' => $totalGajiKotor,
                'total_uang_makan' => $totalUangMakan,
                'total_kasbon' => $potonganKasbon,
                'total_sisa_kasbon_asli' => $totalKasbon,
                'bonus_tambahan' => $bonusTambahan,
                'total_gaji_bersih' => $totalGajiBersih,
                'status' => 'Draft',
                'tanggal_bayar' => null,
                'metode_bayar' => 'Tunai',
                'catatan' => $existing?->catatan,
            ];

            $grandTotalKotor += $totalGajiKotor;
            $grandTotalUangMakan += $totalUangMakan;
            $grandTotalKasbon += $potonganKasbon;
            $grandTotalGajiBersih += $totalGajiBersih;
        }

        return $this->successResponse([
            'bulan' => $bulan,
            'tahun' => $tahun,
            'id_cabang' => $idCabang,
            'summary' => [
                'total_karyawan' => count($results),
                'total_gaji_kotor' => $grandTotalKotor,
                'total_uang_makan' => $grandTotalUangMakan,
                'total_kasbon_dipotong' => $grandTotalKasbon,
                'total_gaji_bersih' => $grandTotalGajiBersih,
            ],
            'payrolls' => $results,
        ]);
    }

    /**
     * Finalize & Pay salary for an employee. Deducts kasbon accordingly.
     */
    public function bayarGaji(Request $request): JsonResponse
    {
        $request->validate([
            'id_karyawan' => 'required|integer|exists:karyawans,id',
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2050',
            'metode_bayar' => 'required|string|in:Tunai,Transfer Bank',
            'bonus_tambahan' => 'nullable|numeric|min:0',
            'potong_kasbon' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string|max:500',
        ]);

        $idKaryawan = (int) $request->id_karyawan;
        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        return DB::transaction(function () use ($request, $idKaryawan, $bulan, $tahun) {
            $karyawan = Karyawan::findOrFail($idKaryawan);
            $idCabang = $karyawan->id_cabang;

            $gajiHarian = (float) $karyawan->gaji_harian;

            $totalHariKerja = (float) AbsensiKaryawan::where('id_karyawan', $idKaryawan)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->sum('nilai_hari');

            $totalGajiKotor = $totalHariKerja * $gajiHarian;

            $totalUangMakan = (float) KasHarian::where('id_karyawan', $idKaryawan)
                ->where('kategori', 'Uang Makan')
                ->whereMonth('created_at', $bulan)
                ->whereYear('created_at', $tahun)
                ->sum('jumlah');

            $totalKasbonTersedia = (float) KasbonKaryawan::where('id_karyawan', $idKaryawan)
                ->where('status', 'Belum Lunas')
                ->sum('sisa_kasbon');

            $dimintaPotongKasbon = $request->filled('potong_kasbon')
                ? (float) $request->potong_kasbon
                : min($totalKasbonTersedia, max(0, $totalGajiKotor - $totalUangMakan));

            $potonganKasbon = min($totalKasbonTersedia, $dimintaPotongKasbon);

            $bonusTambahan = (float) $request->input('bonus_tambahan', 0);
            $totalGajiBersih = max(0, $totalGajiKotor - $totalUangMakan - $potonganKasbon + $bonusTambahan);

            // Deduct Kasbon records
            if ($potonganKasbon > 0) {
                $remainingDeduction = $potonganKasbon;
                $activeKasbons = KasbonKaryawan::where('id_karyawan', $idKaryawan)
                    ->where('status', 'Belum Lunas')
                    ->orderBy('tanggal', 'asc')
                    ->get();

                foreach ($activeKasbons as $kasbon) {
                    if ($remainingDeduction <= 0) break;

                    if ($kasbon->sisa_kasbon <= $remainingDeduction) {
                        $remainingDeduction -= $kasbon->sisa_kasbon;
                        $kasbon->sisa_kasbon = 0;
                        $kasbon->status = 'Lunas';
                        $kasbon->save();
                    } else {
                        $kasbon->sisa_kasbon -= $remainingDeduction;
                        $remainingDeduction = 0;
                        $kasbon->save();
                    }
                }
            }

            $kodeGaji = 'SLIP-' . $tahun . str_pad($bulan, 2, '0', STR_PAD_LEFT) . '-' . $idKaryawan . '-' . strtoupper(Str::random(4));

            $gaji = GajiKaryawan::updateOrCreate(
                [
                    'id_karyawan' => $idKaryawan,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                [
                    'kode_gaji' => $kodeGaji,
                    'id_cabang' => $idCabang,
                    'id_user_admin' => $request->user()->id,
                    'gaji_harian' => $gajiHarian,
                    'total_hari_kerja' => $totalHariKerja,
                    'total_gaji_kotor' => $totalGajiKotor,
                    'total_uang_makan' => $totalUangMakan,
                    'total_kasbon' => $potonganKasbon,
                    'bonus_tambahan' => $bonusTambahan,
                    'total_gaji_bersih' => $totalGajiBersih,
                    'status' => 'Dibayarkan',
                    'tanggal_bayar' => now(),
                    'metode_bayar' => $request->metode_bayar,
                    'catatan' => $request->catatan,
                ]
            );

            return $this->successResponse(
                $gaji->load(['karyawan', 'cabang', 'userAdmin']),
                "Gaji pegawai {$karyawan->nama_karyawan} berhasil dibayarkan & slip gaji tercatat."
            );
        });
    }

    /**
     * Get detailed slip gaji.
     */
    public function slip(int $id): JsonResponse
    {
        $gaji = GajiKaryawan::with(['karyawan', 'cabang', 'userAdmin'])->findOrFail($id);
        return $this->successResponse($gaji);
    }
}
