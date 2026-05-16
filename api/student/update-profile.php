<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$sid = $user['id'];

$fname   = sanitize($_POST['first_name']??'');
$lname   = sanitize($_POST['last_name']??'');
$address = sanitize($_POST['address']??'');
$curPass = $_POST['current_password']??'';
$newPass = $_POST['new_password']??'';
$conPass = $_POST['confirm_password']??'';

if (!$fname||!$lname) jsonResponse(['success'=>false,'message'=>'Name fields are required']);

$db->execute("UPDATE students SET first_name=?,last_name=?,address=?,updated_at=datetime('now') WHERE id=?",
    [$fname,$lname,$address,$sid]);

if ($curPass) {
    $student = $db->fetch("SELECT password FROM students WHERE id=?",[$sid]);
    if (!password_verify($curPass,$student['password'])) jsonResponse(['success'=>false,'message'=>'Current password is incorrect']);
    if (strlen($newPass)<8) jsonResponse(['success'=>false,'message'=>'New password must be 8+ characters']);
    if ($newPass!==$conPass) jsonResponse(['success'=>false,'message'=>'Passwords do not match']);
    $db->execute("UPDATE students SET password=? WHERE id=?",[password_hash($newPass,PASSWORD_BCRYPT),$sid]);
}

jsonResponse(['success'=>true,'message'=>'Profile updated successfully']);
