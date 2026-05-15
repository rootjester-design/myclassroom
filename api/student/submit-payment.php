<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$sid = $user['id'];

$courseId = (int)($_POST['course_id']??0);
$ref      = sanitize($_POST['payment_reference']??'');
$notes    = sanitize($_POST['notes']??'');

if (!$courseId||!$ref) jsonResponse(['success'=>false,'message'=>'Course and payment reference required']);
if (empty($_FILES['payment_slip']['name'])) jsonResponse(['success'=>false,'message'=>'Payment slip is required']);

// Check already enrolled
$enrolled = $db->fetch("SELECT id FROM enrollments WHERE student_id=? AND course_id=? AND status='active'",[$sid,$courseId]);
if ($enrolled) jsonResponse(['success'=>false,'message'=>'You are already enrolled in this course']);

// Check pending payment
$pending = $db->fetch("SELECT id FROM payments WHERE student_id=? AND course_id=? AND status='pending'",[$sid,$courseId]);
if ($pending) jsonResponse(['success'=>false,'message'=>'You already have a pending payment for this course']);

$course = $db->fetch("SELECT * FROM courses WHERE id=? AND is_active=1",[$courseId]);
if (!$course) jsonResponse(['success'=>false,'message'=>'Course not found']);

$upload = uploadFile($_FILES['payment_slip'],'payments',ALLOWED_IMAGE_TYPES);
if (!$upload['success']) jsonResponse(['success'=>false,'message'=>$upload['message']]);

$db->insert("INSERT INTO payments (student_id,course_id,tutor_id,amount,payment_reference,payment_slip,notes,status) VALUES (?,?,?,?,?,?,?,'pending')",
    [$sid,$courseId,$course['tutor_id'],$course['price'],$ref,$upload['path'],$notes]);

createNotification($sid,'student','Payment Submitted','Your payment for "'.$course['title'].'" is under review.','info',$courseId,'course');
createNotification((int)$course['tutor_id'],'tutor','New Payment Received','A student submitted payment for "'.$course['title'].'".','info',$courseId,'course');

jsonResponse(['success'=>true,'message'=>'Payment submitted successfully! Awaiting approval.']);
