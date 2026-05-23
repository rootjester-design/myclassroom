<?php
require_once __DIR__ . '/../../app/includes/helpers.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$db = Database::getInstance();
$baseUrl = APP_URL;

// Static Pages
$staticPages = [
    '/',
    '/auth/login.php',
    '/auth/register.php'
];

foreach ($staticPages as $page) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . $page) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>" . ($page === '/' ? '1.0' : '0.8') . "</priority>\n";
    echo "  </url>\n";
}

// Dynamic Tutor Profiles
$tutors = $db->fetchAll("SELECT id FROM tutors WHERE is_approved=1");
foreach ($tutors as $tutor) {
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($baseUrl . '/tutor-profile.php?id=' . $tutor['id']) . "</loc>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
