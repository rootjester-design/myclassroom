<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
if (!verifyCsrfToken()) jsonResponse(['success'=>false,'message'=>'Invalid CSRF token'],403);
$db = Database::getInstance(); $tid = $user['id'];
$cid   = (int)($_POST['course_id']??0);
$title = sanitize($_POST['title']??'');
$bunny_id = sanitize($_POST['bunny_video_id']??'');
$vurl  = sanitize($_POST['video_url']??'');
$desc  = sanitize($_POST['description']??'');
$dur   = sanitize($_POST['duration']??'');
if (!$cid||!$title||!$vurl) jsonResponse(['success'=>false,'message'=>'Required fields missing']);
$own = $db->fetch("SELECT id FROM courses WHERE id=? AND tutor_id=?",[$cid,$tid]);
if (!$own) jsonResponse(['success'=>false,'message'=>'Course not found']);
$db->insert("INSERT INTO recordings (course_id,tutor_id,title,bunny_video_id,video_url,description,duration) VALUES (?,?,?,?,?,?,?)",[$cid,$tid,$title,$bunny_id,$vurl,$desc,$dur]);
jsonResponse(['success'=>true,'message'=>'Recording added']);
