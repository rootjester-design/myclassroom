<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$tid = $user['id'];
$id = (int)($_POST['id']??0);
if (!$id) jsonResponse(['success'=>false,'message'=>'Course ID required']);
$db->execute("DELETE FROM courses WHERE id=? AND tutor_id=?",[$id,$tid]);
jsonResponse(['success'=>true,'message'=>'Course deleted']);
