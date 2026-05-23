<?php
require_once __DIR__ . '/../app/config/config.php';

// Simple authentication check - only logged-in super admin or localhost can run this for safety.
// If the user wants to run it on VPS, they can also run it once if they define a secret or if we do a session check.
session_start();
$isAdmin = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$isLocalhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']);

// If neither admin nor localhost, require a secret token to bypass (or display simple access-denied)
$bypassToken = $_GET['secret'] ?? '';
$secretKey = 'myclassroom_debug'; // Simple default secret fallback for remote setup

if (!$isAdmin && !$isLocalhost && $bypassToken !== $secretKey) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><title>Access Denied</title><meta name="viewport" content="width=device-width,initial-scale=1.0"><style>body{background:#f8f9fa;color:#111;font-family:system-ui,-apple-system,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;padding:20px;box-sizing:border-box;}div{background:#fff;padding:32px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.06);max-width:480px;text-align:center;border:1px solid rgba(229,57,53,0.12);}</style></head><body><div><h2 style="color:#e53935;margin-top:0">🔒 Diagnostics Locked</h2><p style="color:#555;line-height:1.5">For security, the deployment diagnostics page is restricted to local execution or authenticated administrators.</p><p style="color:#777;font-size:0.9rem">To view this page on your remote VPS, append the secret token to the URL:<br><code style="display:inline-block;background:#eee;padding:4px 8px;border-radius:4px;margin-top:8px;font-family:monospace">?secret=myclassroom_debug</code></p></div></body></html>';
    exit;
}

// Handle clear log action
$_ROOT_DIAG = dirname(__DIR__);
if (isset($_GET['clear_log'])) {
    $logFile = $_ROOT_DIAG . '/logs/upload_errors.log';
    if (file_exists($logFile)) @unlink($logFile);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?secret=' . urlencode($bypassToken));
    exit;
}

// Gather PHP and Server Info
$phpVersion = PHP_VERSION;
$webServer = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$os = PHP_OS_NAME;
$currentUser = function_exists('posix_getpwuid') && function_exists('posix_geteuid') 
    ? posix_getpwuid(posix_geteuid())['name'] 
    : (getenv('USERNAME') ?: 'webserver-user');

// Check Extensions
$extensions = [
    'pdo_sqlite' => [
        'title' => 'PDO SQLite',
        'desc' => 'Required to communicate with the SQLite database file.',
        'status' => extension_loaded('pdo_sqlite')
    ],
    'fileinfo' => [
        'title' => 'FileInfo',
        'desc' => 'Required for secure, server-side MIME-type verification on uploads.',
        'status' => extension_loaded('fileinfo')
    ],
    'mbstring' => [
        'title' => 'MBString',
        'desc' => 'Required for multi-byte string functions and secure text operations.',
        'status' => extension_loaded('mbstring')
    ],
    'openssl' => [
        'title' => 'OpenSSL',
        'desc' => 'Required for secure token generation and cryptographic tasks.',
        'status' => extension_loaded('openssl')
    ]
];

