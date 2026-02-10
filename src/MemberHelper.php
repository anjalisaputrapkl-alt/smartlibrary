<?php
/**
 * MemberHelper - Handle member lookup and auto-creation
 * 
 * Menghandle koneksi antara users dan members table
 * Jika siswa login tanpa member record, otomatis buat member
 */

class MemberHelper
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get member_id dari user session
     * 
     * Logic:
     * 1. Cek apakah NISN ada di session
     * 2. Jika ada, cari member dengan NISN
     * 3. Jika belum ada, buat member baru otomatis
     * 4. Jika tidak ada NISN, gunakan user_id (fallback)
     * 
     * @param array $userSession - Data dari $_SESSION['user']
     * @return int - member_id yang valid
     */
    public function getMemberId($userSession)
    {
        $user_id = $userSession['id'] ?? null;
        $school_id = $userSession['school_id'] ?? null;
        $nisn = $userSession['nisn'] ?? null;
        $role = $userSession['role'] ?? 'student';
        $name = $userSession['name'] ?? 'Unknown';
        $email = $userSession['email'] ?? null;

        if (!$user_id || !$school_id) {
            throw new Exception('Invalid user session data');
        }

        // Jika tidak ada NISN/Identifier, gunakan user_id sebagai member_id (fallback)
        if (!$nisn) {
            return $user_id;
        }

        try {
            // Cek apakah member sudah ada
            $stmt = $this->pdo->prepare(
                'SELECT id FROM members WHERE nisn = :nisn AND school_id = :school_id LIMIT 1'
            );
            $stmt->execute([
                'nisn' => $nisn,
                'school_id' => $school_id
            ]);
            $member = $stmt->fetch();

            if ($member) {
                return $member['id'];
            }

            // Jika belum ada, buat member baru
            $insertStmt = $this->pdo->prepare(
                'INSERT INTO members (school_id, name, email, nisn, role, status, created_at)
                 VALUES (:school_id, :name, :email, :nisn, :role, "active", NOW())'
            );
            $insertStmt->execute([
                'school_id' => $school_id,
                'name' => $name,
                'email' => $email,
                'nisn' => $nisn,
                'role' => $role
            ]);

            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            // Jika auto-create gagal, fallback ke user_id
            error_log('MemberHelper: Failed to create member - ' . $e->getMessage());
            return $user_id;
        }
    }

    /**
     * Verify member exists
     * @param int $member_id
     * @param int $school_id
     * @return bool
     */
    public function memberExists($member_id, $school_id)
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM members WHERE id = :id AND school_id = :school_id LIMIT 1'
        );
        $stmt->execute([
            'id' => $member_id,
            'school_id' => $school_id
        ]);
        return (bool) $stmt->fetch();
    }
}
?>