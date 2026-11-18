<?php
require_once __DIR__ . '/process/helpers.php';
header('Location: view/page/' . (!empty($_SESSION['user_id']) ? 'dashboard.php' : 'login.php'));
exit;
