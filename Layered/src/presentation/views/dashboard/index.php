<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="row g-4">
    <!-- Kartu Sambutan & Profil Singkat -->
    <div class="col-md-8">
        <div class="card p-4">
            <h4 class="fw-bold mb-1">
                Halo, <?= htmlspecialchars($currentUser['nama_depan'] . ' ' . $currentUser['nama_belakang']) ?>!
            </h4>
            <p class="text-muted">
                Anda masuk sebagai <span class="badge bg-primary text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', $currentUser['role'])) ?></span>
            </p>

            <hr>

            <?php if ($currentUser['role'] === 'pelanggan'): ?>
                <div class="p-3 bg-light rounded border mb-3">
                    <span class="text-muted small">Saldo Rekening Anda:</span>
                    <h2 class="text-success fw-bold mt-1">
                        Rp <?= number_format($currentUser['saldo'], 2, ',', '.') ?>
                    </h2>
                </div>
                <div class="d-flex gap-2">
                    <a href="index.php?page=profile" class="btn btn-outline-primary">
                        <i class="bi bi-person-gear"></i> Edit Profil Saya
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-info d-flex align-items-center">
                    <i class="bi bi-shield-check fs-2 me-3"></i>
                    <div>
                        <div class="fw-bold">Akses Manajemen Pengguna Aktif</div>
                        <div class="small">
                            <?php if ($currentUser['role'] === 'super_admin'): ?>
                                Anda memiliki wewenang penuh untuk <strong>CRUD Pelanggan & Admin</strong> serta mengaktifkan/menonaktifkan akun.
                            <?php else: ?>
                                Anda memiliki wewenang untuk <strong>Mencari & Melihat Data Pelanggan</strong> serta mengaktifkan/menonaktifkan akun.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <a href="index.php?page=customers" class="btn btn-primary">
                        <i class="bi bi-people-fill me-1"></i> Buka Manajemen Pelanggan
                    </a>
                    <?php if ($currentUser['role'] === 'super_admin'): ?>
                        <a href="index.php?page=customer-create" class="btn btn-success">
                            <i class="bi bi-person-plus-fill me-1"></i> Tambah Pelanggan Baru
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informasi Arsitektur Layered -->
    <div class="col-md-4">
        <div class="card p-3 bg-white">
            <h6 class="fw-bold text-dark border-bottom pb-2">
                <i class="bi bi-layers me-1 text-primary"></i> Struktur Layered Architecture
            </h6>
            <ul class="list-unstyled small mb-0">
                <li class="mb-2">
                    <span class="badge bg-secondary">1. Presentation</span><br>
                    <span class="text-muted"><code>AuthController</code> & <code>CustomerController</code> menerima request HTTP & memilih View.</span>
                </li>
                <li class="mb-2">
                    <span class="badge bg-secondary">2. Business Logic</span><br>
                    <span class="text-muted"><code>CustomerService</code> & <code>AuthService</code> memvalidasi regex no HP/email, Bcrypt password, & aturan status aktif.</span>
                </li>
                <li>
                    <span class="badge bg-secondary">3. Data Access</span><br>
                    <span class="text-muted"><code>UserRepository</code> menjalankan query SQL PDO berparameter (Prepared Statements).</span>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

