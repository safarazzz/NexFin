<?php
// ==========================================================
// PRESENTATION LAYER: WalletController
// Menangani request HTTP terkait dompet digital (Wallet Hub),
// Top Up, Transfer, Penarikan Dana (Withdraw), & Riwayat
// ==========================================================

require_once __DIR__ . '/../business/WalletService.php';

class WalletController {
    private $walletService;

    public function __construct() {
        $this->walletService = new WalletService();
    }

    private function requireAuth() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?page=login");
            exit;
        }
    }

    /**
     * Halaman Utama Dompet (Wallet Hub): Top Up, Transfer, Withdraw
     */
    public function index() {
        $this->requireAuth();
        $userId = $_SESSION['user']['id'];

        // Ambil saldo terbaru real-time dari database
        try {
            $currentBalance = $this->walletService->getBalance($userId);
            $_SESSION['user']['saldo'] = $currentBalance;
        } catch (Exception $e) {
            $currentBalance = $_SESSION['user']['saldo'] ?? 0;
        }

        $cashFlow = $this->walletService->getCashFlow($userId);
        $recentHistory = $this->walletService->getHistory($userId, 5);

        require __DIR__ . '/views/wallet/index.php';
    }

    /**
     * Halaman Riwayat Transaksi Lengkap
     */
    public function history() {
        $this->requireAuth();
        $userId = $_SESSION['user']['id'];

        $transactions = $this->walletService->getHistory($userId, 100);
        $cashFlow = $this->walletService->getCashFlow($userId);

        require __DIR__ . '/views/wallet/history.php';
    }

    /**
     * Memproses Pengisian Saldo (Top Up)
     */
    public function processTopUp() {
        $this->requireAuth();
        $userId = $_SESSION['user']['id'];

        $nominal = $_POST['nominal'] ?? 0;
        $keterangan = $_POST['keterangan'] ?? 'Top Up Saldo via Virtual Account';

        try {
            $result = $this->walletService->topUp($userId, $nominal, $keterangan);
            $_SESSION['user']['saldo'] = $result['saldo_baru'];
            $_SESSION['success'] = $result['message'];
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=wallet");
        exit;
    }

    /**
     * Memproses Penarikan Saldo (Withdraw)
     */
    public function processWithdraw() {
        $this->requireAuth();
        $userId = $_SESSION['user']['id'];

        $nominal = $_POST['nominal'] ?? 0;
        $bankTujuan = $_POST['bank_tujuan'] ?? '';

        try {
            $result = $this->walletService->withdraw($userId, $nominal, $bankTujuan);
            $_SESSION['user']['saldo'] = $result['saldo_baru'];
            $_SESSION['success'] = $result['message'];
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=wallet");
        exit;
    }

    /**
     * Memproses Transfer Saldo ke Pengguna Lain
     */
    public function processTransfer() {
        $this->requireAuth();
        $pengirimId = $_SESSION['user']['id'];

        $target = $_POST['target'] ?? '';
        $nominal = $_POST['nominal'] ?? 0;
        $pesan = $_POST['pesan'] ?? '';

        try {
            $result = $this->walletService->transfer($pengirimId, $target, $nominal, $pesan);
            $_SESSION['user']['saldo'] = $result['saldo_baru'];
            $_SESSION['success'] = $result['message'];
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header("Location: index.php?page=wallet");
        exit;
    }
}

