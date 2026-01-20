-- Multi-Tenant System Database Migration
-- Run this to add multi-tenant support to perpustakaan_online database

-- =====================================================
-- 1. Modify schools table - Add status & fields
-- =====================================================

ALTER TABLE `schools` 
ADD COLUMN `status` ENUM('trial', 'active', 'suspended') DEFAULT 'trial' AFTER `slug`,
ADD COLUMN `trial_started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`,
ADD COLUMN `trust_score` INT DEFAULT 0 AFTER `trial_started_at`,
ADD COLUMN `activation_requested_at` TIMESTAMP NULL AFTER `trust_score`,
ADD COLUMN `activation_requested_by` INT NULL AFTER `activation_requested_at`,
ADD COLUMN `admin_notes` TEXT DEFAULT NULL AFTER `activation_requested_by`;

-- Update existing schools to trial (if not active)
UPDATE `schools` SET `status` = 'trial' WHERE `status` = 'pending';
UPDATE `schools` SET `status` = 'trial' WHERE `status` = 'rejected';

-- =====================================================
-- 2. Create activation_codes table
-- =====================================================

CREATE TABLE IF NOT EXISTS `activation_codes` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT NOT NULL,
  `code` VARCHAR(12) NOT NULL UNIQUE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `regenerated_at` TIMESTAMP NULL,
  `regenerated_by` INT NULL,
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. Create trust_scores table
-- =====================================================

CREATE TABLE IF NOT EXISTS `trust_scores` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT NOT NULL,
  `total_score` INT DEFAULT 0,
  `factors` JSON,
  `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_updated_by` VARCHAR(50),
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_total_score` (`total_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. Create trial_limits table
-- =====================================================

CREATE TABLE IF NOT EXISTS `trial_limits` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT NOT NULL,
  `limit_type` ENUM('books', 'students', 'borrows_monthly') NOT NULL,
  `max_allowed` INT NOT NULL,
  `current_count` INT DEFAULT 0,
  `checked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_limit` (`school_id`, `limit_type`),
  INDEX `idx_school_id` (`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default trial limits for existing schools
INSERT INTO `trial_limits` (`school_id`, `limit_type`, `max_allowed`)
SELECT `id`, 'books', 50 FROM `schools` WHERE `status` = 'trial'
ON DUPLICATE KEY UPDATE `max_allowed` = 50;

INSERT INTO `trial_limits` (`school_id`, `limit_type`, `max_allowed`)
SELECT `id`, 'students', 100 FROM `schools` WHERE `status` = 'trial'
ON DUPLICATE KEY UPDATE `max_allowed` = 100;

INSERT INTO `trial_limits` (`school_id`, `limit_type`, `max_allowed`)
SELECT `id`, 'borrows_monthly', 200 FROM `schools` WHERE `status` = 'trial'
ON DUPLICATE KEY UPDATE `max_allowed` = 200;

-- =====================================================
-- 5. Create school_activities table (for logging & anomaly detection)
-- =====================================================

CREATE TABLE IF NOT EXISTS `school_activities` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT NOT NULL,
  `activity_type` VARCHAR(100) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `data_count` INT DEFAULT 1,
  `ip_address` VARCHAR(45),
  `user_id` INT,
  `details` JSON,
  `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_activity_type` (`activity_type`),
  INDEX `idx_recorded_at` (`recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. Create trust_score_factors reference table
-- =====================================================

CREATE TABLE IF NOT EXISTS `trust_score_factors` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `factor_code` VARCHAR(50) NOT NULL UNIQUE,
  `factor_name` VARCHAR(255) NOT NULL,
  `points` INT NOT NULL,
  `description` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default trust score factors
INSERT INTO `trust_score_factors` (`factor_code`, `factor_name`, `points`, `description`) VALUES
('activation_requested', 'Sekolah mengajukan aktivasi', 10, 'Submission via activation button'),
('email_sch_id', 'Email admin domain .sch.id', 15, 'Official school domain'),
('activation_code_entered', 'Kode aktivasi dimasukkan', 20, 'Proof of admin access'),
('normal_activity', 'Aktivitas sistem wajar', 25, 'No anomalies detected'),
('trial_duration', 'Umur trial > 7 hari', 10, 'Sufficient testing period'),
('min_transactions', 'Minimal 5 transaksi', 10, 'Active system usage'),
('email_verified', 'Email verified', 5, 'Additional verification');

-- =====================================================
-- 7. Create trust_score_history table
-- =====================================================

CREATE TABLE IF NOT EXISTS `trust_score_history` (
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` INT NOT NULL,
  `old_score` INT DEFAULT 0,
  `new_score` INT DEFAULT 0,
  `reason` VARCHAR(255),
  `triggered_by` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`school_id`) REFERENCES `schools`(`id`) ON DELETE CASCADE,
  INDEX `idx_school_id` (`school_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. Add school_id index to existing tables (if not exists)
-- =====================================================

ALTER TABLE `books` ADD INDEX `idx_school_id` (`school_id`);
ALTER TABLE `members` ADD INDEX `idx_school_id` (`school_id`);
ALTER TABLE `borrows` ADD INDEX `idx_school_id` (`school_id`);
ALTER TABLE `users` ADD INDEX `idx_school_id` (`school_id`);

-- =====================================================
-- 9. Insert default activation codes for existing schools
-- =====================================================

INSERT INTO `activation_codes` (`school_id`, `code`)
SELECT `id`, CONCAT(SUBSTRING(MD5(CONCAT(`id`, `name`, NOW())), 1, 12))
FROM `schools`
WHERE `id` NOT IN (SELECT `school_id` FROM `activation_codes`)
ON DUPLICATE KEY UPDATE `code` = `code`;

-- =====================================================
-- Done!
-- =====================================================
-- Verify by running:
-- SELECT * FROM schools WHERE id = 2;
-- SELECT * FROM activation_codes WHERE school_id = 2;
-- SELECT * FROM trial_limits WHERE school_id = 2;

