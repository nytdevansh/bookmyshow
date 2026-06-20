<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$showId = (int)($_GET['show_id'] ?? 0);
$stmt   = $pdo->prepare("SELECT s.*, m.title, m.poster FROM shows s JOIN movies m ON m.id=s.movie_id WHERE s.id=?");
$stmt->execute([$showId]);
$show   = $stmt->fetch();
if (!$show) {
    header('Location: ' . BASE_PATH . '/movies.php');
    exit;
}

// Compute layout from total_seats
$totalSeats  = max(1, (int)$show['total_seats']);
$seatsPerRow = 10;
$numRows     = (int)ceil($totalSeats / $seatsPerRow);
$rowLabels   = array_slice(range('A', 'Z'), 0, $numRows);

// Auto-generate seats if missing
$seats = $pdo->prepare("SELECT * FROM seats WHERE show_id=? ORDER BY seat_no");
$seats->execute([$showId]);
$seats = $seats->fetchAll();
if (!$seats) {
    $ins = $pdo->prepare("INSERT INTO seats (show_id, seat_no, status) VALUES (?,?,'available')");
    $generated = 0;
    foreach ($rowLabels as $r) {
        for ($c = 1; $c <= $seatsPerRow && $generated < $totalSeats; $c++) {
            $ins->execute([$showId, $r . $c]);
            $generated++;
        }
    }
    $seats = $pdo->prepare("SELECT * FROM seats WHERE show_id=? ORDER BY seat_no");
    $seats->execute([$showId]);
    $seats = $seats->fetchAll();
}

$seatMap = [];
foreach ($seats as $s) $seatMap[$s['seat_no']] = $s['status'];

