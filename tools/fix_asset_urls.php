<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance();
$hostPatterns = [
    'http://myclassroom.lk',
    'https://myclassroom.lk',
    'http://www.myclassroom.lk',
    'https://www.myclassroom.lk'
];
$tables = [
    ['table'=>'courses','col'=>'thumbnail'],
    ['table'=>'tutors','col'=>'profile_image'],
    ['table'=>'tutors','col'=>'banner_image'],
    ['table'=>'students','col'=>'profile_image'],
    ['table'=>'recordings','col'=>'thumbnail'],
    ['table'=>'course_materials','col'=>'file_path'],
    ['table'=>'payments','col'=>'payment_slip']
];
foreach ($tables as $t) {
    $table = $t['table']; $col = $t['col'];
    try {
        $rows = $db->fetchAll("SELECT id, $col FROM $table WHERE $col LIKE '%myclassroom.lk%'");
        foreach ($rows as $r) {
            $val = $r[$col];
            $new = $val;
            foreach ($hostPatterns as $p) $new = str_replace($p, '', $new);
            // ensure leading slash
            if ($new && $new[0] !== '/') $new = '/' . ltrim($new, '/');
            $db->execute("UPDATE $table SET $col = ? WHERE id = ?", [$new, $r['id']]);
            echo "Updated $table id={$r['id']}\n";
        }
    } catch (Exception $e) {
        echo "Skipping $table:$col - error: " . $e->getMessage() . "\n";
    }
}
echo "Done\n"; 
