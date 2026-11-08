-- ==========================================================
-- DATABASE NEXFIN (nexfin_db)
-- Single Database File: Schema (DDL) + Dummy Data (DML)
-- Total: 1 Super Admin, 3 Admin, 21 Pelanggan
-- Password semua akun: password123 (Bcrypt Hash)
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `nexfin_db`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `nexfin_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `user_status_logs`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------
-- 1. TABEL: users
-- ----------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_depan` VARCHAR(50) NOT NULL,
    `nama_belakang` VARCHAR(50) NOT NULL,
    `no_hp` VARCHAR(20) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('pelanggan', 'admin', 'super_admin') NOT NULL DEFAULT 'pelanggan',
    `status_aktif` BOOLEAN NOT NULL DEFAULT TRUE,
    `saldo` DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    `created_by` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. TABEL: transactions
-- ----------------------------------------------------------
CREATE TABLE `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pengirim_id` INT NULL,
    `penerima_id` INT NULL,
    `jenis` ENUM('TOPUP', 'TRANSFER', 'WITHDRAW') NOT NULL,
    `nominal` DECIMAL(15, 2) NOT NULL,
    `saldo_akhir` DECIMAL(15, 2) NOT NULL,
    `keterangan` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_trans_pengirim` FOREIGN KEY (`pengirim_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_trans_penerima` FOREIGN KEY (`penerima_id`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. TABEL: user_status_logs
-- ----------------------------------------------------------
CREATE TABLE `user_status_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `target_user_id` INT NOT NULL,
    `changed_by` INT NOT NULL,
    `status_sebelum` BOOLEAN NOT NULL,
    `status_sesudah` BOOLEAN NOT NULL,
    `alasan` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_log_target_user` FOREIGN KEY (`target_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_log_changed_by` FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indeks
CREATE INDEX `idx_users_role` ON `users`(`role`);
CREATE INDEX `idx_users_nama` ON `users`(`nama_depan`, `nama_belakang`);
CREATE INDEX `idx_users_status` ON `users`(`status_aktif`);
CREATE INDEX `idx_trans_pengirim` ON `transactions`(`pengirim_id`);
CREATE INDEX `idx_trans_penerima` ON `transactions`(`penerima_id`);

-- ----------------------------------------------------------
-- DATA DUMMY AWAL
-- Password untuk semua akun: password123
-- Hash Bcrypt: $2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2
-- ----------------------------------------------------------

-- A. SUPER ADMIN (1 orang, saldo Rp 0)
INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `role`, `status_aktif`, `saldo`, `created_by`) VALUES 
(1, 'Super', 'Admin', '081200000001', 'superadmin@nexfin.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'super_admin', TRUE, 0.00, NULL);

-- B. ADMIN (3 orang, pekerja NexFin memiliki saldo)
INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `role`, `status_aktif`, `saldo`, `created_by`) VALUES 
(2, 'Budi', 'Santoso', '081200000002', 'admin.budi@nexfin.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'admin', TRUE, 5000000.00, 1),
(3, 'Siti', 'Aminah', '081200000003', 'admin.siti@nexfin.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'admin', TRUE, 4500000.00, 1),
(4, 'Hendra', 'Wijaya', '081200000004', 'admin.hendra@nexfin.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'admin', TRUE, 3500000.00, 1);

-- C. PELANGGAN (21 orang)
INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `role`, `status_aktif`, `saldo`, `created_by`) VALUES 
(5, 'Ahmad', 'Fauzi', '081234567801', 'ahmad.fauzi@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 1500000.00, NULL),
(6, 'Dewi', 'Lestari', '081234567802', 'dewi.lestari@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 750000.00, NULL),
(7, 'Rian', 'Pratama', '081234567803', 'rian.pratama@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 250000.00, NULL),
(8, 'Doni', 'Kusuma', '081234567804', 'doni.fraud@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', FALSE, 50000.00, NULL),
(9, 'Maya', 'Indah', '081234567805', 'maya.indah@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 1200000.00, NULL),
(10, 'Rizky', 'Ramadhan', '081234567806', 'rizky.ramadhan@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 890000.00, NULL),
(11, 'Putri', 'Ayu', '081234567807', 'putri.ayu@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 430000.00, NULL),
(12, 'Eko', 'Prasetyo', '081234567808', 'eko.prasetyo@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 2100000.00, NULL),
(13, 'Nurul', 'Hidayah', '081234567809', 'nurul.hidayah@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 650000.00, NULL),
(14, 'Bayu', 'Saputra', '081234567810', 'bayu.saputra@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 1750000.00, NULL),
(15, 'Anisa', 'Rahma', '081234567811', 'anisa.rahma@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 320000.00, NULL),
(16, 'Dimas', 'Setiawan', '081234567812', 'dimas.setiawan@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 940000.00, NULL),
(17, 'Fajar', 'Nugraha', '081234567813', 'fajar.nugraha@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 510000.00, NULL),
(18, 'Gita', 'Gutawa', '081234567814', 'gita.gutawa@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 1340000.00, NULL),
(19, 'Haris', 'Munandar', '081234567815', 'haris.munandar@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 780000.00, NULL),
(20, 'Intan', 'Permata', '081234567816', 'intan.permata@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 1620000.00, NULL),
(21, 'Joko', 'Susilo', '081234567817', 'joko.susilo@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', FALSE, 15000.00, NULL),
(22, 'Kartika', 'Sari', '081234567818', 'kartika.sari@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 880000.00, NULL),
(23, 'Lutfi', 'Hakim', '081234567819', 'lutfi.hakim@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 1150000.00, NULL),
(24, 'Mega', 'Utami', '081234567820', 'mega.utami@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 470000.00, NULL),
(25, 'Naufal', 'Zaki', '081234567821', 'naufal.zaki@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 2300000.00, NULL);

ALTER TABLE `users` AUTO_INCREMENT = 26;

-- D. TRANSAKSI (Campuran Top Up, Transfer antar Pelanggan, dan Tarik Saldo)
INSERT INTO `transactions` (`id`, `pengirim_id`, `penerima_id`, `jenis`, `nominal`, `saldo_akhir`, `keterangan`) VALUES 
(1, NULL, 5, 'TOPUP', 2000000.00, 2000000.00, 'Top Up saldo via Virtual Account'),
(2, 5, 6, 'TRANSFER', 500000.00, 1500000.00, 'Transfer saldo ke Dewi Lestari'),
(3, 6, NULL, 'WITHDRAW', 200000.00, 750000.00, 'Penarikan tunai saldo ke Bank BCA'),
(4, NULL, 7, 'TOPUP', 250000.00, 250000.00, 'Top Up saldo via Merchant'),
(5, NULL, 2, 'TOPUP', 5000000.00, 5000000.00, 'Deposit Saldo Karyawan Admin'),
(6, NULL, 3, 'TOPUP', 4500000.00, 4500000.00, 'Deposit Saldo Karyawan Admin'),
(7, NULL, 4, 'TOPUP', 3500000.00, 3500000.00, 'Deposit Saldo Karyawan Admin'),
(8, 12, 14, 'TRANSFER', 350000.00, 1750000.00, 'Pembayaran patungan proyek'),
(9, 25, 23, 'TRANSFER', 200000.00, 2100000.00, 'Transfer ke Lutfi Hakim'),
(10, NULL, 9, 'TOPUP', 1200000.00, 1200000.00, 'Top Up QRIS Mandiri'),
(11, 10, NULL, 'WITHDRAW', 100000.00, 890000.00, 'Tarik tunai ATM'),
(12, 16, 20, 'TRANSFER', 150000.00, 790000.00, 'Transfer jajan ke Intan Permata');

ALTER TABLE `transactions` AUTO_INCREMENT = 13;

-- E. AUDIT LOG STATUS AKUN (Recent Activity dari Admin)
INSERT INTO `user_status_logs` (`id`, `target_user_id`, `changed_by`, `status_sebelum`, `status_sesudah`, `alasan`, `created_at`) VALUES 
(1, 8, 2, TRUE, FALSE, 'Terdeteksi anomali penarikan dana dan percobaan aktivitas mencurigakan', '2026-11-08 10:15:00'),
(2, 21, 3, TRUE, FALSE, 'Laporan indikasi spam transfer dan data identitas belum valid', '2026-11-08 14:30:00');

ALTER TABLE `user_status_logs` AUTO_INCREMENT = 3;
