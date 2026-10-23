<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KasHarian;
use App\Models\SetoranKas;
use App\Models\TutupKasir;
use App\Services\KasOperasionalService;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KasOperasionalController extends Controller
{
    use ApiResponse;

    protected KasOperasionalService $kasService;

    public function __construct(KasOperasionalService $kasService)
    {
        $this->kasService = $kasService;
    }

    /**
     * Get branch real-time cash overview for today.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $idCabang = $request->user()->id_cabang ?? $request->query('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditemukan.', 400);
        }

        $summary = $this->kasService->getRingkasanKasCabang((int) $idCabang, $request->query('tanggal'));
        return $this->successResponse($summary);
    }

    /**
     * List petty cash transaction entries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = KasHarian::with(['user', 'cabang', 'karyawan', 'transaksi', 'pembayaranPiutang']);

        if (in_array($request->user()->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $query->where('id_cabang', $request->user()->id_cabang);
        } elseif ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }

        if ($request->filled('tipe')) {
            $query->where('tipe', $request->tipe);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $kasList = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 25));
        return $this->successResponse($kasList);
    }

    /**
     * Record miscellaneous non-sales cash in.
     */
    public function storeKasMasuk(Request $request): JsonResponse
    {
        $request->validate([
            'jumlah' => 'required|numeric|min:1',
            'kategori' => 'required|string|max:50',
            'keterangan' => 'required|string',
            'foto_bukti' => 'nullable|string',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $entry = $this->kasService->catatKasMasuk(
                (int) $idCabang,
                $request->user()->id,
                (float) $request->jumlah,
                $request->kategori,
                $request->keterangan,
                $request->foto_bukti
            );
            return $this->successResponse($entry, 'Pemasukan kas berhasil dicatat', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Record operational cash out (petty cash expense).
     */
    public function storeKasKeluar(Request $request): JsonResponse
    {
        $request->validate([
            'jumlah' => 'required|numeric|min:1',
            'kategori' => 'required|string|max:50',
            'keterangan' => 'required|string',
            'id_karyawan' => 'nullable|integer|exists:karyawans,id',
            'foto_bukti' => 'nullable|string',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $entry = $this->kasService->catatKasKeluar(
                (int) $idCabang,
                $request->user()->id,
                (float) $request->jumlah,
                $request->kategori,
                $request->keterangan,
                $request->filled('id_karyawan') ? (int) $request->id_karyawan : null,
                $request->foto_bukti
            );
            return $this->successResponse($entry, 'Pengeluaran kas berhasil dicatat', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Record meal allowance for an employee (Global employee list).
     */
    public function storeUangMakan(Request $request): JsonResponse
    {
        $request->validate([
            'id_karyawan' => 'required|integer|exists:karyawans,id',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $entry = $this->kasService->catatUangMakan(
                (int) $idCabang,
                $request->user()->id,
                (int) $request->id_karyawan,
                (float) $request->jumlah,
                $request->keterangan
            );
            return $this->successResponse($entry, 'Uang makan karyawan berhasil dicatat', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Branch creates a cash deposit to HQ.
     */
    public function setorKasKePusat(Request $request): JsonResponse
    {
        $request->validate([
            'jumlah' => 'required|numeric|min:1',
            'metode' => 'required|in:Tunai,Transfer Bank',
            'bukti_transfer' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $setoran = $this->kasService->setorKasKePusat(
                (int) $idCabang,
                $request->user()->id,
                (float) $request->jumlah,
                $request->metode,
                $request->bukti_transfer,
                $request->catatan
            );
            return $this->successResponse($setoran, 'Setoran kas ke pusat berhasil dibuat (menunggu verifikasi)', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Admin Pusat verifies and confirms branch cash deposit.
     */
    public function konfirmasiSetoran(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'is_approved' => 'required|boolean',
            'catatan' => 'nullable|string',
        ]);

        try {
            $setoran = $this->kasService->konfirmasiSetoranKas(
                $id,
                $request->user()->id,
                (bool) $request->is_approved,
                $request->catatan
            );
            return $this->successResponse($setoran, 'Status setoran kas berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * List cash deposits.
     */
    public function listSetoran(Request $request): JsonResponse
    {
        $query = SetoranKas::with(['cabang', 'userPenyetor', 'userPenerima']);

        if (in_array($request->user()->role, ['cabang', 'Kasir Cabang', 'Admin Cabang'])) {
            $query->where('id_cabang', $request->user()->id_cabang);
        } elseif ($request->filled('id_cabang')) {
            $query->where('id_cabang', $request->id_cabang);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $setorans = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));
        return $this->successResponse($setorans);
    }

    /**
     * Daily closing and reconciliation.
     */
    public function tutupKasir(Request $request): JsonResponse
    {
        $request->validate([
            'saldo_fisik_riil' => 'required|numeric|min:0',
            'catatan' => 'nullable|string',
        ]);

        $idCabang = $request->user()->id_cabang ?? $request->input('id_cabang');
        if (!$idCabang) {
            return $this->errorResponse('ID Cabang tidak ditentukan.', 400);
        }

        try {
            $closing = $this->kasService->tutupKasirHarian(
                (int) $idCabang,
                $request->user()->id,
                (float) $request->saldo_fisik_riil,
                $request->catatan
            );
            return $this->successResponse($closing, 'Tutup kasir harian berhasil disimpan', 201);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
