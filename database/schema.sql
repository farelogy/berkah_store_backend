-- ========================================================
-- Berkah Store v2.0 - Complete MySQL Database Schema & Seed
-- Compatible with MySQL 5.7+ / 8.0+ / MariaDB 10.3+
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 1. Cabang
DROP TABLE IF EXISTS `cabangs`;
CREATE TABLE `cabangs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_cabang` varchar(100) NOT NULL,
  `alamat` text,
  `no_telepon` varchar(30),
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cabangs_nama_cabang_unique` (`nama_cabang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'admin, cabang, direksi, pending',
  `id_cabang` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_id_cabang_foreign` (`id_cabang`),
  KEY `users_role_is_active_index` (`role`,`is_active`),
  CONSTRAINT `users_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Kas Pusat
DROP TABLE IF EXISTS `kas_pusats`;
CREATE TABLE `kas_pusats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `saldo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Operasional Pusat
DROP TABLE IF EXISTS `operasional_pusats`;
CREATE TABLE `operasional_pusats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_user` bigint unsigned DEFAULT NULL,
  `kategori` varchar(255) NOT NULL DEFAULT 'Operasional Kantor',
  `keterangan` varchar(255) NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `status` enum('Masuk','Keluar') NOT NULL,
  `bukti_foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `operasional_pusats_id_user_foreign` (`id_user`),
  KEY `operasional_pusats_created_at_status_index` (`created_at`,`status`),
  CONSTRAINT `operasional_pusats_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Barangs
