<?php
/**
 * Birthday Wish Cron Job — MyClassroom
 * ─────────────────────────────────────────────────────────────
 * Trigger via server cron (e.g., daily at 8 AM):
 *   curl -s "https://yourdomain.com/api/cron/send-birthdays.php?secret=YOUR_SECRET" >> /var/log/birthday_cron.log
 *
 * Or schedule with: 0 8 * * * php /path/to/public/api/cron/send-birthdays.php secret=YOUR_SECRET
 */
require_once __DIR__ . '/../../../app/includes/helpers.php';

// ── CORS off: this endpoint should only be called server-side ──
header('Content-Type: application/json');

// ── Secret validation ──────────────────────────────────────────
$providedSecret = $_GET['secret'] ?? $_SERVER['argv'][1] ?? '';

if (!hash_equals(CRON_SECRET, (string)$providedSecret)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Invalid or missing secret token.'
    ]);
    exit;
}

// ── Run the birthday wishing function ─────────────────────────
$startTime = microtime(true);

try {
    $result = sendBirthdayWishes();
    $elapsed = round(microtime(true) - $startTime, 3);

    // Log a summary to the activity log
    $wishedCount = $result['wished_count'] ?? 0;
    $smsSentCount = $result['sms_sent_count'] ?? 0;

    // Log in activity_logs
    try {
        $db = Database::getInstance();
        $db->insert(
            "INSERT INTO activity_logs (user_id, user_type, action, description) VALUES (?, ?, ?, ?)",
            [0, 'system', 'birthday_wishes_sent', "Cron job ran: {$wishedCount} students wished, {$smsSentCount} SMS sent. Time: {$elapsed}s"]
        );
    } catch (Exception $e) {
        // non-critical
    }

    echo json_encode(array_merge($result, [
        'elapsed_seconds' => $elapsed,
        'run_at'          => date('Y-m-d H:i:s')
    ]));
} catch (Exception $e) {
    http_response_code(500);
    error_log('[BirthdayCron] Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Internal error: ' . $e->getMessage()
    ]);
}
