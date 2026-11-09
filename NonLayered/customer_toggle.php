<?php
// ==========================================================
// NON-LAYERED: customer_toggle.php
// Aktifkan / bekukan akun (pelanggan atau admin) + catat audit log.
// Dipanggil dari customers.php dan admins.php (POST).
// ==========================================================
require_once __DIR__ . '/koneksi.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$actor    = $_SESSION['user'];
$redirect = (($_POST['from'] ?? '') === 'admins') ? 'admins.php' : 'customers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}
if ($actor['role'] !== 'admin' && $actor['role'] !== 'super_admin') {
    $_SESSION['error'] = "Akses ditolak.";
    header('Location: dashboard.php');
    exit;
}

$targetId = (int)($_POST['id'] ?? 0);
$alasan   = trim($_POST['alasan'] ?? '');
if ($alasan === '') {
    $alasan = 'Aksi perubahan status oleh ' . $actor['nama_depan'];
}
$alasan = mb_substr($alasan, 0, 1000);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, nama_depan, nama_belakang, role, status_aktif FROM users WHERE id = :id FOR UPDATE");
    $stmt->execute(['id' => $targetId]);
    $target = $stmt->fetch();

    if (!$target) {
        throw new RuntimeException("Pengguna tidak ditemukan.");
    }
    if ($targetId === (int)$actor['id']) {
        throw new RuntimeException("Anda tidak dapat mengubah status akun Anda sendiri.");
    }
    if ($actor['role'] === 'admin' && $target['role'] !== 'pelanggan') {
        throw new RuntimeException("Akses ditolak: Admin hanya berwenang mengubah status akun pelanggan.");
    }
    if ($target['role'] === 'super_admin') {
        throw new RuntimeException("Akun Super Admin tidak dapat dinonaktifkan.");
    }

    $statusSebelum = (int)$target['status_aktif'];
    $statusSesudah = $statusSebelum ? 0 : 1;

    $pdo->prepare("UPDATE users SET status_aktif = :s WHERE id = :id")
        ->execute(['s' => $statusSesudah, 'id' => $targetId]);

    $pdo->prepare("INSERT INTO user_status_logs (target_user_id, changed_by, status_sebelum, status_sesudah, alasan)
                   VALUES (:target, :actor, :sebelum, :sesudah, :alasan)")
        ->execute(['target' => $targetId, 'actor' => $actor['id'], 'sebelum' => $statusSebelum, 'sesudah' => $statusSesudah, 'alasan' => $alasan]);

    $pdo->commit();
    $_SESSION['success'] = "Status akun berhasil " . ($statusSesudah ? "diaktifkan" : "dinonaktifkan/dibekukan") . ".";
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error'] = "Gagal mengubah status akun. Silakan coba lagi.";
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['error'] = htmlspecialchars($e->getMessage());
}

header('Location: ' . $redirect);
exit;
