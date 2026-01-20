-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 20, 2026 at 08:17 AM
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
(10, 2, 'Bedebah Di Ujung Tanduk', 'Tere Liye', '123993002', 'Fiksi', 10, '2026-01-15 06:09:41', '1A', 3, 'book_1768807416_696ddbf83888f.jpg'),
(18, 7, 'the art of stoicsm', 'andora ', '598467', 'Non-Fiksi', 1, '2026-01-19 03:24:18', '3', 2, 'book_1768793058_696da3e2b0ead.jpg'),
(19, 7, 'pyshcology of money', 'morgan housel', '4523657568', 'Non-Fiksi', 1, '2026-01-19 03:31:52', '4', 2, 'book_1768793512_696da5a8af9d1.jpg'),
(20, 7, 'selamat tinggal', 'Tere liye', '47906879478', 'Lainnya', 1, '2026-01-19 03:34:48', '5', 1, 'book_1768793688_696da6586b752.jpg'),
(21, 7, 'bicara itu ada seninya', 'oh su hyang', '9589646', 'Seni & Budaya', 1, '2026-01-19 03:35:35', '4', 3, 'book_1768793735_696da687805b4.jpg'),
(22, 7, 'belajar coding itu penting di era revousi industri', 'yeni mulayani', '6974854', 'Teknologi', 1, '2026-01-19 03:38:49', '2', 2, 'book_1768793929_696da7499f7cc.jpg'),
(23, 7, 'belajar coding membuat program', 'someone', '47568673', 'Teknologi', 1, '2026-01-19 03:39:41', '1', 4, 'book_1768793981_696da77d831ab.jpg'),
(24, 2, 'Madilog', 'Tan Malaka', '131234123', 'Non-Fiksi', 13, '2026-01-19 07:05:48', '1A', 2, 'book_1768807083_696ddaab3d431.jpeg'),
(25, 7, 'stoicsm', 'andora ', '63476', 'Non-Fiksi', 1, '2026-01-20 03:13:28', '2', 5, 'book_1768878808_696ef2d876f4a.jpg');

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
(1, 10, 'Damaged', 'rusaK', '2026-01-15 07:33:42'),
(3, 10, 'Good', 'ihihi', '2026-01-19 04:42:28');

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
  `status` enum('borrowed','returned','overdue') DEFAULT 'borrowed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrows`
--

INSERT INTO `borrows` (`id`, `school_id`, `book_id`, `member_id`, `borrowed_at`, `due_at`, `returned_at`, `status`) VALUES
(11, 7, 18, 3, '2026-01-19 10:51:43', '2026-01-20 00:00:00', NULL, 'overdue');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `member_no` varchar(100) DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `school_id`, `name`, `email`, `member_no`, `nisn`, `password`, `status`, `created_at`) VALUES
(3, 7, 'Uya', 'uya4767@gmail.com', '0853748529', NULL, NULL, 'active', '2026-01-19 01:35:51'),
(4, 7, 'anjali', 'ada@gmail.com', '4675965903', NULL, NULL, 'active', '2026-01-19 01:36:00'),
(5, 2, 'Anjali Saputra', 'anjalisaputra@gmail.com', '081292593620', '111111', NULL, 'active', '2026-01-19 07:37:05'),
(6, 2, 'Surya', 'saya@gmail.com', '085891129163', '222222', NULL, 'active', '2026-01-20 01:31:31');

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

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_siswa`, `judul`, `pesan`, `jenis_notifikasi`, `tanggal`, `status_baca`, `created_at`, `updated_at`) VALUES
(1, 1, 'Buku Telat Dikembalikan', 'Buku \"Clean Code\" belum dikembalikan. Tenggat: 2024-01-15. Denda: Rp 5.000/hari', 'telat', '2026-01-17 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(2, 1, 'Peringatan: Denda Diperoleh', 'Anda telah dikenakan denda sebesar Rp 15.000 untuk keterlambatan pengembalian buku', 'peringatan', '2026-01-15 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(3, 1, 'Notifikasi Pengembalian Buku', 'Jangan lupa mengembalikan buku \"Design Patterns\" sebelum tanggal 2024-01-20', 'pengembalian', '2026-01-19 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(4, 1, 'Informasi Terbaru', 'Perpustakaan akan ditutup pada tanggal 25 Januari untuk pemeliharaan sistem', 'info', '2026-01-20 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(5, 1, 'Peminjaman Berhasil', 'Anda berhasil meminjam buku \"Refactoring\" pada 2024-01-10', 'sukses', '2026-01-13 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(6, 1, 'Katalog Buku Baru', 'Ada 5 buku baru dalam katalog perpustakaan: \"Microservices\", \"Cloud Native\", dan lainnya', 'buku', '2026-01-18 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(7, 2, 'Buku Siap Diambil', 'Buku yang Anda pesan \"Introduction to Algorithms\" sudah tersedia di perpustakaan', 'info', '2026-01-20 10:06:06', 0, '2026-01-20 03:06:06', '2026-01-20 03:06:06'),
(8, 2, 'Peminjaman Berhasil', 'Anda berhasil meminjam 3 buku pada 2024-01-10', 'sukses', '2026-01-18 10:06:06', 1, '2026-01-20 03:06:06', '2026-01-20 03:06:06');

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
  `logo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `slug`, `status`, `activation_code`, `created_at`, `email`, `phone`, `address`, `description`, `logo`) VALUES
