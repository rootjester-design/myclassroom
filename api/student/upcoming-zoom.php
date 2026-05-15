<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];

$zoom = $db->fetchAll("
    SELECT z.*, c.title AS course_title
    FROM zoom_links z
    JOIN courses c ON c.id=z.course_id
    WHERE z.course_id IN (SELECT course_id FROM enrollments WHERE student_id=?)
    AND z.is_active=1
    AND (z.scheduled_at IS NULL OR z.scheduled_at >= datetime('now'))
    ORDER BY z.scheduled_at ASC LIMIT 5", [$sid]);

jsonResponse(['success'=>true,'data'=>$zoom]);
