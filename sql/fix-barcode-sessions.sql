-- Fix barcode_sessions table to have proper AUTO_INCREMENT id

-- Drop the old table
DROP TABLE IF EXISTS barcode_sessions;

-- Create the corrected table
CREATE TABLE `barcode_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `school_id` int(11) NOT NULL,
  `session_token` varchar(32) NOT NULL UNIQUE,
  `status` enum('active','completed','expired') DEFAULT 'active',
  `member_barcode` varchar(255) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `books_scanned` longtext DEFAULT NULL COMMENT 'JSON array of scanned book data',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 30 minute),
  KEY `school_id` (`school_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
