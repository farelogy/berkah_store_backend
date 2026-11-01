-- ========================================================
-- BERKAH STORE — SQL PATCH UPDATE HOSTING (REVISED)
-- Periode: Update dari sesi "Add Frontend .." s/d Manajemen Cabang & Master Data
-- Kompatibel: phpMyAdmin (cPanel Import), MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- 1. Penyesuaian Kolom Tabel `cabangs`
-- Menambahkan kolom baru yang belum ada di tabel cabang lama
-- --------------------------------------------------------
ALTER TABLE `cabangs`
  ADD `kode_cabang` varchar(20) DEFAULT NULL AFTER `id`,
  ADD `nama_tb` varchar(150) DEFAULT NULL COMMENT 'Nama Toko Bangunan untuk Header Struk/Faktur' AFTER `nama_cabang`,
  ADD `no_hp` varchar(30) DEFAULT NULL AFTER `no_telepon`,
  ADD `saldo` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Saldo Kas Laci Cabang Saat Ini' AFTER `no_hp`;

-- --------------------------------------------------------
-- 2. Setup 4 Cabang Operasional Berkah Store
-- (1: Kavling, 2: THB, 3: LB, 4: PS)
-- --------------------------------------------------------
INSERT INTO `cabangs` (`id`, `kode_cabang`, `nama_cabang`, `nama_tb`, `alamat`, `no_telepon`, `no_hp`, `saldo`, `is_active`) VALUES
(1, 'CAB-KVL', 'Kavling', 'TB Berkah Kavling', 'Jl. Raya Kavling No. 1', '081211112222', '081211112222', 0.00, 1),
(2, 'CAB-THB', 'THB', 'TB Berkah THB', 'Taman Harapan Baru Blok A No. 5', '081233334444', '081233334444', 0.00, 1),
(3, 'CAB-LB', 'LB', 'TB Berkah LB', 'Jl. Raya Lemahabang No. 88', '081255556666', '081255556666', 0.00, 1),
(4, 'CAB-PS', 'PS', 'TB Berkah PS', 'Jl. Pasar Baru No. 12', '081277778888', '081277778888', 0.00, 1)
ON DUPLICATE KEY UPDATE
  `kode_cabang` = VALUES(`kode_cabang`),
  `nama_cabang` = VALUES(`nama_cabang`),
  `nama_tb` = VALUES(`nama_tb`),
  `alamat` = VALUES(`alamat`),
  `no_telepon` = VALUES(`no_telepon`),
  `no_hp` = VALUES(`no_hp`),
  `saldo` = VALUES(`saldo`),
  `is_active` = VALUES(`is_active`);

-- Tambah Unique Key untuk kode_cabang
ALTER TABLE `cabangs`
  ADD UNIQUE KEY `cabangs_kode_cabang_unique` (`kode_cabang`);

-- --------------------------------------------------------
-- 3. Penyesuaian Kolom Tabel `karyawans` (Gaji Harian & Cabang)
-- --------------------------------------------------------
ALTER TABLE `karyawans`
  ADD `gaji_harian` decimal(15,2) NOT NULL DEFAULT '0.00' AFTER `no_hp`,
  ADD `id_cabang` bigint unsigned DEFAULT NULL AFTER `gaji_harian`,
  ADD KEY `karyawans_id_cabang_foreign` (`id_cabang`),
  ADD CONSTRAINT `karyawans_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL;

-- --------------------------------------------------------
-- 4. Tabel Absensi Karyawan
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `absensi_karyawans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_karyawan` bigint unsigned NOT NULL,
  `id_cabang` bigint unsigned DEFAULT NULL,
  `id_user_input` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Masuk','Setengah Hari','Tidak Masuk') NOT NULL DEFAULT 'Masuk',
  `nilai_hari` decimal(3,1) NOT NULL DEFAULT '1.0' COMMENT '1.0: Sehari/Masuk, 0.5: Setengah Hari, 0.0: Tidak Masuk',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `absensi_karyawans_karyawan_tanggal_unique` (`id_karyawan`,`tanggal`),
  KEY `absensi_karyawans_cabang_tanggal_index` (`id_cabang`,`tanggal`),
  KEY `absensi_karyawans_id_user_input_foreign` (`id_user_input`),
  CONSTRAINT `absensi_karyawans_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `absensi_karyawans_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `absensi_karyawans_id_user_input_foreign` FOREIGN KEY (`id_user_input`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabel Kasbon Karyawan
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `kasbon_karyawans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_kasbon` varchar(50) NOT NULL,
  `id_karyawan` bigint unsigned NOT NULL,
  `id_cabang` bigint unsigned DEFAULT NULL,
  `id_user_input` bigint unsigned NOT NULL,
  `tanggal` date NOT NULL,
  `nominal` decimal(15,2) NOT NULL,
  `sisa_kasbon` decimal(15,2) NOT NULL,
  `status` enum('Belum Lunas','Lunas') NOT NULL DEFAULT 'Belum Lunas',
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kasbon_karyawans_kode_kasbon_unique` (`kode_kasbon`),
  KEY `kasbon_karyawans_karyawan_status_index` (`id_karyawan`,`status`),
  KEY `kasbon_karyawans_cabang_status_index` (`id_cabang`,`status`),
  KEY `kasbon_karyawans_id_user_input_foreign` (`id_user_input`),
  CONSTRAINT `kasbon_karyawans_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kasbon_karyawans_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kasbon_karyawans_id_user_input_foreign` FOREIGN KEY (`id_user_input`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabel Gaji Karyawan (Payroll Rekap)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gaji_karyawans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_gaji` varchar(50) NOT NULL,
  `id_karyawan` bigint unsigned NOT NULL,
  `id_cabang` bigint unsigned DEFAULT NULL,
  `id_user_admin` bigint unsigned NOT NULL,
  `bulan` tinyint unsigned NOT NULL,
  `tahun` smallint unsigned NOT NULL,
  `gaji_harian` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_hari_kerja` decimal(5,1) NOT NULL DEFAULT '0.0',
  `total_gaji_kotor` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_uang_makan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_kasbon` decimal(15,2) NOT NULL DEFAULT '0.00',
  `bonus_tambahan` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_gaji_bersih` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('Draft','Dibayarkan') NOT NULL DEFAULT 'Draft',
  `tanggal_bayar` timestamp NULL DEFAULT NULL,
  `metode_bayar` varchar(50) DEFAULT NULL,
  `catatan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gaji_karyawans_karyawan_periode_unique` (`id_karyawan`,`bulan`,`tahun`),
  KEY `gaji_karyawans_cabang_periode_index` (`id_cabang`,`bulan`,`tahun`),
  KEY `gaji_karyawans_id_user_admin_foreign` (`id_user_admin`),
  CONSTRAINT `gaji_karyawans_id_karyawan_foreign` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawans` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gaji_karyawans_id_cabang_foreign` FOREIGN KEY (`id_cabang`) REFERENCES `cabangs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gaji_karyawans_id_user_admin_foreign` FOREIGN KEY (`id_user_admin`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
