<?php
// ==========================================================
// CONFIG: Database Connection (PDO)
// ==========================================================

class Database {
    private static $host = '127.0.0.1';
    private static $db_name = 'nexfin_db';
    private static $username = 'root';
    private static $password = '';
    private static $port = '3306';
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$db_name . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, self::$username, self::$password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die("Koneksi Database Gagal" . $e->getMessage() . "<br>");
            }
        }
        return self::$pdo;
    }
}

