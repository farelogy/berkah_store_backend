<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PembayaranPiutang;
use App\Services\PiutangService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PiutangController extends Controller
{
    use ApiResponse;

    protected PiutangService $piutangService;

    public function __construct(PiutangService $piutangService)
    {
        $this->piutangService = $piutangService;
    }

    /**
     * Get list of debtors with unpaid balance.
     */
    public function getDebtors(Request $request): JsonResponse
    {
        $idCabang = null;
        if (in_array($request->user()->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $idCabang = $request->user()->id_cabang;
        } elseif ($request->filled('id_cabang')) {
            $idCabang = (int) $request->id_cabang;
        }

        $debtors = $this->piutangService->getDebtors($idCabang, $request->search);
        return $this->successResponse($debtors);
    }

    /**
     * Get list of unpaid invoices for a specific debtor.
     */
    public function getUnpaidInvoices(int $idPembeli): JsonResponse
    {
        $invoices = $this->piutangService->getUnpaidInvoices($idPembeli);
        return $this->successResponse($invoices);
    }

    /**
     * Pay customer debt (FIFO).
     * STRICT: Admin Pusat is READ-ONLY. Only Branch Cashier / Branch Admin can accept payments!
     */
    public function bayarPiutang(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->role === 'admin' || $user->role === 'Admin Pusat' || $user->role === 'direksi' || $user->role === 'Direksi') {
            return $this->errorResponse('Akses ditolak: Penerimaan pembayaran piutang hanya boleh dilakukan oleh Kasir di Cabang.', 403);
        }

        $request->validate([
            'id_pembeli' => 'required|integer|exists:pembelis,id',
            'jumlah_bayar' => 'required|numeric|min:0',
            'potong_saldo_pembeli' => 'nullable|numeric|min:0',
            'metode_pembayaran' => 'required|string|max:30',
            'catatan' => 'nullable|string',
        ]);

        $idCabang = $user->id_cabang;
        if (!$idCabang) {
            return $this->errorResponse('Kasir tidak terasosiasi dengan cabang mana pun.', 400);
        }

        try {
            $pembayaran = $this->piutangService->bayarPiutang($request->all(), (int) $idCabang, $user->id);
            return $this->successResponse($pembayaran, 'Pembayaran piutang berhasil dicatat', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Payment receipt history.
     */
    public function getPaymentHistory(Request $request): JsonResponse
    {
        $query = PembayaranPiutang::with(['pembeli', 'user', 'cabang', 'transaksi']);

        if (in_array($request->user()->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $query->where('id_cabang', $request->user()->id_cabang);
        } elseif ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('id_pembeli')) {
            $query->where('id_pembeli', $request->id_pembeli);
        }

        $history = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));
        return $this->successResponse($history);
    }
}
