<?php

if (!isset($errorPage)) {
    http_response_code(403);
    exit;
}

if (isset($connection)) {
    return;
}

// Menonaktifkan laporan error MySQLi
mysqli_report(MYSQLI_REPORT_OFF);

// Membaca konfigurasi database dari file .env
$env = @parse_ini_file(
    __DIR__ . '/../.env',
    false,
    INI_SCANNER_RAW
);

// Memeriksa apakah file .env berhasil dibaca
$valid = is_array($env);

foreach ([
    'FM_DB_HOST',
    'FM_DB_USER',
    'FM_DB_PASSWORD',
    'FM_DB_NAME',
    'FM_DB_PORT'
] as $key) {

    if (!isset($env[$key]) || !is_string($env[$key])) {
        $valid = false;
    }
}


$connection = false;

if (
    $valid &&
    $env['FM_DB_HOST'] !== '' &&
    $env['FM_DB_USER'] !== '' &&
    $env['FM_DB_NAME'] !== ''
) {
    $connection = @mysqli_connect(
        $env['FM_DB_HOST'],
        $env['FM_DB_USER'],
        $env['FM_DB_PASSWORD'],
        $env['FM_DB_NAME'],
        (int) $env['FM_DB_PORT']
    );
}

if (!$connection) {

    if ($errorPage === false) {
        http_response_code(503);
        exit('Terjadi kesalahan sistem. Silakan coba lagi.');
    }

    flash_redirect(
        'Terjadi kesalahan sistem. Silakan coba lagi.',
        $errorPage
    );
}