<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
$db = Database::getInstance();
$sid = (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? 'mark_all_read';

    if ($action === 'mark_read' && isset($body['id'])) {
        $db->execute("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=? AND user_type='student'", [(int)$body['id'], $sid]);
    } else {
        // mark_all_read
        $db->execute("UPDATE notifications SET is_read=1 WHERE user_id=? AND user_type='student'", [$sid]);
    }
    jsonResponse(['success' => true]);
}

$notifs = $db->fetchAll(
    "SELECT * FROM notifications WHERE user_id=? AND user_type='student' ORDER BY created_at DESC LIMIT 50",
    [$sid]
);
jsonResponse(['success' => true, 'data' => $notifs]);
