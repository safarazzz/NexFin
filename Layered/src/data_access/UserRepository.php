<?php
// ==========================================================
// DATA ACCESS LAYER: UserRepository
// Bertanggung jawab murni untuk komunikasi dan query SQL ke database
// ==========================================================

require_once __DIR__ . '/../../config/Database.php';

class UserRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function findByPhone($phone) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE no_hp = :phone LIMIT 1");
        $stmt->execute(['phone' => $phone]);
        return $stmt->fetch();
    }

    public function findByPhoneOrEmail($identifier) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE no_hp = :id_phone OR email = :id_email LIMIT 1");
        $stmt->execute([
            'id_phone' => $identifier,
            'id_email' => $identifier
        ]);
        return $stmt->fetch();
    }

    public function isEmailTaken($email, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
            $stmt->execute(['email' => $email, 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
        }
        return (bool) $stmt->fetch();
    }

    public function isPhoneTaken($phone, $excludeId = null) {
        if ($excludeId) {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE no_hp = :phone AND id != :id LIMIT 1");
            $stmt->execute(['phone' => $phone, 'id' => $excludeId]);
        } else {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE no_hp = :phone LIMIT 1");
            $stmt->execute(['phone' => $phone]);
        }
        return (bool) $stmt->fetch();
    }

    /**
     * Pencarian pelanggan dengan parameter unik untuk mencegah error PDO native prepared statement
     */
    public function findAllPelanggan($keyword = '', $status = '') {
        $sql = "SELECT id, nama_depan, nama_belakang, no_hp, email, role, status_aktif, saldo, created_at 
                FROM users 
                WHERE role = 'pelanggan'";
        $params = [];

        if (!empty($keyword)) {
            // Gunakan placeholder unik (:kw1, :kw2, :kw3, :kw4) untuk menghindari SQLSTATE[HY093] pada PDO MySQL
            $sql .= " AND (nama_depan LIKE :kw1 OR nama_belakang LIKE :kw2 OR email LIKE :kw3 OR no_hp LIKE :kw4)";
            $kw = '%' . trim($keyword) . '%';
            $params['kw1'] = $kw;
            $params['kw2'] = $kw;
            $params['kw3'] = $kw;
            $params['kw4'] = $kw;
        }

        if ($status !== '' && $status !== null) {
            $sql .= " AND status_aktif = :status";
            $params['status'] = (int) $status;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create($data) {
        $sql = "INSERT INTO users (nama_depan, nama_belakang, no_hp, email, password, role, status_aktif, saldo, created_by)
                VALUES (:nama_depan, :nama_belakang, :no_hp, :email, :password, :role, :status_aktif, :saldo, :created_by)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nama_depan'    => $data['nama_depan'],
            'nama_belakang' => $data['nama_belakang'],
            'no_hp'         => $data['no_hp'],
            'email'         => $data['email'],
            'password'      => $data['password'],
            'role'          => $data['role'] ?? 'pelanggan',
            'status_aktif'  => isset($data['status_aktif']) ? (int)$data['status_aktif'] : 1,
            'saldo'         => $data['saldo'] ?? 0.00,
            'created_by'    => $data['created_by'] ?? null
        ]);

        return $this->db->lastInsertId();
    }

    public function update($id, $data) {
        $fields = [
            "nama_depan = :nama_depan",
            "nama_belakang = :nama_belakang",
            "no_hp = :no_hp",
            "email = :email"
        ];
        $params = [
            'id'            => $id,
            'nama_depan'    => $data['nama_depan'],
            'nama_belakang' => $data['nama_belakang'],
            'no_hp'         => $data['no_hp'],
            'email'         => $data['email']
        ];

        if (!empty($data['password'])) {
            $fields[] = "password = :password";
            $params['password'] = $data['password'];
        }

        if (isset($data['saldo'])) {
            $fields[] = "saldo = :saldo";
            $params['saldo'] = $data['saldo'];
        }

        if (isset($data['status_aktif'])) {
            $fields[] = "status_aktif = :status_aktif";
            $params['status_aktif'] = (int) $data['status_aktif'];
        }

        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE users SET status_aktif = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => (int) $status]);
    }

    /**
     * Mutasi Saldo Atomik (menambah atau mengurangi saldo)
     */
    public function updateSaldo($id, $deltaNominal) {
        $stmt = $this->db->prepare("UPDATE users SET saldo = saldo + :delta WHERE id = :id");
        return $stmt->execute([
            'delta' => (float)$deltaNominal,
            'id'    => $id
        ]);
    }

    /**
     * Statistik ringkasan untuk dashboard Admin / Super Admin
     */
    public function getSystemStats() {
        $stmtUsers = $this->db->query("
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'pelanggan' THEN 1 ELSE 0 END) as total_pelanggan,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admin,
                SUM(CASE WHEN status_aktif = 1 AND role = 'pelanggan' THEN 1 ELSE 0 END) as pelanggan_aktif,
                SUM(CASE WHEN status_aktif = 0 AND role = 'pelanggan' THEN 1 ELSE 0 END) as pelanggan_nonaktif,
                SUM(saldo) as total_dana_beredar
            FROM users
        ");
        return $stmtUsers->fetch() ?: [];
    }

    /**
     * Mendapatkan daftar pelanggan terbaru untuk widget dashboard
     */
    public function getRecentCustomers($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT id, nama_depan, nama_belakang, email, no_hp, saldo, status_aktif, created_at 
            FROM users 
            WHERE role = 'pelanggan' 
            ORDER BY created_at DESC 
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mengambil daftar seluruh Admin (untuk Super Admin)
     */
    public function findAllAdmins() {
        $stmt = $this->db->query("
            SELECT u.id, u.nama_depan, u.nama_belakang, u.no_hp, u.email, u.role, u.status_aktif, u.saldo, u.created_at,
                   CONCAT(u_creator.nama_depan, ' ', u_creator.nama_belakang) AS diangkat_oleh
            FROM users u
            LEFT JOIN users u_creator ON u.created_by = u_creator.id
            WHERE u.role = 'admin'
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Mengangkat pelanggan menjadi Admin (Khusus Super Admin)
     */
    public function promoteCustomerToAdmin($customerId, $superAdminId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET role = 'admin', created_by = :super_admin_id 
            WHERE id = :id AND role = 'pelanggan'
        ");
        return $stmt->execute([
            'super_admin_id' => $superAdminId,
            'id'             => $customerId
        ]);
    }

    /**
     * Menurunkan status Admin kembali menjadi Pelanggan biasa (Khusus Super Admin)
     */
    public function demoteAdminToCustomer($adminId) {
        $stmt = $this->db->prepare("
            UPDATE users 
            SET role = 'pelanggan' 
            WHERE id = :id AND role = 'admin'
        ");
        return $stmt->execute(['id' => $adminId]);
    }
}
