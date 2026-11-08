<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-5">
        <div class="card shadow p-4">
            <div class="text-center mb-4">
                <i class="bi bi-wallet2 text-primary display-4"></i>
                <h3 class="fw-bold mt-2">Masuk ke NexFin</h3>
                <p class="text-muted small">Implementasi Layered Architecture</p>
            </div>

            <form action="index.php?page=login" method="POST">
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

<?php require __DIR__ . '/../layout/footer.php'; ?>

