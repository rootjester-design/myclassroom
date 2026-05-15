<?php
require_once '../../includes/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);

$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'],403);

$phone = sanitize($_POST['phone'] ?? '');
$otp   = sanitize($_POST['otp'] ?? '');
$purpose = sanitize($_POST['purpose'] ?? 'register');

if (empty($phone) || empty($otp)) jsonResponse(['success'=>false,'message'=>'Phone and OTP are required']);
if (!in_array($purpose, ['register', 'reset'], true)) {
    $purpose = 'register';
}

$valid = verifyOtp($phone, $otp, $purpose);
if ($valid) {
    jsonResponse(['success'=>true,'message'=>'Phone verified successfully']);
} else {
    jsonResponse(['success'=>false,'message'=>'Invalid or expired OTP. Please try again.']);
}
