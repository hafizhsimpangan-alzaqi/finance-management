<?php
$requireBalance = true;
require_once __DIR__ . '/../../process/guard.php';
$errorPage = false;
require __DIR__ . '/../../database/connection.php';

/** @var mysqli $connection */

if (!mysqli_begin_transaction($connection, MYSQLI_TRANS_START_WITH_CONSISTENT_SNAPSHOT)) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

date_default_timezone_set('Asia/Jakarta');
$monthStart = date('Y-m-01');
$nextMonth = date('Y-m-d', strtotime('first day of next month'));
$type = 'expense';

$query = mysqli_prepare($connection, "SELECT COALESCE(SUM(amount), 0) AS total, COUNT(*) AS count FROM transactions WHERE user_id = ? AND type = ? AND transaction_date >= ? AND transaction_date < ?");
if (!$query || !mysqli_stmt_bind_param($query, 'ssss', $_SESSION['user_id'], $type, $monthStart, $nextMonth) || !mysqli_stmt_execute($query) || !($result = mysqli_stmt_get_result($query)) || !($summary = mysqli_fetch_assoc($result))) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

mysqli_stmt_close($query);
$monthTotal = nominal_integer((string) $summary['total']);
$monthCount = nominal_integer((string) $summary['count']);

if ($monthTotal === null || $monthCount === null) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

$query = mysqli_prepare($connection, "SELECT id, description, note, amount, transaction_date, created_at FROM transactions WHERE user_id = ? AND type = ? ORDER BY transaction_date DESC, created_at DESC, id DESC");
if (!$query || !mysqli_stmt_bind_param($query, 'ss', $_SESSION['user_id'], $type) || !mysqli_stmt_execute($query) || !($result = mysqli_stmt_get_result($query))) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

$transactions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $amount = nominal_integer((string) $row['amount']);
    if ($amount === null || $amount === 0) {
        http_response_code(503);
        exit('Terjadi kesalahan sistem. Silakan coba lagi.');
    }
    $transactions[] = $row;
}
mysqli_stmt_close($query);

if (!mysqli_commit($connection)) {
    mysqli_rollback($connection);
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}

mysqli_close($connection);
$monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$monthLabel = $monthNames[(int) date('n')] . ' ' . date('Y');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8faff">
    <title>Pengeluaran — Finance Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/expense.css?v=<?= filemtime(__DIR__ . '/../css/expense.css') ?>">
    <script src="../js/expense.js" defer></script>
</head>

