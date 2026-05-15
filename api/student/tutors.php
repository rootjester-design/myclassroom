<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];

$tutors = $db->fetchAll("SELECT id,first_name,last_name,display_name,subjects,profile_image,banner_image,rating,total_students FROM tutors WHERE is_active=1 AND is_approved=1 AND is_suspended=0 ORDER BY rating DESC");
jsonResponse(['success'=>true,'data'=>$tutors]);
