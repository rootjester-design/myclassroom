<?php
// ============================================================
// MyClassroom LMS — Core Configuration
// ============================================================

define('APP_NAME', 'MyClassroom');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://myclassroom.lk'); // Change to your domain

// Database
define('DB_PATH', dirname(__DIR__) . '/database/lms.db');

// Session
define('SESSION_NAME', 'myclassroom_session');
define('SESSION_LIFETIME', 86400); // 24 hours

// OTP (Text.lk API)
define('TEXTLK_API_KEY', '4380|DXUfR2LWkQtVx23W0OqeJBscr3jQth1BvsUxZBM400ab218d'); // Replace with real key
define('TEXTLK_SENDER_ID', 'myclassroom');
define('OTP_EXPIRY', 300); // 5 minutes

// Upload Settings
define('UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_DOC_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Security
define('CSRF_TOKEN_NAME', '_csrf_token');

// Super Admin Default Credentials (change after first login)
define('SUPER_ADMIN_DEFAULT_EMAIL', 'admin@myclassroom.lk');
define('SUPER_ADMIN_DEFAULT_PASSWORD', 'Admin@123456');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Colombo');
