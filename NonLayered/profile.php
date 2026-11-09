<?php
// ==========================================================
// NON-LAYERED: profile.php
// Edit profil sendiri (semua role)
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// ---------- PROSES SIMPAN (validasi + SQL langsung) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaDepan    = trim($_POST['nama_depan'] ?? '');
    $namaBelakang = trim($_POST['nama_belakang'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $noHp         = trim($_POST['no_hp'] ?? '');
    $password     = $_POST['password'] ?? '';

    $errors = [];
    if ($namaDepan === '')    $errors[] = "Nama depan tidak boleh kosong.";
    if ($namaBelakang === '') $errors[] = "Nama belakang tidak boleh kosong.";

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
        $stmt->execute(['email' => $email, 'id' => $userId]);
        if ($stmt->fetch()) $errors[] = "Email sudah digunakan oleh akun lain.";
    }

    if ($noHp === '' || !preg_match('/^(\+62|62|0)8[0-9]{8,11}$/', $noHp)) {
        $errors[] = "Nomor HP tidak valid (gunakan format Indonesia).";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE no_hp = :hp AND id != :id LIMIT 1");
        $stmt->execute(['hp' => $noHp, 'id' => $userId]);
        if ($stmt->fetch()) $errors[] = "Nomor HP sudah digunakan oleh akun lain.";
    }

    if ($password !== '' && strlen($password) < 6) {
        $errors[] = "Password baru minimal 6 karakter.";
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", array_map('htmlspecialchars', $errors));
    } else {
        $sql    = "UPDATE users SET nama_depan = :nd, nama_belakang = :nb, no_hp = :hp, email = :email";
        $params = ['nd' => $namaDepan, 'nb' => $namaBelakang, 'hp' => $noHp, 'email' => $email, 'id' => $userId];
        if ($password !== '') {
            $sql .= ", password = :pw";
            $params['pw'] = password_hash($password, PASSWORD_BCRYPT);
        }
        $sql .= " WHERE id = :id";
        $pdo->prepare($sql)->execute($params);

        $_SESSION['user']['nama_depan']    = $namaDepan;
        $_SESSION['user']['nama_belakang'] = $namaBelakang;
        $_SESSION['user']['email']         = $email;
        $_SESSION['user']['no_hp']         = $noHp;
        $_SESSION['success'] = "Profil Anda berhasil diperbarui.";
    }

    header('Location: profile.php');
    exit;
}

require __DIR__ . '/_header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-0"><i class="bi bi-person-gear text-primary me-2"></i>Edit Profil Saya</h4>
                    <p class="text-muted small mb-0">Ubah data identitas akun Anda sendiri</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <form action="profile.php" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Depan</label>
                        <input type="text" name="nama_depan" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['nama_depan']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nama Belakang</label>
                        <input type="text" name="nama_belakang" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['nama_belakang']) ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Alamat Email</label>
                        <input type="email" name="email" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['email']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor Handphone</label>
                        <input type="text" name="no_hp" class="form-control" required
                               value="<?= htmlspecialchars($currentUser['no_hp']) ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Ganti Kata Sandi Baru (Opsional)</label>
                    <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengganti password">
                    <div class="form-text">Jika diisi, minimal 6 karakter dan akan di-hash ulang dengan Bcrypt.</div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="bi bi-check-lg me-1"></i> Simpan Perubahan Profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
