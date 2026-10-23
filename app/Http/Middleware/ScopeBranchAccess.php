<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeBranchAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'cabang') {
            if (! $user->id_cabang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun kasir Anda belum ditugaskan ke cabang mana pun. Silakan hubungi Admin.',
                ], 403);
            }

            // Otomatis inject atau override id_cabang dengan cabang penugasan kasir
            $request->merge(['id_cabang' => $user->id_cabang]);
        }

        return $next($request);
    }
}
