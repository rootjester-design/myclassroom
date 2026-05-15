<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance();
$tid = $user['id'];
$limit = (int)($_GET['limit'] ?? 100);
$students = $db->fetchAll("
    SELECT s.id,s.first_name,s.last_name,s.phone,s.student_id,e.enrolled_at,e.progress,c.title AS course_title
    FROM enrollments e
    JOIN students s ON s.id=e.student_id
    JOIN courses c ON c.id=e.course_id
    WHERE e.tutor_id=?
    ORDER BY e.enrolled_at DESC LIMIT ?", [$tid,$limit]);
jsonResponse(['success'=>true,'data'=>$students]);
