<?php
// ==========================================================
// NON-LAYERED: customer_transactions.php
// Pengawasan transaksi PELANGGAN oleh Admin / Super Admin.
// Aturan: transaksi yang melibatkan Admin / Super Admin disembunyikan.
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$role = $_SESSION['user']['role'];
if ($role !== 'admin' && $role !== 'super_admin') {
    $_SESSION['error'] = "Akses ditolak.";
    header('Location: dashboard.php');
    exit;
}

$keyword = trim($_GET['keyword'] ?? '');

$sql = "SELECT t.id, t.pengirim_id, t.penerima_id, t.jenis, t.nominal, t.saldo_akhir, t.keterangan, t.created_at,
               CONCAT(up.nama_depan, ' ', up.nama_belakang) AS nama_pengirim, up.no_hp AS no_hp_pengirim,
               CONCAT(ur.nama_depan, ' ', ur.nama_belakang) AS nama_penerima, ur.no_hp AS no_hp_penerima
        FROM transactions t
        LEFT JOIN users up ON t.pengirim_id = up.id
        LEFT JOIN users ur ON t.penerima_id = ur.id
        WHERE (
              (t.pengirim_id IS NULL AND ur.role = 'pelanggan')        -- Top Up pelanggan
           OR (t.penerima_id IS NULL AND up.role = 'pelanggan')        -- Tarik tunai pelanggan
           OR (up.role = 'pelanggan' AND ur.role = 'pelanggan')        -- Transfer antar pelanggan
        )";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (up.nama_depan LIKE :kw1 OR ur.nama_depan LIKE :kw2
                   OR up.no_hp LIKE :kw3 OR ur.no_hp LIKE :kw4
                   OR t.keterangan LIKE :kw5)";
    $kw = '%' . $keyword . '%';
    $params = ['kw1' => $kw, 'kw2' => $kw, 'kw3' => $kw, 'kw4' => $kw, 'kw5' => $kw];
}
$sql .= " ORDER BY t.created_at DESC, t.id DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require __DIR__ . '/_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Riwayat Transaksi Para Pelanggan</h4>
        <p class="text-muted small mb-0">Pengawasan operasional: Memantau mutasi dana keluar dan masuk khusus akun pelanggan</p>
    </div>
    <span class="badge bg-secondary px-3 py-2">
        <i class="bi bi-shield-lock me-1"></i> Mode Pengawasan Admin
    </span>
</div>

<div class="alert alert-light border small text-muted mb-4 d-flex align-items-center">
    <i class="bi bi-info-circle-fill text-primary fs-5 me-2"></i>
    <div>
        <strong>Privasi &amp; Keamanan Data Karyawan:</strong> Sesuai kebijakan sistem, Admin hanya dapat mengawasi riwayat transaksi milik <strong>Pelanggan</strong>. Transaksi pribadi dari sesama Admin dan Super Admin disembunyikan secara otomatis.
    </div>
</div>

<div class="card p-3 mb-4 shadow-sm">
    <form action="customer_transactions.php" method="GET" class="row g-2 align-items-center">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="keyword" class="form-control"
                       placeholder="Cari berdasarkan nama pelanggan, nomor HP, atau keterangan transaksi..."
                       value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-filter"></i> Cari Transaksi
            </button>
            <?php if ($keyword !== ''): ?>
                <a href="customer_transactions.php" class="btn btn-outline-danger" title="Reset">
                    <i class="bi bi-x-circle"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>Waktu Transaksi</th>
                    <th>Jenis</th>
                    <th>Keterangan</th>
                    <th>Pengirim / Penerima</th>
                    <th class="text-end">Nominal Mutasi</th>
                    <th class="text-end">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Tidak ada transaksi pelanggan yang ditemukan sesuai kriteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $idx => $t): ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($t['created_at'])) ?></div>
                                <small class="text-muted"><?= date('H:i:s', strtotime($t['created_at'])) ?> WIB</small>
                            </td>
                            <td>
                                <?php if ($t['jenis'] === 'TOPUP'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">TOP UP</span>
                                <?php elseif ($t['jenis'] === 'TRANSFER'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">TRANSFER</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">TARIK TUNAI</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($t['keterangan']) ?></div>
                            </td>
                            <td>
                                <?php if ($t['jenis'] === 'TRANSFER'): ?>
                                    <div class="small">
                                        Dari: <strong><?= htmlspecialchars($t['nama_pengirim'] ?? '') ?></strong> (<?= htmlspecialchars($t['no_hp_pengirim'] ?? '') ?>)<br>
                                        Ke: <strong><?= htmlspecialchars($t['nama_penerima'] ?? '') ?></strong> (<?= htmlspecialchars($t['no_hp_penerima'] ?? '') ?>)
                                    </div>
                                <?php elseif ($t['jenis'] === 'TOPUP'): ?>
                                    <div class="small text-muted">
                                        Penerima: <strong><?= htmlspecialchars($t['nama_penerima'] ?? '') ?></strong> (<?= htmlspecialchars($t['no_hp_penerima'] ?? '') ?>)
                                    </div>
                                <?php else: ?>
                                    <div class="small text-muted">
                                        Pemilik: <strong><?= htmlspecialchars($t['nama_pengirim'] ?? '') ?></strong> (<?= htmlspecialchars($t['no_hp_pengirim'] ?? '') ?>)
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                Rp <?= number_format($t['nominal'], 2, ',', '.') ?>
                            </td>
                            <td class="text-end text-muted small">
                                Rp <?= number_format($t['saldo_akhir'], 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
