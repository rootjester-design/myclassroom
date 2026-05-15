<?php
require_once '../../includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();
$logs = $db->fetchAll("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 100");
jsonResponse(['success'=>true,'data'=>$logs]);
