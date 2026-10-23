<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda telah dinonaktifkan oleh Admin.',
            ], 403);
        }

        if ($user->role === 'pending') {
            return response()->json([
                'success' => false,
                'status' => 'PENDING_APPROVAL',
                'message' => 'Akun Anda sedang menunggu persetujuan role & cabang dari Admin Pusat.',
            ], 403);
        }

        if (! in_array($user->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk tindakan ini.',
            ], 403);
        }

        return $next($request);
    }
}
