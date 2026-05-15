<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance(); $tid = $user['id'];
$data = $db->fetchAll("SELECT z.*,c.title AS course_title FROM zoom_links z JOIN courses c ON c.id=z.course_id WHERE z.tutor_id=? ORDER BY z.scheduled_at DESC",[$tid]);
jsonResponse(['success'=>true,'data'=>$data]);
