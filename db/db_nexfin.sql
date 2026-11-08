-- ==========================================================
-- DATABASE NEXFIN (nexfin_db)
-- Single Database File: Schema (DDL) + Dummy Data (DML)
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
-- Password semua akun: password123 (Bcrypt Hash)
-- ----------------------------------------------------------

-- Super Admin
INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `role`, `status_aktif`, `saldo`, `created_by`)
VALUES (1, 'Super', 'Admin', '081200000001', 'superadmin@nexfin.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'super_admin', TRUE, 0.00, NULL);

-- Admin
INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `role`, `status_aktif`, `saldo`, `created_by`) VALUES 
(2, 'Budi', 'Santoso', '081200000002', 'admin.budi@nexfin.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'admin', TRUE, 0.00, 1),
(3, 'Siti', 'Aminah', '081200000003', 'admin.siti@nexfin.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'admin', TRUE, 0.00, 1);

-- Pelanggan Aktif
INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `role`, `status_aktif`, `saldo`, `created_by`) VALUES 
(4, 'Ahmad', 'Fauzi', '081234567890', 'ahmad.fauzi@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 1500000.00, NULL),
(5, 'Dewi', 'Lestari', '081234567891', 'dewi.lestari@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 750000.00, NULL),
(6, 'Rian', 'Pratama', '081234567892', 'rian.pratama@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', TRUE, 250000.00, NULL);

-- Pelanggan Nonaktif
INSERT INTO `users` (`id`, `nama_depan`, `nama_belakang`, `no_hp`, `email`, `password`, `role`, `status_aktif`, `saldo`, `created_by`)
VALUES (7, 'Doni', 'Kusuma', '081234567899', 'doni.fraud@gmail.com', '$2y$10$HQB.pHzLp9bEn/wyf2X9ku0QRJ7oTWZ0jKjgYedXCH/Ti2K1ouaI2', 'pelanggan', FALSE, 50000.00, NULL);

ALTER TABLE `users` AUTO_INCREMENT = 8;

-- Transaksi
INSERT INTO `transactions` (`id`, `pengirim_id`, `penerima_id`, `jenis`, `nominal`, `saldo_akhir`, `keterangan`) VALUES 
(1, NULL, 4, 'TOPUP', 2000000.00, 2000000.00, 'Top Up saldo via Virtual Account'),
(2, 4, 5, 'TRANSFER', 500000.00, 1500000.00, 'Transfer saldo ke Dewi Lestari'),
(3, 5, NULL, 'WITHDRAW', 200000.00, 750000.00, 'Penarikan tunai saldo ke Bank BCA'),
(4, NULL, 6, 'TOPUP', 250000.00, 250000.00, 'Top Up saldo via Merchant');

ALTER TABLE `transactions` AUTO_INCREMENT = 5;

-- Audit Log Perubahan Status Akun
INSERT INTO `user_status_logs` (`id`, `target_user_id`, `changed_by`, `status_sebelum`, `status_sesudah`, `alasan`)
VALUES (1, 7, 2, TRUE, FALSE, 'Terdeteksi anomali penarikan dana dan percobaan aktivitas mencurigakan');

ALTER TABLE `user_status_logs` AUTO_INCREMENT = 2;

