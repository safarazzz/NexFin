<?php
// ==========================================================
// NON-LAYERED: customer_form.php
// CREATE (tanpa ?id) dan UPDATE (dengan ?id=...) pelanggan.
// Validasi, hashing password, dan query SQL ditulis langsung di sini.
// Khusus Super Admin.
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
if ($_SESSION['user']['role'] !== 'super_admin') {
    $_SESSION['error'] = "Akses ditolak: Hanya Super Admin yang dapat menambah / mengedit pelanggan.";
    header('Location: customers.php');
    exit;
}

$id     = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;
$self   = $isEdit ? "customer_form.php?id=$id" : "customer_form.php";

// Mode edit: ambil data pelanggan yang akan diubah
$customer = [];
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'pelanggan'");
    $stmt->execute(['id' => $id]);
    $customer = $stmt->fetch();
    if (!$customer) {
        $_SESSION['error'] = "Data pelanggan tidak ditemukan.";
        header('Location: customers.php');
        exit;
    }
}

// ---------- PROSES SIMPAN (validasi + SQL langsung) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaDepan    = trim($_POST['nama_depan'] ?? '');
    $namaBelakang = trim($_POST['nama_belakang'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $noHp         = trim($_POST['no_hp'] ?? '');
    $password     = $_POST['password'] ?? '';
    $saldoInput   = trim((string)($_POST['saldo'] ?? '0'));
    $statusAktif  = (($_POST['status_aktif'] ?? '1') === '0') ? 0 : 1;

    $errors = [];

    // 1. Nama
    if ($namaDepan === '')    $errors[] = "Nama depan wajib diisi.";
    if ($namaBelakang === '') $errors[] = "Nama belakang wajib diisi.";
    if (mb_strlen($namaDepan) > 50 || mb_strlen($namaBelakang) > 50) $errors[] = "Nama maksimal 50 karakter.";

    // 2. Email: format + unik
    if ($email === '') {
        $errors[] = "Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $errors[] = "Format email tidak valid (contoh: user@domain.com).";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ($stmt->fetch()) $errors[] = "Email sudah digunakan oleh akun lain.";
    }

    // 3. Nomor HP: format + unik
    if ($noHp === '') {
        $errors[] = "Nomor HP wajib diisi.";
    } elseif (!preg_match('/^(\+62|62|0)8[0-9]{8,11}$/', $noHp)) {
        $errors[] = "Format nomor HP tidak valid (harus format Indonesia diawali 08/628, 10-13 digit).";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = :hp AND id != :id LIMIT 1");
        $stmt->execute(['hp' => $noHp, 'id' => $id]);
        if ($stmt->fetch()) $errors[] = "Nomor HP sudah terdaftar pada akun lain.";
    }

    // 4. Password: wajib saat tambah, opsional saat edit
    if (!$isEdit) {
        if ($password === '')           $errors[] = "Password wajib diisi untuk akun baru.";
        elseif (strlen($password) < 6)  $errors[] = "Password minimal terdiri dari 6 karakter.";
    } elseif ($password !== '' && strlen($password) < 6) {
        $errors[] = "Password baru minimal terdiri dari 6 karakter.";
    }

    // 5. Saldo
    if (!is_numeric($saldoInput) || (float)$saldoInput < 0) {
        $errors[] = "Saldo harus berupa angka dan tidak boleh negatif.";
    }

    if (!empty($errors)) {
        $_SESSION['error']     = implode("<br>", array_map('htmlspecialchars', $errors));
        $_SESSION['old_input'] = $_POST;
        header("Location: $self");
        exit;
    }

    $saldo = (float)$saldoInput;

    try {
        if ($isEdit) {
            $sql    = "UPDATE users SET nama_depan = :nd, nama_belakang = :nb, no_hp = :hp, email = :email,
                              saldo = :saldo, status_aktif = :status";
            $params = ['nd' => $namaDepan, 'nb' => $namaBelakang, 'hp' => $noHp, 'email' => $email,
                       'saldo' => $saldo, 'status' => $statusAktif, 'id' => $id];
            if ($password !== '') {
                $sql .= ", password = :pw";
                $params['pw'] = password_hash($password, PASSWORD_BCRYPT);
            }
            $sql .= " WHERE id = :id AND role = 'pelanggan'";
            $pdo->prepare($sql)->execute($params);
            $_SESSION['success'] = "Data pelanggan berhasil diperbarui.";
        } else {
            $pdo->prepare("INSERT INTO users (nama_depan, nama_belakang, no_hp, email, password, role, status_aktif, saldo, created_by)
                           VALUES (:nd, :nb, :hp, :email, :pw, 'pelanggan', :status, :saldo, :creator)")
                ->execute([
                    'nd' => $namaDepan, 'nb' => $namaBelakang, 'hp' => $noHp, 'email' => $email,
                    'pw' => password_hash($password, PASSWORD_BCRYPT),
                    'status' => $statusAktif, 'saldo' => $saldo, 'creator' => $_SESSION['user']['id'],
                ]);
            $_SESSION['success'] = "Pelanggan baru berhasil ditambahkan.";
        }
        header('Location: customers.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error']     = "Gagal menyimpan data pelanggan. Pastikan email / nomor HP belum dipakai dan nilai saldo wajar.";
        $_SESSION['old_input'] = $_POST;
        header("Location: $self");
        exit;
    }
}

// ---------- TAMPILAN ----------
// Nilai form: data lama dari DB (edit) ditimpa input sebelumnya jika ada error
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$val = [
    'nama_depan'    => $old['nama_depan']    ?? ($customer['nama_depan']    ?? ''),
    'nama_belakang' => $old['nama_belakang'] ?? ($customer['nama_belakang'] ?? ''),
    'email'         => $old['email']         ?? ($customer['email']         ?? ''),
    'no_hp'         => $old['no_hp']         ?? ($customer['no_hp']         ?? ''),
    'saldo'         => $old['saldo']         ?? ($customer['saldo']         ?? '0'),
    'status_aktif'  => (string)($old['status_aktif'] ?? ($customer['status_aktif'] ?? '1')),
];

require __DIR__ . '/_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0">
                        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-person-plus' ?> text-primary me-2"></i>
                        <?= $isEdit ? 'Edit Data Pelanggan' : 'Tambah Pelanggan Baru' ?>
                    </h4>
                    <p class="text-muted small mb-0">
                        <?= $isEdit ? 'Ubah profil pelanggan. Validasi diproses langsung di file ini.' : 'Input data pelanggan lengkap. Validasi &amp; hashing diproses langsung di file ini.' ?>
                    </p>
                </div>
                <a href="customers.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <form action="<?= htmlspecialchars($self) ?>" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Depan <span class="text-danger">*</span></label>
                        <input type="text" name="nama_depan" class="form-control" placeholder="Contoh: Budi" required maxlength="50"
                               value="<?= htmlspecialchars($val['nama_depan']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Belakang <span class="text-danger">*</span></label>
                        <input type="text" name="nama_belakang" class="form-control" placeholder="Contoh: Setiawan" required maxlength="50"
                               value="<?= htmlspecialchars($val['nama_belakang']) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="user@gmail.com" required maxlength="100"
                               value="<?= htmlspecialchars($val['email']) ?>">
                        <div class="form-text">Harus unik dan berformat email valid.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor Handphone <span class="text-danger">*</span></label>
                        <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 081234567890" required
                               value="<?= htmlspecialchars($val['no_hp']) ?>">
                        <div class="form-text">Format Indonesia (diawali 08/628, 10-13 digit).</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            <?= $isEdit ? 'Kata Sandi Baru (Opsional)' : 'Kata Sandi (Password)' ?>
                            <?php if (!$isEdit): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <input type="password" name="password" class="form-control"
                               placeholder="<?= $isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' ?>"
                               <?= $isEdit ? '' : 'required' ?>>
                        <div class="form-text">
                            <?= $isEdit ? 'Biarkan kosong jika tidak ingin mengubah password lama.' : 'Akan otomatis di-hash menggunakan algoritma <strong>Bcrypt</strong>.' ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold"><?= $isEdit ? 'Saldo (Rp)' : 'Saldo Awal (Rp)' ?></label>
                        <input type="number" step="1000" min="0" name="saldo" class="form-control" placeholder="0"
                               value="<?= htmlspecialchars((string)$val['saldo']) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Status Akun</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= $val['status_aktif'] === '1' ? 'selected' : '' ?>>Aktif (Dapat Login &amp; Transaksi)</option>
                        <option value="0" <?= $val['status_aktif'] === '0' ? 'selected' : '' ?>>Nonaktif / Dibekukan</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="customers.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-save me-1"></i> <?= $isEdit ? 'Perbarui Data' : 'Simpan Pelanggan' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
