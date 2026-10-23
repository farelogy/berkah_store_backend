<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pembeli;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PembeliController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Pembeli::with('cabang');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_pembeli', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        if ($request->filled('id_cabang')) {
            $query->where('id_cabang_pendaftaran', $request->id_cabang);
        }

        $pembelis = $query->orderBy('nama_pembeli', 'asc')->paginate($request->input('per_page', 25));
        return $this->successResponse($pembelis);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama_pembeli' => 'required|string|max:100',
            'no_hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'limit_piutang' => 'nullable|numeric|min:0',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');

        $pembeli = Pembeli::create([
            'nama_pembeli' => $request->nama_pembeli,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'id_cabang_pendaftaran' => $idCabang,
            'saldo' => 0,
            'sisa_piutang' => 0,
            'limit_piutang' => (float) ($request->limit_piutang ?? 5000000),
            'is_active' => true,
        ]);

        return $this->successResponse($pembeli, 'Pelanggan berhasil ditambahkan', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pembeli = Pembeli::findOrFail($id);

        $request->validate([
            'nama_pembeli' => 'required|string|max:100',
            'no_hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
            'limit_piutang' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $pembeli->update($request->only([
            'nama_pembeli',
            'no_hp',
            'alamat',
            'limit_piutang',
            'is_active',
        ]));

        return $this->successResponse($pembeli, 'Data pelanggan berhasil diperbarui');
    }
}
