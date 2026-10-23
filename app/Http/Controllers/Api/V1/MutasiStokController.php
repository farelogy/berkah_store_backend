<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MutasiStok;
use App\Services\MutasiStokService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MutasiStokController extends Controller
{
    use ApiResponse;

    protected MutasiStokService $mutasiService;

    public function __construct(MutasiStokService $mutasiService)
    {
        $this->mutasiService = $mutasiService;
    }

    /**
     * List mutasi requests.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MutasiStok::with(['barang', 'cabangTujuan', 'cabangAsal', 'userRequest', 'userDispatcher', 'userPenerima']);

        $user = $request->user();
        if (in_array($user->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $query->where(function ($q) use ($user) {
                $q->where('id_cabang_tujuan', $user->id_cabang)
                  ->orWhere('id_cabang_asal', $user->id_cabang);
            });
        } elseif ($request->filled('id_cabang')) {
            $idCabang = $request->id_cabang;
            $query->where(function ($q) use ($idCabang) {
                $q->where('id_cabang_tujuan', $idCabang)
                  ->orWhere('id_cabang_asal', $idCabang);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $mutasis = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));
        return $this->successResponse($mutasis);
    }

    /**
     * Step 1: Branch requests stock.
     */
    public function requestMutasi(Request $request): JsonResponse
    {
        $request->validate([
            'id_barang' => 'required|integer|exists:barangs,id',
            'jumlah_minta' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang_tujuan');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tujuan tidak ditentukan.', 400);
        }

        try {
            $mutasi = $this->mutasiService->requestMutasi(
                (int) $idCabang,
                (int) $request->id_barang,
                (int) $request->jumlah_minta,
                $request->catatan,
                $request->user()->id
            );
            return $this->successResponse($mutasi, 'Permintaan mutasi berhasil dibuat', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Step 2: Admin Pusat dispatches stock from Pusat or another branch.
     */
    public function dispatchMutasi(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'sumber_tipe' => 'required|in:Gudang Pusat,Cabang Lain',
            'id_cabang_asal' => 'required_if:sumber_tipe,Cabang Lain|nullable|integer|exists:cabangs,id',
            'jumlah_kirim' => 'required|integer|min:1',
            'catatan' => 'nullable|string',
        ]);

        try {
            $mutasi = $this->mutasiService->dispatchMutasi(
                $id,
                $request->sumber_tipe,
                $request->filled('id_cabang_asal') ? (int) $request->id_cabang_asal : null,
                (int) $request->jumlah_kirim,
                $request->user()->id,
                $request->catatan
            );
            return $this->successResponse($mutasi, 'Barang mutasi berhasil disetujui & dikirim');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Step 3: Destination branch verifies arrival and confirms receipt.
     */
    public function confirmReceive(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'jumlah_diterima' => 'required|integer|min:0',
            'catatan' => 'nullable|string',
        ]);

        try {
            $mutasi = $this->mutasiService->confirmReceive(
                $id,
                (int) $request->jumlah_diterima,
                $request->user()->id,
                $request->catatan
            );
            return $this->successResponse($mutasi, 'Penerimaan fisik barang berhasil dikonfirmasi. Stok cabang bertambah.');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Reject mutasi request.
     */
    public function rejectMutasi(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'alasan' => 'required|string|max:255',
        ]);

        try {
            $mutasi = $this->mutasiService->rejectMutasi($id, $request->user()->id, $request->alasan);
            return $this->successResponse($mutasi, 'Mutasi berhasil ditolak');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
