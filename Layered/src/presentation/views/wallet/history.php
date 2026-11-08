<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Riwayat Transaksi Keuangan</h4>
        <p class="text-muted small mb-0">Catatan lengkap mutasi saldo keluar dan masuk pada akun Anda</p>
    </div>
    <a href="index.php?page=wallet" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-wallet2 me-1"></i> Buka Dompet Saya
    </a>
</div>

<!-- Kartu Ringkasan Arus Kas -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-3 border-start border-success border-4 shadow-sm">
            <span class="text-muted small text-uppercase fw-semibold">Total Uang Masuk</span>
            <h3 class="text-success fw-bold mb-0 mt-1">+ Rp <?= number_format($cashFlow['total_masuk'], 2, ',', '.') ?></h3>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-3 border-start border-danger border-4 shadow-sm">
            <span class="text-muted small text-uppercase fw-semibold">Total Uang Keluar</span>
            <h3 class="text-danger fw-bold mb-0 mt-1">- Rp <?= number_format($cashFlow['total_keluar'], 2, ',', '.') ?></h3>
        </div>
    </div>
</div>

<!-- Tabel Riwayat Transaksi -->
<div class="card shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width: 50px;">#</th>
                    <th>Waktu</th>
                    <th>Jenis</th>
                    <th>Keterangan / Pihak Terkait</th>
                    <th class="text-end">Nominal</th>
                    <th class="text-end">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada riwayat transaksi pada akun ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $idx => $t): ?>
                        <?php 
                            $isIncoming = ($t['jenis'] === 'TOPUP') || ($t['jenis'] === 'TRANSFER' && $t['penerima_id'] == $currentUser['id']);
                        ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $idx + 1 ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($t['created_at'])) ?></div>
                                <small class="text-muted"><?= date('H:i:s', strtotime($t['created_at'])) ?> WIB</small>
                            </td>
                            <td>
                                <?php if ($t['jenis'] === 'TOPUP'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-plus-circle me-1"></i> TOP UP
                                    </span>
                                <?php elseif ($t['jenis'] === 'TRANSFER'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                        <i class="bi bi-arrow-left-right me-1"></i> TRANSFER
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-cash-stack me-1"></i> TARIK TUNAI
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($t['keterangan']) ?></div>
                                <?php if ($t['jenis'] === 'TRANSFER'): ?>
                                    <small class="text-muted">
                                        <?php if ($t['pengirim_id'] == $currentUser['id']): ?>
                                            Ke: <strong><?= htmlspecialchars($t['nama_penerima'] ?: $t['email_penerima']) ?></strong> (<?= htmlspecialchars($t['no_hp_penerima']) ?>)
                                        <?php else: ?>
                                            Dari: <strong><?= htmlspecialchars($t['nama_pengirim'] ?: $t['email_pengirim']) ?></strong> (<?= htmlspecialchars($t['no_hp_pengirim']) ?>)
                                        <?php endif; ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold <?= $isIncoming ? 'text-success' : 'text-danger' ?>">
                                <?= $isIncoming ? '+' : '-' ?> Rp <?= number_format($t['nominal'], 2, ',', '.') ?>
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

<?php require __DIR__ . '/../layout/footer.php'; ?>

