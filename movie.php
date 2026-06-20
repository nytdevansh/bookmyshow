<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

$id    = (int)($_GET['id'] ?? 0);
$stmt  = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) {
    header('Location: movies.php');
    exit;
}

$stmt2 = $pdo->prepare("SELECT * FROM shows WHERE movie_id = ? AND show_date >= CURDATE() ORDER BY show_date, show_time");
$stmt2->execute([$id]);
$shows = $stmt2->fetchAll();

$byDate = [];
foreach ($shows as $s) $byDate[$s['show_date']][] = $s;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($movie['title']) ?> - BookMyShow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: #f5f5f5;
            color: #1a1a2e;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a { text-decoration: none; color: inherit; }

        /* ── NAVBAR ── */
        .navbar {
            display: flex;
            align-items: center;
            gap: 22px;
            padding: 0 32px;
            height: 64px;
            background: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,.10);
            position: sticky;
            top: 0;
            z-index: 200;
        }

        .navbar .logo img { height: 36px; display: block; }

        .search-box {
            display: flex;
            align-items: center;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            background: #fafafa;
            transition: border-color .2s;
        }

        .search-box:focus-within {
            border-color: #dc3546;
            background: #fff;
        }

        .search-box input {
            border: none;
            outline: none;
            padding: 8px 14px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            width: 240px;
            background: transparent;
            color: #333;
        }

        .search-box button {
            background: none;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            color: #888;
            font-size: 15px;
            transition: color .2s;
        }

        .search-box button:hover { color: #dc3546; }

        .nav-links {
            display: flex;
            gap: 6px;
            margin-left: auto;
            align-items: center;
            list-style: none;
        }

        .nav-links a {
            font-size: 14px;
            font-weight: 600;
            color: #444;
            padding: 7px 13px;
            border-radius: 6px;
            transition: color .2s, background .2s;
        }

        .nav-links a:hover {
            color: #dc3546;
            background: #fff0f1;
        }

        .btn-signin {
            background: #dc3546 !important;
            color: #fff !important;
            border-radius: 22px !important;
            padding: 8px 20px !important;
            font-weight: 700 !important;
            transition: background .2s, transform .15s !important;
        }

        .btn-signin:hover {
            background: #b52a38 !important;
            transform: translateY(-1px);
        }

        /* ── MAIN WRAPPER ── */
        .content-wrapper {
            max-width: 960px;
            margin: 38px auto;
            padding: 0 20px;
            flex: 1;
        }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            font-size: 13px;
            color: #888;
            margin-bottom: 20px;
        }

        .breadcrumb a { color: #dc3546; font-weight: 600; }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb span { margin: 0 6px; }

        /* ── MOVIE CARD ── */
        .movie-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            padding: 32px;
            display: flex;
            gap: 36px;
            align-items: flex-start;
            animation: fadeSlideUp .5s ease both;
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── POSTER ── */
        .poster-wrap { flex-shrink: 0; }

        .poster-wrap img {
            width: 220px;
            height: 320px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 8px 28px rgba(0,0,0,.22);
            display: block;
        }

        /* ── MOVIE INFO ── */
        .movie-info { flex: 1; min-width: 0; }

        /* Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .6px;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 12px;
        }

        .status-badge.showing {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .status-badge.soon {
            background: #fff8e1;
            color: #e65100;
            border: 1px solid #ffcc80;
        }

        .status-badge .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-badge.showing .dot { background: #2e7d32; }
        .status-badge.soon .dot   { background: #e65100; }

        /* Title */
        .movie-info h1 {
            font-size: 30px;
            font-weight: 900;
            color: #1a1a2e;
            margin-bottom: 14px;
            line-height: 1.2;
        }

        /* Tags */
        .tags-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 18px;
        }

        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            background: #f0f0f0;
            border-radius: 20px;
            padding: 4px 12px;
        }

        .tag-pill .icon { font-size: 14px; }

        /* Divider */
        .info-divider {
            width: 100%;
            height: 1px;
            background: #f0f0f0;
            margin: 16px 0;
        }

        /* Description */
        .movie-info .description {
            font-size: 14px;
            color: #555;
            line-height: 1.75;
            margin-bottom: 20px;
        }

        /* Trailer */
        .trailer-wrap { margin-top: 4px; }

        .trailer-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .7px;
            color: #aaa;
            margin-bottom: 8px;
        }

        .trailer-wrap iframe {
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,.12);
            display: block;
            max-width: 100%;
        }

        /* ── BOOK TICKETS CARD ── */
        .book-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            margin-top: 28px;
            overflow: hidden;
            animation: fadeSlideUp .55s .1s ease both;
        }

        .book-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px 28px;
            border-bottom: 2px solid #f5f5f5;
        }

        .book-card-header .ticket-icon { font-size: 22px; }

        .book-card-header h2 {
            font-size: 20px;
            font-weight: 800;
            color: #1a1a2e;
        }

        .book-card-body { padding: 24px 28px; }

        /* Date group */
        .date-group { margin-bottom: 26px; }
        .date-group:last-child { margin-bottom: 0; }

        .date-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #888;
            text-transform: uppercase;
            letter-spacing: .6px;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1.5px solid #f0f0f0;
            width: 100%;
        }

        /* Show times */
        .show-times {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Show-time pill */
        .show-card {
            display: flex;
            flex-direction: column;
            gap: 3px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 18px;
            text-decoration: none;
            color: #333;
            font-size: 13px;
            background: #fff;
            transition: border-color .2s, background .2s, transform .18s, box-shadow .2s;
            min-width: 148px;
        }

        .show-card:hover {
            border-color: #dc3546;
            background: #fff5f6;
            transform: translateY(-3px) scale(1.025);
            box-shadow: 0 6px 20px rgba(220,53,70,.13);
        }

        .show-card .s-time {
            font-size: 17px;
            font-weight: 800;
            color: #1a1a2e;
            letter-spacing: .3px;
        }

        .show-card .s-screen {
            font-size: 12px;
            font-weight: 600;
            color: #999;
        }

        .show-card .s-avail {
            font-size: 12px;
            font-weight: 700;
            color: #2e7d32;
            margin-top: 4px;
        }

        .show-card .s-price { color: #dc3546; }

        /* No shows */
        .no-shows {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 32px 0;
            color: #bbb;
        }

        .no-shows .ns-icon { font-size: 40px; }

        .no-shows p {
            font-size: 15px;
            font-weight: 600;
        }

        /* ── FOOTER ── */
        footer {
            background: #1a1a2e;
            color: #aaa;
            padding: 28px 24px;
            margin-top: auto;
        }

        .footer-inner {
            max-width: 960px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            height: 28px;
            opacity: .7;
            filter: brightness(0) invert(1);
        }

        .footer-links {
            display: flex;
            gap: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .footer-links a { color: #888; transition: color .2s; }
        .footer-links a:hover { color: #dc3546; }

        .footer-copy { font-size: 12px; color: #555; }

        /* ── RESPONSIVE ── */
        @media (max-width: 680px) {
            .movie-card {
                flex-direction: column;
                align-items: center;
                padding: 22px 18px;
                gap: 22px;
            }

            .poster-wrap img { width: 180px; height: 260px; }
            .movie-info h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

    <!-- ═══════════════════════ NAVBAR ═══════════════════════ -->
    <nav class="navbar">
        <a class="logo" href="index.php">
            <img src="a.png" alt="BookMyShow">
        </a>
        <form class="search-box" action="movies.php" method="GET">
            <input type="search" name="q" placeholder="Search movies, events…" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
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
    </nav>

    <!-- ═══════════════════════ CONTENT ═══════════════════════ -->
    <div class="content-wrapper">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span>&#8250;</span>
            <a href="movies.php">Movies</a>
            <span>&#8250;</span>
            <?= htmlspecialchars($movie['title']) ?>
        </div>

        <!-- ── Movie Detail Card ── -->
        <div class="movie-card">

            <!-- Poster -->
            <div class="poster-wrap">
                <img
                    src="<?= $movie['poster'] ? 'assets/uploads/posters/' . htmlspecialchars($movie['poster']) : 'assets/placeholder.jpg' ?>"
                    alt="<?= htmlspecialchars($movie['title']) ?>"
                >
            </div>

            <!-- Info panel -->
            <div class="movie-info">

                <!-- Status badge -->
                <span class="status-badge <?= $movie['status'] === 'showing' ? 'showing' : 'soon' ?>">
                    <span class="dot"></span>
                    <?= $movie['status'] === 'showing' ? 'Now Showing' : 'Coming Soon' ?>
                </span>

                <!-- Title -->
                <h1><?= htmlspecialchars($movie['title']) ?></h1>

                <!-- Tag pills -->
                <div class="tags-row">
                    <?php if (!empty($movie['genre'])): ?>
                        <span class="tag-pill"><?= htmlspecialchars($movie['genre']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($movie['duration'])): ?>
                        <span class="tag-pill"><?= htmlspecialchars($movie['duration']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($movie['release_date'])): ?>
                        <span class="tag-pill"><?= htmlspecialchars($movie['release_date']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="info-divider"></div>

                <!-- Description -->
                <?php if (!empty($movie['description'])): ?>
                    <p class="description"><?= nl2br(htmlspecialchars($movie['description'])) ?></p>
                <?php endif; ?>

                <!-- Trailer -->
                <?php if (!empty($movie['trailer_url'])): ?>
                    <div class="trailer-wrap">
                        <div class="trailer-label">Official Trailer</div>
                        <iframe
                            width="380"
                            height="214"
                            src="<?= htmlspecialchars($movie['trailer_url']) ?>"
                            title="Trailer"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen>
                        </iframe>
                    </div>
                <?php endif; ?>

            </div><!-- /.movie-info -->
        </div><!-- /.movie-card -->

        <!-- ── Book Tickets Card ── -->
        <?php if ($movie['status'] === 'showing'): ?>
        <div class="book-card">
            <div class="book-card-header">
                <h2>Book Tickets</h2>
            </div>
            <div class="book-card-body">
                <?php if ($byDate): ?>
                    <?php foreach ($byDate as $date => $dayShows): ?>
                        <div class="date-group">
                            <div class="date-label">
                                <?= date('l, d M Y', strtotime($date)) ?>
                            </div>
                            <div class="show-times">
                                <?php foreach ($dayShows as $s):
                                    $cnt = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE show_id=? AND status='booked'");
                                    $cnt->execute([$s['id']]);
                                    $avail = $s['total_seats'] - $cnt->fetchColumn();
                                ?>
                                    <a class="show-card" href="booking.php?show_id=<?= $s['id'] ?>">
                                        <div class="s-time"><?= date('h:i A', strtotime($s['show_time'])) ?></div>
                                        <div class="s-screen"><?= htmlspecialchars($s['screen_name']) ?></div>
                                        <div class="s-avail">
                                            <?= $avail ?> seats &nbsp;<span class="s-price">&#8377;<?= number_format($s['price'], 2) ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-shows">
                        <p>No upcoming shows scheduled yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.content-wrapper -->

    <!-- ═══════════════════════ FOOTER ═══════════════════════ -->
    <footer>
        <div class="footer-inner">
            <img src="a.png" alt="BookMyShow" class="footer-logo">
            <div class="footer-links">
                <a href="movies.php">Movies</a>
                <a href="login.php">Sign In</a>
                <a href="register.php">Register</a>
            </div>
            <p class="footer-copy">&copy; <?= date('Y') ?> BookMyShow. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>