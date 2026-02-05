<?php
require_once __DIR__ . '/EmailHelper.php';
/**
 * NotificationsHelper - Helper class untuk manajemen notifikasi
 * 
 * Menyediakan fungsi reusable untuk:
 * - Create notifikasi
 * - Fetch notifikasi
 * - Mark as read
 * - Delete notifikasi
 * - Broadcast notifikasi ke multiple users
 */

class NotificationsHelper {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Buat notifikasi tunggal
     * 
     * @param int $schoolId ID sekolah
     * @param int $studentId ID siswa
     * @param string $type Tipe notifikasi (borrow, return_request, return_confirm, late_warning, info, new_book)
     * @param string $title Judul notifikasi
     * @param string $message Isi pesan notifikasi
     * @return bool Success status
     */
    public function createNotification($schoolId, $studentId, $type, $title, $message) {
        try {
            // 1. Simpan ke database
            $stmt = $this->pdo->prepare(
                'INSERT INTO notifications (school_id, student_id, type, title, message, is_read, created_at)
                 VALUES (:school_id, :student_id, :type, :title, :message, 0, NOW())'
            );

            $saved = $stmt->execute([
                ':school_id' => $schoolId,
                ':student_id' => $studentId,
                ':type' => $type,
                ':title' => $title,
                ':message' => $message
            ]);

            if ($saved) {
                // 2. Kirim Notifikasi ke Email (Simple)
                $this->sendEmailToStudent($studentId, $title, $message);
            }

            return $saved;
        } catch (Exception $e) {
            error_log('Error creating notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper internal untuk kirim email ke siswa berdasarkan member_id
     */
    private function sendEmailToStudent($studentId, $title, $message) {
        try {
            // Ambil email siswa dari tabel members
            $stmt = $this->pdo->prepare('SELECT email FROM members WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student && !empty($student['email'])) {
                $subject = "Notifikasi Baru: " . $title;
                // Panggil fungsi dari EmailHelper.php
                return sendNotificationEmail($student['email'], $subject, $title, $message);
            }
        } catch (Exception $e) {
            error_log('Error sending email notification: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * Broadcast notifikasi ke multiple siswa
     * 
     * @param int $schoolId ID sekolah
     * @param array $studentIds Array ID siswa
     * @param string $type Tipe notifikasi
     * @param string $title Judul notifikasi
     * @param string $message Isi pesan notifikasi
     * @return int Jumlah notifikasi yang berhasil dibuat
     */
    public function broadcastNotification($schoolId, $studentIds, $type, $title, $message) {
        $count = 0;
        
        try {
            $this->pdo->beginTransaction();
            
            foreach ($studentIds as $studentId) {
                if ($this->createNotification($schoolId, $studentId, $type, $title, $message)) {
                    $count++;
                }
            }
            
            $this->pdo->commit();
            return $count;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Error broadcasting notification: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get notifikasi berdasarkan berbagai filter
     * 
     * @param int $schoolId ID sekolah
     * @param int $studentId ID siswa
     * @param string $type Optional: filter by type
     * @param int $limit Default 10
     * @param int $offset Default 0
     * @return array Array notifikasi
     */
    public function getNotifications($schoolId, $studentId, $type = null, $limit = 10, $offset = 0) {
        try {
            $query = 'SELECT * FROM notifications 
                     WHERE school_id = :school_id AND student_id = :student_id';
            $params = [
                ':school_id' => $schoolId,
                ':student_id' => $studentId
            ];

            if ($type) {
                $query .= ' AND type = :type';
                $params[':type'] = $type;
            }

            $query .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching notifications: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get statistik notifikasi
     * 
     * @param int $schoolId ID sekolah
     * @param int $studentId ID siswa
     * @return array Array dengan key: total, unread, borrow, return_request, return_confirm, late_warning, info, new_book
     */
    public function getStatistics($schoolId, $studentId) {
        try {
            // Total & Unread
            $stmt = $this->pdo->prepare(
                'SELECT 
                    COUNT(*) as total,
                    SUM(is_read = 0) as unread
                 FROM notifications
                 WHERE school_id = :school_id AND student_id = :student_id'
            );
            $stmt->execute([':school_id' => $schoolId, ':student_id' => $studentId]);
            $general = $stmt->fetch(PDO::FETCH_ASSOC);

            // By type
            $stmt = $this->pdo->prepare(
                'SELECT type, COUNT(*) as count
                 FROM notifications
                 WHERE school_id = :school_id AND student_id = :student_id
                 GROUP BY type'
            );
            $stmt->execute([':school_id' => $schoolId, ':student_id' => $studentId]);
            $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = [
                'total' => $general['total'] ?? 0,
                'unread' => $general['unread'] ?? 0,
                'borrow' => 0,
                'return_request' => 0,
                'return_confirm' => 0,
                'late_warning' => 0,
                'info' => 0,
                'new_book' => 0
            ];

            foreach ($byType as $type) {
                $stats[$type['type']] = $type['count'];
            }

            return $stats;
        } catch (Exception $e) {
            error_log('Error getting statistics: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Mark notifikasi sebagai dibaca
     * 
     * @param int $schoolId ID sekolah
     * @param int $notificationId ID notifikasi
     * @param int $studentId ID siswa (untuk security check)
     * @return bool Success status
     */
    public function markAsRead($schoolId, $notificationId, $studentId) {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE notifications 
                 SET is_read = 1
                 WHERE id = :id AND school_id = :school_id AND student_id = :student_id'
            );

            return $stmt->execute([
                ':id' => $notificationId,
                ':school_id' => $schoolId,
                ':student_id' => $studentId
            ]);
        } catch (Exception $e) {
            error_log('Error marking notification as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark semua notifikasi sebagai dibaca
     * 
     * @param int $schoolId ID sekolah
     * @param int $studentId ID siswa
     * @return bool Success status
     */
    public function markAllAsRead($schoolId, $studentId) {
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE notifications 
                 SET is_read = 1
                 WHERE school_id = :school_id AND student_id = :student_id AND is_read = 0'
            );

            return $stmt->execute([
                ':school_id' => $schoolId,
                ':student_id' => $studentId
            ]);
        } catch (Exception $e) {
            error_log('Error marking all notifications as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete notifikasi lama (lebih dari 30 hari)
     * Jalankan via cron atau manual cleanup
     * 
     * @param int $days Jumlah hari threshold
     * @return int Jumlah notifikasi yang dihapus
     */
    public function deleteOldNotifications($days = 30) {
        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM notifications 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)'
            );
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            error_log('Error deleting old notifications: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Check dan generate late_warning notifikasi untuk siswa tertentu
     * 
     * @param int $schoolId ID sekolah
     * @param int $studentId ID siswa
     * @return int Jumlah notifikasi warning yang dibuat
     */
    public function checkAndCreateLateWarnings($schoolId, $studentId = null) {
        try {
            $this->pdo->beginTransaction();
            $count = 0;

            // Query untuk cari buku yang terlambat
            if ($studentId) {
                $query = 'SELECT b.id, b.member_id, b.due_at, bk.title
                         FROM borrows b
                         JOIN books bk ON b.book_id = bk.id
                         WHERE b.school_id = :school_id
                         AND b.member_id = :student_id
                         AND b.status IN ("borrowed", "overdue")
                         AND b.due_at < NOW()
                         AND b.returned_at IS NULL';
                $params = [':school_id' => $schoolId, ':student_id' => $studentId];
            } else {
                $query = 'SELECT DISTINCT b.member_id, b.due_at, bk.title
                         FROM borrows b
                         JOIN books bk ON b.book_id = bk.id
                         WHERE b.school_id = :school_id
                         AND b.status IN ("borrowed", "overdue")
                         AND b.due_at < NOW()
                         AND b.returned_at IS NULL';
                $params = [':school_id' => $schoolId];
            }

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            $overdueRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Check jika sudah ada late_warning, jangan buat duplikat
            foreach ($overdueRecords as $record) {
                $checkStmt = $this->pdo->prepare(
                    'SELECT COUNT(*) as count FROM notifications
                     WHERE school_id = :school_id
                     AND student_id = :student_id
                     AND type = "late_warning"
                     AND DATE(created_at) = CURDATE()'
                );
                $checkStmt->execute([
                    ':school_id' => $schoolId,
                    ':student_id' => $record['member_id']
                ]);
                $check = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($check['count'] == 0) {
                    $message = 'Anda terlambat mengembalikan buku "' . htmlspecialchars($record['title']) . '". Segera ajukan pengembalian.';
                    if ($this->createNotification($schoolId, $record['member_id'], 'late_warning', 'Peringatan Keterlambatan', $message)) {
                        $count++;
                    }
                }
            }

            $this->pdo->commit();
            return $count;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Error checking late warnings: ' . $e->getMessage());
            return 0;
        }
    }
}
