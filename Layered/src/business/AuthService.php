<?php
// ==========================================================
// BUSINESS LOGIC LAYER: AuthService
// Bertanggung jawab atas autentikasi, pengecekan hak akses,
// dan pembaruan profil mandiri
// ==========================================================

require_once __DIR__ . '/../data_access/UserRepository.php';

class AuthService {
    private $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }

    /**
     * Autentikasi Pengguna (Login)
     */
    public function login($email, $password) {
        $email = trim($email);
        if (empty($email) || empty($password)) {
            throw new Exception("Email dan password wajib diisi.");
        }

        $user = $this->userRepository->findByEmail($email);
        if (!$user) {
            throw new Exception("Kredensial tidak valid (Email tidak terdaftar).");
        }

        // Verifikasi password dengan Bcrypt
        if (!password_verify($password, $user['password'])) {
            throw new Exception("Kredensial tidak valid (Password salah).");
        }

        // Cek status aktif
        if (!$user['status_aktif']) {
            throw new Exception("Akun Anda telah dinonaktifkan oleh Administrator. Silakan hubungi layanan bantuan.");
        }

        return $user;
    }

    /**
     * Edit Profil Sendiri (Semua Role)
     */
    public function updateProfile($userId, $input) {
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception("Pengguna tidak ditemukan.");
        }

        $errors = [];
        if (empty(trim($input['nama_depan'] ?? ''))) $errors[] = "Nama depan tidak boleh kosong.";
        if (empty(trim($input['nama_belakang'] ?? ''))) $errors[] = "Nama belakang tidak boleh kosong.";

        $email = trim($input['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email tidak valid.";
        } elseif ($this->userRepository->isEmailTaken($email, $userId)) {
            $errors[] = "Email sudah digunakan oleh akun lain.";
        }

        $noHp = trim($input['no_hp'] ?? '');
        if (empty($noHp) || !preg_match('/^(\+62|62|0)8[0-9]{8,11}$/', $noHp)) {
            $errors[] = "Nomor HP tidak valid (gunakan format Indonesia).";
        } elseif ($this->userRepository->isPhoneTaken($noHp, $userId)) {
            $errors[] = "Nomor HP sudah digunakan oleh akun lain.";
        }

        if (!empty($input['password'])) {
            if (strlen($input['password']) < 6) {
                $errors[] = "Password baru minimal 6 karakter.";
            }
        }

        if (!empty($errors)) {
            throw new Exception(implode("<br>", $errors));
        }

        $updateData = [
            'nama_depan'    => trim($input['nama_depan']),
            'nama_belakang' => trim($input['nama_belakang']),
            'no_hp'         => $noHp,
            'email'         => $email
        ];

        if (!empty($input['password'])) {
            $updateData['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }

        return $this->userRepository->update($userId, $updateData);
    }
}

