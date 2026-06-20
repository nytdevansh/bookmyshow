<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../database.php';

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT poster, banner FROM movies WHERE id=?");
    $stmt->execute([$id]);
    $m = $stmt->fetch();
    if ($m) {
        if ($m['poster']) @unlink(__DIR__ . '/../assets/uploads/posters/' . $m['poster']);
        if ($m['banner']) @unlink(__DIR__ . '/../assets/uploads/banners/' . $m['banner']);
    }
    $pdo->prepare("DELETE FROM movies WHERE id=?")->execute([$id]);
    header('Location: ' . BASE_PATH . '/admin/movies.php?deleted=1');
    exit;
}

$movies = $pdo->query("SELECT * FROM movies ORDER BY id DESC")->fetchAll();
$cur    = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movies - Admin</title>
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

        .alert-inner {
            margin: 15px;
            padding: 9px 12px;
            border-radius: 5px;
            font-size: 13px;
            background: #e8f5e9;
            color: #2e7d32;
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

        .actions {
            display: flex;
            gap: 6px;
        }

        .thumb-img {
            width: 40px;
            height: 56px;
            object-fit: cover;
            border-radius: 4px;
        }

        .thumb-placeholder {
            width: 40px;
            height: 56px;
            background: #ddd;
            border-radius: 4px;
        }

        .badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 3px;
        }

        .badge.showing {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge.soon {
            background: #fff8e1;
            color: #f57c00;
        }

        .btn {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 5px;
            font-size: 12px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #ccc;
            color: #555;
            background: none;
        }

        .btn-red {
            background: #d9505a;
            color: #fff;
            border-color: #d9505a;
            padding: 7px 14px;
            font-size: 13px;
        }

        .btn-red:hover {
            background: #c0404a;
        }

        .btn:hover {
            border-color: #d9505a;
            color: #d9505a;
        }

        .btn-del {
            color: #d9505a;
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
                <img src="<?= BASE_PATH ?>/a.png" alt="BookMyShow">
                <small>Admin Panel</small>
            </div>
            <nav>
                <a href="<?= BASE_PATH ?>/admin/admin_dashboard.php" class="<?= $cur === 'admin_dashboard.php' ? 'active' : '' ?>">Dashboard</a>
                <a href="<?= BASE_PATH ?>/admin/movies.php" class="<?= $cur === 'movies.php'     ? 'active' : '' ?>">Movies</a>
                <a href="<?= BASE_PATH ?>/admin/add_movie.php" class="<?= $cur === 'add_movie.php'  ? 'active' : '' ?>">Add Movie</a>
                <a href="<?= BASE_PATH ?>/admin/shows.php" class="<?= $cur === 'shows.php'      ? 'active' : '' ?>">Shows</a>
                <hr>
                <a href="<?= BASE_PATH ?>/index.php">View Site</a>
                <a href="<?= BASE_PATH ?>/logout.php">Logout</a>
            </nav>
        </div>
        <div class="content">
            <div class="table-wrap">
                <div class="top">
                    <h3>Movies</h3>
                    <a href="<?= BASE_PATH ?>/admin/add_movie.php" class="btn btn-red">+ Add Movie</a>
                </div>

                <?php if ($_GET['deleted'] ?? false): ?><div class="alert-inner">Movie deleted.</div><?php endif; ?>
                <?php if ($_GET['saved']   ?? false): ?><div class="alert-inner">Movie saved.</div><?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Title</th>
                            <th>Genre</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($movies): ?>
                            <?php foreach ($movies as $m): ?>
                                <tr>
                                    <td>
                                        <?php if ($m['poster']): ?>
                                            <img src="<?= BASE_PATH ?>/assets/uploads/posters/<?= htmlspecialchars($m['poster']) ?>" class="thumb-img">
                                        <?php else: ?>
                                            <div class="thumb-placeholder"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= htmlspecialchars($m['title']) ?></strong></td>
                                    <td><?= htmlspecialchars($m['genre']) ?></td>
                                    <td><?= htmlspecialchars($m['duration']) ?></td>
                                    <td>
                                        <span class="badge <?= $m['status'] === 'showing' ? 'showing' : 'soon' ?>">
                                            <?= $m['status'] === 'showing' ? 'Now Showing' : 'Coming Soon' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                             <a href="<?= BASE_PATH ?>/admin/add_movie.php?edit=<?= $m['id'] ?>" class="btn">Edit</a>
                                             <a href="<?= BASE_PATH ?>/admin/shows.php?movie_id=<?= $m['id'] ?>" class="btn">Shows</a>
                                            <a href="?delete=<?= $m['id'] ?>" class="btn btn-del" onclick="return confirm('Delete this movie?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="td-empty">No movies yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>