<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
if ($_SERVER['REQUEST_METHOD']!=='POST') jsonResponse(['success'=>false,'message'=>'Method not allowed'],405);
$db = Database::getInstance();
$adminId = $user['id'];

$id     = (int)($_POST['id']??0);
$action = sanitize($_POST['action']??'');
$reason = sanitize($_POST['reason']??'');

if (!$id||!in_array($action,['approve','reject'])) jsonResponse(['success'=>false,'message'=>'Invalid request']);

$payment = $db->fetch("SELECT * FROM payments WHERE id=?",[$id]);
if (!$payment) jsonResponse(['success'=>false,'message'=>'Payment not found']);
if ($payment['status']!=='pending') jsonResponse(['success'=>false,'message'=>'Payment already reviewed']);

if ($action==='approve') {
    $db->execute("UPDATE payments SET status='approved',reviewed_by=?,reviewed_at=datetime('now'),updated_at=datetime('now') WHERE id=?",[$adminId,$id]);
    $existing = $db->fetch("SELECT id FROM enrollments WHERE student_id=? AND course_id=?",[$payment['student_id'],$payment['course_id']]);
    if (!$existing) {
        $db->insert("INSERT INTO enrollments (student_id,course_id,tutor_id,status) VALUES (?,?,?,'active')",[$payment['student_id'],$payment['course_id'],$payment['tutor_id']]);
        $db->execute("UPDATE courses SET total_students=total_students+1 WHERE id=?",[$payment['course_id']]);
        $db->execute("UPDATE tutors SET total_students=total_students+1 WHERE id=?",[$payment['tutor_id']]);
    } else {
        $db->execute("UPDATE enrollments SET status='active' WHERE student_id=? AND course_id=?",[$payment['student_id'],$payment['course_id']]);
    }
    $course = $db->fetch("SELECT title FROM courses WHERE id=?",[$payment['course_id']]);
    createNotification((int)$payment['student_id'],'student','Payment Approved! 🎉','Your enrollment in "'.$course['title'].'" is now active.','success');
} else {
    $db->execute("UPDATE payments SET status='rejected',reviewed_by=?,reviewed_at=datetime('now'),reject_reason=?,updated_at=datetime('now') WHERE id=?",[$adminId,$reason,$id]);
    $course = $db->fetch("SELECT title FROM courses WHERE id=?",[$payment['course_id']]);
    createNotification((int)$payment['student_id'],'student','Payment Rejected','Your payment for "'.$course['title'].'" was rejected.','error');
}
jsonResponse(['success'=>true,'message'=>'Payment '.($action==='approve'?'approved':'rejected')]);
