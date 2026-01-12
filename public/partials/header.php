<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$user = $_SESSION['user'] ?? null;
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = '/perpustakaan-online/public';
function _is_active($path, $current) { 
  $current = rtrim(str_replace('/perpustakaan-online/public', '', $current), '/') ?: '/';
  $path = rtrim(str_replace('/perpustakaan-online/public', '', $path), '/') ?: '/';
  return $current === $path ? ' active' : ''; 
}
?>
<header class="navbar">
  <div class="nav-container">
    <div class="nav-left">
      <a class="brand" href="<?php echo $base; ?>/" title="Kembali ke beranda">
        <svg class="brand-logo" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
        </svg>
        <span class="brand-text">Perpustakaan</span>
      </a>
      <nav class="nav-links">
        <a class="nav-link<?php echo _is_active($base.'/', $current); ?>" href="<?php echo $base; ?>/">📚 Home</a>
        <a class="nav-link<?php echo _is_active($base.'/books.php', $current); ?>" href="<?php echo $base; ?>/books.php">📖 Buku</a>
        <a class="nav-link<?php echo _is_active($base.'/members.php', $current); ?>" href="<?php echo $base; ?>/members.php">👥 Anggota</a>
        <a class="nav-link<?php echo _is_active($base.'/borrows.php', $current); ?>" href="<?php echo $base; ?>/borrows.php">🔄 Pinjam</a>
        <?php if ($user): ?>
          <a class="nav-link<?php echo _is_active($base.'/settings.php', $current); ?>" href="<?php echo $base; ?>/settings.php">⚙️ Pengaturan</a>
        <?php endif; ?>
      </nav>
    </div>

    <div class="nav-right">
      <div class="search-box">
        <input type="search" placeholder="Cari buku..." aria-label="Cari buku" />
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"></circle>
          <path d="m21 21-4.35-4.35"></path>
        </svg>
      </div>

      <?php if ($user): ?>
        <div class="user-menu-wrapper">
          <button class="user-btn" aria-haspopup="true" aria-expanded="false" title="Menu pengguna">
            <div class="avatar" title="<?php echo htmlspecialchars($user['name']); ?>">
              <?php echo htmlspecialchars(strtoupper(substr($user['name'],0,1))); ?>
            </div>
            <span class="user-name"><?php echo htmlspecialchars(substr($user['name'], 0, 20)); ?></span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div class="user-dropdown" role="menu">
            <a class="dropdown-item" href="<?php echo $base; ?>/dashboard.php">
              <span>📊</span>
              <span>Dashboard</span>
            </a>
            <a class="dropdown-item" href="<?php echo $base; ?>/settings.php">
              <span>⚙️</span>
              <span>Pengaturan</span>
            </a>
            <hr style="margin: 8px 0; border: none; border-top: 1px solid #e0e0e0;">
            <a class="dropdown-item danger" href="<?php echo $base; ?>/logout.php">
              <span>🚪</span>
              <span>Logout</span>
            </a>
          </div>
        </div>
      <?php else: ?>
        <a class="btn btn-small" href="<?php echo $base; ?>/login.php">Login</a>
        <a class="btn btn-small btn-secondary" href="<?php echo $base; ?>/register.php">Daftar</a>
      <?php endif; ?>

      <button class="nav-toggle" aria-label="Toggle navigation menu" title="Toggle menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </div>
</header>

<style>
header.navbar {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  position: sticky;
  top: 0;
  z-index: 1000;
  padding: 0;
}

.nav-container {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  gap: 20px;
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 30px;
  flex: 1;
}

.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
  text-decoration: none;
  font-weight: 600;
  font-size: 18px;
  white-space: nowrap;
  padding: 4px 0;
  transition: opacity 0.2s;
}

.brand:hover {
  opacity: 0.9;
}

.brand-logo {
  width: 28px;
  height: 28px;
  color: white;
}

.brand-text {
  color: white;
  letter-spacing: -0.5px;
}

.nav-links {
  display: flex;
  gap: 8px;
  align-items: center;
  flex-wrap: wrap;
}

.nav-link {
  color: rgba(255,255,255,0.85);
  text-decoration: none;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  white-space: nowrap;
}

.nav-link:hover {
  background: rgba(255,255,255,0.2);
  color: white;
}

.nav-link.active {
  background: rgba(255,255,255,0.25);
  color: white;
  border-bottom: 2px solid white;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}

