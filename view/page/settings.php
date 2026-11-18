<?php
require_once __DIR__ . '/../../process/guard.php';
$errorPage = false;
require_once __DIR__ . '/../../database/connection.php';

/** @var mysqli $connection */

$query = mysqli_prepare($connection, 'SELECT username, email, opening_balance FROM users WHERE id = ?');

if (!$query || !mysqli_stmt_bind_param($query, 's', $_SESSION['user_id']) || !mysqli_stmt_execute($query) || !($result = mysqli_stmt_get_result($query)) || !($user = mysqli_fetch_assoc($result))) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}
mysqli_stmt_close($query);
mysqli_close($connection);

$username = $user['username'];
$email = $user['email'];
$openingBalance = $user['opening_balance'] === null ? null : nominal_integer((string) $user['opening_balance']);
if ($user['opening_balance'] !== null && $openingBalance === null) {
    http_response_code(503);
    exit('Terjadi kesalahan sistem. Silakan coba lagi.');
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8faff">
    <title>Pengaturan Akun — Finance Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/settings.css">
    <script src="../js/settings.js" defer></script>
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
                <a class="nav-item" href="report.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M5 4.5h14v15H5zM8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                    </svg><span>Laporan</span></a>
                <a class="nav-item is-active" href="settings.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
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
                    <h1>Pengaturan Akun</h1>
                </div>
                <div class="profile"><span class="avatar" aria-hidden="true">F</span><span class="profile-name"><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></span></div>
            </header>
            <main class="page-content">
                <form class="mobile-logout" action="../../process/logout.php" method="post"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><button type="submit">Keluar</button></form>
                <section class="page-heading" aria-labelledby="page-title"><span class="heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7" />
                            <path d="m19.4 15 .1.1 1.1.8-1.4 2.4-1.3-.6a7.8 7.8 0 0 1-1.4.8l-.2 1.4h-2.8l-.2-1.4a7.8 7.8 0 0 1-1.4-.8l-1.3.6-1.4-2.4 1.1-.8a7 7 0 0 1 0-1.6l-1.1-.8 1.4-2.4 1.3.6a7.8 7.8 0 0 1 1.4-.8l.2-1.4h2.8l.2 1.4a7.8 7.8 0 0 1 1.4.8l1.3-.6 1.4 2.4-1.1.8a7 7 0 0 1 0 1.6Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
                        </svg></span>
                    <div>
                        <p class="welcome-kicker">AKUN DAN KEUANGAN</p>
                        <h2 id="page-title">Pengaturan Akun</h2>
                        <p>Kelola informasi akun, keamanan, dan saldo awal keuangan Anda.</p>
                    </div>
                </section>
                <?php if (isset($_SESSION['flash'])): ?><p class="page-notice" id="settings-notice" role="status"><?= htmlspecialchars($_SESSION['flash']['message'], ENT_QUOTES, 'UTF-8') ?></p><?php unset($_SESSION['flash']);
                                                                                                                                                                                                endif; ?>

                <div class="settings-grid">
                    <section class="settings-card account-card" aria-labelledby="account-title">
                        <div class="card-heading"><span class="card-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7" />
                                    <path d="M5.5 19c.6-3.1 2.9-4.8 6.5-4.8s5.9 1.7 6.5 4.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                </svg></span>
                            <div>
                                <h2 id="account-title">Informasi Akun</h2>
                                <p>Perbarui informasi akun yang digunakan untuk mengakses Finance Management.</p>
                            </div>
                        </div>
                        <form id="username-form" action="../../process/settings.php" method="post" novalidate>
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="update_username">
                            <div class="error-summary" id="username-summary" role="alert" tabindex="-1" aria-labelledby="username-summary-title" hidden><strong id="username-summary-title">Periksa kembali isian berikut:</strong>
                                <ul id="username-summary-list"></ul>
                            </div>
                            <div class="account-fields">
                                <div class="field-group"><label for="username">Username</label><input id="username" name="username" type="text" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>" maxlength="50" autocomplete="username" required aria-describedby="username-hint username-error" aria-invalid="false">
                                    <p class="field-hint" id="username-hint">Gunakan username unik, maksimal 50 karakter.</p>
                                    <p class="field-error" id="username-error" aria-live="polite"></p>
                                </div>
                                <div class="field-group"><label for="email">Email</label><input id="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" readonly aria-describedby="email-help">
                                    <p class="field-hint" id="email-help">Alamat email tidak dapat diubah melalui aplikasi.</p>
                                </div>
                            </div>
                            <p class="form-feedback" id="username-feedback" role="status" aria-live="polite"></p>
                            <div class="form-actions"><button class="button button-primary" type="submit">Simpan Username</button></div>
                        </form>
                    </section>

                    <section class="settings-card" aria-labelledby="security-title">
                        <div class="card-heading"><span class="card-icon icon-security" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.7" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
                                </svg></span>
                            <div>
                                <h2 id="security-title">Keamanan Akun</h2>
                                <p>Perbarui password secara berkala untuk menjaga keamanan akun.</p>
                            </div>
                        </div>
                        <p class="policy-note">Setelah perubahan, sesi perangkat ini tetap aktif. Sesi pada perangkat lain juga tidak otomatis diakhiri.</p>
                        <form id="password-form" action="../../process/settings.php" method="post" novalidate>
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="update_password">
                            <div class="error-summary" id="password-summary" role="alert" tabindex="-1" aria-labelledby="password-summary-title" hidden><strong id="password-summary-title">Periksa kembali isian berikut:</strong>
                                <ul id="password-summary-list"></ul>
                            </div>
                            <div class="field-group"><label for="current-password">Password Lama</label>
                                <div class="password-control"><input id="current-password" name="old_password" type="password" autocomplete="current-password" required aria-describedby="current-password-error" aria-invalid="false"><button class="password-toggle" type="button" data-password-toggle="current-password" aria-controls="current-password" aria-label="Tampilkan password lama" aria-pressed="false">Tampilkan</button></div>
                                <p class="field-error" id="current-password-error" aria-live="polite"></p>
                            </div>
                            <div class="field-group"><label for="new-password">Password Baru</label>
                                <div class="password-control"><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="8" required aria-describedby="password-hint new-password-error" aria-invalid="false"><button class="password-toggle" type="button" data-password-toggle="new-password" aria-controls="new-password" aria-label="Tampilkan password baru" aria-pressed="false">Tampilkan</button></div>
                                <p class="field-hint" id="password-hint">Gunakan minimal 8 karakter.</p>
                                <p class="field-error" id="new-password-error" aria-live="polite"></p>
                            </div>
                            <p class="form-feedback" id="password-feedback" role="status" aria-live="polite"></p>
                            <div class="form-actions"><button class="button button-primary" type="submit">Ubah Password</button></div>
                        </form>
                    </section>

                    <section class="settings-card balance-card" aria-labelledby="balance-title">
                        <div class="card-heading"><span class="card-icon icon-balance" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 7.5h16v12H4z" stroke="currentColor" stroke-width="1.7" />
                                    <path d="M4 8V5.5L18 4v3.5M15 13.5h2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                                </svg></span>
                            <div>
                                <h2 id="balance-title">Saldo Awal Keuangan</h2>
                                <p>Atur saldo awal sebagai dasar perhitungan keuangan pribadi Anda.</p>
                            </div>
                        </div>
                        <div class="balance-current" aria-label="Saldo awal saat ini"><span>Status saat ini</span><strong><?= $openingBalance === null ? 'Belum ditetapkan' : format_rupiah($openingBalance) ?></strong><small><?= $openingBalance === null ? 'Status saldo belum ditetapkan' : 'Nominal saldo saat ini' ?></small></div>
                        <p class="balance-explanation">Saldo awal disimpan terpisah dari pemasukan dan menjadi dasar perhitungan saldo. Perubahan saldo akhir akan diperiksa oleh PHP.</p>
                        <?php if ($openingBalance === null): ?><p class="balance-explanation">Tetapkan saldo awal terlebih dahulu melalui <a href="dashboard.php">Dashboard</a>.</p><?php else: ?>
                            <form id="balance-form" action="../../process/balance.php" method="post" novalidate>
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="source" value="settings">

                                <div class="error-summary" id="balance-summary" role="alert" tabindex="-1" aria-labelledby="balance-summary-title" hidden><strong id="balance-summary-title">Periksa kembali isian berikut:</strong>
                                    <ul id="balance-summary-list"></ul>
                                </div>
                                <div class="field-group"><label for="opening-balance">Saldo Awal</label>
                                    <div class="money-control"><span aria-hidden="true">Rp</span><input id="opening-balance" name="opening_balance" type="text" value="<?= $openingBalance ?>" inputmode="numeric" autocomplete="off" required aria-describedby="balance-hint opening-balance-error" aria-invalid="false"></div>
                                    <p class="field-hint" id="balance-hint">Masukkan angka bulat tanpa pemisah. Rp0 diperbolehkan.</p>
                                    <p class="field-error" id="opening-balance-error" aria-live="polite"></p>
                                    <p class="balance-preview" id="balance-preview" aria-live="polite">Pratinjau tampilan: <?= format_rupiah($openingBalance) ?></p>
                                </div>
                                <p class="form-feedback" id="balance-feedback" role="status" aria-live="polite"></p>
                                <div class="form-actions"><button class="button button-primary" type="submit">Simpan Saldo Awal</button></div>
                            </form>
                        <?php endif; ?>
                    </section>
                </div>
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
        <a class="bottom-nav-item" href="report.php"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M5 4.5h14v15H5zM8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
            </svg><span>Laporan</span></a>
        <a class="bottom-nav-item is-active" href="settings.php" aria-current="page"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7" />
                <path d="m19.4 15 .1.1 1.1.8-1.4 2.4-1.3-.6a7.8 7.8 0 0 1-1.4.8l-.2 1.4h-2.8l-.2-1.4a7.8 7.8 0 0 1-1.4-.8l-1.3.6-1.4-2.4 1.1-.8a7 7 0 0 1 0-1.6l-1.1-.8 1.4-2.4 1.3.6a7.8 7.8 0 0 1 1.4-.8l.2-1.4h2.8l.2 1.4a7.8 7.8 0 0 1 1.4.8l1.3-.6 1.4 2.4-1.1.8a7 7 0 0 1 0 1.6Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
            </svg><span>Pengaturan</span></a>
    </nav>
</body>

</html>