$maxPerBooking = 10;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $sel = array_filter(array_map('trim', explode(',', $_POST['selected_seats'] ?? '')));
        if (!$sel) {
            $error = 'Please select at least one seat.';
        } elseif (count($sel) > $maxPerBooking) {
            $error = "You can book a maximum of $maxPerBooking seats at a time.";
        } else {
            $pdo->beginTransaction();
            try {
                foreach ($sel as $sn) {
                    $chk = $pdo->prepare("SELECT status FROM seats WHERE show_id=? AND seat_no=?");
                    $chk->execute([$showId, $sn]);
                    $row = $chk->fetch();
                    if (!$row || $row['status'] === 'booked') throw new Exception("Seat $sn is already booked.");
                }
                foreach ($sel as $sn)
                    $pdo->prepare("UPDATE seats SET status='booked' WHERE show_id=? AND seat_no=?")->execute([$showId, $sn]);
                $total = count($sel) * $show['price'];
                $code  = strtoupper(substr(md5(uniqid()), 0, 10));
                $pdo->prepare("INSERT INTO bookings (booking_code, user_id, show_id, seats, total_amount) VALUES (?,?,?,?,?)")
                    ->execute([$code, $_SESSION['user_id'], $showId, implode(',', $sel), $total]);
                $pdo->commit();
                header("Location: " . BASE_PATH . "/bookings.php?success=$code");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Select Seats – <?= htmlspecialchars($show['title']) ?> | BookMyShow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: #f5f5f5;
      color: #1a1a2e;
      min-height: 100vh;
    }

    /* ── NAVBAR ── */
    .navbar {
      background: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,.08);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .navbar-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 24px;
      height: 60px;
      display: flex;
      align-items: center;
      gap: 24px;
    }
    .navbar-logo {
      font-size: 1.5rem;
      font-weight: 800;
      color: #dc3546;
      text-decoration: none;
      letter-spacing: -0.5px;
      flex-shrink: 0;
    }
    .navbar-logo span { color: #1a1a2e; }
    .navbar-search {
      flex: 1;
      max-width: 380px;
    }
    .navbar-search input {
      width: 100%;
      padding: 8px 16px;
      border: 1.5px solid #e8e8e8;
      border-radius: 24px;
      font-family: 'Nunito', sans-serif;
      font-size: .9rem;
      outline: none;
      transition: border-color .2s;
      background: #f8f8f8;
    }
    .navbar-search input:focus { border-color: #dc3546; background: #fff; }
    .navbar-links {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .navbar-links a {
      color: #444;
      text-decoration: none;
      font-weight: 600;
      font-size: .88rem;
      transition: color .2s;
    }
    .navbar-links a:hover { color: #dc3546; }
    .navbar-links .btn-login {
      background: #dc3546;
      color: #fff;
      padding: 7px 20px;
      border-radius: 24px;
      font-weight: 700;
      transition: background .2s, transform .15s;
    }
    .navbar-links .btn-login:hover { background: #b71c2c; transform: translateY(-1px); }

    /* ── PAGE WRAPPER ── */
    .page-wrap {
      max-width: 760px;
      margin: 32px auto 110px;
      padding: 0 16px;
    }

    /* ── BREADCRUMB ── */
    .breadcrumb {
      font-size: .8rem;
      color: #999;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .breadcrumb a { color: #dc3546; text-decoration: none; font-weight: 600; }
    .breadcrumb a:hover { text-decoration: underline; }
    .breadcrumb-sep { color: #ccc; }

    /* ── SHOW HEADER ── */
    .show-header {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      padding: 22px 26px;
      margin-bottom: 24px;
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .show-poster {
      width: 68px;
      height: 96px;
      border-radius: 8px;
      object-fit: cover;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(0,0,0,.15);
    }
    .show-poster-placeholder {
      width: 68px;
      height: 96px;
      border-radius: 8px;
      background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.6rem;
    }
    .show-info h2 {
      font-size: 1.35rem;
      font-weight: 800;
      color: #1a1a2e;
      margin-bottom: 8px;
    }
    .show-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 6px 18px;
      font-size: .85rem;
      color: #777;
      font-weight: 600;
    }
    .show-meta .dot { color: #ccc; font-size: .7rem; }
    .show-price-badge {
      margin-left: auto;
      flex-shrink: 0;
      background: #fff4f4;
      border: 1.5px solid #f9c6cb;
      border-radius: 10px;
      padding: 10px 18px;
      text-align: center;
    }
    .show-price-badge .price-amt {
      font-size: 1.25rem;
      font-weight: 800;
      color: #dc3546;
      display: block;
    }
    .show-price-badge .price-label {
      font-size: .72rem;
      color: #999;
      font-weight: 600;
    }

    /* ── ERROR ALERT ── */
    .alert-error {
      background: #fff4f4;
      border: 1.5px solid #f5c6cb;
      color: #c0392b;
      border-radius: 10px;
      padding: 14px 20px;
      margin-bottom: 22px;
      font-weight: 700;
      font-size: .92rem;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .alert-error::before {
      content: '\26A0';
      font-size: 1.1rem;
      flex-shrink: 0;
    }

    /* ── SEAT SELECTOR CARD ── */
    .seat-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 2px 12px rgba(0,0,0,.07);
      padding: 28px 24px 24px;
      margin-bottom: 24px;
    }

    /* ── SCREEN BAR ── */
    .screen-wrap {
      text-align: center;
      margin-bottom: 32px;
      perspective: 400px;
    }
    .screen-bar {
      display: inline-block;
      width: min(92%, 560px);
      height: 10px;
      background: linear-gradient(90deg, #dc3546 0%, #ff6b6b 50%, #dc3546 100%);
      border-radius: 50% 50% 0 0 / 100% 100% 0 0;
      box-shadow: 0 4px 24px rgba(220,53,70,.35);
      transform: rotateX(-12deg);
    }
    .screen-label {
      font-size: .72rem;
      font-weight: 700;
      letter-spacing: 4px;
      color: #aaa;
      text-transform: uppercase;
      margin-top: 8px;
    }

    /* ── SEAT GRID ── */
    .seat-grid-wrapper {
      overflow-x: auto;
      padding-bottom: 4px;
    }
    .seat-row {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 8px;
      min-width: max-content;
    }
    .row-label {
      width: 24px;
      text-align: right;
      font-size: .75rem;
      font-weight: 700;
      color: #bbb;
      flex-shrink: 0;
      user-select: none;
    }
    .seat-group {
      display: flex;
      gap: 6px;
    }
    .seat-aisle { width: 14px; flex-shrink: 0; }

    .seat {
      width: 36px;
      height: 36px;
      border-radius: 6px;
      border: 2px solid #ccc;
      background: #fff;
      font-size: .7rem;
      font-weight: 700;
      color: #555;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      user-select: none;
      transition: all .18s ease;
      flex-shrink: 0;
    }
    .seat:hover:not(.seat-booked):not(.seat-selected) {
      border-color: #dc3546;
      background: #fff0f1;
      color: #dc3546;
      transform: scale(1.06);
      box-shadow: 0 2px 8px rgba(220,53,70,.2);
    }
    .seat-selected {
      background: #dc3546 !important;
      border-color: #dc3546 !important;
      color: #fff !important;
      transform: scale(1.06);
      box-shadow: 0 4px 12px rgba(220,53,70,.35) !important;
    }
    .seat-booked {
      background: #eee !important;
      border-color: #ddd !important;
      color: #bbb !important;
      cursor: not-allowed !important;
      transform: none !important;
    }

    /* ── LEGEND ── */
    .legend {
      display: flex;
      justify-content: center;
      gap: 28px;
      margin-top: 22px;
      padding-top: 18px;
      border-top: 1px solid #f0f0f0;
    }
    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: .8rem;
      font-weight: 700;
      color: #666;
    }
    .legend-box {
      width: 22px;
      height: 22px;
      border-radius: 5px;
      border: 2px solid transparent;
    }
    .legend-available { background: #fff; border-color: #ccc; }
    .legend-selected  { background: #dc3546; border-color: #dc3546; }
    .legend-booked    { background: #eee; border-color: #ddd; }

    /* ── SUMMARY BAR ── */
    .summary-bar {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff;
      box-shadow: 0 -4px 20px rgba(0,0,0,.1);
      z-index: 50;
      padding: 14px 24px;
    }
    .summary-inner {
      max-width: 760px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 16px;
      flex-wrap: wrap;
    }
    .summary-info { flex: 1; }
    .summary-seats-label {
      font-size: .78rem;
      color: #999;
      font-weight: 600;
      margin-bottom: 2px;
    }
    .summary-seats-value {
      font-size: .92rem;
      font-weight: 700;
      color: #1a1a2e;
      min-height: 1.2em;
    }
    .summary-total { text-align: right; }
    .summary-total-label {
      font-size: .78rem;
      color: #999;
      font-weight: 600;
      margin-bottom: 2px;
    }
    .summary-total-value {
      font-size: 1.25rem;
      font-weight: 800;
      color: #dc3546;
    }
    .btn-confirm {
      background: #dc3546;
      color: #fff;
      border: none;
      border-radius: 10px;
      padding: 13px 32px;
      font-family: 'Nunito', sans-serif;
      font-size: 1rem;
      font-weight: 800;
      cursor: pointer;
      transition: background .2s, transform .15s, box-shadow .2s;
      box-shadow: 0 4px 14px rgba(220,53,70,.35);
      white-space: nowrap;
    }
    .btn-confirm:hover:not(:disabled) {
      background: #b71c2c;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220,53,70,.45);
    }
    .btn-confirm:disabled {
      background: #e0a0a7;
      box-shadow: none;
      cursor: not-allowed;
    }
    .max-warning {
      width: 100%;
      text-align: center;
      font-size: .82rem;
      font-weight: 700;
      color: #dc3546;
      margin-top: 4px;
    }
    #selected-seats-input { display: none; }

    @media (max-width: 600px) {
      .show-header { flex-wrap: wrap; }
      .show-price-badge { margin-left: 0; }
      .summary-inner { gap: 10px; }
      .btn-confirm { padding: 11px 22px; font-size: .9rem; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="navbar-inner">
    <a href="index.php" class="navbar-logo">Book<span>MyShow</span></a>
    <div class="navbar-search">
      <input type="text" placeholder="Search for movies, events, plays…" />
    </div>
    <div class="navbar-links">
      <a href="movies.php">Movies</a>
      <a href="bookings.php">My Bookings</a>
      <a href="logout.php" class="btn-login">Logout</a>
    </div>
  </div>
</nav>

<!-- PAGE CONTENT -->
<div class="page-wrap">

  <!-- Breadcrumb -->
  <div class="breadcrumb">
    <a href="movies.php">Movies</a>
    <span class="breadcrumb-sep">›</span>
    <span><?= htmlspecialchars($show['title']) ?></span>
    <span class="breadcrumb-sep">›</span>
    <span>Select Seats</span>
  </div>

  <!-- Show Header -->
  <div class="show-header">
    <?php if (!empty($show['poster'])): ?>
      <img src="<?= htmlspecialchars($show['poster']) ?>"
           alt="<?= htmlspecialchars($show['title']) ?>"
           class="show-poster" />
    <?php else: ?>
      <div class="show-poster-placeholder">🎬</div>
    <?php endif; ?>

    <div class="show-info">
      <h2><?= htmlspecialchars($show['title']) ?></h2>
      <div class="show-meta">
        <span>🎭 <?= htmlspecialchars($show['screen_name'] ?? 'Screen 1') ?></span>
        <span class="dot">•</span>
        <span>📅 <?= htmlspecialchars(date('D, d M Y', strtotime($show['show_date']))) ?></span>
        <span class="dot">•</span>
        <span>🕐 <?= htmlspecialchars(date('g:i A', strtotime($show['show_time']))) ?></span>
      </div>
    </div>

    <div class="show-price-badge">
      <span class="price-amt">₹<?= number_format($show['price'], 2) ?></span>
      <span class="price-label">per seat</span>
    </div>
  </div>

  <!-- Error Alert -->
  <?php if ($error): ?>
    <div class="alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <!-- Seat Selector Card -->
  <div class="seat-card">

    <!-- Cinema Screen -->
    <div class="screen-wrap">
      <div class="screen-bar"></div>
      <div class="screen-label">S C R E E N</div>
    </div>

    <!-- Booking Form + Seat Grid -->
    <form method="POST" id="booking-form" action="?show_id=<?= $showId ?>">
      <input type="hidden" name="csrf" value="<?= csrf() ?>" />
      <input type="hidden" name="selected_seats" id="selected-seats-input" value="" />

      <div class="seat-grid-wrapper">
        <?php foreach ($rowLabels as $rowIdx => $rowLabel): ?>
          <div class="seat-row">
            <span class="row-label"><?= $rowLabel ?></span>

            <!-- Left half (cols 1-5) -->
            <div class="seat-group">
              <?php
              $leftEnd = min(5, $seatsPerRow);
              for ($col = 1; $col <= $leftEnd; $col++):
                $seatNo = $rowLabel . $col;
                $status = $seatMap[$seatNo] ?? 'available';
                $cls    = 'seat' . ($status === 'booked' ? ' seat-booked' : '');
              ?>
                <div class="<?= $cls ?>"
                     data-seat="<?= htmlspecialchars($seatNo) ?>"
                     data-status="<?= htmlspecialchars($status) ?>"
                     title="<?= $status === 'booked' ? 'Already booked' : htmlspecialchars($seatNo) ?>">
                  <?= $col ?>
                </div>
              <?php endfor; ?>
            </div>

            <?php if ($seatsPerRow > 5): ?>
              <!-- Aisle -->
              <div class="seat-aisle"></div>
              <!-- Right half (cols 6-10) -->
              <div class="seat-group">
                <?php for ($col = 6; $col <= $seatsPerRow; $col++):
                  $seatNo = $rowLabel . $col;
                  $status = $seatMap[$seatNo] ?? 'available';
                  $cls    = 'seat' . ($status === 'booked' ? ' seat-booked' : '');
                ?>
                  <div class="<?= $cls ?>"
                       data-seat="<?= htmlspecialchars($seatNo) ?>"
                       data-status="<?= htmlspecialchars($status) ?>"
                       title="<?= $status === 'booked' ? 'Already booked' : htmlspecialchars($seatNo) ?>">
                    <?= $col ?>
                  </div>
                <?php endfor; ?>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>
      </div><!-- /.seat-grid-wrapper -->

      <!-- Legend -->
      <div class="legend">
        <div class="legend-item">
          <div class="legend-box legend-available"></div>
          <span>Available</span>
        </div>
        <div class="legend-item">
          <div class="legend-box legend-selected"></div>
          <span>Selected</span>
        </div>
        <div class="legend-item">
          <div class="legend-box legend-booked"></div>
          <span>Booked</span>
        </div>
      </div>

    </form>
  </div><!-- /.seat-card -->

</div><!-- /.page-wrap -->

<!-- SUMMARY BAR -->
<div class="summary-bar">
  <div class="summary-inner">
    <div class="summary-info">
      <div class="summary-seats-label">Selected Seats</div>
      <div class="summary-seats-value" id="summary-seats">—</div>
    </div>
    <div class="summary-total">
      <div class="summary-total-label">Total Amount</div>
      <div class="summary-total-value" id="summary-total">₹0.00</div>
    </div>
    <button type="submit" form="booking-form" class="btn-confirm" id="confirm-btn" disabled>
      Confirm Booking
    </button>
    <div class="max-warning" id="max-warning" style="display:none;">
      ⚠ Max <?= $maxPerBooking ?> seats per booking reached!
    </div>
  </div>
</div>

<script>
(function () {
  var MAX    = <?= (int)$maxPerBooking ?>;
  var PRICE  = <?= (float)$show['price'] ?>;
  var selected = new Set();

  var seatsInput   = document.getElementById('selected-seats-input');
  var summarySeats = document.getElementById('summary-seats');
  var summaryTotal = document.getElementById('summary-total');
  var confirmBtn   = document.getElementById('confirm-btn');
  var maxWarning   = document.getElementById('max-warning');

  function rupee(n) {
    return '₹' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function updateUI() {
    var count = selected.size;
    if (count === 0) {
      summarySeats.textContent = '—';
      summaryTotal.textContent = rupee(0);
      confirmBtn.disabled = true;
    } else {
      var arr = [];
      selected.forEach(function(v){ arr.push(v); });
      arr.sort();
      summarySeats.textContent = arr.join(', ');
      summaryTotal.textContent = rupee(count * PRICE);
      confirmBtn.disabled = false;
    }
    maxWarning.style.display = (count >= MAX) ? 'block' : 'none';
    var out = [];
    selected.forEach(function(v){ out.push(v); });
    seatsInput.value = out.join(',');
  }

  document.querySelectorAll('.seat:not(.seat-booked)').forEach(function (el) {
    el.addEventListener('click', function () {
      var seatNo = el.getAttribute('data-seat');
      if (selected.has(seatNo)) {
        selected.delete(seatNo);
        el.classList.remove('seat-selected');
      } else {
        if (selected.size >= MAX) return;
        selected.add(seatNo);
        el.classList.add('seat-selected');
      }
      updateUI();
    });
  });

  updateUI();
})();
</script>

</body>
</html>
