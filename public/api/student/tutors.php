<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];

$tutors = $db->fetchAll("SELECT id,first_name,last_name,display_name,subjects,profile_image,banner_image,rating,total_students FROM tutors WHERE is_active=1 AND is_approved=1 AND is_suspended=0 ORDER BY rating DESC");
foreach ($tutors as &$t) {
	if (!empty($t['profile_image'])) $t['profile_image'] = assetPath($t['profile_image']);
	if (!empty($t['banner_image'])) $t['banner_image'] = assetPath($t['banner_image']);
}
unset($t);
jsonResponse(['success'=>true,'data'=>$tutors]);
