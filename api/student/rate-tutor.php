<?php
require_once '../../includes/helpers.php';
$user = requireAuth('student');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);

$db = Database::getInstance();
$sid = $user['id'];
$tid = (int)($_POST['tutor_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$review = sanitize($_POST['review'] ?? '');
$courseId = (int)($_POST['course_id'] ?? 0);

if (!$tid || $rating < 1 || $rating > 5) {
    jsonResponse(['success' => false, 'message' => 'Please submit a valid rating.']);
}

$tutor = $db->fetch("SELECT id FROM tutors WHERE id=? AND is_active=1 AND is_approved=1", [$tid]);
if (!$tutor) {
    jsonResponse(['success' => false, 'message' => 'Tutor not found.']);
}

$enrolled = $db->fetch(
    "SELECT e.id FROM enrollments e JOIN courses c ON c.id=e.course_id WHERE e.student_id=? AND c.tutor_id=? AND e.status='active' LIMIT 1",
    [$sid, $tid]
);
if (!$enrolled) {
    jsonResponse(['success' => false, 'message' => 'You must have an active enrollment with this tutor to leave a review.']);
}

$courseId = $courseId ?: 0;
$existing = $db->fetch("SELECT id FROM ratings WHERE student_id=? AND tutor_id=? AND course_id=?", [$sid, $tid, $courseId]);
if ($existing) {
    $db->execute("UPDATE ratings SET rating=?, review=?, created_at=datetime('now') WHERE id=?", [$rating, $review, $existing['id']]);
} else {
    $db->insert("INSERT INTO ratings (student_id, tutor_id, course_id, rating, review) VALUES (?,?,?,?,?)", [$sid, $tid, $courseId, $rating, $review]);
}

$stats = $db->fetch("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_students FROM ratings WHERE tutor_id=?", [$tid]);
$avg = round((float)($stats['avg_rating'] ?? 0), 2);
$count = (int)($stats['total_students'] ?? 0);
$db->execute("UPDATE tutors SET rating=?, total_students=?, updated_at=datetime('now') WHERE id=?", [$avg, $count, $tid]);

jsonResponse(['success' => true, 'message' => 'Review submitted successfully.']);
