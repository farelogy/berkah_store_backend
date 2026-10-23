<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * Daftar seluruh pengguna (bisa filter role=pending untuk approval).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('cabang')->orderBy('created_at', 'desc');

        if ($request->has('role') && $request->role !== '') {
            $query->where('role', $request->role);
        }

        if ($request->boolean('pending_only')) {
            $query->where('role', 'pending');
        }

        $users = $query->paginate(20);

        return $this->successResponse($users, 'Data user berhasil diambil.');
    }

    /**
     * Admin menetapkan Role dan Cabang Penugasan Pengguna.
     */
    public function updateRoleAndBranch(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|in:admin,cabang,direksi,pending',
            'id_cabang' => 'nullable|exists:cabangs,id',
            'is_active' => 'boolean',
        ]);

        $user = User::findOrFail($id);

        // Jika role kasir cabang, cabang wajib dipilih
        if ($request->role === 'cabang' && ! $request->id_cabang) {
            return $this->errorResponse('Role kasir cabang wajib memilih Cabang Penugasan.', 422);
        }

        $user->update([
            'role' => $request->role,
            'id_cabang' => $request->role === 'cabang' ? $request->id_cabang : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return $this->successResponse($user->load('cabang'), 'Role dan penugasan user berhasil diperbarui.');
    }

    /**
     * Nonaktifkan atau aktifkan kembali akun user.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Mencegah admin menonaktifkan dirinya sendiri
        if ($user->id === auth()->id()) {
            return $this->errorResponse('Anda tidak dapat menonaktifkan akun Anda sendiri.', 400);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        // Jika dinonaktifkan, hapus semua token aktif agar langsung logout
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->successResponse($user, "Akun user berhasil {$statusText}.");
    }
}
