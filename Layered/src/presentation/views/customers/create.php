<?php 
require __DIR__ . '/../layout/header.php'; 
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0"><i class="bi bi-person-plus text-primary me-2"></i>Tambah Pelanggan Baru</h4>
                    <p class="text-muted small mb-0">Input data pelanggan lengkap. Validasi & hashing diproses di Business Layer.</p>
                </div>
                <a href="index.php?page=customers" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <form action="index.php?page=customer-store" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Depan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_depan" class="form-control" placeholder="Contoh: Budi" required
                               value="<?= htmlspecialchars($old['nama_depan'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Belakang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_belakang" class="form-control" placeholder="Contoh: Setiawan" required
                               value="<?= htmlspecialchars($old['nama_belakang'] ?? '') ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="user@gmail.com" required
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                        <div class="form-text">Harus unik dan berformat email valid.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor Handphone <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 081234567890" required
                               value="<?= htmlspecialchars($old['no_hp'] ?? '') ?>">
                        <div class="form-text">Format Indonesia (diawali 08/628, 10-13 digit).</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Kata Sandi (Password) <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        <div class="form-text">Akan otomatis di-hash menggunakan algoritma <strong>Bcrypt</strong>.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Saldo Awal (Rp)</label>
                        <input type="number" step="1000" name="saldo" class="form-control" placeholder="0" 
                               value="<?= htmlspecialchars($old['saldo'] ?? '0') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Status Akun</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= ($old['status_aktif'] ?? '1') == '1' ? 'selected' : '' ?>>Aktif (Dapat Login & Transaksi)</option>
                        <option value="0" <?= ($old['status_aktif'] ?? '') == '0' ? 'selected' : '' ?>>Nonaktif / Dibekukan</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="index.php?page=customers" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Pelanggan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