.search-box {
  display: flex;
  align-items: center;
  background: rgba(255,255,255,0.15);
  border-radius: 20px;
  padding: 6px 12px;
  border: 1px solid rgba(255,255,255,0.2);
  transition: all 0.2s;
}

.search-box:hover,
.search-box:focus-within {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255,255,255,0.3);
}

.search-box input {
  background: none;
  border: none;
  color: white;
  padding: 0;
  outline: none;
  font-size: 14px;
  width: 150px;
}

.search-box input::placeholder {
  color: rgba(255,255,255,0.7);
}

.search-box svg {
  color: rgba(255,255,255,0.7);
  margin-left: 6px;
}

.user-menu-wrapper {
  position: relative;
}

.user-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.2);
  color: white;
  padding: 6px 10px;
  border-radius: 20px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s;
  white-space: nowrap;
}

.user-btn:hover {
  background: rgba(255,255,255,0.25);
  border-color: rgba(255,255,255,0.3);
}

.avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(255,255,255,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 12px;
  color: white;
}

.user-name {
  font-weight: 500;
}

.user-btn svg {
  width: 12px;
  height: 12px;
  transition: transform 0.2s;
}

.user-btn[aria-expanded="true"] svg {
  transform: rotate(180deg);
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 16px rgba(0,0,0,0.15);
  min-width: 180px;
  padding: 8px 0;
  display: none;
  z-index: 1001;
}

.user-dropdown.open {
  display: block;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  color: #333;
  text-decoration: none;
  font-size: 14px;
  transition: background 0.2s;
  white-space: nowrap;
}

.dropdown-item:hover {
  background: #f5f7fb;
}

.dropdown-item.danger:hover {
  background: #ffe5e5;
  color: #d32f2f;
}

.dropdown-item span:first-child {
  font-size: 16px;
}

.btn-small {
  padding: 6px 14px;
  font-size: 13px;
  border-radius: 6px;
}

.btn-secondary {
  background: rgba(255,255,255,0.2);
  color: white;
  border: 1px solid rgba(255,255,255,0.3);
}

.btn-secondary:hover {
  background: rgba(255,255,255,0.3);
}

.nav-toggle {
  display: none;
  flex-direction: column;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
}

.nav-toggle span {
  width: 24px;
  height: 2px;
  background: white;
  border-radius: 2px;
  transition: all 0.3s;
}

@media (max-width: 900px) {
  .nav-links {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    flex-direction: column;
    background: #0a58ca;
    padding: 12px 0;
    gap: 0;
    border-top: 1px solid rgba(255,255,255,0.1);
  }

  .nav-links.open {
    display: flex;
  }

  .nav-link {
    padding: 12px 20px;
    border-radius: 0;
    border: none;
  }

  .nav-link.active {
    border: none;
    border-left: 3px solid white;
    padding-left: 17px;
  }

  .nav-toggle {
    display: flex;
  }

  .search-box {
    width: 100%;
    order: 3;
  }

  .search-box input {
    width: 100%;
  }

  .nav-container {
    flex-wrap: wrap;
  }

  .nav-left {
    order: 1;
    width: 100%;
    gap: 0;
    justify-content: space-between;
  }

  .nav-right {
    order: 2;
    width: 100%;
    gap: 8px;
  }
}

@media (max-width: 600px) {
  .brand-text {
    display: none;
  }

  .nav-container {
    padding: 10px 12px;
  }

  .search-box input {
    width: 80px;
  }

  .user-name {
    display: none;
  }

  .user-btn {
    padding: 6px 8px;
  }

  .nav-link {
    font-size: 13px;
  }
}
</style>

<script>
(function(){
  var toggle = document.querySelector('.nav-toggle');
  var links = document.querySelector('.nav-links');
  if(toggle && links) {
    toggle.addEventListener('click', function(e){
      e.preventDefault();
      links.classList.toggle('open');
      toggle.classList.toggle('open');
    });
    document.addEventListener('click', function(e){
      if(!toggle.contains(e.target) && !links.contains(e.target)) {
        links.classList.remove('open');
        toggle.classList.remove('open');
      }
    });
  }

  var userBtn = document.querySelector('.user-btn');
  var dropdown = document.querySelector('.user-dropdown');
  if(userBtn && dropdown){
    userBtn.addEventListener('click', function(e){
      e.preventDefault();
      e.stopPropagation();
      var open = dropdown.classList.toggle('open');
      userBtn.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', function(e){
      if(!userBtn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
        userBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }
})();
</script>