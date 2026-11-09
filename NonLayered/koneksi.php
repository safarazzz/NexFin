<?php
// ==========================================================
// NON-LAYERED ARCHITECTURE: koneksi.php
// Koneksi database (PDO) + session.
// Di-include oleh SETIAP halaman. Tidak ada kelas Repository:
// setiap halaman menulis query SQL-nya sendiri memakai $pdo.
// ==========================================================

session_start();

$dbHost = '127.0.0.1';
$dbName = 'nexfin_db';
$dbUser = 'root';
$dbPass = '';
$dbPort = '3306';

try {
    $pdo = new PDO(
        "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . htmlspecialchars($e->getMessage()));
}
