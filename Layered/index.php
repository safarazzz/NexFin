<?php
// ==========================================================
// ENTRY POINT / FRONT CONTROLLER: Layered Architecture
// Bertugas mengarahkan request ke Controller di Presentation Layer
// ==========================================================

session_start();

require_once __DIR__ . '/src/presentation/AuthController.php';
require_once __DIR__ . '/src/presentation/CustomerController.php';
require_once __DIR__ . '/src/presentation/WalletController.php';

$authController = new AuthController();
$customerController = new CustomerController();
$walletController = new WalletController();

$page = $_GET['page'] ?? (isset($_SESSION['user']) ? 'dashboard' : 'login');
$id = $_GET['id'] ?? null;

switch ($page) {
    // --- Autentikasi & Dashboard ---
    case 'login':
        $authController->login();
        break;

    case 'logout':
        $authController->logout();
        break;

    case 'dashboard':
        $authController->dashboard();
        break;

    case 'profile':
        $authController->profile();
        break;

    // --- Dompet Digital & Transaksi Keuangan (Pelanggan & Admin) ---
    case 'wallet':
        $walletController->index();
        break;

    case 'wallet-history':
        $walletController->history();
        break;

    case 'wallet-topup':
        $walletController->processTopUp();
        break;

    case 'wallet-transfer':
        $walletController->processTransfer();
        break;

    case 'wallet-withdraw':
        $walletController->processWithdraw();
        break;

    // --- Manajemen Pelanggan (CRUD & Search) ---
    case 'customers':
        $customerController->index();
        break;

    case 'customer-create':
        $customerController->create();
        break;

    case 'customer-store':
        $customerController->store();
        break;

    case 'customer-edit':
        $customerController->edit($id);
        break;

    case 'customer-update':
        $customerController->update($id);
        break;

    case 'customer-delete':
        $customerController->delete($id);
        break;

    case 'customer-toggle':
        $customerController->toggleStatus($id);
        break;

    // --- Riwayat Transaksi Para Pelanggan (Khusus Pengawasan Admin & Super Admin) ---
    case 'customer-transactions':
        $customerController->customerTransactions();
        break;

    // --- Pengelolaan Admin & Aktivitas Admin (Khusus Super Admin) ---
    case 'admins':
        $customerController->admins();
        break;

    case 'admin-promote':
        $customerController->promoteAdmin();
        break;

    case 'admin-demote':
        $customerController->demoteAdmin();
        break;

    case 'admin-activities':
        $customerController->adminActivities();
        break;

    default:
        header("Location: index.php?page=dashboard");
        exit;
}
