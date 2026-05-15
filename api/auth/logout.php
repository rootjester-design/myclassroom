<?php
require_once '../../includes/helpers.php';
logoutUser();
redirect('/auth/login.php');
