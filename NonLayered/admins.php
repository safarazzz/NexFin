<?php
// ==========================================================
// NON-LAYERED: admins.php
// Pengelolaan Admin (khusus Super Admin):
// daftar admin, angkat pelanggan jadi admin, turunkan admin.
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user']['role'] !== 'super_admin') {
    $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat mengelola data Admin.";
    header('Location: dashboard.php');
    exit;
}

$superAdminId = (int)$_SESSION['user']['id'];

// ---------- PROSES POST: promote / demote ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        $pdo->beginTransaction();

        if ($action === 'promote') {
            $customerId = (int)($_POST['customer_id'] ?? 0);
            if ($customerId <= 0) {
                throw new RuntimeException("Silakan pilih pelanggan yang ingin diangkat menjadi Admin.");
            }

            $stmt = $pdo->prepare("SELECT id, nama_depan, nama_belakang, status_aktif FROM users WHERE id = :id AND role = 'pelanggan' FOR UPDATE");
            $stmt->execute(['id' => $customerId]);
            $cust = $stmt->fetch();
            if (!$cust)                 throw new RuntimeException("Data pelanggan tidak ditemukan.");
            if (!$cust['status_aktif']) throw new RuntimeException("Pelanggan dalam status nonaktif tidak dapat diangkat menjadi Admin.");

            $pdo->prepare("UPDATE users SET role = 'admin', created_by = :sa WHERE id = :id AND role = 'pelanggan'")
                ->execute(['sa' => $superAdminId, 'id' => $customerId]);

            $pdo->prepare("INSERT INTO user_status_logs (target_user_id, changed_by, status_sebelum, status_sesudah, alasan)
                           VALUES (:target, :actor, 1, 1, :alasan)")
                ->execute([
                    'target' => $customerId,
                    'actor'  => $superAdminId,
                    'alasan' => "Pengangkatan peran: Pelanggan ({$cust['nama_depan']} {$cust['nama_belakang']}) resmi diangkat menjadi Admin",
                ]);

            $_SESSION['success'] = "Pelanggan berhasil diangkat menjadi Admin.";

        } elseif ($action === 'demote') {
            $adminId = (int)($_POST['admin_id'] ?? 0);

            $stmt = $pdo->prepare("SELECT id, nama_depan, nama_belakang, role FROM users WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $adminId]);
            $adm = $stmt->fetch();
            if (!$adm)                    throw new RuntimeException("Pengguna tidak ditemukan.");
            if ($adm['role'] !== 'admin') throw new RuntimeException("Hanya pengguna dengan peran Admin yang dapat diturunkan perannya.");

            $pdo->prepare("UPDATE users SET role = 'pelanggan' WHERE id = :id AND role = 'admin'")
                ->execute(['id' => $adminId]);

            $pdo->prepare("INSERT INTO user_status_logs (target_user_id, changed_by, status_sebelum, status_sesudah, alasan)
                           VALUES (:target, :actor, 1, 1, :alasan)")
                ->execute([
                    'target' => $adminId,
                    'actor'  => $superAdminId,
                    'alasan' => "Penurunan peran: Admin ({$adm['nama_depan']} {$adm['nama_belakang']}) diturunkan kembali menjadi Pelanggan biasa",
                ]);

            $_SESSION['success'] = "Admin berhasil diturunkan perannya menjadi Pelanggan biasa.";

        } else {
            throw new RuntimeException("Aksi tidak dikenali.");
        }

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        unset($_SESSION['success']);
        $_SESSION['error'] = "Gagal memproses perubahan peran. Silakan coba lagi.";
    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        unset($_SESSION['success']);
        $_SESSION['error'] = htmlspecialchars($e->getMessage());
    }

    header('Location: admins.php');
    exit;
}

// ---------- AMBIL DATA (GET) ----------
$admins = $pdo->query("
    SELECT u.id, u.nama_depan, u.nama_belakang, u.no_hp, u.email, u.status_aktif, u.saldo,
           CONCAT(c.nama_depan, ' ', c.nama_belakang) AS diangkat_oleh
    FROM users u
    LEFT JOIN users c ON u.created_by = c.id
    WHERE u.role = 'admin'
    ORDER BY u.created_at DESC, u.id DESC
")->fetchAll();

$candidates = $pdo->query("
    SELECT id, nama_depan, nama_belakang, email
    FROM users
    WHERE role = 'pelanggan' AND status_aktif = 1
    ORDER BY nama_depan, nama_belakang
")->fetchAll();

require __DIR__ . '/_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-shield-lock text-primary me-2"></i>Pengelolaan Admin</h4>
        <p class="text-muted small mb-0">Kelola akun Admin operasional dan angkat pelanggan berprestasi menjadi Admin</p>
    </div>
    <a href="admin_activities.php" class="btn btn-outline-warning btn-sm">
        <i class="bi bi-activity me-1"></i> Pantau Aktivitas Admin
    </a>
</div>

<div class="row g-4">
    <!-- Daftar Admin -->
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
                                            <button type="button"
                                                    class="btn <?= $adm['status_aktif'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalToggleStatusAdmin"
                                                    data-id="<?= $adm['id'] ?>"
                                                    data-name="<?= htmlspecialchars($adm['nama_depan'] . ' ' . $adm['nama_belakang']) ?>"
                                                    data-status="<?= $adm['status_aktif'] ? 'aktif' : 'nonaktif' ?>">
                                                <i class="bi <?= $adm['status_aktif'] ? 'bi-lock' : 'bi-unlock' ?>"></i>
                                                <?= $adm['status_aktif'] ? 'Bekukan' : 'Aktifkan' ?>
                                            </button>

                                            <form action="admins.php" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Apakah Anda yakin ingin menurunkan peran Admin ini menjadi Pelanggan biasa?')">
                                                <input type="hidden" name="action" value="demote">
                                                <input type="hidden" name="admin_id" value="<?= $adm['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger rounded-0 rounded-end" title="Turunkan ke Pelanggan">
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

    <!-- Form Angkat Admin -->
    <div class="col-lg-4">
        <div class="card p-4 shadow-sm border-top border-primary border-4">
            <h5 class="fw-bold mb-2"><i class="bi bi-person-fill-up text-primary me-2"></i>Angkat Jadi Admin</h5>
            <p class="text-muted small mb-3">Pilih pelanggan aktif untuk diberikan hak akses dan wewenang sebagai Admin.</p>

            <form action="admins.php" method="POST">
                <input type="hidden" name="action" value="promote">
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

<!-- MODAL ALASAN PERUBAHAN STATUS ADMIN -->
<div class="modal fade" id="modalToggleStatusAdmin" tabindex="-1" aria-labelledby="modalAdminStatusLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="customer_toggle.php" method="POST">
                <input type="hidden" name="from" value="admins">
                <input type="hidden" name="id" id="modalAdminTargetId" value="">
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
                        <div class="form-text">Alasan ini akan disimpan di tabel <code>user_status_logs</code>.</div>
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
    const modal = document.getElementById('modalToggleStatusAdmin');
    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const isBekukan = (button.getAttribute('data-status') === 'aktif');

        document.getElementById('modalAdminTargetId').value = button.getAttribute('data-id');
        document.getElementById('modalAdminTargetName').textContent = button.getAttribute('data-name');
        document.getElementById('modalAdminStatusLabel').textContent = isBekukan ? 'Bekukan / Nonaktifkan Akun Admin' : 'Aktifkan Kembali Akun Admin';
        document.getElementById('modalAdminAlasan').placeholder = 'Contoh: ' + (isBekukan
            ? 'Penyalahgunaan wewenang / evaluasi internal'
            : 'Pemulihan akses operasional Admin');

        const btn = document.getElementById('btnAdminConfirmStatus');
        btn.className = 'btn fw-semibold ' + (isBekukan ? 'btn-warning' : 'btn-success');
        btn.textContent = isBekukan ? 'Konfirmasi Pembekuan Admin' : 'Konfirmasi Pengaktifan Admin';
    });
});
</script>

<?php require __DIR__ . '/_footer.php'; ?>
