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

    public function index(): JsonResponse
    {
        $karyawans = Karyawan::where('is_active', true)->orderBy('nama_karyawan', 'asc')->get();
        return $this->successResponse($karyawans);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:30',
        ]);

        $karyawan = Karyawan::create([
            'nama_karyawan' => $request->nama_karyawan,
            'jabatan' => $request->jabatan ?? 'Umum',
            'no_hp' => $request->no_hp,
            'is_active' => true,
        ]);

        return $this->successResponse($karyawan, 'Karyawan berhasil ditambahkan', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $karyawan = Karyawan::findOrFail($id);

        $request->validate([
            'nama_karyawan' => 'required|string|max:100',
            'jabatan' => 'nullable|string|max:50',
            'no_hp' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        $karyawan->update($request->only(['nama_karyawan', 'jabatan', 'no_hp', 'is_active']));
        return $this->successResponse($karyawan, 'Data karyawan berhasil diperbarui');
    }
}
