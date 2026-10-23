<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\DetailTransaksi;
use App\Models\KasHarian;
use App\Models\Keranjang;
use App\Models\Pembeli;
use App\Models\RiwayatStok;
use App\Models\StokBarang;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PosCheckoutService
{
    /**
     * Get active shared cart for a branch.
     */
    public function getCart(int $idCabang)
    {
        return Keranjang::with('barang')
            ->where('id_cabang', $idCabang)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Add an item to the branch's shared cart or increment qty.
     */
    public function addToCart(int $idCabang, int $idBarang, int $jumlah = 1, ?float $customPrice = null, ?int $userId = null)
    {
        $barang = Barang::findOrFail($idBarang);
        $hargaSatuan = $customPrice !== null ? $customPrice : (float) $barang->harga;

        $item = Keranjang::where('id_cabang', $idCabang)
            ->where('id_barang', $idBarang)
            ->first();

        if ($item) {
            $item->jumlah += $jumlah;
            if ($customPrice !== null) {
                $item->harga_satuan = $customPrice;
            }
            $item->id_user_last_modified = $userId;
            $item->save();
        } else {
            $item = Keranjang::create([
                'id_cabang' => $idCabang,
                'id_barang' => $idBarang,
                'jumlah' => $jumlah,
                'harga_satuan' => $hargaSatuan,
                'id_user_last_modified' => $userId,
            ]);
        }

        return $item->load('barang');
    }

    /**
     * Update qty or negotiated price of an item in the cart.
     */
    public function updateCartItem(int $idKeranjang, int $jumlah, ?float $customPrice = null, ?int $userId = null)
    {
        $item = Keranjang::findOrFail($idKeranjang);

        if ($jumlah <= 0) {
            $item->delete();
            return null;
        }

        $item->jumlah = $jumlah;
        if ($customPrice !== null) {
            $item->harga_satuan = $customPrice;
        }
        $item->id_user_last_modified = $userId;
        $item->save();

        return $item->load('barang');
    }

    /**
     * Remove an item from the cart.
     */
    public function removeFromCart(int $idKeranjang): bool
    {
        $item = Keranjang::find($idKeranjang);
        if ($item) {
            return $item->delete();
        }
        return false;
    }

    /**
     * Clear all items in branch cart.
     */
    public function clearCart(int $idCabang): int
    {
        return Keranjang::where('id_cabang', $idCabang)->delete();
    }

    /**
     * Execute POS checkout atomically with row locking.
     */
    public function checkout(array $data, int $userId, int $idCabang): Transaksi
    {
        return DB::transaction(function () use ($data, $userId, $idCabang) {
            // 1. Resolve Items: either from request payload or branch active cart
            $cartItems = [];
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $it) {
                    $cartItems[] = (object) [
                        'id_barang' => (int) $it['id_barang'],
                        'jumlah' => (int) $it['jumlah'],
                        'harga_satuan' => (float) $it['harga_satuan'],
                    ];
                }
            } else {
                $rawCart = Keranjang::where('id_cabang', $idCabang)->get();
                if ($rawCart->isEmpty()) {
                    throw new InvalidArgumentException('Keranjang belanja kosong. Masukkan barang terlebih dahulu.');
                }
                foreach ($rawCart as $it) {
                    $cartItems[] = (object) [
                        'id_barang' => (int) $it->id_barang,
                        'jumlah' => (int) $it->jumlah,
                        'harga_satuan' => (float) $it->harga_satuan,
                    ];
                }
            }

            if (empty($cartItems)) {
                throw new InvalidArgumentException('Tidak ada item untuk di-checkout.');
            }

            // 2. Validate and Lock Stocks
            $subtotal = 0;
            $itemsWithModels = [];

            foreach ($cartItems as $item) {
                if ($item->jumlah <= 0) {
                    throw new InvalidArgumentException('Jumlah barang harus lebih dari 0.');
                }

                $barang = Barang::findOrFail($item->id_barang);
                if (!$barang->is_active) {
                    throw new RuntimeException("Barang [{$barang->nama_barang}] berstatus non-aktif.");
                }

                $stokRow = StokBarang::where('id_cabang', $idCabang)
                    ->where('id_barang', $item->id_barang)
                    ->lockForUpdate()
                    ->first();

                $currentStok = $stokRow ? (int) $stokRow->stok : 0;
                if ($currentStok < $item->jumlah) {
                    throw new RuntimeException("Stok barang [{$barang->nama_barang}] tidak mencukupi (sisa: {$currentStok}, diminta: {$item->jumlah}).");
                }

                $itemSubtotal = $item->jumlah * $item->harga_satuan;
                $subtotal += $itemSubtotal;

                $itemsWithModels[] = [
                    'barang' => $barang,
                    'stokRow' => $stokRow,
                    'jumlah' => $item->jumlah,
                    'harga_satuan' => $item->harga_satuan,
                    'subtotal' => $itemSubtotal,
                ];
            }

            // 3. Financial calculations
            $diskon = isset($data['diskon']) ? max(0, (float) $data['diskon']) : 0;
            $totalBelanja = max(0, $subtotal - $diskon);

            $idPembeli = !empty($data['id_pembeli']) ? (int) $data['id_pembeli'] : null;
            $namaPembeliNonMember = $data['nama_pembeli_non_member'] ?? null;
            $pembeli = null;

            $potongSaldo = 0;
            if ($idPembeli) {
                $pembeli = Pembeli::lockForUpdate()->findOrFail($idPembeli);
                if (!empty($data['potong_saldo_pembeli'])) {
                    $requestedPotong = max(0, (float) $data['potong_saldo_pembeli']);
                    if ($requestedPotong > (float) $pembeli->saldo) {
                        throw new InvalidArgumentException("Saldo pembeli tidak mencukupi (saldo: {$pembeli->saldo}, diminta potong: {$requestedPotong}).");
                    }
                    $potongSaldo = min($requestedPotong, $totalBelanja);
                    $pembeli->saldo -= $potongSaldo;
                }
            } else {
                if (!empty($data['potong_saldo_pembeli']) && (float) $data['potong_saldo_pembeli'] > 0) {
                    throw new InvalidArgumentException('Pelanggan Umum tidak memiliki saldo toko.');
                }
            }

            $totalTagihanAkhir = max(0, $totalBelanja - $potongSaldo);
            $jumlahBayar = isset($data['jumlah_bayar']) ? max(0, (float) $data['jumlah_bayar']) : 0;
            $metodePembayaran = $data['metode_pembayaran'] ?? 'Tunai';

            $kembalianTunai = 0;
            $masukSaldoPembeli = 0;
            $sisaPiutang = 0;
            $status = 'Lunas';

            if ($jumlahBayar >= $totalTagihanAkhir) {
                // Lunas / Lebih Bayar
                $kelebihan = $jumlahBayar - $totalTagihanAkhir;
                $simpanKeSaldo = !empty($data['simpan_kembalian_ke_saldo']) && $pembeli !== null;

                if ($simpanKeSaldo && $kelebihan > 0) {
                    $masukSaldoPembeli = $kelebihan;
                    $pembeli->saldo += $kelebihan;
                } else {
                    $kembalianTunai = $kelebihan;
                }
            } else {
                // Kurang bayar -> Piutang!
                if (!$pembeli) {
                    throw new InvalidArgumentException('Pelanggan Umum tidak diperkenankan berhutang / memiliki piutang. Silakan pilih Pelanggan Langganan atau lakukan bayar lunas.');
                }

                $sisaPiutang = $totalTagihanAkhir - $jumlahBayar;
                $status = 'Belum Lunas';
                $pembeli->sisa_piutang += $sisaPiutang;
            }

            if ($pembeli) {
                $pembeli->save();
            }

            // 4. Generate transaction record
            $kodeTransaksi = 'TRX-' . $idCabang . '-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
            $transaksi = Transaksi::create([
                'kode_transaksi' => $kodeTransaksi,
                'id_cabang' => $idCabang,
                'id_user' => $userId,
                'id_pembeli' => $idPembeli,
                'nama_pembeli_non_member' => $namaPembeliNonMember,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total_belanja' => $totalBelanja,
                'potong_saldo_pembeli' => $potongSaldo,
                'total_tagihan_akhir' => $totalTagihanAkhir,
                'jumlah_bayar' => $jumlahBayar,
                'kembalian_tunai' => $kembalianTunai,
                'masuk_saldo_pembeli' => $masukSaldoPembeli,
                'sisa_piutang' => $sisaPiutang,
                'metode_pembayaran' => $metodePembayaran,
                'status' => $status,
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // 5. Create DetailTransaksi, Deduct Stocks, and Record RiwayatStok
            foreach ($itemsWithModels as $im) {
                $barang = $im['barang'];
                $stokRow = $im['stokRow'];
                $qty = $im['jumlah'];
                $hargaSatuan = $im['harga_satuan'];
                $itemSubtotal = $im['subtotal'];

                // Snapshot HPP modal at the exact time of sale
                DetailTransaksi::create([
                    'id_transaksi' => $transaksi->id,
                    'id_barang' => $barang->id,
                    'nama_barang_snapshot' => $barang->nama_barang,
                    'satuan_snapshot' => $barang->satuan,
                    'modal_satuan_snapshot' => $barang->modal,
                    'harga_satuan' => $hargaSatuan,
                    'jumlah' => $qty,
                    'jumlah_direfund' => 0,
                    'subtotal' => $itemSubtotal,
                ]);

                // Atomically decrement stock
                $stokRow->stok -= $qty;
                $stokRow->save();

                // Stock ledger
                RiwayatStok::create([
                    'id_barang' => $barang->id,
                    'id_cabang' => $idCabang,
                    'id_user' => $userId,
                    'jumlah' => -$qty,
                    'stok_akhir' => $stokRow->stok,
                    'tipe' => 'Penjualan',
                    'alasan' => "Penjualan Nota {$kodeTransaksi}",
                    'referensi_kode' => $kodeTransaksi,
                ]);
            }

            // 6. Record Cash Inflow for POS store petty cash
            // Only cash received physically goes to Kas Harian
            if ($metodePembayaran === 'Tunai' || str_contains($metodePembayaran, 'Tunai')) {
                // If overpayment went to saldo, the store actually kept the full physical money
                $uangMasukFisik = ($masukSaldoPembeli > 0) ? $jumlahBayar : ($jumlahBayar - $kembalianTunai);
                if ($uangMasukFisik > 0) {
                    KasHarian::create([
                        'id_cabang' => $idCabang,
                        'id_user' => $userId,
                        'id_transaksi' => $transaksi->id,
                        'kategori' => 'Penjualan Tunai',
                        'tipe' => 'Masuk',
                        'jumlah' => $uangMasukFisik,
                        'keterangan' => "Penerimaan Kasir Penjualan {$kodeTransaksi}",
                    ]);
                }
            }

            // 7. Clear the branch cart
            Keranjang::where('id_cabang', $idCabang)->delete();

            return $transaksi->load(['details.barang', 'pembeli', 'user', 'cabang']);
        });
    }
}
