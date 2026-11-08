<?php
// ==========================================================
// DATA ACCESS LAYER: AuditLogRepository
// Bertanggung jawab murni atas query tabel `user_status_logs`
// Mencatat dan menampilkan aktivitas supervisi yang dilakukan Admin
// ==========================================================

require_once __DIR__ . '/../../config/Database.php';

class AuditLogRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Mencatat aksi perubahan status akun ke dalam log audit
     */
    public function createLog($targetUserId, $changedBy, $statusSebelum, $statusSesudah, $alasan = '') {
        $sql = "INSERT INTO user_status_logs (target_user_id, changed_by, status_sebelum, status_sesudah, alasan, created_at)
                VALUES (:target_user_id, :changed_by, :status_sebelum, :status_sesudah, :alasan, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'target_user_id' => $targetUserId,
            'changed_by'     => $changedBy,
            'status_sebelum' => (int)$statusSebelum,
            'status_sesudah' => (int)$statusSesudah,
            'alasan'         => trim($alasan) ?: 'Aksi perubahan status oleh administrator'
        ]);
    }

    /**
     * Mengambil daftar aktivitas terbaru dari para Admin (untuk Super Admin)
     */
    public function getAdminActivities($limit = 50) {
        $sql = "SELECT 
                    l.id,
                    l.target_user_id,
                    l.changed_by,
                    l.status_sebelum,
                    l.status_sesudah,
                    l.alasan,
                    l.created_at,
                    CONCAT(u_admin.nama_depan, ' ', u_admin.nama_belakang) AS nama_admin,
                    u_admin.email AS email_admin,
                    u_admin.role AS role_admin,
                    CONCAT(u_target.nama_depan, ' ', u_target.nama_belakang) AS nama_target,
                    u_target.email AS email_target,
                    u_target.role AS role_target
                FROM user_status_logs l
                INNER JOIN users u_admin ON l.changed_by = u_admin.id
                INNER JOIN users u_target ON l.target_user_id = u_target.id
                ORDER BY l.created_at DESC
                LIMIT :lim";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