<body>
    <div class="app-shell" id="app-shell">
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
                <a class="nav-item is-active" href="expense.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
                    <h1>Pengeluaran</h1>
                </div>
                <div class="profile"><span class="avatar" aria-hidden="true">NP</span><span class="profile-name"><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></span></div>
            </header>
            <main class="page-content">
                <form class="mobile-logout" action="../../process/logout.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit">Keluar</button></form>
                <?php if (isset($_SESSION['flash'])): ?><p class="page-notice" role="status"><?= htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8') ?></p><?php unset($_SESSION['flash']);
                                                                                                                                                                        endif; ?>
                <section class="page-heading" aria-labelledby="page-title">
                    <div>
                        <p class="welcome-kicker">Pengeluaran · <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></p>
                        <h2 id="page-title">Kelola Pengeluaranmu</h2>
                        <p>Catat dan lihat seluruh riwayat pengeluaran Anda.</p>
                    </div><button class="button button-primary add-button" id="open-add-dialog" type="button"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>Tambah Pengeluaran</button>
                </section>
                <section class="expense-summary" aria-label="Ringkasan Pengeluaran bulan ini">
                    <article class="summary-card">
                        <div class="summary-top"><span>Total Pengeluaran Bulan Ini</span><span class="summary-icon icon-blue" aria-hidden="true">Rp</span></div>
                        <p class="summary-value"><?= format_rupiah($monthTotal) ?></p>
                        <p class="summary-caption"><?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?></p>
                    </article>
                    <article class="summary-card">
                        <div class="summary-top"><span>Jumlah Transaksi Bulan Ini</span><span class="summary-icon icon-sky" aria-hidden="true">#</span></div>
                        <p class="summary-value"><?= $monthCount ?> <span class="summary-unit">transaksi</span></p>
                        <p class="summary-caption">Bulan berjalan</p>
                    </article>
                </section>
                <section class="history-section" aria-labelledby="history-title">
                    <div class="section-heading">
                        <div>
                            <h2 id="history-title">Riwayat Pengeluaran</h2>
                            <p>Seluruh riwayat, dari yang terbaru.</p>
                        </div><span class="history-count"><?= count($transactions) ?> transaksi</span>
                    </div>
                    <?php if ($transactions): ?><div class="table-wrap">
                            <table class="history-table">
                                <caption class="visually-hidden">Riwayat Pengeluaran</caption>
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th>Catatan</th>
                                        <th>Nominal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $index => $row): $timestamp = strtotime($row['transaction_date']);
                                        $dateLabel = date('j', $timestamp) . ' ' . $monthNames[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
                                        $noteAttribute = str_replace(["\r", "\n"], ['&#13;', '&#10;'], htmlspecialchars($row['note'] ?? '', ENT_QUOTES, 'UTF-8'));
                                        $descriptionAttribute = str_replace(["\r", "\n"], ['&#13;', '&#10;'], htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8')); ?>
                                        <tr data-id="<?= htmlspecialchars((string) $row['id'], ENT_QUOTES, 'UTF-8') ?>" data-description="<?= $descriptionAttribute ?>" data-date="<?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?>" data-note="<?= $noteAttribute ?>" data-amount="<?= htmlspecialchars((string) $row['amount'], ENT_QUOTES, 'UTF-8') ?>">
                                            <td data-label="No"><?= $index + 1 ?></td>
                                            <td data-label="Tanggal"><?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td data-label="Keterangan"><span class="transaction-name"><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td data-label="Catatan"><span class="transaction-note"><?= $row['note'] !== null && $row['note'] !== '' ? nl2br(htmlspecialchars($row['note'], ENT_QUOTES, 'UTF-8')) : '—' ?></span></td>
                                            <td data-label="Nominal" class="amount-cell"><?= format_rupiah((int) $row['amount']) ?></td>
                                            <td data-label="Aksi" class="action-cell">
                                                <details class="row-actions">
                                                    <summary aria-label="Aksi untuk <?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?>"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                            <circle cx="5" cy="12" r="1.7" />
                                                            <circle cx="12" cy="12" r="1.7" />
                                                            <circle cx="19" cy="12" r="1.7" />
                                                        </svg></summary>
                                                    <div class="action-menu"><button type="button" data-action="detail">Lihat detail</button><button type="button" data-action="edit">Edit</button><button type="button" data-action="delete">Hapus</button></div>
                                                </details>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div><?php else: ?><div class="empty-state">
                            <h3>Belum ada riwayat pengeluaran</h3>
                            <p>Transaksi akan muncul di sini setelah ditambahkan.</p>
                        </div><?php endif; ?>
                </section>
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
        <a class="bottom-nav-item is-active" href="expense.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
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

    <dialog class="modal" id="add-dialog" aria-labelledby="add-title">
        <div class="modal-head">
            <div>
                <h2 id="add-title">Tambah Pengeluaran</h2>
            </div><button class="icon-button" data-close-dialog type="button" aria-label="Tutup dialog"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg></button>
        </div>
        <p class="modal-description">Tanggal dan jenis transaksi ditentukan otomatis.</p>
        <form id="add-form" action="../../process/expense.php" method="post" novalidate><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="add">
            <div class="error-summary" id="add-summary" role="alert" tabindex="-1" hidden><strong>Periksa kembali isian berikut:</strong>
                <ul id="add-summary-list"></ul>
            </div>
            <div class="field-group"><label for="add-description">Keterangan</label><input id="add-description" name="description" type="text" maxlength="100" aria-describedby="add-description-error" aria-invalid="false">
                <p class="field-error" id="add-description-error" aria-live="polite"></p>
            </div>
            <div class="field-group"><label for="add-amount">Nominal</label>
                <div class="money-input"><span aria-hidden="true">Rp</span><input id="add-amount" name="amount" type="text" inputmode="numeric" aria-describedby="add-amount-help add-amount-error" aria-invalid="false"></div>
                <p class="field-hint" id="add-amount-help">Angka bulat lebih dari 0, tanpa pemisah.</p>
                <p class="field-error" id="add-amount-error" aria-live="polite"></p>
            </div>
            <div class="field-group"><label for="add-note">Catatan <span class="optional-label">Opsional</span></label><textarea id="add-note" name="note" maxlength="500" rows="3" aria-describedby="add-note-error" aria-invalid="false"></textarea>
                <p class="field-error" id="add-note-error" aria-live="polite"></p>
            </div>
            <p class="form-feedback" id="add-feedback" role="status"></p>
            <div class="modal-actions"><button class="button button-secondary" data-close-dialog type="button">Batal</button><button class="button button-primary" type="submit">Simpan Pengeluaran</button></div>
        </form>
    </dialog>
    <dialog class="modal" id="edit-dialog" aria-labelledby="edit-title">
        <div class="modal-head">
            <div>
                <h2 id="edit-title">Edit Pengeluaran</h2>
            </div><button class="icon-button" data-close-dialog type="button" aria-label="Tutup dialog"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg></button>
        </div>
        <p class="modal-description">Tanggal dan jenis transaksi ditentukan otomatis.</p>
        <form id="edit-form" action="../../process/expense.php" method="post" novalidate><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="edit"><input type="hidden" name="transaction_id" id="edit-transaction-id">
            <div class="error-summary" id="edit-summary" role="alert" tabindex="-1" hidden><strong>Periksa kembali isian berikut:</strong>
                <ul id="edit-summary-list"></ul>
            </div>
            <div class="field-group"><label for="edit-description">Keterangan</label><input id="edit-description" name="description" type="text" maxlength="100" aria-describedby="edit-description-error" aria-invalid="false">
                <p class="field-error" id="edit-description-error" aria-live="polite"></p>
            </div>
            <div class="field-group"><label for="edit-amount">Nominal</label>
                <div class="money-input"><span aria-hidden="true">Rp</span><input id="edit-amount" name="amount" type="text" inputmode="numeric" aria-describedby="edit-amount-help edit-amount-error" aria-invalid="false"></div>
                <p class="field-hint" id="edit-amount-help">Angka bulat lebih dari 0, tanpa pemisah.</p>
                <p class="field-error" id="edit-amount-error" aria-live="polite"></p>
            </div>
            <div class="field-group"><label for="edit-note">Catatan <span class="optional-label">Opsional</span></label><textarea id="edit-note" name="note" maxlength="500" rows="3" aria-describedby="edit-note-error" aria-invalid="false"></textarea>
                <p class="field-error" id="edit-note-error" aria-live="polite"></p>
            </div>
            <p class="form-feedback" id="edit-feedback" role="status"></p>
            <div class="modal-actions"><button class="button button-secondary" data-close-dialog type="button">Batal</button><button class="button button-primary" type="submit">Simpan Pengeluaran</button></div>
        </form>
    </dialog>
    <dialog class="modal" id="detail-dialog" aria-labelledby="detail-title">
        <div class="modal-head">
            <h2 id="detail-title">Detail Pengeluaran</h2><button class="icon-button" id="close-detail-dialog" data-close-dialog type="button" aria-label="Tutup dialog">×</button>
        </div>
        <dl class="detail-list">
            <div>
                <dt>Tanggal</dt>
                <dd id="detail-date">—</dd>
            </div>
            <div>
                <dt>Keterangan</dt>
                <dd id="detail-description">—</dd>
            </div>
            <div>
                <dt>Nominal</dt>
                <dd class="detail-amount" id="detail-amount">—</dd>
            </div>
            <div>
                <dt>Catatan</dt>
                <dd id="detail-note">—</dd>
            </div>
            <div>
                <dt>Jenis</dt>
                <dd>Pengeluaran</dd>
            </div>
        </dl>
        <div class="modal-actions"><button class="button button-secondary" data-close-dialog type="button">Tutup</button></div>
    </dialog>
    <dialog class="modal modal-compact" id="delete-dialog" aria-labelledby="delete-title" aria-describedby="delete-description">
        <div class="modal-head">
            <h2 id="delete-title">Hapus Pengeluaran?</h2><button class="icon-button" data-close-dialog type="button" aria-label="Tutup dialog"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                </svg></button>
        </div>
        <p class="modal-description" id="delete-description">Transaksi <strong id="delete-transaction-name"></strong> akan dihapus secara permanen.</p>
        <form id="delete-form" action="../../process/expense.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="transaction_id" id="delete-transaction-id">
            <div class="modal-actions"><button class="button button-secondary" id="cancel-delete-dialog" data-close-dialog type="button">Batal</button><button class="button button-danger" type="submit">Hapus Pengeluaran</button></div>
        </form>
    </dialog>
</body>

</html>