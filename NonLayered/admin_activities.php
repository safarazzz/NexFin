<?php
// ==========================================================
// NON-LAYERED: admin_activities.php
// Audit log aktivitas admin (khusus Super Admin)
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user']['role'] !== 'super_admin') {
    $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat memantau aktivitas Admin.";
    header('Location: dashboard.php');
    exit;
}

$activities = $pdo->query("
    SELECT l.id, l.status_sebelum, l.status_sesudah, l.alasan, l.created_at,
           CONCAT(ua.nama_depan, ' ', ua.nama_belakang) AS nama_admin, ua.email AS email_admin, ua.role AS role_admin,
           CONCAT(ut.nama_depan, ' ', ut.nama_belakang) AS nama_target, ut.email AS email_target, ut.role AS role_target
    FROM user_status_logs l
    INNER JOIN users ua ON l.changed_by = ua.id
    INNER JOIN users ut ON l.target_user_id = ut.id
    ORDER BY l.created_at DESC, l.id DESC
    LIMIT 100
")->fetchAll();

require __DIR__ . '/_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-activity text-warning me-2"></i>Riwayat Aktivitas Admin (Recent Activity)</h4>
        <p class="text-muted small mb-0">Catatan audit log pengawasan: Memantau tindakan yang diambil oleh para Admin terhadap akun pengguna</p>
    </div>
    <a href="admins.php" class="btn btn-outline-primary btn-sm">
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
                                <small class="text-muted"><?= htmlspecialchars($act['email_admin']) ?> (<?= htmlspecialchars(strtoupper($act['role_admin'])) ?>)</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($act['nama_target']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($act['email_target']) ?> (<?= htmlspecialchars(strtoupper($act['role_target'])) ?>)</small>
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

<?php require __DIR__ . '/_footer.php'; ?>
