<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-shield-lock text-primary me-2"></i>Pengelolaan Admin</h4>
        <p class="text-muted small mb-0">Kelola akun Admin operasional dan angkat pelanggan berprestasi menjadi Admin</p>
    </div>
    <a href="index.php?page=admin-activities" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-activity me-1"></i> Pantau Aktivitas Admin
    </a>
</div>

<div class="row g-4">
    <!-- Kolom Kiri: Tabel Daftar Admin Aktif -->
    <div class="col-lg-8">
        <div class="card shadow-sm overflow-hidden">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-badge me-2"></i>Daftar Admin NexFin</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Admin</th>
                            <th>Kontak</th>
                            <th>Saldo Rekening</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi Super Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admins)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Belum ada akun Admin terdaftar.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($admins as $adm): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($adm['nama_depan'] . ' ' . $adm['nama_belakang']) ?></div>
                                        <small class="text-muted">Diangkat oleh: <?= htmlspecialchars($adm['diangkat_oleh'] ?: 'Sistem') ?></small>
                                    </td>
                                    <td>
                                        <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($adm['email']) ?></div>
                                        <small class="text-muted"><i class="bi bi-phone me-1"></i><?= htmlspecialchars($adm['no_hp']) ?></small>
                                    </td>
                                    <td class="text-success fw-semibold">
                                        Rp <?= number_format($adm['saldo'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($adm['status_aktif']): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <!-- Tombol Pemicu Modal Ubah Status Admin -->
                                            <button type="button" 
                                                    class="btn <?= $adm['status_aktif'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalToggleStatusAdmin"
                                                    data-id="<?= $adm['id'] ?>"
                                                    data-name="<?= htmlspecialchars($adm['nama_depan'] . ' ' . $adm['nama_belakang']) ?>"
                                                    data-status="<?= $adm['status_aktif'] ? 'aktif' : 'nonaktif' ?>"
                                                    title="<?= $adm['status_aktif'] ? 'Bekukan' : 'Aktifkan' ?>">
                                                <i class="bi <?= $adm['status_aktif'] ? 'bi-lock' : 'bi-unlock' ?>"></i>
                                                <?= $adm['status_aktif'] ? 'Bekukan' : 'Aktifkan' ?>
                                            </button>

                                            <!-- Turunkan ke Pelanggan Biasa -->
                                            <form action="index.php?page=admin-demote" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menurunkan peran Admin ini menjadi Pelanggan biasa?')">
                                                <input type="hidden" name="admin_id" value="<?= $adm['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger" title="Turunkan ke Pelanggan">
                                                    <i class="bi bi-arrow-down-circle"></i> Turunkan
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Form Angkat Pelanggan Menjadi Admin -->
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border-top border-primary border-4">
            <h5 class="fw-bold mb-2"><i class="bi bi-person-fill-up text-primary me-2"></i>Angkat Jadi Admin</h5>
            <p class="text-muted small mb-3">Pilih pelanggan aktif untuk diberikan hak akses dan wewenang sebagai Admin.</p>

            <form action="index.php?page=admin-promote" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Pelanggan Calon Admin</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php foreach ($candidates as $cand): ?>
                            <option value="<?= $cand['id'] ?>">
                                <?= htmlspecialchars($cand['nama_depan'] . ' ' . $cand['nama_belakang']) ?> (<?= htmlspecialchars($cand['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Hanya pelanggan berstatus aktif yang dapat dipilih.</div>
                </div>

                <div class="alert alert-info small mb-3">
                    <i class="bi bi-info-circle me-1"></i> Setelah diangkat, akun ini akan dapat mencari pelanggan, melihat transaksi pelanggan, dan membekukan akun. Saldo pelanggan tetap terjaga.
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" onclick="return confirm('Konfirmasi pengangkatan pelanggan ini menjadi Admin?')">
                    <i class="bi bi-shield-plus me-1"></i> Angkat Menjadi Admin
                </button>
            </form>
        </div>
    </div>
</div>

<!-- MODAL INPUT ALASAN PERUBAHAN STATUS ADMIN -->
<div class="modal fade" id="modalToggleStatusAdmin" tabindex="-1" aria-labelledby="modalAdminStatusLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formStatusToggleAdmin" action="" method="POST">
                <input type="hidden" name="from" value="admins">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalAdminStatusLabel">Ubah Status Akun Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">Anda akan mengubah status akun Admin: <strong id="modalAdminTargetName" class="text-primary"></strong></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Alasan Tindakan Super Admin <span class="text-danger">*</span></label>
                        <textarea name="alasan" id="modalAdminAlasan" class="form-control" rows="3" required
                                  placeholder="Contoh: Evaluasi kedisiplinan / rotasi tugas operasional"></textarea>
                        <div class="form-text">Alasan ini akan disimpan di basis data pada tabel <code>user_status_logs</code>.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnAdminConfirmStatus" class="btn btn-primary fw-semibold">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const adminStatusModal = document.getElementById('modalToggleStatusAdmin');
    if (adminStatusModal) {
        adminStatusModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-id');
            const userName = button.getAttribute('data-name');
            const currentStatus = button.getAttribute('data-status');

            const isBekukan = (currentStatus === 'aktif');
            const actionText = isBekukan ? 'Bekukan / Nonaktifkan Akun Admin' : 'Aktifkan Kembali Akun Admin';
            const defaultPlaceholder = isBekukan ? 'Penyalahgunaan wewenang / evaluasi internal' : 'Pemulihan akses operasional Admin';

            document.getElementById('modalAdminStatusLabel').textContent = actionText;
            document.getElementById('modalAdminTargetName').textContent = userName;
            document.getElementById('modalAdminAlasan').placeholder = 'Contoh: ' + defaultPlaceholder;
            document.getElementById('formStatusToggleAdmin').action = 'index.php?page=customer-toggle&id=' + userId;

            const btnConfirm = document.getElementById('btnAdminConfirmStatus');
            if (isBekukan) {
                btnConfirm.className = 'btn btn-warning fw-semibold';
                btnConfirm.textContent = 'Konfirmasi Pembekuan Admin';
            } else {
                btnConfirm.className = 'btn btn-success fw-semibold';
                btnConfirm.textContent = 'Konfirmasi Pengaktifan Admin';
            }
        });
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
