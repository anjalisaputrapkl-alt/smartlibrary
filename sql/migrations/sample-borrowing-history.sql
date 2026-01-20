-- ============================================
-- Sample Data untuk Testing Modul Riwayat Peminjaman
-- ============================================

-- Pastikan sudah ada sekolah
-- INSERT INTO schools (id, name, slug, status) VALUES (7, 'smk bm3', 'smk-bm3', 'active');

-- Pastikan sudah ada members (siswa)
-- Data ini mungkin sudah ada, uncomment jika belum

INSERT INTO members (school_id, name, email, member_no, nisn, status) VALUES
(7, 'Aldi Pratama', 'aldi.pratama@email.com', '001', '1234567890', 'active'),
(7, 'Budi Santoso', 'budi.santoso@email.com', '002', '1234567891', 'active'),
(7, 'Citra Dewi', 'citra.dewi@email.com', '003', '1234567892', 'active')
ON DUPLICATE KEY UPDATE status = 'active';

-- ============================================
-- Insert Sample Buku (jika belum ada)
-- ============================================

INSERT INTO books (school_id, title, author, isbn, category, copies, shelf, row_number, cover_image) VALUES
(7, 'Mentalitas Kaya', 'T. Harv Eker', '9789793068476', 'Self-Help', 3, '1', 1, 'book_1.jpg'),
(7, 'Atomic Habits', 'James Clear', '9780735211292', 'Self-Help', 2, '1', 2, 'book_2.jpg'),
(7, 'Sapiens', 'Yuval Noah Harari', '9780062316097', 'Non-Fiksi', 2, '2', 1, 'book_3.jpg'),
(7, 'Laskar Pelangi', 'Andrea Hirata', '9789793061537', 'Fiksi', 5, '2', 2, 'book_4.jpg'),
(7, 'The Lean Startup', 'Eric Ries', '9780307887894', 'Bisnis', 1, '3', 1, 'book_5.jpg')
ON DUPLICATE KEY UPDATE copies = copies;

-- ============================================
-- Insert Sample Data Peminjaman (Riwayat)
-- ============================================

-- Member dengan ID 3 (Uya) - Sudah ada di database
-- Data peminjaman yang sudah ada akan di-update

-- 1. Peminjaman yang sudah dikembalikan (bulan lalu)
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, returned_at, status) VALUES
(7, 18, 3, '2026-01-05 08:00:00', '2026-01-12 00:00:00', '2026-01-11 15:30:00', 'returned'),
(7, 19, 3, '2026-01-01 09:00:00', '2026-01-08 00:00:00', '2026-01-08 14:00:00', 'returned');

-- 2. Peminjaman yang sedang berlangsung (normal)
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, returned_at, status) VALUES
(7, 20, 3, '2026-01-19 10:00:00', '2026-01-26 00:00:00', NULL, 'borrowed');

-- 3. Peminjaman yang sudah dikembalikan tapi telat
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, returned_at, status) VALUES
(7, 21, 3, '2026-01-10 11:00:00', '2026-01-17 00:00:00', '2026-01-19 10:00:00', 'returned');

-- 4. Peminjaman yang masih dipinjam (normal)
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, returned_at, status) VALUES
(7, 22, 3, '2026-01-18 14:00:00', '2026-01-25 00:00:00', NULL, 'borrowed');

-- 5. Peminjaman untuk member lain (Budi - ID 4)
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, returned_at, status) VALUES
(7, 23, 4, '2026-01-16 09:00:00', '2026-01-23 00:00:00', NULL, 'borrowed'),
(7, 24, 4, '2026-01-08 10:00:00', '2026-01-15 00:00:00', '2026-01-15 13:00:00', 'returned');

-- 6. Peminjaman untuk member lain (Citra - ID 5)
INSERT INTO borrows (school_id, book_id, member_id, borrowed_at, due_at, returned_at, status) VALUES
(7, 10, 5, '2026-01-17 08:30:00', '2026-01-24 00:00:00', NULL, 'borrowed');

-- ============================================
-- Verifikasi Data
-- ============================================

-- Tampilkan semua peminjaman untuk member 3
SELECT 
    b.id,
    b.borrowed_at,
    b.due_at,
    b.returned_at,
    b.status,
    bk.title,
    bk.author,
    DATEDIFF(b.due_at, NOW()) as days_remaining
FROM borrows b
LEFT JOIN books bk ON b.book_id = bk.id
WHERE b.member_id = 3
ORDER BY b.borrowed_at DESC;

-- Tampilkan statistik untuk setiap member
SELECT 
    m.id,
    m.name,
    COUNT(b.id) as total_borrows,
    SUM(CASE WHEN b.status = 'borrowed' THEN 1 ELSE 0 END) as currently_borrowed,
    SUM(CASE WHEN b.status = 'returned' THEN 1 ELSE 0 END) as returned,
    SUM(CASE WHEN b.status = 'overdue' THEN 1 ELSE 0 END) as overdue
FROM members m
LEFT JOIN borrows b ON m.id = b.member_id
WHERE m.school_id = 7
GROUP BY m.id, m.name;

-- ============================================
-- Update Status Overdue jika ada yang telat
-- (jalankan daily atau on-demand)
-- ============================================

UPDATE borrows 
SET status = 'overdue'
WHERE status = 'borrowed' 
  AND due_at < NOW() 
  AND returned_at IS NULL;

-- ============================================
-- Cleanup (jika perlu reset)
-- ============================================
-- DELETE FROM borrows WHERE member_id IN (3, 4, 5) AND school_id = 7;
-- DELETE FROM members WHERE id IN (1, 2, 3, 4, 5) AND school_id = 7;
-- DELETE FROM books WHERE school_id = 7;
