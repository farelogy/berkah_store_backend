<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Berkah Store v2.0 - RESTful API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // 1. Publik (Autentikasi Google Sign-In)
    Route::post('/auth/google', [AuthController::class, 'loginWithGoogle']);

    // 2. Terproteksi Token Sesi (Auth:Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        // Profil & Sesi
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // 3. Khusus Role: Admin Pusat (Super Admin)
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Manajemen Pengguna & Approval Role Karyawan
            Route::get('/users', [UserController::class, 'index']);
            Route::put('/users/{id}/role', [UserController::class, 'updateRoleAndBranch']);
            Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);
        });
    });
});
