<?php
require_once '../../includes/helpers.php';
$user = requireAuth('tutor');
$db = Database::getInstance();
$tutorId = $user['id'];

// Get tutor commission percentage
$commission = $db->fetch(
    "SELECT commission_percentage FROM tutor_commissions WHERE tutor_id = ?",
    [$tutorId]
);
$commissionPercentage = ($commission && $commission['commission_percentage']) 
    ? $commission['commission_percentage']
    : 70; // Default: tutor gets 70%

// Get total revenue from this tutor's courses
$totalRevenue = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE tutor_id = ? AND status = 'approved'",
    [$tutorId]
)['total'] ?? 0;

// Calculate tutor profit
$tutorProfit = $totalRevenue * $commissionPercentage / 100;

// Get breakdown by month
$monthlyBreakdown = $db->fetchAll(
    "SELECT DATE(p.created_at) AS month, 
            COALESCE(SUM(p.amount), 0) AS amount
     FROM payments p
     WHERE p.tutor_id = ? AND p.status = 'approved'
     GROUP BY DATE(p.created_at)
     ORDER BY DATE(p.created_at) DESC
     LIMIT 12"
    ,
    [$tutorId]
);

// Calculate monthly profit
foreach ($monthlyBreakdown as &$month) {
    $month['profit'] = $month['amount'] * $commissionPercentage / 100;
}

jsonResponse([
    'success' => true,
    'total_revenue' => (float)$totalRevenue,
    'tutor_profit' => (float)$tutorProfit,
    'commission_percentage' => (float)$commissionPercentage,
    'admin_percentage' => 100 - (float)$commissionPercentage,
    'monthly_breakdown' => $monthlyBreakdown
]);
