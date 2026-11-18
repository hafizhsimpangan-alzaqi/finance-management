<?php

if (session_status() !== PHP_SESSION_ACTIVE) {

    ini_set('session.use_strict_mode', '1');

    $params = session_get_cookie_params();
    $params['httponly'] = true;
    $params['samesite'] = 'Lax';
    $params['secure'] = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params($params);
    session_start();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}


function flash_redirect($message, $page, $type = 'error') {

    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];

    header('Location: ' . $page);
    exit;
}


function csrf_valid() {

    return isset($_POST['csrf']) &&
           is_string($_POST['csrf']) &&
           hash_equals($_SESSION['csrf'], $_POST['csrf']);
}


// Mengubah input nominal menjadi integer
function nominal_integer($value) {

    // Memastikan input berupa string angka
    if (!is_string($value) || !preg_match('/^[0-9]+$/D', $value)) {
        return null;
    }

    // Menghapus angka nol di depan
    $value = ltrim($value, '0');

    if ($value === '') {
        $value = '0';
    }

    // Memeriksa batas maksimum integer PHP
    $max = (string) PHP_INT_MAX;

    if (
        strlen($value) > strlen($max) ||
        (
            strlen($value) === strlen($max) &&
            strcmp($value, $max) > 0
        )
    ) {
        return null;
    }

    return (int) $value;
}


function hitung_saldo($awal, $masuk, $keluar) {

    // Jika pemasukan lebih besar atau sama dengan pengeluaran
    if ($masuk >= $keluar) {

        $selisih = $masuk - $keluar;

        // Memeriksa batas maksimum integer
        if ($awal > PHP_INT_MAX - $selisih) {
            return null;
        }

        return $awal + $selisih;
    }

    // Jika pengeluaran lebih besar daripada pemasukan
    return $awal - ($keluar - $masuk);
}


// Mengubah nominal menjadi format Rupiah
function format_rupiah($value) {

    return 'Rp' . preg_replace(
        '/\B(?=(\d{3})+(?!\d))/',
        '.',
        (string) $value
    );
}


