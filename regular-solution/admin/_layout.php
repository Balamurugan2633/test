<?php
/**
 * Admin page chrome.
 *   rs_admin_header('Page title', 'menu-key', $admin, ['Parent' => 'url']);
 *   ...page content...
 *   rs_admin_footer();
 */

declare(strict_types=1);

require_once __DIR__ . '/../api/site-settings.php';
require_once __DIR__ . '/_ui.php';

function rs_admin_head(string $title): void
{
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> · Regular Solution Admin</title>
    <script>
        // Apply the saved theme before anything paints, so there is no flash.
        (function () {
            var t = null;
            try { t = localStorage.getItem('rs-admin-theme'); } catch (e) {}
            if (!t) { t = window.matchMedia && matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; }
            document.documentElement.setAttribute('data-theme', t);
            document.documentElement.setAttribute('data-bs-theme', t);
        })();
    </script>
    <link rel="icon" href="../images/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/admin.css?v=3" rel="stylesheet">
</head>
<?php
}

function rs_admin_header(string $title, string $active, array $admin, array $crumbs = []): void
{
    $counts   = rs_booking_counts();
    $newCount = $counts['new'];
    $latest   = rs_bookings('new', '', 5);
    $logo     = rs_settings()['logo'];
    $flash    = rs_take_flash();

    $menu = [
        'Overview' => [
            'dashboard' => ['index.php', 'bi-grid-1x2', 'Dashboard'],
        ],
        'Customers' => [
            'bookings'    => ['bookings.php', 'bi-inbox', 'Booking Requests'],
            'subscribers' => ['subscribers.php', 'bi-envelope-paper', 'Subscribers'],
        ],
        'Content' => [
            'blogs' => ['blogs.php', 'bi-journal-richtext', 'All Blogs'],
            'new'   => ['blog-form.php', 'bi-plus-square', 'Write New Blog'],
        ],
        'Configuration' => [
            'settings' => ['settings.php', 'bi-sliders', 'Website Settings'],
            'password' => ['password.php', 'bi-shield-lock', 'Security'],
        ],
    ];

    rs_admin_head($title);
    ?>
<body>
<div class="app">

<aside class="sidebar" id="sidebar" aria-label="Admin navigation">
    <a class="sidebar-brand" href="index.php">
        <span class="logo-chip"><img src="../<?= e($logo) ?>" alt=""></span>
        <span><strong>Regular Solution</strong><small>Admin Console</small></span>
    </a>

    <nav class="sidebar-nav">
        <?php foreach ($menu as $section => $items): ?>
            <div class="nav-section"><?= e($section) ?></div>
            <?php foreach ($items as $key => [$href, $icon, $label]): ?>
                <a class="nav-item-x <?= $key === $active ? 'active' : '' ?>" href="<?= $href ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>>
                    <i class="bi <?= $icon ?>" aria-hidden="true"></i><span><?= e($label) ?></span>
                    <?php if ($key === 'bookings' && $newCount > 0): ?>
                        <span class="nav-count"><?= $newCount ?><span class="visually-hidden"> new</span></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <div class="nav-section">Website</div>
        <a class="nav-item-x" href="../index.html" target="_blank" rel="noopener"><i class="bi bi-globe2" aria-hidden="true"></i><span>Open Website</span><i class="bi bi-arrow-up-right ms-auto small" aria-hidden="true"></i></a>
    </nav>

    <div class="sidebar-foot">
        <div class="sidebar-user">
            <?= rs_avatar($admin['username'], 'sm') ?>
            <div class="who"><strong class="text-truncate"><?= e(ucfirst($admin['username'])) ?></strong><small>Administrator</small></div>
            <form method="post" action="logout.php">
                <?= rs_csrf_field() ?>
                <button type="submit" title="Sign out" aria-label="Sign out"><i class="bi bi-box-arrow-right"></i></button>
            </form>
        </div>
    </div>
</aside>
<div class="backdrop" data-nav-toggle></div>

<div class="main">
    <header class="topbar">
        <button class="icon-btn d-lg-none" type="button" data-nav-toggle aria-controls="sidebar" aria-label="Open menu"><i class="bi bi-list"></i></button>

        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="index.php" class="d-none d-sm-inline"><i class="bi bi-house-door"></i></a>
            <?php foreach ($crumbs as $label => $url): ?>
                <i class="bi bi-chevron-right small d-none d-sm-inline"></i><a href="<?= e($url) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
            <i class="bi bi-chevron-right small d-none d-sm-inline"></i><strong><?= e($title) ?></strong>
        </nav>

        <form class="top-search" action="bookings.php" method="get" role="search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" id="globalSearch" placeholder="Search customers, phone, area…" aria-label="Search booking requests">
            <kbd>/</kbd>
        </form>

        <div class="d-flex align-items-center gap-2 ms-auto ms-lg-0">
            <button class="icon-btn" type="button" data-theme-toggle aria-label="Switch light / dark theme" title="Switch theme">
                <i class="bi bi-moon-stars theme-light-icon"></i><i class="bi bi-sun theme-dark-icon"></i>
            </button>

            <div class="dropdown">
                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= $newCount ?> new booking requests">
                    <i class="bi bi-bell"></i><?php if ($newCount > 0): ?><span class="ping"></span><?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notif-menu">
                    <div class="notif-head">
                        <strong>Notifications</strong>
                        <span class="pill pill-new"><i></i><?= $newCount ?> new</span>
                    </div>
                    <?php if (!$latest): ?>
                        <?= rs_empty('bi-check2-circle', "You're all caught up", 'New booking requests from the website will appear here.') ?>
                    <?php else: foreach ($latest as $b): ?>
                        <a class="notif-item" href="booking.php?id=<?= (int) $b['id'] ?>">
                            <?= rs_avatar($b['name'], 'sm') ?>
                            <div class="min-w-0">
                                <div><strong><?= e($b['name']) ?></strong> requested <?= e($b['service'] ?: 'a quote') ?></div>
                                <div class="meta"><i class="bi bi-clock"></i> <?= e(rs_time_ago($b['received_at'])) ?> · <?= e($b['phone']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; endif; ?>
                    <a class="notif-foot" href="bookings.php?status=new">View all new requests</a>
                </div>
            </div>

            <div class="dropdown">
                <button class="top-user" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?= rs_avatar($admin['username'], 'sm') ?><span class="name"><?= e(ucfirst($admin['username'])) ?></span><i class="bi bi-chevron-down small text-3"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Signed in as <?= e($admin['username']) ?></h6></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-sliders"></i> Website settings</a></li>
                    <li><a class="dropdown-item" href="password.php"><i class="bi bi-shield-lock"></i> Change password</a></li>
                    <li><a class="dropdown-item" href="../index.html" target="_blank" rel="noopener"><i class="bi bi-globe2"></i> Open website</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="post" action="logout.php">
                            <?= rs_csrf_field() ?>
                            <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Sign out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="content" id="main">
        <?php if ($admin['is_default']): ?>
            <div class="banner fade-up">
                <span class="ic"><i class="bi bi-shield-exclamation"></i></span>
                <div class="flex-grow-1"><strong>Secure your account.</strong> You are still using the default password.</div>
                <a class="btn btn-sm btn-light-x" href="password.php">Change password</a>
            </div>
        <?php endif; ?>

        <?php if ($flash): ?>
            <span id="flashData" hidden data-type="<?= $flash['type'] === 'error' ? 'err' : 'ok' ?>" data-message="<?= e($flash['message']) ?>"></span>
        <?php endif; ?>
<?php
}

function rs_admin_footer(): void
{
    ?>
    </main>
</div>
</div>

<div class="toast-stack" id="toasts" aria-live="polite"></div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content p-4">
            <div class="confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <h2 class="h5 fw-bold mb-2" id="confirmTitle">Are you sure?</h2>
            <p class="text-2 mb-4" id="confirmText"></p>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-light-x" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-soft-danger" id="confirmGo"><i class="bi bi-trash"></i> Yes, delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';
    var root = document.documentElement;

    // Mobile sidebar
    document.querySelectorAll('[data-nav-toggle]').forEach(function (el) {
        el.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
    });

    // Theme switch
    document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            root.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('rs-admin-theme', next); } catch (e) {}
        });
    });

    // "/" focuses the search box
    var search = document.getElementById('globalSearch');
    document.addEventListener('keydown', function (e) {
        var tag = (e.target.tagName || '').toLowerCase();
        if (e.key === '/' && search && tag !== 'input' && tag !== 'textarea' && tag !== 'select') {
            e.preventDefault();
            search.focus();
        }
    });

    // Toasts
    window.rsToast = function (message, type) {
        var stack = document.getElementById('toasts');
        var el = document.createElement('div');
        el.className = 'toast-x ' + (type || 'ok');
        el.setAttribute('role', type === 'err' ? 'alert' : 'status');
        el.innerHTML = '<span class="ic"><i class="bi ' + (type === 'err' ? 'bi-exclamation-lg' : 'bi-check-lg') + '"></i></span>'
            + '<div><strong>' + (type === 'err' ? 'Something needs attention' : 'Done') + '</strong><span></span></div>'
            + '<button type="button" aria-label="Dismiss"><i class="bi bi-x-lg"></i></button>';
        el.querySelector('div span').textContent = message;
        var close = function () { el.classList.add('hide'); setTimeout(function () { el.remove(); }, 300); };
        el.querySelector('button').addEventListener('click', close);
        stack.appendChild(el);
        setTimeout(close, type === 'err' ? 9000 : 5000);
    };
    var flash = document.getElementById('flashData');
    if (flash) { rsToast(flash.getAttribute('data-message'), flash.getAttribute('data-type')); }

    // Confirm dialog for destructive forms
    var modalEl = document.getElementById('confirmModal');
    var modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    var pending = null;
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute('data-confirm') || form.dataset.confirmed === '1' || !modal) { return; }
        e.preventDefault();
        pending = form;
        document.getElementById('confirmTitle').textContent = form.getAttribute('data-confirm-title') || 'Are you sure?';
        document.getElementById('confirmText').textContent = form.getAttribute('data-confirm');
        modal.show();
    }, true);
    var go = document.getElementById('confirmGo');
    if (go) {
        go.addEventListener('click', function () {
            if (!pending) { return; }
            pending.dataset.confirmed = '1';
            go.disabled = true;
            pending.submit();
        });
    }

    // Selects that save on change
    document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            sel.className = sel.className.replace(/pill-\w+/, 'pill-' + sel.value);
            sel.form.submit();
        });
    });
})();
</script>
</body>
</html>
<?php
}
