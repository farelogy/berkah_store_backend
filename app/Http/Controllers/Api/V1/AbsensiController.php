<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AbsensiKaryawan;
use App\Models\Karyawan;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbsensiController extends Controller
{
    use ApiResponse;

    /**
     * List absensi with filters (tanggal, bulan, tahun, id_cabang, id_karyawan).
     */
    public function index(Request $request): JsonResponse
    {
        $query = AbsensiKaryawan::with(['karyawan', 'cabang', 'userInput']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('bulan') && $request->filled('tahun')) {
            $query->whereMonth('tanggal', $request->bulan)
                  ->whereYear('tanggal', $request->tahun);
        }

        if ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('id_karyawan')) {
            $query->where('id_karyawan', $request->id_karyawan);
        }

        $absensis = $query->orderBy('tanggal', 'desc')->get();

        return $this->successResponse($absensis);
    }

    /**
     * Batch save / update absensi for a specific date across employees.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $request->validate([
            'tanggal' => 'required|date',
            'id_cabang' => 'nullable|integer|exists:cabangs,id',
            'items' => 'required|array|min:1',
            'items.*.id_karyawan' => 'required|integer|exists:karyawans,id',
            'items.*.status' => 'required|in:Masuk,Setengah Hari,Tidak Masuk',
            'items.*.keterangan' => 'nullable|string|max:255',
        ]);

        $tanggal = $request->tanggal;
        $idUserInput = $request->user()->id;
        $idCabang = $request->id_cabang;

        return DB::transaction(function () use ($request, $tanggal, $idUserInput, $idCabang) {
            $saved = [];

            foreach ($request->items as $item) {
                $status = $item['status'];
                $nilaiHari = match ($status) {
                    'Masuk' => 1.0,
                    'Setengah Hari' => 0.5,
                    'Tidak Masuk' => 0.0,
                    default => 1.0,
                };

                $record = AbsensiKaryawan::updateOrCreate(
                    [
                        'id_karyawan' => $item['id_karyawan'],
                        'tanggal' => $tanggal,
                    ],
                    [
                        'id_cabang' => $idCabang,
                        'id_user_input' => $idUserInput,
                        'status' => $status,
                        'nilai_hari' => $nilaiHari,
                        'keterangan' => $item['keterangan'] ?? null,
                    ]
                );

                $saved[] = $record->load('karyawan');
            }

            return $this->successResponse($saved, 'Data absensi tanggal ' . $tanggal . ' berhasil disimpan.');
        });
    }

    /**
     * Get monthly attendance summary per employee.
     */
    public function monthlySummary(Request $request): JsonResponse
    {
        $request->validate([
            'bulan' => 'required|integer|min:1|max:12',
            'tahun' => 'required|integer|min:2020|max:2050',
            'id_cabang' => 'nullable|integer',
        ]);

        $bulan = (int) $request->bulan;
        $tahun = (int) $request->tahun;

        $karyawanQuery = Karyawan::where('is_active', true);
        if ($request->filled('id_cabang')) {
            $karyawanQuery->where('id_cabang', $request->id_cabang);
        }

        $karyawans = $karyawanQuery->get();

        $summary = $karyawans->map(function ($k) use ($bulan, $tahun) {
            $records = AbsensiKaryawan::where('id_karyawan', $k->id)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->get();

            $totalMasuk = $records->where('status', 'Masuk')->count();
            $totalSetengah = $records->where('status', 'Setengah Hari')->count();
            $totalAbsen = $records->where('status', 'Tidak Masuk')->count();
            $totalHariKerja = (float) $records->sum('nilai_hari');

            return [
                'id_karyawan' => $k->id,
                'nama_karyawan' => $k->nama_karyawan,
                'jabatan' => $k->jabatan,
                'gaji_harian' => (float) $k->gaji_harian,
                'total_masuk' => $totalMasuk,
                'total_setengah_hari' => $totalSetengah,
                'total_tidak_masuk' => $totalAbsen,
                'total_hari_kerja' => $totalHariKerja,
            ];
        });

        return $this->successResponse($summary);
    }
}