function ambil_laporan($connection, $userId, $now) {

    // Daftar nama bulan
    $monthNames = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    // Menentukan periode laporan
    $monthStart = date('Y-m-01', $now);
    $nextMonth = date('Y-m-d', strtotime($monthStart . ' +1 month'));


    // 1. Mengambil informasi pengguna
    $query = mysqli_prepare(
        $connection,
        'SELECT username, opening_balance
         FROM users
         WHERE id = ?'
    );

    if (
        !$query ||
        !mysqli_stmt_bind_param($query, 's', $userId) ||
        !mysqli_stmt_execute($query) ||
        !($result = mysqli_stmt_get_result($query)) ||
        !($user = mysqli_fetch_assoc($result))
    ) {
        return false;
    }

    mysqli_stmt_close($query);

    // Memastikan saldo awal sudah ditetapkan
    if ($user['opening_balance'] === null) {
        return false;
    }

    $opening = nominal_integer((string) $user['opening_balance']);

    if ($opening === null) {
        return false;
    }


    // 2. Mengambil ringkasan pemasukan dan pengeluaran
    $query = mysqli_prepare(
        $connection,
        "SELECT
            COALESCE(
                SUM(CASE
                    WHEN type = 'income' AND transaction_date < ?
                    THEN amount ELSE 0
                END), 0
            ) AS previous_income,

            COALESCE(
                SUM(CASE
                    WHEN type = 'expense' AND transaction_date < ?
                    THEN amount ELSE 0
                END), 0
            ) AS previous_expense,

            COALESCE(
                SUM(CASE
                    WHEN type = 'income'
                    AND transaction_date >= ?
                    AND transaction_date < ?
                    THEN amount ELSE 0
                END), 0
            ) AS monthly_income,

            COALESCE(
                SUM(CASE
                    WHEN type = 'expense'
                    AND transaction_date >= ?
                    AND transaction_date < ?
                    THEN amount ELSE 0
                END), 0
            ) AS monthly_expense,

            COALESCE(
                SUM(CASE
                    WHEN type = 'income' AND transaction_date < ?
                    THEN amount ELSE 0
                END), 0
            ) AS period_income,

            COALESCE(
                SUM(CASE
                    WHEN type = 'expense' AND transaction_date < ?
                    THEN amount ELSE 0
                END), 0
            ) AS period_expense

         FROM transactions
         WHERE user_id = ?"
    );

    if (
        !$query ||
        !mysqli_stmt_bind_param(
            $query,
            'sssssssss',
            $monthStart,
            $monthStart,
            $monthStart,
            $nextMonth,
            $monthStart,
            $nextMonth,
            $nextMonth,
            $nextMonth,
            $userId
        ) ||
        !mysqli_stmt_execute($query) ||
        !($result = mysqli_stmt_get_result($query)) ||
        !($totals = mysqli_fetch_assoc($result))
    ) {
        return false;
    }

    mysqli_stmt_close($query);


    // 3. Mengubah hasil query menjadi integer
    foreach ($totals as $key => $value) {

        $totals[$key] = nominal_integer((string) $value);

        if ($totals[$key] === null) {
            return false;
        }
    }


    // 4. Menghitung saldo awal dan saldo akhir bulan
    $monthBalance = hitung_saldo(
        $opening,
        $totals['previous_income'],
        $totals['previous_expense']
    );

    $closingBalance = hitung_saldo(
        $opening,
        $totals['period_income'],
        $totals['period_expense']
    );

    if ($monthBalance === null || $closingBalance === null) {
        return false;
    }


    // 5. Mengambil daftar transaksi bulan berjalan
    $query = mysqli_prepare(
        $connection,
        'SELECT
            transaction_date,
            type,
            description,
            note,
            amount
         FROM transactions
         WHERE user_id = ?
         AND transaction_date >= ?
         AND transaction_date < ?
         ORDER BY
            transaction_date DESC,
            created_at DESC,
            id DESC'
    );

    if (
        !$query ||
        !mysqli_stmt_bind_param(
            $query,
            'sss',
            $userId,
            $monthStart,
            $nextMonth
        ) ||
        !mysqli_stmt_execute($query) ||
        !($result = mysqli_stmt_get_result($query))
    ) {
        return false;
    }

    $transactions = [];

    while ($row = mysqli_fetch_assoc($result)) {

        $row['amount'] = nominal_integer((string) $row['amount']);

        if (
            $row['amount'] === null ||
            $row['amount'] === 0 ||
            !in_array($row['type'], ['income', 'expense'], true)
        ) {
            return false;
        }

        $transactions[] = $row;
    }

    mysqli_stmt_close($query);


    // 6. Mengembalikan seluruh data laporan
    return [
        'username' => $user['username'],

        'month_key' => date('Y-m', $now),

        'month_label' =>
            $monthNames[(int) date('n', $now)] . ' ' . date('Y', $now),

        'report_date' =>
            date('j', $now) . ' ' .
            $monthNames[(int) date('n', $now)] . ' ' .
            date('Y', $now),

        'opening' => $monthBalance,
        'income' => $totals['monthly_income'],
        'expense' => $totals['monthly_expense'],
        'closing' => $closingBalance,
        'transactions' => $transactions,
        'month_names' => $monthNames
    ];
}

function hash_password($password) {

    $hash = password_hash(
        base64_encode(hash('sha256', $password, true)),
        PASSWORD_DEFAULT
    );

    if ($hash === false) {
        return false;
    } else {
        return 'sha256:' . $hash;
    }
}


function verify_password($password, $hash) {

    // Memeriksa format hash password baru
    if (strpos($hash, 'sha256:') === 0) {

        return password_verify(
            base64_encode(hash('sha256', $password, true)),
            substr($hash, 7)
        );
    }

    // Memverifikasi password dengan format hash lama
    return password_verify($password, $hash);
}