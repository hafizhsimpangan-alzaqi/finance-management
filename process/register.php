<?php

require_once __DIR__ . '/helpers.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: ../view/page/dashboard.php');
    exit;
}

$page = '../view/page/register.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        $page
    );
}

if (is_string($_POST['username'] ?? null)) {
    $username = trim($_POST['username']);
} else {
    $username = '';
}

if (is_string($_POST['email'] ?? null)) {
    $email = trim($_POST['email']);
} else {
    $email = '';
}

if (is_string($_POST['password'] ?? null)) {
    $password = $_POST['password'];
} else {
    $password = '';
}

if ($username === '' || $email === '' || $password === '') {
    flash_redirect('Semua field wajib diisi.', $page);
}

// Memeriksa panjang username
if (mb_strlen($username, 'UTF-8') > 50) {
    flash_redirect('Username maksimal 50 karakter.', $page);
}

// Memeriksa format dan panjang email
if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash_redirect('Format email tidak valid.', $page);
}

// Memeriksa panjang password
if (mb_strlen($password, 'UTF-8') < 8) {
    flash_redirect('Password minimal 8 karakter.', $page);
}

// Koneksi database
$errorPage = $page;
require_once __DIR__ . '/../database/connection.php';

/** @var mysqli $connection */

// Memeriksa apakah username atau email sudah digunakan
$query = mysqli_prepare(
    $connection,
    'SELECT username, email
     FROM users
     WHERE username = ? OR email = ?'
);

if (
    !$query ||
    !mysqli_stmt_bind_param($query, 'ss', $username, $email) ||
    !mysqli_stmt_execute($query)
) {
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

$result = mysqli_stmt_get_result($query);

if (!$result) {
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

if (mysqli_fetch_assoc($result)) {
    flash_redirect('Username atau email sudah digunakan.', $page);
}

mysqli_stmt_close($query);

// Membuat tanggal registrasi
date_default_timezone_set('Asia/Jakarta');
$registeredAt = date('Y-m-d H:i:s');

// Membuat hash password
$hash = hash_password($password);

$query = mysqli_prepare(
    $connection,
    'INSERT INTO users
     (username, email, password_hash, opening_balance, registered_at)
     VALUES (?, ?, ?, NULL, ?)'
);

// Memeriksa hash dan parameter query
if (
    !$hash ||
    !$query ||
    !mysqli_stmt_bind_param(
        $query,
        'ssss',
        $username,
        $email,
        $hash,
        $registeredAt
    )
) {
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

// Menyimpan pengguna baru ke database
if (!mysqli_stmt_execute($query)) {

    // Memeriksa apakah terjadi duplikasi username atau email
    if (mysqli_stmt_errno($query) === 1062) {
        $message = 'Username atau email sudah digunakan.';
    } else {
        $message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    }

    flash_redirect($message, $page);
}

mysqli_stmt_close($query);
mysqli_close($connection);

flash_redirect(
    'Pendaftaran berhasil. Silakan masuk.',
    '../view/page/login.php',
    'success'
);