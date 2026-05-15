<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance(); $tid = $user['id'];
$fname = sanitize($_POST['first_name']??'');
$lname = sanitize($_POST['last_name']??'');
$email = sanitize($_POST['email']??'');
$curPass = $_POST['current_password']??'';
$newPass = $_POST['new_password']??'';
$conPass = $_POST['confirm_password']??'';
if (!$fname||!$lname) jsonResponse(['success'=>false,'message'=>'Name required']);
$db->execute("UPDATE tutors SET first_name=?,last_name=?,email=?,updated_at=datetime('now') WHERE id=?",[$fname,$lname,$email,$tid]);
if ($curPass) {
    $t = $db->fetch("SELECT password FROM tutors WHERE id=?",[$tid]);
    if (!password_verify($curPass,$t['password'])) jsonResponse(['success'=>false,'message'=>'Current password incorrect']);
    if (strlen($newPass)<8) jsonResponse(['success'=>false,'message'=>'New password must be 8+ characters']);
    if ($newPass!==$conPass) jsonResponse(['success'=>false,'message'=>'Passwords do not match']);
    $db->execute("UPDATE tutors SET password=? WHERE id=?",[password_hash($newPass,PASSWORD_BCRYPT),$tid]);
}
jsonResponse(['success'=>true,'message'=>'Settings saved']);
