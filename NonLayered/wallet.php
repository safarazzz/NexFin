<?php
// ==========================================================
// NON-LAYERED: wallet.php  (Transaction Script)
// Satu file menangani: validasi aturan bisnis, mutasi saldo,
// query SQL langsung, dan tampilan HTML untuk
// Top Up, Transfer, dan Tarik Tunai.
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user']['id'];

// ---------- PROSES FORM (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $nominal = (float)($_POST['nominal'] ?? 0);

    switch ($action) {

        // ===== TOP UP =====
        case 'topup':
            $keterangan = trim($_POST['keterangan'] ?? '');
            if ($keterangan === '') $keterangan = 'Top Up Saldo via Virtual Account';
            $keterangan = mb_substr($keterangan, 0, 255);

            if ($nominal < 10000) {
                $_SESSION['error'] = "Nominal Top Up minimal adalah Rp 10.000.";
                break;
            }

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT status_aktif FROM users WHERE id = :id FOR UPDATE");
                $stmt->execute(['id' => $userId]);
                $u = $stmt->fetch();
                if (!$u)                 throw new RuntimeException("Akun pengguna tidak ditemukan.");
                if (!$u['status_aktif']) throw new RuntimeException("Akun Anda sedang dinonaktifkan. Transaksi dibatalkan.");

                $pdo->prepare("UPDATE users SET saldo = saldo + :n WHERE id = :id")
                    ->execute(['n' => $nominal, 'id' => $userId]);

                $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $saldoBaru = $stmt->fetchColumn();

                $pdo->prepare("INSERT INTO transactions (pengirim_id, penerima_id, jenis, nominal, saldo_akhir, keterangan)
                               VALUES (NULL, :penerima, 'TOPUP', :nominal, :saldo, :ket)")
                    ->execute(['penerima' => $userId, 'nominal' => $nominal, 'saldo' => $saldoBaru, 'ket' => $keterangan]);

                $pdo->commit();
                $_SESSION['user']['saldo'] = (float)$saldoBaru;
                $_SESSION['success'] = "Top Up sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil!";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['error'] = "Gagal memproses Top Up. Silakan coba lagi.";
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['error'] = htmlspecialchars($e->getMessage());
            }
            break;

        // ===== TARIK TUNAI =====
        case 'withdraw':
            $bankTujuan = mb_substr(trim($_POST['bank_tujuan'] ?? ''), 0, 150);

            if ($nominal < 10000) {
                $_SESSION['error'] = "Nominal penarikan saldo minimal adalah Rp 10.000.";
                break;
            }

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT status_aktif, saldo FROM users WHERE id = :id FOR UPDATE");
                $stmt->execute(['id' => $userId]);
                $u = $stmt->fetch();
                if (!$u)                 throw new RuntimeException("Akun pengguna tidak ditemukan.");
                if (!$u['status_aktif']) throw new RuntimeException("Akun Anda sedang dinonaktifkan. Penarikan dana dibatalkan.");
                if ((float)$u['saldo'] < $nominal) {
                    throw new RuntimeException("Saldo tidak mencukupi. Saldo Anda saat ini: Rp " . number_format($u['saldo'], 0, ',', '.'));
                }

                $pdo->prepare("UPDATE users SET saldo = saldo - :n WHERE id = :id")
                    ->execute(['n' => $nominal, 'id' => $userId]);

                $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $saldoBaru = $stmt->fetchColumn();

                $keterangan = "Tarik Saldo" . ($bankTujuan !== '' ? " ke " . $bankTujuan : "");
                $pdo->prepare("INSERT INTO transactions (pengirim_id, penerima_id, jenis, nominal, saldo_akhir, keterangan)
                               VALUES (:pengirim, NULL, 'WITHDRAW', :nominal, :saldo, :ket)")
                    ->execute(['pengirim' => $userId, 'nominal' => $nominal, 'saldo' => $saldoBaru, 'ket' => $keterangan]);

                $pdo->commit();
                $_SESSION['user']['saldo'] = (float)$saldoBaru;
                $_SESSION['success'] = "Penarikan saldo sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil!";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['error'] = "Gagal memproses penarikan saldo. Silakan coba lagi.";
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['error'] = htmlspecialchars($e->getMessage());
            }
            break;

        // ===== TRANSFER =====
        case 'transfer':
            $target = trim($_POST['target'] ?? '');
            $pesan  = mb_substr(trim($_POST['pesan'] ?? ''), 0, 100);

            if ($nominal < 10000) {
                $_SESSION['error'] = "Nominal transfer minimal adalah Rp 10.000.";
                break;
            }
            if ($target === '') {
                $_SESSION['error'] = "Nomor HP atau email penerima wajib diisi.";
                break;
            }

            try {
                $pdo->beginTransaction();

                // Cari id penerima berdasarkan No HP atau Email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = :a OR email = :b LIMIT 1");
                $stmt->execute(['a' => $target, 'b' => $target]);
                $penerimaId = (int)$stmt->fetchColumn();

                // Kunci baris pengirim & penerima (urut id agar tidak deadlock)
                $lockIds = array_unique(array_filter([$userId, $penerimaId]));
                sort($lockIds);
                $rows = $pdo->query("SELECT id, nama_depan, nama_belakang, saldo, status_aktif
                                     FROM users WHERE id IN (" . implode(',', array_map('intval', $lockIds)) . ")
                                     ORDER BY id FOR UPDATE")->fetchAll();
                $byId = [];
                foreach ($rows as $r) $byId[(int)$r['id']] = $r;

                $pengirim = $byId[$userId] ?? null;
                $penerima = $penerimaId ? ($byId[$penerimaId] ?? null) : null;

                if (!$pengirim)                 throw new RuntimeException("Pengirim tidak valid.");
                if (!$pengirim['status_aktif']) throw new RuntimeException("Akun Anda sedang dinonaktifkan. Pengiriman dana dibatalkan.");
                if ((float)$pengirim['saldo'] < $nominal) {
                    throw new RuntimeException("Saldo Anda tidak mencukupi untuk melakukan transfer ini. Saldo: Rp " . number_format($pengirim['saldo'], 0, ',', '.'));
                }
                if (!$penerima) {
                    throw new RuntimeException("Penerima dengan nomor HP / email '" . htmlspecialchars($target) . "' tidak ditemukan.");
                }
                if ($penerimaId === $userId)    throw new RuntimeException("Anda tidak dapat mentransfer saldo ke akun Anda sendiri.");
                if (!$penerima['status_aktif']) throw new RuntimeException("Akun penerima sedang dinonaktifkan. Transfer tidak dapat dilakukan.");

                // 1. Kurangi saldo pengirim, 2. Tambah saldo penerima
                $pdo->prepare("UPDATE users SET saldo = saldo - :n WHERE id = :id")->execute(['n' => $nominal, 'id' => $userId]);
                $pdo->prepare("UPDATE users SET saldo = saldo + :n WHERE id = :id")->execute(['n' => $nominal, 'id' => $penerimaId]);

                $stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $saldoBaru = $stmt->fetchColumn();

                // 3. Catat riwayat transaksi
                $namaPenerima = $penerima['nama_depan'] . ' ' . $penerima['nama_belakang'];
                $keterangan   = "Transfer ke " . $namaPenerima . ($pesan !== '' ? " (" . $pesan . ")" : "");
                $pdo->prepare("INSERT INTO transactions (pengirim_id, penerima_id, jenis, nominal, saldo_akhir, keterangan)
                               VALUES (:pengirim, :penerima, 'TRANSFER', :nominal, :saldo, :ket)")
                    ->execute(['pengirim' => $userId, 'penerima' => $penerimaId, 'nominal' => $nominal, 'saldo' => $saldoBaru, 'ket' => $keterangan]);

                $pdo->commit();
                $_SESSION['user']['saldo'] = (float)$saldoBaru;
                $_SESSION['success'] = "Transfer sebesar Rp " . number_format($nominal, 0, ',', '.') . " kepada " . htmlspecialchars($namaPenerima) . " berhasil!";
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['error'] = "Gagal memproses transfer. Silakan coba lagi.";
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['error'] = $e->getMessage(); // sudah di-escape saat dibuat
            }
            break;

        default:
            $_SESSION['error'] = "Aksi tidak dikenali.";
    }

    header('Location: wallet.php');
    exit;
}

// ---------- AMBIL DATA UNTUK TAMPILAN (GET) ----------
$stmt = $pdo->prepare("SELECT saldo FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$saldoDb = $stmt->fetchColumn();
$currentBalance = ($saldoDb !== false) ? (float)$saldoDb : (float)($_SESSION['user']['saldo'] ?? 0);
$_SESSION['user']['saldo'] = $currentBalance;

// Arus kas: dana masuk (TOPUP/TRANSFER sebagai penerima) & dana keluar (WITHDRAW/TRANSFER sebagai pengirim)
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN penerima_id = :u1 AND jenis IN ('TOPUP','TRANSFER')   THEN nominal END), 0) AS total_masuk,
        COALESCE(SUM(CASE WHEN pengirim_id = :u2 AND jenis IN ('WITHDRAW','TRANSFER') THEN nominal END), 0) AS total_keluar
    FROM transactions
    WHERE pengirim_id = :u3 OR penerima_id = :u4
