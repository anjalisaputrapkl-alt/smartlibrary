<?php
/**
 * Student Photo Upload API
 * Handles photo uploads from student card page
 */

// No output before this
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set JSON header FIRST
header('Content-Type: application/json; charset=utf-8');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check auth
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Tidak terautentikasi']));
}

try {
    // Initialize database with error handling
    try {
        $pdo = require __DIR__ . '/../../src/db.php';
    } catch (Exception $dbErr) {
        error_log("DB Connection Error: " . $dbErr->getMessage());
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'Database connection error']));
    }
    
    $userId = (int)$_SESSION['user']['id'];
    $action = $_POST['action'] ?? '';
    
    // Handle photo upload
    if ($action === 'upload_photo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Check file
        if (!isset($_FILES['photo'])) {
            http_response_code(400);
            exit(json_encode(['success' => false, 'message' => 'Tidak ada file yang diunggah']));
        }
        
        $file = $_FILES['photo'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("Upload error code: " . $file['error']);
            http_response_code(400);
            exit(json_encode(['success' => false, 'message' => 'Upload error code ' . $file['error']]));
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            http_response_code(400);
            exit(json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (max 5MB)']));
        }
        
        // Validate MIME type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        error_log("File MIME type: " . $mime_type);
        
        if (!in_array($mime_type, $allowed_types)) {
            http_response_code(400);
            exit(json_encode(['success' => false, 'message' => 'Format file harus JPG, PNG, atau WEBP (ditemukan: ' . $mime_type . ')']));
        }
        
        // Create upload directory
        $upload_dir = __DIR__ . '/../../uploads/siswa';
        if (!is_dir($upload_dir)) {
            error_log("Creating upload directory: " . $upload_dir);
            if (!@mkdir($upload_dir, 0755, true)) {
                error_log("Failed to create upload directory: " . $upload_dir);
                http_response_code(500);
                exit(json_encode(['success' => false, 'message' => 'Gagal membuat folder upload']));
            }
        }
        
        // Check if writable
        if (!is_writable($upload_dir)) {
            error_log("Upload directory not writable: " . $upload_dir);
            http_response_code(500);
            exit(json_encode(['success' => false, 'message' => 'Folder upload tidak dapat ditulis']));
        }
        
        // Generate filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'siswa_' . $userId . '_' . time() . '_' . uniqid() . '.' . strtolower($ext);
        $filepath = $upload_dir . '/' . $filename;
        
        error_log("Moving file to: " . $filepath);
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("Failed to move uploaded file");
            http_response_code(500);
            exit(json_encode(['success' => false, 'message' => 'Gagal menyimpan file ke server']));
        }
        
        error_log("File saved successfully to: " . $filepath);
        
        // Update database (store DB path without traversal)
        $photo_db_path = 'uploads/siswa/' . $filename;
        // Public path for browser (from public/ directory this page is served)
        $photo_public_path = '../uploads/siswa/' . $filename;
        
        // Update siswa table (store DB-facing path). Use id_siswa (primary key matching users.id)
        try {
            $stmt = $pdo->prepare("UPDATE siswa SET foto = ?, updated_at = NOW() WHERE id_siswa = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed");
            }
            $result = $stmt->execute([$photo_db_path, $userId]);
            // If updated, also update session so pages using session show the new foto immediately
            if ($result) {
                $_SESSION['user']['foto'] = $photo_db_path;
            }
            error_log("Updated siswa table: " . ($result ? 'success' : 'no rows updated'));
        } catch (Exception $e) {
            error_log("Update siswa error: " . $e->getMessage());
        }
        
        // Return success
        http_response_code(200);
        exit(json_encode([
            'success' => true,
            // public path is for browser usage from /public/ pages
            'path' => $photo_public_path,
            'db_path' => $photo_db_path,
            'message' => 'Foto berhasil diperbarui'
        ]));
    }
    
    // Default error
    error_log("Invalid action: " . $action . ", method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Action tidak valid']));
    
} catch (Exception $e) {
    error_log("Upload photo error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Server error']));
}
