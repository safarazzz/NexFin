<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-activity text-warning me-2"></i>Riwayat Aktivitas Admin (Recent Activity)</h4>
        <p class="text-muted small mb-0">Catatan audit log pengawasan: Memantau tindakan yang diambil oleh para Admin terhadap akun pengguna</p>
    </div>
    <a href="index.php?page=admins" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-shield-lock me-1"></i> Pengelolaan Admin
    </a>
</div>

<div class="card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>Waktu Aksi</th>
                    <th>Admin Pelaksana</th>
                    <th>Akun Target</th>
                    <th class="text-center">Perubahan Status</th>
                    <th>Keterangan / Alasan Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada riwayat aktivitas tindakan yang tercatat oleh para Admin.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($activities as $idx => $act): ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($act['created_at'])) ?></div>
                                <small class="text-muted"><?= date('H:i:s', strtotime($act['created_at'])) ?> WIB</small>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    <i class="bi bi-person-badge text-primary me-1"></i>
                                    <?= htmlspecialchars($act['nama_admin']) ?>
                                </div>
                                <small class="text-muted"><?= htmlspecialchars($act['email_admin']) ?> (<?= strtoupper($act['role_admin']) ?>)</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($act['nama_target']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($act['email_target']) ?> (<?= strtoupper($act['role_target']) ?>)</small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <span class="badge <?= $act['status_sebelum'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $act['status_sebelum'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                    <i class="bi bi-arrow-right text-muted"></i>
                                    <span class="badge <?= $act['status_sesudah'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $act['status_sesudah'] ? 'Aktif' : 'Nonaktif' ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border p-2 text-wrap text-start">
                                    <?= htmlspecialchars($act['alasan']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

