<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance(); $tid = $user['id'];
$field = isset($_FILES['profile_image']) ? 'profile_image' : (isset($_FILES['banner_image']) ? 'banner_image' : '');
if (!$field) jsonResponse(['success'=>false,'message'=>'No image provided']);
$up = uploadFile($_FILES[$field],'tutor-'.$field,ALLOWED_IMAGE_TYPES);
if (!$up['success']) jsonResponse(['success'=>false,'message'=>$up['message']]);
$db->execute("UPDATE tutors SET {$field}=?,updated_at=datetime('now') WHERE id=?",[$up['path'],$tid]);
$_SESSION['user'][$field] = $up['path'];
jsonResponse(['success'=>true,'path'=>$up['path'],'message'=>'Image updated']);
