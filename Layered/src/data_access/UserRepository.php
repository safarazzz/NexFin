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

    public function findAllPelanggan($keyword = '', $status = '') {
        $sql = "SELECT id, nama_depan, nama_belakang, no_hp, email, role, status_aktif, saldo, created_at 
                FROM users 
                WHERE role = 'pelanggan'";
        $params = [];

        if (!empty($keyword)) {
            $sql .= " AND (nama_depan LIKE :kw OR nama_belakang LIKE :kw OR email LIKE :kw OR no_hp LIKE :kw)";
            $params['kw'] = '%' . $keyword . '%';
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
}

