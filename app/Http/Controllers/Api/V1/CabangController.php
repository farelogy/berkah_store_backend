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

    /**
     * Get list of branches.
     * Public active branches for POS/Kasir, or all branches for Admin.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Cabang::query();

        // If not requesting all (e.g. from cashier/POS), filter active only
        if (!$request->boolean('all') && $request->user()?->role !== 'admin') {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_cabang', 'like', "%{$search}%")
                  ->orWhere('kode_cabang', 'like', "%{$search}%")
                  ->orWhere('nama_tb', 'like', "%{$search}%")
                  ->orWhere('alamat', 'like', "%{$search}%");
            });
        }

        $cabangs = $query->orderBy('id', 'asc')->get();
        return $this->successResponse($cabangs);
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama_cabang' => 'required|string|max:100|unique:cabangs,nama_cabang',
            'kode_cabang' => 'nullable|string|max:20|unique:cabangs,kode_cabang',
            'nama_tb' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:30',
            'no_hp' => 'nullable|string|max:30',
        ]);

        $phone = $request->no_telepon ?? $request->no_hp;
        $kode = $request->kode_cabang;
        if (empty($kode)) {
            $slug = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $request->nama_cabang));
            $kode = 'CAB-' . substr($slug, 0, 4);
        }

        $cabang = Cabang::create([
            'kode_cabang' => $kode,
            'nama_cabang' => $request->nama_cabang,
            'nama_tb' => $request->nama_tb ?? ('TB Berkah ' . $request->nama_cabang),
            'alamat' => $request->alamat,
            'no_telepon' => $phone,
            'no_hp' => $phone,
            'saldo' => 0.00,
            'is_active' => true,
        ]);

        return $this->successResponse($cabang, 'Cabang berhasil ditambahkan', 201);
    }

    /**
     * Update an existing branch.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $cabang = Cabang::findOrFail($id);

        $request->validate([
            'nama_cabang' => "required|string|max:100|unique:cabangs,nama_cabang,{$id}",
            'kode_cabang' => "nullable|string|max:20|unique:cabangs,kode_cabang,{$id}",
            'nama_tb' => 'nullable|string|max:150',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:30',
            'no_hp' => 'nullable|string|max:30',
            'is_active' => 'nullable|boolean',
        ]);

        $phone = $request->no_telepon ?? $request->no_hp ?? $cabang->no_telepon ?? $cabang->no_hp;

        $cabang->update([
            'nama_cabang' => $request->nama_cabang,
            'kode_cabang' => $request->kode_cabang ?? $cabang->kode_cabang,
            'nama_tb' => $request->nama_tb ?? $cabang->nama_tb,
            'alamat' => $request->alamat,
            'no_telepon' => $phone,
            'no_hp' => $phone,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $cabang->is_active,
        ]);

        return $this->successResponse($cabang, 'Data cabang berhasil diperbarui');
    }

    /**
     * Toggle active/inactive status of a branch.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $cabang = Cabang::findOrFail($id);
        $cabang->is_active = !$cabang->is_active;
        $cabang->save();

        $statusStr = $cabang->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return $this->successResponse($cabang, "Cabang {$cabang->nama_cabang} berhasil {$statusStr}");
    }

    /**
     * Soft delete/deactivate a branch.
     */
    public function destroy(int $id): JsonResponse
    {
        $cabang = Cabang::findOrFail($id);
        $cabang->is_active = false;
        $cabang->save();

        return $this->successResponse(null, "Cabang {$cabang->nama_cabang} berhasil dinonaktifkan");
    }
}
