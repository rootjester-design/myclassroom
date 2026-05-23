<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
if (!verifyCsrfToken()) jsonResponse(['success'=>false,'message'=>'Invalid CSRF token'],403);
$db = Database::getInstance(); $tid = $user['id'];
$display = sanitize($_POST['display_name']??'');
$desc    = sanitize($_POST['description']??'');
$exp     = sanitize($_POST['experience']??'');
$qual    = sanitize($_POST['qualifications']??'');
$subj    = sanitize($_POST['subjects']??'');
$contact = sanitize($_POST['contact_info']??'');
$bank    = sanitize($_POST['bank_details']??'');
$socialRaw = trim($_POST['social_links']??'');
$social  = $socialRaw ? (json_decode($socialRaw) ? json_encode(json_decode($socialRaw)) : '') : '';
$db->execute("UPDATE tutors SET display_name=?,description=?,experience=?,qualifications=?,subjects=?,contact_info=?,bank_details=?,social_links=?,updated_at=datetime('now') WHERE id=?",
    [$display,$desc,$exp,$qual,$subj,$contact,$bank,$social,$tid]);
$_SESSION['user'] = array_merge($_SESSION['user'],['display_name'=>$display,'description'=>$desc,'subjects'=>$subj,'bank_details'=>$bank]);
jsonResponse(['success'=>true,'message'=>'Profile updated']);
