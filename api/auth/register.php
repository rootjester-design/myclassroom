<?php
require_once '../../includes/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);

$phone    = sanitize($_POST['phone'] ?? '');
$fname    = sanitize($_POST['first_name'] ?? '');
$lname    = sanitize($_POST['last_name'] ?? '');
$birthday = sanitize($_POST['birthday'] ?? '');
$address  = sanitize($_POST['address'] ?? '');
$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) jsonResponse(['success'=>false,'message'=>'Invalid CSRF token'],403);

$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Validate
if (!$phone || !$fname || !$lname || !$birthday || !$address || !$password) {
    jsonResponse(['success'=>false,'message'=>'All fields are required']);
}
if (!validatePhone($phone)) jsonResponse(['success'=>false,'message'=>'Invalid phone number']);
if (!validatePassword($password)) jsonResponse(['success'=>false,'message'=>'Password must be at least 8 characters']);
if ($password !== $confirm) jsonResponse(['success'=>false,'message'=>'Passwords do not match']);

$db = Database::getInstance();

// Check phone not already registered
$exists = $db->fetch("SELECT id FROM students WHERE phone = ?", [$phone]);
if ($exists) jsonResponse(['success'=>false,'message'=>'This phone number is already registered']);

// Check OTP was verified (check used OTP exists)
$otpUsed = $db->fetch(
    "SELECT id FROM otp_tokens WHERE phone=? AND purpose='register' AND is_used=1 AND created_at > datetime('now','-10 minutes') ORDER BY id DESC LIMIT 1",
    [$phone]
);
if (!$otpUsed) jsonResponse(['success'=>false,'message'=>'Phone number not verified. Please verify OTP first.']);

$hashed = password_hash($password, PASSWORD_BCRYPT);
$studentId = generateStudentId();

try {
    $id = $db->insert(
        "INSERT INTO students (first_name,last_name,phone,password,birthday,address,student_id,is_verified) VALUES (?,?,?,?,?,?,?,1)",
        [$fname, $lname, $phone, $hashed, $birthday, $address, $studentId]
    );

    $user = $db->fetch("SELECT * FROM students WHERE id=?", [$id]);
    loginUser($user, 'student');
    logActivity((int)$id, 'student', 'register', 'New student registered');
    createNotification((int)$id,'student','Welcome to MyClassroom! 🎉','Start exploring courses in the Store tab.','success');

    jsonResponse(['success'=>true,'message'=>'Account created successfully','redirect'=>'/student/dashboard.php']);
} catch (Exception $e) {
    jsonResponse(['success'=>false,'message'=>'Registration failed. Please try again.']);
}
