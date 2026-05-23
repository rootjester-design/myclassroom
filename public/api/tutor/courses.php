<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance();
$tid = $user['id'];
 $courses = $db->fetchAll("SELECT * FROM courses WHERE tutor_id=? ORDER BY created_at DESC", [$tid]);
 foreach ($courses as &$c) {
	 if (!empty($c['thumbnail'])) $c['thumbnail'] = assetPath($c['thumbnail']);
 }
 jsonResponse(['success'=>true,'data'=>$courses]);
