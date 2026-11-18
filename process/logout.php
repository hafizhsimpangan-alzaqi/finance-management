<?php

require_once __DIR__ . '/helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../view/page/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        '../view/page/dashboard.php'
    );
}

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

// Menentukan path cookie Remember Me
$path = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/';

if ($path === '//') {
    $path = '/';
}

// Menghapus cookie Remember Me
setcookie('remember_username', '', [
    'expires' => time() - 3600,
    'path' => $path,
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Mengambil konfigurasi cookie session
$params = session_get_cookie_params();

// Menghapus cookie session
setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => $params['samesite'] ?: 'Lax'
]);


session_unset();
session_destroy();
header('Location: ../view/page/login.php');
exit;