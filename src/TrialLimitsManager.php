<?php

/**
 * TrialLimitsManager.php
 * 
 * Enforcement dari hard limits untuk trial schools.
 * Prevent sekolah trial exceed kuota buku, siswa, transaksi.
 */

class TrialLimitsManager
{
    private $pdo;
    private $multiTenantManager;

    // Hard limits untuk trial
    const TRIAL_MAX_BOOKS = 50;
    const TRIAL_MAX_STUDENTS = 100;
    const TRIAL_MAX_BORROWS_MONTHLY = 200;

    public function __construct($pdo, $multiTenantManager)
    {
        $this->pdo = $pdo;
        $this->multiTenantManager = $multiTenantManager;
    }

    /**
     * Check sebelum menambah buku
     * Throw exception jika melebihi limit
     */
    public function checkBeforeAddBook($school_id)
    {
        if (!$this->multiTenantManager->isSchoolTrial($school_id)) {
            return true; // Active school, no limit
        }

        $count = $this->getBookCount($school_id);
        if ($count >= self::TRIAL_MAX_BOOKS) {
            throw new TrialLimitException(
                "Sekolah trial terbatas maksimal " . self::TRIAL_MAX_BOOKS . " buku. "
                . "Sudah ada " . $count . " buku. Upgrade ke active untuk menambah lebih banyak."
            );
        }

        return true;
    }

    /**
     * Check sebelum menambah siswa
     */
    public function checkBeforeAddStudent($school_id)
    {
        if (!$this->multiTenantManager->isSchoolTrial($school_id)) {
            return true;
        }

        $count = $this->getStudentCount($school_id);
        if ($count >= self::TRIAL_MAX_STUDENTS) {
            throw new TrialLimitException(
                "Sekolah trial terbatas maksimal " . self::TRIAL_MAX_STUDENTS . " siswa. "
                . "Sudah ada " . $count . " siswa."
            );
        }

        return true;
    }

    /**
     * Check sebelum membuat peminjaman
     */
    public function checkBeforeBorrow($school_id)
    {
        if (!$this->multiTenantManager->isSchoolTrial($school_id)) {
            return true;
        }

        $monthCount = $this->getBorrowCountThisMonth($school_id);
        if ($monthCount >= self::TRIAL_MAX_BORROWS_MONTHLY) {
            throw new TrialLimitException(
                "Sekolah trial terbatas " . self::TRIAL_MAX_BORROWS_MONTHLY . " peminjaman per bulan. "
                . "Sudah mencapai limit bulan ini (" . $monthCount . ")."
            );
        }

        return true;
    }

    /**
     * Check sebelum export/print laporan
     */
    public function checkBeforeExportReport($school_id)
    {
        if (!$this->multiTenantManager->isSchoolTrial($school_id)) {
            return true;
        }

        throw new TrialFeatureException(
            "Laporan resmi (export/print) hanya tersedia untuk sekolah dengan status active. "
            . "Harap upgrade ke active terlebih dahulu."
        );
    }

