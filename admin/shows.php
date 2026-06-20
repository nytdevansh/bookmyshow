<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../database.php';

$movieId = (int)($_GET['movie_id'] ?? 0);

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM shows WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: /admin/shows.php' . ($movieId ? "?movie_id=$movieId" : ''));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mid    = (int)$_POST['movie_id'];
    $date   = $_POST['show_date'];
    $time   = $_POST['show_time'];
    $screen = trim($_POST['screen_name']);
    $seats  = (int)$_POST['total_seats'];
    $price  = (float)$_POST['price'];

    if (!$mid || !$date || !$time || !$screen || !$price) {
        $error = 'All fields are required.';
    } else {
        $pdo->prepare("INSERT INTO shows (movie_id, show_date, show_time, screen_name, total_seats, price) VALUES (?,?,?,?,?,?)")
            ->execute([$mid, $date, $time, $screen, $seats, $price]);
        header('Location: /admin/shows.php?movie_id=' . $mid . '&saved=1');
        exit;
    }
}

$movies = $pdo->query("SELECT id, title FROM movies ORDER BY title")->fetchAll();

$sql    = "SELECT s.*, m.title FROM shows s JOIN movies m ON m.id=s.movie_id";
$params = [];
if ($movieId) {
    $sql .= " WHERE s.movie_id=?";
    $params[] = $movieId;
}
$sql .= " ORDER BY s.show_date DESC, s.show_time";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$shows = $stmt->fetchAll();

$cur = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shows - Admin</title>
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

        .admin-content {
            flex: 1;
            padding: 30px;
            background: #f5f5f5;
        }

        .admin-content h2 {
            font-size: 22px;
            margin-bottom: 20px;
        }

        .shows-layout {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }

        .shows-form-col {
            min-width: 260px;
            width: 260px;
        }

        .shows-form-col h3 {
            font-size: 16px;
            margin-bottom: 14px;
        }

        .shows-table-col {
            flex: 1;
        }

        .shows-filter {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .admin-form {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 25px;
        }

        .form-group {
            margin-bottom: 14px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            color: #555;
            margin-bottom: 5px;
        }

        .admin-form input,
        .admin-form select,
        .admin-form textarea {
            width: 100%;
            padding: 8px 11px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            outline: none;
            font-family: Arial, sans-serif;
        }

        .admin-form input:focus,
        .admin-form select:focus,
        .admin-form textarea:focus {
            border-color: #d9505a;
        }

        .btn {
            display: inline-block;
            padding: 7px 15px;
            border-radius: 5px;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-red {
            background: #d9505a;
            color: #fff;
        }

        .btn-red:hover {
            background: #c0404a;
        }

        .btn-outline {
            background: none;
            border: 1px solid #ccc;
            color: #555;
        }

        .btn-outline:hover {
            border-color: #d9505a;
            color: #d9505a;
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 12px;
        }

        .btn-full {
            width: 100%;
        }

        .btn-danger-text {
            color: #d9505a;
        }

        .alert {
            padding: 9px 12px;
            border-radius: 5px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .alert.error {
            background: #fdecea;
            color: #c62828;
        }

        .alert.success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .alert-spaced {
            margin-bottom: 12px;
        }

        .admin-table-wrap {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e5e5e5;
            overflow: hidden;
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
                <a href="admin/movies.php" class="<?= $cur === 'movies.php'    ? 'active' : '' ?>">Movies</a>
                <a href="admin/add_movie.php" class="<?= $cur === 'add_movie.php' ? 'active' : '' ?>">Add Movie</a>
                <a href="admin/shows.php" class="<?= $cur === 'shows.php'     ? 'active' : '' ?>">Shows</a>
                <hr>
                <a href="index.php">View Site</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
        <div class="admin-content">
            <div class="shows-layout">

                <!-- Add show form -->
                <div class="admin-form shows-form-col">
                    <h3>Add New Show</h3>
                    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Movie</label>
                            <select name="movie_id" required>
                                <option value="">Select movie</option>
                                <?php foreach ($movies as $m): ?>
                                    <option value="<?= $m['id'] ?>" <?= $m['id'] == $movieId ? 'selected' : '' ?>><?= htmlspecialchars($m['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="show_date" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Time</label>
                            <input type="time" name="show_time" required>
                        </div>
                        <div class="form-group">
                            <label>Screen</label>
                            <input type="text" name="screen_name" placeholder="Screen 1" required>
                        </div>
                        <div class="form-group">
                            <label>Total Seats <small style="color:#aaa">(max 260)</small></label>
                            <input type="number" name="total_seats" value="50" min="1" max="260" required>
                        </div>
                        <div class="form-group">
                            <label>Price (&#8377;)</label>
                            <input type="number" name="price" step="0.01" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-red btn-full">Add Show</button>
                    </form>
                </div>

                <!-- Shows table -->
                <div class="shows-table-col">
                    <?php if ($_GET['saved'] ?? false): ?>
                        <div class="alert success alert-spaced">Show added.</div>
                    <?php endif; ?>

                    <div class="shows-filter">
                        <a href="admin/shows.php" class="btn <?= !$movieId ? 'btn-red' : 'btn-outline' ?> btn-sm">All</a>
                        <?php foreach ($movies as $m): ?>
                            <a href="admin/shows.php?movie_id=<?= $m['id'] ?>" class="btn <?= $m['id'] == $movieId ? 'btn-red' : 'btn-outline' ?> btn-sm"><?= htmlspecialchars($m['title']) ?></a>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Movie</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Screen</th>
                                    <th>Seats</th>
                                    <th>Price</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($shows): ?>
                                    <?php foreach ($shows as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['title']) ?></td>
                                            <td><?= date('d M Y', strtotime($s['show_date'])) ?></td>
                                            <td><?= date('h:i A', strtotime($s['show_time'])) ?></td>
                                            <td><?= htmlspecialchars($s['screen_name']) ?></td>
                                            <td><?= $s['total_seats'] ?></td>
                                            <td>&#8377;<?= number_format($s['price'], 2) ?></td>
                                            <td>
                                                <a href="?delete=<?= $s['id'] ?><?= $movieId ? "&movie_id=$movieId" : '' ?>"
                                                    class="btn btn-outline btn-sm btn-danger-text"
                                                    onclick="return confirm('Delete this show?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="td-empty">No shows yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>