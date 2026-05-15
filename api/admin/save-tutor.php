<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();

$editId  = (int)($_POST['tutor_id']??0);
$fname   = sanitize($_POST['first_name']??'');
$lname   = sanitize($_POST['last_name']??'');
$email   = sanitize($_POST['email']??'');
$phone   = sanitize($_POST['phone']??'');
$pass    = $_POST['password']??'';
$subj    = sanitize($_POST['subjects']??'');
$display = sanitize($_POST['display_name']??'');
$approved= isset($_POST['is_approved'])?1:0;

if (!$fname||!$lname||!$email||!$phone) jsonResponse(['success'=>false,'message'=>'Required fields missing']);

if ($editId) {
    // Update
    $existing = $db->fetch("SELECT id FROM tutors WHERE id=?",[$editId]);
    if (!$existing) jsonResponse(['success'=>false,'message'=>'Tutor not found']);
    $db->execute("UPDATE tutors SET first_name=?,last_name=?,email=?,phone=?,subjects=?,display_name=?,updated_at=datetime('now') WHERE id=?",[$fname,$lname,$email,$phone,$subj,$display,$editId]);
    if ($pass) $db->execute("UPDATE tutors SET password=? WHERE id=?",[password_hash($pass,PASSWORD_BCRYPT),$editId]);
    logActivity((int)$user['id'],'admin','edit_tutor',"Edited tutor ID:{$editId}");
    jsonResponse(['success'=>true,'message'=>'Tutor updated']);
}

// Create new
if (!$pass||strlen($pass)<8) jsonResponse(['success'=>false,'message'=>'Password must be 8+ characters']);
$exists = $db->fetch("SELECT id FROM tutors WHERE email=? OR phone=?",[$email,$phone]);
if ($exists) jsonResponse(['success'=>false,'message'=>'Email or phone already exists']);

$id = $db->insert("INSERT INTO tutors (first_name,last_name,email,phone,password,subjects,display_name,is_approved,is_active) VALUES (?,?,?,?,?,?,?,?,1)",
    [$fname,$lname,$email,$phone,password_hash($pass,PASSWORD_BCRYPT),$subj,$display,$approved]);
logActivity((int)$user['id'],'admin','create_tutor',"Created tutor: {$fname} {$lname}");
jsonResponse(['success'=>true,'message'=>'Tutor account created successfully']);
