<?php require __DIR__ . '/../layout/header.php'; ?>

<div class="row g-4">
    <!-- Kolom Kiri: Dompet Utama & Formulir Transaksi -->
    <div class="col-lg-8">
        <!-- Kartu Saldo Modern -->
        <div class="card bg-primary text-white p-4 mb-4 shadow" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small text-uppercase fw-semibold">Saldo Rekening Digital</span>
                    <h1 class="display-6 fw-bold mb-0 mt-1">
                        Rp <?= number_format($currentBalance, 2, ',', '.') ?>
                    </h1>
                </div>
                <div class="text-end">
                    <span class="badge bg-light text-primary px-3 py-2 fs-6">
                        <i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars(strtoupper($currentUser['role'])) ?>
                    </span>
                    <div class="small text-white-50 mt-1"><?= htmlspecialchars($currentUser['no_hp']) ?></div>
                </div>
            </div>
        </div>

        <!-- Formulir Transaksi Bertab: Top Up, Transfer, Tarik Tunai -->
        <div class="card p-4 shadow-sm">
            <h5 class="fw-bold mb-3"><i class="bi bi-arrow-left-right text-primary me-2"></i>Pusat Transaksi Dana</h5>

            <ul class="nav nav-pills nav-fill mb-4 p-1 bg-light rounded" id="walletTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold" id="topup-tab" data-bs-toggle="tab" data-bs-target="#topup" type="button" role="tab">
                        <i class="bi bi-plus-circle me-1"></i> Top Up Saldo
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="transfer-tab" data-bs-toggle="tab" data-bs-target="#transfer" type="button" role="tab">
                        <i class="bi bi-send me-1"></i> Transfer Saldo
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold" id="withdraw-tab" data-bs-toggle="tab" data-bs-target="#withdraw" type="button" role="tab">
                        <i class="bi bi-cash-stack me-1"></i> Tarik Tunai
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="walletTabContent">
                <!-- TAB 1: TOP UP -->
                <div class="tab-pane fade show active" id="topup" role="tabpanel">
                    <form action="index.php?page=wallet-topup" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Top Up (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="nominal" class="form-control form-control-lg" placeholder="Minimal 10.000" min="10000" step="1000" required>
                            <div class="form-text">Pilihan Cepat:</div>
                            <div class="d-flex gap-2 mt-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementsByName('nominal')[0].value=50000">50.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementsByName('nominal')[0].value=100000">100.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementsByName('nominal')[0].value=250000">250.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementsByName('nominal')[0].value=500000">500.000</button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Metode Pembayaran</label>
                            <select name="keterangan" class="form-select">
                                <option value="Top Up via Virtual Account BCA">Virtual Account BCA</option>
                                <option value="Top Up via Virtual Account Mandiri">Virtual Account Mandiri</option>
                                <option value="Top Up via QRIS Instant">QRIS Instant</option>
                                <option value="Top Up via Minimarket / Merchant">Gerai Minimarket</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                            <i class="bi bi-check-circle me-1"></i> Konfirmasi Top Up Saldo
                        </button>
                    </form>
                </div>

                <!-- TAB 2: TRANSFER SALDO -->
                <div class="tab-pane fade" id="transfer" role="tabpanel">
                    <form action="index.php?page=wallet-transfer" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor HP atau Email Penerima <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-search"></i></span>
                                <input type="text" name="target" class="form-control" placeholder="Contoh: 081234567891 atau dewi@gmail.com" required>
                            </div>
                            <div class="form-text">Masukkan nomor handphone atau email akun NexFin tujuan.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Transfer (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="nominal" class="form-control form-control-lg" placeholder="Minimal 10.000" min="10000" step="1000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan / Pesan Transfer (Opsional)</label>
                            <input type="text" name="pesan" class="form-control" placeholder="Contoh: Pembayaran tagihan / Uang saku">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" onclick="return confirm('Pastikan data penerima sudah benar. Lanjutkan transfer?')">
                            <i class="bi bi-send-fill me-1"></i> Kirim Saldo Sekarang
                        </button>
                    </form>
                </div>

                <!-- TAB 3: TARIK SALDO (WITHDRAW) -->
                <div class="tab-pane fade" id="withdraw" role="tabpanel">
                    <form action="index.php?page=wallet-withdraw" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Penarikan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="nominal" class="form-control form-control-lg" placeholder="Minimal 10.000" min="10000" step="1000" max="<?= $currentBalance ?>" required>
                            <div class="form-text">Maksimal penarikan: Rp <?= number_format($currentBalance, 2, ',', '.') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tujuan Penarikan (Bank / E-Wallet)</label>
                            <input type="text" name="bank_tujuan" class="form-control" placeholder="Contoh: Bank BCA - 1234567890 an <?= htmlspecialchars($currentUser['nama_depan']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 py-2 fw-semibold" onclick="return confirm('Konfirmasi penarikan dana ke rekening tujuan?')">
                            <i class="bi bi-cash-coin me-1"></i> Tarik Dana ke Rekening
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Ringkasan Arus Kas & 5 Aktivitas Terbaru -->
    <div class="col-lg-4">
        <!-- Arus Kas -->
        <div class="card p-3 mb-3 shadow-sm">
            <h6 class="fw-bold mb-3 text-muted text-uppercase small">Arus Dana Akun Anda</h6>
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success-subtle p-2 text-success me-2">
                        <i class="bi bi-arrow-down-left fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Dana Masuk</div>
                        <div class="fw-bold text-success">+ Rp <?= number_format($cashFlow['total_masuk'], 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-danger-subtle p-2 text-danger me-2">
                        <i class="bi bi-arrow-up-right fs-5"></i>
                    </div>
                    <div>
                        <div class="small text-muted">Total Dana Keluar</div>
                        <div class="fw-bold text-danger">- Rp <?= number_format($cashFlow['total_keluar'], 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5 Aktivitas Terakhir -->
        <div class="card p-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-dark small text-uppercase">Aktivitas Terakhir</h6>
                <a href="index.php?page=wallet-history" class="small text-primary text-decoration-none">Lihat Semua</a>
            </div>

            <?php if (empty($recentHistory)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-receipt fs-4 d-block mb-1"></i> Belum ada aktivitas transaksi.
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($recentHistory as $tx): ?>
                        <?php 
                            $isIncoming = ($tx['jenis'] === 'TOPUP') || ($tx['jenis'] === 'TRANSFER' && $tx['penerima_id'] == $currentUser['id']);
                        ?>
                        <li class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge <?= $tx['jenis'] === 'TOPUP' ? 'bg-success' : ($tx['jenis'] === 'TRANSFER' ? 'bg-primary' : 'bg-warning text-dark') ?>">
                                    <?= $tx['jenis'] ?>
                                </span>
                                <div class="fw-semibold text-truncate mt-1" style="max-width: 170px;" title="<?= htmlspecialchars($tx['keterangan']) ?>">
                                    <?= htmlspecialchars($tx['keterangan']) ?>
                                </div>
                                <span class="text-muted" style="font-size: 0.75rem;"><?= date('d M H:i', strtotime($tx['created_at'])) ?></span>
                            </div>
                            <div class="text-end fw-bold <?= $isIncoming ? 'text-success' : 'text-danger' ?>">
                                <?= $isIncoming ? '+' : '-' ?> Rp <?= number_format($tx['nominal'], 0, ',', '.') ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>

