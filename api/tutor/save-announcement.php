<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance(); $tid = $user['id'];
$cid     = (int)($_POST['course_id']??0);
$title   = sanitize($_POST['title']??'');
$content = sanitize($_POST['content']??'');
if (!$cid||!$title||!$content) jsonResponse(['success'=>false,'message'=>'All fields required']);
$own = $db->fetch("SELECT id FROM courses WHERE id=? AND tutor_id=?",[$cid,$tid]);
if (!$own) jsonResponse(['success'=>false,'message'=>'Course not found']);
$db->insert("INSERT INTO announcements (course_id,tutor_id,title,content,type) VALUES (?,?,?,?,'course')",[$cid,$tid,$title,$content]);
// Notify enrolled students
$students = $db->fetchAll("SELECT student_id FROM enrollments WHERE course_id=? AND status='active'",[$cid]);
foreach ($students as $s) createNotification((int)$s['student_id'],'student','New Announcement: '.$title,$content,'info',$cid,'course');
jsonResponse(['success'=>true,'message'=>'Announcement posted']);
