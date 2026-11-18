<?php

$guardLogin = '../view/page/login.php';
$guardDashboard = '../view/page/dashboard.php';
$requireBalance = true;

require_once __DIR__ . '/guard.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit;
}

$errorPage = false;
require __DIR__ . '/../database/connection.php';

/** @var mysqli $connection */

date_default_timezone_set('Asia/Jakarta');
$now = time();

if (!mysqli_begin_transaction($connection, MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT)) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

$report = ambil_laporan($connection, $_SESSION['user_id'], $now);

if ($report === false || !mysqli_commit($connection)) {
    mysqli_rollback($connection);
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

mysqli_close($connection);

// Mengamankan teks CSV dari formula spreadsheet
function teks_csv_aman($text) {
    $text = (string) $text;

    if (preg_match('/^[\s\p{Z}\x00-\x1F\x7F]*[=+\-@]/u', $text)) {
        return "'" . $text;
    } else {
        return $text;
    }
}

// Membuka output CSV
$output = @fopen('php://output', 'w');

if ($output === false) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

// Mengatur header file CSV
header('Content-Type: text/csv; charset=UTF-8');
header(
    'Content-Disposition: attachment; filename="laporan_keuangan_' .
    $report['month_key'] . '.csv"'
);
header('X-Content-Type-Options: nosniff');

// Menulis identitas laporan
fputcsv($output, ['Finance Management'], ',', '"', '');
fputcsv($output, ['Laporan Keuangan'], ',', '"', '');

fputcsv($output, [
    'Pengguna',
    teks_csv_aman($report['username'])
], ',', '"', '');

fputcsv($output, [
    'Periode',
    $report['month_label']
], ',', '"', '');

fputcsv($output, [
    'Tanggal Pembuatan',
    $report['report_date']
], ',', '"', '');

// Baris kosong
fputcsv($output, [], ',', '"', '');

// Menulis ringkasan keuangan
fputcsv($output, ['Ringkasan Keuangan'], ',', '"', '');

fputcsv($output, [
    'Saldo Awal Bulan',
    $report['opening']
], ',', '"', '');

fputcsv($output, [
    'Total Pemasukan',
    $report['income']
], ',', '"', '');

fputcsv($output, [
    'Total Pengeluaran',
    $report['expense']
], ',', '"', '');

fputcsv($output, [
    'Saldo Akhir Bulan',
    $report['closing']
], ',', '"', '');

// Baris kosong
fputcsv($output, [], ',', '"', '');

// Menulis judul dan header tabel transaksi
fputcsv($output, ['Detail Transaksi'], ',', '"', '');

fputcsv($output, [
    'No',
    'Tanggal',
    'Jenis',
    'Keterangan',
    'Catatan',
    'Nominal'
], ',', '"', '');

// Menulis seluruh transaksi bulan berjalan
foreach ($report['transactions'] as $index => $transaction) {

    // Menentukan jenis transaksi
    if ($transaction['type'] === 'income') {
        $type = 'Pemasukan';
    } else {
        $type = 'Pengeluaran';
    }

    // Menulis satu baris transaksi
    fputcsv($output, [
        $index + 1,
        $transaction['transaction_date'],
        $type,
        teks_csv_aman($transaction['description']),
        teks_csv_aman($transaction['note'] ?? ''),
        $transaction['amount']
    ], ',', '"', '');
}

if (!$report['transactions']) {
    fputcsv(
        $output,
        ['Belum ada transaksi pada bulan ini.'],
        ',',
        '"',
        ''
    );
}

fclose($output);
exit;