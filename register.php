<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/index.php');
    exit;
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $pass2 = $_POST['password2'] ?? '';

        if (!$name || !$email || !$pass)
            $error = 'All fields are required.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
            $error = 'Invalid email address.';
        elseif (strlen($pass) < 6)
            $error = 'Password must be at least 6 characters.';
        elseif ($pass !== $pass2)
            $error = 'Passwords do not match.';
        else {
            $chk = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $chk->execute([$email]);
            if ($chk->fetch())
                $error = 'Email already registered.';
            else {
                $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?,?,?)")
                    ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                $success = 'Account created! <a href="login.php">Sign in here</a>.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Account &mdash; BookMyShow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --red:       #dc3546;
      --red-dark:  #b52233;
      --dark:      #1a1a2e;
      --white:     #ffffff;
      --gray-bg:   #f5f5f5;
      --gray-line: #e0e0e0;
      --text-main: #1a1a2e;
      --text-sub:  #6b7280;
      --green:     #16a34a;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--gray-bg);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* NAVBAR */
    .navbar {
      background: var(--white);
      box-shadow: 0 2px 12px rgba(0,0,0,.08);
      height: 62px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 36px;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .navbar .logo {
      font-size: 1.5rem;
      font-weight: 900;
      color: var(--red);
      text-decoration: none;
      letter-spacing: -0.5px;
    }
    .navbar .logo span { color: var(--dark); }
    .nav-links { display: flex; align-items: center; gap: 24px; }
    .nav-links a {
      font-size: .92rem;
      font-weight: 700;
      color: var(--text-main);
      text-decoration: none;
      transition: color .2s;
    }
    .nav-links a:hover { color: var(--red); }
    .nav-links .btn-nav {
      background: var(--red);
      color: var(--white) !important;
      padding: 7px 20px;
      border-radius: 6px;
      transition: background .2s, transform .15s;
    }
    .nav-links .btn-nav:hover { background: var(--red-dark); transform: translateY(-1px); }

    /* MAIN SPLIT LAYOUT */
    .main-wrap {
      flex: 1;
      display: flex;
      align-items: stretch;
      min-height: calc(100vh - 62px - 56px);
    }

    /* Left cinematic panel */
    .cinema-panel {
      flex: 1 1 42%;
      background: var(--dark);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 60px 48px;
      position: relative;
      overflow: hidden;
    }
    .cinema-panel::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(ellipse at 20% 20%, rgba(220,53,70,.18) 0%, transparent 55%),
        radial-gradient(ellipse at 80% 80%, rgba(22,33,62,.7) 0%, transparent 60%);
    }
    .cinema-panel .cp-inner { position: relative; z-index: 1; text-align: center; }
    .cinema-panel .brand-icon {
      font-size: 3.6rem;
      margin-bottom: 18px;
      display: block;
    }
    .cinema-panel h1 {
      font-size: 2.4rem;
      font-weight: 900;
      color: var(--white);
      line-height: 1.2;
      margin-bottom: 14px;
      letter-spacing: -0.5px;
    }
    .cinema-panel h1 span { color: var(--red); }
    .cinema-panel p {
      font-size: 1.05rem;
      color: rgba(255,255,255,.65);
      line-height: 1.7;
      max-width: 340px;
      margin-bottom: 28px;
    }
    .pill {
      display: inline-block;
      background: var(--red);
      color: var(--white);
      font-size: .82rem;
      font-weight: 800;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      padding: 8px 22px;
      border-radius: 50px;
      box-shadow: 0 4px 18px rgba(220,53,70,.45);
    }
    .perks {
      margin-top: 32px;
      list-style: none;
      text-align: left;
      display: inline-block;
    }
    .perks li {
      font-size: .92rem;
      color: rgba(255,255,255,.7);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .perks li .ck {
      width: 22px; height: 22px;
      background: rgba(220,53,70,.25);
      border: 1.5px solid var(--red);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: .7rem;
      color: var(--red);
    }
    .cinema-panel .dots {
      position: absolute;
      bottom: 32px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 8px;
    }
    .cinema-panel .dots span {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: rgba(255,255,255,.25);
    }
    .cinema-panel .dots span:nth-child(2) { background: var(--red); width: 22px; border-radius: 4px; }

    /* Right form panel */
    .form-panel {
      flex: 1 1 58%;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 32px;
      background: var(--gray-bg);
    }

    /* Form card */
    .card {
      background: var(--white);
      border-radius: 16px;
      box-shadow: 0 8px 40px rgba(0,0,0,.10);
      padding: 40px 44px 34px;
      width: 100%;
      max-width: 460px;
      animation: fadeSlideUp .45s ease both;
    }
    @keyframes fadeSlideUp {
      from { opacity: 0; transform: translateY(28px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .card-header { margin-bottom: 24px; }
    .card-header h2 {
      font-size: 1.65rem;
      font-weight: 800;
      color: var(--text-main);
      margin-bottom: 5px;
    }
    .card-header p {
      font-size: .9rem;
      color: var(--text-sub);
    }

    /* Alerts */
    .alert {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: .88rem;
      font-weight: 600;
      margin-bottom: 18px;
      border-left: 4px solid transparent;
      line-height: 1.5;
    }
    .alert-error {
      background: #fff5f5;
      border-left-color: var(--red);
      color: #c0392b;
    }
    .alert-success {
      background: #f0fdf4;
      border-left-color: var(--green);
      color: var(--green);
    }
    .alert-success a {
      color: var(--green);
      font-weight: 800;
    }

    /* Form groups */
    .form-group { margin-bottom: 16px; }
    .form-group label {
      display: block;
      font-size: .85rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 7px;
      letter-spacing: .3px;
    }
    .form-group input {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid var(--gray-line);
      border-radius: 8px;
      font-family: 'Nunito', sans-serif;
      font-size: .95rem;
      color: var(--text-main);
      background: #fafafa;
      outline: none;
      transition: border-color .2s, box-shadow .2s, background .2s;
    }
    .form-group input:focus {
      border-color: var(--red);
      box-shadow: 0 0 0 3.5px rgba(220,53,70,.13);
      background: var(--white);
    }
    .form-group input::placeholder { color: #b0b0b0; }

    /* Two-col row */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    /* Submit button */
    .btn-submit {
      width: 100%;
      padding: 12px;
      background: var(--red);
      color: var(--white);
      border: none;
      border-radius: 8px;
      font-family: 'Nunito', sans-serif;
      font-size: 1rem;
      font-weight: 800;
      letter-spacing: .4px;
      cursor: pointer;
      margin-top: 6px;
      transition: background .2s, transform .15s, box-shadow .2s;
      box-shadow: 0 4px 16px rgba(220,53,70,.30);
    }
    .btn-submit:hover {
      background: var(--red-dark);
      transform: translateY(-2px) scale(1.015);
      box-shadow: 0 6px 20px rgba(220,53,70,.40);
    }
    .btn-submit:active { transform: translateY(0) scale(1); }

    /* Password hint */
    .hint {
      font-size: .76rem;
      color: var(--text-sub);
      margin-top: 4px;
      font-weight: 600;
    }

    /* Switch link */
    .switch-link {
      text-align: center;
      margin-top: 20px;
      font-size: .88rem;
      color: var(--text-sub);
    }
    .switch-link a {
      color: var(--red);
      font-weight: 700;
      text-decoration: none;
      transition: opacity .2s;
    }
    .switch-link a:hover { opacity: .75; }

    /* FOOTER */
    footer {
      background: var(--dark);
      color: rgba(255,255,255,.5);
      text-align: center;
      padding: 16px 24px;
      font-size: .8rem;
      font-weight: 600;
      letter-spacing: .3px;
    }
    footer a { color: var(--red); text-decoration: none; }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .cinema-panel { display: none; }
      .form-panel { padding: 32px 16px; }
      .card { padding: 32px 24px 28px; }
      .navbar { padding: 0 20px; }
      .form-row { grid-template-columns: 1fr; gap: 0; }
    }
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <a class="logo" href="index.php">Book<span>MyShow</span></a>
    <div class="nav-links">
      <a href="index.php">Movies</a>
      <a href="login.php" class="btn-nav">Sign In</a>
    </div>
  </nav>

  <!-- MAIN -->
  <div class="main-wrap">

    <!-- Left: cinematic panel -->
    <div class="cinema-panel">
      <div class="cp-inner">
        <span class="brand-icon">&#127909;</span>
        <h1>Join<br/><span>BookMyShow</span></h1>
        <p>Create your free account and start exploring the best movies and events near you.</p>
        <span class="pill">Book. Watch. Enjoy.</span>
        <ul class="perks">
          <li><span class="ck">&#10003;</span> Instant booking confirmation</li>
          <li><span class="ck">&#10003;</span> Personalised recommendations</li>
          <li><span class="ck">&#10003;</span> Exclusive early-bird offers</li>
          <li><span class="ck">&#10003;</span> Easy cancellation &amp; refunds</li>
        </ul>
      </div>
      <div class="dots">
        <span></span><span></span><span></span>
      </div>
    </div>

    <!-- Right: form panel -->
    <div class="form-panel">
      <div class="card">
        <div class="card-header">
          <h2>Create Account</h2>
          <p>It's free and only takes a minute!</p>
        </div>

        <?php if ($error): ?>
          <div class="alert alert-error">&#9888; <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
          <div class="alert alert-success">&#10003; <?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="">
          <input type="hidden" name="csrf" value="<?= csrf() ?>"/>

          <div class="form-group">
            <label for="name">Full Name</label>
            <input
              type="text"
              id="name"
              name="name"
              placeholder="John Doe"
              value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
              required
              autocomplete="name"
            />
          </div>

          <div class="form-group">
            <label for="email">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="you@example.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              required
              autocomplete="email"
            />
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="password">Password</label>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Min. 6 characters"
                required
                autocomplete="new-password"
              />
              <div class="hint">At least 6 characters</div>
            </div>
            <div class="form-group">
              <label for="password2">Confirm Password</label>
              <input
                type="password"
                id="password2"
                name="password2"
                placeholder="Repeat password"
                required
                autocomplete="new-password"
              />
            </div>
          </div>

          <button type="submit" class="btn-submit">Create Account &rarr;</button>
        </form>

        <div class="switch-link">
          Already have one? <a href="login.php">Sign in</a>
        </div>
      </div>
    </div>

  </div>

  <!-- FOOTER -->
  <footer>
    &copy; <?= date('Y') ?> <a href="index.php">BookMyShow Clone</a>. All rights reserved.
  </footer>

</body>
</html>
