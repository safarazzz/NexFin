<?php
// ==========================================================
// BUSINESS LOGIC LAYER: CustomerService
// Bertanggung jawab atas seluruh aturan bisnis, validasi data,
// dan pemanggilan algoritma keamanan (Bcrypt)
// ==========================================================

require_once __DIR__ . '/../data_access/UserRepository.php';

class CustomerService {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
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
                $errors[] = "Password wajib diisi untuk pelanggan baru.";
            } elseif (strlen($password) < 6) {
                $errors[] = "Password minimal terdiri dari 6 karakter.";
            }
        } else {
            // Jika update dan password diisi, panjangnya harus >= 6
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

        // Hashing password dengan Bcrypt
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
        // Cek keberadaan pelanggan
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

        // Jika ada password baru, hash password tersebut
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
        $this->getCustomerById($id); // Memastikan user ada
        return $this->userRepository->delete($id);
    }

    /**
     * Logika Bisnis: Mengaktifkan / Menonaktifkan Status Akun
     */
    public function toggleStatus($id) {
        $customer = $this->getCustomerById($id);
        $newStatus = $customer['status_aktif'] ? 0 : 1;
        return $this->userRepository->updateStatus($id, $newStatus);
    }
}

