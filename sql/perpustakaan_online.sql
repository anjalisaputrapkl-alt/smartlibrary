-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 27, 2026 at 08:50 AM
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
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(100) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `copies` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shelf` varchar(50) DEFAULT NULL,
  `row_number` int(11) DEFAULT NULL,
  `cover_image` varchar(225) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `school_id`, `title`, `author`, `isbn`, `category`, `copies`, `created_at`, `shelf`, `row_number`, `cover_image`) VALUES
(1, 4, 'Mengunyah Rindu', 'Budi Maryono', '982384', 'Fiksi', 1, '2026-01-26 02:48:25', '1A', 1, 'book_1769399260_6976e3dc930ca.jpg'),
(2, 4, 'Bu, aku ingin pelukmu', 'Reza Mustopa', '4522343', 'Fiksi', 5, '2026-01-26 02:49:48', '1A', 1, 'book_1769399253_6976e3d564a9a.png'),
(3, 4, 'Madilog', 'Tan Malaka', '533454', 'Referensi', 3, '2026-01-26 02:52:47', '1B', 1, 'book_1769399247_6976e3cfbddeb.jpeg'),
(4, 4, 'Sebuah Seni Untuk Bersikap Bodoamat', 'Mark Manson', '345645', 'Non-Fiksi', 5, '2026-01-26 02:55:04', '1B', 2, 'book_1769399240_6976e3c8d253c.png'),
(5, 4, 'The Psychology of Money', 'Morgan Housel', '9786238371044', 'Lainnya', 14, '2026-01-26 04:02:19', '1B', 5, 'book_1769400139_6976e74ba9d73.jpg'),
(6, 4, 'Sang Alkemis', 'Paulo Coelho', '9786020656069', 'Lainnya', 2, '2026-01-26 04:03:53', '1B', 4, 'book_1769400245_6976e7b53bd5e.jpg'),
(7, 4, 'B.J. Habibie : Sebuah Biografi', 'Fatimah Fayrus', '9786231643094', 'Biografi', 1, '2026-01-26 04:07:07', '1C', 2, 'book_1769400427_6976e86b698e3.jpg');

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

--
-- Dumping data for table `book_damage_fines`
--

INSERT INTO `book_damage_fines` (`id`, `school_id`, `borrow_id`, `member_id`, `book_id`, `damage_type`, `damage_description`, `fine_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 1, 1, 'minor_tear', 'Robekan di pinggir bawah halaman 10', 25000.00, 'pending', '2026-01-27 03:40:23', '2026-01-27 03:40:23');

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
  `status` enum('borrowed','returned','overdue','pending_return') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrows`
--

INSERT INTO `borrows` (`id`, `school_id`, `book_id`, `member_id`, `borrowed_at`, `due_at`, `returned_at`, `status`) VALUES
(1, 4, 1, 1, '2026-01-26 10:08:29', '2026-02-02 10:08:29', NULL, 'borrowed'),
(2, 4, 7, 1, '2026-01-27 08:40:22', '2026-02-03 08:40:22', '2026-01-27 09:11:15', 'returned'),
(3, 4, 5, 1, '2026-01-27 09:17:32', '2026-02-03 09:17:32', NULL, 'pending_return'),
(4, 4, 4, 1, '2026-01-27 09:17:43', '2026-02-03 09:17:43', NULL, 'borrowed');

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

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `student_id`, `book_id`, `category`, `created_at`) VALUES
(1, 4, 4, NULL, '2026-01-26 03:07:56'),
(3, 4, 2, NULL, '2026-01-26 03:07:58'),
(4, 4, 1, NULL, '2026-01-26 03:07:59'),
(5, 4, 7, NULL, '2026-01-27 02:11:42');

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
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `school_id`, `name`, `email`, `nisn`, `status`, `created_at`) VALUES
(1, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', 'active', '2026-01-26 03:06:14'),
(2, 4, 'Surya', 'surz@gmail.com', '000000', 'active', '2026-01-27 03:56:53');

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
(1, 4, 4, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"Sebuah Seni Untuk Bersikap Bodoamat\" ke koleksi favorit Anda.', 'info', 0, '2026-01-26 03:07:56', '2026-01-26 03:07:56'),
(2, 4, 4, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"Madilog\" ke koleksi favorit Anda.', 'info', 0, '2026-01-26 03:07:57', '2026-01-26 03:07:57'),
(3, 4, 4, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"Bu, aku ingin pelukmu\" ke koleksi favorit Anda.', 'info', 0, '2026-01-26 03:07:58', '2026-01-26 03:07:58'),
(4, 4, 4, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"Mengunyah Rindu\" ke koleksi favorit Anda.', 'info', 0, '2026-01-26 03:07:59', '2026-01-26 03:07:59'),
(5, 4, 4, 'Peminjaman Berhasil', 'Anda telah meminjam buku \"Mengunyah Rindu\". Harap dikembalikan sebelum tanggal 02/02/2026.', 'borrow', 0, '2026-01-26 03:08:29', '2026-01-26 03:08:29'),
(6, 4, 4, 'Buku Baru Tersedia', 'Buku \"The Psychology of Money\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-01-26 04:02:19', '2026-01-26 04:02:19'),
(7, 4, 4, 'Buku Baru Tersedia', 'Buku \"Sang Alkemis\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-01-26 04:03:53', '2026-01-26 04:03:53'),
(8, 4, 4, 'Buku Baru Tersedia', 'Buku \"B.J. Habibie : Sebuah Biografi\" telah ditambahkan ke perpustakaan. Silakan pinjam sekarang!', 'new_book', 0, '2026-01-26 04:07:07', '2026-01-26 04:07:07'),
(9, 4, 4, 'Peminjaman Berhasil', 'Anda telah meminjam buku \"B.J. Habibie : Sebuah Biografi\". Harap dikembalikan sebelum tanggal 03/02/2026.', 'borrow', 0, '2026-01-27 01:40:22', '2026-01-27 01:40:22'),
(10, 4, 4, 'Permintaan Pengembalian Dikirim', 'Permintaan pengembalian untuk buku \"B.J. Habibie : Sebuah Biografi\" menunggu konfirmasi admin.', 'return_request', 0, '2026-01-27 02:10:23', '2026-01-27 02:10:23'),
(12, 4, 4, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"B.J. Habibie : Sebuah Biografi\" ke koleksi favorit Anda.', 'info', 0, '2026-01-27 02:11:42', '2026-01-27 02:11:42'),
(13, 4, 4, 'Buku Ditambahkan ke Favorit', 'Anda telah menambahkan \"The Psychology of Money\" ke koleksi favorit Anda.', 'info', 0, '2026-01-27 02:17:21', '2026-01-27 02:17:21'),
(14, 4, 4, 'Peminjaman Berhasil', 'Anda telah meminjam buku \"The Psychology of Money\". Harap dikembalikan sebelum tanggal 03/02/2026.', 'borrow', 0, '2026-01-27 02:17:32', '2026-01-27 02:17:32'),
(15, 4, 4, 'Peminjaman Berhasil', 'Anda telah meminjam buku \"Sebuah Seni Untuk Bersikap Bodoamat\". Harap dikembalikan sebelum tanggal 03/02/2026.', 'borrow', 0, '2026-01-27 02:17:43', '2026-01-27 02:17:43'),
(16, 4, 4, 'Permintaan Pengembalian Dikirim', 'Permintaan pengembalian untuk buku \"The Psychology of Money\" menunggu konfirmasi admin.', 'return_request', 0, '2026-01-27 03:41:36', '2026-01-27 03:41:36');

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
  `founded_year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `slug`, `status`, `activation_code`, `created_at`, `email`, `phone`, `address`, `description`, `logo`, `profile_picture`, `npsn`, `website`, `photo_path`, `founded_year`) VALUES
