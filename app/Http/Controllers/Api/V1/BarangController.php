<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\RiwayatStok;
use App\Models\StokBarang;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangController extends Controller
{
    use ApiResponse;

    /**
     * List master products with stock per branch or central warehouse.
     */
    public function index(Request $request): JsonResponse
    {
        $idCabang = $request->user()->id_cabang ?? $request->query('id_cabang');

        $query = Barang::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Include current stock for the caller branch or central warehouse
        $query->with(['stoks' => function ($q) use ($idCabang) {
            if ($idCabang) {
                $q->where('id_cabang', $idCabang)->where('is_gudang_pusat', false);
            } else {
                $q->where('is_gudang_pusat', true);
            }
        }]);

        $barangs = $query->orderBy('nama_barang', 'asc')->paginate($request->input('per_page', 30));

        // Format each item with single `stok_saat_ini`
        $barangs->getCollection()->transform(function ($item) {
            $stokModel = $item->stoks->first();
            $item->stok_saat_ini = $stokModel ? (int) $stokModel->stok : 0;
            unset($item->stoks);
            return $item;
        });

        return $this->successResponse($barangs);
    }

    /**
     * Store new master product.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'kode_barang' => 'nullable|string|max:50|unique:barangs,kode_barang',
            'nama_barang' => 'required|string|max:150',
            'kategori' => 'nullable|string|max:50',
            'satuan' => 'nullable|string|max:30',
            'modal' => 'required|numeric|min:0',
            'harga' => 'required|numeric|min:0',
            'minimal_stok' => 'nullable|integer|min:0',
            'stok_awal_pusat' => 'nullable|integer|min:0',
        ]);

        return DB::transaction(function () use ($request) {
            $barang = Barang::create([
                'kode_barang' => $request->kode_barang,
                'nama_barang' => $request->nama_barang,
                'kategori' => $request->kategori ?? 'Umum',
                'satuan' => $request->satuan ?? 'Pcs',
                'modal' => (float) $request->modal,
                'harga' => (float) $request->harga,
                'minimal_stok' => (int) ($request->minimal_stok ?? 5),
                'is_active' => true,
            ]);

            $stokAwal = (int) ($request->stok_awal_pusat ?? 0);
            if ($stokAwal > 0) {
                StokBarang::create([
                    'id_barang' => $barang->id,
                    'id_cabang' => null,
                    'is_gudang_pusat' => true,
                    'stok' => $stokAwal,
                ]);

                RiwayatStok::create([
                    'id_barang' => $barang->id,
                    'id_cabang' => null,
                    'id_user' => $request->user()->id,
                    'jumlah' => $stokAwal,
                    'stok_akhir' => $stokAwal,
                    'tipe' => 'Restock',
                    'alasan' => 'Stok awal barang baru di Gudang Pusat',
                ]);
            }

            return $this->successResponse($barang, 'Barang master berhasil ditambahkan', 201);
        });
    }

    /**
     * Update product details.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $barang = Barang::findOrFail($id);

        $request->validate([
            'nama_barang' => 'required|string|max:150',
            'kategori' => 'nullable|string|max:50',
            'satuan' => 'nullable|string|max:30',
            'modal' => 'required|numeric|min:0',
            'harga' => 'required|numeric|min:0',
            'minimal_stok' => 'nullable|integer|min:0',
        ]);

        $barang->update($request->only([
            'nama_barang',
            'kategori',
            'satuan',
            'modal',
            'harga',
            'minimal_stok',
        ]));

        return $this->successResponse($barang, 'Data barang berhasil diperbarui');
    }

    /**
     * Restock / Stock Opname adjustment.
     */
    public function adjustStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'id_cabang' => 'nullable|integer|exists:cabangs,id',
            'is_gudang_pusat' => 'nullable|boolean',
            'perubahan_stok' => 'required|integer', // Positif (+) atau Negatif (-)
            'tipe' => 'required|in:Restock,Stock Opname',
            'alasan' => 'required|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $barang = Barang::findOrFail($id);
            $isPusat = $request->boolean('is_gudang_pusat');
            $idCabang = $isPusat ? null : ($request->id_cabang ?? $request->user()->id_cabang);

            $stokRow = StokBarang::firstOrCreate(
                [
                    'id_barang' => $barang->id,
                    'id_cabang' => $idCabang,
                    'is_gudang_pusat' => $isPusat,
                ],
                ['stok' => 0]
            );

            $stokRow = StokBarang::where('id', $stokRow->id)->lockForUpdate()->first();
            $stokRow->stok += (int) $request->perubahan_stok;
            if ($stokRow->stok < 0) {
                $stokRow->stok = 0;
            }
            $stokRow->save();

            RiwayatStok::create([
                'id_barang' => $barang->id,
                'id_cabang' => $idCabang,
                'id_user' => $request->user()->id,
                'jumlah' => (int) $request->perubahan_stok,
                'stok_akhir' => $stokRow->stok,
                'tipe' => $request->tipe,
                'alasan' => $request->alasan,
            ]);

            return $this->successResponse([
                'id_barang' => $barang->id,
                'nama_barang' => $barang->nama_barang,
                'stok_terkini' => $stokRow->stok,
            ], 'Stok berhasil disesuaikan');
        });
    }
}
