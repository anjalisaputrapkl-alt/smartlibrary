-- perpustakaan-online: MySQL schema
-- Create database (change charset/collation as needed)
CREATE DATABASE IF NOT EXISTS perpustakaan_online CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE perpustakaan_online;

-- Schools (tenants)
CREATE TABLE IF NOT EXISTS schools (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users (admin/librarian) --- user is associated to a school
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','librarian') DEFAULT 'librarian',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Books (per school)
CREATE TABLE IF NOT EXISTS books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255),
  isbn VARCHAR(100),
  copies INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Members (per school)
CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  member_no VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Borrows
CREATE TABLE IF NOT EXISTS borrows (
  id INT AUTO_INCREMENT PRIMARY KEY,
  school_id INT NOT NULL,
  book_id INT NOT NULL,
  member_id INT NOT NULL,
  borrowed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  due_at DATETIME,
  returned_at DATETIME NULL,
  status ENUM('borrowed','returned','overdue') DEFAULT 'borrowed',
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
  FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample seed: one school and admin (password is 'admin123' - hashed should be set during install)
INSERT INTO schools (name, slug) VALUES ('Contoh Sekolah', 'contoh-sekolah');

-- NOTE: insert a real hashed password during setup; here we leave a placeholder
INSERT INTO users (school_id, name, email, password, role) VALUES (1, 'Admin Sekolah', 'admin@contoh.sch.id', '$2y$10$PLACEHOLDER_HASH', 'admin');
