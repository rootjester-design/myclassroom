<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = $user['id'];
$cid = (int)($_GET['id']??0);

// Check enrollment
$enroll = $db->fetch("SELECT * FROM enrollments WHERE student_id=? AND course_id=?",[$sid,$cid]);
if (!$enroll) jsonResponse(['success'=>false,'message'=>'Not enrolled in this course']);

$course = $db->fetch("SELECT c.*, t.first_name||' '||t.last_name AS tutor_name FROM courses c JOIN tutors t ON t.id=c.tutor_id WHERE c.id=?",[$cid]);

$recordings   = $db->fetchAll("SELECT * FROM recordings WHERE course_id=? AND is_active=1 ORDER BY created_at DESC",[$cid]);
$zoom         = $db->fetchAll("SELECT * FROM zoom_links WHERE course_id=? AND is_active=1 ORDER BY scheduled_at DESC",[$cid]);
$materials    = $db->fetchAll("SELECT * FROM course_materials WHERE course_id=? AND is_active=1 ORDER BY created_at DESC",[$cid]);
foreach ($materials as &$material) {
    $material['file_path'] = assetPath($material['file_path']);
}
unset($material);
$announcements= $db->fetchAll("SELECT * FROM announcements WHERE course_id=? AND is_active=1 ORDER BY created_at DESC",[$cid]);
$assignments  = $db->fetchAll("SELECT * FROM assignments WHERE course_id=? AND is_active=1 ORDER BY created_at DESC",[$cid]);
foreach ($assignments as &$assignment) {
    if (!empty($assignment['file_path'])) {
        $assignment['file_path'] = assetPath($assignment['file_path']);
    }
}
unset($assignment);
$notes        = $db->fetchAll("SELECT * FROM student_notes WHERE student_id=? AND course_id=? ORDER BY updated_at DESC",[$sid,$cid]);

jsonResponse(['success'=>true,'course'=>$course,'recordings'=>$recordings,'zoom'=>$zoom,'materials'=>$materials,'announcements'=>$announcements,'assignments'=>$assignments,'notes'=>$notes]);
