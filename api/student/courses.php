<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];

$courses = $db->fetchAll("
    SELECT c.*, t.first_name||' '||t.last_name AS tutor_name, e.progress, e.status
    FROM enrollments e
    JOIN courses c ON c.id=e.course_id
    JOIN tutors t ON t.id=c.tutor_id
    WHERE e.student_id=?
    ORDER BY e.enrolled_at DESC", [$sid]);

jsonResponse(['success'=>true,'data'=>$courses]);
