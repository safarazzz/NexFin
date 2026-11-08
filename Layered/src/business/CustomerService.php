<?php
// ==========================================================
// BUSINESS LOGIC LAYER: CustomerService
// Bertanggung jawab atas seluruh aturan bisnis, validasi data,
// otorisasi, pengangkatan peran, audit logging, & Bcrypt
// ==========================================================

require_once __DIR__ . '/../data_access/UserRepository.php';
require_once __DIR__ . '/../data_access/AuditLogRepository.php';

class CustomerService {
    private $userRepository;
    private $auditLogRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->auditLogRepository = new AuditLogRepository();
    }

    /**
     * Mengambil daftar pelanggan dengan pencarian dan filter
     */
    public function getCustomers($keyword = '', $status = '') {
        return $this->userRepository->findAllPelanggan($keyword, $status);
    }

    /**
     * Mengambil satu data pelanggan berdasarkan ID
     */
    public function getCustomerById($id) {
        $customer = $this->userRepository->findById($id);
        if (!$customer || $customer['role'] !== 'pelanggan') {
            throw new Exception("Data pelanggan tidak ditemukan.");
        }
        return $customer;
    }

    /**
     * Mengambil data user apa saja berdasarkan ID
     */
    public function getUserById($id) {
        $user = $this->userRepository->findById($id);
        if (!$user) {
            throw new Exception("Pengguna tidak ditemukan.");
        }
        return $user;
    }

    /**
     * Validasi umum input pengguna (Nama, Email, No HP, Password)
     */
    public function validateCustomerData($data, $isUpdate = false, $id = null) {
        $errors = [];

        // 1. Validasi Nama
        if (empty(trim($data['nama_depan'] ?? ''))) {
            $errors[] = "Nama depan wajib diisi.";
        }
        if (empty(trim($data['nama_belakang'] ?? ''))) {
            $errors[] = "Nama belakang wajib diisi.";
        }

        // 2. Validasi Format & Keunikan Email
        $email = trim($data['email'] ?? '');
        if (empty($email)) {
            $errors[] = "Email wajib diisi.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid (contoh: user@domain.com).";
        } elseif ($this->userRepository->isEmailTaken($email, $id)) {
            $errors[] = "Email sudah digunakan oleh akun lain.";
        }

        // 3. Validasi Format & Keunikan Nomor HP
        $noHp = trim($data['no_hp'] ?? '');
        if (empty($noHp)) {
            $errors[] = "Nomor HP wajib diisi.";
        } elseif (!preg_match('/^(\+62|62|0)8[0-9]{8,11}$/', $noHp)) {
            $errors[] = "Format nomor HP tidak valid (harus format Indonesia diawali 08/628, 10-13 digit).";
        } elseif ($this->userRepository->isPhoneTaken($noHp, $id)) {
            $errors[] = "Nomor HP sudah terdaftar pada akun lain.";
        }

        // 4. Validasi Password
        $password = $data['password'] ?? '';
        if (!$isUpdate) {
            if (empty($password)) {
                $errors[] = "Password wajib diisi untuk akun baru.";
            } elseif (strlen($password) < 6) {
                $errors[] = "Password minimal terdiri dari 6 karakter.";
            }
        } else {
            if (!empty($password) && strlen($password) < 6) {
                $errors[] = "Password baru minimal terdiri dari 6 karakter.";
            }
        }

        return $errors;
    }

    /**
     * Logika Bisnis: Menambah Pelanggan Baru
     */
    public function createCustomer($input, $createdById = null) {
        $errors = $this->validateCustomerData($input, false);
        if (!empty($errors)) {
            throw new Exception(implode("<br>", $errors));
        }

        $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

        $customerData = [
            'nama_depan'    => trim($input['nama_depan']),
            'nama_belakang' => trim($input['nama_belakang']),
            'no_hp'         => trim($input['no_hp']),
            'email'         => trim($input['email']),
            'password'      => $hashedPassword,
            'role'          => 'pelanggan',
            'status_aktif'  => isset($input['status_aktif']) ? (int)$input['status_aktif'] : 1,
            'saldo'         => isset($input['saldo']) ? (float)$input['saldo'] : 0.00,
            'created_by'    => $createdById
        ];

        return $this->userRepository->create($customerData);
    }

    /**
     * Logika Bisnis: Mengubah Data Pelanggan
     */
    public function updateCustomer($id, $input) {
        $existing = $this->getCustomerById($id);

        $errors = $this->validateCustomerData($input, true, $id);
        if (!empty($errors)) {
            throw new Exception(implode("<br>", $errors));
        }

        $updateData = [
            'nama_depan'    => trim($input['nama_depan']),
            'nama_belakang' => trim($input['nama_belakang']),
            'no_hp'         => trim($input['no_hp']),
            'email'         => trim($input['email']),
        ];

        if (!empty($input['password'])) {
            $updateData['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        if (isset($input['saldo'])) {
            $updateData['saldo'] = (float)$input['saldo'];
        }

        if (isset($input['status_aktif'])) {
            $updateData['status_aktif'] = (int)$input['status_aktif'];
        }

        return $this->userRepository->update($id, $updateData);
    }

    /**
     * Logika Bisnis: Menghapus Pelanggan
     */
    public function deleteCustomer($id) {
        $this->getCustomerById($id);
        return $this->userRepository->delete($id);
    }

    /**
     * Logika Bisnis: Mengaktifkan / Menonaktifkan Status Akun & Mencatat Log Audit
     */
    public function toggleStatus($targetUserId, $actorId, $actorRole, $alasan = '') {
        $target = $this->getUserById($targetUserId);

        // Aturan: Tidak boleh menonaktifkan akun sendiri
        if ((int)$targetUserId === (int)$actorId) {
            throw new Exception("Anda tidak dapat mengubah status akun Anda sendiri.");
        }

        // Aturan: Admin hanya boleh toggle status Pelanggan
        if ($actorRole === 'admin' && $target['role'] !== 'pelanggan') {
            throw new Exception("Akses ditolak: Admin hanya berwenang mengubah status akun pelanggan.");
        }

        // Aturan: Super Admin tidak boleh dinonaktifkan oleh siapapun
        if ($target['role'] === 'super_admin') {
            throw new Exception("Akun Super Admin tidak dapat dinonaktifkan.");
        }

        $statusSebelum = (bool)$target['status_aktif'];
        $statusSesudah = !$statusSebelum;

        // Eksekusi update
        $this->userRepository->updateStatus($targetUserId, $statusSesudah);

        // Catat ke Audit Log
        $actionName = $statusSesudah ? "Mengaktifkan akun" : "Membekukan/Menonaktifkan akun";
        $catatan = trim($alasan) ?: "{$actionName} {$target['nama_depan']} {$target['nama_belakang']}";
        $this->auditLogRepository->createLog($targetUserId, $actorId, $statusSebelum, $statusSesudah, $catatan);

        return $statusSesudah;
    }

    /**
     * Mengambil daftar seluruh Admin (Khusus Super Admin)
     */
    public function getAdmins() {
        return $this->userRepository->findAllAdmins();
    }

    /**
     * Logika Bisnis: Mengangkat Pelanggan Menjadi Admin (Khusus Super Admin)
     */
    public function promoteCustomerToAdmin($customerId, $superAdminId) {
        $customer = $this->getCustomerById($customerId);

        if (!$customer['status_aktif']) {
            throw new Exception("Pelanggan dalam status nonaktif tidak dapat diangkat menjadi Admin.");
        }

        $this->userRepository->promoteCustomerToAdmin($customerId, $superAdminId);

        // Catat ke audit log
        $this->auditLogRepository->createLog(
            $customerId, 
            $superAdminId, 
            1, 
            1, 
            "Pengangkatan peran: Pelanggan ({$customer['nama_depan']} {$customer['nama_belakang']}) resmi diangkat menjadi Admin"
        );

        return true;
    }

    /**
     * Logika Bisnis: Menurunkan status Admin kembali menjadi Pelanggan biasa (Khusus Super Admin)
     */
    public function demoteAdminToCustomer($adminId, $superAdminId) {
        $admin = $this->getUserById($adminId);
        if ($admin['role'] !== 'admin') {
            throw new Exception("Hanya pengguna dengan peran Admin yang dapat diturunkan perannya.");
        }

        $this->userRepository->demoteAdminToCustomer($adminId);

        // Catat ke audit log
        $this->auditLogRepository->createLog(
            $adminId, 
            $superAdminId, 
            1, 
            1, 
            "Penurunan peran: Admin ({$admin['nama_depan']} {$admin['nama_belakang']}) diturunkan kembali menjadi Pelanggan biasa"
        );

        return true;
    }

    /**
     * Mengambil riwayat aktivitas seluruh Admin (Audit Log untuk Super Admin)
     */
    public function getAdminActivities($limit = 50) {
        return $this->auditLogRepository->getAdminActivities($limit);
    }
}
