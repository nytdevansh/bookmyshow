<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../database.php';

$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalMovies   = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status='confirmed'")->fetchColumn();
$revenue       = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status='confirmed'")->fetchColumn();

$recent = $pdo->query("
    SELECT b.booking_code, b.total_amount, b.booking_date, u.name AS uname, m.title
    FROM bookings b
    JOIN users u ON u.id = b.user_id
    JOIN shows s ON s.id = b.show_id
    JOIN movies m ON m.id = s.movie_id
    ORDER BY b.booking_date DESC LIMIT 10
")->fetchAll();

$cur = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .admin-wrap {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 200px;
            min-width: 200px;
            background: #222;
            padding: 20px 0;
        }

        .sidebar .brand {
            text-align: center;
            padding: 0 15px 20px;
            border-bottom: 1px solid #333;
            margin-bottom: 10px;
        }

        .sidebar .brand img {
            width: 110px;
        }

        .sidebar .brand small {
            display: block;
            color: #888;
            font-size: 12px;
            margin-top: 4px;
        }

        .sidebar nav a {
            display: block;
            padding: 10px 18px;
            color: #bbb;
            text-decoration: none;
            font-size: 14px;
            border-radius: 5px;
            margin: 2px 8px;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: #d9505a;
            color: #fff;
        }

        .sidebar nav hr {
            border: none;
            border-top: 1px solid #333;
            margin: 10px 0;
        }

        .content {
            flex: 1;
            padding: 30px;
            background: #f5f5f5;
        }

        .content h2 {
            font-size: 22px;
            margin-bottom: 20px;
        }

        .stat-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .stat-box {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 18px 22px;
            flex: 1;
            min-width: 120px;
            text-align: center;
        }

        .stat-box .num {
            font-size: 28px;
            font-weight: bold;
            color: #d9505a;
        }

        .stat-box .lbl {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }

        .table-wrap {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
            overflow: hidden;
        }

        .table-wrap .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 18px;
            border-bottom: 1px solid #eee;
        }

        .table-wrap .top h3 {
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f9f9f9;
            text-align: left;
            padding: 10px 15px;
            font-size: 13px;
            color: #666;
            border-bottom: 1px solid #eee;
        }

        table td {
            padding: 10px 15px;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        .td-empty {
            text-align: center;
            color: #aaa;
        }

        a {
            color: #d9505a;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="admin-wrap">
        <div class="sidebar">
            <div class="brand">
                <img src="a.png" alt="BookMyShow">
                <small>Admin Panel</small>
            </div>
            <nav>
                <a href="admin/admin_dashboard.php" class="<?= $cur === 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="admin/movies.php" class="<?= $cur === 'movies.php'     ? 'active' : '' ?>">Movies</a>
                <a href="admin/add_movie.php" class="<?= $cur === 'add_movie.php'  ? 'active' : '' ?>">Add Movie</a>
                <a href="admin/shows.php" class="<?= $cur === 'shows.php'      ? 'active' : '' ?>">Shows</a>
                <hr>
                <a href="index.php">View Site</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
        <div class="content">
            <h2>Dashboard</h2>

            <div class="stat-row">
                <div class="stat-box">
                    <div class="num"><?= $totalUsers ?></div>
                    <div class="lbl">Users</div>
                </div>
                <div class="stat-box">
                    <div class="num"><?= $totalMovies ?></div>
                    <div class="lbl">Movies</div>
                </div>
                <div class="stat-box">
                    <div class="num"><?= $totalBookings ?></div>
                    <div class="lbl">Bookings</div>
                </div>
                <div class="stat-box">
                    <div class="num">&#8377;<?= number_format($revenue) ?></div>
                    <div class="lbl">Revenue</div>
                </div>
            </div>

            <div class="table-wrap">
                <div class="top">
                    <h3>Recent Bookings</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>User</th>
                            <th>Movie</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent): ?>
                            <?php foreach ($recent as $b): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($b['booking_code']) ?></code></td>
                                    <td><?= htmlspecialchars($b['uname']) ?></td>
                                    <td><?= htmlspecialchars($b['title']) ?></td>
                                    <td>&#8377;<?= number_format($b['total_amount'], 2) ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($b['booking_date'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="td-empty">No bookings yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>