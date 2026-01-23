<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$pageTitle = 'Bantuan';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bantuan - Perpustakaan Digital</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/school-profile.css">
    <link rel="stylesheet" href="../assets/css/help.css">
</head>

<body>
    <!-- Navigation Sidebar -->
    <?php include 'partials/student-sidebar.php'; ?>

    <!-- Hamburger Menu Button -->
    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
        <iconify-icon icon="mdi:menu" width="24" height="24"></iconify-icon>
    </button>

    <!-- Global Student Header -->
    <?php include 'partials/student-header.php'; ?>

    <!-- Main Container -->
    <div class="container-main">
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <iconify-icon icon="mdi:help-circle" width="28" height="28"></iconify-icon>
                Bantuan & Panduan
            </h1>
            <p>Temukan jawaban untuk pertanyaan umum dan pelajari cara menggunakan perpustakaan digital kami</p>
        </div>

        <!-- Features Section -->
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-card-icon">
                    <iconify-icon icon="mdi:book-open-variant"></iconify-icon>
                </div>
                <h3>Jelajahi Koleksi</h3>
                <p>Cari dan temukan ribuan buku dari berbagai kategori yang tersedia di perpustakaan kami</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon">
                    <iconify-icon icon="mdi:calendar-check"></iconify-icon>
                </div>
                <h3>Kelola Peminjaman</h3>
                <p>Pinjam, kembalikan, dan pantau durasi peminjaman buku dengan mudah</p>
            </div>
            <div class="feature-card">
                <div class="feature-card-icon">
                    <iconify-icon icon="mdi:heart"></iconify-icon>
                </div>
                <h3>Favorit & Wishlist</h3>
                <p>Tandai buku favorit Anda dan buat daftar buku yang ingin dipinjam nanti</p>
            </div>
        </div>

        <!-- Getting Started Section -->
        <div class="steps-section">
            <h2 style="margin: 0 0 24px 0;">
                Memulai
            </h2>
            <div class="steps-container">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4 class="step-title">Jelajahi Dashboard</h4>
                    <p class="step-description">Mulai dari dashboard untuk melihat rekomendasi buku dan statistik
                        peminjaman Anda</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4 class="step-title">Cari Buku</h4>
                    <p class="step-description">Gunakan kolom pencarian untuk menemukan buku berdasarkan judul, penulis,
                        atau kategori</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4 class="step-title">Pinjam Buku</h4>
                    <p class="step-description">Pilih buku yang ingin dipinjam dan ikuti proses konfirmasi peminjaman
                    </p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h4 class="step-title">Pantau Peminjaman</h4>
                    <p class="step-description">Lihat riwayat peminjaman dan tanggal pengembalian di halaman riwayat</p>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-container">
            <h2
                style="padding: 24px 24px 16px 24px; font-size: 20px; margin: 0; display: flex; align-items: center; gap: 14px;">
                <iconify-icon icon="mdi:frequently-asked-questions"
                    style="width: 26px; height: 26px; color: var(--accent);"></iconify-icon>
                Pertanyaan yang Sering Diajukan
            </h2>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Berapa lama saya dapat meminjam buku?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Durasi peminjaman standar adalah <strong>7 hari</strong> dari tanggal peminjaman. Anda dapat
                            melihat tanggal pengembalian yang tepat di halaman riwayat peminjaman. Jika Anda ingin
                            memperpanjang peminjaman, hubungi pustakawan melalui halaman bantuan atau datang langsung ke
                            perpustakaan.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana cara mengembalikan buku?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Ada beberapa cara untuk mengembalikan buku:</p>
                        <ul>
                            <li><strong>Secara langsung:</strong> Bawa buku ke perpustakaan dan serahkan kepada petugas
                            </li>
                            <li><strong>Melalui aplikasi:</strong> Tandai sebagai dikembalikan di halaman riwayat
                                peminjaman (jika tersedia)</li>
                            <li><strong>Informasi:</strong> Pastikan buku dalam kondisi baik dan kembalikan sesuai
                                tanggal yang ditentukan</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Apakah ada biaya denda jika saya terlambat mengembalikan?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Ya, terdapat denda keterlambatan untuk buku yang tidak dikembalikan tepat waktu. Besarnya
                            denda dapat dilihat pada peraturan perpustakaan atau dengan menghubungi petugas. Anda akan
                            menerima notifikasi otomatis jika peminjaman Anda mendekati batas waktu pengembalian.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Berapa jumlah maksimal buku yang dapat saya pinjam?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Jumlah maksimal buku yang dapat dipinjam secara bersamaan adalah <strong>5 buku</strong>.
                            Setelah mengembalikan salah satu buku, Anda dapat meminjam buku lainnya. Jika ada kebutuhan
                            khusus untuk meminjam lebih banyak, silakan diskusikan dengan petugas perpustakaan.</p>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana cara menambahkan buku ke favorit?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Untuk menambahkan buku ke favorit:</p>
                        <ul>
                            <li>Buka detail buku yang ingin disimpan</li>
                            <li>Klik tombol hati (<iconify-icon icon="mdi:heart-outline"
                                    style="width: 16px; height: 16px;"></iconify-icon>) di halaman detail</li>
                            <li>Buku akan ditambahkan ke daftar favorit Anda</li>
                            <li>Akses daftar favorit dari menu sidebar di halaman "Koleksi Favorit"</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana cara mengubah profil saya?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Untuk mengubah profil Anda:</p>
                        <ul>
                            <li>Klik menu "Profil Saya" di sidebar navigasi</li>
                            <li>Pilih tombol "Edit Profil"</li>
                            <li>Ubah informasi yang diinginkan (nama, email, foto, dll)</li>
                            <li>Klik "Simpan Perubahan" untuk menyimpan</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Apakah saya bisa mengubah tema tampilan?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Ya, Anda dapat mengubah tema tampilan aplikasi. Untuk melakukannya:</p>
                        <ul>
                            <li>Buka halaman "Pengaturan" dari sidebar navigasi</li>
                            <li>Cari opsi "Preferensi Tampilan" atau "Tema"</li>
                            <li>Pilih tema yang Anda inginkan (Terang/Gelap)</li>
                            <li>Perubahan akan diterapkan secara otomatis</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <div class="faq-question-text">
                        <iconify-icon icon="mdi:help-circle-outline"></iconify-icon>
                        <span>Bagaimana saya mendapat notifikasi reminder?</span>
                    </div>
                    <div class="faq-toggle">
                        <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                    </div>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        <p>Sistem akan otomatis mengirimkan notifikasi untuk:</p>
                        <ul>
                            <li><strong>Reminder pengembalian:</strong> Dikirim 2-3 hari sebelum batas waktu
                                pengembalian</li>
                            <li><strong>Notifikasi keterlambatan:</strong> Dikirim jika Anda tidak mengembalikan buku
                                tepat waktu</li>
                            <li><strong>Notifikasi sistem:</strong> Untuk update penting dan informasi perpustakaan</li>
                            <li>Anda dapat melihat semua notifikasi di halaman "Notifikasi" atau di icon bell di header
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips Section -->
        <div class="help-section">
            <h2>
                <iconify-icon icon="mdi:lightbulb-on"></iconify-icon>
                Tips & Trik
            </h2>
            <div class="tips-container">
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Gunakan Filter Pencarian</div>
                        <div class="tip-text">Manfaatkan filter kategori dan opsi pengurutan untuk menemukan buku yang
                            Anda cari dengan lebih cepat</div>
                    </div>
                </div>
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Baca Detail Buku</div>
                        <div class="tip-text">Selalu baca deskripsi dan review buku sebelum meminjam untuk memastikan
                            sesuai dengan minat Anda</div>
                    </div>
                </div>
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Perhatikan Batas Waktu</div>
                        <div class="tip-text">Catat tanggal pengembalian buku Anda untuk menghindari denda keterlambatan
                        </div>
                    </div>
                </div>
                <div class="tip">
                    <iconify-icon icon="mdi:star" class="tip-icon"></iconify-icon>
                    <div class="tip-content">
                        <div class="tip-title">Jaga Kondisi Buku</div>
                        <div class="tip-text">Perlakukan buku dengan hati-hati untuk menjaga kualitas dan memastikan
                            tersedia untuk pembaca lainnya</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Need Help Section -->
        <div class="contact-section">
            <h2>Butuh Bantuan Lebih Lanjut?</h2>
            <p>Jika Anda tidak menemukan jawaban yang Anda cari, jangan ragu untuk menghubungi kami</p>
            <div class="contact-buttons">
                <a href="mailto:library@school.id" class="contact-btn">
                    <iconify-icon icon="mdi:email"></iconify-icon>
                    Email Kami
                </a>
                <a href="student-dashboard.php" class="contact-btn"
                    style="background: rgba(255,255,255,0.2); color: white; border: 1px solid white;">
                    <iconify-icon icon="mdi:home"></iconify-icon>
                    Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>

    <script src="../assets/js/help.js"></script>
</body>

</html>