<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();
$limit = (int)($_GET['limit']??200);
$tutors = $db->fetchAll("SELECT * FROM tutors ORDER BY created_at DESC LIMIT ?",[$limit]);
foreach ($tutors as &$t) {
	if (!empty($t['profile_image'])) $t['profile_image'] = assetPath($t['profile_image']);
	if (!empty($t['banner_image'])) $t['banner_image'] = assetPath($t['banner_image']);
}
unset($t);
jsonResponse(['success'=>true,'data'=>$tutors]);
