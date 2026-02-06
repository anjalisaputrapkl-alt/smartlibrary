<?php
session_start();
$pdo = require __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/MemberHelper.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: /?login_required=1');
    exit;
}

$user = $_SESSION['user'];
$book_id = $_GET['id'] ?? null;

if (!$book_id) {
    header('Location: student-dashboard.php');
    exit;
}

// Get book details
$bookStmt = $pdo->prepare('SELECT * FROM books WHERE id = :id AND school_id = :school_id');
$bookStmt->execute(['id' => $book_id, 'school_id' => $user['school_id']]);
$book = $bookStmt->fetch();

if (!$book) {
    header('Location: student-dashboard.php');
    exit;
}

// Get ratings summary
$summaryStmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as r5,
        COUNT(CASE WHEN rating = 4 THEN 1 END) as r4,
        COUNT(CASE WHEN rating = 3 THEN 1 END) as r3,
        COUNT(CASE WHEN rating = 2 THEN 1 END) as r2,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as r1
    FROM rating_buku 
    WHERE id_buku = :book_id
');
$summaryStmt->execute(['book_id' => $book_id]);
$summary = $summaryStmt->fetch();

$total_reviews = $summary['total_reviews'] ?: 0;
$avg_rating = round($summary['avg_rating'] ?: 0, 1);

