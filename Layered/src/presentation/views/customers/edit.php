<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Data Pelanggan</h4>
                    <p class="text-muted small mb-0">Ubah profil pelanggan. Perubahan divalidasi oleh CustomerService.</p>
                </div>
                <a href="index.php?page=customers" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <form action="index.php?page=customer-update&id=<?= $customer['id'] ?>" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Depan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_depan" class="form-control" required
                               value="<?= htmlspecialchars($customer['nama_depan']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Belakang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_belakang" class="form-control" required
                               value="<?= htmlspecialchars($customer['nama_belakang']) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($customer['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor Handphone <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" required
                               value="<?= htmlspecialchars($customer['no_hp']) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kata Sandi Baru (Opsional)</label>
                        <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diubah">
                        <div class="form-text">Biarkan kosong jika tidak ingin mengubah password lama.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Saldo (Rp)</label>
                        <input type="number" step="1000" name="saldo" class="form-control" 
                               value="<?= htmlspecialchars($customer['saldo']) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Status Akun</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= $customer['status_aktif'] ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= !$customer['status_aktif'] ? 'selected' : '' ?>>Nonaktif / Dibekukan</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=customers" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

