<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';
requireLogin();

$success  = $_GET['success'] ?? '';
$stmt     = $pdo->prepare("
    SELECT b.*, m.title, m.poster, s.show_date, s.show_time, s.screen_name
    FROM bookings b
    JOIN shows s ON s.id = b.show_id
    JOIN movies m ON m.id = s.movie_id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Bookings – BookMyShow</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: #f5f5f5;
      color: #1a1a2e;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── NAVBAR ── */
    .navbar {
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,.10);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .nav-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
      height: 64px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .nav-logo {
      font-size: 1.55rem;
      font-weight: 900;
      text-decoration: none;
      letter-spacing: -0.5px;
    }
    .nav-logo span:first-child { color: #dc3546; }
    .nav-logo span:last-child  { color: #1a1a2e; }

    .nav-search {
      flex: 1;
      max-width: 380px;
      position: relative;
    }
    .nav-search input {
      width: 100%;
      padding: 9px 16px 9px 38px;
      border: 1.5px solid #e8e8e8;
      border-radius: 50px;
      font-family: 'Nunito', sans-serif;
      font-size: .9rem;
      background: #f7f7f7;
      outline: none;
      transition: border-color .2s, background .2s;
    }
    .nav-search input:focus {
      border-color: #dc3546;
      background: #fff;
    }
    .nav-search .search-icon {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #aaa;
      font-size: .85rem;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-left: auto;
    }
    .nav-links a {
      text-decoration: none;
      font-size: .88rem;
      font-weight: 700;
      color: #555;
      padding: 8px 14px;
      border-radius: 50px;
      transition: background .2s, color .2s;
    }
    .nav-links a:hover { background: #f5f5f5; color: #dc3546; }
    .nav-links a.active { color: #dc3546; }
    .nav-links a.signout {
      background: #dc3546;
      color: #fff !important;
    }
    .nav-links a.signout:hover { background: #b52a38; }

    /* ── MAIN WRAPPER ── */
    .main-wrapper {
      flex: 1;
      max-width: 820px;
      width: 100%;
      margin: 36px auto 48px;
      padding: 0 16px;
    }

    /* ── PAGE HEADING ── */
    .page-heading {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 24px;
    }
    .page-heading h1 {
      font-size: 1.55rem;
      font-weight: 900;
      color: #1a1a2e;
    }
    .page-heading .count-badge {
      background: #dc3546;
      color: #fff;
      font-size: .75rem;
      font-weight: 800;
      padding: 2px 9px;
      border-radius: 50px;
    }

    /* ── SUCCESS BANNER ── */
    .success-banner {
      display: flex;
      align-items: center;
      gap: 12px;
      background: #edfaf1;
      border: 1.5px solid #6fcf97;
      border-radius: 50px;
      padding: 13px 22px;
      margin-bottom: 26px;
      animation: slideDown .4s ease both;
    }
    .success-banner .icon-circle {
      width: 32px;
      height: 32px;
      background: #27ae60;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .success-banner .icon-circle i { color: #fff; font-size: .85rem; }
    .success-banner .sb-text { flex: 1; }
    .success-banner .sb-title {
      font-size: .95rem;
      font-weight: 800;
      color: #1e8449;
    }
    .success-banner .sb-code {
      font-size: .8rem;
      color: #27ae60;
      font-weight: 600;
    }

    /* ── BOOKING CARD ── */
    .booking-card {
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,.07);
      overflow: hidden;
      margin-bottom: 18px;
      display: flex;
      flex-direction: column;
      transition: transform .22s ease, box-shadow .22s ease;
      opacity: 0;
      animation: slideUp .45s ease forwards;
    }
    .booking-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 28px rgba(0,0,0,.13);
    }

    .card-body {
      display: flex;
      gap: 18px;
      padding: 18px 20px 14px;
    }

    /* Poster */
    .card-poster { flex-shrink: 0; }
    .card-poster img {
      width: 75px;
      height: 105px;
      object-fit: cover;
      border-radius: 8px;
      display: block;
    }

    /* Info */
    .card-info {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 0;
    }
    .movie-title {
      font-size: 1.1rem;
      font-weight: 800;
      color: #1a1a2e;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .info-row {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: .82rem;
      color: #666;
      font-weight: 600;
    }
    .info-row i {
      color: #dc3546;
      font-size: .78rem;
      width: 14px;
      text-align: center;
      flex-shrink: 0;
    }
    .seats-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 5px;
      margin-top: 2px;
    }
    .seat-pill {
      background: #f0f0f0;
      color: #444;
      font-size: .72rem;
      font-weight: 700;
      padding: 2px 9px;
      border-radius: 50px;
    }

    /* Footer row */
    .card-footer-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 20px 14px;
      border-top: 1px solid #f0f0f0;
      flex-wrap: wrap;
      gap: 8px;
    }
    .left-footer {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: .78rem;
      font-weight: 800;
      padding: 3px 12px;
      border-radius: 50px;
      letter-spacing: .3px;
    }
    .status-badge.confirmed {
      background: #edfaf1;
      color: #1e8449;
      border: 1px solid #a9dfbf;
    }
    .status-badge.cancelled {
      background: #fdf3f3;
      color: #c0392b;
      border: 1px solid #f5b7b1;
    }
    .booking-code-text {
      font-size: .72rem;
      color: #aaa;
      font-weight: 600;
      padding-left: 2px;
    }
    .amount-text {
      font-size: 1.25rem;
      font-weight: 900;
      color: #dc3546;
    }

    /* ── EMPTY STATE ── */
    .empty-state {
      text-align: center;
      padding: 72px 20px;
    }
    .empty-state .empty-icon {
      font-size: 4.5rem;
      color: #ddd;
      margin-bottom: 18px;
    }
    .empty-state h2 {
      font-size: 1.3rem;
      font-weight: 800;
      color: #555;
      margin-bottom: 8px;
    }
    .empty-state p {
      font-size: .9rem;
      color: #999;
      margin-bottom: 26px;
    }
    .btn-browse {
      display: inline-block;
      background: #dc3546;
      color: #fff;
      font-family: 'Nunito', sans-serif;
      font-size: .92rem;
      font-weight: 800;
      padding: 12px 30px;
      border-radius: 50px;
      text-decoration: none;
      transition: background .2s, transform .2s;
    }
    .btn-browse:hover { background: #b52a38; transform: translateY(-2px); }

    /* ── FOOTER ── */
    .site-footer {
      background: #1a1a2e;
      color: #888;
      text-align: center;
      padding: 22px 16px;
      font-size: .82rem;
    }
    .site-footer span { color: #dc3546; }

    /* ── ANIMATIONS ── */
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-14px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="navbar">
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">
      <span>Book</span><span>MyShow</span>
    </a>
    <div class="nav-search">
      <i class="fa fa-search search-icon"></i>
      <input type="text" placeholder="Search for movies, events…"/>
    </div>
    <div class="nav-links">
      <a href="index.php">Movies</a>
      <a href="bookings.php" class="active">My Bookings</a>
      <?php if (isAdmin()): ?>
        <a href="admin/index.php">Admin</a>
      <?php endif; ?>
      <a href="logout.php" class="signout">
        <i class="fa fa-sign-out-alt"></i> Sign out
      </a>
    </div>
  </div>
</nav>

<!-- ══ MAIN ══ -->
<div class="main-wrapper">

  <!-- Success Banner -->
  <?php if ($success): ?>
    <div class="success-banner">
      <div class="icon-circle"><i class="fa fa-check"></i></div>
      <div class="sb-text">
        <div class="sb-title">Booking Confirmed! 🎉</div>
        <div class="sb-code">Booking Code: <strong><?= htmlspecialchars($success) ?></strong></div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Page Heading -->
  <div class="page-heading">
    <h1>My Bookings</h1>
    <?php if (count($bookings) > 0): ?>
      <span class="count-badge"><?= count($bookings) ?></span>
    <?php endif; ?>
  </div>

  <!-- Bookings List -->
  <?php if (empty($bookings)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i class="fa fa-ticket-alt"></i></div>
      <h2>No bookings yet!</h2>
      <p>Looks like you haven't booked any tickets. Explore what's showing now.</p>
      <a href="index.php" class="btn-browse">Browse Movies</a>
    </div>

  <?php else: ?>
    <?php foreach ($bookings as $i => $b): ?>
      <?php
        $poster = !empty($b['poster'])
          ? 'assets/uploads/posters/' . $b['poster']
          : 'assets/placeholder.jpg';
        $showDate  = date('d M Y', strtotime($b['show_date']));
        $showTime  = date('h:i A', strtotime($b['show_time']));
        $seatList  = array_map('trim', explode(',', $b['seats']));
        $statusCls = strtolower($b['status']);
        $delay     = $i * 80;
      ?>
      <div class="booking-card" style="animation-delay: <?= $delay ?>ms;">
        <div class="card-body">

          <!-- Poster -->
          <div class="card-poster">
            <img src="<?= htmlspecialchars($poster) ?>"
                 alt="<?= htmlspecialchars($b['title']) ?>"
                 onerror="this.src='assets/placeholder.jpg'"/>
          </div>

          <!-- Info -->
          <div class="card-info">
            <div class="movie-title"><?= htmlspecialchars($b['title']) ?></div>

            <div class="info-row">
              <i class="fa fa-calendar-alt"></i>
              <?= htmlspecialchars($showDate) ?> &nbsp;at&nbsp; <?= htmlspecialchars($showTime) ?>
            </div>

            <div class="info-row">
              <i class="fa fa-film"></i>
              <?= htmlspecialchars($b['screen_name']) ?>
            </div>

            <div class="info-row" style="align-items:flex-start; margin-top:4px;">
              <i class="fa fa-chair" style="margin-top:3px;"></i>
              <div class="seats-wrap">
                <?php foreach ($seatList as $seat): ?>
                  <span class="seat-pill"><?= htmlspecialchars($seat) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div><!-- /.card-body -->

        <!-- Footer Row -->
        <div class="card-footer-row">
          <div class="left-footer">
            <span class="status-badge <?= $statusCls ?>">
              <?php if ($statusCls === 'confirmed'): ?>
                <i class="fa fa-check-circle"></i>
              <?php else: ?>
                <i class="fa fa-times-circle"></i>
              <?php endif; ?>
              <?= ucfirst($b['status']) ?>
            </span>
            <span class="booking-code-text">
              Code: <?= htmlspecialchars($b['booking_code']) ?>
            </span>
          </div>
          <div class="amount-text">
            &#8377;<?= number_format($b['total_amount'], 2) ?>
          </div>
        </div>

      </div><!-- /.booking-card -->
    <?php endforeach; ?>
  <?php endif; ?>

</div><!-- /.main-wrapper -->

<!-- ══ FOOTER ══ -->
<footer class="site-footer">
  &copy; <?= date('Y') ?> <span>BookMyShow</span> Clone &mdash; All rights reserved.
</footer>

</body>
</html>