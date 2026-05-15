<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();
$courses = $db->fetchAll("SELECT c.*,t.first_name||' '||t.last_name AS tutor_name FROM courses c JOIN tutors t ON t.id=c.tutor_id ORDER BY c.created_at DESC");
jsonResponse(['success'=>true,'data'=>$courses]);
