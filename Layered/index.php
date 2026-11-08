<?php
// ==========================================================
// ENTRY POINT / FRONT CONTROLLER: Layered Architecture
// Bertugas mengarahkan request ke Controller di Presentation Layer
// ==========================================================

session_start();

require_once __DIR__ . '/src/presentation/AuthController.php';
require_once __DIR__ . '/src/presentation/CustomerController.php';

$authController = new AuthController();
$customerController = new CustomerController();

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

    // --- Manajemen Pelanggan (CRUD & Search) ---
    case 'customers':
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $customerController->index();
        break;

    case 'customer-create':
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $customerController->create();
        break;

    case 'customer-store':
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $customerController->store();
        break;

    case 'customer-edit':
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $customerController->edit($id);
        break;

    case 'customer-update':
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $customerController->update($id);
        break;

    case 'customer-delete':
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $customerController->delete($id);
        break;

    case 'customer-toggle':
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
        $customerController->toggleStatus($id);
        break;

    default:
        header("Location: index.php?page=dashboard");
        exit;
}