DROP TABLE IF EXISTS `barangs`;
CREATE TABLE `barangs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_barang` varchar(50) DEFAULT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `kategori` varchar(255) NOT NULL DEFAULT 'Umum',
  `satuan` varchar(30) NOT NULL DEFAULT 'Pcs',
  `modal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `harga` decimal(15,2) NOT NULL DEFAULT '0.00',
  `minimal_stok` int NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barangs_kode_barang_unique` (`kode_barang`),
  KEY `barangs_nama_barang_kategori_index` (`nama_barang`,`kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Stok Barangs
DROP TABLE IF EXISTS `stok_barangs`;
CREATE TABLE `stok_barangs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_barang` bigint unsigned NOT NULL,
  `id_cabang` bigint unsigned DEFAULT NULL,
  `is_gudang_pusat` tinyint(1) NOT NULL DEFAULT '0',
  `stok` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stok_barangs_id_barang_id_cabang_is_gudang_pusat_unique` (`id_barang`,`id_cabang`,`is_gudang_pusat`),
  KEY `stok_barangs_id_cabang_foreign` (`id_cabang`),
  CONSTRAINT `stok_barangs_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `barangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stok_barangs_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Riwayat Stok
DROP TABLE IF EXISTS `riwayat_stoks`;
CREATE TABLE `riwayat_stoks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_barang` bigint unsigned NOT NULL,
  `id_cabang` bigint unsigned DEFAULT NULL,
  `id_user` bigint unsigned DEFAULT NULL,
  `jumlah` int NOT NULL,
  `stok_akhir` int NOT NULL,
  `tipe` varchar(50) NOT NULL,
  `alasan` varchar(255) DEFAULT NULL,
  `referensi_kode` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `riwayat_stoks_id_cabang_foreign` (`id_cabang`),
  KEY `riwayat_stoks_id_user_foreign` (`id_user`),
  KEY `riwayat_stoks_id_barang_id_cabang_created_at_index` (`id_barang`,`id_cabang`,`created_at`),
  CONSTRAINT `riwayat_stoks_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `barangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `riwayat_stoks_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `riwayat_stoks_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Mutasi Stok
DROP TABLE IF EXISTS `mutasi_stoks`;
CREATE TABLE `mutasi_stoks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_mutasi` varchar(50) NOT NULL,
  `id_cabang_tujuan` bigint unsigned NOT NULL,
  `id_user_request` bigint unsigned NOT NULL,
  `id_barang` bigint unsigned NOT NULL,
  `jumlah_minta` int NOT NULL,
  `jumlah_kirim` int DEFAULT NULL,
  `jumlah_diterima` int DEFAULT NULL,
  `sumber_tipe` enum('Gudang Pusat','Cabang Lain') DEFAULT NULL,
  `id_cabang_asal` bigint unsigned DEFAULT NULL,
  `id_user_dispatcher` bigint unsigned DEFAULT NULL,
  `id_user_penerima` bigint unsigned DEFAULT NULL,
  `status` enum('Requested','Dispatched','Completed','Rejected') NOT NULL DEFAULT 'Requested',
  `catatan` text,
  `dispatched_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mutasi_stoks_kode_mutasi_unique` (`kode_mutasi`),
  KEY `mutasi_stoks_id_cabang_tujuan_status_index` (`id_cabang_tujuan`,`status`),
  KEY `mutasi_stoks_id_barang_foreign` (`id_barang`),
  KEY `mutasi_stoks_id_cabang_asal_foreign` (`id_cabang_asal`),
  KEY `mutasi_stoks_id_user_request_foreign` (`id_user_request`),
  KEY `mutasi_stoks_id_user_dispatcher_foreign` (`id_user_dispatcher`),
  KEY `mutasi_stoks_id_user_penerima_foreign` (`id_user_penerima`),
  CONSTRAINT `mutasi_stoks_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `barangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mutasi_stoks_id_cabang_asal_foreign` FOREIGN KEY (`id_cabang_asal`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mutasi_stoks_id_cabang_tujuan_foreign` FOREIGN KEY (`id_cabang_tujuan`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mutasi_stoks_id_user_dispatcher_foreign` FOREIGN KEY (`id_user_dispatcher`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mutasi_stoks_id_user_penerima_foreign` FOREIGN KEY (`id_user_penerima`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `mutasi_stoks_id_user_request_foreign` FOREIGN KEY (`id_user_request`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Pembelis (Pelanggan Langganan)
DROP TABLE IF EXISTS `pembelis`;
CREATE TABLE `pembelis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_pembeli` varchar(255) NOT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `alamat` text,
  `id_cabang_pendaftaran` bigint unsigned DEFAULT NULL,
  `saldo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sisa_piutang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `limit_piutang` decimal(15,2) NOT NULL DEFAULT '5000000.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pembelis_nama_pembeli_no_hp_index` (`nama_pembeli`,`no_hp`),
  KEY `pembelis_id_cabang_pendaftaran_foreign` (`id_cabang_pendaftaran`),
  CONSTRAINT `pembelis_id_cabang_pendaftaran_foreign` FOREIGN KEY (`id_cabang_pendaftaran`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Keranjangs (Single Shared Cart per Branch)
DROP TABLE IF EXISTS `keranjangs`;
CREATE TABLE `keranjangs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cabang` bigint unsigned NOT NULL,
  `id_barang` bigint unsigned NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `harga_satuan` decimal(15,2) NOT NULL,
  `id_user_last_modified` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `keranjangs_id_cabang_id_barang_unique` (`id_cabang`,`id_barang`),
  KEY `keranjangs_id_barang_foreign` (`id_barang`),
  KEY `keranjangs_id_user_last_modified_foreign` (`id_user_last_modified`),
  CONSTRAINT `keranjangs_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `barangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `keranjangs_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `keranjangs_id_user_last_modified_foreign` FOREIGN KEY (`id_user_last_modified`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Transaksis
DROP TABLE IF EXISTS `transaksis`;
CREATE TABLE `transaksis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(50) NOT NULL,
  `id_cabang` bigint unsigned NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `id_pembeli` bigint unsigned DEFAULT NULL,
  `nama_pembeli_non_member` varchar(255) DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `diskon` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_belanja` decimal(15,2) NOT NULL,
  `potong_saldo_pembeli` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_tagihan_akhir` decimal(15,2) NOT NULL,
  `jumlah_bayar` decimal(15,2) NOT NULL,
  `kembalian_tunai` decimal(15,2) NOT NULL DEFAULT '0.00',
  `masuk_saldo_pembeli` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sisa_piutang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `metode_pembayaran` varchar(30) NOT NULL DEFAULT 'Tunai',
  `status` enum('Lunas','Belum Lunas','Batal') NOT NULL DEFAULT 'Lunas',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaksis_kode_transaksi_unique` (`kode_transaksi`),
  KEY `transaksis_id_cabang_created_at_index` (`id_cabang`,`created_at`),
  KEY `transaksis_id_pembeli_status_index` (`id_pembeli`,`status`),
  KEY `transaksis_id_user_foreign` (`id_user`),
  CONSTRAINT `transaksis_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaksis_id_pembeli_foreign` FOREIGN KEY (`id_pembeli`) REFERENCES `pembelis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transaksis_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Detail Transaksis
DROP TABLE IF EXISTS `detail_transaksis`;
CREATE TABLE `detail_transaksis` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_transaksi` bigint unsigned NOT NULL,
  `id_barang` bigint unsigned NOT NULL,
  `nama_barang_snapshot` varchar(255) NOT NULL,
  `satuan_snapshot` varchar(30) NOT NULL,
  `modal_satuan_snapshot` decimal(15,2) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `jumlah` int NOT NULL,
  `jumlah_direfund` int NOT NULL DEFAULT '0',
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `detail_transaksis_id_transaksi_id_barang_index` (`id_transaksi`,`id_barang`),
  KEY `detail_transaksis_id_barang_foreign` (`id_barang`),
  CONSTRAINT `detail_transaksis_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `barangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_transaksis_id_transaksi_foreign` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksis` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Pembayaran Piutang
DROP TABLE IF EXISTS `pembayaran_piutangs`;
CREATE TABLE `pembayaran_piutangs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_pembayaran` varchar(50) NOT NULL,
  `id_cabang` bigint unsigned NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `id_pembeli` bigint unsigned NOT NULL,
  `id_transaksi` bigint unsigned DEFAULT NULL,
  `jumlah_bayar` decimal(15,2) NOT NULL,
  `potong_saldo_pembeli` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_dialokasikan` decimal(15,2) NOT NULL,
  `kelebihan_masuk_saldo` decimal(15,2) NOT NULL DEFAULT '0.00',
  `metode_pembayaran` varchar(30) NOT NULL DEFAULT 'Tunai',
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pembayaran_piutangs_kode_pembayaran_unique` (`kode_pembayaran`),
  KEY `pembayaran_piutangs_id_cabang_created_at_index` (`id_cabang`,`created_at`),
  KEY `pembayaran_piutangs_id_pembeli_id_transaksi_index` (`id_pembeli`,`id_transaksi`),
  KEY `pembayaran_piutangs_id_user_foreign` (`id_user`),
  KEY `pembayaran_piutangs_id_transaksi_foreign` (`id_transaksi`),
  CONSTRAINT `pembayaran_piutangs_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_piutangs_id_pembeli_foreign` FOREIGN KEY (`id_pembeli`) REFERENCES `pembelis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_piutangs_id_transaksi_foreign` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pembayaran_piutangs_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Returs
DROP TABLE IF EXISTS `returs`;
CREATE TABLE `returs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_retur` varchar(50) NOT NULL,
  `id_transaksi` bigint unsigned NOT NULL,
  `id_cabang` bigint unsigned NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `tipe_retur` enum('Murni Retur','Tukar Barang') NOT NULL DEFAULT 'Tukar Barang',
  `total_nilai_retur` decimal(15,2) NOT NULL,
  `total_nilai_pengganti` decimal(15,2) NOT NULL DEFAULT '0.00',
  `selisih_nominal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status_penyelesaian` varchar(40) NOT NULL DEFAULT 'Selesai',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `returs_kode_retur_unique` (`kode_retur`),
  KEY `returs_id_cabang_created_at_index` (`id_cabang`,`created_at`),
  KEY `returs_id_transaksi_foreign` (`id_transaksi`),
  KEY `returs_id_user_foreign` (`id_user`),
  CONSTRAINT `returs_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `returs_id_transaksi_foreign` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksis` (`id`) ON DELETE CASCADE,
  CONSTRAINT `returs_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Detail Returs
DROP TABLE IF EXISTS `detail_returs`;
CREATE TABLE `detail_returs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_retur` bigint unsigned NOT NULL,
  `id_barang` bigint unsigned NOT NULL,
  `jumlah` int NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `kondisi_barang` enum('Layak Jual','Rusak Klaim Distributor') NOT NULL DEFAULT 'Layak Jual',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `detail_returs_id_retur_foreign` (`id_retur`),
  KEY `detail_returs_id_barang_foreign` (`id_barang`),
  CONSTRAINT `detail_returs_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `barangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_returs_id_retur_foreign` FOREIGN KEY (`id_retur`) REFERENCES `returs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Klaim Barang Rusak
DROP TABLE IF EXISTS `klaim_barang_rusaks`;
CREATE TABLE `klaim_barang_rusaks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_klaim` varchar(50) NOT NULL,
  `id_cabang` bigint unsigned NOT NULL,
  `id_barang` bigint unsigned NOT NULL,
  `id_distributor` bigint unsigned DEFAULT NULL,
  `jumlah` int NOT NULL,
  `status` enum('Dilaporkan Cabang','Diajukan ke Distributor','Selesai Ganti Fisik','Selesai Potong Nota','Ditolak') NOT NULL DEFAULT 'Dilaporkan Cabang',
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `klaim_barang_rusaks_kode_klaim_unique` (`kode_klaim`),
  KEY `klaim_barang_rusaks_id_cabang_status_index` (`id_cabang`,`status`),
  KEY `klaim_barang_rusaks_id_barang_foreign` (`id_barang`),
  CONSTRAINT `klaim_barang_rusaks_id_barang_foreign` FOREIGN KEY (`id_barang`) REFERENCES `barangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `klaim_barang_rusaks_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Karyawan (Global Employee List)
DROP TABLE IF EXISTS `karyawans`;
CREATE TABLE `karyawans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_karyawan` varchar(255) NOT NULL,
  `jabatan` varchar(50) DEFAULT 'Umum',
  `no_hp` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Kas Harian
DROP TABLE IF EXISTS `kas_harianns`;
CREATE TABLE `kas_harianns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cabang` bigint unsigned NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `id_transaksi` bigint unsigned DEFAULT NULL,
  `id_pembayaran_piutang` bigint unsigned DEFAULT NULL,
  `id_karyawan` bigint unsigned DEFAULT NULL,
  `kategori` varchar(50) NOT NULL,
  `tipe` enum('Masuk','Keluar') NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `keterangan` text,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `kas_harianns_id_cabang_created_at_tipe_index` (`id_cabang`,`created_at`,`tipe`),
  KEY `kas_harianns_id_user_foreign` (`id_user`),
  KEY `kas_harianns_id_transaksi_foreign` (`id_transaksi`),
  KEY `kas_harianns_id_pembayaran_piutang_foreign` (`id_pembayaran_piutang`),
  KEY `kas_harianns_id_karyawan_foreign` (`id_karyawan`),
  CONSTRAINT `kas_harianns_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kas_harianns_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawans` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kas_harianns_id_pembayaran_piutang_foreign` FOREIGN KEY (`id_pembayaran_piutang`) REFERENCES `pembayaran_piutangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kas_harianns_id_transaksi_foreign` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksis` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kas_harianns_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Setoran Kas
DROP TABLE IF EXISTS `setoran_kas`;
CREATE TABLE `setoran_kas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_setoran` varchar(50) NOT NULL,
  `id_cabang` bigint unsigned NOT NULL,
  `id_user_penyetor` bigint unsigned NOT NULL,
  `jumlah` decimal(15,2) NOT NULL,
  `metode` enum('Tunai','Transfer Bank') NOT NULL DEFAULT 'Tunai',
  `bukti_transfer` varchar(255) DEFAULT NULL,
  `id_user_penerima` bigint unsigned DEFAULT NULL,
  `status` enum('Menunggu Konfirmasi','Terverifikasi','Ditolak') NOT NULL DEFAULT 'Menunggu Konfirmasi',
  `catatan` text,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setoran_kas_kode_setoran_unique` (`kode_setoran`),
  KEY `setoran_kas_id_cabang_status_index` (`id_cabang`,`status`),
  KEY `setoran_kas_id_user_penyetor_foreign` (`id_user_penyetor`),
  KEY `setoran_kas_id_user_penerima_foreign` (`id_user_penerima`),
  CONSTRAINT `setoran_kas_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `setoran_kas_id_user_penerima_foreign` FOREIGN KEY (`id_user_penerima`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `setoran_kas_id_user_penyetor_foreign` FOREIGN KEY (`id_user_penyetor`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Tutup Kasir
DROP TABLE IF EXISTS `tutup_kasirs`;
CREATE TABLE `tutup_kasirs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_cabang` bigint unsigned NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `saldo_awal` decimal(15,2) NOT NULL,
  `total_masuk` decimal(15,2) NOT NULL,
  `total_keluar` decimal(15,2) NOT NULL,
  `saldo_sistem` decimal(15,2) NOT NULL,
  `saldo_fisik_riil` decimal(15,2) NOT NULL,
  `selisih` decimal(15,2) NOT NULL DEFAULT '0.00',
  `catatan_selisih` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tutup_kasirs_id_cabang_tanggal_unique` (`id_cabang`,`tanggal`),
  KEY `tutup_kasirs_id_user_foreign` (`id_user`),
  CONSTRAINT `tutup_kasirs_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tutup_kasirs_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Distributors
DROP TABLE IF EXISTS `distributors`;
CREATE TABLE `distributors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_distributor` varchar(255) NOT NULL,
  `kontak_person` varchar(255) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `alamat` text,
  `total_utang` decimal(15,2) NOT NULL DEFAULT '0.00',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Nota Distributors
DROP TABLE IF EXISTS `nota_distributors`;
CREATE TABLE `nota_distributors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nomor_nota` varchar(80) NOT NULL,
  `id_distributor` bigint unsigned NOT NULL,
  `tanggal_nota` date NOT NULL,
  `total_tagihan` decimal(15,2) NOT NULL,
  `total_dibayar` decimal(15,2) NOT NULL DEFAULT '0.00',
  `sisa_utang` decimal(15,2) NOT NULL,
  `status` enum('Belum Lunas','Sebagian','Lunas') NOT NULL DEFAULT 'Belum Lunas',
  `catatan` text,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nota_distributors_nomor_nota_unique` (`nomor_nota`),
  KEY `nota_distributors_id_distributor_status_index` (`id_distributor`,`status`),
  CONSTRAINT `nota_distributors_id_distributor_foreign` FOREIGN KEY (`id_distributor`) REFERENCES `distributors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Detail Nota Distributors
DROP TABLE IF EXISTS `detail_nota_distributors`;
CREATE TABLE `detail_nota_distributors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_nota_distributor` bigint unsigned NOT NULL,
  `nama_barang_nota` varchar(255) NOT NULL,
  `jumlah` int NOT NULL,
  `satuan` varchar(30) DEFAULT NULL,
  `harga_beli_satuan` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `detail_nota_distributors_id_nota_distributor_index` (`id_nota_distributor`),
  CONSTRAINT `detail_nota_distributors_id_nota_distributor_foreign` FOREIGN KEY (`id_nota_distributor`) REFERENCES `nota_distributors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Pembayaran Distributors
DROP TABLE IF EXISTS `pembayaran_distributors`;
CREATE TABLE `pembayaran_distributors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_nota_distributor` bigint unsigned NOT NULL,
  `id_user` bigint unsigned NOT NULL,
  `jumlah_bayar` decimal(15,2) NOT NULL,
  `tanggal_bayar` date NOT NULL,
  `metode_pembayaran` varchar(30) NOT NULL DEFAULT 'Transfer Bank',
  `bukti_bayar` varchar(255) DEFAULT NULL,
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pembayaran_distributors_id_nota_distributor_created_at_index` (`id_nota_distributor`,`created_at`),
  KEY `pembayaran_distributors_id_user_foreign` (`id_user`),
  CONSTRAINT `pembayaran_distributors_id_nota_distributor_foreign` FOREIGN KEY (`id_nota_distributor`) REFERENCES `nota_distributors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pembayaran_distributors_id_user_foreign` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Personal Access Tokens (Sanctum)
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- INITIAL SEED DATA
-- ========================================================

-- Initial Branch: Cabang Pusat
INSERT INTO `cabangs` (`id`, `nama_cabang`, `alamat`, `no_telepon`, `is_active`) VALUES
(1, 'Cabang Pusat (Gudang Utama)', 'Jl. Raya Utama No. 1', '08123456789', 1),
(2, 'Cabang 1 (Toko Timur)', 'Jl. Timur Raya No. 10', '08123456788', 1);

-- Initial Kas Pusat Row
INSERT INTO `kas_pusats` (`id`, `saldo`) VALUES
(1, 0.00);

-- Super Admin User
INSERT INTO `users` (`id`, `name`, `email`, `role`, `id_cabang`, `is_active`) VALUES
(1, 'Super Admin', 'farelogy@gmail.com', 'admin', 1, 1);

-- Initial Sample Employees for Uang Makan / Operations
INSERT INTO `karyawans` (`id`, `nama_karyawan`, `jabatan`, `no_hp`, `is_active`) VALUES
(1, 'Budi Santoso', 'Sopir Truk', '08111111111', 1),
(2, 'Agus Setiawan', 'Kenek', '08222222222', 1),
(3, 'Joko Widodo', 'Gudang', '08333333333', 1);

SET FOREIGN_KEY_CHECKS = 1;
