<?php

namespace App\Services;

use App\Models\KasHarian;
use App\Models\PembayaranPiutang;
use App\Models\Pembeli;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PiutangService
{
    /**
     * Get list of debtors with outstanding balances.
     */
    public function getDebtors(?int $idCabang = null, ?string $search = null)
    {
        $query = Pembeli::where('sisa_piutang', '>', 0);

        if ($idCabang) {
            $query->where('id_cabang_pendaftaran', $idCabang);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pembeli', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('sisa_piutang', 'desc')->paginate(20);
    }

    /**
     * Get unpaid invoices of a buyer.
     */
    public function getUnpaidInvoices(int $idPembeli)
    {
        return Transaksi::where('id_pembeli', $idPembeli)
            ->where('status', 'Belum Lunas')
            ->where('sisa_piutang', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Pay customer debt (FIFO across unpaid invoices).
     * STRICT: Executed only by branch cashiers.
     */
    public function bayarPiutang(array $data, int $idCabang, int $idKasir): PembayaranPiutang
    {
        return DB::transaction(function () use ($data, $idCabang, $idKasir) {
            $idPembeli = (int) $data['id_pembeli'];
            $jumlahBayar = isset($data['jumlah_bayar']) ? max(0, (float) $data['jumlah_bayar']) : 0;
            $potongSaldo = isset($data['potong_saldo_pembeli']) ? max(0, (float) $data['potong_saldo_pembeli']) : 0;
            $metodePembayaran = $data['metode_pembayaran'] ?? 'Tunai';

            $totalDana = $jumlahBayar + $potongSaldo;
            if ($totalDana <= 0) {
                throw new InvalidArgumentException('Nominal pembayaran atau potongan saldo harus lebih besar dari 0.');
            }

            $pembeli = Pembeli::lockForUpdate()->findOrFail($idPembeli);

            if ($potongSaldo > 0) {
                if ($potongSaldo > (float) $pembeli->saldo) {
                    throw new InvalidArgumentException("Saldo pembeli tidak mencukupi (saldo: {$pembeli->saldo}, diminta potong: {$potongSaldo}).");
                }
                $pembeli->saldo -= $potongSaldo;
            }

            // Fetch all unpaid invoices in FIFO order
            $unpaidInvoices = Transaksi::where('id_pembeli', $idPembeli)
                ->where('status', 'Belum Lunas')
                ->where('sisa_piutang', '>', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $remainingDana = $totalDana;
            $totalDialokasikan = 0;

            foreach ($unpaidInvoices as $invoice) {
                if ($remainingDana <= 0) {
                    break;
                }

                $sisaInvoice = (float) $invoice->sisa_piutang;

                if ($remainingDana >= $sisaInvoice) {
                    // Pay invoice completely
                    $invoice->sisa_piutang = 0;
                    $invoice->status = 'Lunas';
                    $invoice->save();

                    $remainingDana -= $sisaInvoice;
                    $totalDialokasikan += $sisaInvoice;
                } else {
                    // Partial payment on this invoice
                    $invoice->sisa_piutang -= $remainingDana;
                    $invoice->save();

                    $totalDialokasikan += $remainingDana;
                    $remainingDana = 0;
                }
            }

            // If there is excess money left, deposit into customer store credit (saldo)
            $kelebihanMasukSaldo = 0;
            if ($remainingDana > 0) {
                $kelebihanMasukSaldo = $remainingDana;
                $pembeli->saldo += $kelebihanMasukSaldo;
            }

            // Atomically update customer outstanding debt
            $totalRemainingPiutang = Transaksi::where('id_pembeli', $idPembeli)
                ->where('status', 'Belum Lunas')
                ->sum('sisa_piutang');
            $pembeli->sisa_piutang = $totalRemainingPiutang;
            $pembeli->save();

            // Record payment receipt
            $kodePembayaran = 'BYR-PIU-' . $idCabang . '-' . date('YmdHis') . '-' . strtoupper(Str::random(4));
            $pembayaran = PembayaranPiutang::create([
                'kode_pembayaran' => $kodePembayaran,
                'id_cabang' => $idCabang,
                'id_user' => $idKasir,
                'id_pembeli' => $idPembeli,
                'id_transaksi' => $unpaidInvoices->count() === 1 ? $unpaidInvoices->first()->id : null,
                'jumlah_bayar' => $jumlahBayar,
                'potong_saldo_pembeli' => $potongSaldo,
                'total_dialokasikan' => $totalDialokasikan,
                'kelebihan_masuk_saldo' => $kelebihanMasukSaldo,
                'metode_pembayaran' => $metodePembayaran,
                'catatan' => $data['catatan'] ?? null,
            ]);

            // If physical cash was handed to cashier, record into Kas Harian
            if ($jumlahBayar > 0 && ($metodePembayaran === 'Tunai' || str_contains($metodePembayaran, 'Tunai'))) {
                KasHarian::create([
                    'id_cabang' => $idCabang,
                    'id_user' => $idKasir,
                    'id_pembayaran_piutang' => $pembayaran->id,
                    'kategori' => 'Pembayaran Piutang',
                    'tipe' => 'Masuk',
                    'jumlah' => $jumlahBayar,
                    'keterangan' => "Pelunasan Piutang [{$pembeli->nama_pembeli}] ({$kodePembayaran})",
                ]);
            }

            return $pembayaran->load(['pembeli', 'user', 'cabang']);
        });
    }
}
