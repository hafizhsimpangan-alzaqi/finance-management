<?php
$requireBalance = true;
require_once __DIR__ . '/../../process/guard.php';
$errorPage = false;
require __DIR__ . '/../../database/connection.php';

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

$username = $report['username'];
$monthLabel = $report['month_label'];
$reportDate = $report['report_date'];
$openingBalance = $report['opening'];
$incomeTotal = $report['income'];
$expenseTotal = $report['expense'];
$closingBalance = $report['closing'];
$transactions = $report['transactions'];
$monthNames = $report['month_names'];
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8faff">
    <title>Laporan Keuangan — Finance Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/report.css">
    <script src="../js/report.js" defer></script>
</head>

<body>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Navigasi aplikasi">
            <a class="brand" href="dashboard.php" aria-label="Finance Management, Dashboard"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none">
                        <path d="M5 23.5 12.2 16l4.7 4.7L27 9.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M19.5 9.5H27v7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg></span><span>Finance <strong>Management</strong></span></a>
            <p class="nav-caption">MENU UTAMA</p>
            <nav class="side-nav" aria-label="Halaman aplikasi">
                <a class="nav-item" href="dashboard.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                        <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                        <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                        <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                    </svg><span>Dashboard</span></a>
                <a class="nav-item" href="income.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 18.5 9.2 13l3.4 3.3L20 8.5M14.5 8.5H20V14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg><span>Pemasukan</span></a>
                <a class="nav-item" href="expense.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 7 17 19M7 19h10V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg><span>Pengeluaran</span></a>
                <a class="nav-item is-active" href="report.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 4.5h14v15H5zM8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg><span>Laporan</span></a>
                <a class="nav-item" href="settings.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7" />
                        <path d="m19.4 15 .1.1 1.1.8-1.4 2.4-1.3-.6a7.8 7.8 0 0 1-1.4.8l-.2 1.4h-2.8l-.2-1.4a7.8 7.8 0 0 1-1.4-.8l-1.3.6-1.4-2.4 1.1-.8a7 7 0 0 1 0-1.6l-1.1-.8 1.4-2.4 1.3.6a7.8 7.8 0 0 1 1.4-.8l.2-1.4h2.8l.2 1.4a7.8 7.8 0 0 1 1.4.8l1.3-.6 1.4 2.4-1.1.8a7 7 0 0 1 0 1.6Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
                    </svg><span>Pengaturan</span></a>
            </nav>
            <div class="sidebar-bottom"><span class="sidebar-divider"></span>
                <form action="../../process/logout.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button class="nav-item logout-button" type="submit"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M10 5H5.5A1.5 1.5 0 0 0 4 6.5v11A1.5 1.5 0 0 0 5.5 19H10M15 16l4-4-4-4M19 12H9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg><span>Keluar</span></button></form>
            </div>
            <p class="sidebar-footnote">Finance Management</p>
        </aside>
        <div class="workspace">
            <header class="topbar">
                <div>
                    <p class="topbar-eyebrow">RUANG KEUANGAN PRIBADI</p>
                    <h1>Laporan Keuangan</h1>
                </div>
                <div class="profile"><span class="avatar" aria-hidden="true">F</span><span class="profile-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span></div>
            </header>
            <main class="page-content">
                <form class="mobile-logout" action="../../process/logout.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit">Keluar</button></form>
                <section class="page-heading" aria-labelledby="page-title">
                    <div>
                        <p class="welcome-kicker">Periode laporan · <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></p>
                        <h2 id="page-title">Ringkasan keuanganmu</h2>
                        <p>Laporan transaksi dan saldo selama bulan berjalan.</p>
                    </div>
                    <div class="report-actions"><a class="button button-secondary" href="../../process/export_csv.php">Ekspor CSV</a><button class="button button-primary" id="print-report" type="button"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6H7z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                            </svg><span>Cetak / Simpan PDF</span></button></div>
                </section>
                <article class="report-paper" aria-labelledby="report-title">
                    <header class="report-identity">
                        <div class="report-brand"><span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none">
                                    <path d="M5 23.5 12.2 16l4.7 4.7L27 9.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M19.5 9.5H27v7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span><span>Finance <strong>Management</strong></span></div>
                        <h2 id="report-title">Laporan Keuangan</h2>
                        <p class="report-period"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></p>
                        <dl class="report-meta">
                            <div>
                                <dt>Pengguna</dt>
                                <dd><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                            <div>
                                <dt>Periode</dt>
                                <dd><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                            <div>
                                <dt>Tanggal dibuat</dt>
                                <dd><?= htmlspecialchars($reportDate, ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                        </dl>
                    </header>
                    <section class="report-summary" aria-label="Ringkasan keuangan">
                        <article class="report-card"><span>Saldo Awal Bulan</span><strong><?= format_rupiah($openingBalance) ?></strong></article>
                        <article class="report-card"><span>Total Pemasukan</span><strong class="income-value"><?= format_rupiah($incomeTotal) ?></strong></article>
                        <article class="report-card"><span>Total Pengeluaran</span><strong class="expense-value"><?= format_rupiah($expenseTotal) ?></strong></article>
                        <article class="report-card"><span>Saldo Akhir Bulan</span><strong><?= format_rupiah($closingBalance) ?></strong></article>
                    </section>
                    <section class="report-transactions" aria-labelledby="transactions-title">
                        <div class="section-heading">
                            <div>
                                <h3 id="transactions-title">Detail Transaksi Bulan Ini</h3>
                                <p><?= count($transactions) ?> transaksi · <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <?php if ($transactions): ?><div class="table-wrap">
                                <table class="report-table">
                                    <caption class="visually-hidden">Seluruh transaksi pada periode <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></caption>
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Tanggal</th>
                                            <th scope="col">Jenis</th>
                                            <th scope="col">Keterangan</th>
                                            <th scope="col">Catatan</th>
                                            <th scope="col">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $index => $transaction): $timestamp = strtotime($transaction['transaction_date']); ?>
                                            <tr>
                                                <td data-label="No"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></td>
                                                <td data-label="Tanggal"><time datetime="<?= htmlspecialchars($transaction['transaction_date'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('j', $timestamp) . ' ' . substr($monthNames[(int) date('n', $timestamp)], 0, 3) . ' ' . date('Y', $timestamp), ENT_QUOTES, 'UTF-8') ?></time></td>
                                                <td data-label="Jenis"><span class="type-badge <?= $transaction['type'] === 'income' ? 'type-income' : 'type-expense' ?>"><?= $transaction['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' ?></span></td>
                                                <td data-label="Keterangan"><?= htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td data-label="Catatan"><?= $transaction['note'] !== null && $transaction['note'] !== '' ? nl2br(htmlspecialchars($transaction['note'], ENT_QUOTES, 'UTF-8')) : '—' ?></td>
                                                <td data-label="Nominal" class="<?= $transaction['type'] === 'income' ? 'income-value' : 'expense-value' ?>"> <?= $transaction['type'] === 'expense' ? '−' : '+' ?><?= format_rupiah($transaction['amount']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div><?php else: ?><div class="empty-state">
                                <h3>Belum ada transaksi pada bulan ini.</h3>
                                <p>Saldo awal tetap dibawa dari periode sebelumnya.</p>
                            </div><?php endif; ?>
                    </section>
                </article>
            </main>
        </div>
    </div>
    <nav class="bottom-nav" aria-label="Navigasi utama">
        <a class="bottom-nav-item" href="dashboard.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                <rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
                <rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7" />
            </svg><span>Dashboard</span></a>
        <a class="bottom-nav-item" href="income.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M4 18.5 9.2 13l3.4 3.3L20 8.5M14.5 8.5H20V14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg><span>Pemasukan</span></a>
        <a class="bottom-nav-item" href="expense.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 7 17 19M7 19h10V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg><span>Pengeluaran</span></a>
        <a class="bottom-nav-item is-active" href="report.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 4.5h14v15H5zM8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            </svg><span>Laporan</span></a>
        <a class="bottom-nav-item" href="settings.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7" />
                <path d="m19.4 15 .1.1 1.1.8-1.4 2.4-1.3-.6a7.8 7.8 0 0 1-1.4.8l-.2 1.4h-2.8l-.2-1.4a7.8 7.8 0 0 1-1.4-.8l-1.3.6-1.4-2.4 1.1-.8a7 7 0 0 1 0-1.6l-1.1-.8 1.4-2.4 1.3.6a7.8 7.8 0 0 1 1.4-.8l.2-1.4h2.8l.2 1.4a7.8 7.8 0 0 1 1.4.8l1.3-.6 1.4 2.4-1.1.8a7 7 0 0 1 0 1.6Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
            </svg><span>Pengaturan</span></a>
    </nav>
</body>

</html>