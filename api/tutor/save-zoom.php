<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance(); $tid = $user['id'];
$cid  = (int)($_POST['course_id']??0);
$title= sanitize($_POST['title']??'');
$url  = sanitize($_POST['zoom_url']??'');
$mid  = sanitize($_POST['meeting_id']??'');
$pass = sanitize($_POST['passcode']??'');
$sched= sanitize($_POST['scheduled_at']??'');
$dur  = (int)($_POST['duration_minutes']??60);
if (!$cid||!$title||!$url) jsonResponse(['success'=>false,'message'=>'Required fields missing']);
$own = $db->fetch("SELECT id FROM courses WHERE id=? AND tutor_id=?",[$cid,$tid]);
if (!$own) jsonResponse(['success'=>false,'message'=>'Course not found']);
$db->insert("INSERT INTO zoom_links (course_id,tutor_id,title,zoom_url,meeting_id,passcode,scheduled_at,duration_minutes) VALUES (?,?,?,?,?,?,?,?)",[$cid,$tid,$title,$url,$mid,$pass,$sched?:null,$dur]);
jsonResponse(['success'=>true,'message'=>'Live class added']);
