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
$duration   = sanitize($_POST['duration']??'');
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
    $params = [$title,$desc,$price,$duration,$editId];
    if ($thumbnail) array_splice($params,-1,0,[$thumbnail]);
    $db->execute("UPDATE courses SET title=?,description=?,price=?,duration=?{$thumbSql},updated_at=datetime('now') WHERE id=?", $params);
    jsonResponse(['success'=>true,'message'=>'Course updated']);
}

// New course - generate unique slug
$baseSlug = strtolower(preg_replace('/[^a-z0-9]+/','-',$title));
$baseSlug = trim($baseSlug,'-'); // Remove leading/trailing dashes
$slug = $baseSlug;
$counter = 1;

// Ensure slug is unique
while ($db->fetch("SELECT id FROM courses WHERE slug=?",[$slug])) {
    $slug = $baseSlug.'-'.$counter;
    $counter++;
}

$db->insert("INSERT INTO courses (tutor_id,title,slug,description,price,duration,thumbnail) VALUES (?,?,?,?,?,?,?)",
    [$tid,$title,$slug,$desc,$price,$duration,$thumbnail]);
jsonResponse(['success'=>true,'message'=>'Course created successfully']);
