<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$tid = $user['id'];

$courseId   = (int)($_POST['course_id']??0);
$title      = sanitize($_POST['title']??'');
$desc       = sanitize($_POST['description']??'');
$price      = (float)($_POST['price']??0);
$monthly    = (float)($_POST['monthly_fee']??0);
$duration   = sanitize($_POST['duration']??'');
$level      = sanitize($_POST['level']??'beginner');
$editId     = (int)($_POST['course_id_edit']??0);

// Use course_id hidden field for edit detection
$editId = (int)($_POST['course_id']??0);
$isEdit = $editId > 0;

if (!$title) jsonResponse(['success'=>false,'message'=>'Course title required']);

$thumbnail = null;
if (!empty($_FILES['thumbnail']['name'])) {
    $up = uploadFile($_FILES['thumbnail'],'thumbnails',ALLOWED_IMAGE_TYPES);
    if ($up['success']) $thumbnail = $up['path'];
}

// Check if editing (course_id passed and belongs to tutor)
if ($isEdit) {
    $existing = $db->fetch("SELECT * FROM courses WHERE id=? AND tutor_id=?",[$editId,$tid]);
    if (!$existing) jsonResponse(['success'=>false,'message'=>'Course not found']);
    $thumbSql = $thumbnail ? ",thumbnail=?" : "";
    $params = [$title,$desc,$price,$monthly,$duration,$level,$editId];
    if ($thumbnail) array_splice($params,-1,0,[$thumbnail]);
    $db->execute("UPDATE courses SET title=?,description=?,price=?,monthly_fee=?,duration=?,level=?{$thumbSql},updated_at=datetime('now') WHERE id=?", $params);
    jsonResponse(['success'=>true,'message'=>'Course updated']);
}

// New course
$slug = strtolower(preg_replace('/[^a-z0-9]+/','-',$title)).'-'.time();
$db->insert("INSERT INTO courses (tutor_id,title,slug,description,price,monthly_fee,duration,level,thumbnail) VALUES (?,?,?,?,?,?,?,?,?)",
    [$tid,$title,$slug,$desc,$price,$monthly,$duration,$level,$thumbnail]);
jsonResponse(['success'=>true,'message'=>'Course created successfully']);
