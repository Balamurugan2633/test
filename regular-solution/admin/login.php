<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

if (rs_admin() !== null) {
    rs_redirect('index.php');
}

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rs_csrf_check();
    $username = rs_post('username');
    $password = (string) ($_POST['password'] ?? '');

    if (rs_login_locked()) {
        $error = 'Too many failed attempts. For your security, sign-in is paused for 15 minutes.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } elseif (rs_login_attempt($username, $password)) {
        rs_redirect('index.php');
    } else {
        $error = 'That username and password don\'t match. Please try again.';
    }
}

$logo = rs_settings()['logo'];
rs_admin_head('Sign in');
?>
<body>
<div class="auth">
    <section class="auth-brand" aria-hidden="true">
        <div class="auth-logo">
            <span><img src="../<?= e($logo) ?>" alt=""></span>
            Regular Solution
        </div>

        <div>
            <h2>Run your whole business from <em>one place.</em></h2>
            <p>Track every enquiry, publish fresh content and keep your website up to date — without touching a line of code.</p>
            <ul class="auth-features">
                <li><i class="bi bi-inbox"></i> Every booking request, the moment it arrives</li>
                <li><i class="bi bi-journal-richtext"></i> Write and publish blogs in minutes</li>
                <li><i class="bi bi-sliders"></i> Update phone, logo and footer site-wide</li>
            </ul>
        </div>

        <footer>&copy; <?= date('Y') ?> Regular Solution · Admin Console</footer>
    </section>

    <section class="auth-form">
        <div class="auth-card fade-up">
            <h1>Welcome back</h1>
            <p class="lead-x">Sign in to your admin console to continue.</p>

            <?php if ($error !== ''): ?>
                <div class="auth-alert" role="alert"><i class="bi bi-exclamation-circle-fill"></i><span><?= e($error) ?></span></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= rs_csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input class="form-control" type="text" id="username" name="username" value="<?= e($username) ?>"
                               autocomplete="username" placeholder="Enter your username" required <?= $username === '' ? 'autofocus' : '' ?>>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input class="form-control" type="password" id="password" name="password" autocomplete="current-password"
                               placeholder="Enter your password" required <?= $username !== '' ? 'autofocus' : '' ?>>
                        <button class="reveal" type="button" id="togglePassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <button class="btn btn-primary btn-lg" type="submit" id="signIn">
                    Sign in <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="secure-note"><i class="bi bi-shield-lock-fill"></i> Protected by encrypted sessions and login throttling</div>

            <div class="auth-foot">
                <a href="../index.html"><i class="bi bi-arrow-left"></i> Back to website</a>
                <button class="icon-btn" type="button" data-theme-toggle aria-label="Switch light / dark theme">
                    <i class="bi bi-moon-stars theme-light-icon"></i><i class="bi bi-sun theme-dark-icon"></i>
                </button>
            </div>
        </div>
    </section>
</div>
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        var input = document.getElementById('password');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
    document.querySelector('form').addEventListener('submit', function () {
        var b = document.getElementById('signIn');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Signing in…';
    });
    document.querySelector('[data-theme-toggle]').addEventListener('click', function () {
        var r = document.documentElement, next = r.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        r.setAttribute('data-theme', next); r.setAttribute('data-bs-theme', next);
        try { localStorage.setItem('rs-admin-theme', next); } catch (e) {}
    });
</script>
</body>
</html>
