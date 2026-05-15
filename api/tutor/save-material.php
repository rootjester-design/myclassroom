<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance(); $tid = $user['id'];
$cid   = (int)($_POST['course_id']??0);
$title = sanitize($_POST['title']??'');
$desc  = sanitize($_POST['description']??'');
if (!$cid||!$title) jsonResponse(['success'=>false,'message'=>'Required fields missing']);
if (!isset($_FILES['file']) || empty($_FILES['file']['name'])) jsonResponse(['success'=>false,'message'=>'File is required']);
$own = $db->fetch("SELECT id FROM courses WHERE id=? AND tutor_id=?",[$cid,$tid]);
if (!$own) jsonResponse(['success'=>false,'message'=>'Course not found']);
$up = uploadFile($_FILES['file'],'materials');
if (!$up['success']) jsonResponse(['success'=>false,'message'=>$up['message']]);
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['file']['tmp_name']);
$db->insert("INSERT INTO course_materials (course_id,tutor_id,title,description,file_path,file_type,file_size) VALUES (?,?,?,?,?,?,?)",
    [$cid,$tid,$title,$desc,$up['path'],$mime,$_FILES['file']['size']]);
jsonResponse(['success'=>true,'message'=>'Material uploaded']);
