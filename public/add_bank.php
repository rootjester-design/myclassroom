<?php
require_once __DIR__ . '/../app/includes/helpers.php';
$db = Database::getInstance();
try {
    $db->execute("ALTER TABLE tutors ADD COLUMN bank_details TEXT NULL");
    echo "Column added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
