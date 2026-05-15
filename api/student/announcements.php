<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];

$ann = $db->fetchAll("
    SELECT a.*, c.title AS course_title
    FROM announcements a
    LEFT JOIN courses c ON c.id=a.course_id
    WHERE a.course_id IN (SELECT course_id FROM enrollments WHERE student_id=?) AND a.is_active=1
    ORDER BY a.created_at DESC LIMIT 10", [$sid]);

jsonResponse(['success'=>true,'data'=>$ann]);
