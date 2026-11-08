<?php
// ==========================================================
// PRESENTATION LAYER: CustomerController
// Menangani request HTTP terkait manajemen pelanggan,
// pengangkatan admin (Super Admin), dan riwayat aktivitas admin
// ==========================================================

require_once __DIR__ . '/../business/CustomerService.php';
require_once __DIR__ . '/../business/WalletService.php';

class CustomerController {
    private $customerService;
    private $walletService;

    public function __construct() {
        $this->customerService = new CustomerService();
        $this->walletService = new WalletService();
    }

    private function requireAuth() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
    }

    /**
     * Menampilkan daftar pelanggan dengan fitur Searching & Filtering
     */
    public function index() {
        $this->requireAuth();
        $keyword = $_GET['keyword'] ?? '';
        $status = $_GET['status'] ?? '';

        $customers = $this->customerService->getCustomers($keyword, $status);
        require __DIR__ . '/views/customers/index.php';
    }

    /**
     * Menampilkan form tambah pelanggan baru (Khusus Super Admin)
     */
    public function create() {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat menambah pelanggan manual.";
            header("Location: index.php?page=customers");
            exit;
        }

        require __DIR__ . '/views/customers/create.php';
    }

    /**
     * Memproses data form penambahan pelanggan
     */
    public function store() {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak.";
            header("Location: index.php?page=customers");
            exit;
        }

        try {
            $createdById = $_SESSION['user']['id'];
            $this->customerService->createCustomer($_POST, $createdById);
            $_SESSION['success'] = "Pelanggan baru berhasil ditambahkan.";
            header("Location: index.php?page=customers");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['old_input'] = $_POST;
            header("Location: index.php?page=customer-create");
            exit;
        }
    }

    /**
     * Menampilkan form edit pelanggan (Khusus Super Admin)
     */
    public function edit($id) {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat mengedit data pelanggan.";
            header("Location: index.php?page=customers");
            exit;
        }

        try {
            $customer = $this->customerService->getCustomerById($id);
            require __DIR__ . '/views/customers/edit.php';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: index.php?page=customers");
            exit;
        }
    }

    /**
     * Memproses pembaruan data pelanggan
     */
    public function update($id) {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak.";
            header("Location: index.php?page=customers");
            exit;
        }

        try {
            $this->customerService->updateCustomer($id, $_POST);
            $_SESSION['success'] = "Data pelanggan berhasil diperbarui.";
            header("Location: index.php?page=customers");
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header("Location: index.php?page=customer-edit&id=" . $id);
            exit;
        }
    }

    /**
     * Memproses penghapusan pelanggan (Khusus Super Admin)
     */
    public function delete($id) {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat menghapus pelanggan.";
            header("Location: index.php?page=customers");
            exit;
        }

        try {
            $this->customerService->deleteCustomer($id);
            $_SESSION['success'] = "Data pelanggan berhasil dihapus.";
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=customers");
        exit;
    }

    /**
     * Mengaktifkan atau menonaktifkan status akun pelanggan / admin
     */
    public function toggleStatus($id) {
        $this->requireAuth();
        $actor = $_SESSION['user'];

        if ($actor['role'] !== 'admin' && $actor['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak.";
            header("Location: index.php?page=customers");
            exit;
        }

        $alasan = trim($_POST['alasan'] ?? $_GET['alasan'] ?? '');
        if (empty($alasan)) {
            $alasan = 'Aksi perubahan status oleh ' . $actor['nama_depan'];
        }
        $redirect = (($_POST['from'] ?? $_GET['from'] ?? '') === 'admins') ? 'admins' : 'customers';

        try {
            $newStatus = $this->customerService->toggleStatus($id, $actor['id'], $actor['role'], $alasan);
            $statusText = $newStatus ? "diaktifkan" : "dinonaktifkan/dibekukan";
            $_SESSION['success'] = "Status akun berhasil {$statusText}.";
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=" . $redirect);
        exit;
    }

    /**
     * Pengelolaan Admin (Khusus Super Admin): Menampilkan daftar admin dan calon dari pelanggan
     */
    public function admins() {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat mengelola data Admin.";
            header("Location: index.php?page=dashboard");
            exit;
        }

        $admins = $this->customerService->getAdmins();
        // Mengambil seluruh pelanggan aktif sebagai kandidat untuk diangkat menjadi admin
        $candidates = $this->customerService->getCustomers('', '1');

        require __DIR__ . '/views/admins/index.php';
    }

    /**
     * Memproses Pengangkatan Pelanggan Menjadi Admin (Khusus Super Admin)
     */
    public function promoteAdmin() {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak.";
            header("Location: index.php?page=dashboard");
            exit;
        }

        $customerId = $_POST['customer_id'] ?? null;
        if (!$customerId) {
            $_SESSION['error'] = "Silakan pilih pelanggan yang ingin diangkat menjadi Admin.";
            header("Location: index.php?page=admins");
            exit;
        }

        try {
            $superAdminId = $_SESSION['user']['id'];
            $this->customerService->promoteCustomerToAdmin($customerId, $superAdminId);
            $_SESSION['success'] = "Pelanggan berhasil diangkat menjadi Admin.";
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=admins");
        exit;
    }

    /**
     * Menurunkan status Admin kembali menjadi Pelanggan biasa (Khusus Super Admin)
     */
    public function demoteAdmin() {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak.";
            header("Location: index.php?page=dashboard");
            exit;
        }

        $adminId = $_POST['admin_id'] ?? null;
        try {
            $superAdminId = $_SESSION['user']['id'];
            $this->customerService->demoteAdminToCustomer($adminId, $superAdminId);
            $_SESSION['success'] = "Admin berhasil diturunkan perannya menjadi Pelanggan biasa.";
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=admins");
        exit;
    }

    /**
     * Riwayat Aktivitas Seluruh Admin (Recent Activity - Khusus Super Admin)
     */
    public function adminActivities() {
        $this->requireAuth();
        if ($_SESSION['user']['role'] !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat memantau aktivitas Admin.";
            header("Location: index.php?page=dashboard");
            exit;
        }

        $activities = $this->customerService->getAdminActivities(100);
        require __DIR__ . '/views/admins/activities.php';
    }

    /**
     * Riwayat Transaksi Para Pelanggan (Untuk Admin & Super Admin)
     * Admin TIDAK DAPAT melihat transaksi Admin lain!
     */
    public function customerTransactions() {
        $this->requireAuth();
        $role = $_SESSION['user']['role'];
        if ($role !== 'admin' && $role !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak.";
            header("Location: index.php?page=dashboard");
            exit;
        }

        $keyword = $_GET['keyword'] ?? '';
        $transactions = $this->walletService->getCustomerTransactionsForAdmin($keyword);

        require __DIR__ . '/views/customers/transactions.php';
    }
}
