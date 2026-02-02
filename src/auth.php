<?php
/**
 * Authentication Helper Functions
 * Mulai session dan cek autentikasi
 */

// Mulai session jika belum
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 */
function isAuthenticated()
{
    return !empty($_SESSION['user']) && !empty($_SESSION['user']['id']);
}

/**
 * Dapatkan user yang sedang login
 */
function getAuthUser()
{
    return $_SESSION['user'] ?? null;
}

/**
 * Redirect ke login jika belum autentikasi
 * Safe header redirect
 */
function requireAuth()
{
    if (!isAuthenticated()) {
        // Redirect ke halaman login
        $loginUrl = '/perpustakaan-online/?login_required=1';
        header('Location: ' . $loginUrl, true, 302);
        exit;
    }
}

/**
 * Logout user
 */
function logout()
{
    session_destroy();
    // Redirect ke halaman index/home
    header('Location: /perpustakaan-online/index.php', true, 302);
    exit;
}

/**
 * Get the current user ID from session
 */
function getCurrentUserId()
{
    return $_SESSION['user']['id'] ?? null;
}

/**
 * Get the current school ID from session
 */
function getCurrentSchoolId()
{
    return $_SESSION['user']['school_id'] ?? null;
}
?>