<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance(); $tid = $user['id'];
$data = $db->fetchAll("SELECT a.*,c.title AS course_title FROM announcements a JOIN courses c ON c.id=a.course_id WHERE a.tutor_id=? ORDER BY a.created_at DESC",[$tid]);
jsonResponse(['success'=>true,'data'=>$data]);
