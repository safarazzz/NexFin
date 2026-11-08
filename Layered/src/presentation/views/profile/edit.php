<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0"><i class="bi bi-person-gear text-primary me-2"></i>Edit Profil Saya</h4>
                    <p class="text-muted small mb-0">Ubah data identitas akun Anda sendiri</p>
                </div>
                <a href="index.php?page=dashboard" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <form action="index.php?page=profile" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Depan</label>
                        <input type="text" name="nama_depan" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['nama_depan']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Belakang</label>
                        <input type="text" name="nama_belakang" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['nama_belakang']) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alamat Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor Handphone</label>
                        <input type="text" name="no_hp" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['no_hp']) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Ganti Kata Sandi Baru (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password">
                    <div class="form-text">Jika diisi, minimal 6 karakter dan akan di-hash ulang dengan Bcrypt.</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=dashboard" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

