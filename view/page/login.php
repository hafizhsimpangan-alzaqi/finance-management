<?php
require_once __DIR__ . '/../../process/helpers.php';
if (!empty($_SESSION['user_id'])) { 
    header('Location: dashboard.php'); 
    exit; 
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8faff">
    <title>Masuk — Finance Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">
    <script src="../js/login.js" defer></script>
</head>
<body>
    <main class="auth-layout">
        <aside class="brand-panel" aria-label="Tentang Finance Management">
            <a class="brand" href="login.php" aria-label="Finance Management, halaman masuk">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none"><path d="M5 23.5 12.2 16l4.7 4.7L27 9.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.5 9.5H27v7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>Finance <strong>Management</strong></span>
            </a>

            <div class="brand-copy">
                <span class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> Keuangan lebih tertata</span>
                <h1>Kenali arah uangmu.<br><span>Rencanakan langkahmu.</span></h1>
                <p>Catat pemasukan dan pengeluaran dengan lebih mudah, lalu pahami kondisi keuanganmu dari waktu ke waktu.</p>
            </div>

            <div class="finance-art" aria-hidden="true">
                <div class="art-orbit art-orbit-one"></div>
                <div class="art-orbit art-orbit-two"></div>
                <div class="mini-card">
                    <div class="mini-card-head"><span>Ringkasan keuangan</span><span class="mini-dots">•••</span></div>
                    <div class="mini-card-label">Perjalanan finansialmu</div>
                    <div class="chart-area">
                        <span class="chart-grid chart-grid-one"></span><span class="chart-grid chart-grid-two"></span>
                        <svg viewBox="0 0 360 112" preserveAspectRatio="none"><defs><linearGradient id="chartFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#93c5fd" stop-opacity=".4"/><stop offset="1" stop-color="#93c5fd" stop-opacity="0"/></linearGradient></defs><path d="M0 88C28 82 32 61 62 68s39 22 67 4 36-4 59-23 38 18 64-1 41-20 56-12 32-17 52-26v102H0Z" fill="url(#chartFill)"/><path d="M0 88C28 82 32 61 62 68s39 22 67 4 36-4 59-23 38 18 64-1 41-20 56-12 32-17 52-26" fill="none" stroke="#bfdbfe" stroke-width="3" stroke-linecap="round"/></svg>
                        <span class="chart-point"></span>
                    </div>
                    <div class="chart-months"><span>Awal</span><span>Proses</span><span>Tujuan</span></div>
                </div>
                <div class="floating-note"><span class="note-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M5 12.5 9.2 17 19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span>Mulai dari langkah kecil</span></div>
            </div>
            <p class="brand-footer">Kelola dengan sadar. Melangkah dengan tenang.</p>
        </aside>

        <section class="form-panel" aria-labelledby="form-title">
            <div class="form-wrap">
                <a class="mobile-brand" href="login.php" aria-label="Finance Management, halaman masuk">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M5 23.5 12.2 16l4.7 4.7L27 9.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.5 9.5H27v7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span>Finance <strong>Management</strong></span>
                </a>
                <div class="form-heading">
                    <span class="form-kicker">Selamat datang kembali</span>
                    <h2 id="form-title">Masuk ke Akun Anda</h2>
                    <p>Masukkan username atau email dan password untuk melanjutkan.</p>
                </div>

                <form id="login-form" action="../../process/login.php" method="post" novalidate>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($flash): ?><p class="form-status" role="alert"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <div id="error-summary" class="error-summary" role="alert" tabindex="-1" aria-labelledby="error-title" hidden>
                        <h3 id="error-title">Periksa kembali isian Anda</h3>
                        <p id="summary-message"></p>
                        <ul id="summary-links"></ul>
                    </div>
                    <div class="field-group">
                        <label for="identity">Username atau Email</label>
                        <input id="identity" name="identity" type="text" value="<?= htmlspecialchars(is_string($_COOKIE['remember_username'] ?? null) ? $_COOKIE['remember_username'] : '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" autocapitalize="none" spellcheck="false" required aria-describedby="identity-error" aria-invalid="false">
                        <p id="identity-error" class="field-error" aria-live="polite"></p>
                    </div>
                    <div class="field-group">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input id="password" name="password" type="password" autocomplete="current-password" required aria-describedby="password-error" aria-invalid="false">
                            <button class="password-toggle" id="toggle-password" type="button" aria-label="Tampilkan password" aria-pressed="false">
                                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.7"/></svg>
                            </button>
                        </div>
                        <p id="password-error" class="field-error" aria-live="polite"></p>
                    </div>
                    <div class="form-options">
                        <label class="remember-option" for="remember-me"><input id="remember-me" name="remember_me" type="checkbox" value="1"><span class="custom-check" aria-hidden="true"></span><span>Ingat Saya</span></label>
                    </div>
                    <button class="submit-button" type="submit">Masuk <span aria-hidden="true">→</span></button>
                    <p id="form-status" class="form-status" role="status" aria-live="polite"></p>
                </form>
                <p class="form-switch">Belum memiliki akun? <a href="register.php">Daftar sekarang</a></p>
            </div>
        </section>
    </main>
</body>
</html>
