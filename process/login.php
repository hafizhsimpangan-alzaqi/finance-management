<?php

require_once __DIR__ . '/helpers.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ../view/page/dashboard.php');
    exit;
}

// Menentukan halaman tujuan jika terjadi kesalahan
$page = '../view/page/login.php';

// Memeriksa metode request dan CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        $page
    );
}

if (is_string($_POST['identity'] ?? null)) {
    $identity = trim($_POST['identity']);
} else {
    $identity = '';
}

if (is_string($_POST['password'] ?? null)) {
    $password = $_POST['password'];
} else {
    $password = '';
}

if ($identity === '' || $password === '') {
    flash_redirect('Semua field wajib diisi.', $page);
}

// Koneksi database
$errorPage = $page;
require_once __DIR__ . '/../database/connection.php';

/** @var mysqli $connection */

$query = mysqli_prepare(
    $connection,
    'SELECT id, username, password_hash
     FROM users
     WHERE username = ? OR email = ?'
);

if (
    !$query ||
    !mysqli_stmt_bind_param($query, 'ss', $identity, $identity) ||
    !mysqli_stmt_execute($query)
) {
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

$result = mysqli_stmt_get_result($query);

if (!$result) {
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

$matched = null;
$count = 0;

while ($user = mysqli_fetch_assoc($result)) {

    if (verify_password($password, $user['password_hash'])) {
        $matched = $user;
        $count++;
    }
}

mysqli_stmt_close($query);
mysqli_close($connection);

if ($count !== 1) {
    flash_redirect('Username/email atau password salah.', $page);
}

session_regenerate_id(true);

// Menyimpan data pengguna ke session
$_SESSION['user_id'] = $matched['id'];
$_SESSION['username'] = $matched['username'];

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

// Menentukan path cookie
$path = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/';

if ($path === '//') {
    $path = '/';
}

// Menentukan masa berlaku cookie Remember Me
if (isset($_POST['remember_me'])) {
    $expires = time() + 604800;
} else {
    $expires = time() - 3600;
}

// Mengatur konfigurasi cookie
$options = [
    'expires' => $expires,
    'path' => $path,
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
];

// Menyimpan username jika Remember Me dicentang
if (isset($_POST['remember_me'])) {

    setcookie(
        'remember_username',
        $matched['username'],
        $options
    );

// Menghapus cookie lama jika Remember Me tidak dicentang
} elseif (isset($_COOKIE['remember_username'])) {

    setcookie(
        'remember_username',
        '',
        $options
    );
}

header('Location: ../view/page/dashboard.php');
exit;