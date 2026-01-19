-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 19, 2026 at 08:01 AM
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
(10, 2, 'Bedebah Di Ujung Tanduk', 'Tere Liye', '123993002', 'Fiksi', 10, '2026-01-15 06:09:41', '1A', 3, NULL),
(18, 7, 'the art of stoicsm', 'andora ', '598467', 'Non-Fiksi', 1, '2026-01-19 03:24:18', '3', 2, 'book_1768793058_696da3e2b0ead.jpg'),
(19, 7, 'pyshcology of money', 'morgan housel', '4523657568', 'Non-Fiksi', 1, '2026-01-19 03:31:52', '4', 2, 'book_1768793512_696da5a8af9d1.jpg'),
(20, 7, 'selamat tinggal', 'Tere liye', '47906879478', 'Lainnya', 1, '2026-01-19 03:34:48', '5', 1, 'book_1768793688_696da6586b752.jpg'),
(21, 7, 'bicara itu ada seninya', 'oh su hyang', '9589646', 'Seni & Budaya', 1, '2026-01-19 03:35:35', '4', 3, 'book_1768793735_696da687805b4.jpg'),
(22, 7, 'belajar coding itu penting di era revousi industri', 'yeni mulayani', '6974854', 'Teknologi', 1, '2026-01-19 03:38:49', '2', 2, 'book_1768793929_696da7499f7cc.jpg'),
(23, 7, 'belajar coding membuat program', 'someone', '47568673', 'Teknologi', 1, '2026-01-19 03:39:41', '1', 4, 'book_1768793981_696da77d831ab.jpg');

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
(5, 2, 10, 2, '2026-01-15 13:15:55', '2026-01-15 00:00:00', NULL, 'overdue'),
(6, 2, 10, 1, '2026-01-15 13:16:09', '2026-01-16 00:00:00', NULL, 'borrowed'),
(11, 7, 18, 3, '2026-01-19 10:51:43', '2026-01-20 00:00:00', NULL, 'borrowed');

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
  `password` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `school_id`, `name`, `email`, `member_no`, `password`, `status`, `created_at`) VALUES
(1, 2, 'Anjali Saputra', 'anjalisaputra@gmail.com', '081292593620', NULL, 'active', '2026-01-12 07:45:59'),
(2, 2, 'surya ali rafsanjani', 'uya4767@gmail.com', '085891129163', NULL, 'active', '2026-01-13 01:56:41'),
(3, 7, 'Uya', 'uya4767@gmail.com', '0853748529', NULL, 'active', '2026-01-19 01:35:51'),
(4, 7, 'anjali', 'ada@gmail.com', '4675965903', NULL, 'active', '2026-01-19 01:36:00');

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
(2, 2, 'orange', NULL, NULL, '2026-01-15 03:04:29', '2026-01-15 07:50:13'),
(4, 5, 'indigo', NULL, NULL, '2026-01-15 03:05:01', '2026-01-15 04:14:18'),
(7, 4, 'purple', '{\"color-text\":\"#6b21a8\",\"color-muted\":\"#c084fc\",\"color-accent\":\"#d946ef\",\"color-danger\":\"#dc2626\",\"color-success\":\"#a855f7\",\"color-border\":\"#0000FF\"}', '{\"font-family\":\"Merriweather\",\"font-weight\":\"900\"}', '2026-01-15 03:07:50', '2026-01-15 03:13:44'),
(18, 7, 'purple', NULL, NULL, '2026-01-19 01:37:02', '2026-01-19 07:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','librarian') DEFAULT 'librarian',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 1, 'Admin Sekolah', 'admin@contoh.sch.id', '$2y$10$PLACEHOLDER_HASH', 'admin', '2026-01-12 06:24:19'),
(2, 2, 'Anjali', 'anjalisaputra@gmail.com', '$2y$10$w0d2n2raeoL8FioSrPh/0esCDb.i6wk2ZZDjO3Ibqx2lquGn/Zr66', 'admin', '2026-01-12 06:44:08'),
(3, 4, 'surya', 'uya47467@gmail.com', '$2y$10$Fw9bDgKPX7Vp.F6xXBbCQuqRKkEc5zuJ4zGPHCuJjKZfGlz8ziTuu', 'admin', '2026-01-13 01:53:13'),
(4, 5, 'saya', 'saya@gmail.com', '$2y$10$doKWQAmV8KM5GLIUC5lHB.nk6MbiubNWq32EWDZohp8BR1mroFblm', 'admin', '2026-01-13 06:47:10'),
(5, 7, 'someone', 'uya4767@gmail.com', '$2y$10$K7P1nbWS6EqrF69R39PZ5udKqxckTUMDnUDftvP2UmJI.Cvbhfs7y', 'admin', '2026-01-19 01:33:26');

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
  ADD KEY `idx_members_school_no` (`school_id`,`member_no`),
  ADD KEY `idx_members_school_status` (`school_id`,`status`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_users_school_email` (`school_id`,`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
