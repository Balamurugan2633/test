<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$admin  = rs_require_admin();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rs_csrf_check();
    $current = (string) ($_POST['current'] ?? '');
    $new     = (string) ($_POST['new'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');

    $stmt = rs_db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $stmt->execute([$admin['id']]);
    $hash = (string) $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $errors['current'] = 'Your current password is incorrect.';
    }
    if (strlen($new) < 8) {
        $errors['new'] = 'Use at least 8 characters.';
    } elseif (password_verify($new, $hash)) {
        $errors['new'] = 'Choose a password different from the current one.';
    }
    if ($new !== $confirm) {
        $errors['confirm'] = 'The two passwords do not match.';
    }

    if (!$errors) {
        rs_db()->prepare('UPDATE admins SET password_hash = ?, is_default = 0 WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
        session_regenerate_id(true);
        rs_log('admin.log', 'password changed user=' . $admin['username']);
        rs_flash('success', 'Your password was changed. Use the new one next time you sign in.');
        rs_redirect('password.php');
    }
}

rs_admin_header('Security', 'password', $admin, ['Configuration' => 'settings.php']);

$field = function (string $name, string $label, string $autocomplete, string $extra = '') use ($errors) {
    $bad = isset($errors[$name]);
    echo '<div class="mb-3"><label class="form-label" for="' . $name . '">' . $label . '</label>'
       . '<div class="input-icon"><i class="bi bi-lock"></i>'
       . '<input class="form-control' . ($bad ? ' is-invalid' : '') . '" type="password" id="' . $name . '" name="' . $name . '" autocomplete="' . $autocomplete . '" required>'
       . '<button class="reveal" type="button" data-reveal="' . $name . '" aria-label="Show password"><i class="bi bi-eye"></i></button></div>'
       . $extra
       . ($bad ? '<div class="invalid-feedback d-block">' . e($errors[$name]) . '</div>' : '')
       . '</div>';
};
?>
<div class="page-head fade-up">
    <div>
        <h1>Security</h1>
        <p>Keep your admin console safe with a strong, unique password.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <form class="card-x fade-up d1" method="post" novalidate>
            <?= rs_csrf_field() ?>
            <div class="card-x-head"><div><h2>Change password</h2><p>You'll stay signed in on this device.</p></div></div>
            <div class="card-x-body">
                <?php $field('current', 'Current password', 'current-password'); ?>
                <?php $field('new', 'New password', 'new-password', '<div class="strength" id="strength" data-level="0"><span></span><span></span><span></span><span></span></div><div class="form-hint" id="strengthText">Use 8+ characters with a mix of letters, numbers and symbols.</div>'); ?>
                <?php $field('confirm', 'Confirm new password', 'new-password'); ?>
            </div>
            <div class="card-x-foot d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-shield-check"></i> Update password</button>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card-x fade-up d2">
            <div class="card-x-head"><h2>Account</h2></div>
            <div class="card-x-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <?= rs_avatar($admin['username'], 'lg') ?>
                    <div>
                        <div class="fw-bold fs-5"><?= e(ucfirst($admin['username'])) ?></div>
                        <div class="text-3 small">Administrator</div>
                    </div>
                </div>
                <ul class="timeline">
                    <li><strong>Last sign-in</strong><?= $admin['last_login_at'] ? e(date('d M Y, h:i A', strtotime($admin['last_login_at']))) : 'This is your first session' ?></li>
                    <li><strong>Password</strong><?= $admin['is_default'] ? '<span class="pill pill-new"><i></i>Default — please change</span>' : '<span class="pill pill-completed"><i></i>Custom password set</span>' ?></li>
                </ul>
            </div>
            <div class="card-x-foot small text-3">
                <i class="bi bi-info-circle"></i> After 5 wrong passwords, sign-in pauses for 15 minutes. You're signed out after <?= (int) rs_config()['admin_idle_minutes'] ?> minutes of inactivity.
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-reveal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-reveal'));
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });
    var pw = document.getElementById('new'), meter = document.getElementById('strength'), text = document.getElementById('strengthText');
    var labels = ['Use 8+ characters with a mix of letters, numbers and symbols.', 'Weak — add more characters.', 'Fair — add numbers or symbols.', 'Good password.', 'Strong password. Nice!'];
    pw.addEventListener('input', function () {
        var v = pw.value, s = 0;
        if (v.length >= 8) s++;
        if (v.length >= 12) s++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
        if (/\d/.test(v) && /[^A-Za-z0-9]/.test(v)) s++;
        if (v.length > 0 && s === 0) s = 1;
        meter.setAttribute('data-level', String(s));
        text.textContent = labels[s];
    });
})();
</script>
<?php
rs_admin_footer();