// Check Write Permissions
$_ROOT = dirname(__DIR__);
$paths = [
    'database_dir' => [
        'title' => 'Database Directory',
        'path' => $_ROOT . '/database',
        'desc' => 'SQLite needs write permissions on the parent directory to manage file locks and write journals.',
        'writable' => is_writable($_ROOT . '/database')
    ],
    'database_file' => [
        'title' => 'Database Storage File',
        'path' => $_ROOT . '/database/lms.db',
        'desc' => 'Required to save profiles, courses, enrollments, and payment submissions.',
        'writable' => file_exists($_ROOT . '/database/lms.db') ? is_writable($_ROOT . '/database/lms.db') : is_writable($_ROOT . '/database')
    ],
    'uploads_dir' => [
        'title' => 'Uploads Root Directory',
        'path' => $_ROOT . '/public/assets/uploads',
        'desc' => 'Required to store tutor profile pictures, course thumbnails, materials, and student slips.',
        'writable' => is_writable($_ROOT . '/public/assets/uploads')
    ],
    'uploads_profiles' => [
        'title' => 'Uploads - Profiles Directory',
        'path' => $_ROOT . '/public/assets/uploads/profiles',
        'desc' => 'Required to store student and tutor avatar pictures.',
        'writable' => file_exists($_ROOT . '/public/assets/uploads/profiles') ? is_writable($_ROOT . '/public/assets/uploads/profiles') : is_writable($_ROOT . '/public/assets/uploads')
    ],
    'uploads_payments' => [
        'title' => 'Uploads - Payments Directory',
        'path' => $_ROOT . '/public/assets/uploads/payments',
        'desc' => 'Required to store bank payment slip files.',
        'writable' => file_exists($_ROOT . '/public/assets/uploads/payments') ? is_writable($_ROOT . '/public/assets/uploads/payments') : is_writable($_ROOT . '/public/assets/uploads')
    ],
    'uploads_thumbnails' => [
        'title' => 'Uploads - Thumbnails Directory',
        'path' => $_ROOT . '/public/assets/uploads/thumbnails',
        'desc' => 'Required to store course thumbnail images.',
        'writable' => file_exists($_ROOT . '/public/assets/uploads/thumbnails') ? is_writable($_ROOT . '/public/assets/uploads/thumbnails') : is_writable($_ROOT . '/public/assets/uploads')
    ],
    'uploads_tutor_profile' => [
        'title' => 'Uploads - Tutor Profile images Directory',
        'path' => $_ROOT . '/public/assets/uploads/tutor-profile_image',
        'desc' => 'Required to store tutor avatars.',
        'writable' => file_exists($_ROOT . '/public/assets/uploads/tutor-profile_image') ? is_writable($_ROOT . '/public/assets/uploads/tutor-profile_image') : is_writable($_ROOT . '/public/assets/uploads')
    ],
    'uploads_tutor_banner' => [
        'title' => 'Uploads - Tutor Banner images Directory',
        'path' => $_ROOT . '/public/assets/uploads/tutor-banner_image',
        'desc' => 'Required to store tutor banner images.',
        'writable' => file_exists($_ROOT . '/public/assets/uploads/tutor-banner_image') ? is_writable($_ROOT . '/public/assets/uploads/tutor-banner_image') : is_writable($_ROOT . '/public/assets/uploads')
    ],
    'uploads_materials' => [
        'title' => 'Uploads - Course Materials Directory',
        'path' => $_ROOT . '/public/assets/uploads/materials',
        'desc' => 'Required to store tutor lecture/course materials.',
        'writable' => file_exists($_ROOT . '/public/assets/uploads/materials') ? is_writable($_ROOT . '/public/assets/uploads/materials') : is_writable($_ROOT . '/public/assets/uploads')
    ],
    'logs_dir' => [
        'title' => 'Logs Directory',
        'path' => $_ROOT . '/logs',
        'desc' => 'Required to log system errors and trace payment/CSRF debug warnings.',
        'writable' => is_writable($_ROOT . '/logs') || (!file_exists($_ROOT . '/logs') && is_writable($_ROOT))
    ]
];

// PHP Config Parameters
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$memoryLimit = ini_get('memory_limit');
$uploadTmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
$uploadTmpWritable = is_writable($uploadTmpDir);

// Helper to convert ini shorthands to bytes
function toBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    return $val;
}

$uploadMaxBytes = toBytes($uploadMax);
$postMaxBytes = toBytes($postMax);
$warningUploads = $uploadMaxBytes < (5 * 1024 * 1024); // smaller than 5MB

