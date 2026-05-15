<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance(); $tid = $user['id'];
$display = sanitize($_POST['display_name']??'');
$desc    = sanitize($_POST['description']??'');
$exp     = sanitize($_POST['experience']??'');
$qual    = sanitize($_POST['qualifications']??'');
$subj    = sanitize($_POST['subjects']??'');
$contact = sanitize($_POST['contact_info']??'');
$social  = sanitize($_POST['social_links']??'');
$db->execute("UPDATE tutors SET display_name=?,description=?,experience=?,qualifications=?,subjects=?,contact_info=?,social_links=?,updated_at=datetime('now') WHERE id=?",
    [$display,$desc,$exp,$qual,$subj,$contact,$social,$tid]);
$_SESSION['user'] = array_merge($_SESSION['user'],['display_name'=>$display,'description'=>$desc,'subjects'=>$subj]);
jsonResponse(['success'=>true,'message'=>'Profile updated']);
