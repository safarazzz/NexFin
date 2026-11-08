<?php
$currentUser = $_SESSION['user'] ?? null;
$page = $_GET['page'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexFin - Layered Architecture</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .navbar-brand { font-weight: 700; letter-spacing: -0.5px; }
        .badge-role { font-size: 0.75rem; text-transform: uppercase; }
        .card { border-radius: 10px; border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
        .hover-shadow:hover { box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important; transition: all 0.2s ease; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="index.php?page=dashboard">
            <i class="bi bi-wallet2 text-light me-1"></i> NexFin <span class="badge bg-secondary fs-6 fw-normal">Layered</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <?php if ($currentUser): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $page === 'dashboard' ? 'active' : '' ?>" href="index.php?page=dashboard">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>

                    <!-- Menu Dompet Digital & Riwayat Pribadi (Pelanggan & Admin) -->
                    <?php if ($currentUser['role'] === 'pelanggan' || $currentUser['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'wallet' ? 'active' : '' ?>" href="index.php?page=wallet">
                                <i class="bi bi-wallet2"></i> Dompet
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'wallet-history' ? 'active' : '' ?>" href="index.php?page=wallet-history">
                                <i class="bi bi-clock-history"></i> Riwayat Saya
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Menu Kelola Pelanggan (Admin & Super Admin) -->
                    <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($page === 'customers' || $page === 'customer-create' || $page === 'customer-edit') ? 'active' : '' ?>" href="index.php?page=customers">
                                <i class="bi bi-people"></i> Kelola Pelanggan
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Menu Transaksi Pelanggan (Khusus Pengawasan Admin & Super Admin) -->
                    <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'customer-transactions' ? 'active' : '' ?>" href="index.php?page=customer-transactions">
                                <i class="bi bi-receipt-cutoff"></i> Transaksi Pelanggan
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Menu Khusus Super Admin: Pengelolaan Admin & Aktivitas Admin -->
                    <?php if ($currentUser['role'] === 'super_admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'admins' ? 'active' : '' ?>" href="index.php?page=admins">
                                <i class="bi bi-shield-lock"></i> Pengelolaan Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $page === 'admin-activities' ? 'active' : '' ?>" href="index.php?page=admin-activities">
                                <i class="bi bi-activity"></i> Aktivitas Admin
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if (isset($currentUser['saldo']) && ($currentUser['role'] === 'pelanggan' || $currentUser['role'] === 'admin')): ?>
                        <li class="nav-item me-3 text-light">
                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1">
                                <i class="bi bi-cash-stack me-1"></i> Rp <?= number_format($currentUser['saldo'], 0, ',', '.') ?>
                            </span>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item me-3 text-light">
                        <span class="badge bg-primary badge-role me-1"><?= htmlspecialchars(str_replace('_', ' ', $currentUser['role'])) ?></span>
                        <strong><?= htmlspecialchars($currentUser['nama_depan']) ?></strong>
                    </li>
                    <li class="nav-item me-2">
                        <a href="index.php?page=profile" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-person-gear"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php?page=logout" class="btn btn-sm btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <!-- Flash Messages (Notifikasi Sukses / Error) -->
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
