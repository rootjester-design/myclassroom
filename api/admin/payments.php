<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();
$status = sanitize($_GET['status']??'all');
$limit  = (int)($_GET['limit']??200);
$id     = (int)($_GET['id']??0);

if ($id) {
    $p = $db->fetch("SELECT p.*,s.first_name||' '||s.last_name AS student_name,c.title AS course_title,t.first_name||' '||t.last_name AS tutor_name FROM payments p JOIN students s ON s.id=p.student_id JOIN courses c ON c.id=p.course_id JOIN tutors t ON t.id=p.tutor_id WHERE p.id=?",[$id]);
    if ($p) {
        $p['payment_slip'] = assetPath($p['payment_slip']);
    }
    jsonResponse(['success'=>true,'data'=>$p]);
}

$where = $status!=='all' ? "WHERE p.status='{$status}'" : '';
$payments = $db->fetchAll("SELECT p.*,s.first_name||' '||s.last_name AS student_name,c.title AS course_title,t.first_name||' '||t.last_name AS tutor_name FROM payments p JOIN students s ON s.id=p.student_id JOIN courses c ON c.id=p.course_id JOIN tutors t ON t.id=p.tutor_id {$where} ORDER BY p.created_at DESC LIMIT ?",[$limit]);
foreach ($payments as &$payment) {
    $payment['payment_slip'] = assetPath($payment['payment_slip']);
}
unset($payment);
jsonResponse(['success'=>true,'data'=>$payments]);
