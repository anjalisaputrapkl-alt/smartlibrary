<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Perpustakaan Digital — Akses Pengetahuan Modern</title>
  <link rel="stylesheet" href="assets/css/landing.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Merriweather:wght@700;900&display=swap"
    rel="stylesheet">
</head>

<body>

  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand">
        <div class="logo">📖</div>
        <div class="brand-text">
          <div class="site-title">Perpustakaan Digital</div>
          <div class="site-sub">Sistem Manajemen Sekolah</div>
        </div>
      </a>

      <nav class="main-nav">
        <a href="#solution">Solusi</a>
        <a href="#features">Fitur</a>
        <a href="#audience">Pengguna</a>
        <a href="#contact">Kontak</a>
      </nav>

      <div class="nav-right">
        <a href="#" onclick="openLoginModal(event)" class="nav-btn login">Login</a>
        <a href="#" onclick="openRegisterModal(event)" class="nav-btn register">Daftar</a>
      </div>

      <button class="nav-toggle" onclick="toggleNav()">☰</button>
    </div>
  </header>

  <main id="main">

    <!-- HERO -->
    <section class="hero">
      <div class="container hero-inner">
        <div class="hero-copy">
          <h1>Sistem Perpustakaan Digital untuk Semua Sekolah</h1>
          <p class="lede">
            Platform manajemen perpustakaan terintegrasi yang memudahkan sekolah Anda mengelola koleksi buku, anggota,
            dan peminjaman dalam satu sistem yang mudah digunakan.
          </p>
          <p class="hero-cta">
            <a href="#" onclick="openRegisterModal(event)" class="btn primary">Daftarkan Sekolah Anda</a>
            <a href="#" onclick="openLoginModal(event)" class="btn ghost">Masuk Sekarang</a>
          </p>
        </div>
        <div class="hero-visual">
          <img src="img/g1.jpg" alt="Dashboard sistem perpustakaan sekolah" />
        </div>
      </div>
    </section>

    <!-- PROBLEM -->
    <section class="section problem">
      <div class="container">
        <h2>Tantangan Manajemen Perpustakaan Sekolah</h2>
        <p class="microcopy">Banyak sekolah masih menghadapi kesulitan dalam mengelola perpustakaan secara efisien dan
          modern.</p>

        <div class="values-grid">
          <article class="value">
            <h3>📚 Pencatatan Manual</h3>
            <p>Proses pencatatan buku dan peminjaman masih dilakukan secara manual dan rentan kesalahan.</p>
          </article>
          <article class="value">
            <h3>⏱️ Waktu Hilang</h3>
            <p>Pencarian buku dan data anggota memakan waktu lama tanpa sistem digital yang tepat.</p>
          </article>
          <article class="value">
            <h3>📊 Laporan Sulit</h3>
            <p>Kesulitan membuat laporan dan analisis penggunaan perpustakaan untuk evaluasi.</p>
          </article>
          <article class="value">
            <h3>🔒 Data Tidak Aman</h3>
            <p>Data perpustakaan berisiko hilang atau tidak terorganisir dengan baik.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- SOLUTION -->
    <section id="solution" class="section solution">
      <div class="container split">
        <div class="col">
          <h2>Solusi Perpustakaan Digital Terintegrasi</h2>
          <p>
            Kami menyediakan sistem manajemen perpustakaan yang dirancang khusus untuk kebutuhan sekolah. Platform kami
            mengintegrasikan semua aspek operasional perpustakaan dalam satu dashboard yang intuitif dan mudah
            digunakan.
          </p>

          <ul class="story">
            <li>✔️ Kelola koleksi buku dengan mudah dan terstruktur</li>
            <li>✔️ Catat anggota perpustakaan secara digital</li>
            <li>✔️ Proses peminjaman dan pengembalian otomatis</li>
            <li>✔️ Laporan statistik dan analisis penggunaan real-time</li>
          </ul>
        </div>
        <div class="col">
          <img src="img/g2.jpg" class="section-img" alt="Dashboard sistem perpustakaan terintegrasi" />
        </div>
      </div>
    </section>

    <!-- STATS -->
    <section class="section stats">
      <div class="container values-grid">
        <article class="value">
          <h3>50+</h3>
          <p>Sekolah Terdaftar</p>
        </article>
        <article class="value">
          <h3>25.000+</h3>
          <p>Koleksi Buku</p>
        </article>
        <article class="value">
          <h3>15.000+</h3>
          <p>Pengguna Aktif</p>
        </article>
        <article class="value">
          <h3>99%</h3>
          <p>Uptime Sistem</p>
        </article>
      </div>
    </section>

    <!-- COLLECTIONS -->
    <section id="features" class="section preview">
      <div class="container">
        <h2>Fitur-Fitur Utama Sistem</h2>

        <div class="values-grid">
          <article class="value">
            <div class="feature-icon">📖</div>
            <h3>Manajemen Buku</h3>
            <p>Kelola koleksi buku dengan pencarian mudah, kategori, dan informasi lengkap setiap judul.</p>
          </article>
          <article class="value">
            <div class="feature-icon">👥</div>
            <h3>Manajemen Anggota</h3>
            <p>Pendaftaran anggota digital, tracking aktivitas, dan identitas terverifikasi.</p>
          </article>
          <article class="value">
            <div class="feature-icon">📤</div>
            <h3>Peminjaman & Pengembalian</h3>
            <p>Proses peminjaman cepat dengan notifikasi otomatis dan manajemen tenggat waktu.</p>
          </article>
          <article class="value">
            <div class="feature-icon">📊</div>
            <h3>Laporan & Analitik</h3>
            <p>Dashboard interaktif dengan laporan statistik penggunaan perpustakaan real-time.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- AUDIENCE -->
    <section id="audience" class="section audience">
      <div class="container">
        <h2>Untuk Siapa Sistem Ini?</h2>
        <div class="audience-grid">
          <div class="aud-item">
            <div class="aud-icon">🏫</div>
            <div>Admin Sekolah</div>
          </div>
          <div class="aud-item">
            <div class="aud-icon">📚</div>
            <div>Pustakawan</div>
          </div>
          <div class="aud-item">
            <div class="aud-icon">👨‍🏫</div>
            <div>Guru & Dosen</div>
          </div>
          <div class="aud-item">
            <div class="aud-icon">👨‍🎓</div>
            <div>Siswa & Mahasiswa</div>
          </div>
          <div class="aud-item">
            <div class="aud-icon">🎓</div>
            <div>Institusi Pendidikan</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CLOSING CTA -->
    <section class="section closing">
      <div class="container">
        <h2>Transformasikan Perpustakaan Sekolah Anda</h2>
        <p>Bergabunglah dengan 50+ sekolah yang telah mempercayai sistem kami untuk mengelola perpustakaan secara
          modern, efisien, dan terintegrasi.</p>
        <div style="margin:32px 0;">
          <a href="#" onclick="openRegisterModal(event)" class="btn primary">Daftarkan Sekarang</a>
          <a href="#contact" class="btn ghost">Hubungi Kami</a>
        </div>
        <p class="closing-meta">Gratis • Setup Otomatis • Support 24/7</p>
      </div>
    </section>

    <!-- FOOTER -->
    <footer id="contact" class="site-footer" style="padding:0 !important;">
      <div style="padding:60px 40px;background:#0F1724;width:100%;">
        <div style="max-width:1400px;margin:0 auto;">
          <!-- Newsletter Section -->
          <div
            style="background:rgba(255,255,255,.05);border-radius:12px;padding:32px 40px;margin-bottom:60px;border:1px solid rgba(255,255,255,.1);">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:40px;">
              <div style="flex:1;">
                <h3 style="color:#fff;margin:0 0 8px 0;font-size:16px;font-weight:700;">Dapatkan Update Terbaru</h3>
                <p style="color:rgba(255,255,255,.6);margin:0;font-size:13px;">Berlangganan untuk tips manajemen
                  perpustakaan dan update fitur terbaru.</p>
              </div>
              <div style="display:flex;gap:8px;white-space:nowrap;">
                <input type="email" placeholder="Email Anda"
                  style="padding:10px 14px;border:1px solid rgba(255,255,255,.2);border-radius:6px;background:rgba(255,255,255,.05);color:#fff;font-size:13px;min-width:220px;" />
                <button
                  style="padding:10px 24px;background:#fff;color:var(--accent);border:none;border-radius:6px;font-weight:600;cursor:pointer;font-size:13px;transition:.2s ease;"
                  onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">Subscribe</button>
              </div>
            </div>
          </div>

          <!-- Main Footer Grid -->
          <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1.2fr;gap:50px;margin-bottom:60px;">
            <!-- Brand Column -->
            <div>
              <h4 style="color:#fff;margin:0 0 12px 0;font-size:15px;font-weight:800;">Perpustakaan Digital</h4>
              <p style="color:rgba(255,255,255,.6);margin:0;font-size:13px;line-height:1.6;">Solusi manajemen
                perpustakaan modern untuk institusi pendidikan Indonesia.</p>
            </div>

            <!-- Produk Column -->
            <div>
              <h4
                style="color:#fff;margin:0 0 16px 0;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                Produk</h4>
              <ul style="list-style:none;margin:0;padding:0;">
                <li style="margin-bottom:10px;"><a href="#features"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Fitur
                    Utama</a></li>
                <li style="margin-bottom:10px;"><a href="#solution"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Solusi</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#audience"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Pengguna</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Harga</a>
                </li>
                <li><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">API</a>
                </li>
              </ul>
            </div>

            <!-- Perusahaan Column -->
            <div>
              <h4
                style="color:#fff;margin:0 0 16px 0;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                Perusahaan</h4>
              <ul style="list-style:none;margin:0;padding:0;">
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Tentang</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Blog</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Karir</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Partner</a>
                </li>
                <li><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Testimonial</a>
                </li>
              </ul>
            </div>

            <!-- Dukungan Column -->
            <div>
              <h4
                style="color:#fff;margin:0 0 16px 0;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                Dukungan</h4>
              <ul style="list-style:none;margin:0;padding:0;">
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Bantuan</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Dokumentasi</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Status</a>
                </li>
                <li style="margin-bottom:10px;"><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">FAQ</a>
                </li>
                <li><a href="#"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">Support</a>
                </li>
              </ul>
            </div>

            <!-- Kontak Column -->
            <div>
              <h4
                style="color:#fff;margin:0 0 16px 0;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">
                Kontak</h4>
              <ul style="list-style:none;margin:0;padding:0;">
                <li style="margin-bottom:10px;"><a href="mailto:support@perpustakaan.edu"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">📧
                    support@perpustakaan.edu</a></li>
                <li style="margin-bottom:10px;"><a href="tel:+622745551234"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">📞
                    (0274) 555-1234</a></li>
                <li style="color:rgba(255,255,255,.7);font-size:13px;">🕐 Senin–Jumat 09:00–17:00</li>
              </ul>
            </div>
          </div>

          <!-- Social & Compliance -->
          <div
            style="padding:50px 0;border-top:1px solid rgba(255,255,255,.1);border-bottom:1px solid rgba(255,255,255,.1);display:grid;grid-template-columns:1fr 1fr 1fr;gap:50px;">
            <!-- Social Media -->
            <div>
              <h5
                style="font-weight:700;color:#fff;margin:0 0 16px 0;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;">
                Ikuti Kami</h5>
              <div style="display:flex;gap:12px;align-items:center;justify-content:center;">
                <a href="#"
                  style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:8px;color:#fff;text-decoration:none;font-size:18px;transition:.2s ease;font-weight:600;"
                  title="Facebook" onmouseover="this.style.background='rgba(255,255,255,.15)'"
                  onmouseout="this.style.background='rgba(255,255,255,.1)'">f</a>
                <a href="#"
                  style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:8px;color:#fff;text-decoration:none;font-size:18px;transition:.2s ease;font-weight:600;"
                  title="Twitter" onmouseover="this.style.background='rgba(255,255,255,.15)'"
                  onmouseout="this.style.background='rgba(255,255,255,.1)'">𝕏</a>
                <a href="#"
                  style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:8px;color:#fff;text-decoration:none;font-size:18px;transition:.2s ease;font-weight:600;"
                  title="LinkedIn" onmouseover="this.style.background='rgba(255,255,255,.15)'"
                  onmouseout="this.style.background='rgba(255,255,255,.1)'">in</a>
                <a href="#"
                  style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;background:rgba(255,255,255,.1);border-radius:8px;color:#fff;text-decoration:none;font-size:18px;transition:.2s ease;font-weight:600;"
                  title="Instagram" onmouseover="this.style.background='rgba(255,255,255,.15)'"
                  onmouseout="this.style.background='rgba(255,255,255,.1)'">📷</a>
              </div>
            </div>

            <!-- Security -->
            <div>
              <h5
                style="font-weight:700;color:#fff;margin:0 0 16px 0;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;">
                Keamanan</h5>
              <ul style="list-style:none;margin:0;padding:0;">
                <li style="margin-bottom:8px;color:rgba(255,255,255,.6);font-size:12px;">✓ GDPR Compliant</li>
                <li style="margin-bottom:8px;color:rgba(255,255,255,.6);font-size:12px;">✓ ISO 27001 Certified</li>
                <li style="color:rgba(255,255,255,.6);font-size:12px;">✓ Data Backup Daily</li>
              </ul>
            </div>

            <!-- Legal -->
            <div>
              <h5
                style="font-weight:700;color:#fff;margin:0 0 16px 0;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;">
                Legal</h5>
              <ul style="list-style:none;margin:0;padding:0;">
                <li style="margin-bottom:8px;"><a href="#"
                    style="color:rgba(255,255,255,.6);text-decoration:none;font-size:12px;transition:.2s ease;">Privasi</a>
                </li>
                <li style="margin-bottom:8px;"><a href="#"
                    style="color:rgba(255,255,255,.6);text-decoration:none;font-size:12px;transition:.2s ease;">Terms</a>
                </li>
                <li><a href="#"
                    style="color:rgba(255,255,255,.6);text-decoration:none;font-size:12px;transition:.2s ease;">Sertifikasi</a>
                </li>
              </ul>
            </div>
          </div>

          <!-- Bottom -->
          <div style="padding-top:30px;text-align:center;">
            <p style="color:rgba(255,255,255,.4);font-size:12px;margin:0;">© 2026 Perpustakaan Digital Indonesia. Hak
              cipta dilindungi undang-undang.</p>
            <p style="color:rgba(255,255,255,.3);font-size:11px;margin:6px 0 0 0;">Made with ❤️ for Indonesian Education
              | v1.0.0</p>
          </div>
        </div>
      </div>
    </footer>

    <!-- LOGIN MODAL -->
    <div id="loginModal" class="modal" onclick="closeLoginModal(event)">
      <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeLoginModal()">&times;</button>

        <div class="login-modal-header">
          <div class="login-icon">📚</div>
          <h2>Masuk Perpustakaan</h2>
          <p>Kelola perpustakaan sekolah Anda</p>
        </div>

        <form method="post" action="public/api/login.php" id="loginForm" class="login-modal-form">
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required placeholder="admin@sekolah.com">
          </div>

          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required placeholder="••••••••">
          </div>

          <button type="submit" class="btn-modal-submit">🔓 Login</button>
        </form>

        <div class="login-modal-divider"></div>

        <p class="login-modal-footer">Belum punya akun?</p>
        <a href="#" onclick="closeLoginModal(); openRegisterModal(event);" class="btn-modal-register">📝 Daftar Akun</a>
      </div>
    </div>

    <!-- REGISTER MODAL -->
    <div id="registerModal" class="modal" onclick="closeRegisterModal(event)">
      <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeRegisterModal()">&times;</button>

        <div class="login-modal-header">
          <div class="login-icon">📖</div>
          <h2>Daftar Sekolah Baru</h2>
          <p>Kelola perpustakaan sekolah dengan sistem yang modern</p>
        </div>

        <form method="post" action="public/api/register.php" id="registerForm" class="login-modal-form">
          <div class="form-group">
            <label>Nama Sekolah</label>
            <input type="text" name="school_name" required placeholder="SMA Maju Jaya">
          </div>

          <div class="form-group">
            <label>Nama Admin</label>
            <input type="text" name="admin_name" required placeholder="Budi Santoso">
          </div>

          <div class="form-group">
            <label>Email Admin</label>
            <input type="email" name="admin_email" required placeholder="admin@sekolah.com">
          </div>

          <div class="form-group">
            <label>Password Admin</label>
            <input type="password" name="admin_password" required placeholder="••••••••">
          </div>

          <button type="submit" class="btn-modal-submit">✓ Daftarkan Sekolah</button>
        </form>

        <div class="login-modal-divider"></div>

        <p class="login-modal-footer">Sudah punya akun?</p>
        <a href="#" onclick="closeRegisterModal(); openLoginModal(event);" class="btn-modal-register">🔓 Login di
          sini</a>
      </div>
    </div>

    <script>
      // Check if login is required and auto-open modal
      if (new URLSearchParams(window.location.search).get('login_required') === '1') {
        window.addEventListener('load', () => {
          openLoginModal();
        });
      }

      function openLoginModal(e) {
        if (e) e.preventDefault();
        document.getElementById('loginModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }

      function closeLoginModal(e) {
        if (e && e.target.id !== 'loginModal') return;
        document.getElementById('loginModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }

      // Close modal on Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeLoginModal();
          closeRegisterModal();
        }
      });

      // Handle login form submission
      document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        try {
          const response = await fetch('public/api/login.php', {
            method: 'POST',
            body: formData
          });

          const data = await response.json();
          if (data.success) {
            window.location.href = 'public/index.php';
          } else {
            alert(data.message || 'Login gagal');
          }
        } catch (error) {
          alert('Terjadi kesalahan: ' + error.message);
        }
      });

      // Register Modal Functions
      function openRegisterModal(e) {
        if (e) e.preventDefault();
        document.getElementById('registerModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }

      function closeRegisterModal(e) {
        if (e && e.target.id !== 'registerModal') return;
        document.getElementById('registerModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }

      // Handle register form submission
      document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        try {
          const response = await fetch('public/api/register.php', {
            method: 'POST',
            body: formData
          });

          const data = await response.json();
          if (data.success) {
            alert('Pendaftaran berhasil! Silakan login dengan akun Anda.');
            closeRegisterModal();
            openLoginModal();
          } else {
            alert(data.message || 'Pendaftaran gagal');
          }
        } catch (error) {
          alert('Terjadi kesalahan: ' + error.message);
        }
      });
    </script>
</body>

</html>