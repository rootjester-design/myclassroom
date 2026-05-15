<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance();
$tid = $user['id'];
$status = sanitize($_GET['status'] ?? 'all');
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $p = $db->fetch("SELECT p.*,s.first_name||' '||s.last_name AS student_name,c.title AS course_title FROM payments p JOIN students s ON s.id=p.student_id JOIN courses c ON c.id=p.course_id WHERE p.id=? AND p.tutor_id=?",[$id,$tid]);
    if ($p) {
        $p['payment_slip'] = assetPath($p['payment_slip']);
    }
    jsonResponse(['success'=>true,'data'=>$p]);
}

$where = $status !== 'all' ? "AND p.status='{$status}'" : '';
$payments = $db->fetchAll("SELECT p.*,s.first_name||' '||s.last_name AS student_name,c.title AS course_title FROM payments p JOIN students s ON s.id=p.student_id JOIN courses c ON c.id=p.course_id WHERE p.tutor_id=? {$where} ORDER BY p.created_at DESC",[$tid]);
foreach ($payments as &$payment) {
    $payment['payment_slip'] = assetPath($payment['payment_slip']);
}
unset($payment);
jsonResponse(['success'=>true,'data'=>$payments]);
