<?php
// ==========================================================
// DATA ACCESS LAYER: TransactionRepository
// Bertanggung jawab murni atas query tabel `transactions`
// ==========================================================

require_once __DIR__ . '/../../config/Database.php';

class TransactionRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Mencatat record transaksi baru
     */
    public function create($pengirimId, $penerimaId, $jenis, $nominal, $saldoAkhir, $keterangan = '') {
        $sql = "INSERT INTO transactions (pengirim_id, penerima_id, jenis, nominal, saldo_akhir, keterangan)
                VALUES (:pengirim_id, :penerima_id, :jenis, :nominal, :saldo_akhir, :keterangan)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'pengirim_id' => $pengirimId,
            'penerima_id' => $penerimaId,
            'jenis'       => $jenis,
            'nominal'     => $nominal,
            'saldo_akhir' => $saldoAkhir,
            'keterangan'  => $keterangan
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Riwayat transaksi lengkap milik pengguna (sebagai pengirim atau penerima)
     */
    public function findByUserId($userId, $limit = 50) {
        $sql = "SELECT 
                    t.id, 
                    t.pengirim_id, 
                    t.penerima_id, 
                    t.jenis, 
                    t.nominal, 
                    t.saldo_akhir, 
                    t.keterangan, 
                    t.created_at,
                    CONCAT(u_pengirim.nama_depan, ' ', u_pengirim.nama_belakang) AS nama_pengirim,
                    u_pengirim.email AS email_pengirim,
                    u_pengirim.no_hp AS no_hp_pengirim,
                    CONCAT(u_penerima.nama_depan, ' ', u_penerima.nama_belakang) AS nama_penerima,
                    u_penerima.email AS email_penerima,
                    u_penerima.no_hp AS no_hp_penerima
                FROM transactions t
                LEFT JOIN users u_pengirim ON t.pengirim_id = u_pengirim.id
                LEFT JOIN users u_penerima ON t.penerima_id = u_penerima.id
                WHERE t.pengirim_id = :uid1 OR t.penerima_id = :uid2
                ORDER BY t.created_at DESC
                LIMIT :lim";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Menghitung total arus kas pengguna (Uang Masuk & Uang Keluar)
     */
    public function getUserCashFlow($userId) {
        // Uang masuk: TOPUP milik user, atau TRANSFER di mana user sebagai penerima
        $stmtMasuk = $this->db->prepare("
            SELECT COALESCE(SUM(nominal), 0) AS total_masuk 
            FROM transactions 
            WHERE (jenis = 'TOPUP' AND penerima_id = :uid1)
               OR (jenis = 'TRANSFER' AND penerima_id = :uid2)
        ");
        $stmtMasuk->execute(['uid1' => $userId, 'uid2' => $userId]);
        $masuk = $stmtMasuk->fetch()['total_masuk'] ?? 0;

        // Uang keluar: WITHDRAW milik user, atau TRANSFER di mana user sebagai pengirim
        $stmtKeluar = $this->db->prepare("
            SELECT COALESCE(SUM(nominal), 0) AS total_keluar 
            FROM transactions 
            WHERE (jenis = 'WITHDRAW' AND pengirim_id = :uid1)
               OR (jenis = 'TRANSFER' AND pengirim_id = :uid2)
        ");
        $stmtKeluar->execute(['uid1' => $userId, 'uid2' => $userId]);
        $keluar = $stmtKeluar->fetch()['total_keluar'] ?? 0;

        return [
            'total_masuk'  => (float)$masuk,
            'total_keluar' => (float)$keluar
        ];
    }

    /**
     * Transaksi sistem hari ini untuk dashboard Admin / Super Admin
     */
    public function getTodayTransactionsCount() {
        $stmt = $this->db->query("
            SELECT COUNT(*) AS total_hari_ini, COALESCE(SUM(nominal), 0) AS volume_hari_ini
            FROM transactions 
            WHERE DATE(created_at) = CURDATE()
        ");
        return $stmt->fetch() ?: ['total_hari_ini' => 0, 'volume_hari_ini' => 0];
    }

    /**
     * Riwayat transaksi khusus para pelanggan (untuk diawasi oleh Admin).
     * Aturan ketat: Admin TIDAK DAPAT melihat transaksi yang melibatkan Admin lain / Super Admin.
     */
    public function getCustomerOnlyTransactions($keyword = '', $limit = 100) {
        $sql = "SELECT 
                    t.id, 
                    t.pengirim_id, 
                    t.penerima_id, 
                    t.jenis, 
                    t.nominal, 
                    t.saldo_akhir, 
                    t.keterangan, 
                    t.created_at,
                    CONCAT(u_pengirim.nama_depan, ' ', u_pengirim.nama_belakang) AS nama_pengirim,
                    u_pengirim.email AS email_pengirim,
                    u_pengirim.no_hp AS no_hp_pengirim,
                    u_pengirim.role AS role_pengirim,
                    CONCAT(u_penerima.nama_depan, ' ', u_penerima.nama_belakang) AS nama_penerima,
                    u_penerima.email AS email_penerima,
                    u_penerima.no_hp AS no_hp_penerima,
                    u_penerima.role AS role_penerima
                FROM transactions t
                LEFT JOIN users u_pengirim ON t.pengirim_id = u_pengirim.id
                LEFT JOIN users u_penerima ON t.penerima_id = u_penerima.id
                WHERE (
                    -- Top Up Pelanggan murni
                    (t.pengirim_id IS NULL AND u_penerima.role = 'pelanggan')
                    OR
                    -- Withdraw Pelanggan murni
                    (t.penerima_id IS NULL AND u_pengirim.role = 'pelanggan')
                    OR
                    -- Transfer antar sesama Pelanggan
                    (u_pengirim.role = 'pelanggan' AND u_penerima.role = 'pelanggan')
                )";

        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (
                u_pengirim.nama_depan LIKE :kw1 
                OR u_penerima.nama_depan LIKE :kw2 
                OR u_pengirim.no_hp LIKE :kw3 
                OR u_penerima.no_hp LIKE :kw4 
                OR t.keterangan LIKE :kw5
            )";
            $kw = '%' . trim($keyword) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
            $params['kw4'] = $kw;
            $params['kw5'] = $kw;
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT :lim";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

