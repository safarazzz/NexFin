<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-people me-2"></i>Daftar & Pencarian Pelanggan</h4>
        <p class="text-muted small mb-0">Kelola data pelanggan, pencarian terfilter, dan ubah status akun</p>
    </div>
    <?php if ($currentUser['role'] === 'super_admin'): ?>
        <a href="index.php?page=customer-create" class="btn btn-primary">
            <i class="bi bi-person-plus-fill me-1"></i> Tambah Pelanggan
        </a>
    <?php endif; ?>
</div>

<!-- Form Pencarian & Filter (Searching Pelanggan) -->
<div class="card p-3 mb-4 shadow-sm">
    <form action="index.php" method="GET" class="row g-2 align-items-center">
        <input type="hidden" name="page" value="customers">
        
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="keyword" class="form-control" 
                       placeholder="Cari berdasarkan nama, email, atau nomor HP..." 
                       value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
            </div>
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua Status Akun</option>
                <option value="1" <?= (isset($_GET['status']) && $_GET['status'] === '1') ? 'selected' : '' ?>>Hanya Aktif</option>
                <option value="0" <?= (isset($_GET['status']) && $_GET['status'] === '0') ? 'selected' : '' ?>>Hanya Nonaktif</option>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-secondary w-100">
                <i class="bi bi-filter"></i> Filter / Cari
            </button>
            <?php if (!empty($_GET['keyword']) || isset($_GET['status']) && $_GET['status'] !== ''): ?>
                <a href="index.php?page=customers" class="btn btn-outline-danger" title="Reset Filter">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabel Daftar Pelanggan -->
<div class="card overflow-hidden shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width: 60px;">ID</th>
                    <th>Nama Pelanggan</th>
                    <th>Kontak (Email & HP)</th>
                    <th>Saldo</th>
                    <th class="text-center">Status Akun</th>
                    <th class="text-center" style="width: 220px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            Tidak ada data pelanggan yang cocok dengan kriteria pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="text-center fw-bold text-muted"><?= $c['id'] ?></td>
                            <td>
                                <div class="fw-bold text-dark">
                                    <?= htmlspecialchars($c['nama_depan'] . ' ' . $c['nama_belakang']) ?>
                                </div>
                                <small class="text-muted">Terdaftar: <?= date('d M Y', strtotime($c['created_at'])) ?></small>
                            </td>
                            <td>
                                <div><i class="bi bi-envelope me-1 text-muted"></i> <?= htmlspecialchars($c['email']) ?></div>
                                <div class="small text-muted"><i class="bi bi-telephone me-1"></i> <?= htmlspecialchars($c['no_hp']) ?></div>
                            </td>
                            <td>
                                <span class="fw-semibold text-success">
                                    Rp <?= number_format($c['saldo'], 2, ',', '.') ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($c['status_aktif']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check-circle me-1"></i> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="bi bi-x-circle me-1"></i> Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <!-- Tombol Pemicu Modal Ubah Status dengan Alasan -->
                                    <button type="button" 
                                            class="btn <?= $c['status_aktif'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-trigger-status"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalToggleStatus"
                                            data-id="<?= $c['id'] ?>"
                                            data-name="<?= htmlspecialchars($c['nama_depan'] . ' ' . $c['nama_belakang']) ?>"
                                            data-status="<?= $c['status_aktif'] ? 'aktif' : 'nonaktif' ?>">
                                        <i class="bi <?= $c['status_aktif'] ? 'bi-lock' : 'bi-unlock' ?>"></i>
                                        <?= $c['status_aktif'] ? 'Bekukan' : 'Aktifkan' ?>
                                    </button>

                                    <!-- Edit & Delete (Hanya Super Admin) -->
                                    <?php if ($currentUser['role'] === 'super_admin'): ?>
                                        <a href="index.php?page=customer-edit&id=<?= $c['id'] ?>" class="btn btn-outline-primary" title="Edit Data">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        <a href="index.php?page=customer-delete&id=<?= $c['id'] ?>" class="btn btn-outline-danger" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pelanggan ini secara permanen?')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL INPUT ALASAN PERUBAHAN STATUS AKUN (SOLUSI B: MEMFUNGSIKAN KOLOM `alasan`) -->
<div class="modal fade" id="modalToggleStatus" tabindex="-1" aria-labelledby="modalStatusLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formStatusToggle" action="" method="POST">
                <input type="hidden" name="from" value="customers">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalStatusLabel">Ubah Status Akun</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Anda akan mengubah status akun untuk: <strong id="modalTargetName" class="text-primary"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Tindakan <span class="text-danger">*</span></label>
                        <textarea name="alasan" id="modalAlasan" class="form-control" rows="3" required
                                  placeholder="Contoh: Terdeteksi aktivitas transfer mencurigakan / Permintaan pemulihan dari pelanggan"></textarea>
                        <div class="form-text">Alasan ini akan disimpan di basis data pada tabel <code>user_status_logs</code> untuk keperluan audit Super Admin.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnConfirmStatus" class="btn btn-primary fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const statusModal = document.getElementById('modalToggleStatus');
    if (statusModal) {
        statusModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const userName = button.getAttribute('data-name');
            const currentStatus = button.getAttribute('data-status');

            const isBekukan = (currentStatus === 'aktif');
            const actionText = isBekukan ? 'Bekukan / Nonaktifkan Akun' : 'Aktifkan Kembali Akun';
            const defaultPlaceholder = isBekukan ? 'Terdeteksi anomali transaksi atau laporan penipuan' : 'Verifikasi identitas selesai, akun dipulihkan';

            document.getElementById('modalStatusLabel').textContent = actionText;
            document.getElementById('modalTargetName').textContent = userName;
            document.getElementById('modalAlasan').placeholder = 'Contoh: ' + defaultPlaceholder;
            document.getElementById('formStatusToggle').action = 'index.php?page=customer-toggle&id=' + userId;

            const btnConfirm = document.getElementById('btnConfirmStatus');
            if (isBekukan) {
                btnConfirm.className = 'btn btn-warning fw-semibold';
                btnConfirm.textContent = 'Konfirmasi Pembekuan Akun';
            } else {
                btnConfirm.className = 'btn btn-success fw-semibold';
                btnConfirm.textContent = 'Konfirmasi Pengaktifan Akun';
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