");
$stmt->execute(['u1' => $userId, 'u2' => $userId, 'u3' => $userId, 'u4' => $userId]);
$cashFlow = $stmt->fetch();

// 5 aktivitas terakhir
$stmt = $pdo->prepare("
    SELECT jenis, nominal, keterangan, penerima_id, created_at
    FROM transactions
    WHERE pengirim_id = :u1 OR penerima_id = :u2
    ORDER BY created_at DESC, id DESC
    LIMIT 5
");
$stmt->execute(['u1' => $userId, 'u2' => $userId]);
$recentHistory = $stmt->fetchAll();

require __DIR__ . '/_header.php';
?>

<div class="row g-4">
    <!-- Kolom Kiri: Dompet Utama & Formulir Transaksi -->
    <div class="col-lg-8">
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
                    <form action="wallet.php" method="POST">
                        <input type="hidden" name="action" value="topup">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Top Up (Rp) <span class="text-danger">*</span></label>
                            <input type="number" id="topupNominal" name="nominal" class="form-control form-control-lg" placeholder="Minimal 10.000" min="10000" step="1000" required>
                            <div class="form-text">Pilihan Cepat:</div>
                            <div class="d-flex gap-2 mt-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('topupNominal').value=50000">50.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('topupNominal').value=100000">100.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('topupNominal').value=250000">250.000</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('topupNominal').value=500000">500.000</button>
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
                    <form action="wallet.php" method="POST">
                        <input type="hidden" name="action" value="transfer">
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
                            <input type="text" name="pesan" maxlength="100" class="form-control" placeholder="Contoh: Pembayaran tagihan / Uang saku">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" onclick="return confirm('Pastikan data penerima sudah benar. Lanjutkan transfer?')">
                            <i class="bi bi-send-fill me-1"></i> Kirim Saldo Sekarang
                        </button>
                    </form>
                </div>

                <!-- TAB 3: TARIK SALDO -->
                <div class="tab-pane fade" id="withdraw" role="tabpanel">
                    <form action="wallet.php" method="POST">
                        <input type="hidden" name="action" value="withdraw">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nominal Penarikan (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="nominal" class="form-control form-control-lg" placeholder="Minimal 10.000" min="10000" step="1000" max="<?= $currentBalance ?>" required>
                            <div class="form-text">Maksimal penarikan: Rp <?= number_format($currentBalance, 2, ',', '.') ?></div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tujuan Penarikan (Bank / E-Wallet)</label>
                            <input type="text" name="bank_tujuan" maxlength="150" class="form-control" placeholder="Contoh: Bank BCA - 1234567890 an <?= htmlspecialchars($currentUser['nama_depan']) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100 py-2 fw-semibold" onclick="return confirm('Konfirmasi penarikan dana ke rekening tujuan?')">
                            <i class="bi bi-cash-coin me-1"></i> Tarik Dana ke Rekening
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Arus Kas & Aktivitas Terbaru -->
    <div class="col-lg-4">
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

        <div class="card p-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-dark small text-uppercase">Aktivitas Terakhir</h6>
                <a href="wallet_history.php" class="small text-primary text-decoration-none">Lihat Semua</a>
            </div>

            <?php if (empty($recentHistory)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-receipt fs-4 d-block mb-1"></i> Belum ada aktivitas transaksi.
                </div>
            <?php else: ?>
                <ul class="list-group list-group-flush small">
                    <?php foreach ($recentHistory as $tx): ?>
                        <?php $isIncoming = ($tx['jenis'] === 'TOPUP') || ($tx['jenis'] === 'TRANSFER' && $tx['penerima_id'] == $currentUser['id']); ?>
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

<?php require __DIR__ . '/_footer.php'; ?>
