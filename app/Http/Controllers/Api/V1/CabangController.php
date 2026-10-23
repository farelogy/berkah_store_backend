<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CabangController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $cabangs = Cabang::where('is_active', true)->get();
        return $this->successResponse($cabangs);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama_cabang' => 'required|string|max:100|unique:cabangs,nama_cabang',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:30',
        ]);

        $cabang = Cabang::create([
            'nama_cabang' => $request->nama_cabang,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'is_active' => true,
        ]);

        return $this->successResponse($cabang, 'Cabang berhasil ditambahkan', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $cabang = Cabang::findOrFail($id);

        $request->validate([
            'nama_cabang' => "required|string|max:100|unique:cabangs,nama_cabang,{$id}",
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        $cabang->update($request->only(['nama_cabang', 'alamat', 'no_telepon', 'is_active']));
        return $this->successResponse($cabang, 'Data cabang berhasil diperbarui');
    }
}
