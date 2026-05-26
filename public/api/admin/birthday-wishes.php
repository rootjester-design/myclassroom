<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('super_admin');
$db   = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // ── Manual trigger: send all pending birthday wishes now ──
    try {
        $result = sendBirthdayWishes();
        jsonResponse($result);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

// ── GET: return today's birthdays + recent wish log ───────────
$todayMd = date('m-d');

// Students with birthdays today (all statuses so admin can see suspended ones too)
$allStudents = $db->fetchAll(
    "SELECT id, first_name, last_name, phone, birthday, is_active, is_suspended
     FROM students
     WHERE birthday IS NOT NULL AND birthday != ''
     ORDER BY first_name"
);

$todayBirthdays = [];
foreach ($allStudents as $s) {
    if (date('m-d', strtotime($s['birthday'])) === $todayMd) {
        // Check if already wished today
        $already = $db->fetch(
            "SELECT id FROM notifications
             WHERE user_id = ? AND user_type = 'student' AND title = 'Happy Birthday!'
             AND (date(created_at) = date('now') OR date(created_at, 'localtime') = date('now','localtime'))",
            [$s['id']]
        );
        $todayBirthdays[] = [
            'id'          => $s['id'],
            'name'        => $s['first_name'] . ' ' . $s['last_name'],
            'phone'       => $s['phone'],
            'birthday'    => $s['birthday'],
            'is_active'   => (int)$s['is_active'],
            'is_suspended'=> (int)$s['is_suspended'],
            'wish_sent'   => $already ? true : false,
        ];
    }
}

// Recent birthday wish notifications (last 50)
$recentWishes = $db->fetchAll(
    "SELECT n.id, n.user_id, n.created_at,
            (s.first_name || ' ' || s.last_name) AS student_name,
            s.phone
     FROM notifications n
     LEFT JOIN students s ON s.id = n.user_id
     WHERE n.user_type = 'student' AND n.title = 'Happy Birthday!'
     ORDER BY n.created_at DESC
     LIMIT 50"
);

// Stats
$upcomingBirthdays = [];
for ($i = 1; $i <= 7; $i++) {
    $futureMd = date('m-d', strtotime("+{$i} days"));
    foreach ($allStudents as $s) {
        if (!$s['is_suspended'] && $s['is_active'] && date('m-d', strtotime($s['birthday'])) === $futureMd) {
            $upcomingBirthdays[] = [
                'id'      => $s['id'],
                'name'    => $s['first_name'] . ' ' . $s['last_name'],
                'phone'   => $s['phone'],
                'birthday'=> $s['birthday'],
                'days_away'=> $i,
            ];
        }
    }
}

jsonResponse([
    'success'           => true,
    'today_birthdays'   => $todayBirthdays,
    'today_count'       => count($todayBirthdays),
    'wished_today'      => count(array_filter($todayBirthdays, fn($b) => $b['wish_sent'])),
    'recent_wishes'     => $recentWishes,
    'upcoming_birthdays'=> $upcomingBirthdays,
    'cron_url'          => rtrim(APP_URL, '/') . '/api/cron/send-birthdays.php?secret=' . urlencode(CRON_SECRET),
]);
