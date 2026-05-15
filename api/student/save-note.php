<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$sid = $user['id'];

$courseId = (int)($_POST['course_id']??0);
$title    = sanitize($_POST['title']??'');
$content  = sanitize($_POST['content']??'');

if (!$courseId||!$title) jsonResponse(['success'=>false,'message'=>'Course and title required']);

// Check enrolled
$en = $db->fetch("SELECT id FROM enrollments WHERE student_id=? AND course_id=?",[$sid,$courseId]);
if (!$en) jsonResponse(['success'=>false,'message'=>'Not enrolled']);

$db->insert("INSERT INTO student_notes (student_id,course_id,title,content) VALUES (?,?,?,?)",[$sid,$courseId,$title,$content]);
jsonResponse(['success'=>true,'message'=>'Note saved']);
