<?php
require_once '../../includes/helpers.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);

$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) jsonResponse(['success'=>false,'message'=>'Invalid CSRF token'],403);

$phone    = sanitize($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (!$phone || !$password) jsonResponse(['success'=>false,'message'=>'All fields required']);
if (!validatePassword($password)) jsonResponse(['success'=>false,'message'=>'Password must be 8+ characters']);

$db = Database::getInstance();

// Check verified reset OTP
$otpUsed = $db->fetch(
    "SELECT id FROM otp_tokens WHERE phone=? AND purpose='reset' AND is_used=1 AND created_at > datetime('now','-15 minutes') ORDER BY id DESC LIMIT 1",
    [$phone]
);
if (!$otpUsed) jsonResponse(['success'=>false,'message'=>'OTP verification required']);

$hashed = password_hash($password, PASSWORD_BCRYPT);

// Try updating student
$rows = $db->execute("UPDATE students SET password=?,updated_at=datetime('now') WHERE phone=?", [$hashed, $phone]);
if ($rows > 0) { jsonResponse(['success'=>true,'message'=>'Password reset successfully']); }

// Try tutor
$rows = $db->execute("UPDATE tutors SET password=?,updated_at=datetime('now') WHERE phone=?", [$hashed, $phone]);
if ($rows > 0) { jsonResponse(['success'=>true,'message'=>'Password reset successfully']); }

jsonResponse(['success'=>false,'message'=>'Phone number not found']);
