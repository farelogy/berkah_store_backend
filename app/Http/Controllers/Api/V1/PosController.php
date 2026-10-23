<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use App\Services\PosCheckoutService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    use ApiResponse;

    protected PosCheckoutService $posService;

    public function __construct(PosCheckoutService $posService)
    {
        $this->posService = $posService;
    }

    /**
     * Get branch shared cart.
     */
    public function getCart(Request $request): JsonResponse
    {
        $idCabang = $request->user()->id_cabang ?? $request->query('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditemukan.', 400);
        }

        $cart = $this->posService->getCart((int) $idCabang);
        return $this->successResponse($cart, 'Data keranjang berhasil diambil');
    }

    /**
     * Add item to cart.
     */
    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'id_barang' => 'required|integer|exists:barangs,id',
            'jumlah' => 'nullable|integer|min:1',
            'harga_satuan' => 'nullable|numeric|min:0',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $item = $this->posService->addToCart(
                (int) $idCabang,
                (int) $request->id_barang,
                (int) ($request->jumlah ?? 1),
                $request->has('harga_satuan') ? (float) $request->harga_satuan : null,
                $request->user()->id
            );
            return $this->successResponse($item, 'Barang berhasil ditambahkan ke keranjang');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Update item in cart.
     */
    public function updateCartItem(Request $request, int $idKeranjang): JsonResponse
    {
        $request->validate([
            'jumlah' => 'required|integer|min:0',
            'harga_satuan' => 'nullable|numeric|min:0',
        ]);

        try {
            $item = $this->posService->updateCartItem(
                $idKeranjang,
                (int) $request->jumlah,
                $request->has('harga_satuan') ? (float) $request->harga_satuan : null,
                $request->user()->id
            );
            return $this->successResponse($item, 'Item keranjang berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart(int $idKeranjang): JsonResponse
    {
        $deleted = $this->posService->removeFromCart($idKeranjang);
        return $this->successResponse(['deleted' => $deleted], 'Item berhasil dihapus dari keranjang');
    }

    /**
     * Clear all items in branch cart.
     */
    public function clearCart(Request $request): JsonResponse
    {
        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        $count = $this->posService->clearCart((int) $idCabang);
        return $this->successResponse(['deleted_count' => $count], 'Keranjang berhasil dikosongkan');
    }

    /**
     * Atomic Checkout.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'id_pembeli' => 'nullable|integer|exists:pembelis,id',
            'nama_pembeli_non_member' => 'nullable|string|max:100',
            'diskon' => 'nullable|numeric|min:0',
            'potong_saldo_pembeli' => 'nullable|numeric|min:0',
            'jumlah_bayar' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|string|max:30',
            'simpan_kembalian_ke_saldo' => 'nullable|boolean',
            'keterangan' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id_barang' => 'required_with:items|integer|exists:barangs,id',
            'items.*.jumlah' => 'required_with:items|integer|min:1',
            'items.*.harga_satuan' => 'required_with:items|numeric|min:0',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $transaksi = $this->posService->checkout($request->all(), $request->user()->id, (int) $idCabang);
            return $this->successResponse($transaksi, 'Checkout transaksi berhasil', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * List transaction history.
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $query = Transaksi::with(['details.barang', 'pembeli', 'user', 'cabang']);

        if (in_array($request->user()->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $query->where('id_cabang', $request->user()->id_cabang);
        } elseif ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_transaksi', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli_non_member', 'like', "%{$search}%")
                  ->orWhereHas('pembeli', function ($pq) use ($search) {
                      $pq->where('nama_pembeli', 'like', "%{$search}%");
                  });
            });
        }

        $transaksis = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));
        return $this->successResponse($transaksis);
    }

    /**
     * Detail transaction by ID.
     */
    public function getTransactionDetail(int $id): JsonResponse
    {
        $transaksi = Transaksi::with(['details.barang', 'pembeli', 'user', 'cabang'])->findOrFail($id);
        return $this->successResponse($transaksi);
    }
}
