<?php
// ============================================================
// MyClassroom LMS — Core Configuration
// ============================================================

// Load .env file (project root is two levels up from this file: app/config/ -> app/ -> root)
$_envFile = dirname(dirname(__DIR__)) . '/.env';
if (file_exists($_envFile)) {
    $lines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $_line) {
        $_line = trim($_line);
        if ($_line === '' || str_starts_with($_line, '#')) continue;
        if (str_contains($_line, '=')) {
            [$_key, $_val] = explode('=', $_line, 2);
            $_ENV[trim($_key)] = trim($_val);
        }
    }
    unset($lines, $_line, $_key, $_val);
}
unset($_envFile);

define('APP_NAME', 'MyClassroom');
define('APP_VERSION', '1.0.0');
define('APP_URL', $_ENV['APP_URL'] ?? 'https://myclassroom.lk');

// ── Paths ─────────────────────────────────────────────────────────────────────
// Project root = dirname(dirname(__DIR__))  (app/config/ -> app/ -> root)
$_ROOT = dirname(dirname(__DIR__));

// Database: stored OUTSIDE the web root (project-root/database/)
define('DB_PATH', $_ROOT . '/database/lms.db');

// Uploads: inside public/assets/uploads/ (web-accessible, PHP execution blocked)
define('UPLOAD_DIR', $_ROOT . '/public/assets/uploads/');
define('UPLOAD_URL', '/assets/uploads/');   // URL prefix for browser

unset($_ROOT);

// ── Session ───────────────────────────────────────────────────────────────────
define('SESSION_NAME', 'myclassroom_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// ── OTP (Text.lk API) ─────────────────────────────────────────────────────────
define('TEXTLK_API_KEY', $_ENV['TEXTLK_API_KEY'] ?? 'YOUR_TEXTLK_API_KEY');
define('TEXTLK_SENDER_ID', 'myclassroom');
define('OTP_EXPIRY', 300); // 5 minutes

// ── Upload Settings ───────────────────────────────────────────────────────────
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/pjpeg', 'image/x-png']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// ── Security ──────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', '_csrf_token');

// ── Super Admin Default Credentials (seed only — change after first login) ───
define('SUPER_ADMIN_DEFAULT_EMAIL',    $_ENV['SUPER_ADMIN_EMAIL']    ?? 'admin@myclassroom.lk');
define('SUPER_ADMIN_DEFAULT_PASSWORD', $_ENV['SUPER_ADMIN_PASSWORD'] ?? 'Admin@123456');

// ── Error Reporting ───────────────────────────────────────────────────────────
$_isProduction = (($_ENV['APP_ENV'] ?? 'production') === 'production');
if ($_isProduction) {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    // Adjust log path for your VPS: e.g. '/var/www/myclassroom/logs/php_errors.log'
    ini_set('error_log', dirname(dirname(__DIR__)) . '/logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
unset($_isProduction);

// ── Timezone ──────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Colombo');
