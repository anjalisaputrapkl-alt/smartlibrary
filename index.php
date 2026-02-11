<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5, user-scalable=yes" />
  <meta name="description" content="Sistem Informasi Perpustakaan Digital Sekolah Modern.">
  <title>Perpustakaan Digital — Platform Sekolah Modern</title>
  <link rel="stylesheet" href="assets/css/landing.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap"
    rel="stylesheet">
  <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
</head>

<body>

  <!-- GLASS HEADER -->
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.php" class="brand">
        <div class="logo-icon">
          <img src="img/logo.png" alt="Logo">
        </div>
        <div class="brand-text">
          <div class="site-title">Perpustakaan<span style="color:var(--accent);">Digital</span></div>
        </div>
      </a>

      <nav class="main-nav">
        <a href="#features">Fitur</a>
        <a href="#audience">Solusi</a>
        <a href="#stats">Statistik</a>
        <a href="#testimonials">Testimoni</a>
        <!-- Mobile only login link style handled by current mobile flex logic -->
        <a href="#" onclick="openLoginModal(event)" class="nav-btn login hide-on-desktop" style="margin-top: 10px; background: var(--primary); color: #fff; padding: 12px 32px; border-radius: 50px;">Masuk</a>
      </nav>

      <div class="nav-right">
        <a href="#" onclick="openLoginModal(event)" class="nav-btn login">Masuk</a>
        <a href="#" onclick="openRegisterModal(event)" class="nav-btn register hide-on-mobile">Daftar Sekolah</a>
      </div>

      <button class="nav-toggle" aria-label="Toggle menu">
        <iconify-icon icon="solar:hamburger-menu-linear"></iconify-icon>
      </button>
    </div>
  </header>

  <main>
    <!-- MODERN HERO -->
    <section class="hero">
      <div class="hero-bg-glow"></div>
      <div class="container hero-inner">
        <div class="hero-content">
          <div class="badge-pill">
            <span class="badge-dot"></span>Telah Hadir
          </div>
          <h1>Transformasi Digital Perpustakaan Sekolah Anda</h1>
          <p class="lede">Platform manajemen perpustakaan modern yang terintegrasi, efisien, dan menyenangkan. Kelola buku, anggota, dan peminjaman dalam satu dashboard pintar.</p>
          <div class="hero-actions">
            <a href="#" onclick="openRegisterModal(event)" class="btn btn-primary btn-lg hide-on-mobile">
              Mulai Sekarang <iconify-icon icon="solar:arrow-right-linear"></iconify-icon>
            </a>
            <a href="#features" class="btn btn-outline btn-lg">Pelajari Fitur</a>
          </div>
          <div class="hero-trust">
            <p>Dipercaya oleh 50+ Sekolah di Indonesia</p>
            <div class="trust-icons">
               <!-- Placeholder for logos if needed -->
            </div>
          </div>
        </div>
        <div class="hero-image">
          <div class="hero-card glass-card">
            <div class="card-header">
              <div class="dot red"></div>
              <div class="dot yellow"></div>
              <div class="dot green"></div>
            </div>
            <img src="img/g1.jpg" alt="Dashboard Preview" class="dashboard-img">
            
            <!-- Floating Elements -->
            <div class="float-stat glass-card">
              <div class="stat-icon"><iconify-icon icon="solar:book-bookmark-bold"></iconify-icon></div>
              <div>
                <span class="stat-val">25rb+</span>
                <span class="stat-label">Koleksi Buku</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- BENTO GRID FEATURES -->
    <section id="features" class="section">
      <div class="container">
        <div class="section-header text-center animate-on-scroll">
          <h2 class="section-title">Fitur Lengkap & Canggih</h2>
          <p class="section-subtitle">Semua yang Anda butuhkan untuk mengelola perpustakaan modern</p>
        </div>

        <div class="bento-grid">
          <!-- Large Feature -->
          <div class="bento-item large bento-1 animate-on-scroll delay-100">
            <div class="bento-content">
              <h3>Manajemen Sirkulasi Otomatis</h3>
              <p>Peminjaman dan pengembalian buku tercatat otomatis. Notifikasi denda dan keterlambatan terkirim via sistem.</p>
            </div>
            <div class="bento-visual">
              <iconify-icon icon="solar:round-transfer-horizontal-bold-duotone" class="big-icon"></iconify-icon>
            </div>
          </div>

          <!-- Medium Features -->
          <div class="bento-item medium bento-2 animate-on-scroll delay-200">
            <div class="icon-box"><iconify-icon icon="solar:users-group-rounded-bold-duotone"></iconify-icon></div>
            <h3>Manajemen Anggota</h3>
            <p>Database siswa, guru, dan karyawan terpusat dengan kartu anggota digital.</p>
          </div>

          <div class="bento-item medium bento-3 animate-on-scroll delay-300">
             <div class="icon-box"><iconify-icon icon="solar:chart-2-bold-duotone"></iconify-icon></div>
            <h3>Laporan Analitik</h3>
            <p>Visualisasi data peminjaman real-time untuk keputusan yang lebih baik.</p>
          </div>

          <!-- Wide Feature -->
          <!-- Wide Feature -->
          <div class="bento-item wide bento-4 animate-on-scroll delay-100">
            <div class="bento-flex">
              <div class="bento-text">
                <h3>Katalog OPAC Modern</h3>
                <p>Pencarian buku cepat & akurat. Filter kategori, penulis, dan ketersediaan real-time.</p>
              </div>
              <div class="bento-visual-opac">
                <div class="mock-search-bar">
                   <iconify-icon icon="solar:magnifer-linear"></iconify-icon>
                   <span>Cari buku sejarah...</span>
                   <div class="search-btn">Cari</div>
                </div>
                <div class="mock-tags">
                   <span>#Sejarah</span>
                   <span>#Sains</span>
                   <span>#Novel</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- STATS COUNTER -->
    <section id="stats" class="section stats-section animate-on-scroll">
      <div class="container">
        <div class="stats-grid">
          <div class="stat-item">
            <h3 class="counter" data-target="50">0</h3>
            <p>Sekolah Mitra</p>
          </div>
          <div class="stat-item">
            <h3 class="counter" data-target="25000">0</h3>
            <p>Buku Digital</p>
          </div>
          <div class="stat-item">
            <h3 class="counter" data-target="15000">0</h3>
            <p>Pengguna Aktif</p>
          </div>
          <div class="stat-item">
            <h3 class="counter-percent" data-target="99">0</h3>
            <p>Kepuasan User</p>
          </div>
        </div>
      </div>
    </section>

    <!-- TESTIMONIALS & FAQ SPLIT -->
    <section id="testimonials" class="section bg-soft">
      <div class="container split-layout">
        <div class="testimonials-col animate-on-scroll">
          <h2 class="section-title">Kata Mereka</h2>
          <div class="testimonial-slider">
            <div class="testimonial-single glass-card">
              <div class="quote-icon"><iconify-icon icon="solar:quote-up-bold"></iconify-icon></div>
              <p class="quote-text">"Sistem ini mengubah cara perpustakaan kami bekerja. Dari manual ke digital dalam hitungan hari. Sangat membantu!"</p>
              <div class="user-profile">
                <div class="avatar">BS</div>
                <div>
                  <h4>Budi Santoso</h4>
                  <p>Kepala Perpus SMAN 1</p>
                </div>
              </div>
            </div>
             <div class="testimonial-single glass-card mt-4">
              <div class="quote-icon"><iconify-icon icon="solar:quote-up-bold"></iconify-icon></div>
              <p class="quote-text">"Fitur booking bukunya keren banget. Gak perlu rebutan lagi kalau ada buku baru."</p>
              <div class="user-profile">
                <div class="avatar">AD</div>
                <div>
                  <h4>Ani Dewantara</h4>
                  <p>Siswi Kelas XII</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="faq-col animate-on-scroll delay-200">
          <h2 class="section-title">FAQ</h2>
          <div class="accordion">
            <div class="accordion-item">
              <button class="accordion-header" onclick="toggleAccordion(this)">
                Apakah sistem ini berbayar?
                <iconify-icon icon="solar:alt-arrow-down-linear" class="arrow"></iconify-icon>
              </button>
              <div class="accordion-body">
                <p>Kami menyediakan semua fitur gratis tanpa di pungut biaya apapun.</p>
              </div>
            </div>
            <div class="accordion-item">
               <button class="accordion-header" onclick="toggleAccordion(this)">
                Bagaimana cara daftar?
                <iconify-icon icon="solar:alt-arrow-down-linear" class="arrow"></iconify-icon>
              </button>
              <div class="accordion-body">
                <p>Klik tombol "Daftar Sekolah" di pojok kanan atas, isi data sekolah, dan verifikasi email Anda.</p>
              </div>
            </div>
            <div class="accordion-item">
               <button class="accordion-header" onclick="toggleAccordion(this)">
                Apakah data aman?
                <iconify-icon icon="solar:alt-arrow-down-linear" class="arrow"></iconify-icon>
              </button>
              <div class="accordion-body">
                <p>Ya, kami menggunakan enkripsi standar industri dan backup data harian untuk menjamin keamanan data sekolah Anda.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="section cta-section animate-on-scroll hide-on-mobile">
      <div class="container text-center">
        <div class="cta-box glass-card glow">
          <h2>Siap Modernisasi Perpustakaan Anda?</h2>
          <p>Bergabunglah sekarang dan rasakan kemudahannya.</p>
          <div class="cta-buttons">
            <a href="#" onclick="openRegisterModal(event)" class="btn btn-light btn-lg">Daftar Gratis</a>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo-text">Perpustakaan<span style="color:var(--accent-light);">Digital</span></div>
          <p>© 2026. Made for Education.</p>
        </div>
        <div class="footer-links">
          <h4>Produk</h4>
          <a href="#">Fitur</a>
          <a href="#">Harga</a>
          <a href="#">Panduan</a>
        </div>
        <div class="footer-links">
          <h4>Legal</h4>
          <a href="#">Privasi</a>
          <a href="#">Syarat</a>
        </div>
        <div class="footer-social">
           <a href="#"><iconify-icon icon="mdi:instagram"></iconify-icon></a>
           <a href="#"><iconify-icon icon="mdi:twitter"></iconify-icon></a>
           <a href="#"><iconify-icon icon="mdi:linkedin"></iconify-icon></a>
        </div>
      </div>
    </div>
  </footer>

  <!-- MODALS (Login/Register/UserType) - Reused Logic -->
  <!-- User Type Modal -->
  <div id="userTypeModal" class="modal" onclick="closeUserTypeModal(event)">
    <div class="modal-content glass-modal">
       <button class="modal-close" onclick="closeUserTypeModal()">&times;</button>
       <h2>Siapa Anda?</h2>
       <div class="role-grid">
         <button onclick="selectUserType('student')" class="role-card">
           <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
           <span>Siswa / Guru / Karyawan</span>
         </button>
         <button onclick="selectUserType('school')" class="role-card hide-on-mobile">
           <iconify-icon icon="solar:shield-user-bold-duotone"></iconify-icon>
           <span>Admin / Pustakawan</span>
         </button>
       </div>
    </div>
  </div>

  <!-- Login Modal -->
   <div id="loginModal" class="modal" onclick="closeLoginModal(event)">
      <div class="modal-content glass-modal" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeLoginModal()">&times;</button>
        
        <!-- Student Form -->
        <div id="studentLoginForm" style="display:none;">
          <h2 class="modal-title">Login Anggota</h2>
          <p class="modal-subtitle">Masuk dengan ID/NISN Anda</p>
          <div id="studentLoginError" class="login-error-msg"></div>
          <form onsubmit="handleLogin(event, 'student')" class="modern-form">
            <input type="hidden" name="user_type" value="student">
            <div class="input-group">
               <iconify-icon icon="solar:user-id-bold"></iconify-icon>
               <input type="text" name="nisn" required placeholder="NISN / NIP / ID">
            </div>
            <div class="input-group">
               <iconify-icon icon="solar:lock-password-bold"></iconify-icon>
               <input type="password" name="password" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary full-width">Masuk</button>
          </form>
        </div>

        <!-- School Form -->
        <div id="schoolLoginForm" style="display:none;">
          <h2 class="modal-title">Login Admin</h2>
          <p class="modal-subtitle">Masuk akun sekolah</p>
          <div id="schoolLoginError" class="login-error-msg"></div>
          <form onsubmit="handleLogin(event, 'school')" class="modern-form">
            <input type="hidden" name="user_type" value="school">
             <div class="input-group">
               <iconify-icon icon="solar:letter-bold"></iconify-icon>
               <input type="email" name="email" required placeholder="Email">
            </div>
            <div class="input-group">
            <iconify-icon icon="solar:buildings-bold"></iconify-icon>
               <input type="password" name="password" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary full-width">Masuk Admin</button>
          </form>
          <div class="modal-footer">
            Belum punya akun? <a href="#" onclick="switchModal('register')">Daftar Sekolah</a>
          </div>
        </div>
      </div>
   </div>

   <!-- Register Modal -->
   <div id="registerModal" class="modal" onclick="closeRegisterModal(event)">
      <div class="modal-content glass-modal" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeRegisterModal()">&times;</button>
        <h2 class="modal-title" style="margin-bottom: 20px;">Daftar Sekolah</h2>
        <div id="registerError" class="login-error-msg"></div>
        <form onsubmit="handleRegister(event)" class="modern-form">
             <div class="input-group">
               <iconify-icon icon="solar:buildings-bold"></iconify-icon>
               <input type="text" name="school_name" required placeholder="Nama Sekolah">
            </div>
             <div class="input-group">
               <iconify-icon icon="solar:user-circle-bold"></iconify-icon>
               <input type="text" name="admin_name" required placeholder="Nama Admin">
            </div>
             <div class="input-group">
               <iconify-icon icon="solar:letter-bold"></iconify-icon>
               <input type="email" name="admin_email" required placeholder="Email (@sch.id)" pattern=".*@sch\.id$">
            </div>
             <div class="input-group">
               <iconify-icon icon="solar:lock-password-bold"></iconify-icon>
               <input type="password" name="admin_password" required placeholder="Password">
            </div>
            <button type="submit" class="btn btn-primary full-width">Daftar Sekarang</button>
        </form>
         <div class="modal-footer">
            Sudah punya akun? <a href="#" onclick="switchModal('login')">Masuk</a>
          </div>
      </div>

   <!-- OTP Verification Modal - MODERN PREMIUM DESIGN -->
   <div id="otpModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(11, 61, 97, 0.15); backdrop-filter: blur(10px); z-index: 99999; justify-content: center; align-items: center; animation: fadeIn 0.3s ease;">
      <div style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-radius: 24px; padding: 0; max-width: 480px; width: 90%; box-shadow: 0 25px 80px rgba(11, 61, 97, 0.25), 0 0 1px rgba(0,0,0,0.1); position: relative; overflow: hidden; border: 1px solid rgba(255,255,255,0.8);">
        
        <!-- Decorative gradient header -->
        <div style="background: linear-gradient(135deg, #0B3D61 0%, #1e40af 100%); padding: 32px 40px; position: relative; overflow: hidden;">
          <div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%; filter: blur(40px);"></div>
          <div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%; filter: blur(30px);"></div>
          
          <button onclick="closeOTPModalNew()" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.2); border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 20px; cursor: pointer; color: white; transition: all 0.3s; display: flex; align-items: center; justify-content: center;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
          
          <div style="position: relative; z-index: 1;">
            <div style="width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.3);">
              <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h2 style="margin: 0 0 8px 0; font-size: 28px; color: white; font-weight: 700; letter-spacing: -0.5px;">Verifikasi Email</h2>
            <p style="margin: 0; color: rgba(255,255,255,0.9); font-size: 15px; line-height: 1.5;">Masukkan kode OTP yang telah dikirim ke email Anda untuk melanjutkan</p>
          </div>
        </div>
        
        <!-- Content area -->
        <div style="padding: 32px 40px 40px;">
          
          <!-- OTP Code Display - PREMIUM STYLE -->
          <div id="otpCodeDisplayNew" style="display: none; background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%); border: 2px dashed #3b82f6; border-radius: 16px; padding: 24px; margin-bottom: 24px; text-align: center; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; background: rgba(59, 130, 246, 0.1); border-radius: 50%; filter: blur(30px);"></div>
            <div style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 12px;">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1e40af" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
              <p style="margin: 0; font-size: 13px; color: #1e40af; font-weight: 700; text-transform: uppercase; letter-spacing: 1px;">Kode OTP Anda</p>
            </div>
            <div id="otpCodeValueNew" style="font-size: 40px; font-weight: 900; color: #1e40af; letter-spacing: 12px; font-family: 'Courier New', monospace; margin: 16px 0; text-shadow: 0 2px 4px rgba(30, 64, 175, 0.1); position: relative; z-index: 1;"></div>
            <button type="button" onclick="copyOTPNew()" style="margin-top: 8px; padding: 10px 24px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; border-radius: 10px; font-size: 13px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); transition: all 0.3s; display: inline-flex; align-items: center; gap: 6px;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(59, 130, 246, 0.3)'">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
              Copy Kode
            </button>
          </div>
          
          <div id="otpErrorNew" style="display: none; background: linear-gradient(135deg, #fee 0%, #fdd 100%); border: 1px solid #fcc; color: #c33; padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 500;"></div>
          
          <form onsubmit="handleOTPVerificationNew(event)" style="margin: 0;">
            <input type="hidden" id="otpUserIdNew" name="user_id">
            <input type="hidden" id="otpEmailNew" name="email">
            
            <label style="display: block; margin-bottom: 8px; color: #0f172a; font-size: 14px; font-weight: 600;">Kode Verifikasi</label>
            <div style="position: relative; margin-bottom: 24px;">
              <input type="text" id="otpCodeNew" name="verification_code" required placeholder="000000" maxlength="6" pattern="[0-9]{6}" style="width: 100%; padding: 16px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 20px; box-sizing: border-box; font-family: 'Courier New', monospace; letter-spacing: 8px; text-align: center; font-weight: 800; transition: all 0.3s; background: #f8fafc;" onfocus="this.style.borderColor='#3b82f6'; this.style.background='white'; this.style.boxShadow='0 0 0 4px rgba(59, 130, 246, 0.1)'" onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'; this.style.boxShadow='none'">
            </div>
            
            <button type="submit" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #0B3D61 0%, #1e40af 100%); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; box-shadow: 0 8px 24px rgba(11, 61, 97, 0.3); transition: all 0.3s; letter-spacing: 0.5px;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 12px 32px rgba(11, 61, 97, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 8px 24px rgba(11, 61, 97, 0.3)'">Verifikasi Sekarang</button>
          </form>
          
          <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0; text-align: center; font-size: 14px; color: #64748b;">
            Tidak menerima kode? <a href="#" onclick="resendOTPNew(event)" style="color: #0B3D61; font-weight: 700; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#1e40af'" onmouseout="this.style.color='#0B3D61'">Kirim Ulang</a>
          </div>
        </div>
      </div>
   </div>
   </div>

   <!-- JS Logic -->
   <script>
    // --- Mobile Menu Logic ---
    const navToggle = document.querySelector('.nav-toggle');
    const mainNav = document.querySelector('.main-nav');
    const toggleIcon = navToggle.querySelector('iconify-icon');

    navToggle.addEventListener('click', () => {
      mainNav.classList.toggle('active');
      
      if (mainNav.classList.contains('active')) {
        toggleIcon.setAttribute('icon', 'solar:close-circle-linear');
        navToggle.style.transform = 'rotate(90deg)';
      } else {
        toggleIcon.setAttribute('icon', 'solar:hamburger-menu-linear');
        navToggle.style.transform = 'rotate(0)';
      }
    });

    // Close menu when clicking links
    document.querySelectorAll('.main-nav a').forEach(link => {
      link.addEventListener('click', () => {
        mainNav.classList.remove('active');
        toggleIcon.setAttribute('icon', 'solar:hamburger-menu-linear');
        navToggle.style.transform = 'rotate(0)';
      });
    });

    // --- Modal Logic ---
    function openLoginModal(e) {
      if(e) e.preventDefault();
      if (window.innerWidth <= 768) {
        selectUserType('student');
      } else {
        document.getElementById('userTypeModal').classList.add('active');
      }
    }
    function closeUserTypeModal() {
      document.getElementById('userTypeModal').classList.remove('active');
    }
    function selectUserType(type) {
      closeUserTypeModal();
      
      // Clear forms before opening
      document.querySelectorAll('#loginModal form').forEach(form => form.reset());
      document.querySelectorAll('.login-error-msg').forEach(msg => {
        msg.style.display = 'none';
        msg.innerText = '';
      });
      
      document.getElementById('loginModal').classList.add('active');
      document.getElementById('studentLoginForm').style.display = 'none';
      document.getElementById('schoolLoginForm').style.display = 'none';
      
      if(type === 'student') document.getElementById('studentLoginForm').style.display = 'block';
      else document.getElementById('schoolLoginForm').style.display = 'block';
    }
    function closeLoginModal(event) {
       // Stop if clicked inside modal content
       if(event && event.target !== event.currentTarget) return;
       
       document.getElementById('loginModal').classList.remove('active');
       
       // Clear all forms and error messages
       document.querySelectorAll('#loginModal form').forEach(form => form.reset());
       document.querySelectorAll('.login-error-msg').forEach(msg => {
         msg.style.display = 'none';
         msg.innerText = '';
       });
    }
    function openRegisterModal(e) {
      if(e) e.preventDefault();
      closeLoginModal();
      
      // Clear form before opening
      const form = document.querySelector('#registerModal form');
      if(form) form.reset();
      
      document.getElementById('registerModal').classList.add('active');
    }
    function closeRegisterModal(event) {
      // Stop if clicked inside modal content
      if(event && event.target !== event.currentTarget) return;
      
      // ONLY close register modal - DO NOT close OTP modal!
      document.getElementById('registerModal').classList.remove('active');
      
      // Only clear register form
      const form = document.querySelector('#registerModal form');
      if(form) form.reset();
      
      console.log('🔴 Register modal closed - OTP modal should remain untouched');
    }
    function closeOTPModal(event) {
      if(event && event.target !== event.currentTarget) return;
      document.getElementById('otpModal').classList.remove('active');
      // Clear OTP form
      document.getElementById('otpCode').value = '';
      document.getElementById('otpCodeDisplay').style.display = 'none';
      document.getElementById('otpError').style.display = 'none';
    }
    function switchModal(target) {
       if(target === 'register') openRegisterModal();
       else {
         closeRegisterModal();
         openLoginModal();
       }
    }

    // --- Accordion Logic ---
    function toggleAccordion(btn) {
      const item = btn.parentElement;
      item.classList.toggle('active');
    }

    // --- Stats Animation ---
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if(entry.isIntersecting) {
           const counters = entry.target.querySelectorAll('.counter, .counter-percent');
           counters.forEach(counter => {
             const target = +counter.getAttribute('data-target');
             const duration = 2000;
             const step = target / (duration / 16);
             let current = 0;
             const update = () => {
               current += step;
               if(current < target) {
                 counter.innerText = Math.ceil(current) + (counter.classList.contains('counter-percent') ? '%' : '');
                 requestAnimationFrame(update);
               } else {
                 counter.innerText = target + (counter.classList.contains('counter-percent') ? '%' : '');
               }
             };
             update();
           });
           observer.unobserve(entry.target);
        }
      });
    });
    document.querySelector('.stats-section') && observer.observe(document.querySelector('.stats-section'));

    // --- Navbar Sticky ---
    window.addEventListener('scroll', () => {
      const header = document.querySelector('.site-header');
      if(window.scrollY > 50) header.classList.add('scrolled');
      else header.classList.remove('scrolled');
    });

    // --- Scroll Fade In Animation ---
    const scrollObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if(entry.isIntersecting) {
          entry.target.classList.add('is-visible');
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => scrollObserver.observe(el));

    // --- AJAX Login Logic ---
    async function handleLogin(e, type) {
      e.preventDefault();
      const form = e.target;
      const btn = form.querySelector('button[type="submit"]');
      const errorMsg = document.getElementById(type + 'LoginError');
      const originalText = btn.innerText;

      // Reset state
      errorMsg.style.display = 'none';
      errorMsg.innerText = '';
      btn.disabled = true;
      btn.innerText = 'Memproses...';

      const formData = new FormData(form);

      try {
        const response = await fetch('public/api/login.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          btn.innerText = 'Berhasil!';
          form.reset(); // Clear form fields
          window.location.href = result.redirect_url;
        } else {
          throw new Error(result.message || 'Login gagal.');
        }
      } catch (err) {
        errorMsg.style.display = 'block';
        // Handle JSON parse error or network error
        errorMsg.innerText = err.message || 'Terjadi kesalahan koneksi.';
        btn.disabled = false;
        btn.innerText = originalText;
      }
    }

    // --- AJAX Register Logic - REBUILT ---
    async function handleRegister(e) {
      e.preventDefault();
      console.log('🔵 Register form submitted');
      
      const form = e.target;
      const btn = form.querySelector('button[type="submit"]');
      const errorMsg = document.getElementById('registerError');
      const originalText = btn.innerText;

      errorMsg.style.display = 'none';
      errorMsg.innerText = '';
      btn.disabled = true;
      btn.innerText = 'Memproses...';

      const formData = new FormData(form);

      try {
        console.log('🟡 Sending registration request...');
        const response = await fetch('public/api/register.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        console.log('🟢 Registration response:', result);

        if (result.success) {
          console.log('✅ Registration successful!');
          
          // Reset form and button
          form.reset();
          btn.disabled = false;
          btn.innerText = originalText;
          
          // RADICAL APPROACH: DON'T close register modal AT ALL!
          // Just show OTP modal on top with higher z-index (already 9999)
          console.log('🟣 Opening OTP modal WITHOUT closing register...');
          
          const otpModal = document.getElementById('otpModal');
          otpModal.style.display = 'flex';
          otpModal.style.zIndex = '99999'; // Even higher than before
          console.log('✅ OTP modal opened on top of register modal');
          
          // Set user_id and email
          document.getElementById('otpUserIdNew').value = result.user_id;
          document.getElementById('otpEmailNew').value = result.email;
          
          // Display OTP code for demo
          if (result.verification_code) {
            console.log('🔑 OTP Code:', result.verification_code);
            document.getElementById('otpCodeDisplayNew').style.display = 'block';
            document.getElementById('otpCodeValueNew').innerText = result.verification_code;
            document.getElementById('otpCodeNew').value = result.verification_code;
          }
          
          // SAVE to sessionStorage for persistence across refresh
          sessionStorage.setItem('otpPending', JSON.stringify({
            user_id: result.user_id,
            email: result.email,
            verification_code: result.verification_code || '',
            timestamp: Date.now()
          }));
          
          console.log('✅ Done - both modals open, OTP on top');
          
        } else {
          throw new Error(result.message || 'Pendaftaran gagal.');
        }
      } catch (err) {
        console.error('❌ Registration error:', err);
        errorMsg.style.display = 'block';
        errorMsg.innerText = err.message || 'Terjadi kesalahan.';
        btn.disabled = false;
        btn.innerText = originalText;
      }
      
      return false;
    }

    // --- NEW OTP Functions ---
    function closeOTPModalNew() {
      document.getElementById('otpModal').style.display = 'none';
      document.getElementById('otpCodeNew').value = '';
      document.getElementById('otpCodeDisplayNew').style.display = 'none';
      document.getElementById('otpErrorNew').style.display = 'none';
      
      // Clear sessionStorage when modal is closed
      sessionStorage.removeItem('otpPending');
    }

    function copyOTPNew() {
      const code = document.getElementById('otpCodeValueNew').innerText;
      navigator.clipboard.writeText(code).then(() => {
        alert('Kode OTP berhasil disalin!');
      });
    }

    async function handleOTPVerificationNew(e) {
      e.preventDefault();
      const form = e.target;
      const btn = form.querySelector('button[type="submit"]');
      const errorMsg = document.getElementById('otpErrorNew');
      const originalText = btn.innerText;

      errorMsg.style.display = 'none';
      errorMsg.innerText = '';
      btn.disabled = true;
      btn.innerText = 'Memverifikasi...';

      const formData = new FormData(form);

      try {
        const response = await fetch('public/api/verify-and-login.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          // Clear sessionStorage on success
          sessionStorage.removeItem('otpPending');
          
          alert('✅ Email berhasil diverifikasi! Silakan login.');
          closeOTPModalNew();
          
          // Close register modal juga
          document.getElementById('registerModal').classList.remove('active');
          
          // Redirect to dashboard if auto-login
          if (result.redirect_url) {
            window.location.href = result.redirect_url;
          } else {
            openLoginModal();
          }
        } else {
          throw new Error(result.message || 'Kode OTP salah.');
        }
      } catch (err) {
        errorMsg.style.display = 'block';
        errorMsg.innerText = err.message || 'Verifikasi gagal.';
        btn.disabled = false;
        btn.innerText = originalText;
      }
    }

    async function resendOTPNew(e) {
      e.preventDefault();
      const email = document.getElementById('otpEmailNew').value;
      
      try {
        const response = await fetch('public/api/resend-otp.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: email })
        });

        const result = await response.json();
        
        if (result.success) {
          alert('📧 Kode OTP baru telah dikirim ke email Anda.');
          
          // Update displayed OTP code with NEW code
          if (result.verification_code) {
            console.log('🔑 New OTP Code:', result.verification_code);
            document.getElementById('otpCodeDisplayNew').style.display = 'block';
            document.getElementById('otpCodeValueNew').innerText = result.verification_code;
            document.getElementById('otpCodeNew').value = result.verification_code;
            
            // Update sessionStorage with new code
            const otpData = JSON.parse(sessionStorage.getItem('otpPending') || '{}');
            otpData.verification_code = result.verification_code;
            otpData.timestamp = Date.now();
            sessionStorage.setItem('otpPending', JSON.stringify(otpData));
          }
        } else {
          alert(result.message || 'Gagal mengirim ulang OTP.');
        }
      } catch (err) {
        alert('Terjadi kesalahan saat mengirim ulang OTP.');
      }
    }

    // Copy OTP to clipboard
    function copyOTP() {
      const code = document.getElementById('otpCodeValue').innerText;
      navigator.clipboard.writeText(code).then(() => {
        alert('Kode OTP berhasil disalin!');
      });
    }

    // --- OTP Verification Logic ---
    async function handleOTPVerification(e) {
      e.preventDefault();
      const form = e.target;
      const btn = form.querySelector('button[type="submit"]');
      const errorMsg = document.getElementById('otpError');
      const originalText = btn.innerText;

      errorMsg.style.display = 'none';
      errorMsg.innerText = '';
      btn.disabled = true;
      btn.innerText = 'Memverifikasi...';

      const formData = new FormData(form);

      try {
        const response = await fetch('public/api/verify-email.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          alert('Email berhasil diverifikasi! Silakan login.');
          document.getElementById('otpModal').classList.remove('active');
          form.reset();
          openLoginModal();
        } else {
          throw new Error(result.message || 'Kode OTP salah.');
        }
      } catch (err) {
        errorMsg.style.display = 'block';
        errorMsg.innerText = err.message || 'Verifikasi gagal.';
        btn.disabled = false;
        btn.innerText = originalText;
      }
    }

    // --- Resend OTP ---
    async function resendOTP(e) {
      e.preventDefault();
      const email = document.getElementById('otpEmail').value;
      
      try {
        const response = await fetch('public/api/resend-otp.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: email })
        });

        const result = await response.json();
        
        if (result.success) {
          alert('Kode OTP baru telah dikirim ke email Anda.');
        } else {
          alert(result.message || 'Gagal mengirim ulang OTP.');
        }
      } catch (err) {
        alert('Terjadi kesalahan saat mengirim ulang OTP.');
      }
    }

    // DEBUG: Test function to open OTP modal directly
    function testOpenOTP() {
      console.log('🧪 TEST: Opening OTP modal...');
      const otpModal = document.getElementById('otpModal');
      console.log('🧪 Modal element:', otpModal);
      console.log('🧪 Before - Display:', otpModal.style.display);
      
      otpModal.style.display = 'flex';
      
      console.log('🧪 After - Display:', otpModal.style.display);
      console.log('🧪 Computed style:', window.getComputedStyle(otpModal).display);
      
      // Set demo OTP
      document.getElementById('otpCodeDisplayNew').style.display = 'block';
      document.getElementById('otpCodeValueNew').innerText = '123456';
      document.getElementById('otpCodeNew').value = '123456';
      
      console.log('✅ TEST: Modal should be visible now!');
      
      // Check after 1 second if it's still visible
      setTimeout(() => {
        console.log('🔍 Check after 1s - Display:', otpModal.style.display);
        console.log('🔍 Check after 1s - Computed:', window.getComputedStyle(otpModal).display);
      }, 1000);
    }
    
    // --- RESTORE OTP MODAL ON PAGE LOAD ---
    function restoreOTPModal() {
      const otpPending = sessionStorage.getItem('otpPending');
      console.log('🔍 Checking sessionStorage for OTP:', otpPending);
      
      if (otpPending) {
        try {
          const otpData = JSON.parse(otpPending);
          console.log('📦 OTP Data found:', otpData);
          
          // Check if OTP is not too old (e.g., within 15 minutes)
          const ageMinutes = (Date.now() - otpData.timestamp) / (1000 * 60);
          console.log(`⏰ OTP age: ${ageMinutes.toFixed(2)} minutes`);
          
          if (ageMinutes < 15) {
            console.log('🔄 Restoring OTP modal from sessionStorage...');
            
            // Restore OTP modal
            const otpModal = document.getElementById('otpModal');
            otpModal.style.display = 'flex';
            otpModal.style.zIndex = '99999';
            
            // Restore form data
            document.getElementById('otpUserIdNew').value = otpData.user_id || '';
            document.getElementById('otpEmailNew').value = otpData.email || '';
            
            // Restore OTP code display
            if (otpData.verification_code) {
              document.getElementById('otpCodeDisplayNew').style.display = 'block';
              document.getElementById('otpCodeValueNew').innerText = otpData.verification_code;
              document.getElementById('otpCodeNew').value = otpData.verification_code;
            }
            
            console.log('✅ OTP modal restored successfully!');
          } else {
            // Too old, remove from storage
            console.log('⏰ OTP code expired, clearing sessionStorage');
            sessionStorage.removeItem('otpPending');
          }
        } catch (err) {
          console.error('❌ Error restoring OTP modal:', err);
          sessionStorage.removeItem('otpPending');
        }
      } else {
        console.log('ℹ️ No pending OTP in sessionStorage');
      }
    }
    
    // Run immediately AND on DOMContentLoaded
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', restoreOTPModal);
    } else {
      // DOM already loaded, run immediately
      restoreOTPModal();
    }
   </script>
</body>
</html>