<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$sid = $user['id'];

$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$courseId = (int)($data['course_id']??0);

if (!$courseId) jsonResponse(['success'=>false,'message'=>'Course ID required']);

// Check if already enrolled
$enrolled = $db->fetch("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND status='active'",[$sid,$courseId]);
if ($enrolled) jsonResponse(['success'=>false,'message'=>'You are already enrolled in this course']);

$course = $db->fetch("SELECT * FROM courses WHERE id=? AND is_active=1",[$courseId]);
if (!$course) jsonResponse(['success'=>false,'message'=>'Course not found']);

if ((float)$course['price'] > 0) {
    jsonResponse(['success'=>false,'message'=>'This course requires payment']);
}

// Direct enroll
$db->insert("INSERT INTO enrollments (student_id,course_id,tutor_id,status) VALUES (?,?,?,'active')", [$sid,$courseId,$course['tutor_id']]);

createNotification($sid,'student','Course Enrolled','You successfully enrolled in "'.$course['title'].'".','success',$courseId,'course');
createNotification((int)$course['tutor_id'],'tutor','New Enrollment','A student enrolled in your free course "'.$course['title'].'".','info',$courseId,'course');

jsonResponse(['success'=>true,'message'=>'Successfully enrolled in free course!']);
