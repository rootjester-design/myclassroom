<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance(); $tid = $user['id'];
$type = sanitize($_POST['type']??'');
$id   = (int)($_POST['id']??0);
if (!$type||!$id) jsonResponse(['success'=>false,'message'=>'Invalid request']);
$tables = ['zoom'=>'zoom_links','recording'=>'recordings','material'=>'course_materials','announcement'=>'announcements'];
if (!isset($tables[$type])) jsonResponse(['success'=>false,'message'=>'Invalid type']);
$db->execute("DELETE FROM {$tables[$type]} WHERE id=? AND tutor_id=?",[$id,$tid]);
jsonResponse(['success'=>true,'message'=>ucfirst($type).' deleted']);
