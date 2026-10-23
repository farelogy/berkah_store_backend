<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KlaimBarangRusak;
use App\Models\Retur;
use App\Services\ReturService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturController extends Controller
{
    use ApiResponse;

    protected ReturService $returService;

    public function __construct(ReturService $returService)
    {
        $this->returService = $returService;
    }

    /**
     * Process return or exchange.
     */
    public function prosesRetur(Request $request): JsonResponse
    {
        $request->validate([
            'id_transaksi' => 'required|integer|exists:transaksis,id',
            'tipe_retur' => 'required|in:Murni Retur,Tukar Barang',
            'cara_refund' => 'nullable|in:Tunai,Saldo,Potong Piutang',
            'keterangan' => 'nullable|string',
            'items_retur' => 'required|array|min:1',
            'items_retur.*.id_barang' => 'required|integer|exists:barangs,id',
            'items_retur.*.jumlah' => 'required|integer|min:1',
            'items_retur.*.kondisi_barang' => 'required|in:Layak Jual,Rusak Klaim Distributor',
            'items_pengganti' => 'nullable|array',
            'items_pengganti.*.id_barang' => 'required_with:items_pengganti|integer|exists:barangs,id',
            'items_pengganti.*.jumlah' => 'required_with:items_pengganti|integer|min:1',
            'items_pengganti.*.harga_satuan' => 'required_with:items_pengganti|numeric|min:0',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $retur = $this->returService->prosesRetur($request->all(), (int) $idCabang, $request->user()->id);
            return $this->successResponse($retur, 'Proses retur barang berhasil dicatat', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * List retur transactions.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Retur::with(['details.barang', 'transaksi.pembeli', 'cabang', 'user']);

        if (in_array($request->user()->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $query->where('id_cabang', $request->user()->id_cabang);
        } elseif ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        $returs = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));
        return $this->successResponse($returs);
    }

    /**
     * List distributor defect claims (Klaim Barang Rusak).
     */
    public function listKlaimRusak(Request $request): JsonResponse
    {
        $query = KlaimBarangRusak::with(['barang', 'cabang', 'distributor']);

        if (in_array($request->user()->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $query->where('id_cabang', $request->user()->id_cabang);
        } elseif ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $klaims = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));
        return $this->successResponse($klaims);
    }
}
