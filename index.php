<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5, user-scalable=yes" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <title>Perpustakaan Digital — Akses Pengetahuan Modern</title>
  <link rel="stylesheet" href="assets/css/landing.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Merriweather:wght@700;900&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
</head>

<body>

  <header class="site-header">
    <div class="container">
      <a href="index.php" class="brand">
        <img src="img/logo.png" alt="Logo Perpustakaan Digital" class="logo-img"
          style="width: 45px; height: 45px; object-fit: contain;">
        <div class="brand-text">
          <div class="site-title">AS Library</div>
          <div class="site-sub">Sistem Manajemen Perpustakaan Sekolah</div>
        </div>
      </a>

      <nav class="main-nav">
        <a href="#solution">Solusi</a>
        <a href="#features">Fitur</a>
        <a href="#audience">Pengguna</a>
        <a href="#contact">Kontak</a>
        <div class="nav-mobile-cta">
          <a href="#" onclick="openLoginModal(event)" class="nav-btn login">Login</a>
          <a href="#" onclick="openRegisterModal(event)" class="nav-btn register">Daftar</a>
        </div>
      </nav>

      <div class="nav-right">
        <a href="#" onclick="openLoginModal(event)" class="nav-btn login">Login</a>
        <a href="#" onclick="openRegisterModal(event)" class="nav-btn register">Daftar</a>
      </div>

      <button class="nav-toggle" id="hamburger-btn" aria-label="Toggle menu">☰</button>
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
      <div class="container" data-aos="zoom-in">
        <h2>Tantangan Manajemen Perpustakaan Sekolah</h2>
        <p class="microcopy">Banyak sekolah masih menghadapi kesulitan dalam mengelola perpustakaan secara efisien dan
          modern.</p>

        <div class="values-grid">
          <article class="value">
            <h3>Pencatatan Manual</h3>
            <p>Tanpa sistem otomatis yang handal, proses pencatatan buku dan peminjaman masih dilakukan secara manual
              dan sangat rentan terhadap kesalahan data.</p>
          </article>
          <article class="value">
            <h3>Waktu Hilang</h3>
            <p>Proses pencarian buku dan data anggota memakan waktu yang sangat lama ketika tidak didukung oleh sistem
              digital yang tepat dan terintegrasi.</p>
          </article>
          <article class="value">
            <h3>Laporan Sulit</h3>
            <p>Tanpa sistem analitik yang memadai, sekolah kesulitan membuat laporan komprehensif dan melakukan evaluasi
              penggunaan perpustakaan.</p>
          </article>
          <article class="value">
            <h3>Data Tidak Aman</h3>
            <p>Tanpa sistem digital yang terorganisir, data perpustakaan berisiko hilang atau tidak terstruktur dengan
              rapih dan dengan baik
              baik.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- SOLUTION -->
    <section id="solution" class="section solution">
      <div class="container split" data-aos="zoom-in">
        <div class="col">
          <h2>Solusi Perpustakaan Digital Terintegrasi</h2>
          <p>
            Kami menyediakan sistem manajemen perpustakaan yang dirancang khusus untuk kebutuhan sekolah. Platform kami
            mengintegrasikan semua aspek operasional perpustakaan dalam satu dashboard yang intuitif dan mudah
            digunakan.
          </p>

          <ul class="story">
            <li> Kelola koleksi buku dengan mudah dan terstruktur</li>
            <li> Catat anggota perpustakaan secara digital</li>
            <li> Proses peminjaman dan pengembalian otomatis</li>
            <li> Laporan statistik dan analisis penggunaan real-time</li>
          </ul>
        </div>
        <div class="col">
          <img src="img/g2.jpg" class="section-img" alt="Dashboard sistem perpustakaan terintegrasi" />
        </div>
      </div>
    </section>

    <!-- STATS -->
    <section class="section stats"
      style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); padding: 80px 0; position: relative; overflow: hidden;">
      <div
        style="position: absolute; top: -40px; right: -40px; width: 300px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 50%; z-index: 0;">
      </div>
      <div
        style="position: absolute; bottom: -60px; left: -60px; width: 250px; height: 250px; background: rgba(255,255,255,0.05); border-radius: 50%; z-index: 0;">
      </div>

      <div class="container" style="position: relative; z-index: 1;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 40px;">
          <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: 700; color: #fff; margin-bottom: 8px;" class="stat-number"
              data-target="50">0</div>
            <div style="font-size: 14px; color: rgba(255,255,255,0.8); font-weight: 500; letter-spacing: 0.5px;">SEKOLAH
              TERDAFTAR</div>
            <div style="width: 40px; height: 2px; background: rgba(255,255,255,0.3); margin: 12px auto 0;"></div>
          </div>

          <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: 700; color: #fff; margin-bottom: 8px;" class="stat-number"
              data-target="25000">0</div>
            <div style="font-size: 14px; color: rgba(255,255,255,0.8); font-weight: 500; letter-spacing: 0.5px;">KOLEKSI
              BUKU</div>
            <div style="width: 40px; height: 2px; background: rgba(255,255,255,0.3); margin: 12px auto 0;"></div>
          </div>

          <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: 700; color: #fff; margin-bottom: 8px;" class="stat-number"
              data-target="15000">0</div>
            <div style="font-size: 14px; color: rgba(255,255,255,0.8); font-weight: 500; letter-spacing: 0.5px;">
              PENGGUNA AKTIF</div>
            <div style="width: 40px; height: 2px; background: rgba(255,255,255,0.3); margin: 12px auto 0;"></div>
          </div>

          <div style="text-align: center;">
            <div style="font-size: 48px; font-weight: 700; color: #fff; margin-bottom: 8px;" class="stat-number-percent"
              data-target="99">0</div>
            <div style="font-size: 14px; color: rgba(255,255,255,0.8); font-weight: 500; letter-spacing: 0.5px;">UPTIME
              SISTEM</div>
            <div style="width: 40px; height: 2px; background: rgba(255,255,255,0.3); margin: 12px auto 0;"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- COLLECTIONS -->
    <section id="features" class="section preview">
      <div class="container" data-aos="zoom-in">
        <h2>Fitur-Fitur Utama Sistem</h2>

        <div
          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-top: 48px;">
          <div
            style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.03) 0%, rgba(37, 99, 235, 0) 100%); border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; border-left: 4px solid #2563eb; transition: all 0.3s ease; cursor: pointer; position: relative;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.15)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div
              style="display: inline-block; width: 40px; height: 40px; background: #2563eb; color: white; border-radius: 8px; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
              01</div>
            <h3 style="font-size: 18px; font-weight: 600; color: #2563eb; margin: 0 0 12px 0;">Manajemen Buku</h3>
            <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0;">Kelola koleksi buku dengan
              pencarian mudah, kategori, dan informasi lengkap setiap judul.</p>
          </div>

          <div
            style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.03) 0%, rgba(37, 99, 235, 0) 100%); border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; border-left: 4px solid #2563eb; transition: all 0.3s ease; cursor: pointer; position: relative;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.15)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div
              style="display: inline-block; width: 40px; height: 40px; background: #2563eb; color: white; border-radius: 8px; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
              02</div>
            <h3 style="font-size: 18px; font-weight: 600; color: #2563eb; margin: 0 0 12px 0;">Manajemen Anggota</h3>
            <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0;">Pendaftaran anggota digital,
              tracking aktivitas, dan identitas terverifikasi dengan aman.</p>
          </div>

          <div
            style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.03) 0%, rgba(37, 99, 235, 0) 100%); border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; border-left: 4px solid #2563eb; transition: all 0.3s ease; cursor: pointer; position: relative;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.15)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div
              style="display: inline-block; width: 40px; height: 40px; background: #2563eb; color: white; border-radius: 8px; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
              03</div>
            <h3 style="font-size: 18px; font-weight: 600; color: #2563eb; margin: 0 0 12px 0;">Peminjaman & Pengembalian
            </h3>
            <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0;">Proses peminjaman cepat dengan
              notifikasi otomatis dan manajemen tenggat waktu.</p>
          </div>

          <div
            style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.03) 0%, rgba(37, 99, 235, 0) 100%); border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; border-left: 4px solid #2563eb; transition: all 0.3s ease; cursor: pointer; position: relative;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.15)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <div
              style="display: inline-block; width: 40px; height: 40px; background: #2563eb; color: white; border-radius: 8px; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
              04</div>
            <h3 style="font-size: 18px; font-weight: 600; color: #2563eb; margin: 0 0 12px 0;">Laporan & Analitik</h3>
            <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin: 0;">Dashboard interaktif dengan laporan
              statistik penggunaan perpustakaan real-time.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- AUDIENCE -->
    <section id="audience" class="section audience" style="background: #f9fafb; padding: 80px 0;">
      <div class="container">
        <h2>Untuk Siapa Sistem Ini?</h2>
        <p class="microcopy" style="text-align: center; max-width: 600px; margin: 0 auto 48px;">Dirancang untuk memenuhi
          kebutuhan semua pihak yang terlibat dalam ekosistem perpustakaan
          sekolah.</p>

        <div
          style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-top: 48px;">
          <div
            style="background: #fff; border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; text-align: center; transition: all 0.3s ease; cursor: pointer;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.12)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <img src="img/admin-sekolah.jpg" alt="Admin Sekolah"
              style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; margin: 0 auto 16px; display: block;">
            <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;">Admin Sekolah</h3>
            <p style="font-size: 13px; color: #6b7280; line-height: 1.6; margin: 0;">Kelola seluruh sistem, user, dan
              laporan operasional perpustakaan dengan mudah.</p>
          </div>

          <div
            style="background: #fff; border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; text-align: center; transition: all 0.3s ease; cursor: pointer;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.12)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <img src="img/pustakawan.jpg" alt="Pustakawan"
              style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; margin: 0 auto 16px; display: block;">
            <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;">Pustakawan</h3>
            <p style="font-size: 13px; color: #6b7280; line-height: 1.6; margin: 0;">Kelola inventaris buku, proses
              peminjaman, dan pengembalian dalam satu dashboard.</p>
          </div>

          <div
            style="background: #fff; border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; text-align: center; transition: all 0.3s ease; cursor: pointer;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.12)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <img src="img/guru.jpg" alt="Guru & Dosen"
              style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; margin: 0 auto 16px; display: block;">
            <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;">Guru & Dosen</h3>
            <p style="font-size: 13px; color: #6b7280; line-height: 1.6; margin: 0;">Akses mudah ke koleksi buku dan
              rekomendasi sumber pembelajaran untuk kelas.</p>
          </div>

          <div
            style="background: #fff; border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; text-align: center; transition: all 0.3s ease; cursor: pointer;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.12)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <img src="img/murid.jpg" alt="Siswa & Mahasiswa"
              style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; margin: 0 auto 16px; display: block;">
            <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;">Siswa & Mahasiswa</h3>
            <p style="font-size: 13px; color: #6b7280; line-height: 1.6; margin: 0;">Cari dan pinjam buku online,
              tracking status peminjaman, dan perpanjangan otomatis.</p>
          </div>

          <div
            style="background: #fff; border-radius: 12px; padding: 32px; border: 1px solid #e5e7eb; text-align: center; transition: all 0.3s ease; cursor: pointer;"
            onmouseover="this.style.boxShadow='0 10px 30px rgba(37, 99, 235, 0.12)'; this.style.transform='translateY(-4px)'"
            onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)'">
            <img src="img/institusi.jpg" alt="Institusi Pendidikan"
              style="width: 80px; height: 80px; border-radius: 12px; object-fit: cover; margin: 0 auto 16px; display: block;">
            <h3 style="font-size: 16px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0;">Institusi Pendidikan</h3>
            <p style="font-size: 13px; color: #6b7280; line-height: 1.6; margin: 0;">Solusi enterprise dengan analitik
              mendalam dan integrasi sistem sekolah yang komprehensif.</p>
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
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">
                    anjalisaputrapkl@gmail.com</a></li>
                <li style="margin-bottom:10px;"><a href="tel:+622745551234"
                    style="color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;transition:.2s ease;">
                    +62 851-8737-8063</a></li>
                <li style="color:rgba(255,255,255,.7);font-size:13px;">Senin–Jumat 09:00–17:00</li>
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

    <!-- USER TYPE SELECTION MODAL -->
    <div id="userTypeModal" class="modal" onclick="closeUserTypeModal(event)">
      <div class="modal-content user-type-modal" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeUserTypeModal()">&times;</button>

        <div class="user-type-header">
          <h2>Masuk ke Akun Anda</h2>
          <p>Pilih jenis akun untuk melanjutkan</p>
        </div>

        <div class="user-type-options">
          <button type="button" class="user-type-option" onclick="selectUserType('student')">
            <div class="user-type-icon"></div>
            <div class="user-type-title">Siswa / Mahasiswa</div>
            <div class="user-type-desc">Akses sebagai pengguna pelajar</div>
          </button>

          <button type="button" class="user-type-option" onclick="selectUserType('school')">
            <div class="user-type-icon"></div>
            <div class="user-type-title">Admin / Pustakawan</div>
            <div class="user-type-desc">Akses sebagai admin/pustakawan</div>
          </button>
        </div>
      </div>
    </div>

    <!-- LOGIN MODAL -->
    <div id="loginModal" class="modal" onclick="closeLoginModal(event)">
      <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeLoginModal()">&times;</button>

        <!-- STUDENT LOGIN FORM -->
        <div id="studentLoginForm" class="login-form-container" style="display: none;">
          <div class="login-modal-header">
            <div class="login-icon"></div>
            <h2>Login Siswa</h2>
            <p>Akses akun siswa Anda dengan NISN</p>
          </div>

          <form method="post" action="public/api/login.php" class="login-modal-form">
            <input type="hidden" name="user_type" value="student">
            <div class="form-group">
              <label>NISN (Nomor Induk Siswa Nasional)</label>
              <input type="text" name="nisn" required placeholder="Contoh: 1234567890">
            </div>

            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-modal-submit">Login</button>
          </form>

          <div class="login-modal-divider"></div>
        </div>

        <!-- SCHOOL ADMIN LOGIN FORM -->
        <div id="schoolLoginForm" class="login-form-container" style="display: none;">
          <div class="login-modal-header">
            <div class="login-icon"></div>
            <h2>Login Admin Sekolah</h2>
            <p>Kelola perpustakaan sekolah Anda</p>
          </div>

          <form method="post" action="public/api/login.php" class="login-modal-form">
            <input type="hidden" name="user_type" value="school">
            <div class="form-group">
              <label>Email Sekolah</label>
              <input type="email" name="email" required placeholder="admin@sekolah.com">
            </div>

            <div class="form-group">
              <label>Password</label>
              <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-modal-submit">Login</button>
          </form>

          <div class="login-modal-divider"></div>

          <p class="login-modal-footer">Belum punya akun?</p>
          <a href="#" onclick="closeLoginModal(); openRegisterModal(event);" class="btn-modal-register">Daftar
            Sekolah Baru</a>

        </div>
      </div>
    </div>

    <!-- REGISTER MODAL -->
    <div id="registerModal" class="modal" onclick="closeRegisterModal(event)">
      <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" onclick="closeRegisterModal()">&times;</button>

        <div class="login-modal-header">
          <div class="login-icon"></div>
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
            <label>Email Sekolah</label>
            <input type="email" name="admin_email" required placeholder="admin@sch.id" pattern=".*@sch\.id$"
              title="Email harus menggunakan domain @sch.id">
            <small style="color: #6b7280; display: block; margin-top: 6px; font-size: 11px;">ⓘ Email harus menggunakan
              domain @sch.id</small>
          </div>

          <div class="form-group">
            <label>Password Admin</label>
            <input type="password" name="admin_password" required placeholder="••••••••">
          </div>

          <button type="submit" class="btn-modal-submit">✓ Daftarkan Sekolah</button>
        </form>

        <div class="login-modal-divider"></div>

        <p class="login-modal-footer">Sudah punya akun?</p>
        <a href="#" onclick="closeRegisterModal(); openLoginModal(event);" class="btn-modal-register">Login di
          sini</a>
      </div>
    </div>

    <!-- EMAIL VERIFICATION MODAL -->
    <div id="verificationModal" class="modal" onclick="closeVerificationModal(event)">
      <div class="modal-content" onclick="event.stopPropagation()">
        <button class="modal-close" id="verificationCloseBtn" onclick="closeVerificationModal()" style="display: none;">&times;</button>

        <div class="login-modal-header">
          <div class="login-icon"></div>
          <h2>Verifikasi Email</h2>
          <p>Masukkan kode verifikasi yang telah dikirim ke email Anda</p>
        </div>

        <div style="background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px; margin-bottom: 20px; text-align: center;">
          <p style="font-size: 13px; color: #1e40af; margin: 0; font-weight: 600;">Email: <span id="verificationEmail" style="color: #2563eb;"></span></p>
        </div>

        <div class="verification-error" id="verificationError" style="display: none; background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px; margin-bottom: 16px; color: #dc2626; font-size: 13px; text-align: center;"></div>
        
        <div class="verification-success" id="verificationSuccess" style="display: none; background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; padding: 12px; margin-bottom: 16px; color: #16a34a; font-size: 13px; text-align: center; font-weight: 600;">Verifikasi berhasil! Mengalihkan...</div>

        <form id="verificationForm" class="login-modal-form">
          <input type="hidden" id="verificationUserId" name="user_id">

          <div class="form-group">
            <label>Kode Verifikasi (6 digit)</label>
            <div class="code-input-group" style="display: flex; gap: 8px; justify-content: center; margin-top: 12px;">
              <input type="text" class="code-input" id="code1" maxlength="1" inputmode="numeric" required style="width: 44px; height: 44px; font-size: 20px; font-weight: bold; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; transition: all 0.2s ease;">
              <input type="text" class="code-input" id="code2" maxlength="1" inputmode="numeric" required style="width: 44px; height: 44px; font-size: 20px; font-weight: bold; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; transition: all 0.2s ease;">
              <input type="text" class="code-input" id="code3" maxlength="1" inputmode="numeric" required style="width: 44px; height: 44px; font-size: 20px; font-weight: bold; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; transition: all 0.2s ease;">
              <input type="text" class="code-input" id="code4" maxlength="1" inputmode="numeric" required style="width: 44px; height: 44px; font-size: 20px; font-weight: bold; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; transition: all 0.2s ease;">
              <input type="text" class="code-input" id="code5" maxlength="1" inputmode="numeric" required style="width: 44px; height: 44px; font-size: 20px; font-weight: bold; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; transition: all 0.2s ease;">
              <input type="text" class="code-input" id="code6" maxlength="1" inputmode="numeric" required style="width: 44px; height: 44px; font-size: 20px; font-weight: bold; text-align: center; border: 2px solid #e5e7eb; border-radius: 8px; background: #f9fafb; transition: all 0.2s ease;">
            </div>
          </div>

          <div style="background: #fffbeb; border: 1px solid #fed7aa; border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 12px; color: #92400e;">
            <strong style="display: block; margin-bottom: 6px;">Tip Penting:</strong>
            <ul style="margin: 0; padding-left: 20px;">
              <li>Kode verifikasi terdiri dari 6 digit angka</li>
              <li>Kode berlaku selama 15 menit</li>
              <li>Cek folder spam jika tidak menerima email</li>
            </ul>
          </div>

          <button type="submit" class="btn-modal-submit">Verifikasi Email</button>
        </form>

        <div style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
          <p style="font-size: 12px; color: #6b7280; margin: 0 0 8px 0;">
            Kode kadaluarsa dalam <strong><span id="timerMinutes">15</span>:<span id="timerSeconds">00</span></strong>
          </p>
          <div style="display: flex; gap: 8px; justify-content: center;">
            <button type="button" class="btn-modal-register" id="resendBtn" disabled style="font-size: 13px; cursor: not-allowed; opacity: 0.5; flex: 1;">Kirim Ulang Kode</button>
            <button type="button" class="btn-modal-register" id="cancelRegistrationBtn" style="font-size: 13px; background: #ef4444; flex: 1;">Daftar Ulang</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Check pending verification on page load
      window.addEventListener('load', async () => {
        // Check localStorage for pending verification
        const pendingVerification = localStorage.getItem('pending_verification');
        
        if (pendingVerification) {
          try {
            const data = JSON.parse(pendingVerification);
            const response = await fetch('public/api/check-pending-verification.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/x-www-form-urlencoded'},
              body: 'user_id=' + data.user_id
            });
            
            const result = await response.json();
            
            if (result.success && result.has_pending) {
              // Auto-open verification modal
              openVerificationModal(result.user_id, result.email, result.verification_code);
            } else {
              // No longer pending, clear localStorage
              localStorage.removeItem('pending_verification');
            }
          } catch (error) {
            console.error('Error checking pending verification:', error);
          }
        }
        
        // Check if login is required
        if (new URLSearchParams(window.location.search).get('login_required') === '1') {
          openLoginModal();
        }
      });

      function openLoginModal(e) {
        if (e) e.preventDefault();
        document.body.style.overflow = 'hidden';

        // Check screen size: if mobile/tablet (<=768px), go directly to student form
        if (window.innerWidth <= 768) {
          document.getElementById('loginModal').style.display = 'flex';
          showLoginForm('student');
        } else {
          // Desktop: show user type selection first
          document.getElementById('userTypeModal').style.display = 'flex';
        }
      }

      function closeUserTypeModal(e) {
        if (e && e.target.id !== 'userTypeModal') return;
        clearLoginForms();
        document.getElementById('userTypeModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }

      function selectUserType(type) {
        closeUserTypeModal();
        document.getElementById('loginModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        showLoginForm(type);
      }

      function showLoginForm(type) {
        // Hide all forms
        document.getElementById('studentLoginForm').style.display = 'none';
        document.getElementById('schoolLoginForm').style.display = 'none';

        // Show selected form
        if (type === 'student') {
          document.getElementById('studentLoginForm').style.display = 'block';
        } else if (type === 'school') {
          document.getElementById('schoolLoginForm').style.display = 'block';
        }

        // Store current type
        window.currentLoginType = type;
      }

      function switchLoginType(newType) {
        event.preventDefault();
        showLoginForm(newType);
      }

      // Clear all login forms
      function clearLoginForms() {
        // Clear student login form
        const studentForm = document.getElementById('studentLoginForm');
        if (studentForm) {
          studentForm.querySelectorAll('input[type="text"], input[type="password"]').forEach(input => {
            input.value = '';
          });
        }

        // Clear school login form
        const schoolForm = document.getElementById('schoolLoginForm');
        if (schoolForm) {
          schoolForm.querySelectorAll('input[type="email"], input[type="password"]').forEach(input => {
            input.value = '';
          });
        }
      }

      function closeLoginModal(e) {
        if (e && e.target.id !== 'loginModal') return;
        clearLoginForms();
        document.getElementById('loginModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }

      // Close modal on Escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeUserTypeModal();
          closeLoginModal();
          closeRegisterModal();
        }
      });

      // Handle login form submission (works for both student and school forms)
      document.addEventListener('submit', async (e) => {
        if (e.target.classList.contains('login-modal-form') && e.target.id !== 'registerForm') {
          e.preventDefault();
          const formData = new FormData(e.target);

          try {
            const response = await fetch('public/api/login.php', {
              method: 'POST',
              body: formData
            });

            const data = await response.json();
            if (data.success) {
              // Clear form inputs
              clearLoginForms();
              // Redirect to appropriate dashboard based on user type
              const redirectUrl = data.redirect_url
                ? 'public/' + data.redirect_url
                : 'public/index.php';
              window.location.href = redirectUrl;
            } else {
              // Show detailed error message
              console.error('Login Error:', data);
              alert(data.message || 'Login gagal. Silakan coba lagi.');
            }
          } catch (error) {
            console.error('Login Request Error:', error);
            alert('Terjadi kesalahan: ' + error.message);
          }
        }
      });

      // Register Modal Functions
      function openRegisterModal(e) {
        if (e) e.preventDefault();
        // Clear form inputs sebelum membuka modal
        clearRegisterForm();
        document.getElementById('registerModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
      }

      function closeRegisterModal(e) {
        if (e && e.target.id !== 'registerModal') return;
        // Clear form inputs sebelum menutup modal
        clearRegisterForm();
        document.getElementById('registerModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }

      function clearRegisterForm() {
        // Clear all input fields di register form
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
          registerForm.reset();
          // Explicitly clear all inputs
          const inputs = registerForm.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
          inputs.forEach(input => {
            input.value = '';
            input.style.borderColor = '#e5e7eb';
            input.style.background = '#f9fafb';
          });
        }
      }

      // Handle register form submission
      document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const emailInput = document.querySelector('#registerForm input[name="admin_email"]');
        const email = emailInput.value.trim();

        // Validasi email harus @sch.id
        if (!email.endsWith('@sch.id')) {
          emailInput.setCustomValidity('Email harus menggunakan domain @sch.id');
          emailInput.reportValidity();
          return;
        }

        const formData = new FormData(e.target);

        // Debug: log semua form data
        console.log('Form Data:');
        for (let [key, value] of formData.entries()) {
          console.log(`${key}: ${value}`);
        }

        try {
          console.log('Submitting registration form...');
          const response = await fetch('public/api/register.php', {
            method: 'POST',
            body: formData
          });

          console.log('Response status:', response.status);
          console.log('Response headers:', response.headers);

          const text = await response.text();
          console.log('Raw response:', text);

          try {
            const data = JSON.parse(text);
            if (data.success) {
              // Simpan pending verification ke localStorage untuk auto-restore jika tab ditutup
              localStorage.setItem('pending_verification', JSON.stringify({
                user_id: data.user_id,
                email: data.email,
                timestamp: new Date().getTime()
              }));

              // Tutup register modal dan buka verification modal
              closeRegisterModal();

              // Log code untuk development
              console.log('✅ Registrasi berhasil!');
              console.log('User ID:', data.user_id);
              console.log('Email:', data.email);
              console.log('📧 VERIFICATION CODE:', data.verification_code);
              console.log('⚠️ Copy kode di atas dan masukkan ke form verifikasi');

              openVerificationModal(data.user_id, data.email, data.verification_code);
            } else {
              alert(data.message || 'Pendaftaran gagal');
            }
          } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response was:', text);
            alert('Kesalahan respons dari server: ' + text.substring(0, 200));
          }
        } catch (error) {
          console.error('Fetch Error:', error);
          alert('Terjadi kesalahan: ' + error.message);
        }
      });

      // ====== EMAIL VERIFICATION FUNCTIONS ======
      let verificationTimer = null;
      let remainingTime = 15 * 60; // 15 minutes in seconds

      function openVerificationModal(userId, email, verificationCode) {
        document.getElementById('verificationUserId').value = userId;
        document.getElementById('verificationEmail').textContent = email;
        document.getElementById('verificationModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';

        // Show verification code in modal for development
        if (verificationCode) {
          // Cek apakah sudah ada code display sebelumnya
          let existingDisplay = document.querySelector('.code-display-dev');
          if (existingDisplay) {
            existingDisplay.remove();
          }

          const codeDisplay = document.createElement('div');
          codeDisplay.className = 'code-display-dev';
          codeDisplay.style.cssText = 'background: #fef3c7; border: 2px solid #fbbf24; border-radius: 8px; padding: 15px; margin-bottom: 16px; text-align: center;';
          codeDisplay.innerHTML = `
            <p style="color: #92400e; margin: 0 0 8px 0; font-size: 12px; font-weight: 600;">KODE VERIFIKASI (untuk development):</p>
            <div style="font-size: 32px; font-weight: bold; color: #f59e0b; letter-spacing: 4px; font-family: 'Courier New', monospace; margin: 0;">${verificationCode}</div>
          `;
          const form = document.getElementById('verificationForm');
          form.parentNode.insertBefore(codeDisplay, form);
        }

        // Reset form
        document.querySelectorAll('.code-input').forEach(input => {
          input.value = '';
          input.style.borderColor = '#e5e7eb';
          input.style.background = '#f9fafb';
        });
        
        const errorDiv = document.getElementById('verificationError');
        errorDiv.style.display = 'none';
        errorDiv.textContent = '';
        
        document.getElementById('verificationSuccess').style.display = 'none';

        // Start countdown timer
        startVerificationTimer();
      }

      function closeVerificationModal(e) {
        if (e && e.target.id !== 'verificationModal') return;
        // Jangan biarkan user menutup modal verifikasi
        // Modal hanya bisa ditutup setelah verifikasi berhasil atau kode expired
        return false;
      }

      function startVerificationTimer() {
        remainingTime = 15 * 60;
        if (verificationTimer) clearInterval(verificationTimer);

        verificationTimer = setInterval(() => {
          remainingTime--;
          const minutes = Math.floor(remainingTime / 60);
          const seconds = remainingTime % 60;

          document.getElementById('timerMinutes').textContent = String(minutes).padStart(2, '0');
          document.getElementById('timerSeconds').textContent = String(seconds).padStart(2, '0');

          // Enable resend button at 1 minute mark
          if (remainingTime <= 60 && remainingTime > 0) {
            const resendBtn = document.getElementById('resendBtn');
            resendBtn.disabled = false;
            resendBtn.style.opacity = '1';
            resendBtn.style.cursor = 'pointer';
          }

          // Time expired
          if (remainingTime <= 0) {
            clearInterval(verificationTimer);
            const errorDiv = document.getElementById('verificationError');
            errorDiv.style.display = 'block';
            errorDiv.textContent = 'Kode verifikasi telah kadaluarsa. Silakan daftar ulang.';
            
            document.querySelectorAll('.code-input').forEach(input => {
              input.disabled = true;
              input.style.opacity = '0.5';
            });
            document.querySelector('.btn-modal-submit').disabled = true;
          }
        }, 1000);
      }

      // Handle code input auto-focus
      document.querySelectorAll('.code-input').forEach((input, index) => {
        input.addEventListener('input', (e) => {
          // Clear error on input
          const errorDiv = document.getElementById('verificationError');
          errorDiv.style.display = 'none';
          errorDiv.textContent = '';
          
          e.target.style.borderColor = '#e5e7eb';
          e.target.style.background = '#f9fafb';

          // Only allow numbers
          e.target.value = e.target.value.replace(/[^0-9]/g, '');

          // Auto focus to next input
          if (e.target.value && index < 5) {
            document.getElementById('code' + (index + 2)).focus();
          }
        });

        input.addEventListener('keydown', (e) => {
          // Handle backspace
          if (e.key === 'Backspace' && !input.value && index > 0) {
            document.getElementById('code' + index).focus();
          }
        });

        input.addEventListener('focus', (e) => {
          e.target.style.borderColor = '#2563eb';
          e.target.style.background = '#fff';
        });

        input.addEventListener('blur', (e) => {
          if (!e.target.value) {
            e.target.style.borderColor = '#e5e7eb';
            e.target.style.background = '#f9fafb';
          }
        });
      });

      // Handle verification form submission
      document.getElementById('verificationForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        // Get verification code from inputs
        const codeInputs = document.querySelectorAll('.code-input');
        const verificationCode = Array.from(codeInputs).map(input => input.value).join('');

        // Validate
        if (verificationCode.length !== 6) {
          const errorDiv = document.getElementById('verificationError');
          errorDiv.style.display = 'block';
          errorDiv.textContent = 'Silakan masukkan 6 digit kode verifikasi';
          codeInputs.forEach(input => {
            input.style.borderColor = '#ef4444';
            input.style.background = '#fee2e2';
          });
          return;
        }

        const userId = document.getElementById('verificationUserId').value;
        const submitBtn = document.querySelector('.btn-modal-submit');

        // Show loading state
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Sedang Memverifikasi...';

        try {
          const formData = new FormData();
          formData.append('user_id', userId);
          formData.append('verification_code', verificationCode);

          const response = await fetch('public/api/verify-email.php', {
            method: 'POST',
            body: formData
          });

          const data = await response.json();

          if (data.success) {
            // Show success message
            document.getElementById('verificationSuccess').style.display = 'block';
            clearInterval(verificationTimer);
            codeInputs.forEach(input => {
              input.disabled = true;
              input.style.borderColor = '#10b981';
              input.style.background = '#f0fdf4';
            });
            submitBtn.disabled = true;
            document.getElementById('resendBtn').style.display = 'none';
            document.getElementById('cancelRegistrationBtn').style.display = 'none';

            // Clear pending verification from localStorage
            localStorage.removeItem('pending_verification');

            // Redirect after 2 seconds
            setTimeout(() => {
              document.getElementById('verificationModal').style.display = 'none';
              document.body.style.overflow = 'auto';
              window.location.href = 'public/' + (data.redirect_url || 'index.php');
            }, 2000);
          } else {
            // Show error
            const errorDiv = document.getElementById('verificationError');
            errorDiv.style.display = 'block';
            errorDiv.textContent = data.message || 'Verifikasi gagal. Silakan coba lagi.';

            // Add error state to inputs
            codeInputs.forEach(input => {
              input.style.borderColor = '#ef4444';
              input.style.background = '#fee2e2';
            });

            // Reset button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
          }
        } catch (error) {
          const errorDiv = document.getElementById('verificationError');
          errorDiv.style.display = 'block';
          errorDiv.textContent = 'Terjadi kesalahan: ' + error.message;
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      });

      // Resend verification code button (implementation for future enhancement)
      document.getElementById('resendBtn').addEventListener('click', async (e) => {
        e.preventDefault();
        
        const resendBtn = document.getElementById('resendBtn');
        const originalText = resendBtn.textContent;
        resendBtn.disabled = true;
        resendBtn.textContent = 'Mengirim...';

        try {
          const userId = document.getElementById('verificationUserId').value;
          const formData = new FormData();
          formData.append('user_id', userId);
          formData.append('action', 'resend_code');

          const response = await fetch('public/api/verify-email.php', {
            method: 'POST',
            body: formData
          });

          const data = await response.json();

          if (data.success) {
            // Clear previous code display
            let existingDisplay = document.querySelector('.code-display-dev');
            if (existingDisplay) {
              existingDisplay.remove();
            }

            // Show new verification code
            if (data.verification_code) {
              const codeDisplay = document.createElement('div');
              codeDisplay.className = 'code-display-dev';
              codeDisplay.style.cssText = 'background: #fef3c7; border: 2px solid #fbbf24; border-radius: 8px; padding: 15px; margin-bottom: 16px; text-align: center;';
              codeDisplay.innerHTML = `
                <p style="color: #92400e; margin: 0 0 8px 0; font-size: 12px; font-weight: 600;">KODE VERIFIKASI BARU (untuk development):</p>
                <div style="font-size: 32px; font-weight: bold; color: #f59e0b; letter-spacing: 4px; font-family: 'Courier New', monospace; margin: 0;">${data.verification_code}</div>
              `;
              const form = document.getElementById('verificationForm');
              form.parentNode.insertBefore(codeDisplay, form);
            }

            // Clear code inputs
            document.querySelectorAll('.code-input').forEach(input => {
              input.value = '';
              input.style.borderColor = '#e5e7eb';
              input.style.background = '#f9fafb';
            });

            // Reset timer
            startVerificationTimer();
            
            // Reset button
            resendBtn.disabled = true;
            resendBtn.textContent = originalText;
            
            alert('Kode verifikasi baru telah dikirim ke email Anda');
          } else {
            const errorDiv = document.getElementById('verificationError');
            errorDiv.style.display = 'block';
            errorDiv.textContent = data.message || 'Gagal mengirim ulang kode verifikasi';
            resendBtn.disabled = true;
            resendBtn.textContent = originalText;
          }
        } catch (error) {
          const errorDiv = document.getElementById('verificationError');
          errorDiv.style.display = 'block';
          errorDiv.textContent = 'Terjadi kesalahan: ' + error.message;
          resendBtn.disabled = true;
          resendBtn.textContent = originalText;
        }
      });

      // Cancel registration button
      document.getElementById('cancelRegistrationBtn').addEventListener('click', (e) => {
        e.preventDefault();
        
        if (confirm('Apakah Anda yakin ingin membatalkan dan mendaftar ulang dengan email berbeda?')) {
          // Clear verification modal
          if (verificationTimer) clearInterval(verificationTimer);
          document.getElementById('verificationModal').style.display = 'none';
          
          // Clear pending verification from localStorage
          localStorage.removeItem('pending_verification');
          
          // Clear all forms
          clearLoginForms();
          clearRegisterForm();
          
          // Open register modal
          document.getElementById('registerModal').style.display = 'flex';
          document.body.style.overflow = 'hidden';
        }
      });

      // Stat Counter Animation
      function animateCounter(element, target, duration = 2000) {
        let current = 0;
        const increment = target / (duration / 16);
        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            current = target;
            clearInterval(timer);
          }
          element.textContent = Math.floor(current).toLocaleString('id-ID');
        }, 16);
      }

      function animateCounterPercent(element, target, duration = 2000) {
        let current = 0;
        const increment = target / (duration / 16);
        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            current = target;
            clearInterval(timer);
          }
          element.textContent = Math.floor(current) + '%';
        }, 16);
      }

      // Intersection Observer for triggering animation when section is visible
      const observerOptions = {
        threshold: 0.5
      };

      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting && !entry.target.dataset.animated) {
            entry.target.dataset.animated = 'true';
            document.querySelectorAll('.stat-number').forEach(el => {
              const target = parseInt(el.dataset.target);
              animateCounter(el, target);
            });
            document.querySelectorAll('.stat-number-percent').forEach(el => {
              const target = parseInt(el.dataset.target);
              animateCounterPercent(el, target);
            });
          }
        });
      }, observerOptions);

      const statsSection = document.querySelector('.stats');
      if (statsSection) {
        observer.observe(statsSection);
      }
    </script>
    <script src="assets/js/landing.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
      AOS.init();
    </script>
</body>

</html>