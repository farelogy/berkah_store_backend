<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Distributor;
use App\Models\NotaDistributor;
use App\Services\DistributorService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistributorController extends Controller
{
    use ApiResponse;

    protected DistributorService $distributorService;

    public function __construct(DistributorService $distributorService)
    {
        $this->distributorService = $distributorService;
    }

    /**
     * List distributors.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Distributor::query();

        if ($request->filled('search')) {
            $query->where('nama_distributor', 'like', "%{$request->search}%")
                  ->orWhere('kontak_person', 'like', "%{$request->search}%");
        }

        $distributors = $query->orderBy('nama_distributor', 'asc')->paginate($request->input('per_page', 20));
        return $this->successResponse($distributors);
    }

    /**
     * Store new distributor.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nama_distributor' => 'required|string|max:150',
            'kontak_person' => 'nullable|string|max:100',
            'no_hp' => 'nullable|string|max:30',
            'alamat' => 'nullable|string',
        ]);

        $distributor = $this->distributorService->createDistributor($request->all());
        return $this->successResponse($distributor, 'Distributor berhasil ditambahkan', 201);
    }

    /**
     * Input invoice from distributor.
     */
    public function inputNota(Request $request): JsonResponse
    {
        $request->validate([
            'nomor_nota' => 'required|string|max:80',
            'id_distributor' => 'required|integer|exists:distributors,id',
            'tanggal_nota' => 'required|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.nama_barang_nota' => 'required|string|max:150',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.satuan' => 'nullable|string|max:30',
            'items.*.harga_beli_satuan' => 'required|numeric|min:0',
        ]);

        try {
            $nota = $this->distributorService->inputNota($request->all(), $request->user()->id);
            return $this->successResponse($nota, 'Nota distributor berhasil diinput', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Pay supplier invoice (deducts Kas Pusat).
     */
    public function bayarNota(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'jumlah_bayar' => 'required|numeric|min:1',
            'metode' => 'required|string|max:30',
            'bukti_bayar' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        try {
            $pembayaran = $this->distributorService->bayarNota(
                $id,
                (float) $request->jumlah_bayar,
                $request->metode,
                $request->user()->id,
                $request->bukti_bayar,
                $request->catatan
            );
            return $this->successResponse($pembayaran, 'Pembayaran hutang distributor berhasil dicatat dan memotong Kas Pusat');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * List supplier invoices (nota).
     */
    public function listNota(Request $request): JsonResponse
    {
        $query = NotaDistributor::with(['distributor', 'details', 'pembayarans.user']);

        if ($request->filled('id_distributor')) {
            $query->where('id_distributor', $request->id_distributor);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notas = $query->orderBy('tanggal_nota', 'desc')->paginate($request->input('per_page', 20));
        return $this->successResponse($notas);
    }
}