    /**
     * Get current book count
     */
    public function getBookCount($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count FROM books WHERE school_id = :school_id
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Get book count with capacity info
     */
    public function getBookCountWithCapacity($school_id)
    {
        $count = $this->getBookCount($school_id);
        $max = $this->multiTenantManager->isSchoolTrial($school_id) ? self::TRIAL_MAX_BOOKS : 999999;
        $percentage = floor(($count / $max) * 100);

        return [
            'current' => $count,
            'max' => $max,
            'percentage' => $percentage,
            'is_trial' => $this->multiTenantManager->isSchoolTrial($school_id)
        ];
    }

    /**
     * Get current student count
     */
    public function getStudentCount($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count FROM members WHERE school_id = :school_id AND status = "active"
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Get student count with capacity info
     */
    public function getStudentCountWithCapacity($school_id)
    {
        $count = $this->getStudentCount($school_id);
        $max = $this->multiTenantManager->isSchoolTrial($school_id) ? self::TRIAL_MAX_STUDENTS : 999999;
        $percentage = floor(($count / $max) * 100);

        return [
            'current' => $count,
            'max' => $max,
            'percentage' => $percentage,
            'is_trial' => $this->multiTenantManager->isSchoolTrial($school_id)
        ];
    }

    /**
     * Get borrow count this month
     */
    public function getBorrowCountThisMonth($school_id)
    {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as count FROM borrows 
            WHERE school_id = :school_id 
            AND YEAR(borrowed_at) = YEAR(CURDATE())
            AND MONTH(borrowed_at) = MONTH(CURDATE())
        ');
        $stmt->execute(['school_id' => $school_id]);
        $result = $stmt->fetch();
        return $result['count'];
    }

    /**
     * Get borrow count with capacity info
     */
    public function getBorrowCountWithCapacity($school_id)
    {
        $count = $this->getBorrowCountThisMonth($school_id);
        $max = $this->multiTenantManager->isSchoolTrial($school_id) ? self::TRIAL_MAX_BORROWS_MONTHLY : 999999;
        $percentage = floor(($count / $max) * 100);

        return [
            'current' => $count,
            'max' => $max,
            'percentage' => $percentage,
            'is_trial' => $this->multiTenantManager->isSchoolTrial($school_id),
            'period' => date('Y-m')
        ];
    }

    /**
     * Get all limits untuk school
     */
    public function getAllLimits($school_id)
    {
        return [
            'books' => $this->getBookCountWithCapacity($school_id),
            'students' => $this->getStudentCountWithCapacity($school_id),
            'borrows' => $this->getBorrowCountWithCapacity($school_id),
            'trial_days_remaining' => $this->multiTenantManager->isSchoolTrial($school_id)
                ? $this->multiTenantManager->getTrialDaysRemaining($school_id)
                : null
        ];
    }

    /**
     * Get warning message if approaching limits
     */
    public function getWarnings($school_id)
    {
        $warnings = [];

        // Check books
        $books = $this->getBookCountWithCapacity($school_id);
        if ($books['is_trial'] && $books['percentage'] > 80) {
            $warnings[] = [
                'type' => 'books',
                'message' => "Kapasitas buku " . $books['percentage'] . "% ({$books['current']}/{$books['max']})",
                'severity' => 'warning'
            ];
        }

        // Check students
        $students = $this->getStudentCountWithCapacity($school_id);
        if ($students['is_trial'] && $students['percentage'] > 80) {
            $warnings[] = [
                'type' => 'students',
                'message' => "Kapasitas siswa " . $students['percentage'] . "% ({$students['current']}/{$students['max']})",
                'severity' => 'warning'
            ];
        }

        // Check borrows
        $borrows = $this->getBorrowCountWithCapacity($school_id);
        if ($borrows['is_trial'] && $borrows['percentage'] > 80) {
            $warnings[] = [
                'type' => 'borrows',
                'message' => "Peminjaman bulan ini " . $borrows['percentage'] . "% ({$borrows['current']}/{$borrows['max']})",
                'severity' => 'warning'
            ];
        }

        // Check trial expiry
        if ($this->multiTenantManager->isSchoolTrial($school_id)) {
            $daysRemaining = $this->multiTenantManager->getTrialDaysRemaining($school_id);
            if ($daysRemaining <= 3 && $daysRemaining > 0) {
                $warnings[] = [
                    'type' => 'trial_expiry',
                    'message' => "Trial kadaluarsa dalam $daysRemaining hari",
                    'severity' => 'critical'
                ];
            } elseif ($daysRemaining == 0) {
                $warnings[] = [
                    'type' => 'trial_expired',
                    'message' => "Trial sudah kadaluarsa. Ajukan aktivasi untuk lanjut.",
                    'severity' => 'critical'
                ];
            }
        }

        return $warnings;
    }

    /**
     * Check if trial has expired and handle
     */
    public function handleTrialExpiry($school_id)
    {
        if ($this->multiTenantManager->isSchoolTrial($school_id)) {
            if ($this->multiTenantManager->isTrialExpired($school_id)) {
                // Optionally: suspend school atau show blocking message
                // Untuk sekarang hanya return true (handle di view)
                return true;
            }
        }
        return false;
    }
}

// Custom Exception Classes
class TrialLimitException extends Exception
{
}
class TrialFeatureException extends Exception
{
}

?>