<?php
// ==========================================================
// NON-LAYERED: login.php
// Satu file = proses login (bisnis) + query SQL + tampilan HTML
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

// ---------- PROSES LOGIN (logika bisnis + SQL langsung) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $_SESSION['error'] = "Email dan password wajib diisi.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['error'] = "Kredensial tidak valid (Email tidak terdaftar).";
        } elseif (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = "Kredensial tidak valid (Password salah).";
        } elseif (!$user['status_aktif']) {
            $_SESSION['error'] = "Akun Anda telah dinonaktifkan oleh Administrator. Silakan hubungi layanan bantuan.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'            => $user['id'],
                'nama_depan'    => $user['nama_depan'],
                'nama_belakang' => $user['nama_belakang'],
                'email'         => $user['email'],
                'no_hp'         => $user['no_hp'],
                'role'          => $user['role'],
                'saldo'         => (float)$user['saldo'],
            ];
            $_SESSION['success'] = "Selamat datang kembali, " . htmlspecialchars($user['nama_depan']) . "!";
            header('Location: dashboard.php');
            exit;
        }
    }

    $_SESSION['old_email'] = $email;
    header('Location: login.php');
    exit;
}

// ---------- TAMPILAN ----------
require __DIR__ . '/_header.php';
?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow p-4">
            <div class="text-center mb-4">
                <i class="bi bi-wallet2 text-primary display-4"></i>
                <h3 class="fw-bold mt-2">Masuk ke NexFin</h3>
                <p class="text-muted small">Implementasi Non-Layered Architecture</p>
            </div>

            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Alamat Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="nama@domain.com" required
                               value="<?= htmlspecialchars($_SESSION['old_email'] ?? '') ?>">
                    </div>
                    <?php unset($_SESSION['old_email']); ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kata Sandi (Password)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>

            <div class="mt-4 p-3 bg-light rounded small border">
                <div class="fw-bold mb-1 text-dark"><i class="bi bi-info-circle me-1"></i> Akun Pengujian Default:</div>
                <ul class="mb-0 ps-3 text-muted">
                    <li><strong>Super Admin:</strong> <code>superadmin@nexfin.com</code></li>
                    <li><strong>Admin:</strong> <code>admin.budi@nexfin.com</code></li>
                    <li><strong>Pelanggan:</strong> <code>ahmad.fauzi@gmail.com</code></li>
                    <li><strong>Password semua akun:</strong> <code>password123</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
