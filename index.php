<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

$nowShowing = $pdo->query("SELECT * FROM movies WHERE status='showing' ORDER BY id DESC LIMIT 10")->fetchAll();
$comingSoon = $pdo->query("SELECT * FROM movies WHERE status='coming_soon' ORDER BY id DESC LIMIT 6")->fetchAll();
$banners    = $pdo->query("SELECT * FROM movies WHERE status='showing' AND banner != '' ORDER BY id DESC LIMIT 3")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookMyShow - Book Movie Tickets Online</title>
    <meta name="description" content="Book movie tickets online. Browse now showing and coming soon movies.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Nunito', Arial, sans-serif; background: #f5f5f5; color: #333; }

        /* ─── NAVBAR ─── */
        .navbar {
            background: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.10);
            position: sticky; top: 0; z-index: 200;
            height: 60px;
            display: flex; align-items: center; gap: 18px;
            padding: 0 28px;
        }
        .navbar .logo { height: 34px; }
        .search-box {
            display: flex; align-items: center;
            border: 1.5px solid #e0e0e0; border-radius: 6px;
            overflow: hidden; background: #fafafa;
            transition: border-color .2s, box-shadow .2s;
        }
        .search-box:focus-within {
            border-color: #dc3546;
            box-shadow: 0 0 0 3px rgba(220,53,70,.10);
        }
        .search-box input {
            border: none; outline: none; background: transparent;
            padding: 8px 12px; font-size: 14px; color: #333;
            width: 230px; font-family: inherit;
        }
        .search-box button {
            background: none; border: none; cursor: pointer;
            padding: 8px 12px; color: #888; font-size: 16px;
            transition: color .2s;
        }
        .search-box button:hover { color: #dc3546; }
        .nav-links { display: flex; gap: 4px; margin-left: auto; align-items: center; list-style: none; }
        .nav-links a {
            text-decoration: none; color: #444; font-size: 14px; font-weight: 600;
            padding: 6px 12px; border-radius: 6px;
            transition: color .2s, background .2s;
        }
        .nav-links a:hover { color: #dc3546; background: #fff5f6; }
        .btn-signin {
            background: #dc3546 !important; color: #fff !important;
            padding: 8px 20px !important; border-radius: 20px !important;
            font-weight: 700 !important;
            transition: background .2s, transform .15s, box-shadow .2s !important;
            box-shadow: 0 2px 8px rgba(220,53,70,.30);
        }
        .btn-signin:hover {
            background: #b52535 !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(220,53,70,.40) !important;
        }

        /* ─── HERO SLIDESHOW ─── */
        .slideshow-wrap { max-width: 1100px; margin: 24px auto 0; padding: 0 20px; }
        .slides-container {
            position: relative; border-radius: 12px; overflow: hidden;
            height: 420px;
            box-shadow: 0 8px 32px rgba(0,0,0,.18);
        }
        .slide {
            position: absolute; inset: 0;
            opacity: 0; transition: opacity .65s ease;
            pointer-events: none;
        }
        .slide.active { opacity: 1; pointer-events: auto; }
        .slide > a { display: block; width: 100%; height: 100%; }
        .slide img {
            width: 100%; height: 100%;
            object-fit: cover;          /* ← NO black bars */
            display: block;
        }
        .slide-gradient {
            position: absolute; inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,.72) 0%, rgba(0,0,0,.08) 55%, transparent 100%);
            pointer-events: none;
        }
        .slide-info {
            position: absolute; bottom: 0; left: 0; right: 0;
            padding: 24px 28px 22px;
            pointer-events: none;
        }
        .slide-info h3 {
            font-size: 24px; font-weight: 800; color: #fff;
            text-shadow: 0 2px 8px rgba(0,0,0,.5); margin-bottom: 10px;
        }
        .slide-book {
            display: inline-block; pointer-events: auto;
            background: #dc3546; color: #fff;
            padding: 9px 24px; border-radius: 22px;
            font-size: 13px; font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(220,53,70,.45);
            transition: background .2s, transform .2s;
        }
        .slide-book:hover { background: #b52535; transform: translateY(-2px); }
        .slide-counter {
            position: absolute; top: 14px; right: 16px;
            background: rgba(0,0,0,.45); color: #fff;
            font-size: 12px; font-weight: 600; padding: 4px 10px;
            border-radius: 12px; pointer-events: none;
        }
        .prev-btn, .next-btn {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(255,255,255,.20); backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,.35); color: #fff;
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 17px; z-index: 10;
            transition: background .2s, transform .2s;
        }
        .prev-btn:hover, .next-btn:hover {
            background: #dc3546; border-color: #dc3546;
            transform: translateY(-50%) scale(1.10);
        }
        .prev-btn { left: 14px; }
        .next-btn { right: 14px; }
        .dots { display: flex; justify-content: center; gap: 7px; margin: 12px 0 4px; }
        .dot {
            width: 9px; height: 9px; border-radius: 50%;
            background: #ccc; cursor: pointer;
            transition: background .3s, transform .3s, width .3s;
        }
        .dot.active { background: #dc3546; transform: scale(1.3); width: 22px; border-radius: 5px; }

        /* ─── SECTIONS ─── */
        .section { max-width: 1100px; margin: 38px auto; padding: 0 20px; }
        .section-head {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px;
        }
        .section-head h2 { font-size: 21px; font-weight: 800; color: #222; }
        .section-head h2 span {
            display: inline-block; width: 4px; height: 20px;
            background: #dc3546; border-radius: 2px;
            margin-right: 10px; vertical-align: middle;
        }
        .see-all {
            font-size: 13px; color: #dc3546; font-weight: 700;
            text-decoration: none; padding: 5px 10px; border-radius: 5px;
            transition: background .2s;
        }
        .see-all:hover { background: #fff5f6; }

        /* ─── MOVIE GRID ─── */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(165px, 1fr));
            gap: 18px;
        }
        .movie-card {
            text-decoration: none; color: #222;
            background: #fff; border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            transition: transform .28s cubic-bezier(.25,.8,.25,1), box-shadow .28s;
            opacity: 0; animation: fadeUp .45s forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        /* stagger */
        .movie-grid .movie-card:nth-child(1)  { animation-delay: .04s; }
        .movie-grid .movie-card:nth-child(2)  { animation-delay: .08s; }
        .movie-grid .movie-card:nth-child(3)  { animation-delay: .12s; }
        .movie-grid .movie-card:nth-child(4)  { animation-delay: .16s; }
        .movie-grid .movie-card:nth-child(5)  { animation-delay: .20s; }
        .movie-grid .movie-card:nth-child(6)  { animation-delay: .24s; }
        .movie-grid .movie-card:nth-child(7)  { animation-delay: .28s; }
        .movie-grid .movie-card:nth-child(8)  { animation-delay: .32s; }
        .movie-grid .movie-card:nth-child(9)  { animation-delay: .36s; }
        .movie-grid .movie-card:nth-child(10) { animation-delay: .40s; }
        .movie-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(0,0,0,.16);
        }
        .poster-wrap {
            position: relative; height: 242px; overflow: hidden;
        }
        .poster-wrap img {
            width: 100%; height: 100%; object-fit: cover; display: block;
            transition: transform .45s ease;
        }
        .movie-card:hover .poster-wrap img { transform: scale(1.06); }
        .poster-hover {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.52);
            display: flex; align-items: flex-end; justify-content: center;
            padding-bottom: 18px;
            opacity: 0; transition: opacity .28s;
        }
        .movie-card:hover .poster-hover { opacity: 1; }
        .book-pill {
            background: #dc3546; color: #fff;
            padding: 8px 22px; border-radius: 20px;
            font-size: 13px; font-weight: 700;
            transform: translateY(10px);
            transition: transform .28s;
            white-space: nowrap;
        }
        .movie-card:hover .book-pill { transform: translateY(0); }

        .card-body { padding: 11px 12px 13px; }
        .card-title {
            font-size: 13.5px; font-weight: 700; margin-bottom: 3px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .card-meta { font-size: 11.5px; color: #888; margin-bottom: 6px; }
        .badge {
            display: inline-block; font-size: 11px; font-weight: 700;
            padding: 3px 9px; border-radius: 10px;
        }
        .badge.showing { background: #eafaf1; color: #27ae60; border: 1px solid #c3e6cb; }
        .badge.soon    { background: #fff8e1; color: #e67e22; border: 1px solid #ffe0a3; }

        /* ─── FOOTER ─── */
        footer {
            background: #1a1a2e; color: #aaa;
            text-align: center; padding: 26px 20px;
            margin-top: 60px; font-size: 13px;
        }
        footer a { color: #dc3546; text-decoration: none; }
        footer a:hover { text-decoration: underline; }

        .section.empty { text-align: center; padding: 60px 0; color: #aaa; }
        a { color: #dc3546; text-decoration: none; }
    </style>
</head>
<body>

<div class="navbar">
    <a href="index.php"><img src="a.png" alt="BookMyShow" class="logo"></a>
    <form class="search-box" action="movies.php" method="GET">
        <input type="search" name="q" placeholder="Search movies..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <button type="submit">&#128269;</button>
    </form>
    <ul class="nav-links">
        <li><a href="movies.php">Movies</a></li>
        <?php if (isLoggedIn()): ?>
            <li><a href="bookings.php">My Bookings</a></li>
            <?php if (isAdmin()): ?>
                <li><a href="admin/admin_dashboard.php">Admin</a></li>
            <?php endif; ?>
            <li><a href="logout.php" class="btn-signin">Sign out</a></li>
        <?php else: ?>
            <li><a href="login.php">Sign in</a></li>
            <li><a href="register.php" class="btn-signin">Register</a></li>
        <?php endif; ?>
    </ul>
</div>

<?php if ($banners): ?>
<div class="slideshow-wrap">
    <div class="slides-container">
        <?php foreach ($banners as $i => $b): ?>
            <div class="slide <?= $i === 0 ? 'active' : '' ?>">
                <a href="movie.php?id=<?= $b['id'] ?>">
                    <img src="<?= $b['banner'] ? 'assets/uploads/banners/' . htmlspecialchars($b['banner']) : 'assets/placeholder.jpg' ?>"
                         alt="<?= htmlspecialchars($b['title']) ?>">
                </a>
                <div class="slide-gradient"></div>
                <div class="slide-info">
                    <h3><?= htmlspecialchars($b['title']) ?></h3>
                    <a href="movie.php?id=<?= $b['id'] ?>" class="slide-book">Book Tickets</a>
                </div>
                <div class="slide-counter"><?= $i + 1 ?> / <?= count($banners) ?></div>
            </div>
        <?php endforeach; ?>
        <button class="prev-btn" aria-label="Previous">&#10094;</button>
        <button class="next-btn" aria-label="Next">&#10095;</button>
    </div>
    <div class="dots">
        <?php foreach ($banners as $i => $b): ?>
            <span class="dot <?= $i === 0 ? 'active' : '' ?>"></span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($nowShowing): ?>
<div class="section">
    <div class="section-head">
        <h2><span></span>Now Showing</h2>
        <a href="movies.php?status=showing" class="see-all">See all →</a>
    </div>
    <div class="movie-grid">
        <?php foreach ($nowShowing as $m): ?>
            <a class="movie-card" href="movie.php?id=<?= $m['id'] ?>">
                <div class="poster-wrap">
                    <img src="<?= $m['poster'] ? 'assets/uploads/posters/' . htmlspecialchars($m['poster']) : 'assets/placeholder.jpg' ?>"
                         alt="<?= htmlspecialchars($m['title']) ?>">
                    <div class="poster-hover">
                        <span class="book-pill">Book Tickets</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>
                    <div class="card-meta"><?= htmlspecialchars($m['genre']) ?></div>
                    <span class="badge showing">Now Showing</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($comingSoon): ?>
<div class="section">
    <div class="section-head">
        <h2><span></span>Coming Soon</h2>
        <a href="movies.php?status=coming_soon" class="see-all">See all →</a>
    </div>
    <div class="movie-grid">
        <?php foreach ($comingSoon as $m): ?>
            <a class="movie-card" href="movie.php?id=<?= $m['id'] ?>">
                <div class="poster-wrap">
                    <img src="<?= $m['poster'] ? 'assets/uploads/posters/' . htmlspecialchars($m['poster']) : 'assets/placeholder.jpg' ?>"
                         alt="<?= htmlspecialchars($m['title']) ?>">
                    <div class="poster-hover">
                        <span class="book-pill" style="background:#e67e22;">Coming Soon</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($m['title']) ?></div>
                    <div class="card-meta"><?= htmlspecialchars($m['genre']) ?></div>
                    <span class="badge soon">Coming Soon</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!$nowShowing && !$comingSoon): ?>
<div class="section empty">
    <p>No movies yet. <a href="admin/add_movie.php">Add from admin</a>.</p>
</div>
<?php endif; ?>

<footer>
    <p>&copy; <?= date('Y') ?> BookMyShow. All rights reserved.
        &nbsp;|&nbsp; <a href="mailto:info@bookmyshow.com">info@bookmyshow.com</a></p>
</footer>

<script>
(function () {
    var idx    = 0;
    var slides = document.querySelectorAll('.slide');
    var dots   = document.querySelectorAll('.dot');
    if (!slides.length) return;

    function show(n) {
        slides[idx].classList.remove('active');
        if (dots[idx]) dots[idx].classList.remove('active');
        idx = (n + slides.length) % slides.length;
        slides[idx].classList.add('active');
        if (dots[idx]) dots[idx].classList.add('active');
    }

    document.querySelector('.prev-btn').onclick = function () { show(idx - 1); };
    document.querySelector('.next-btn').onclick = function () { show(idx + 1); };
    dots.forEach(function (d, i) { d.onclick = function () { show(i); }; });
    setInterval(function () { show(idx + 1); }, 4500);
})();
</script>
</body>
</html>