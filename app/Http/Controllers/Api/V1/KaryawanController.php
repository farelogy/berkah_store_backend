<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Karyawan::with(['cabang'])->withCount('kasHarians')->orderBy('nama_karyawan', 'asc');

        if (! $request->boolean('all')) {
            $query->where('is_active', true);
        }

        if ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_karyawan', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%")
                    ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        $karyawans = $query->get();

        return $this->successResponse($karyawans);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:30',
            'gaji_harian' => 'nullable|numeric|min:0',
            'id_cabang' => 'nullable|integer|exists:cabangs,id',
        ]);

        $karyawan = Karyawan::create([
            'nama_karyawan' => $request->nama_karyawan,
            'jabatan' => $request->jabatan ?? 'Umum',
            'no_hp' => $request->no_hp,
            'gaji_harian' => $request->input('gaji_harian', 0),
            'id_cabang' => $request->id_cabang,
            'is_active' => true,
        ]);

        return $this->successResponse($karyawan->load('cabang'), 'Karyawan berhasil ditambahkan', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $karyawan = Karyawan::findOrFail($id);

        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:30',
            'gaji_harian' => 'nullable|numeric|min:0',
            'id_cabang' => 'nullable|integer|exists:cabangs,id',
            'is_active' => 'nullable|boolean',
        ]);

        $karyawan->update($request->only(['nama_karyawan', 'jabatan', 'no_hp', 'gaji_harian', 'id_cabang', 'is_active']));
        return $this->successResponse($karyawan->load('cabang'), 'Data karyawan berhasil diperbarui');
    }

    public function toggleActive(int $id): JsonResponse
    {
        $karyawan = Karyawan::findOrFail($id);
        $karyawan->update(['is_active' => ! $karyawan->is_active]);

        $status = $karyawan->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return $this->successResponse($karyawan, "Pegawai {$karyawan->nama_karyawan} berhasil {$status}.");
    }

    public function destroy(int $id): JsonResponse
    {
        $karyawan = Karyawan::findOrFail($id);
        if ($karyawan->kasHarians()->exists()) {
            $karyawan->update(['is_active' => false]);
            return $this->successResponse($karyawan, "Pegawai {$karyawan->nama_karyawan} memiliki histori kas, status diubah menjadi non-aktif.");
        }

        $karyawan->delete();
        return $this->successResponse(null, "Data pegawai berhasil dihapus.");
    }
}
