<?php
require_once '../../includes/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'],403);

$phone = sanitize($_POST['phone'] ?? '');
$purpose = sanitize($_POST['purpose'] ?? 'register');

if (empty($phone)) {
    jsonResponse(['success' => false, 'message' => 'Phone number is required']);
}
if (!validatePhone($phone)) {
    jsonResponse(['success' => false, 'message' => 'Invalid phone number format']);
}
if (!in_array($purpose, ['register', 'reset'], true)) {
    $purpose = 'register';
}

$result = sendOtp($phone, $purpose);
jsonResponse($result);
