<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = requireAuth('super_admin');
$db = Database::getInstance();

// Get total revenue from all approved payments
$totalRevenue = $db->fetch(
    "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE status = 'approved'"
)['total'] ?? 0;

// Get admin income (30% of all payments by default, or as configured per tutor)
$adminIncome = 0;
$allPayments = $db->fetchAll(
    "SELECT p.amount, p.tutor_id FROM payments p WHERE p.status = 'approved'"
);

foreach ($allPayments as $payment) {
    $tutor_id = $payment['tutor_id'];
    $commission = $db->fetch(
        "SELECT commission_percentage FROM tutor_commissions WHERE tutor_id = ?",
        [$tutor_id]
    );
    $commissionPercentage = ($commission && $commission['commission_percentage']) 
        ? (100 - $commission['commission_percentage']) // Admin gets the inverse (e.g., tutor gets 70%, admin gets 30%)
        : 30; // Default: tutor gets 70%, admin gets 30%
    $adminIncome += ($payment['amount'] * $commissionPercentage / 100);
}

// Get tutor breakdown
$tutorBreakdown = $db->fetchAll(
    "SELECT t.id, t.first_name, t.last_name, 
            COALESCE(tc.commission_percentage, 70) AS commission_percentage,
            COALESCE(SUM(p.amount), 0) AS total_earned
     FROM tutors t
     LEFT JOIN tutor_commissions tc ON tc.tutor_id = t.id
     LEFT JOIN payments p ON p.tutor_id = t.id AND p.status = 'approved'
     WHERE t.is_active = 1
     GROUP BY t.id
     ORDER BY total_earned DESC"
);

// Calculate tutor income
foreach ($tutorBreakdown as &$tutor) {
    $tutor['tutor_income'] = $tutor['total_earned'] * $tutor['commission_percentage'] / 100;
    $tutor['admin_income_from_tutor'] = $tutor['total_earned'] * (100 - $tutor['commission_percentage']) / 100;
}

jsonResponse([
    'success' => true,
    'total_revenue' => (float)$totalRevenue,
    'admin_income' => (float)$adminIncome,
    'tutor_breakdown' => $tutorBreakdown
]);
