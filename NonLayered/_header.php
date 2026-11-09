<?php
// Potongan HTML (navbar + flash message) yang di-include oleh semua halaman.
$currentUser = $_SESSION['user'] ?? null;
$activePage  = basename($_SERVER['SCRIPT_NAME'], '.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexFin - Non-Layered Architecture</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        <a class="navbar-brand text-primary fw-bold" href="dashboard.php">
            <i class="bi bi-wallet2 text-light me-1"></i> NexFin <span class="badge bg-warning text-dark fs-6 fw-normal">Non-Layered</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <?php if ($currentUser): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>

                    <?php if ($currentUser['role'] === 'pelanggan' || $currentUser['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage === 'wallet' ? 'active' : '' ?>" href="wallet.php">
                                <i class="bi bi-wallet2"></i> Dompet
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage === 'wallet_history' ? 'active' : '' ?>" href="wallet_history.php">
                                <i class="bi bi-clock-history"></i> Riwayat Saya
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($currentUser['role'] === 'admin' || $currentUser['role'] === 'super_admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($activePage === 'customers' || $activePage === 'customer_form') ? 'active' : '' ?>" href="customers.php">
                                <i class="bi bi-people"></i> Kelola Pelanggan
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage === 'customer_transactions' ? 'active' : '' ?>" href="customer_transactions.php">
                                <i class="bi bi-receipt-cutoff"></i> Transaksi Pelanggan
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php if ($currentUser['role'] === 'super_admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage === 'admins' ? 'active' : '' ?>" href="admins.php">
                                <i class="bi bi-shield-lock"></i> Pengelolaan Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activePage === 'admin_activities' ? 'active' : '' ?>" href="admin_activities.php">
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
                        <a href="profile.php" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-person-gear"></i> Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="btn btn-sm btn-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container pb-5">
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
