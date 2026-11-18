<?php

require_once __DIR__ . '/helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../view/page/login.php');
    exit;
}

$page = '../view/page/settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        $page
    );
}

// Mengambil jenis aksi
$action = $_POST['action'] ?? '';

// Memvalidasi jenis aksi dan mencegah perubahan email
if (
    !in_array($action, ['update_username', 'update_password'], true) ||
    array_key_exists('email', $_POST)
) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        $page
    );
}

// Validasi input berdasarkan jenis aksi
if ($action === 'update_username') {

    // Mengambil username baru
    if (is_string($_POST['username'] ?? null)) {
        $username = trim($_POST['username']);
    } else {
        $username = '';
    }

    // Memeriksa username kosong
    if ($username === '') {
        flash_redirect('Username wajib diisi.', $page);
    }

    // Memeriksa panjang username
    if (mb_strlen($username, 'UTF-8') > 50) {
        flash_redirect('Username maksimal 50 karakter.', $page);
    }

} else {

    // Mengambil password lama
    if (is_string($_POST['old_password'] ?? null)) {
        $old_password = $_POST['old_password'];
    } else {
        $old_password = '';
    }

    // Mengambil password baru
    if (is_string($_POST['new_password'] ?? null)) {
        $new_password = $_POST['new_password'];
    } else {
        $new_password = '';
    }

    // Memeriksa password lama
    if ($old_password === '') {
        flash_redirect('Password lama wajib diisi.', $page);
    }

    // Memeriksa password baru
    if ($new_password === '') {
        flash_redirect('Password baru wajib diisi.', $page);
    }

    // Memeriksa panjang password baru
    if (mb_strlen($new_password, 'UTF-8') < 8) {
        flash_redirect('Password baru minimal 8 karakter.', $page);
    }
}

// Koneksi database
$errorPage = $page;
require_once __DIR__ . '/../database/connection.php';

/** @var mysqli $connection */

// Mengambil hash password pengguna
$query = mysqli_prepare(
    $connection,
    'SELECT password_hash
     FROM users
     WHERE id = ?'
);

if (
    !$query ||
    !mysqli_stmt_bind_param($query, 's', $_SESSION['user_id']) ||
    !mysqli_stmt_execute($query) ||
    !($result = mysqli_stmt_get_result($query)) ||
    !($user = mysqli_fetch_assoc($result))
) {
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

mysqli_stmt_close($query);

// Menjalankan aksi berdasarkan pilihan pengguna
if ($action === 'update_username') {

    // Memeriksa apakah username sudah digunakan pengguna lain
    $query = mysqli_prepare(
        $connection,
        'SELECT id
         FROM users
         WHERE username = ? AND id <> ?'
    );

    if (
        !$query ||
        !mysqli_stmt_bind_param(
            $query,
            'ss',
            $username,
            $_SESSION['user_id']
        ) ||
        !mysqli_stmt_execute($query) ||
        !($result = mysqli_stmt_get_result($query))
    ) {
        flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
    }

    // Menolak username yang sudah digunakan
    if (mysqli_fetch_assoc($result)) {
        flash_redirect('Username sudah digunakan.', $page);
    }

    mysqli_stmt_close($query);

    // Memperbarui username pengguna
    $query = mysqli_prepare(
        $connection,
        'UPDATE users
         SET username = ?
         WHERE id = ?'
    );

    if (
        !$query ||
        !mysqli_stmt_bind_param(
            $query,
            'ss',
            $username,
            $_SESSION['user_id']
        )
    ) {
        flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
    }

    // Menjalankan perubahan username
    if (!mysqli_stmt_execute($query)) {

        // Memeriksa apakah username sudah digunakan
        if (mysqli_stmt_errno($query) === 1062) {
            $message = 'Username sudah digunakan.';
        } else {
            $message = 'Terjadi kesalahan sistem. Silakan coba lagi.';
        }

        flash_redirect($message, $page);
    }

    // Memperbarui username dalam session
    $_SESSION['username'] = $username;

    // Memperbarui cookie Remember Me jika tersedia
    if (isset($_COOKIE['remember_username'])) {

        // Memeriksa koneksi HTTPS
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        // Menentukan path cookie
        $path = dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/';

        if ($path === '//') {
            $path = '/';
        }

        // Memperbarui cookie dengan username baru
        setcookie('remember_username', $username, [
            'expires' => time() + 604800,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    // Pesan keberhasilan
    $message = 'Username berhasil diperbarui.';

} else {

    // Memverifikasi password lama
    if (!verify_password($old_password, $user['password_hash'])) {
        flash_redirect('Password lama salah.', $page);
    }

    // Membuat hash password baru
    $hash = hash_password($new_password);

    if ($hash === false) {
        flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
    }

    // Memperbarui password pengguna
    // Hash lama mencegah pembaruan memakai password
    // yang sudah diganti oleh permintaan lain
    $query = mysqli_prepare(
        $connection,
        'UPDATE users
         SET password_hash = ?
         WHERE id = ? AND password_hash = ?'
    );

    if (
        !$query ||
        !mysqli_stmt_bind_param(
            $query,
            'sss',
            $hash,
            $_SESSION['user_id'],
            $user['password_hash']
        ) ||
        !mysqli_stmt_execute($query)
    ) {
        flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
    }

    // Memastikan password berhasil diperbarui
    if (mysqli_stmt_affected_rows($query) !== 1) {
        flash_redirect('Password lama salah.', $page);
    }

    // Pesan keberhasilan
    $message = 'Password berhasil diperbarui.';
}

// Menutup statement dan koneksi database
mysqli_stmt_close($query);
mysqli_close($connection);

// Mengarahkan pengguna dengan pesan berhasil
flash_redirect($message, $page, 'success');