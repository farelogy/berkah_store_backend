<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KasbonKaryawan;
use App\Models\Karyawan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KasbonController extends Controller
{
    use ApiResponse;

    /**
     * List kasbon with filters (status, id_karyawan, id_cabang, search).
     */
    public function index(Request $request): JsonResponse
    {
        $query = KasbonKaryawan::with(['karyawan', 'cabang', 'userInput']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('id_karyawan')) {
            $query->where('id_karyawan', $request->id_karyawan);
        }

        if ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_kasbon', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%")
                  ->orWhereHas('karyawan', function ($kq) use ($search) {
                      $kq->where('nama_karyawan', 'like', "%{$search}%");
                  });
            });
        }

        $kasbons = $query->orderBy('tanggal', 'desc')->get();

        $totalKasbonBelumLunas = KasbonKaryawan::where('status', 'Belum Lunas')->sum('sisa_kasbon');

        return $this->successResponse([
            'kasbons' => $kasbons,
            'total_belum_lunas' => (float) $totalKasbonBelumLunas,
        ]);
    }

    /**
     * Store new kasbon created by Admin Pusat.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'id_karyawan' => 'required|integer|exists:karyawans,id',
            'nominal' => 'required|numeric|min:1000',
            'tanggal' => 'required|date',
            'id_cabang' => 'nullable|integer|exists:cabangs,id',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $karyawan = Karyawan::findOrFail($request->id_karyawan);
        $idCabang = $request->id_cabang ?: $karyawan->id_cabang;

        $kodeKasbon = 'KSB-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        $kasbon = KasbonKaryawan::create([
            'kode_kasbon' => $kodeKasbon,
            'id_karyawan' => $karyawan->id,
            'id_cabang' => $idCabang,
            'id_user_input' => $request->user()->id,
            'tanggal' => $request->tanggal,
            'nominal' => $request->nominal,
            'sisa_kasbon' => $request->nominal,
            'status' => 'Belum Lunas',
            'keterangan' => $request->keterangan,
        ]);

        return $this->successResponse($kasbon->load(['karyawan', 'cabang', 'userInput']), 'Kasbon pegawai berhasil dicatat', 201);
    }

    /**
     * Update kasbon (e.g. manual payment or adjust keterangan).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $kasbon = KasbonKaryawan::findOrFail($id);

        $request->validate([
            'nominal' => 'nullable|numeric|min:0',
            'sisa_kasbon' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Belum Lunas,Lunas',
            'keterangan' => 'nullable|string|max:500',
        ]);

        $updateData = $request->only(['status', 'keterangan']);
        if ($request->has('nominal')) {
            $updateData['nominal'] = $request->nominal;
        }
        if ($request->has('sisa_kasbon')) {
            $updateData['sisa_kasbon'] = $request->sisa_kasbon;
            if ((float) $request->sisa_kasbon <= 0) {
                $updateData['status'] = 'Lunas';
            }
        }

        $kasbon->update($updateData);

        return $this->successResponse($kasbon->load(['karyawan', 'cabang']), 'Data kasbon berhasil diperbarui');
    }

    /**
     * Delete kasbon if mistakenly entered.
     */
    public function destroy(int $id): JsonResponse
    {
        $kasbon = KasbonKaryawan::findOrFail($id);
        $kasbon->delete();

        return $this->successResponse(null, 'Data kasbon berhasil dihapus');
    }
}
