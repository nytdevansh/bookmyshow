<?php
require_once __DIR__ . '/../auth.php';
requireAdmin();
require_once __DIR__ . '/../database.php';

$editId = (int)($_GET['edit'] ?? 0);
$movie  = null;
if ($editId) {
    $s = $pdo->prepare("SELECT * FROM movies WHERE id=?");
    $s->execute([$editId]);
    $movie = $s->fetch();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $genre    = trim($_POST['genre'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $release  = $_POST['release_date'] ?? '';
    $desc     = trim($_POST['description'] ?? '');
    $trailer  = trim($_POST['trailer_url'] ?? '');
    $status   = $_POST['status'] ?? 'showing';
    $id       = (int)($_POST['id'] ?? 0);

    if (!$title || !$genre || !$duration || !$release) {
        $error = 'Please fill in all required fields.';
    } else {
        $poster = $movie['poster'] ?? '';
        $banner = $movie['banner'] ?? '';

        foreach (['poster' => &$poster, 'banner' => &$banner] as $field => &$ref) {
            if (!empty($_FILES[$field]['name']) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg', 'bmp'])) {
                    $error = 'Only image formats (JPG, PNG, GIF, WEBP, AVIF, SVG, BMP) are allowed.';
                    break;
                }
                $dir = __DIR__ . '/../assets/uploads/' . ($field === 'poster' ? 'posters' : 'banners') . '/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = uniqid() . '.' . $ext;
                
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $filename)) {
                    // Only delete the old file if it has a DIFFERENT name than the newly uploaded file
                    if ($ref && $ref !== $filename && file_exists($dir . $ref)) {
                        @unlink($dir . $ref);
                    }
                    $ref = $filename;
                }
            }
        }

        if (!$error) {
            if ($id) {
                $pdo->prepare("UPDATE movies SET title=?,genre=?,duration=?,release_date=?,description=?,poster=?,banner=?,trailer_url=?,status=? WHERE id=?")
                    ->execute([$title, $genre, $duration, $release, $desc, $poster, $banner, $trailer, $status, $id]);
            } else {
                $pdo->prepare("INSERT INTO movies (title,genre,duration,release_date,description,poster,banner,trailer_url,status) VALUES (?,?,?,?,?,?,?,?,?)")
                    ->execute([$title, $genre, $duration, $release, $desc, $poster, $banner, $trailer, $status]);
            }
            header('Location: ' . BASE_PATH . '/admin/movies.php?saved=1');
            exit;
        }
    }
}

$cur = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $movie ? 'Edit Movie' : 'Add Movie' ?> - Admin</title>
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

        .admin-form {
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            padding: 25px;
            max-width: 650px;
        }

        .admin-form .row2 {
            display: flex;
            gap: 15px;
        }

        .admin-form .row2 .form-group {
            flex: 1;
        }

        .form-group-wide {
            flex: 2 !important;
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

        .req {
            color: #d9505a;
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

        .img-preview {
            margin-bottom: 6px;
        }

        .img-preview img {
            height: 70px;
            border-radius: 4px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 5px;
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
                <a href="<?= BASE_PATH ?>/admin/movies.php" class="<?= $cur === 'movies.php'    ? 'active' : '' ?>">Movies</a>
                <a href="<?= BASE_PATH ?>/admin/add_movie.php" class="<?= $cur === 'add_movie.php' ? 'active' : '' ?>">Add Movie</a>
                <a href="<?= BASE_PATH ?>/admin/shows.php" class="<?= $cur === 'shows.php'     ? 'active' : '' ?>">Shows</a>
                <hr>
                <a href="<?= BASE_PATH ?>/index.php">View Site</a>
                <a href="<?= BASE_PATH ?>/logout.php">Logout</a>
            </nav>
        </div>
        <div class="admin-content">
            <h2><?= $movie ? 'Edit Movie' : 'Add Movie' ?></h2>

            <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="admin-form">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $movie['id'] ?? 0 ?>">

                    <div class="row2">
                        <div class="form-group form-group-wide">
                            <label>Title <span class="req">*</span></label>
                            <input type="text" name="title" value="<?= htmlspecialchars($movie['title'] ?? $_POST['title'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="showing" <?= ($movie['status'] ?? 'showing') === 'showing'    ? 'selected' : '' ?>>Now Showing</option>
                                <option value="coming_soon" <?= ($movie['status'] ?? '') === 'coming_soon' ? 'selected' : '' ?>>Coming Soon</option>
                            </select>
                        </div>
                    </div>

                    <div class="row2">
                        <div class="form-group">
                            <label>Genre <span class="req">*</span></label>
                            <input type="text" name="genre" value="<?= htmlspecialchars($movie['genre'] ?? '') ?>" placeholder="Action, Drama...">
                        </div>
                        <div class="form-group">
                            <label>Duration <span class="req">*</span></label>
                            <input type="text" name="duration" value="<?= htmlspecialchars($movie['duration'] ?? '') ?>" placeholder="2h 15m">
                        </div>
                        <div class="form-group">
                            <label>Release Date <span class="req">*</span></label>
                            <input type="date" name="release_date" value="<?= htmlspecialchars($movie['release_date'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?= htmlspecialchars($movie['description'] ?? '') ?></textarea>
                    </div>

                    <div class="row2">
                        <div class="form-group">
                            <label>Poster Image</label>
                            <?php if (!empty($movie['poster'])): ?>
                                <div class="img-preview"><img src="<?= BASE_PATH ?>/assets/uploads/posters/<?= htmlspecialchars($movie['poster']) ?>"></div>
                            <?php endif; ?>
                            <input type="file" name="poster" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label>Banner Image</label>
                            <?php if (!empty($movie['banner'])): ?>
                                <div class="img-preview"><img src="<?= BASE_PATH ?>/assets/uploads/banners/<?= htmlspecialchars($movie['banner']) ?>"></div>
                            <?php endif; ?>
                            <input type="file" name="banner" accept="image/*">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Trailer URL (YouTube embed)</label>
                        <input type="url" name="trailer_url" value="<?= htmlspecialchars($movie['trailer_url'] ?? '') ?>" placeholder="https://www.youtube.com/embed/...">
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-red"><?= $movie ? 'Update Movie' : 'Add Movie' ?></button>
                        <a href="<?= BASE_PATH ?>/admin/movies.php" class="btn btn-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>