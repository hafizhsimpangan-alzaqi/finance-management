<?php
require_once __DIR__ . '/../../process/guard.php';
$errorPage = false;
require_once __DIR__ . '/../../database/connection.php';

/** @var mysqli $connection */

if (!mysqli_begin_transaction($connection, MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT)) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

date_default_timezone_set('Asia/Jakarta');
$now = time();
$monthStart = date('Y-m-01', $now);
$nextMonth = date('Y-m-d', strtotime($monthStart . ' +1 month'));
$query = mysqli_prepare($connection, 'SELECT username, opening_balance FROM users WHERE id = ?');

if (!$query || !mysqli_stmt_bind_param($query, 's', $_SESSION['user_id']) || !mysqli_stmt_execute($query) || !($result = mysqli_stmt_get_result($query)) || !($user = mysqli_fetch_assoc($result))) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

mysqli_stmt_close($query);
$username = $user['username'];

$opening = $user['opening_balance'] === null ? null : nominal_integer((string) $user['opening_balance']);
if ($user['opening_balance'] !== null && $opening === null) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

$monthlyIncome = 0;
$monthlyExpense = 0;
$monthBalance = null;
$currentBalance = null;

if ($opening !== null) {
    $query = mysqli_prepare($connection, "SELECT
        COALESCE(SUM(CASE WHEN type='income' THEN amount ELSE 0 END),0) AS total_income,
        COALESCE(SUM(CASE WHEN type='expense' THEN amount ELSE 0 END),0) AS total_expense,
        COALESCE(SUM(CASE WHEN type='income' AND transaction_date < ? THEN amount ELSE 0 END),0) AS previous_income,
        COALESCE(SUM(CASE WHEN type='expense' AND transaction_date < ? THEN amount ELSE 0 END),0) AS previous_expense,
        COALESCE(SUM(CASE WHEN type='income' AND transaction_date >= ? AND transaction_date < ? THEN amount ELSE 0 END),0) AS monthly_income,
        COALESCE(SUM(CASE WHEN type='expense' AND transaction_date >= ? AND transaction_date < ? THEN amount ELSE 0 END),0) AS monthly_expense
        FROM transactions WHERE user_id = ?");
    if (!$query || !mysqli_stmt_bind_param($query, 'sssssss', $monthStart, $monthStart, $monthStart, $nextMonth, $monthStart, $nextMonth, $_SESSION['user_id']) || !mysqli_stmt_execute($query) || !($result = mysqli_stmt_get_result($query)) || !($totals = mysqli_fetch_assoc($result))) {
        http_response_code(503);
        exit('Terjadi kesalahan sistem. Silakan coba lagi.');
    }
    mysqli_stmt_close($query);
    foreach ($totals as $key => $value) {
        $totals[$key] = nominal_integer((string) $value);
        if ($totals[$key] === null) {
            http_response_code(503);
            exit('Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }
    $monthlyIncome = $totals['monthly_income'];
    $monthlyExpense = $totals['monthly_expense'];
    $monthBalance = hitung_saldo($opening, $totals['previous_income'], $totals['previous_expense']);
    $currentBalance = hitung_saldo($opening, $totals['total_income'], $totals['total_expense']);
    if ($monthBalance === null || $currentBalance === null) {
        http_response_code(503);
        exit('Terjadi kesalahan sistem. Silakan coba lagi.');
    }
}

$transactions = [];
$query = mysqli_prepare($connection, 'SELECT transaction_date, description, type, amount FROM transactions WHERE user_id = ? AND transaction_date >= ? AND transaction_date < ? ORDER BY transaction_date DESC, created_at DESC, id DESC LIMIT 5');
if (!$query || !mysqli_stmt_bind_param($query, 'sss', $_SESSION['user_id'], $monthStart, $nextMonth) || !mysqli_stmt_execute($query) || !($result = mysqli_stmt_get_result($query))) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}
while ($transaction = mysqli_fetch_assoc($result)) {
    $amount = nominal_integer((string) $transaction['amount']);
    if ($amount === null || $amount === 0 || !in_array($transaction['type'], ['income', 'expense'], true)) {
        http_response_code(503);
        exit('Terjadi kesalahan sistem. Silakan coba lagi.');
    }
    $transaction['amount'] = $amount;
    $transactions[] = $transaction;
}
mysqli_stmt_close($query);

if (!mysqli_commit($connection)) {
    mysqli_rollback($connection);
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

mysqli_close($connection);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8faff">
    <title>Dashboard — Finance Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
    <script src="../js/dashboard.js" defer></script>
</head>

<body>
    <div class="app-shell" id="app-shell">
        <aside class="sidebar" aria-label="Navigasi aplikasi">
            <a class="brand" href="dashboard.php" aria-label="Finance Management, Dashboard">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none">
                        <path d="M5 23.5 12.2 16l4.7 4.7L27 9.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M19.5 9.5H27v7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg></span>
                <span>Finance <strong>Management</strong></span>
            </a>
            <p class="nav-caption">MENU UTAMA</p>
            <nav class="side-nav" aria-label="Halaman aplikasi">
                <a class="nav-item is-active" href="dashboard.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
                <a class="nav-item" href="report.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
                    <h1>Dashboard</h1>
                </div>
                <div class="profile"><span class="avatar" aria-hidden="true">F</span><span class="profile-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span></div>
            </header>
            <main class="page-content">
                <form class="mobile-logout" action="../../process/logout.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit">Keluar</button></form>
                <?php if (isset($_SESSION['flash'])): ?><p class="page-notice" role="alert"><?= htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8') ?></p><?php unset($_SESSION['flash']);
                                                                                                                                                                        endif; ?>
                <section class="welcome-row" aria-labelledby="welcome-title">
                    <div>
                        <p class="welcome-kicker">Ringkasan bulanan · <?= htmlspecialchars(date('m/Y', $now), ENT_QUOTES, 'UTF-8') ?></p>
                        <h2 id="welcome-title">Halo, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>!</h2>
                        <p>Pantau pergerakan keuanganmu dalam satu tampilan.</p>
                    </div>
                </section>

                <?php if ($opening === null): ?>
                    <section class="balance-notice" aria-labelledby="balance-notice-title">
                        <span class="notice-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 3.5 21 20H3L12 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
                                <path d="M12 9v5M12 17h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg></span>
                        <div class="notice-copy">
                            <h2 id="balance-notice-title">Saldo awal belum ditetapkan</h2>
                            <p>Tetapkan saldo awal untuk mulai melihat saldo keuanganmu.</p>
                        </div>
                        <button class="button button-primary notice-button" id="open-balance-dialog" type="button"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            </svg><span>Tetapkan Saldo Awal</span></button>
                    </section>

                <?php endif; ?>
                <section class="summary-grid" aria-label="Ringkasan keuangan bulan ini">
                    <article class="summary-card">
                        <div class="summary-top"><span>Saldo Awal Bulan Berjalan</span><span class="summary-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 18.5 9.2 13l3.4 3.3L20 8.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M14.5 8.5H20V14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span></div>
                        <p class="summary-value"><?= $monthBalance === null ? '—' : format_rupiah($monthBalance) ?></p>
                        <p class="summary-caption"><?= $opening === null ? 'Belum ditetapkan' : 'Awal bulan berjalan' ?></p>
                    </article>
                    <article class="summary-card">
                        <div class="summary-top"><span>Total Pemasukan Bulan Ini</span><span class="summary-icon icon-sky" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <path d="M5 17 17 5M7 5h10v10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span></div>
                        <p class="summary-value"><?= format_rupiah($monthlyIncome) ?></p>
                        <p class="summary-caption">Bulan berjalan</p>
                    </article>
                    <article class="summary-card">
                        <div class="summary-top"><span>Total Pengeluaran Bulan Ini</span><span class="summary-icon icon-sky" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <path d="M5 7 17 19M7 19h10V9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span></div>
                        <p class="summary-value"><?= format_rupiah($monthlyExpense) ?></p>
                        <p class="summary-caption">Bulan berjalan</p>
                    </article>
                    <article class="summary-card">
                        <div class="summary-top"><span>Saldo Saat Ini</span><span class="summary-icon icon-blue" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3.5" y="5.5" width="17" height="13" rx="2.5" stroke="currentColor" stroke-width="1.7" />
                                    <path d="M3.5 9h17M15.5 14h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                </svg></span></div>
                        <p class="summary-value"><?= $currentBalance === null ? '—' : format_rupiah($currentBalance) ?></p>
                        <p class="summary-caption"><?= $opening === null ? 'Menunggu saldo awal' : 'Saldo saat ini' ?></p>
                    </article>
                </section>

                <section class="recent-section" aria-labelledby="recent-title">
                    <div class="section-heading">
                        <div>
                            <h2 id="recent-title">Transaksi Terbaru</h2>
                            <p>Maksimal lima transaksi pada bulan berjalan.</p>
                        </div>
                    </div>
                    <?php if ($transactions): ?>
                        <ul class="recent-list">
                            <?php foreach ($transactions as $transaction): ?>
                                <li class="recent-item">
                                    <div class="recent-details"><strong><?= htmlspecialchars($transaction['description'], ENT_QUOTES, 'UTF-8') ?></strong><span><time datetime="<?= htmlspecialchars($transaction['transaction_date'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('d/m/Y', strtotime($transaction['transaction_date'])), ENT_QUOTES, 'UTF-8') ?></time> · <?= $transaction['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran' ?></span></div>
                                    <span class="recent-amount"><?= $transaction['type'] === 'income' ? '+' : '−' ?><?= format_rupiah($transaction['amount']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="empty-state"><span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <path d="M6 4.5h12A1.5 1.5 0 0 1 19.5 6v14H4.5V6A1.5 1.5 0 0 1 6 4.5Z" stroke="currentColor" stroke-width="1.6" />
                                    <path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                    <path d="M8 2.5v4M16 2.5v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
                                </svg></span>
                            <h3>Belum ada transaksi pada bulan ini.</h3>
                        </div>
                    <?php endif; ?>
                </section>
                <p class="page-footnote">Ringkasan saldo dihitung dari data akun Anda.</p>
            </main>
        </div>
    </div>

    <nav class="bottom-nav" aria-label="Navigasi utama">
        <a class="bottom-nav-item is-active" href="dashboard.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
        <a class="bottom-nav-item" href="report.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 4.5h14v15H5zM8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            </svg><span>Laporan</span></a>
        <a class="bottom-nav-item" href="settings.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7" />
                <path d="m19.4 15 .1.1 1.1.8-1.4 2.4-1.3-.6a7.8 7.8 0 0 1-1.4.8l-.2 1.4h-2.8l-.2-1.4a7.8 7.8 0 0 1-1.4-.8l-1.3.6-1.4-2.4 1.1-.8a7 7 0 0 1 0-1.6l-1.1-.8 1.4-2.4 1.3.6a7.8 7.8 0 0 1 1.4-.8l.2-1.4h2.8l.2 1.4a7.8 7.8 0 0 1 1.4.8l1.3-.6 1.4 2.4-1.1.8a7 7 0 0 1 0 1.6Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
            </svg><span>Pengaturan</span></a>
    </nav>

    <dialog class="modal" id="balance-dialog" aria-labelledby="balance-dialog-title" aria-describedby="balance-dialog-description">
        <div class="modal-head">
            <div><span class="modal-kicker">PENGATURAN SALDO</span>
                <h2 id="balance-dialog-title">Tetapkan Saldo Awal</h2>
            </div><button class="icon-button" id="close-balance-dialog" type="button" aria-label="Tutup dialog"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg></button>
        </div>
        <p class="modal-description" id="balance-dialog-description">Masukkan saldo yang tersedia saat mulai mencatat keuangan. Nilai Rp0 diperbolehkan.</p>
        <form id="balance-form" action="../../process/balance.php" method="post" novalidate>
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="source" value="dashboard">
            <div class="field-group"><label for="opening-balance">Nominal saldo awal</label>
                <div class="money-input"><span aria-hidden="true">Rp</span><input id="opening-balance" name="opening_balance" type="text" inputmode="numeric" autocomplete="off" aria-describedby="balance-help balance-error" aria-invalid="false"></div>
                <p class="field-hint" id="balance-help">Masukkan angka bulat tanpa titik atau koma.</p>
                <p class="field-error" id="balance-error" aria-live="polite"></p>
            </div>
            <p class="form-feedback" id="balance-feedback" role="status" aria-live="polite"></p>
            <div class="modal-actions"><button class="button button-secondary" id="cancel-balance-dialog" type="button">Batal</button><button class="button button-primary" type="submit">Simpan</button></div>
        </form>
    </dialog>
</body>

</html>