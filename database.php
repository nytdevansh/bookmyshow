<?php
// Check if DATABASE_URL is set (standard for Heroku/Render database URLs)
$dbUrl = getenv('DATABASE_URL');

if ($dbUrl) {
    $parsedUrl = parse_url($dbUrl);
    $host = $parsedUrl['host'] ?? 'localhost';
    $port = $parsedUrl['port'] ?? '5432';
    $db   = ltrim($parsedUrl['path'] ?? '/bookmyshow', '/');
    $user = $parsedUrl['user'] ?? 'postgres';
    $pass = $parsedUrl['pass'] ?? '';
} else {
    // Fall back to individual env variables or localhost defaults
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '5432';
    $db   = getenv('DB_NAME') ?: 'bookmyshow';
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASS') ?: '';
}

try {
    $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Auto-bootstrap schema if tables do not exist
    try {
        $pdo->query("SELECT 1 FROM movies LIMIT 1");
    } catch (PDOException $schemaException) {
        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            try {
                $pdo->exec($sql);
            } catch (PDOException $execException) {
                die("Failed to auto-initialize database schema: " . $execException->getMessage());
            }
        }
    }

    // Ensure default admin exists with the correct password ('admin123')
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = 'admin@bookmyshow.com'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        $adminPassword = 'admin123';
        if (!$admin) {
            $hashed = password_hash($adminPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@bookmyshow.com', ?, 'admin')");
            $stmt->execute([$hashed]);
        } else {
            // If the password hash corresponds to the old 'password' hash, update it to 'admin123'
            if (password_verify('password', $admin['password'])) {
                $hashed = password_hash($adminPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $admin['id']]);
            }
        }
    } catch (PDOException $adminException) {
        // Ignore if the users table doesn't exist yet
    }
} catch (PDOException $e) {
    // Show a beautiful, user-friendly error page with troubleshooting steps
    $isRender = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'render.com') !== false) 
                || getenv('RENDER') 
                || getenv('RENDER_SERVICE_ID');
    
    header('HTTP/1.1 500 Internal Server Error');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Failed | BookMyShow</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
                color: #f8fafc;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
                padding: 20px;
                box-sizing: border-box;
            }
            .card {
                background: rgba(30, 41, 59, 0.7);
                backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 16px;
                padding: 40px;
                max-width: 600px;
                width: 100%;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            }
            h1 {
                font-size: 24px;
                margin-top: 0;
                color: #f43f5e;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            p {
                color: #cbd5e1;
                line-height: 1.6;
                font-size: 15px;
            }
            .error-details {
                background: rgba(0, 0, 0, 0.2);
                border-left: 4px solid #f43f5e;
                padding: 12px;
                font-family: monospace;
                font-size: 13px;
                color: #fda4af;
                border-radius: 4px;
                margin: 20px 0;
                word-break: break-all;
            }
            .steps {
                margin-top: 25px;
            }
            .step-title {
                font-weight: 600;
                color: #38bdf8;
                margin-bottom: 5px;
                font-size: 16px;
            }
            .step-desc {
                margin-left: 0;
                margin-top: 0;
                margin-bottom: 15px;
            }
            code {
                background: rgba(255, 255, 255, 0.1);
                padding: 2px 6px;
                border-radius: 4px;
                font-family: monospace;
                color: #f1f5f9;
            }
            .badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 9999px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 15px;
            }
            .badge-render {
                background: #4f46e5;
                color: #ffffff;
            }
            .badge-local {
                background: #0ea5e9;
                color: #ffffff;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-database-backup"><path d="M3 15v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5"/><path d="M3 5v.01"/><path d="M19 5v.01"/><path d="M5 21h14a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2z"/><path d="M12 12v4"/><path d="m9 15 3-3 3 3"/></svg>
                Database Connection Failed
            </h1>
            
            <?php if ($isRender): ?>
                <span class="badge badge-render">Render Environment</span>
                <p>The application is running on Render, but it cannot connect to your PostgreSQL database. This usually means your environment variables are not set or incorrect.</p>
                
                <div class="error-details">
                    <?php echo htmlspecialchars($e->getMessage()); ?>
                </div>

                <div class="steps">
                    <div class="step-title">1. Link your Database to the Web Service</div>
                    <div class="step-desc">In your Render Dashboard, go to your <strong>Web Service</strong>, click on the <strong>Environment</strong> tab, and click <strong>Add Environment Variable</strong>. Set:
                        <br><code>DATABASE_URL</code> = <em>(Paste the "Internal Database URL" from your Render PostgreSQL page)</em>
                    </div>

                    <div class="step-title">2. Alternative: Individual Variables</div>
                    <div class="step-desc">Or add these individual environment variables:
                        <br>• <code>DB_HOST</code>: Hostname of your database
                        <br>• <code>DB_PORT</code>: <code>5432</code>
                        <br>• <code>DB_NAME</code>: Database name
                        <br>• <code>DB_USER</code>: Username
                        <br>• <code>DB_PASS</code>: Password
                    </div>
                </div>
            <?php else: ?>
                <span class="badge badge-local">Local Environment</span>
                <p>The application is running locally, but it cannot connect to a PostgreSQL database on <code>localhost:5432</code>.</p>
                
                <div class="error-details">
                    <?php echo htmlspecialchars($e->getMessage()); ?>
                </div>

                <div class="steps">
                    <div class="step-title">1. Start PostgreSQL locally</div>
                    <div class="step-desc">Make sure PostgreSQL is installed and running on your local machine. If you installed it via Homebrew on macOS, run:
                        <br><code>brew services start postgresql</code>
                    </div>

                    <div class="step-title">2. Set Environment Variables</div>
                    <div class="step-desc">If your local PostgreSQL requires a different username or password, set them in your local environment, or edit the default credentials in <code>database.php</code>.</div>
                </div>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}