(3, 'SMK BINA MANDIRI MULTIMEDIA', 'smk-bina-mandiri-multimedia', 'pending', NULL, '2026-01-26 02:40:51', 'updated@example.com', '082-9999999', 'Jl. Updated No. 999', NULL, NULL, NULL, 'TEST001', 'https://updated.com', NULL, 2020),
(4, 'AUSTRALIA INDEPENDENTS SCHOOL', 'australia-independents-school', 'pending', NULL, '2026-01-26 02:42:13', 'australiaindependentschool@sch.id', NULL, NULL, NULL, NULL, NULL, '2344242', NULL, 'public/uploads/school-photos/school_1769399275_6976e3ebe6757.jpg', 2023),
(5, 'sdfsdf', 'sdfsdf', 'pending', NULL, '2026-01-26 04:37:04', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, 'sdfsdfasdasd', 'sdfsdfasdasd', 'pending', NULL, '2026-01-26 04:39:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'sdfbhsd', 'sdfbhsd', 'pending', NULL, '2026-01-26 04:42:48', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'hdgdfg', 'hdgdfg', 'pending', NULL, '2026-01-26 04:44:45', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
(2, 4, 'dark', NULL, NULL, '2026-01-26 02:43:32', '2026-01-27 03:56:30');

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
(4, 'Anjali Saputra', '0094234', 'XI', 'Rekayasa Perangkat Lunak', '2008-01-17', 'L', 'Limus', 'anjalisaputra@gmail.com', '089234234', 'uploads/siswa/siswa_4_1769479766_69781e562270d.webp', '2026-01-26 03:07:53', '2026-01-27 02:09:26');

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
  `role` enum('admin','librarian','student') DEFAULT 'librarian',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `nisn`, `password`, `verification_code`, `code_expires_at`, `is_verified`, `verified_at`, `role`, `created_at`) VALUES
