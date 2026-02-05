<?php
/**
 * Email Helper - Mengirim email untuk verifikasi
 */

function sendVerificationEmail($recipient_email, $school_name, $admin_name, $verification_code)
{
    $subject = "Verifikasi Email - Pendaftaran Perpustakaan Digital";

    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9fafb; border-radius: 10px; }
            .header { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 30px; border-radius: 10px 10px 0 0; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { background: white; padding: 30px; border-radius: 0 0 10px 10px; }
            .verification-code { background: #f3f4f6; border: 2px solid #2563eb; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .code { font-size: 36px; font-weight: bold; color: #2563eb; letter-spacing: 4px; font-family: 'Courier New', monospace; }
            .info { background: #eff6ff; border-left: 4px solid #2563eb; padding: 15px; margin: 20px 0; border-radius: 4px; }
            .footer { color: #6b7280; font-size: 12px; text-align: center; margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 20px; }
            .btn { display: inline-block; padding: 12px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>✓ Verifikasi Email</h1>
                <p>Pendaftaran Perpustakaan Digital</p>
            </div>
            
            <div class='content'>
                <p>Halo <strong>{$admin_name}</strong>,</p>
                
                <p>Terima kasih telah mendaftarkan <strong>{$school_name}</strong> di Sistem Perpustakaan Digital kami!</p>
                
                <p>Untuk mengaktifkan akun Anda, silakan masukkan kode verifikasi di bawah ini:</p>
                
                <div class='verification-code'>
                    <div class='code'>{$verification_code}</div>
                    <p style='margin: 10px 0 0 0; color: #6b7280; font-size: 13px;'>Kode ini berlaku selama 15 menit</p>
                </div>
                
                <div class='info'>
                    <strong>⚠️ Penting:</strong>
                    <ul style='margin: 10px 0; padding-left: 20px;'>
                        <li>Jangan bagikan kode ini kepada siapa pun</li>
                        <li>Kode verifikasi berlaku 15 menit dari email ini dikirim</li>
                        <li>Jika Anda tidak mendaftar, abaikan email ini</li>
                    </ul>
                </div>
                
                <p style='color: #6b7280;'>Dengan verifikasi ini, akun admin Anda akan segera aktif dan siap digunakan untuk mengelola perpustakaan sekolah.</p>
                
                <p>Pertanyaan? Hubungi tim support kami di support@perpustakaan.edu</p>
            </div>
            
            <div class='footer'>
                <p>© 2026 Sistem Perpustakaan Digital Indonesia. Semua hak dilindungi.</p>
                <p>Email ini dikirim karena ada permintaan verifikasi akun. Jika ini bukan Anda, abaikan email ini.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@perpustakaan.edu" . "\r\n";

    // For testing/development, we can log the email instead of actually sending
    // In production, use: return mail($recipient_email, $subject, $message, $headers);

    // Log the email to a file for debugging
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/emails.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "\n[{$timestamp}] Email to: {$recipient_email}\nSubject: {$subject}\nCode: {$verification_code}\n" . str_repeat('=', 80);
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Try to send email, but don't fail if it doesn't work
    $mail_sent = @mail($recipient_email, $subject, $message, $headers);

    // Return true if we successfully logged it, even if email sending failed
    return true;
}

/**
 * Generate kode verifikasi 6 digit
 */
function generateVerificationCode()
{
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Validasi kode verifikasi (check expiry)
 * Kode berlaku 15 menit (900 detik)
 */
function isVerificationCodeExpired($created_at, $expiry_minutes = 15)
{
    $created = strtotime($created_at);
    $expiry = $created + ($expiry_minutes * 60);
    return time() > $expiry;
}

/**
 * Kirim email notifikasi umum ke siswa
 */
function sendNotificationEmail($recipient_email, $subject, $title, $message) {
    // Log the notification to a file for debugging
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_file = $log_dir . '/notifications.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "\n[{$timestamp}] Notification to: {$recipient_email}\nSubject: {$subject}\nTitle: {$title}\nMessage: {$message}\n" . str_repeat('=', 80);
    file_put_contents($log_file, $log_entry, FILE_APPEND);

    // Try to send email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: noreply@perpustakaan.edu" . "\r\n";

    // In production, use: return mail($recipient_email, $subject, $message, $headers);
    return @mail($recipient_email, $subject, $message, $headers);
}

