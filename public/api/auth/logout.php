<?php
require_once __DIR__ . '/../../../app/includes/helpers.php';
$user = getAuthUser();
$redirectTo = '/auth/login.php';
if ($user && in_array($user['role'], ['tutor', 'super_admin'])) {
    $redirectTo = '/admin/login.php';
}
logoutUser();
redirect($redirectTo);
