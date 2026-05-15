<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance(); $tid = $user['id'];
$data = $db->fetchAll("SELECT r.*,c.title AS course_title FROM recordings r JOIN courses c ON c.id=r.course_id WHERE r.tutor_id=? ORDER BY r.created_at DESC",[$tid]);
jsonResponse(['success'=>true,'data'=>$data]);
