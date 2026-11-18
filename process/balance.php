<?php

require_once __DIR__ . '/helpers.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../view/page/login.php');
    exit;
}

$source = $_POST['source'] ?? 'dashboard';
$page = $source === 'settings'
    ? '../view/page/settings.php'
    : '../view/page/dashboard.php';

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    !csrf_valid() ||
    !in_array($source, ['dashboard', 'settings'], true)
) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        $page
    );
}

$amount = nominal_integer($_POST['opening_balance'] ?? null);

if ($amount === null) {
    flash_redirect(
        'Saldo awal harus berupa bilangan bulat minimal Rp0 dalam batas yang didukung.',
        $page
    );
}

$errorPage = $page;
require_once __DIR__ . '/../database/connection.php';

/** @var mysqli $connection */

if (!mysqli_begin_transaction($connection)) {
    flash_redirect(
        'Terjadi kesalahan sistem. Silakan coba lagi.',
        $page
    );
}

$query = mysqli_prepare(
    $connection,
    'SELECT opening_balance FROM users WHERE id = ? FOR UPDATE'
);

if (
    !$query ||
    !mysqli_stmt_bind_param($query, 's', $_SESSION['user_id']) ||
    !mysqli_stmt_execute($query) ||
    !($result = mysqli_stmt_get_result($query)) ||
    !($user = mysqli_fetch_assoc($result))
) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

mysqli_stmt_close($query);

if ($source === 'dashboard' && $user['opening_balance'] !== null) {
    mysqli_rollback($connection);
    flash_redirect('Saldo awal sudah ditetapkan.', $page);
}

if ($source === 'settings' && $user['opening_balance'] === null) {
    mysqli_rollback($connection);
    flash_redirect(
        'Tetapkan saldo awal melalui Dashboard.',
        '../view/page/dashboard.php'
    );
}

$query = mysqli_prepare(
    $connection,
    "SELECT
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense
     FROM transactions
     WHERE user_id = ?"
);

if (
    !$query ||
    !mysqli_stmt_bind_param($query, 's', $_SESSION['user_id']) ||
    !mysqli_stmt_execute($query) ||
    !($result = mysqli_stmt_get_result($query)) ||
    !($totals = mysqli_fetch_assoc($result))
) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

mysqli_stmt_close($query);

$income = nominal_integer((string) $totals['income']);
$expense = nominal_integer((string) $totals['expense']);

$balance = $income === null || $expense === null
    ? null
    : hitung_saldo($amount, $income, $expense);

if ($balance === null) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

if ($balance < 0) {
    mysqli_rollback($connection);
    flash_redirect(
        'Saldo awal ini akan menyebabkan saldo akhir negatif.',
        $page
    );
}

$query = mysqli_prepare(
    $connection,
    'UPDATE users SET opening_balance = ? WHERE id = ?'
);

if (
    !$query ||
    !mysqli_stmt_bind_param($query, 'ss', $amount, $_SESSION['user_id']) ||
    !mysqli_stmt_execute($query) ||
    !mysqli_commit($connection)
) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

mysqli_stmt_close($query);
mysqli_close($connection);

flash_redirect(
    $source === 'settings'
        ? 'Saldo awal berhasil diperbarui.'
        : 'Saldo awal berhasil disimpan.',
    $page,
    'success'
);
