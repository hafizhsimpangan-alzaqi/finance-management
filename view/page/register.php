<?php
require_once __DIR__ . '/../../process/helpers.php';
if (!empty($_SESSION['user_id'])) { header('Location: dashboard.php'); exit; }
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8faff">
    <title>Buat Akun — Finance Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/register.css">
    <script src="../js/register.js" defer></script>
</head>
<body>
    <main class="auth-layout">
        <aside class="brand-panel" aria-label="Tentang Finance Management">
            <a class="brand" href="login.php" aria-label="Finance Management, halaman masuk">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M5 23.5 12.2 16l4.7 4.7L27 9.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.5 9.5H27v7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>Finance <strong>Management</strong></span>
            </a>
            <div class="brand-copy">
                <span class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> Awali kebiasaan baik</span>
                <h1>Bangun hubungan<br><span>yang lebih sehat</span><br>dengan uang.</h1>
                <p>Satu tempat sederhana untuk mulai mencatat, memahami, dan mengelola keuangan pribadimu.</p>
            </div>
            <div class="finance-art" aria-hidden="true">
                <div class="art-orbit art-orbit-one"></div><div class="art-orbit art-orbit-two"></div>
                <div class="mini-card">
                    <div class="mini-card-head"><span>Langkah pertamamu</span><span class="mini-dots">•••</span></div>
                    <div class="progress-title">Mulai dengan mencatat</div>
                    <div class="progress-track"><span></span></div>
                    <div class="progress-caption"><span>Perjalanan dimulai dari sini</span><span class="progress-check" aria-hidden="true"><svg viewBox="0 0 20 20" fill="none"><path d="m4 10 4 4 8-8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span></div>
                    <div class="mini-bars"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
                </div>
                <div class="floating-note"><span class="note-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M12 3v18M3 12h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span><span>Mulai dengan sederhana</span></div>
            </div>
            <p class="brand-footer">Kebiasaan kecil hari ini, langkah baik esok hari.</p>
        </aside>

        <section class="form-panel" aria-labelledby="form-title">
            <div class="form-wrap">
                <a class="mobile-brand" href="login.php" aria-label="Finance Management, halaman masuk">
                    <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none"><path d="M5 23.5 12.2 16l4.7 4.7L27 9.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M19.5 9.5H27v7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                    <span>Finance <strong>Management</strong></span>
                </a>
                <div class="form-heading">
                    <span class="form-kicker">Mulai perjalananmu</span>
                    <h2 id="form-title">Buat Akun Baru</h2>
                    <p>Isi data berikut untuk menyiapkan akun Finance Management.</p>
                </div>
                <form id="register-form" action="../../process/register.php" method="post" novalidate>
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if ($flash): ?><p class="form-status" role="alert"><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                    <div id="error-summary" class="error-summary" role="alert" tabindex="-1" aria-labelledby="error-title" hidden>
                        <h3 id="error-title">Periksa kembali isian Anda</h3>
                        <ul id="summary-links"></ul>
                    </div>
                    <div class="field-group">
                        <label for="username">Username</label>
                        <input id="username" name="username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="50" required aria-describedby="username-hint username-error" aria-invalid="false">
                        <p id="username-hint" class="field-hint">Maksimal 50 karakter.</p>
                        <p id="username-error" class="field-error" aria-live="polite"></p>
                    </div>
                    <div class="field-group">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" autocomplete="email" autocapitalize="none" spellcheck="false" required aria-describedby="email-error" aria-invalid="false">
                        <p id="email-error" class="field-error" aria-live="polite"></p>
                    </div>
                    <div class="field-group">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required aria-describedby="password-hint password-error" aria-invalid="false">
                            <button class="password-toggle" id="toggle-password" type="button" aria-label="Tampilkan password" aria-pressed="false">
                                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="1.7"/></svg>
                            </button>
                        </div>
                        <p id="password-hint" class="field-hint">Gunakan minimal 8 karakter.</p>
                        <p id="password-error" class="field-error" aria-live="polite"></p>
                    </div>
                    <button class="submit-button" type="submit">Daftar <span aria-hidden="true">→</span></button>
                    <p id="form-status" class="form-status" role="status" aria-live="polite"></p>
                </form>
                <p class="form-switch">Sudah memiliki akun? <a href="login.php">Masuk di sini</a></p>
            </div>
        </section>
    </main>
</body>
</html>
