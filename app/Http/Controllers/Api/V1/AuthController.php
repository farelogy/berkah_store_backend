<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    protected GoogleAuthService $googleService;

    public function __construct(GoogleAuthService $googleService)
    {
        $this->googleService = $googleService;
    }

    /**
     * Login atau Registrasi Otomatis via Google Sign-In.
     */
    public function loginWithGoogle(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $googleData = $this->googleService->verifyIdToken($request->id_token);

        if (! $googleData) {
            return $this->errorResponse('ID Token Google tidak valid atau sudah kadaluarsa.', 401);
        }

        // Cari berdasarkan google_id atau email
        $user = User::where('google_id', $googleData['google_id'])
            ->orWhere('email', $googleData['email'])
            ->first();

        if (! $user) {
            // Pengguna baru pertama kali mendaftar via Google
            $user = User::create([
                'name' => $googleData['name'],
                'email' => $googleData['email'],
                'google_id' => $googleData['google_id'],
                'avatar' => $googleData['avatar'],
                'role' => 'pending',
                'is_active' => true,
            ]);
        } else {
            // Sinkronkan google_id & avatar terbaru jika belum ada
            $user->update([
                'google_id' => $googleData['google_id'],
                'avatar' => $googleData['avatar'] ?? $user->avatar,
                'name' => $googleData['name'] ?? $user->name,
            ]);
        }

        if (! $user->is_active) {
            return $this->errorResponse('Akun Anda telah dinonaktifkan oleh Admin.', 403);
        }

        // Jika user masih pending approval dari Admin
        if ($user->role === 'pending') {
            return response()->json([
                'success' => false,
                'status' => 'PENDING_APPROVAL',
                'message' => 'Akun Google Anda terdaftar. Silakan hubungi Admin Pusat untuk menetapkan Role dan Cabang Anda.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'role' => $user->role,
                    ],
                ],
            ], 200);
        }

        // User sudah disetujui -> terbitkan Sanctum Bearer Token
        $user->load('cabang');
        $token = $user->createToken('berkah_store_auth')->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'cabang' => $user->cabang ? [
                    'id' => $user->cabang->id,
                    'kode_cabang' => $user->cabang->kode_cabang,
                    'nama_cabang' => $user->cabang->nama_cabang,
                    'nama_tb' => $user->cabang->nama_tb,
                ] : null,
            ],
        ], 'Login berhasil.');
    }

    /**
     * Cek status sesi & data profil user terkini.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('cabang');

        return $this->successResponse([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'cabang' => $user->cabang ? [
                'id' => $user->cabang->id,
                'kode_cabang' => $user->cabang->kode_cabang,
                'nama_cabang' => $user->cabang->nama_cabang,
                'nama_tb' => $user->cabang->nama_tb,
                'saldo' => $user->cabang->saldo,
            ] : null,
        ], 'Sesi valid.');
    }

    /**
     * Logout & Revoke Token Sesi.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Berhasil keluar.');
    }
}
