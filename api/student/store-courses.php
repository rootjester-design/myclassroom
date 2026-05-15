<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];

$courses = $db->fetchAll("
    SELECT c.*, t.first_name||' '||t.last_name AS tutor_name,
    (SELECT 1 FROM enrollments WHERE student_id=? AND course_id=c.id AND status='active') AS enrolled
    FROM courses c JOIN tutors t ON t.id=c.tutor_id
    WHERE c.is_active=1 AND t.is_active=1 AND t.is_approved=1
    ORDER BY c.created_at DESC", [$sid]);

jsonResponse(['success'=>true,'data'=>$courses]);