(1, 'Contoh Sekolah', 'contoh-sekolah', 'pending', NULL, '2026-01-12 06:24:19', NULL, NULL, NULL, NULL, NULL),
(2, 'SMK BINA MANDIRI MULTIMEDIA', 'smk-bina-mandiri-multimedia', 'active', NULL, '2026-01-12 06:44:08', NULL, NULL, NULL, NULL, NULL),
(4, 'smp menang 01', 'smp-menang-01', 'pending', NULL, '2026-01-13 01:53:13', NULL, NULL, NULL, NULL, NULL),
(5, 'smk ahay', 'smk-ahay', 'pending', NULL, '2026-01-13 06:47:10', NULL, NULL, NULL, NULL, NULL),
(7, 'smk bm3', 'smk-bm3', 'pending', NULL, '2026-01-19 01:33:26', NULL, NULL, NULL, NULL, NULL);

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
(1, 1, 'dark', '{\"color-text\":\"#ffffff\",\"color-muted\":\"#aabbcc\",\"color-accent\":\"#ff0000\",\"color-danger\":\"#dd0000\",\"color-success\":\"#00ff00\",\"color-border\":\"#0000ff\"}', '{\"font-family\":\"Georgia\",\"font-weight\":\"700\"}', '2026-01-15 03:04:29', '2026-01-15 03:24:34'),
(2, 2, 'blue', NULL, NULL, '2026-01-15 03:04:29', '2026-01-20 01:45:27'),
(4, 5, 'indigo', NULL, NULL, '2026-01-15 03:05:01', '2026-01-15 04:14:18'),
(7, 4, 'purple', '{\"color-text\":\"#6b21a8\",\"color-muted\":\"#c084fc\",\"color-accent\":\"#d946ef\",\"color-danger\":\"#dc2626\",\"color-success\":\"#a855f7\",\"color-border\":\"#0000FF\"}', '{\"font-family\":\"Merriweather\",\"font-weight\":\"900\"}', '2026-01-15 03:07:50', '2026-01-15 03:13:44'),
(18, 7, 'green', NULL, NULL, '2026-01-19 01:37:02', '2026-01-20 07:04:24');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
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

