<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];
$tid = (int)($_GET['id']??0);

$tutor = $db->fetch("SELECT id,first_name,last_name,display_name,description,experience,qualifications,subjects,profile_image,banner_image,rating,total_students,social_links,contact_info FROM tutors WHERE id=? AND is_active=1 AND is_approved=1",[$tid]);
if (!$tutor) jsonResponse(['success'=>false,'message'=>'Tutor not found']);

$courses = $db->fetchAll(
    "SELECT c.*, (SELECT 1 FROM enrollments WHERE student_id=? AND course_id=c.id AND status='active') AS enrolled
    FROM courses c WHERE c.tutor_id=? AND c.is_active=1", [$sid, $tid]);

$userRating = $db->fetch("SELECT rating, review FROM ratings WHERE student_id=? AND tutor_id=? AND course_id=0", [$sid, $tid]);

jsonResponse(['success'=>true,'tutor'=>$tutor,'courses'=>$courses,'user_rating'=>$userRating]);
