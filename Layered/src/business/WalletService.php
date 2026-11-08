<?php
// ==========================================================
// BUSINESS LOGIC LAYER: WalletService
// Mengatur seluruh aturan bisnis transaksi keuangan:
// Top Up, Transfer, Penarikan Dana (Withdraw), dan Riwayat
// ==========================================================

require_once __DIR__ . '/../data_access/UserRepository.php';
require_once __DIR__ . '/../data_access/TransactionRepository.php';

class WalletService {
    private $userRepository;
    private $transactionRepository;
    private $db;

    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->transactionRepository = new TransactionRepository();
        $this->db = Database::getConnection();
    }

    /**
     * Mengambil saldo terbaru pengguna dari basis data
     */
    public function getBalance($userId) {
        $user = $this->userRepository->findById($userId);
        if (!$user) throw new Exception("Pengguna tidak ditemukan.");
        return (float)$user['saldo'];
    }

    /**
     * Logika Bisnis: Top Up Saldo
     */
    public function topUp($userId, $nominal, $keterangan = 'Top Up Saldo') {
        $nominal = (float)$nominal;
        if ($nominal < 10000) {
            throw new Exception("Nominal Top Up minimal adalah Rp 10.000.");
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception("Akun pengguna tidak ditemukan.");
        }
        if (!$user['status_aktif']) {
            throw new Exception("Akun Anda sedang dinonaktifkan. Transaksi dibatalkan.");
        }

        try {
            $this->db->beginTransaction();

            $this->userRepository->updateSaldo($userId, $nominal);
            $updatedUser = $this->userRepository->findById($userId);

            $this->transactionRepository->create(
                null,
                $userId,
                'TOPUP',
                $nominal,
                $updatedUser['saldo'],
                trim($keterangan) ?: 'Top Up Saldo'
            );

            $this->db->commit();
            return [
                'saldo_baru' => $updatedUser['saldo'],
                'message'    => "Top Up sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil!"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Gagal memproses Top Up: " . $e->getMessage());
        }
    }

    /**
     * Logika Bisnis: Penarikan Dana (Withdraw)
     */
    public function withdraw($userId, $nominal, $bankTujuan = '') {
        $nominal = (float)$nominal;
        if ($nominal < 10000) {
            throw new Exception("Nominal penarikan saldo minimal adalah Rp 10.000.");
        }

        $user = $this->userRepository->findById($userId);
        if (!$user) {
            throw new Exception("Akun pengguna tidak ditemukan.");
        }
        if (!$user['status_aktif']) {
            throw new Exception("Akun Anda sedang dinonaktifkan. Penarikan dana dibatalkan.");
        }

        if ((float)$user['saldo'] < $nominal) {
            throw new Exception("Saldo tidak mencukupi. Saldo Anda saat ini: Rp " . number_format($user['saldo'], 0, ',', '.'));
        }

        try {
            $this->db->beginTransaction();

            $this->userRepository->updateSaldo($userId, -$nominal);
            $updatedUser = $this->userRepository->findById($userId);

            $keterangan = "Tarik Saldo" . ($bankTujuan ? " ke " . trim($bankTujuan) : "");
            $this->transactionRepository->create(
                $userId,
                null,
                'WITHDRAW',
                $nominal,
                $updatedUser['saldo'],
                $keterangan
            );

            $this->db->commit();
            return [
                'saldo_baru' => $updatedUser['saldo'],
                'message'    => "Penarikan saldo sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil!"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Gagal memproses penarikan saldo: " . $e->getMessage());
        }
    }

    /**
     * Logika Bisnis: Transfer Saldo Antar Pengguna
     */
    public function transfer($pengirimId, $targetIdentifier, $nominal, $pesan = '') {
        $nominal = (float)$nominal;
        if ($nominal < 10000) {
            throw new Exception("Nominal transfer minimal adalah Rp 10.000.");
        }

        $targetIdentifier = trim($targetIdentifier);
        if (empty($targetIdentifier)) {
            throw new Exception("Nomor HP atau email penerima wajib diisi.");
        }

        $pengirim = $this->userRepository->findById($pengirimId);
        if (!$pengirim) {
            throw new Exception("Pengirim tidak valid.");
        }
        if (!$pengirim['status_aktif']) {
            throw new Exception("Akun Anda sedang dinonaktifkan. Pengiriman dana dibatalkan.");
        }
        if ((float)$pengirim['saldo'] < $nominal) {
            throw new Exception("Saldo Anda tidak mencukupi untuk melakukan transfer ini. Saldo: Rp " . number_format($pengirim['saldo'], 0, ',', '.'));
        }

        // Cari penerima berdasarkan No HP atau Email
        $penerima = $this->userRepository->findByPhoneOrEmail($targetIdentifier);
        if (!$penerima) {
            throw new Exception("Penerima dengan nomor HP / email '{$targetIdentifier}' tidak ditemukan.");
        }

        // Validasi: Tidak boleh transfer ke diri sendiri
        if ((int)$penerima['id'] === (int)$pengirimId) {
            throw new Exception("Anda tidak dapat mentransfer saldo ke akun Anda sendiri.");
        }

        if (!$penerima['status_aktif']) {
            throw new Exception("Akun penerima sedang dinonaktifkan. Transfer tidak dapat dilakukan.");
        }

        // Eksekusi atomik menggunakan database transaction
        try {
            $this->db->beginTransaction();

            // 1. Kurangi saldo pengirim
            $this->userRepository->updateSaldo($pengirimId, -$nominal);
            // 2. Tambah saldo penerima
            $this->userRepository->updateSaldo($penerima['id'], $nominal);

            $pengirimBaru = $this->userRepository->findById($pengirimId);

            // 3. Catat riwayat transaksi
            $namaPenerima = $penerima['nama_depan'] . ' ' . $penerima['nama_belakang'];
            $keterangan = "Transfer ke " . $namaPenerima . ($pesan ? " (" . trim($pesan) . ")" : "");

            $this->transactionRepository->create(
                $pengirimId,
                $penerima['id'],
                'TRANSFER',
                $nominal,
                $pengirimBaru['saldo'],
                $keterangan
            );

            $this->db->commit();
            return [
                'saldo_baru'   => $pengirimBaru['saldo'],
                'nama_penerima'=> $namaPenerima,
                'message'      => "Transfer sebesar Rp " . number_format($nominal, 0, ',', '.') . " kepada {$namaPenerima} berhasil!"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Gagal memproses transfer: " . $e->getMessage());
        }
    }

    /**
     * Riwayat transaksi pengguna
     */
    public function getHistory($userId, $limit = 50) {
        return $this->transactionRepository->findByUserId($userId, $limit);
    }

    /**
     * Ringkasan arus kas pengguna (Uang Masuk & Uang Keluar)
     */
    public function getCashFlow($userId) {
        return $this->transactionRepository->getUserCashFlow($userId);
    }

    /**
     * Riwayat transaksi khusus para pelanggan (untuk diinspeksi oleh Admin / Super Admin)
     * Aturan ketat: Menolak menampilkan transaksi sesama admin
     */
    public function getCustomerTransactionsForAdmin($keyword = '') {
        return $this->transactionRepository->getCustomerOnlyTransactions($keyword);
    }
}

