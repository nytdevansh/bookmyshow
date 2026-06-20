<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$sql    = "SELECT * FROM movies WHERE 1=1";
$params = [];
if ($q) {
    $sql .= " AND title ILIKE ?";
    $params[] = "%$q%";
}
if ($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
}
$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Movies — BookMyShow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --red:      #dc3546;
      --red-dark: #b52a37;
      --bg:       #f5f5f5;
      --white:    #ffffff;
      --text:     #333333;
      --muted:    #777777;
      --navy:     #1a1a2e;
      --green:    #28a745;
      --orange:   #fd7e14;
      --radius:   10px;
      --shadow:   0 4px 20px rgba(0,0,0,0.10);
      --shadow-h: 0 12px 36px rgba(0,0,0,0.18);
      --transition: 0.28s ease;
    }

    html { scroll-behavior: smooth; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* NAVBAR */
    .navbar {
      background: var(--white);
      box-shadow: 0 2px 12px rgba(0,0,0,0.09);
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
    .nav-logo img {
      height: 38px;
      display: block;
      transition: opacity var(--transition);
    }
    .nav-logo img:hover { opacity: 0.85; }

    .nav-search {
      flex: 1;
      max-width: 420px;
    }
    .nav-search form {
      display: flex;
      background: #f0f0f0;
      border-radius: 30px;
      overflow: hidden;
      border: 1.5px solid transparent;
      transition: border-color var(--transition), background var(--transition);
    }
    .nav-search form:focus-within {
      background: var(--white);
      border-color: var(--red);
    }
    .nav-search input {
      flex: 1;
      border: none;
      background: transparent;
      padding: 9px 18px;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      color: var(--text);
      outline: none;
    }
    .nav-search input::placeholder { color: #aaa; }
    .nav-search button {
      background: var(--red);
      border: none;
      padding: 0 18px;
      cursor: pointer;
      color: var(--white);
      font-size: 15px;
      transition: background var(--transition);
    }
    .nav-search button:hover { background: var(--red-dark); }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 6px;
      list-style: none;
      margin-left: auto;
    }
    .nav-links a {
      text-decoration: none;
      font-size: 14px;
      font-weight: 600;
      color: var(--text);
      padding: 7px 14px;
      border-radius: 6px;
      transition: color var(--transition), background var(--transition);
      white-space: nowrap;
    }
    .nav-links a:hover { color: var(--red); background: #fdf0f1; }
    .nav-links a.active { color: var(--red); }
    .nav-links a.btn-red {
      background: var(--red);
      color: var(--white);
      border-radius: 20px;
      padding: 7px 18px;
    }
    .nav-links a.btn-red:hover { background: var(--red-dark); color: var(--white); }
    .nav-links a.btn-outline {
      border: 1.5px solid var(--red);
      color: var(--red);
      border-radius: 20px;
      padding: 6px 16px;
    }
    .nav-links a.btn-outline:hover { background: var(--red); color: var(--white); }

    /* MAIN */
    main {
      flex: 1;
      max-width: 1200px;
      width: 100%;
      margin: 0 auto;
      padding: 36px 24px 56px;
    }

    /* PAGE HEADING */
    .page-heading {
      margin-bottom: 28px;
    }
    .page-heading h1 {
      font-size: 26px;
      font-weight: 800;
      color: var(--navy);
      letter-spacing: -0.3px;
    }
    .page-heading h1 span {
      color: var(--red);
    }
    .page-heading p {
      margin-top: 4px;
      font-size: 14px;
      color: var(--muted);
    }

    /* FILTER BAR */
    .filter-bar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 32px;
    }
    .filter-label {
      font-size: 13px;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.6px;
      margin-right: 4px;
    }
    .filter-pill {
      text-decoration: none;
      font-size: 13px;
      font-weight: 700;
      padding: 7px 20px;
      border-radius: 30px;
      border: 1.5px solid #ddd;
      color: var(--muted);
      background: var(--white);
      transition: all var(--transition);
      white-space: nowrap;
    }
    .filter-pill:hover {
      border-color: var(--red);
      color: var(--red);
      background: #fdf0f1;
    }
    .filter-pill.active {
      background: var(--red);
      border-color: var(--red);
      color: var(--white);
      box-shadow: 0 4px 14px rgba(220,53,70,0.35);
    }

    /* MOVIE GRID */
    .movie-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 28px;
    }

    /* CARD */
    .movie-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: transform var(--transition), box-shadow var(--transition);
      animation: fadeUp 0.45s both ease;
    }
    .movie-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-h);
    }

    .movie-card:nth-child(1)  { animation-delay: 0.03s; }
    .movie-card:nth-child(2)  { animation-delay: 0.07s; }
    .movie-card:nth-child(3)  { animation-delay: 0.11s; }
    .movie-card:nth-child(4)  { animation-delay: 0.15s; }
    .movie-card:nth-child(5)  { animation-delay: 0.19s; }
    .movie-card:nth-child(6)  { animation-delay: 0.23s; }
    .movie-card:nth-child(7)  { animation-delay: 0.27s; }
    .movie-card:nth-child(8)  { animation-delay: 0.31s; }
    .movie-card:nth-child(9)  { animation-delay: 0.35s; }
    .movie-card:nth-child(10) { animation-delay: 0.39s; }
    .movie-card:nth-child(n+11) { animation-delay: 0.43s; }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0);    }
    }

    /* POSTER */
    .poster-wrap {
      position: relative;
      overflow: hidden;
      aspect-ratio: 2/3;
      background: #e9e9e9;
    }
    .poster-wrap img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.38s ease;
    }
    .movie-card:hover .poster-wrap img {
      transform: scale(1.06);
    }

    .poster-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.52);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity var(--transition);
    }
    .movie-card:hover .poster-overlay { opacity: 1; }
    .btn-book {
      display: inline-block;
      background: var(--red);
      color: var(--white);
      font-family: 'Nunito', sans-serif;
      font-size: 13px;
      font-weight: 700;
      padding: 9px 22px;
      border-radius: 30px;
      text-decoration: none;
      border: none;
      cursor: pointer;
      box-shadow: 0 4px 16px rgba(220,53,70,0.5);
      transform: translateY(6px);
      transition: transform var(--transition), background var(--transition);
    }
    .movie-card:hover .btn-book { transform: translateY(0); }
    .btn-book:hover { background: var(--red-dark); }

    .status-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      font-size: 10px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      padding: 4px 10px;
      border-radius: 20px;
      color: #fff;
      pointer-events: none;
    }
    .badge-now    { background: var(--green); }
    .badge-coming { background: var(--orange); }

    /* CARD BODY */
    .card-body {
      padding: 14px 14px 16px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      flex: 1;
    }
    .card-title {
      font-size: 15px;
      font-weight: 800;
      color: var(--navy);
      line-height: 1.3;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .card-meta {
      font-size: 12px;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }
    .card-meta .dot {
      width: 3px;
      height: 3px;
      border-radius: 50%;
      background: #ccc;
      display: inline-block;
    }
    .card-actions {
      margin-top: auto;
      padding-top: 10px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }
    .btn-card {
      flex: 1;
      text-align: center;
      font-size: 12px;
      font-weight: 700;
      padding: 7px 10px;
      border-radius: 20px;
      text-decoration: none;
      transition: all var(--transition);
    }
    .btn-card-primary {
      background: var(--red);
      color: var(--white);
    }
    .btn-card-primary:hover { background: var(--red-dark); }
    .btn-card-secondary {
      border: 1.5px solid var(--red);
      color: var(--red);
      background: transparent;
    }
    .btn-card-secondary:hover { background: var(--red); color: var(--white); }

    /* EMPTY STATE */
    .empty-state {
      grid-column: 1 / -1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 80px 20px;
      text-align: center;
      animation: fadeUp 0.4s ease both;
    }
    .empty-icon {
      font-size: 64px;
      margin-bottom: 18px;
      line-height: 1;
    }
    .empty-state h2 {
      font-size: 22px;
      font-weight: 800;
      color: var(--navy);
      margin-bottom: 8px;
    }
    .empty-state p {
      font-size: 14px;
      color: var(--muted);
      margin-bottom: 22px;
    }
    .empty-state a {
      text-decoration: none;
      background: var(--red);
      color: var(--white);
      font-weight: 700;
      font-size: 14px;
      padding: 10px 28px;
      border-radius: 30px;
      transition: background var(--transition);
    }
    .empty-state a:hover { background: var(--red-dark); }

    /* FOOTER */
    footer {
      background: var(--navy);
      color: rgba(255,255,255,0.55);
      text-align: center;
      padding: 20px 24px;
      font-size: 13px;
      font-weight: 500;
      letter-spacing: 0.2px;
    }
    footer strong { color: rgba(255,255,255,0.85); }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .nav-inner { gap: 12px; }
      .nav-search { max-width: 220px; }
      .movie-grid { grid-template-columns: repeat(auto-fill, minmax(155px, 1fr)); gap: 18px; }
    }
    @media (max-width: 480px) {
      .nav-links a:not(.btn-red):not(.btn-outline) { display: none; }
      .movie-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="nav-inner">

    <a class="nav-logo" href="">
      <img src="a.png" alt="BookMyShow"/>
    </a>

    <div class="nav-search">
      <form action="movies.php" method="GET">
        <?php if ($status): ?>
          <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>"/>
        <?php endif; ?>
        <input
          type="text"
          name="q"
          placeholder="Search movies&#8230;"
          value="<?= htmlspecialchars($q) ?>"
          autocomplete="off"
        />
        <button type="submit" title="Search">&#128269;</button>
      </form>
    </div>

    <ul class="nav-links">
      <li><a href="movies.php" class="active">Movies</a></li>

      <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="bookings.php">My Bookings</a></li>
      <?php endif; ?>

      <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li><a href="admin/admin_dashboard.php">Admin</a></li>
      <?php endif; ?>

      <?php if (isset($_SESSION['user_id'])): ?>
        <li><a href="logout.php" class="btn-red">Sign Out</a></li>
      <?php else: ?>
        <li><a href="login.php" class="btn-outline">Sign In</a></li>
        <li><a href="register.php" class="btn-red">Register</a></li>
      <?php endif; ?>
    </ul>

  </div>
</nav>

<!-- MAIN -->
<main>

  <div class="page-heading">
    <?php if ($q): ?>
      <h1>Movies &mdash; &ldquo;<span><?= htmlspecialchars($q) ?></span>&rdquo;</h1>
      <p><?= count($movies) ?> result<?= count($movies) !== 1 ? 's' : '' ?> found</p>
    <?php else: ?>
      <h1>All <span>Movies</span></h1>
      <p><?= count($movies) ?> movie<?= count($movies) !== 1 ? 's' : '' ?> available</p>
    <?php endif; ?>
  </div>

  <!-- Filter Bar -->
  <div class="filter-bar">
    <span class="filter-label">Filter:</span>
    <?php $baseQ = $q ? '&q=' . urlencode($q) : ''; ?>

    <a href="movies.php?<?= ltrim($baseQ, '&') ?>"
       class="filter-pill <?= $status === '' ? 'active' : '' ?>">
      All
    </a>
    <a href="movies.php?status=showing<?= $baseQ ?>"
       class="filter-pill <?= $status === 'showing' ? 'active' : '' ?>">
      Now Showing
    </a>
    <a href="movies.php?status=coming_soon<?= $baseQ ?>"
       class="filter-pill <?= $status === 'coming_soon' ? 'active' : '' ?>">
      Coming Soon
    </a>
  </div>

  <!-- Movie Grid -->
  <div class="movie-grid">

    <?php if (empty($movies)): ?>

      <div class="empty-state">
        <div class="empty-icon" style="font-size:48px;color:#ccc;">&#10006;</div>
        <h2>No Movies Found</h2>
        <?php if ($q): ?>
          <p>We couldn&apos;t find anything for &ldquo;<strong><?= htmlspecialchars($q) ?></strong>&rdquo;. Try a different search.</p>
        <?php else: ?>
          <p>There are no movies in this category yet. Check back soon!</p>
        <?php endif; ?>
        <a href="movies.php">View All Movies</a>
      </div>

    <?php else: ?>
      <?php foreach ($movies as $movie):
        $poster = !empty($movie['poster'])
          ? 'assets/uploads/posters/' . htmlspecialchars($movie['poster'])
          : 'assets/placeholder.jpg';

        $isNow    = ($movie['status'] === 'showing');
        $isComing = ($movie['status'] === 'coming_soon');
      ?>

      <div class="movie-card">

        <div class="poster-wrap">
          <img src="<?= $poster ?>" alt="<?= htmlspecialchars($movie['title']) ?>" loading="lazy"/>

          <?php if ($isNow): ?>
            <span class="status-badge badge-now">Now Showing</span>
          <?php elseif ($isComing): ?>
            <span class="status-badge badge-coming">Coming Soon</span>
          <?php endif; ?>

          <?php if ($isNow): ?>
          <div class="poster-overlay">
            <a href="movie.php?id=<?= (int)$movie['id'] ?>" class="btn-book">
              Book Tickets
            </a>
          </div>
          <?php endif; ?>
        </div>

        <div class="card-body">
          <div class="card-title"><?= htmlspecialchars($movie['title']) ?></div>

          <div class="card-meta">
            <?php if (!empty($movie['genre'])): ?>
              <span><?= htmlspecialchars($movie['genre']) ?></span>
            <?php endif; ?>
            <?php if (!empty($movie['duration'])): ?>
              <?php if (!empty($movie['genre'])): ?><span class="dot"></span><?php endif; ?>
              <span><?= htmlspecialchars($movie['duration']) ?> min</span>
            <?php endif; ?>
            <?php if (!empty($movie['language'])): ?>
              <span class="dot"></span>
              <span><?= htmlspecialchars($movie['language']) ?></span>
            <?php endif; ?>
          </div>

          <div class="card-actions">
            <?php if ($isNow): ?>
              <a href="movie.php?id=<?= (int)$movie['id'] ?>" class="btn-card btn-card-primary">
                Book Now
              </a>
            <?php else: ?>
              <a href="movie.php?id=<?= (int)$movie['id'] ?>" class="btn-card btn-card-secondary">
                View Details
              </a>
            <?php endif; ?>
          </div>
        </div>

      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>

</main>

<!-- FOOTER -->
<footer>
  &copy; <?= date('Y') ?> <strong>BookMyShow Clone</strong> &mdash; All rights reserved.
</footer>

</body>
</html>