// Read last N lines of upload_errors.log if it exists
$uploadErrorLog = $_ROOT . '/logs/upload_errors.log';
$uploadErrorLines = [];
if (file_exists($uploadErrorLog)) {
    $allLines = file($uploadErrorLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($allLines !== false) {
        $uploadErrorLines = array_slice(array_reverse($allLines), 0, 20); // last 20 lines
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Deployment Diagnostics — MyClassroom</title>
    <style>
        :root {
            --primary: #e53935;
            --primary-dark: #b71c1c;
            --success: #2e7d32;
            --success-bg: rgba(46, 125, 50, 0.08);
            --danger: #c62828;
            --danger-bg: rgba(198, 40, 40, 0.08);
            --warning: #ef6c00;
            --warning-bg: rgba(239, 108, 0, 0.08);
            --bg: #f8f9fa;
            --surface: #ffffff;
            --border: rgba(229, 57, 53, 0.12);
            --text-dark: #111111;
            --text-muted: #555555;
            --radius: 16px;
            --radius-sm: 8px;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
        }
        body {
            background-color: var(--bg);
            color: var(--text-dark);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        .logo-text {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .logo-text span {
            color: var(--primary);
        }
        .subtitle {
            color: var(--text-muted);
            margin-top: 8px;
            font-size: 1.1rem;
        }
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            padding: 32px;
            margin-bottom: 24px;
        }
        .card-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid var(--border);
            padding-bottom: 12px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            background: #fdfdfd;
            padding: 16px;
            border-radius: var(--radius-sm);
            border: 1px solid #eef0f2;
        }
        .info-label {
            font-size: 0.85rem;
            color: #777;
            font-weight: 600;
            text-transform: uppercase;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #222;
            margin-top: 4px;
        }
        .row-check {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid #f1f3f5;
            gap: 20px;
        }
        .row-check:last-child {
            border-bottom: none;
        }
        .check-details {
            flex: 1;
        }
        .check-title {
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .check-path {
            font-family: monospace;
            font-size: 0.85rem;
            background: #f1f3f5;
            padding: 2px 6px;
            border-radius: 4px;
            color: #d63384;
            display: inline-block;
            margin-top: 4px;
            word-break: break-all;
        }
        .check-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .badge-ok {
            background-color: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(46, 125, 50, 0.2);
        }
        .badge-fail {
            background-color: var(--danger-bg);
            color: var(--danger);
            border: 1px solid rgba(198, 40, 40, 0.2);
        }
        .badge-warn {
            background-color: var(--warning-bg);
            color: var(--warning);
            border: 1px solid rgba(239, 108, 0, 0.2);
        }
        .guide-box {
            background: #fff8f8;
            border-left: 4px solid var(--primary);
            padding: 20px;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
            margin-top: 24px;
        }
        .guide-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        pre {
            background: #212529;
            color: #f8f9fa;
            padding: 16px;
            border-radius: var(--radius-sm);
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.9rem;
            margin: 12px 0 0;
            line-height: 1.4;
        }
        .pre-comment {
            color: #8c98a4;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 40px;
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="logo-text">MyClass<span>room</span></h1>
            <p class="subtitle">🔧 Server Deployment & Diagnostics Utility</p>
        </div>

        <!-- 1. Environment Info -->
        <div class="card">
            <h2 class="card-title">🖥️ Server Environment</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">PHP Version</div>
                    <div class="info-value"><?= htmlspecialchars($phpVersion) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Server OS</div>
                    <div class="info-value"><?= htmlspecialchars($os) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Web Server</div>
                    <div class="info-value"><?= htmlspecialchars($webServer) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Active Process Owner</div>
                    <div class="info-value"><?= htmlspecialchars($currentUser) ?></div>
                </div>
            </div>
        </div>

        <!-- 2. Write Permissions -->
        <div class="card">
            <h2 class="card-title">📁 File System & Write Permissions</h2>
            <div class="check-list">
                <?php 
                $hasPermErrors = false;
                foreach ($paths as $key => $p): 
                    if (!$p['writable']) $hasPermErrors = true;
                ?>
                    <div class="row-check">
                        <div class="check-details">
                            <div class="check-title"><?= htmlspecialchars($p['title']) ?></div>
                            <div class="check-path"><?= htmlspecialchars($p['path']) ?></div>
                            <div class="check-desc"><?= htmlspecialchars($p['desc']) ?></div>
                        </div>
                        <div>
                            <?php if ($p['writable']): ?>
                                <span class="badge badge-ok">✅ Writable</span>
                            <?php else: ?>
                                <span class="badge badge-fail">❌ Unwritable</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($hasPermErrors): ?>
                <div class="guide-box">
                    <div class="guide-title">💡 How to Fix File Permissions on Your VPS (Linux)</div>
                    <p style="font-size:0.95rem;color:var(--text-muted);">
                        To allow the web server (usually running as the user <strong>www-data</strong> or <strong>apache</strong>) to write to the database and upload folders, log into your VPS via SSH and execute the following terminal commands:
                    </p>
                    <pre><span class="pre-comment"># 1. Navigate to your website folder</span>
cd <?= htmlspecialchars($_ROOT) ?>

<span class="pre-comment"># 2. Grant group ownership of files to the web server user</span>
sudo chown -R ubuntu:www-data .

<span class="pre-comment"># 3. Grant the web server write access to directories</span>
sudo chmod -R 775 database public/assets/uploads logs

<span class="pre-comment"># 4. Optional: If logs folder doesn't exist, create and own it</span>
mkdir -p logs && sudo chmod 775 logs</pre>
                </div>
            <?php else: ?>
                <div style="background:var(--success-bg);color:var(--success);padding:14px;border-radius:var(--radius-sm);margin-top:16px;text-align:center;font-weight:600;">
                    🎉 All required database and upload directories are perfectly writable on your file system!
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Extensions -->
        <div class="card">
            <h2 class="card-title">🧩 Required PHP Extensions</h2>
            <div class="check-list">
                <?php 
                $hasExtErrors = false;
                foreach ($extensions as $key => $ext): 
                    if (!$ext['status']) $hasExtErrors = true;
                ?>
                    <div class="row-check">
                        <div class="check-details">
                            <div class="check-title"><?= htmlspecialchars($ext['title']) ?></div>
                            <div class="check-desc"><?= htmlspecialchars($ext['desc']) ?></div>
                        </div>
                        <div>
                            <?php if ($ext['status']): ?>
                                <span class="badge badge-ok">✅ Enabled</span>
                            <?php else: ?>
                                <span class="badge badge-fail">❌ Missing</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($hasExtErrors): ?>
                <div class="guide-box">
                    <div class="guide-title">💡 How to Enable Missing Extensions on Your VPS (Linux)</div>
                    <p style="font-size:0.95rem;color:var(--text-muted);">
                        Log into your VPS via SSH and run these commands to install the required PHP packages for your PHP version (e.g. PHP 8.1):
                    </p>
                    <pre><span class="pre-comment"># 1. Install SQLite3 and core library packages</span>
sudo apt-get update
sudo apt-get install php-sqlite3 php-mbstring php-xml php-curl

<span class="pre-comment"># 2. Restart the web server (Apache/Nginx) to apply updates</span>
sudo systemctl restart apache2
<span class="pre-comment"># OR if using Nginx + PHP-FPM:</span>
sudo systemctl restart php8.1-fpm</pre>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. Upload Configurations -->
        <div class="card">
            <h2 class="card-title">⚙️ PHP Upload Limits</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Max Upload Size (filesize)</div>
                    <div class="info-value"><?= htmlspecialchars($uploadMax) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Max Post Request Size</div>
                    <div class="info-value"><?= htmlspecialchars($postMax) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Memory Limit</div>
                    <div class="info-value"><?= htmlspecialchars($memoryLimit) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Upload Temp Directory</div>
                    <div class="info-value" style="font-size:0.85rem;word-break:break-all;"><?= htmlspecialchars($uploadTmpDir ?: '(system default)') ?></div>
                </div>
            </div>

            <!-- Temp dir writability check -->
            <div class="row-check" style="border-bottom:none;padding-top:8px;">
                <div class="check-details">
                    <div class="check-title">PHP Upload Temp Directory Writability</div>
                    <div class="check-path"><?= htmlspecialchars($uploadTmpDir) ?></div>
                    <div class="check-desc">PHP stores uploaded files here temporarily before <code>move_uploaded_file()</code> moves them. If this is not writable, all uploads fail immediately.</div>
                </div>
                <div>
                    <?php if ($uploadTmpWritable): ?>
                        <span class="badge badge-ok">✅ Writable</span>
                    <?php else: ?>
                        <span class="badge badge-fail">❌ Not Writable</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($warningUploads || !$uploadTmpWritable): ?>
                <div class="guide-box" style="background:#fffaf0;border-left-color:var(--warning);">
                    <div class="guide-title" style="color:var(--warning);"><span style="font-size:1.2rem">⚠️</span> PHP Upload Configuration Issues Detected</div>
                    <p style="font-size:0.95rem;color:var(--text-muted);">
                        Edit your active <strong>php.ini</strong> file (or create a <code>.user.ini</code> in your web root) and update these settings:
                    </p>
                    <pre><span class="pre-comment"># Edit php.ini or create /var/www/myclassroom/public/.user.ini with:</span>
upload_max_filesize = 32M
post_max_size = 32M
memory_limit = 128M
upload_tmp_dir = /tmp</pre>
                    <p style="font-size:0.9rem;color:var(--text-muted);margin-top:12px;">Then restart your web server: <code>sudo systemctl restart apache2</code></p>
                </div>
            <?php else: ?>
                <div style="background:rgba(46, 125, 50, 0.04);color:var(--success);padding:14px;border-radius:var(--radius-sm);margin-top:16px;text-align:center;font-weight:600;">
                    🚀 PHP upload configuration looks good — supports files up to <?= htmlspecialchars($uploadMax) ?>.
                </div>
            <?php endif; ?>
        </div>

        <!-- 5. Upload Error Log -->
        <div class="card">
            <h2 class="card-title">📋 Upload Error Log <span style="font-size:0.85rem;font-weight:400;color:#888;">(last 20 entries)</span></h2>
            <?php if (empty($uploadErrorLines)): ?>
                <div style="background:rgba(46,125,50,0.04);color:var(--success);padding:20px;border-radius:var(--radius-sm);text-align:center;font-weight:600;">
                    ✅ No upload errors logged yet. The log file is empty or has not been created.
                </div>
                <p style="color:var(--text-muted);font-size:0.9rem;margin-top:12px;">
                    Try uploading a file now. If it fails, refresh this page — the exact error reason will appear here.
                </p>
            <?php else: ?>
                <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:12px;">
                    Most recent errors are shown first. This helps you identify exactly why <code>move_uploaded_file()</code> is failing on your server.
                </p>
                <pre style="font-size:0.8rem;max-height:400px;overflow-y:auto;"><?php foreach ($uploadErrorLines as $line): ?><?= htmlspecialchars($line) . "\n" ?><?php endforeach; ?></pre>
                <div style="margin-top:16px;">
                    <a href="?secret=<?= htmlspecialchars($bypassToken) ?>&clear_log=1" class="btn btn-danger btn-sm" onclick="return confirm('Clear the upload error log?')">🗑️ Clear Log</a>
                </div>
            <?php endif; ?>
        </div>

        <a href="index.php" class="back-link">← Return to Home Page</a>
    </div>
</body>
</html>
