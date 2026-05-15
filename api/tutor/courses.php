<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance();
$tid = $user['id'];
$courses = $db->fetchAll("SELECT * FROM courses WHERE tutor_id=? ORDER BY created_at DESC", [$tid]);
jsonResponse(['success'=>true,'data'=>$courses]);
