-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 04:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `perpustakaan_online`
--

-- --------------------------------------------------------

--
-- Table structure for table `barcode_sessions`
--

CREATE TABLE `barcode_sessions` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `session_token` varchar(32) NOT NULL,
  `status` enum('active','completed','expired') DEFAULT 'active',
  `member_barcode` varchar(255) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `books_scanned` longtext DEFAULT NULL COMMENT 'JSON array of scanned book data',
  `due_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 30 minute)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `access_level` enum('all','teacher_only') DEFAULT 'all',
  `copies` int(11) DEFAULT 1,
  `max_borrow_days` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shelf` varchar(50) DEFAULT NULL,
  `row_number` int(11) DEFAULT NULL,
  `cover_image` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `school_id`, `title`, `author`, `isbn`, `category`, `access_level`, `copies`, `max_borrow_days`, `created_at`, `shelf`, `row_number`, `cover_image`) VALUES
(1, 4, 'Mengunyah Rindu', 'Budi Maryono', '982384', 'Fiksi', 'all', 1, NULL, '2026-01-26 02:48:25', '1A', 1, 'book_1769399260_6976e3dc930ca.jpg'),
(2, 4, 'Bu, aku ingin pelukmu', 'Reza Mustopa', '4522343', 'Fiksi', 'all', 1, NULL, '2026-01-26 02:49:48', '1A', 1, 'book_1769399253_6976e3d564a9a.png'),
(3, 4, 'Madilog', 'Tan Malaka', '533454', 'Referensi', 'all', 1, NULL, '2026-01-26 02:52:47', '1B', 1, 'book_1769399247_6976e3cfbddeb.jpeg'),
(4, 4, 'Sebuah Seni Untuk Bersikap Bodoamat', 'Mark Manson', '345645', 'Non-Fiksi', 'all', 1, NULL, '2026-01-26 02:55:04', '1B', 2, 'book_1769399240_6976e3c8d253c.png'),
(5, 4, 'The Psychology of Money', 'Morgan Housel', '9786238371044', 'Lainnya', 'all', 1, NULL, '2026-01-26 04:02:19', '1B', 5, 'book_1769400139_6976e74ba9d73.jpg'),
(6, 4, 'Sang Alkemis', 'Paulo Coelho', '9786020656069', 'Lainnya', 'all', 1, NULL, '2026-01-26 04:03:53', '1B', 4, 'book_1769400245_6976e7b53bd5e.jpg'),
(7, 4, 'B.J. Habibie : Sebuah Biografi', 'Fatimah Fayrus', '9786231643094', 'Biografi', 'all', 1, NULL, '2026-01-26 04:07:07', '1C', 2, 'book_1769400427_6976e86b698e3.jpg'),
(8, 10, 'dfgdfg', 'dfgdfg', '232343', 'Referensi', 'all', 1, NULL, '2026-01-29 04:24:26', '1A', 3, 'book_1769660674_697ae1029671b.jpg'),
(14, 4, 'stoicsm', 'andora', '34567890', 'Non-Fiksi', 'teacher_only', 1, NULL, '2026-02-10 07:15:57', '1', 2, 'book_1770707752_698adb282818f.jpg'),
(15, 4, 'coding', 'someone', '9860997', 'Referensi', 'teacher_only', 1, NULL, '2026-02-11 01:55:28', '1', 2, 'book_1770774928_698be1905b664.jpg'),
(16, 4, 'program', 'someone', '9875654', 'Teknologi', 'teacher_only', 1, NULL, '2026-02-11 02:16:19', '1', 2, 'book_1770776179_698be673f3ad1.jpg'),
(17, 4, 'jh', 'kj', '55', 'Non-Fiksi', 'teacher_only', 1, NULL, '2026-02-11 02:21:13', '5', 7, 'book_1770776473_698be79997086.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `book_damage_fines`
--

CREATE TABLE `book_damage_fines` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `borrow_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `damage_type` varchar(50) NOT NULL,
  `damage_description` text DEFAULT NULL,
  `fine_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `book_maintenance`
--

CREATE TABLE `book_maintenance` (
  `id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_maintenance`
--

INSERT INTO `book_maintenance` (`id`, `book_id`, `status`, `notes`, `updated_at`) VALUES
(1, 3, 'Good', 'Bagus', '2026-01-26 02:55:40');

-- --------------------------------------------------------

--
-- Table structure for table `borrows`
--

CREATE TABLE `borrows` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `borrowed_at` datetime DEFAULT current_timestamp(),
  `due_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue','pending_return','pending_confirmation') DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_status` enum('unpaid','paid') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrows`
--

INSERT INTO `borrows` (`id`, `school_id`, `book_id`, `member_id`, `borrowed_at`, `due_at`, `returned_at`, `status`, `fine_amount`, `fine_status`) VALUES
(31, 10, 8, 3, '2026-01-30 08:32:40', '2026-02-06 02:32:40', NULL, 'pending_confirmation', 0.00, 'unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `role` enum('student','teacher','employee') DEFAULT 'student',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `max_pinjam` int(11) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `school_id`, `name`, `email`, `nisn`, `role`, `status`, `created_at`, `max_pinjam`) VALUES
(3, 10, 'fafas', 'asdas@gmail.com', '1211211', 'student', 'active', '2026-01-29 04:25:45', 2),
(4, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', 'student', 'active', '2026-02-10 01:14:22', 2),
(6, 4, 'surya', 'uya4767@gmail.com', '2346558', 'student', 'active', '2026-02-10 06:53:08', 2);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('borrow','return_request','return_confirm','late_warning','info','new_book') NOT NULL DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `school_id`, `student_id`, `title`, `message`, `type`, `is_read`, `created_at`, `updated_at`) VALUES
(18, 4, 3, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"Bu, aku ingin pelukmu\" ke koleksi favorit Anda.', 'info', 0, '2026-02-03 08:15:54', '2026-02-03 08:15:54'),
(37, 4, 2, 'Pengembalian Disetujui', 'Admin telah mengonfirmasi pengembalian buku \"menyerah bukan pilihan\". Terima kasih!', 'return_confirm', 0, '2026-02-05 04:14:07', '2026-02-05 04:14:07'),
(38, 4, 2, 'Pengembalian Disetujui', 'Admin telah mengonfirmasi pengembalian buku \"Belajar coding membuat program\". Terima kasih!', 'return_confirm', 0, '2026-02-05 04:14:16', '2026-02-05 04:14:16'),
(39, 4, 2, 'Pengembalian Disetujui', 'Admin telah mengonfirmasi pengembalian buku \"B.J. Habibie : Sebuah Biografi\". Terima kasih!', 'return_confirm', 0, '2026-02-05 04:14:19', '2026-02-05 04:14:19'),
(45, 4, 13, 'Buku Baru Tersedia', 'Buku \"stoicsm\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-10 07:15:57', '2026-02-10 07:15:57'),
(46, 4, 14, 'Buku Baru Tersedia', 'Buku \"stoicsm\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-10 07:16:25', '2026-02-10 07:16:25'),
(47, 4, 13, 'Buku Baru Tersedia', 'Buku \"coding\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 01:55:28', '2026-02-11 01:55:28'),
(48, 4, 14, 'Buku Baru Tersedia', 'Buku \"coding\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 01:55:32', '2026-02-11 01:55:32'),
(49, 4, 13, 'Buku Baru Tersedia', 'Buku \"program\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:16:20', '2026-02-11 02:16:20'),
(50, 4, 14, 'Buku Baru Tersedia', 'Buku \"program\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:16:23', '2026-02-11 02:16:23'),
(51, 4, 13, 'Buku Baru Tersedia', 'Buku \"jh\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:21:13', '2026-02-11 02:21:13'),
(52, 4, 14, 'Buku Baru Tersedia', 'Buku \"jh\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-02-11 02:21:17', '2026-02-11 02:21:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `pesan` text NOT NULL,
  `jenis_notifikasi` enum('telat','peringatan','pengembalian','info','sukses','buku','default') DEFAULT 'default',
  `tanggal` datetime DEFAULT current_timestamp(),
  `status_baca` tinyint(1) DEFAULT 0 COMMENT '0 = belum dibaca, 1 = sudah dibaca',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rating_buku`
--

CREATE TABLE `rating_buku` (
  `id_rating` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_buku` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rating_buku`
--

INSERT INTO `rating_buku` (`id_rating`, `id_user`, `id_buku`, `rating`, `komentar`, `created_at`) VALUES
(1, 9, 7, 5, 'bgu\r\n', '2026-02-06 03:11:02'),
(2, 4, 7, 4, 'Mantap', '2026-02-09 03:55:16');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `status` enum('pending','active','rejected') DEFAULT 'pending',
  `activation_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `founded_year` int(11) DEFAULT NULL,
  `borrow_duration` int(11) DEFAULT 7,
  `late_fine` decimal(10,2) DEFAULT 500.00,
  `max_books` int(11) DEFAULT 3,
  `max_books_student` int(11) DEFAULT 3,
  `max_books_teacher` int(11) DEFAULT 10,
  `max_books_employee` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `slug`, `status`, `activation_code`, `created_at`, `email`, `phone`, `address`, `description`, `logo`, `profile_picture`, `npsn`, `website`, `photo_path`, `founded_year`, `borrow_duration`, `late_fine`, `max_books`, `max_books_student`, `max_books_teacher`, `max_books_employee`) VALUES
(3, 'SMK BINA MANDIRI MULTIMEDIA', 'smk-bina-mandiri-multimedia', 'pending', NULL, '2026-01-26 02:40:51', NULL, '082-9999999', 'Jl. Updated No. 999', NULL, NULL, NULL, '12345', 'https://updated.com', NULL, 2020, 4, 500.00, 3, 3, 10, 5),
(4, 'AUSTRALIA INDEPENDENTS SCHOOL', '', 'pending', NULL, '2026-01-26 02:42:13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'public/uploads/school-photos/school_1769399275_6976e3ebe6757.jpg', 0, 7, 600.00, 4, 5, 5, 5),
(5, 'sdfsdf', 'sdfsdf', 'pending', NULL, '2026-01-26 04:37:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5),
(7, 'sdfsdfasdasd', 'sdfsdfasdasd', 'pending', NULL, '2026-01-26 04:39:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5),
(8, 'sdfbhsd', 'sdfbhsd', 'pending', NULL, '2026-01-26 04:42:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5),
(9, 'hdgdfg', 'hdgdfg', 'pending', NULL, '2026-01-26 04:44:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5),
(10, 'smamaju', 'smamaju', 'pending', NULL, '2026-01-29 02:37:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5),
(11, 'smk bm3', 'smk-bm3', 'pending', NULL, '2026-02-02 02:46:29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 7, 500.00, 3, 3, 10, 5);

-- --------------------------------------------------------

--
-- Table structure for table `school_themes`
--

CREATE TABLE `school_themes` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `theme_name` varchar(50) DEFAULT 'light',
  `custom_colors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_colors`)),
  `typography` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`typography`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_themes`
--

INSERT INTO `school_themes` (`id`, `school_id`, `theme_name`, `custom_colors`, `typography`, `created_at`, `updated_at`) VALUES
(1, 3, 'light', NULL, NULL, '2026-01-26 02:41:21', '2026-01-26 02:41:21'),
(2, 4, 'terang', NULL, NULL, '2026-01-26 02:43:32', '2026-02-12 02:57:57');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` char(1) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nama_lengkap`, `nisn`, `kelas`, `jurusan`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `email`, `no_hp`, `foto`, `created_at`, `updated_at`) VALUES
(3, 'Budi', NULL, NULL, NULL, NULL, NULL, NULL, 'australiaindependentsschool@sch.id', NULL, NULL, '2026-02-02 02:53:09', '2026-02-02 02:53:09'),
(4, 'Anjali Saputra', '0094234', 'XI', 'Rekayasa Perangkat Lunak', '2008-01-17', 'L', 'Limus', 'anjalisaputra@gmail.com', '089234234', 'uploads/siswa/siswa_4_1769479766_69781e562270d.webp', '2026-01-26 03:07:53', '2026-01-27 02:09:26'),
(9, 'Suryaaa', '000000', NULL, NULL, NULL, NULL, NULL, 'uya4767@gmail.com', NULL, 'uploads/siswa/siswa_9_1770188218_6982edbac928b.jpg', '2026-02-02 02:50:24', '2026-02-06 06:55:14'),
(11, 'fafas', '1211211', NULL, NULL, NULL, NULL, NULL, 'asdas@gmail.com', NULL, NULL, '2026-02-02 02:45:49', '2026-02-02 02:45:49'),
(14, 'surya', '2346558', NULL, NULL, NULL, NULL, NULL, 'uya4767@gmail.com', NULL, NULL, '2026-02-11 06:23:05', '2026-02-11 06:23:05');

-- --------------------------------------------------------

--
-- Table structure for table `special_themes`
--

CREATE TABLE `special_themes` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `theme_key` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `special_themes`
--

INSERT INTO `special_themes` (`id`, `school_id`, `name`, `date`, `is_active`, `theme_key`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'Test Theme', '2026-02-12', 1, 'kemerdekaan', NULL, '2026-02-12 02:10:07', '2026-02-12 02:10:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `code_expires_at` timestamp NULL DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `role` enum('admin','librarian','student','teacher','employee') DEFAULT 'librarian',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `nisn`, `password`, `verification_code`, `code_expires_at`, `is_verified`, `verified_at`, `role`, `created_at`) VALUES
(2, 3, 'Gani', 'gani@sch.id', NULL, '', NULL, '2026-01-25 20:55:51', 1, '2026-01-26 02:41:02', 'admin', '2026-01-26 02:40:51'),
(3, 4, 'Budi', 'australiaindependentsschool@sch.id', NULL, '$2y$10$YYkXfCuDmZWDCEymXPClM.viULRMLWKBxk70BmOxepED0GMNeOWiS', NULL, '2026-01-25 20:57:13', 1, '2026-01-26 02:42:24', 'admin', '2026-01-26 02:42:14'),
(5, 5, 'Gani', 'sdfdf@sch.id', NULL, '$2y$10$y5RgsJjO.nbQ3XXqFeQ4X.5zkiCKKAXEZnH.EhVXPdyvOPc/.MRDm', NULL, '2026-01-25 22:52:04', 1, '2026-01-26 04:37:14', 'admin', '2026-01-26 04:37:04'),
(6, 7, 'sdfsdfasdasd', 'sdfsdfsf@sch.id', NULL, '$2y$10$qTK44/muE8jMTShecEdkZuPoFN3Kh9dDnc80qmzQo1bLNL6zgQ3ua', NULL, '2026-01-25 22:54:59', 1, '2026-01-26 04:40:09', 'admin', '2026-01-26 04:39:59'),
(7, 8, 'sghdfgdf', 'sdsdfsfdf@sch.id', NULL, '$2y$10$E3CEHA.8I4ICe1cYR7hdve6bfeEtSuhXHjrf4q.D.Ux9h.QyZpaQG', NULL, '2026-01-25 22:57:48', 1, '2026-01-26 04:42:57', 'admin', '2026-01-26 04:42:48'),
(8, 9, 'ertert', 'hgdfgdfg@sch.id', NULL, '$2y$10$kIJvRguAWIEKu6XrYqSjLOM8SRIDa0Tgz5PxDcTD1Lkdx7D0QpIpW', NULL, NULL, 1, '2026-01-26 04:44:53', 'admin', '2026-01-26 04:44:45'),
(10, 10, 'maju', 'maju@sch.id', NULL, '$2y$10$P0Dvl957VriyhB2Ss5ZQv.VvPhlJS0iRKgbFyeYnT58YQ9bCBsTS6', NULL, NULL, 1, '2026-01-29 02:37:56', 'admin', '2026-01-29 02:37:45'),
(11, 10, 'fafas', 'asdas@gmail.com', '1211211', '$2y$10$kxRqC.NMu.l1Gt/nT6ITQ.8gRFdrhBww3Bc1AeUxU5qCQq5kbzJey', NULL, NULL, 0, NULL, 'student', '2026-01-29 04:25:45'),
(12, 11, 'someone', 'ada@sch.id', NULL, '$2y$10$hK3aWhbPDT0JIxa.hKx1UOnsgOz554F0tAsK3j.KM4CVW5briRGXO', '548097', '2026-02-01 21:01:29', 0, NULL, 'admin', '2026-02-02 02:46:30'),
(13, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', '$2y$10$KCo55BDxMYIXkX0V7nutBuBW89.oi9f/WZ6NKQFegU7VbfDcHgMNq', NULL, NULL, 0, NULL, 'student', '2026-02-10 01:14:22'),
(14, 4, 'surya', 'uya4767@gmail.com', '2346558', '$2y$10$WyqJq5kEZzBf6HJpYcq8COPZEBf3IcIlUGzEsPPEPYFWxE2PZspQ.', NULL, NULL, 0, NULL, 'student', '2026-02-10 06:53:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barcode_sessions`
--
ALTER TABLE `barcode_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_borrow_id` (`borrow_id`),
  ADD KEY `idx_member_id` (`member_id`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_book_id` (`book_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_updated_at` (`updated_at`);

--
-- Indexes for table `borrows`
--
ALTER TABLE `borrows`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_book` (`student_id`,`book_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_book` (`book_id`),
  ADD KEY `idx_student_book` (`student_id`,`book_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_members_school_status` (`school_id`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_school` (`student_id`,`school_id`),
  ADD KEY `idx_read_status` (`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `idx_student_unread` (`student_id`,`is_read`,`created_at`),
  ADD KEY `idx_student_type` (`student_id`,`type`,`created_at`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `idx_siswa` (`id_siswa`),
  ADD KEY `idx_status` (`status_baca`),
  ADD KEY `idx_jenis` (`jenis_notifikasi`),
  ADD KEY `idx_tanggal` (`tanggal`);
ALTER TABLE `notifikasi` ADD FULLTEXT KEY `ft_search` (`judul`,`pesan`);

--
-- Indexes for table `rating_buku`
--
ALTER TABLE `rating_buku`
  ADD PRIMARY KEY (`id_rating`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_schools_status` (`status`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `school_themes`
--
ALTER TABLE `school_themes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD KEY `idx_nisn` (`nisn`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `special_themes`
--
ALTER TABLE `special_themes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `date` (`date`,`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_users_school_email` (`school_id`,`email`),
  ADD KEY `idx_verification_code` (`verification_code`),
  ADD KEY `idx_is_verified` (`is_verified`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barcode_sessions`
--
ALTER TABLE `barcode_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `borrows`
--
ALTER TABLE `borrows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rating_buku`
--
ALTER TABLE `rating_buku`
  MODIFY `id_rating` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `school_themes`
--
ALTER TABLE `school_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `special_themes`
--
ALTER TABLE `special_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `barcode_sessions`
--
ALTER TABLE `barcode_sessions`
  ADD CONSTRAINT `barcode_sessions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `barcode_sessions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  ADD CONSTRAINT `fk_damage_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_damage_borrow` FOREIGN KEY (`borrow_id`) REFERENCES `borrows` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_damage_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_damage_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  ADD CONSTRAINT `fk_maintenance_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrows`
--
ALTER TABLE `borrows`
  ADD CONSTRAINT `borrows_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrows_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrows_ibfk_3` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_themes`
--
ALTER TABLE `school_themes`
  ADD CONSTRAINT `school_themes_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
