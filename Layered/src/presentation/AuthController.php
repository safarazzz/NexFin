<?php
// ==========================================================
// PRESENTATION LAYER: AuthController
// Menangani alur autentikasi, dashboard, dan pembaruan profil mandiri
// ==========================================================

require_once __DIR__ . '/../business/AuthService.php';
require_once __DIR__ . '/../business/WalletService.php';
require_once __DIR__ . '/../data_access/UserRepository.php';
require_once __DIR__ . '/../data_access/TransactionRepository.php';

class AuthController {
    private $authService;
    private $walletService;
    private $userRepository;
    private $transactionRepository;

    public function __construct() {
        $this->authService = new AuthService();
        $this->walletService = new WalletService();
        $this->userRepository = new UserRepository();
        $this->transactionRepository = new TransactionRepository();
    }

    /**
     * Halaman Login & Proses Autentikasi
     */
    public function login() {
        if (isset($_SESSION['user'])) {
            header("Location: index.php?page=dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            try {
                $user = $this->authService->login($email, $password);
                $_SESSION['user'] = [
                    'id'            => $user['id'],
                    'nama_depan'    => $user['nama_depan'],
                    'nama_belakang' => $user['nama_belakang'],
                    'email'         => $user['email'],
                    'no_hp'         => $user['no_hp'],
                    'role'          => $user['role'],
                    'saldo'         => (float)($user['saldo'] ?? 0.00)
                ];
                $_SESSION['success'] = "Selamat datang kembali, " . htmlspecialchars($user['nama_depan']) . "!";
                header("Location: index.php?page=dashboard");
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $_SESSION['old_email'] = $email;
                header("Location: index.php?page=login");
                exit;
            }
        }

        require __DIR__ . '/views/auth/login.php';
    }

    /**
     * Logout
     */
    public function logout() {
        unset($_SESSION['user']);
        session_destroy();
        header("Location: index.php?page=login");
        exit;
    }

    /**
     * Dashboard Interaktif Sesuai Role
     */
    public function dashboard() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $user = $_SESSION['user'];
        $role = $user['role'];
        $userId = $user['id'];

        // Sinkronisasi saldo terbaru dari DB jika role pelanggan atau admin
        if ($role === 'pelanggan' || $role === 'admin') {
            try {
                $freshBalance = $this->walletService->getBalance($userId);
                $_SESSION['user']['saldo'] = $freshBalance;
                $user['saldo'] = $freshBalance;
            } catch (Exception $e) {}
        }

        // Data transaksi pengguna (untuk Pelanggan & Admin)
        $cashFlow = ['total_masuk' => 0, 'total_keluar' => 0];
        $recentTransactions = [];
        if ($role === 'pelanggan' || $role === 'admin') {
            $cashFlow = $this->walletService->getCashFlow($userId);
            $recentTransactions = $this->walletService->getHistory($userId, 5);
        }

        // Data statistik sistem (untuk Admin & Super Admin)
        $systemStats = [];
        $recentCustomers = [];
        $todayTransactions = ['total_hari_ini' => 0, 'volume_hari_ini' => 0];
        if ($role === 'admin' || $role === 'super_admin') {
            $systemStats = $this->userRepository->getSystemStats();
            $recentCustomers = $this->userRepository->getRecentCustomers(5);
            $todayTransactions = $this->transactionRepository->getTodayTransactionsCount();
        }

        require __DIR__ . '/views/dashboard/index.php';
    }

    /**
     * Halaman Edit Profil Mandiri
     */
    public function profile() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }

        $userId = $_SESSION['user']['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->authService->updateProfile($userId, $_POST);
                
                $_SESSION['user']['nama_depan'] = $_POST['nama_depan'];
                $_SESSION['user']['nama_belakang'] = $_POST['nama_belakang'];
                $_SESSION['user']['email'] = $_POST['email'];
                $_SESSION['user']['no_hp'] = $_POST['no_hp'];

                $_SESSION['success'] = "Profil Anda berhasil diperbarui.";
                header("Location: index.php?page=profile");
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: index.php?page=profile");
                exit;
            }
        }

        require __DIR__ . '/views/profile/edit.php';
    }
}
