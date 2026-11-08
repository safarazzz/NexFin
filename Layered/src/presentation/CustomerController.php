<?php
// ==========================================================
// PRESENTATION LAYER: CustomerController
// Hanya bertugas menangani request HTTP (GET/POST),
// memanggil Business Layer (CustomerService),
// dan mengarahkan ke View yang sesuai
// ==========================================================

require_once __DIR__ . '/../business/CustomerService.php';

class CustomerController {
    private $customerService;

    public function __construct() {
        $this->customerService = new CustomerService();
    }

    /**
     * Menampilkan daftar pelanggan dengan fitur Searching & Filtering
     */
    public function index() {
        $keyword = $_GET['keyword'] ?? '';
        $status = $_GET['status'] ?? '';

        $customers = $this->customerService->getCustomers($keyword, $status);
        
        // Memuat View
        require __DIR__ . '/views/customers/index.php';
    }

    /**
     * Menampilkan form tambah pelanggan baru (Khusus Super Admin)
     */
    public function create() {
        // Cek otorisasi
        if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
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
        if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
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
        if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
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
        if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
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
        if (($_SESSION['user']['role'] ?? '') !== 'super_admin') {
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
     * Mengaktifkan atau menonaktifkan status akun pelanggan (Admin & Super Admin)
     */
    public function toggleStatus($id) {
        $userRole = $_SESSION['user']['role'] ?? '';
        if ($userRole !== 'admin' && $userRole !== 'super_admin') {
            $_SESSION['error'] = "Akses ditolak.";
            header("Location: index.php?page=customers");
            exit;
        }

        try {
            $this->customerService->toggleStatus($id);
            $_SESSION['success'] = "Status akun pelanggan berhasil diubah.";
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=customers");
        exit;
    }
}

