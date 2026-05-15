<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$sid = $user['id'];

if (empty($_FILES['profile_image']['name'])) jsonResponse(['success'=>false,'message'=>'No image provided']);

$upload = uploadFile($_FILES['profile_image'],'profiles',ALLOWED_IMAGE_TYPES);
if (!$upload['success']) jsonResponse(['success'=>false,'message'=>$upload['message']]);

$db->execute("UPDATE students SET profile_image=?,updated_at=datetime('now') WHERE id=?",[$upload['path'],$sid]);
$_SESSION['user']['profile_image'] = $upload['path'];

jsonResponse(['success'=>true,'message'=>'Photo updated','path'=>$upload['path']]);
