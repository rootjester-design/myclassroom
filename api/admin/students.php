<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();

$students = $db->fetchAll("
    SELECT s.*,
           (SELECT COUNT(*) FROM enrollments WHERE student_id = s.id) AS course_count
    FROM students s
    ORDER BY s.created_at DESC
");

jsonResponse(['success' => true, 'data' => $students]);
