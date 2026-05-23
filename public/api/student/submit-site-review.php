<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('student', true);
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$review = isset($_POST['review']) ? trim($_POST['review']) : '';

if ($rating < 1 || $rating > 5 || empty($review)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid rating and review text.']);
    exit;
}

try {
    $db->query(
        "INSERT INTO site_reviews (student_id, rating, review) VALUES (?, ?, ?)",
        [$user['id'], $rating, $review]
    );
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review.']);
}
