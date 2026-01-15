-- ===================================
-- Book Maintenance Module
-- Database Table Creation Script
-- ===================================

-- Drop existing table if exists
DROP TABLE IF EXISTS book_maintenance;

-- Create book_maintenance table
CREATE TABLE book_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    CONSTRAINT fk_maintenance_book
        FOREIGN KEY (book_id) REFERENCES books(id)
        ON DELETE CASCADE,
    
    -- Indexes untuk performa query
    INDEX idx_book_id (book_id),
    INDEX idx_status (status),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- Selesai! Table sudah siap digunakan
-- ===================================
