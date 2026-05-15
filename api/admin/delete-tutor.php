<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$id = (int)($_POST['id']??0);
if (!$id) jsonResponse(['success'=>false,'message'=>'ID required']);
$db->execute("DELETE FROM tutors WHERE id=?",[$id]);
logActivity((int)$user['id'],'admin','delete_tutor',"Deleted tutor ID:{$id}");
jsonResponse(['success'=>true,'message'=>'Tutor deleted']);