(2, 3, 'Gani', 'gani@sch.id', NULL, '$2y$10$hs.V41Tqx9p1A67h9wW4zeF6eXGIAIibCds1ELo.P8KoOcwmlWWXm', NULL, '2026-01-25 20:55:51', 1, '2026-01-26 02:41:02', 'admin', '2026-01-26 02:40:51'),
(3, 4, 'Budi', 'australiaindependentsschool@sch.id', NULL, '$2y$10$YYkXfCuDmZWDCEymXPClM.viULRMLWKBxk70BmOxepED0GMNeOWiS', NULL, '2026-01-25 20:57:13', 1, '2026-01-26 02:42:24', 'admin', '2026-01-26 02:42:14'),
(4, 4, 'Anjali Saputra', 'anjalisaputra@gmail.com', '0094234', '$2y$10$0yfyoUtwXaZKiUjRe/VWd.e9Cv/8N5NiqhJcnlSsDm/aaay7p/0DC', NULL, NULL, 0, NULL, 'student', '2026-01-26 03:06:14'),
(5, 5, 'Gani', 'sdfdf@sch.id', NULL, '$2y$10$y5RgsJjO.nbQ3XXqFeQ4X.5zkiCKKAXEZnH.EhVXPdyvOPc/.MRDm', NULL, '2026-01-25 22:52:04', 1, '2026-01-26 04:37:14', 'admin', '2026-01-26 04:37:04'),
(6, 7, 'sdfsdfasdasd', 'sdfsdfsf@sch.id', NULL, '$2y$10$qTK44/muE8jMTShecEdkZuPoFN3Kh9dDnc80qmzQo1bLNL6zgQ3ua', NULL, '2026-01-25 22:54:59', 1, '2026-01-26 04:40:09', 'admin', '2026-01-26 04:39:59'),
(7, 8, 'sghdfgdf', 'sdsdfsfdf@sch.id', NULL, '$2y$10$E3CEHA.8I4ICe1cYR7hdve6bfeEtSuhXHjrf4q.D.Ux9h.QyZpaQG', NULL, '2026-01-25 22:57:48', 1, '2026-01-26 04:42:57', 'admin', '2026-01-26 04:42:48'),
(8, 9, 'ertert', 'hgdfgdfg@sch.id', NULL, '$2y$10$kIJvRguAWIEKu6XrYqSjLOM8SRIDa0Tgz5PxDcTD1Lkdx7D0QpIpW', NULL, NULL, 1, '2026-01-26 04:44:53', 'admin', '2026-01-26 04:44:45'),
(9, 4, 'Surya', 'surz@gmail.com', '000000', '$2y$10$pCjvGGyhUHrozsbvjWHwAebllSThpZ1VCIGvhwf7l9YbOq.Kchbwq', NULL, NULL, 0, NULL, 'student', '2026-01-27 03:56:53');

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `book_damage_fines`
--
ALTER TABLE `book_damage_fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `borrows`
--
ALTER TABLE `borrows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `school_themes`
--
ALTER TABLE `school_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

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

-- --------------------------------------------------------

--
-- Table structure for table `barcode_sessions`
--

CREATE TABLE `barcode_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `barcode_sessions`
--
ALTER TABLE `barcode_sessions`
  ADD CONSTRAINT `barcode_sessions_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `barcode_sessions_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL;

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
