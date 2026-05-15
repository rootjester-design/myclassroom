<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$curPass = $_POST['current_password']??'';
$newPass = $_POST['new_password']??'';
$conPass = $_POST['confirm_password']??'';
if (!$curPass||!$newPass) jsonResponse(['success'=>false,'message'=>'All fields required']);
$admin = $db->fetch("SELECT password FROM admins WHERE id=?",[$user['id']]);
if (!password_verify($curPass,$admin['password'])) jsonResponse(['success'=>false,'message'=>'Current password incorrect']);
if (strlen($newPass)<8) jsonResponse(['success'=>false,'message'=>'New password must be 8+ characters']);
if ($newPass!==$conPass) jsonResponse(['success'=>false,'message'=>'Passwords do not match']);
$db->execute("UPDATE admins SET password=? WHERE id=?",[password_hash($newPass,PASSWORD_BCRYPT),$user['id']]);
jsonResponse(['success'=>true,'message'=>'Password updated successfully']);
