<?php
require_once '../includes/helpers.php';
$db = Database::getInstance();
$tid = (int)($_GET['id']??0);

$tutor = $db->fetch("SELECT id,first_name,last_name,display_name,description,experience,qualifications,subjects,profile_image,banner_image,rating,total_students,social_links FROM tutors WHERE id=? AND is_active=1 AND is_approved=1",[$tid]);
if (!$tutor) jsonResponse(['success'=>false,'message'=>'Tutor not found']);
$tutor['social_links'] = htmlspecialchars_decode($tutor['social_links'] ?? '');
if (!empty($tutor['profile_image'])) $tutor['profile_image'] = assetPath($tutor['profile_image']);
if (!empty($tutor['banner_image'])) $tutor['banner_image'] = assetPath($tutor['banner_image']);

$courses = $db->fetchAll(
    "SELECT * FROM courses WHERE tutor_id=? AND is_active=1", [$tid]);
foreach ($courses as &$c) { if (!empty($c['thumbnail'])) $c['thumbnail'] = assetPath($c['thumbnail']); }
unset($c);

jsonResponse(['success'=>true,'tutor'=>$tutor,'courses'=>$courses]);