INSERT INTO `siswa` (`id_siswa`, `nama_lengkap`, `nis`, `nisn`, `kelas`, `jurusan`, `tanggal_lahir`, `jenis_kelamin`, `alamat`, `email`, `no_hp`, `foto`, `created_at`, `updated_at`) VALUES
(2, 'Anjali Saputra', '0001', '1234567890001', 'XII RPL', 'Rekayasa Perangkat Lunak', '2007-03-15', 'L', 'Jl. Merdeka No. 10, Jakarta', 'anjalisaputra@gmail.com', '081234567890', NULL, '2026-01-20 06:18:30', '2026-01-20 07:00:49'),
(4, 'Budi Santoso', '0004', '1234567890004', 'XI TKJ', 'Teknik Komputer Jaringan', '2008-05-20', 'L', 'Jl. Sudirman No. 25, Bandung', 'budisantoso@gmail.com', '082345678901', NULL, '2026-01-20 06:18:30', '2026-01-20 06:18:30'),
(5, 'Citra Dewi', '0005', '1234567890005', 'X TKR', 'Teknik Kendaraan Ringan', '2009-07-10', 'P', 'Jl. Ahmad Yani No. 50, Surabaya', 'citradewi@gmail.com', '083456789012', NULL, '2026-01-20 06:18:30', '2026-01-20 06:18:30'),
(7, 'Doni Hermanto', '0007', '1234567890007', 'XI RPL', 'Rekayasa Perangkat Lunak', '2007-11-25', 'L', 'Jl. Gatot Subroto No. 15, Medan', 'saya@gmail.com', '084567890123', NULL, '2026-01-20 06:18:30', '2026-01-20 06:18:30');

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
  `role` enum('admin','librarian','student') DEFAULT 'librarian',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `nisn`, `password`, `role`, `created_at`) VALUES
(1, 1, 'Admin Sekolah', 'admin@contoh.sch.id', NULL, '$2y$10$PLACEHOLDER_HASH', 'admin', '2026-01-12 06:24:19'),
(2, 2, 'Anjali', 'anjalisaputra@gmail.com', NULL, '$2y$10$w0d2n2raeoL8FioSrPh/0esCDb.i6wk2ZZDjO3Ibqx2lquGn/Zr66', 'admin', '2026-01-12 06:44:08'),
(3, 4, 'surya', 'uya47467@gmail.com', NULL, '$2y$10$Fw9bDgKPX7Vp.F6xXBbCQuqRKkEc5zuJ4zGPHCuJjKZfGlz8ziTuu', 'admin', '2026-01-13 01:53:13'),
(4, 5, 'saya', 'saya@gmail.com', NULL, '$2y$10$doKWQAmV8KM5GLIUC5lHB.nk6MbiubNWq32EWDZohp8BR1mroFblm', 'admin', '2026-01-13 06:47:10'),
(5, 7, 'someone', 'uya4767@gmail.com', NULL, '$2y$10$K7P1nbWS6EqrF69R39PZ5udKqxckTUMDnUDftvP2UmJI.Cvbhfs7y', 'admin', '2026-01-19 01:33:26'),
(6, 2, 'Anjali Saputra', 'anjalisaputra@gmail.com', '111111', '$2y$10$qAC3LyD.kq/jFmdqOVvOTu8OzkX6e8xLxfnBisb.hWoXYj0qOebKy', 'student', '2026-01-19 07:37:05'),
(7, 2, 'Surya', 'saya@gmail.com', '222222', '$2y$10$cN.EPRDjv3Us66bGdnZ5e.RDqFc7CnYAoh7Srfa1cm06Ey9546302', 'student', '2026-01-20 01:31:31');

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
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_members_school_no` (`school_id`,`member_no`),
  ADD KEY `idx_members_school_status` (`school_id`,`status`);

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
  ADD KEY `idx_schools_status` (`status`);

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
  ADD KEY `idx_nis` (`nis`),
  ADD KEY `idx_nisn` (`nisn`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `idx_users_school_email` (`school_id`,`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `book_maintenance`
--
ALTER TABLE `book_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `borrows`
--
ALTER TABLE `borrows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `school_themes`
--
ALTER TABLE `school_themes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

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
