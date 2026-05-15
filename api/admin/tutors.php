<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();
$limit = (int)($_GET['limit']??200);
$tutors = $db->fetchAll("SELECT * FROM tutors ORDER BY created_at DESC LIMIT ?",[$limit]);
jsonResponse(['success'=>true,'data'=>$tutors]);
