<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance(); $tid = $user['id'];
$data = $db->fetchAll("SELECT m.*,c.title AS course_title FROM course_materials m JOIN courses c ON c.id=m.course_id WHERE m.tutor_id=? ORDER BY m.created_at DESC",[$tid]);
jsonResponse(['success'=>true,'data'=>$data]);
