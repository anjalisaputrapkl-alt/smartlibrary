<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5, user-scalable=yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
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
      <div class="container" data-aos="zoom-in">
        <h2>Untuk Siapa Sistem Ini?</h2>
        <p class="microcopy">Dirancang untuk memenuhi kebutuhan semua pihak yang terlibat dalam ekosistem perpustakaan
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
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
      AOS.init();
    </script>
</body>

</html>