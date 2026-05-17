<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all tutor commissions
    $commissions = $db->fetchAll(
        "SELECT t.id, t.first_name, t.last_name, t.email, t.subjects, 
                COALESCE(tc.commission_percentage, 70) AS commission_percentage
         FROM tutors t 
         LEFT JOIN tutor_commissions tc ON tc.tutor_id = t.id 
         WHERE t.is_active = 1 AND t.is_approved = 1
         ORDER BY t.created_at DESC"
    );
    jsonResponse(['success' => true, 'data' => $commissions]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutorId = (int)($_POST['tutor_id'] ?? 0);
    $percentage = (float)($_POST['commission_percentage'] ?? 70);
    
    if (!$tutorId || $percentage < 0 || $percentage > 100) {
        jsonResponse(['success' => false, 'message' => 'Invalid tutor or percentage']);
    }
    
    // Check if tutor exists
    $tutor = $db->fetch("SELECT id FROM tutors WHERE id = ?", [$tutorId]);
    if (!$tutor) {
        jsonResponse(['success' => false, 'message' => 'Tutor not found']);
    }
    
    // Insert or update commission
    $existing = $db->fetch("SELECT id FROM tutor_commissions WHERE tutor_id = ?", [$tutorId]);
    if ($existing) {
        $db->execute("UPDATE tutor_commissions SET commission_percentage = ?, updated_at = datetime('now') WHERE tutor_id = ?", [$percentage, $tutorId]);
    } else {
        $db->insert("INSERT INTO tutor_commissions (tutor_id, commission_percentage) VALUES (?, ?)", [$tutorId, $percentage]);
    }
    
    jsonResponse(['success' => true, 'message' => 'Commission updated']);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