// Get all comments
$commentsStmt = $pdo->prepare('
    SELECT r.*, u.name, u.role, s.foto
    FROM rating_buku r
    JOIN users u ON r.id_user = u.id
    LEFT JOIN siswa s ON u.nisn = s.nisn
    WHERE r.id_buku = :book_id
    ORDER BY r.created_at DESC
');
$commentsStmt->execute(['book_id' => $book_id]);
$comments = $commentsStmt->fetchAll();

$pageTitle = 'Rating & Komentar: ' . $book['title'];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?> - SmartLibrary</title>
    <script src="../assets/js/db-theme-loader.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <style>
        :root {
            --dark-navy: #0A1A4F;
            --neon-blue: #3A7FF2;
            --neon-glow: rgba(58, 127, 242, 0.3);
            --card-bg: #112255;
            --border-navy: #1E2D6D;
            --text-white: #FFFFFF;
            --text-gray: #A0AEC0;
        }

        body {
            background: #060E2E;
            color: var(--text-white);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }

        .rating-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--neon-blue);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 24px;
            transition: all 0.2s;
        }

        .book-header-card {
            background: var(--card-bg);
            border: 1px solid var(--border-navy);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            gap: 24px;
            margin-bottom: 30px;
        }

        .book-img-wrapper {
            width: 120px;
            height: 180px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            background: #1A264F;
        }

        .book-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-title-h1 {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .book-author-p {
            color: var(--text-gray);
            font-size: 14px;
            margin-bottom: 16px;
        }

        /* Stats grid */
        .rating-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .rating-grid { grid-template-columns: 1fr; }
        }

        .summary-card {
            background: var(--card-bg);
            border: 1px solid var(--border-navy);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
        }

        .avg-number {
            font-size: 64px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 0 0 20px var(--neon-glow);
        }

        .stars-display {
            color: #FFD700;
            font-size: 24px;
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-bottom: 10px;
        }

        .bars-card {
            background: var(--card-bg);
            border: 1px solid var(--border-navy);
            border-radius: 20px;
            padding: 30px;
        }

        .rating-bar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .bar-bg {
            flex: 1;
            height: 8px;
            background: #1A264F;
            border-radius: 4px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            background: var(--neon-blue);
            box-shadow: 0 0 10px var(--neon-glow);
        }

        /* Radio Star Rating CSS - THE MOST ROBUST WAY */
        .star-rating-form {
            background: var(--card-bg);
            border: 1px solid var(--border-navy);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            box-shadow: 0 0 30px var(--neon-glow);
            text-align: center;
        }

        .star-group {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .star-group input {
            display: none !important;
        }

        .star-group label {
            font-size: 64px;
            color: #2D3748; /* Brighter gray for inactive */
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-shadow: 0 0 5px rgba(0,0,0,0.5);
        }

        /* Hover & Checked Magic */
        .star-group label:hover,
        .star-group label:hover ~ label,
        .star-group input:checked ~ label {
            color: #FFD700;
            transform: scale(1.2) rotate(5deg);
            filter: drop-shadow(0 0 15px rgba(255, 215, 0, 0.6));
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.4);
        }

        .rating-help-text {
            color: var(--neon-blue);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 1px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .comment-textarea {
            width: 100%;
            background: #060E2E;
            border: 1px solid var(--border-navy);
            border-radius: 12px;
            padding: 16px;
            color: white;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            margin-bottom: 20px;
            outline: none;
            transition: all 0.3s;
        }

        .comment-textarea:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 15px var(--neon-glow);
        }

        .btn-submit-rating {
            background: transparent;
            border: 2px solid var(--neon-blue);
            color: var(--neon-blue);
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-submit-rating:hover {
            background: var(--neon-blue);
            color: white;
            box-shadow: 0 0 20px var(--neon-glow);
        }

        /* Comment List */
        .comment-card {
            background: #112255;
            border: 1px solid var(--border-navy);
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .comment-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #1A264F;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--neon-blue);
            font-weight: 700;
        }

        .comment-rating {
            color: #FFD700;
            font-size: 12px;
            margin-left: auto;
        }

        .main-container-rating {
            margin-left: 240px;
            min-height: 100vh;
            background: #060E2E;
            padding-top: 80px;
        }

        @media (max-width: 768px) {
            .main-container-rating { margin-left: 0; }
        }
    </style>
</head>
<body>
    <?php include 'partials/student-sidebar.php'; ?>
    <?php include 'partials/student-header.php'; ?>

    <div class="main-container-rating">
        <div class="rating-container">
            <a href="student-dashboard.php" class="back-link">
                <iconify-icon icon="mdi:arrow-left"></iconify-icon> Kembali ke Dashboard
            </a>

            <!-- Book Summary -->
            <div class="book-header-card">
                <div class="book-img-wrapper">
                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="../img/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Cover">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
                            <iconify-icon icon="mdi:book" width="48" color="#3A7FF2"></iconify-icon>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="book-info-main">
                    <h1 class="book-title-h1"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <p class="book-author-p">Oleh <?php echo htmlspecialchars($book['author'] ?: '-'); ?></p>
                    <span style="background: rgba(58, 127, 242, 0.1); color: var(--neon-blue); padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                        <?php echo htmlspecialchars($book['category'] ?: 'Umum'); ?>
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="rating-grid">
                <div class="summary-card">
                    <div class="avg-number"><?php echo $avg_rating; ?></div>
                    <div class="stars-display">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <iconify-icon icon="<?php echo $i <= round($avg_rating) ? 'mdi:star' : 'mdi:star-outline'; ?>"></iconify-icon>
                        <?php endfor; ?>
                    </div>
                    <div style="color: var(--text-gray); font-size: 14px;"><?php echo $total_reviews; ?> Ulasan</div>
                </div>

                <div class="bars-card">
                    <?php 
                    $rs = [5 => $summary['r5'], 4 => $summary['r4'], 3 => $summary['r3'], 2 => $summary['r2'], 1 => $summary['r1']];
                    foreach($rs as $s => $c): 
                        $p = $total_reviews > 0 ? ($c / $total_reviews) * 100 : 0;
                    ?>
                        <div class="rating-bar-item">
                            <span style="font-size: 13px; width: 15px;"><?php echo $s; ?></span>
                            <div class="bar-bg"><div class="bar-fill" style="width: <?php echo $p; ?>%"></div></div>
                            <span style="font-size: 12px; color: var(--text-gray); width: 25px;"><?php echo (int)$c; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- FORM SELECTION - RADIO BASED -->
            <div class="star-rating-form">
                <span class="rating-help-text">Klik bintang untuk memberi rating</span>
                <form id="ratingForm" action="api/submit-rating.php" method="POST">
                    <input type="hidden" name="book_id" value="<?php echo $book_id; ?>">
                    
                    <div class="star-group">
                        <input type="radio" id="st5" name="rating" value="5">
                        <label for="st5">★</label>
                        
                        <input type="radio" id="st4" name="rating" value="4">
                        <label for="st4">★</label>
                        
                        <input type="radio" id="st3" name="rating" value="3">
                        <label for="st3">★</label>
                        
                        <input type="radio" id="st2" name="rating" value="2">
                        <label for="st2">★</label>
                        
                        <input type="radio" id="st1" name="rating" value="1">
                        <label for="st1">★</label>
                    </div>

                    <textarea name="comment" class="comment-textarea" placeholder="Bagikan pendapat Anda tentang buku ini..." required></textarea>
                    
                    <button type="submit" class="btn-submit-rating">
                        <iconify-icon icon="mdi:send"></iconify-icon> Kirim Ulasan
                    </button>
                </form>
            </div>

            <!-- Comments List -->
            <div class="comments-section">
                <h3 style="margin-bottom: 24px;">Semua Ulasan</h3>
                <?php if (empty($comments)): ?>
                    <p style="text-align: center; color: var(--text-gray);">Belum ada ulasan.</p>
                <?php else: ?>
                    <?php foreach ($comments as $cm): ?>
                        <div class="comment-card">
                            <div class="comment-header">
                                <div class="user-avatar"><?php echo strtoupper(substr($cm['name'], 0, 1)); ?></div>
                                <div>
                                    <div style="font-weight: 700; font-size: 14px;"><?php echo htmlspecialchars($cm['name']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-gray);"><?php echo date('d M Y', strtotime($cm['created_at'])); ?></div>
                                </div>
                                <div class="comment-rating">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <iconify-icon icon="<?php echo $i <= $cm['rating'] ? 'mdi:star' : 'mdi:star-outline'; ?>"></iconify-icon>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div style="font-size: 14px; color: #E2E8F0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($cm['komentar'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.getElementById('ratingForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const rating = this.querySelector('input[name="rating"]:checked');
            if (!rating) {
                Swal.fire({ icon: 'warning', title: 'Pilih Rating', text: 'Silakan pilih bintang dulu bro!', background: '#112255', color: '#fff' });
                return;
            }

            const formData = new FormData(this);
            try {
                const res = await fetch(this.action, { method: 'POST', body: formData });
                const json = await res.json();
                if (json.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: json.message, background: '#112255', color: '#fff' }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: json.message, background: '#112255', color: '#fff' });
                }
            } catch (err) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal kirim data', background: '#112255', color: '#fff' });
            }
        });
    </script>
</body>
</html>
