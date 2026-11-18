<?php
require_once __DIR__ . '/helpers.php';
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
if (empty($_SESSION['user_id'])) {
    header('Location: ' . ($guardLogin ?? 'login.php'));
    exit;
}

if (!empty($requireBalance)) {
    $errorPage = false;
    require_once __DIR__ . '/../database/connection.php';

    /** @var mysqli $connection */
    
    $query = mysqli_prepare($connection, 'SELECT opening_balance FROM users WHERE id = ?');
    if (!$query || !mysqli_stmt_bind_param($query, 's', $_SESSION['user_id']) || !mysqli_stmt_execute($query) || !($result = mysqli_stmt_get_result($query)) || !($user = mysqli_fetch_assoc($result))) {
        http_response_code(503);
        exit('Terjadi kesalahan sistem. Silakan coba lagi.');
    }
    mysqli_stmt_close($query);
    if ($user['opening_balance'] === null) {
        flash_redirect('Tetapkan saldo awal sebelum membuka halaman ini.', $guardDashboard ?? 'dashboard.php');
    }
}
