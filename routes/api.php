<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BarangController;
use App\Http\Controllers\Api\V1\CabangController;
use App\Http\Controllers\Api\V1\DireksiController;
use App\Http\Controllers\Api\V1\DistributorController;
use App\Http\Controllers\Api\V1\KaryawanController;
use App\Http\Controllers\Api\V1\KasOperasionalController;
use App\Http\Controllers\Api\V1\MutasiStokController;
use App\Http\Controllers\Api\V1\PembeliController;
use App\Http\Controllers\Api\V1\PiutangController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\ReturController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Berkah Store v2.0 - RESTful API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // -------------------------------------------------------------
    // 1. Publik (Google OAuth)
    // -------------------------------------------------------------
    Route::post('/auth/google', [AuthController::class, 'loginWithGoogle']);

    // -------------------------------------------------------------
    // 2. Terproteksi Token Sesi (Auth:Sanctum)
    // -------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        // Profil Pengguna Aktif & Logout
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Master Data (Read-only for all active authenticated staff)
        Route::get('/cabangs', [CabangController::class, 'index']);
        Route::get('/barangs', [BarangController::class, 'index']);
        Route::get('/karyawans', [KaryawanController::class, 'index']);
        Route::get('/pembelis', [PembeliController::class, 'index']);
        Route::post('/pembelis', [PembeliController::class, 'store']);
        Route::put('/pembelis/{id}', [PembeliController::class, 'update']);

        // ---------------------------------------------------------
        // 3. Modul POS Kasir & Transaksi (Cabang & Admin)
        // ---------------------------------------------------------
        Route::prefix('pos')->group(function () {
            Route::get('/cart', [PosController::class, 'getCart']);
            Route::post('/cart', [PosController::class, 'addToCart']);
            Route::put('/cart/{id}', [PosController::class, 'updateCartItem']);
            Route::delete('/cart/{id}', [PosController::class, 'removeFromCart']);
            Route::delete('/cart', [PosController::class, 'clearCart']);
            Route::post('/checkout', [PosController::class, 'checkout']);
            Route::get('/transaksis', [PosController::class, 'getTransactions']);
            Route::get('/transaksis/{id}', [PosController::class, 'getTransactionDetail']);
        });

        // ---------------------------------------------------------
        // 4. Modul Mutasi Stok (Request, Dispatch, Confirm Receive)
        // ---------------------------------------------------------
        Route::prefix('mutasi')->group(function () {
            Route::get('/', [MutasiStokController::class, 'index']);
            Route::post('/request', [MutasiStokController::class, 'requestMutasi']);
            Route::post('/{id}/dispatch', [MutasiStokController::class, 'dispatchMutasi']);
            Route::post('/{id}/confirm-receive', [MutasiStokController::class, 'confirmReceive']);
            Route::post('/{id}/reject', [MutasiStokController::class, 'rejectMutasi']);
        });

        // ---------------------------------------------------------
        // 5. Modul Piutang (Debtors, FIFO Invoices, Branch Cashier Repayment)
        // ---------------------------------------------------------
        Route::prefix('piutang')->group(function () {
            Route::get('/debtors', [PiutangController::class, 'getDebtors']);
            Route::get('/unpaid-invoices/{idPembeli}', [PiutangController::class, 'getUnpaidInvoices']);
            Route::post('/bayar', [PiutangController::class, 'bayarPiutang']);
            Route::get('/history', [PiutangController::class, 'getPaymentHistory']);
        });

        // ---------------------------------------------------------
        // 6. Modul Retur & Tukar Barang
        // ---------------------------------------------------------
        Route::prefix('retur')->group(function () {
            Route::get('/', [ReturController::class, 'index']);
            Route::post('/', [ReturController::class, 'prosesRetur']);
            Route::get('/klaim-rusak', [ReturController::class, 'listKlaimRusak']);
        });

        // ---------------------------------------------------------
        // 7. Modul Kas Operasional Toko & Harian
        // ---------------------------------------------------------
        Route::prefix('kas')->group(function () {
            Route::get('/summary', [KasOperasionalController::class, 'getSummary']);
            Route::get('/history', [KasOperasionalController::class, 'index']);
            Route::post('/masuk', [KasOperasionalController::class, 'storeKasMasuk']);
            Route::post('/keluar', [KasOperasionalController::class, 'storeKasKeluar']);
            Route::post('/uang-makan', [KasOperasionalController::class, 'storeUangMakan']);
            Route::post('/setor-pusat', [KasOperasionalController::class, 'setorKasKePusat']);
            Route::get('/setoran', [KasOperasionalController::class, 'listSetoran']);
            Route::post('/setoran/{id}/konfirmasi', [KasOperasionalController::class, 'konfirmasiSetoran']);
            Route::post('/tutup-kasir', [KasOperasionalController::class, 'tutupKasir']);
        });

        // ---------------------------------------------------------
        // 8. Modul Distributor & Supplier
        // ---------------------------------------------------------
        Route::prefix('distributor')->group(function () {
            Route::get('/', [DistributorController::class, 'index']);
            Route::post('/', [DistributorController::class, 'store']);
            Route::get('/nota', [DistributorController::class, 'listNota']);
            Route::post('/nota', [DistributorController::class, 'inputNota']);
            Route::post('/nota/{id}/bayar', [DistributorController::class, 'bayarNota']);
        });

        // ---------------------------------------------------------
        // 9. Modul Direksi / Executive Smart Insights
        // ---------------------------------------------------------
        Route::prefix('direksi')->group(function () {
            Route::get('/overview', [DireksiController::class, 'getOverview']);
            Route::get('/top-debtors', [DireksiController::class, 'getTopDebtors']);
            Route::get('/smart-insights', [DireksiController::class, 'getSmartInsights']);
        });

        // ---------------------------------------------------------
        // 10. Modul Khusus Super Admin Pusat
        // ---------------------------------------------------------
        Route::middleware('role:admin,Admin Pusat')->prefix('admin')->group(function () {
            // Manajemen Pengguna & Approval Role Karyawan
            Route::get('/users', [UserController::class, 'index']);
            Route::put('/users/{id}/role', [UserController::class, 'updateRoleAndBranch']);
            Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive']);

            // Master Manajemen Cabang
            Route::post('/cabangs', [CabangController::class, 'store']);
            Route::put('/cabangs/{id}', [CabangController::class, 'update']);

            // Master Manajemen Barang & Stok
            Route::post('/barangs', [BarangController::class, 'store']);
            Route::put('/barangs/{id}', [BarangController::class, 'update']);
            Route::post('/barangs/{id}/adjust-stock', [BarangController::class, 'adjustStock']);

            // Master Manajemen Karyawan
            Route::post('/karyawans', [KaryawanController::class, 'store']);
            Route::put('/karyawans/{id}', [KaryawanController::class, 'update']);
        });
    });
});
