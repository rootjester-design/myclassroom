<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// ============================================================
// Session
// ============================================================
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false, // set true on HTTPS
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
        session_start();
    }
}

// ============================================================
// Auth Helpers
// ============================================================
function getAuthUser(): ?array {
    startSession();
    return $_SESSION['user'] ?? null;
}

function isLoggedIn(): bool {
    return getAuthUser() !== null;
}

function requireAuth(string $role = ''): array {
    $user = getAuthUser();
    if (!$user) {
        if (isAjax()) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized', 'redirect' => '/auth/login.php'], 401);
        }
        header('Location: /auth/login.php');
        exit;
    }
    if ($role && $user['role'] !== $role) {
        if (isAjax()) {
            jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
        }
        header('Location: /auth/login.php');
        exit;
    }
    return $user;
}

function loginUser(array $user, string $role): void {
    startSession();
    session_regenerate_id(true);
    $_SESSION['user'] = array_merge($user, ['role' => $role]);
}

function logoutUser(): void {
    startSession();
    session_destroy();
}

// ============================================================
// CSRF
// ============================================================
function generateCsrfToken(): string {
    startSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrfToken(string $token): bool {
    startSession();
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// ============================================================
// Response Helpers
// ============================================================
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data);
    exit;
}

function isAjax(): bool {
    return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
}

// ============================================================
// Sanitization & Validation
// ============================================================
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validatePhone(string $phone): bool {
    return preg_match('/^(?:\+94|0)[1-9][0-9]{8}$/', $phone);
}

function validatePassword(string $password): bool {
    return strlen($password) >= 8;
}

function formatPhone(string $phone): string {
    $phone = preg_replace('/\D/', '', $phone);
    if (strpos($phone, '0') === 0) {
        $phone = '94' . substr($phone, 1);
    } elseif (strpos($phone, '94') === 0) {
        // already formatted
    }
    return '+' . $phone;
}

// ============================================================
// OTP (Text.lk API)
// ============================================================
function sendOtp(string $phone, string $purpose = 'register'): array {
    $db = Database::getInstance();
    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    $expiresAt = date('Y-m-d H:i:s', time() + OTP_EXPIRY);

    // Invalidate old OTPs
    $db->execute(
        "UPDATE otp_tokens SET is_used = 1 WHERE phone = ? AND purpose = ? AND is_used = 0",
        [$phone, $purpose]
    );

    // Insert new OTP
    $db->insert(
        "INSERT INTO otp_tokens (phone, otp, purpose, expires_at) VALUES (?, ?, ?, ?)",
        [$phone, $otp, $purpose, $expiresAt]
    );

    // Send via Text.lk API
    $message = "Your MyClassroom verification code is: {$otp}. Valid for 5 minutes. Do not share this code.";

    if (TEXTLK_API_KEY === 'YOUR_TEXTLK_API_KEY') {
        // Development mode: return OTP directly
        return ['success' => true, 'message' => 'OTP sent (dev mode)', 'otp' => $otp];
    }

    $formattedPhone = formatPhone($phone);

    $ch = curl_init('https://app.text.lk/api/v3/sms/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . TEXTLK_API_KEY],
        CURLOPT_POSTFIELDS     => json_encode([
            'recipient' => $formattedPhone,
            'sender_id' => TEXTLK_SENDER_ID,
            'message'   => $message,
        ]),
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        return ['success' => true, 'message' => 'OTP sent successfully'];
    }
    return ['success' => false, 'message' => 'Failed to send OTP. Please try again.'];
}

function verifyOtp(string $phone, string $otp, string $purpose = 'register'): bool {
    $db = Database::getInstance();
    $record = $db->fetch(
        "SELECT * FROM otp_tokens WHERE phone = ? AND otp = ? AND purpose = ? AND is_used = 0 AND expires_at > datetime('now') ORDER BY id DESC LIMIT 1",
        [$phone, $otp, $purpose]
    );
    if ($record) {
        $db->execute("UPDATE otp_tokens SET is_used = 1 WHERE id = ?", [$record['id']]);
        return true;
    }
    return false;
}

// ============================================================
// File Upload
// ============================================================
function uploadFile(array $file, string $subDir = 'misc', array $allowedTypes = []): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File too large. Max 5MB allowed.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowed = empty($allowedTypes) ? array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES) : $allowedTypes;
    if (!in_array($mimeType, $allowed)) {
        return ['success' => false, 'message' => 'Invalid file type.'];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(16)) . '.' . strtolower($ext);
    $uploadPath = UPLOAD_DIR . $subDir . '/';

    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $uploadPath . $filename)) {
        return ['success' => false, 'message' => 'Failed to save file.'];
    }

    $publicPath = '/assets/uploads/' . $subDir . '/' . $filename;
    return ['success' => true, 'path' => APP_URL . $publicPath, 'filename' => $filename];
}

function assetPath(string $path): string {
    if (!$path) return '';
    if (strpos($path, '/assets/') === 0) {
        return APP_URL . $path;
    }
    return $path;
}

// ============================================================
// Student ID Generator
// ============================================================
function generateStudentId(): string {
    return 'MC' . date('Y') . str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
}

// ============================================================
// Activity Logger
// ============================================================
function logActivity(int $userId, string $userType, string $action, string $description = ''): void {
    try {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO activity_logs (user_id, user_type, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)",
            [
                $userId,
                $userType,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]
        );
    } catch (Exception $e) {
        // Silently fail
    }
}

// ============================================================
// Notifications
// ============================================================
function createNotification(int $userId, string $userType, string $title, string $message, string $type = 'info', int $relatedId = null, string $relatedType = null): void {
    try {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO notifications (user_id, user_type, title, message, type, related_id, related_type) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$userId, $userType, $title, $message, $type, $relatedId, $relatedType]
        );
    } catch (Exception $e) {
        // Silently fail
    }
}

// ============================================================
// Redirect helper
// ============================================================
function redirect(string $url): void {
    // Normalize redirect to absolute URL using APP_URL
    if (strpos($url, '/') === 0) {
        $url = rtrim(APP_URL, '/') . $url;
    } else if (strpos($url, 'http') !== 0) {
        $url = rtrim(APP_URL, '/') . '/' . ltrim($url, '/');
    }
    header('Location: ' . $url);
    exit;
}

// Init session on every request
startSession();
