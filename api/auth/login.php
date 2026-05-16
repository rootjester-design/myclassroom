<?php
require_once '../../includes/helpers.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);

$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) jsonResponse(['success'=>false,'message'=>'Invalid CSRF token'],403);

$phone    = sanitize($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (!$phone || !$password) jsonResponse(['success'=>false,'message'=>'Phone and password are required']);

$db = Database::getInstance();

// Try student login
$student = $db->fetch("SELECT * FROM students WHERE phone=?", [$phone]);
if ($student && password_verify($password, $student['password'])) {
    if ($student['is_suspended']) jsonResponse(['success'=>false,'message'=>'Your account has been suspended. Contact support.']);
    loginUser($student, 'student');
    $db->execute("UPDATE students SET last_login=datetime('now') WHERE id=?", [$student['id']]);
    logActivity((int)$student['id'],'student','login','Student logged in');

    if (!empty($student['birthday']) && date('m-d', strtotime($student['birthday'])) === date('m-d')) {
        $already = $db->fetch("SELECT id FROM notifications WHERE user_id=? AND user_type='student' AND title='Happy Birthday!' AND date(created_at)=date('now')", [$student['id']]);
        if (!$already) {
            $message = "Happy Birthday, {$student['first_name']}! 🎉 Wishing you a great year of learning with MyClassroom.";
            $smsResult = sendSms($student['phone'], $message);
            if ($smsResult['success']) {
                createNotification($student['id'], 'student', 'Happy Birthday!', 'Enjoy your special day with a birthday greeting from MyClassroom.', 'success');
            }
        }
    }

    jsonResponse(['success'=>true,'message'=>'Login successful','redirect'=>APP_URL . '/student/dashboard.php']);
}

// Try tutor login
$tutor = $db->fetch("SELECT * FROM tutors WHERE phone=? OR email=?", [$phone, $phone]);
if ($tutor && password_verify($password, $tutor['password'])) {
    if ($tutor['is_suspended']) jsonResponse(['success'=>false,'message'=>'Your account has been suspended.']);
    if (!$tutor['is_approved']) jsonResponse(['success'=>false,'message'=>'Your account is pending approval.']);
    loginUser($tutor, 'tutor');
    logActivity((int)$tutor['id'],'tutor','login','Tutor logged in');
    jsonResponse(['success'=>true,'message'=>'Login successful','redirect'=>APP_URL . '/tutor/dashboard.php']);
}

// Try admin login
$admin = $db->fetch("SELECT * FROM admins WHERE email=?", [$phone]);
if ($admin && password_verify($password, $admin['password'])) {
    if (!$admin['is_active']) jsonResponse(['success'=>false,'message'=>'Admin account is disabled.']);
    loginUser($admin, 'super_admin');
    $db->execute("UPDATE admins SET last_login=datetime('now') WHERE id=?",[$admin['id']]);
    logActivity((int)$admin['id'],'admin','login','Admin logged in');
    jsonResponse(['success'=>true,'message'=>'Login successful','redirect'=>APP_URL . '/admin/dashboard.php']);
}

jsonResponse(['success'=>false,'message'=>'Invalid phone number or password']);
