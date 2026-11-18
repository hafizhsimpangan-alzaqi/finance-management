<?php

require_once __DIR__ . '/helpers.php';

if (isset($transactionType) && $transactionType === 'expense') {
    $type = 'expense';
} else {
    $type = 'income';
}

$page = '../view/page/' . $type . '.php';

if ($type === 'income') {
    $label = 'Pemasukan';
} else {
    $label = 'Pengeluaran';
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../view/page/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        $page
    );
}

$action = $_POST['action'] ?? '';

if (!in_array($action, ['add', 'edit', 'delete'], true)) {
    flash_redirect(
        'Permintaan tidak valid. Silakan muat ulang halaman dan coba lagi.',
        $page
    );
}

$id = null;

if ($action !== 'add') {
    $id = nominal_integer($_POST['transaction_id'] ?? null);

    if ($id === null || $id === 0) {
        flash_redirect('Transaksi tidak ditemukan.', $page);
    }
}

if ($action !== 'delete') {

    if (is_string($_POST['description'] ?? null)) {
        $description = trim($_POST['description']);
    } else {
        $description = '';
    }

    if (is_string($_POST['note'] ?? null)) {
        $note = $_POST['note'];
    } else {
        $note = '';
    }

    $amount = nominal_integer($_POST['amount'] ?? null);

    if ($description === '' || mb_strlen($description, 'UTF-8') > 100) {
        flash_redirect(
            'Keterangan wajib diisi dan maksimal 100 karakter.',
            $page
        );
    }

    if (mb_strlen($note, 'UTF-8') > 500) {
        flash_redirect('Catatan maksimal 500 karakter.', $page);
    }

    if ($amount === null || $amount === 0) {
        flash_redirect(
            'Nominal harus berupa bilangan bulat lebih dari Rp0.',
            $page
        );
    }
}

$errorPage = $page;
require __DIR__ . '/../database/connection.php';

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

if ($user['opening_balance'] === null) {
    mysqli_rollback($connection);

    flash_redirect(
        'Tetapkan saldo awal sebelum membuka halaman ini.',
        '../view/page/dashboard.php'
    );
}

$opening = nominal_integer((string) $user['opening_balance']);
$oldAmount = 0;

if ($action !== 'add') {

    $query = mysqli_prepare(
        $connection,
        'SELECT amount FROM transactions
         WHERE id = ? AND user_id = ? AND type = ?'
    );

    if (
        !$query ||
        !mysqli_stmt_bind_param(
            $query,
            'sss',
            $id,
            $_SESSION['user_id'],
            $type
        ) ||
        !mysqli_stmt_execute($query) ||
        !($result = mysqli_stmt_get_result($query))
    ) {
        mysqli_rollback($connection);
        flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
    }

    $transaction = mysqli_fetch_assoc($result);
    mysqli_stmt_close($query);

    if (!$transaction) {
        mysqli_rollback($connection);
        flash_redirect('Transaksi tidak ditemukan.', $page);
    }

    $oldAmount = nominal_integer((string) $transaction['amount']);
}

$query = mysqli_prepare(
    $connection,
    "SELECT
        COALESCE(
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END),
            0
        ) AS income,
        COALESCE(
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END),
            0
        ) AS expense
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

if (
    $opening === null ||
    $income === null ||
    $expense === null ||
    $oldAmount === null
) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

if ($type === 'income') {
    $remaining = $income - $oldAmount;
} else {
    $remaining = $expense - $oldAmount;
}

if ($action === 'delete') {
    $replacement = 0;
} else {
    $replacement = $amount;
}

if ($remaining < 0 || $remaining > PHP_INT_MAX - $replacement) {
    mysqli_rollback($connection);

    flash_redirect(
        'Nominal melebihi batas perhitungan yang didukung.',
        $page
    );
}

$current = hitung_saldo($opening, $income, $expense);

if ($current === null) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

if ($action === 'delete') {
    $newAmount = 0;
} else {
    $newAmount = $amount;
}

if ($type === 'income') {
    $increase = $newAmount;
    $decrease = $oldAmount;
} else {
    $increase = $oldAmount;
    $decrease = $newAmount;
}

if ($current < 0) {

    if ($increase < $decrease || $increase - $decrease < -$current) {
        mysqli_rollback($connection);

        flash_redirect(
            'Saldo tidak mencukupi untuk transaksi ini.',
            $page
        );
    }

    $projected = ($increase - $decrease) - (-$current);

} else {
    $projected = hitung_saldo($current, $increase, $decrease);
}

if ($projected === null) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

if ($projected < 0) {
    mysqli_rollback($connection);

    flash_redirect(
        'Saldo tidak mencukupi untuk transaksi ini.',
        $page
    );
}

if ($action === 'add') {

    date_default_timezone_set('Asia/Jakarta');

    $date = date('Y-m-d');
    $createdAt = date('Y-m-d H:i:s');

    $query = mysqli_prepare(
        $connection,
        'INSERT INTO transactions
         (user_id, type, description, note, amount, transaction_date, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    $bound = $query && mysqli_stmt_bind_param(
        $query,
        'sssssss',
        $_SESSION['user_id'],
        $type,
        $description,
        $note,
        $amount,
        $date,
        $createdAt
    );

} elseif ($action === 'edit') {

    $query = mysqli_prepare(
        $connection,
        'UPDATE transactions
         SET description = ?, note = ?, amount = ?
         WHERE id = ? AND user_id = ? AND type = ?'
    );

    $bound = $query && mysqli_stmt_bind_param(
        $query,
        'ssssss',
        $description,
        $note,
        $amount,
        $id,
        $_SESSION['user_id'],
        $type
    );

} else {

    $query = mysqli_prepare(
        $connection,
        'DELETE FROM transactions
         WHERE id = ? AND user_id = ? AND type = ?'
    );

    $bound = $query && mysqli_stmt_bind_param(
        $query,
        'sss',
        $id,
        $_SESSION['user_id'],
        $type
    );
}

if (
    !$bound ||
    !mysqli_stmt_execute($query) ||
    !mysqli_commit($connection)
) {
    mysqli_rollback($connection);
    flash_redirect('Terjadi kesalahan sistem. Silakan coba lagi.', $page);
}

mysqli_stmt_close($query);
mysqli_close($connection);

if ($action === 'add') {
    $message = 'ditambahkan';
} elseif ($action === 'edit') {
    $message = 'diubah';
} else {
    $message = 'dihapus';
}

flash_redirect(
    $label . ' berhasil ' . $message . '.',
    $page,
    'success'
);