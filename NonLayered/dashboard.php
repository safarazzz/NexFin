<?php
// ==========================================================
// NON-LAYERED: dashboard.php
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Sinkronisasi saldo terbaru langsung dari DB (pelanggan & admin)
if ($user['role'] === 'pelanggan' || $user['role'] === 'admin') {
    $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = :id");
    $stmt->execute(['id' => $user['id']]);
    $saldo = $stmt->fetchColumn();
    if ($saldo !== false) {
        $_SESSION['user']['saldo'] = (float)$saldo;
        $user['saldo'] = (float)$saldo;
    }
}

require __DIR__ . '/_header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card p-4 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="fw-bold mb-1 text-dark">
                        Selamat Datang, <?= htmlspecialchars($user['nama_depan'] . ' ' . $user['nama_belakang']) ?>!
                    </h3>
                    <p class="text-muted small mb-0">
                        Anda masuk sebagai <span class="badge bg-primary text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', $user['role'])) ?></span>
                    </p>
                </div>
                <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-person-gear"></i> Profil Saya
                </a>
            </div>

            <hr>

            <?php if ($user['role'] === 'pelanggan' || $user['role'] === 'admin'): ?>
                <div class="p-4 bg-light rounded border mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small text-uppercase fw-semibold">Saldo Rekening Anda</span>
                            <h2 class="text-success fw-bold mb-0 mt-1">
                                Rp <?= number_format($user['saldo'], 2, ',', '.') ?>
                            </h2>
                            <small class="text-muted">No. Rekening / Virtual ID: <strong><?= htmlspecialchars($user['no_hp']) ?></strong></small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="wallet.php" class="btn btn-primary">
                                <i class="bi bi-wallet2 me-1"></i> Buka Dompet Digital
                            </a>
                            <a href="wallet_history.php" class="btn btn-outline-primary">
                                <i class="bi bi-clock-history me-1"></i> Riwayat Saya
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <h6 class="fw-bold text-dark text-uppercase small mb-3">Menu Operasional Anda</h6>

            <div class="row g-3">
                <?php if ($user['role'] === 'super_admin'): ?>
                    <div class="col-md-4">
                        <a href="customers.php" class="card text-decoration-none p-3 h-100 border text-center hover-shadow">
                            <i class="bi bi-people text-primary fs-2 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">Kelola Pelanggan</h6>
                            <small class="text-muted">CRUD data seluruh pelanggan</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="admins.php" class="card text-decoration-none p-3 h-100 border text-center hover-shadow">
                            <i class="bi bi-shield-lock text-success fs-2 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">Pengelolaan Admin</h6>
                            <small class="text-muted">Angkat pelanggan jadi admin</small>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="admin_activities.php" class="card text-decoration-none p-3 h-100 border text-center hover-shadow">
                            <i class="bi bi-activity text-warning fs-2 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">Aktivitas Admin</h6>
                            <small class="text-muted">Pantau log supervisi admin</small>
                        </a>
                    </div>
                <?php elseif ($user['role'] === 'admin'): ?>
                    <div class="col-md-6">
                        <a href="customers.php" class="card text-decoration-none p-3 h-100 border text-center hover-shadow">
                            <i class="bi bi-people text-primary fs-2 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">Daftar &amp; Cari Pelanggan</h6>
                            <small class="text-muted">Pencarian data dan freeze akun</small>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="customer_transactions.php" class="card text-decoration-none p-3 h-100 border text-center hover-shadow">
                            <i class="bi bi-receipt-cutoff text-success fs-2 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">Transaksi Pelanggan</h6>
                            <small class="text-muted">Lihat riwayat transaksi para pelanggan</small>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="col-md-6">
                        <a href="wallet.php" class="card text-decoration-none p-3 h-100 border text-center hover-shadow">
                            <i class="bi bi-arrow-left-right text-primary fs-2 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">Top Up &amp; Transfer</h6>
                            <small class="text-muted">Kelola dana dan kirim saldo</small>
                        </a>
                    </div>
                    <div class="col-md-6">
                        <a href="wallet_history.php" class="card text-decoration-none p-3 h-100 border text-center hover-shadow">
                            <i class="bi bi-clock-history text-secondary fs-2 mb-2"></i>
                            <h6 class="fw-bold text-dark mb-1">Riwayat Transaksi</h6>
                            <small class="text-muted">Cek mutasi dana masuk &amp; keluar</small>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
