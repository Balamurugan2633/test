<?php
/**
 * Website settings: contact details, logo, social links, footer and home page
 * text. Saving writes the values into every page of the site.
 */
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';
require_once __DIR__ . '/../api/site-settings.php';

$admin    = rs_require_admin();
$fields   = rs_setting_fields();
$settings = rs_settings();
$errors   = [];
$tab      = $_GET['tab'] ?? 'contact';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rs_csrf_check();
    $tab      = (string) ($_POST['tab'] ?? 'contact');
    $previous = $settings;
    [$settings, $errors] = rs_settings_validate($_POST, $settings);

    [$logo, $logoError] = rs_logo_upload($_FILES['logo'] ?? []);
    if ($logoError !== '') {
        $errors['logo'] = $logoError;
    }

    if ($errors) {
        if ($logo !== '') {
            rs_logo_delete($logo);
        }
        $tab = $fields[array_key_first($errors)][0];
    } else {
        if ($logo !== '') {
            $settings['logo'] = $logo;
        } elseif (!empty($_POST['reset_logo'])) {
            $settings['logo'] = $fields['logo'][3];
        }
        rs_settings_store($settings);
        [$pages, $problems] = rs_publish_settings($settings, $previous);
        if ($previous['logo'] !== $settings['logo']) {
            rs_logo_delete($previous['logo']);
        }

        rs_log('admin.log', 'settings saved by ' . $admin['username'] . ', pages updated=' . $pages);
        if ($problems) {
            rs_flash('error', 'Settings saved, but some pages could not be updated: ' . implode(' ', $problems));
        } else {
            rs_flash('success', $pages > 0
                ? 'Saved and published to ' . $pages . ' page' . ($pages === 1 ? '' : 's') . ' of your website.'
                : 'Saved. Your website already shows these details.');
        }
        rs_redirect('settings.php?tab=' . urlencode($tab));
    }
}

$tabs = [
    'contact' => ['bi-telephone', 'Contact details', 'Phone, email, address, hours'],
    'brand'   => ['bi-palette', 'Logo & social', 'Brand logo and social links'],
    'footer'  => ['bi-layout-text-window-reverse', 'Footer', 'About text and copyright'],
    'landing' => ['bi-house', 'Home page', 'Headline, intro and calls to action'],
];
$tab        = isset($tabs[$tab]) ? $tab : 'contact';
$errorTabs  = array_unique(array_map(fn ($k) => $fields[$k][0], array_keys($errors)));
$fieldIcons = [
    'phone' => 'bi-telephone', 'whatsapp' => 'bi-whatsapp', 'email' => 'bi-envelope', 'location_short' => 'bi-pin-map',
    'hours' => 'bi-clock', 'map_query' => 'bi-map', 'facebook' => 'bi-facebook', 'instagram' => 'bi-instagram',
    'youtube' => 'bi-youtube', 'landing_phone' => 'bi-telephone', 'website' => 'bi-globe2', 'copyright' => 'bi-c-circle',
];

rs_admin_header('Website Settings', 'settings', $admin, ['Configuration' => 'settings.php']);
?>
<div class="page-head fade-up">
    <div>
        <h1>Website Settings</h1>
        <p>Change your contact details, logo and key text once — every page of the website updates automatically.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-light-x" href="../index.html" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Preview website</a>
    </div>
</div>

<?php if ($errors): ?>
    <div class="banner danger fade-up">
        <span class="ic"><i class="bi bi-exclamation-lg"></i></span>
        <div><strong>Nothing was saved.</strong> Please fix the highlighted fields — sections with problems are marked with a red dot.</div>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate id="settingsForm">
    <?= rs_csrf_field() ?>
    <input type="hidden" name="tab" id="activeTab" value="<?= e($tab) ?>">

    <div class="row g-4">
        <div class="col-lg-4 col-xl-3">
            <div class="card-x sticky-side fade-up d1">
                <div class="settings-nav" role="tablist" aria-orientation="vertical">
                    <?php foreach ($tabs as $key => [$icon, $label, $sub]): ?>
                        <button class="<?= $key === $tab ? 'active' : '' ?>" id="tab-<?= $key ?>" data-bs-toggle="tab" data-bs-target="#pane-<?= $key ?>"
                                data-tab="<?= $key ?>" type="button" role="tab" aria-controls="pane-<?= $key ?>" aria-selected="<?= $key === $tab ? 'true' : 'false' ?>">
                            <span class="ic"><i class="bi <?= $icon ?>"></i></span>
                            <span><?= $label ?><small><?= $sub ?></small></span>
                            <?php if (in_array($key, $errorTabs, true)): ?><span class="err-dot" title="Has errors"></span><?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div class="card-x-foot small text-3">
                    <i class="bi bi-shield-check"></i> A backup of every page is kept before each save.
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-xl-9">
            <div class="tab-content">
                <?php foreach ($tabs as $group => [$icon, $label, $sub]): ?>
                    <div class="tab-pane fade <?= $group === $tab ? 'show active' : '' ?>" id="pane-<?= $group ?>" role="tabpanel" aria-labelledby="tab-<?= $group ?>">
                        <div class="card-x fade-up d2">
                            <div class="card-x-head"><div><h2><?= $label ?></h2><p><?= $sub ?></p></div></div>
                            <div class="card-x-body">
                                <div class="row g-4">
                                    <?php foreach ($fields as $key => [$g, $flabel, $type, $default, $hint]):
                                        if ($g !== $group) continue;
                                        $bad   = isset($errors[$key]);
                                        $class = 'form-control' . ($bad ? ' is-invalid' : '');
                                        $wide  = in_array($type, ['textarea', 'image'], true);
                                        $icon  = $fieldIcons[$key] ?? null;
                                        $html  = $type === 'tel' ? 'tel' : ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text'));
                                    ?>
                                        <div class="<?= $wide ? 'col-12' : 'col-md-6' ?>">
                                            <label class="form-label" for="f-<?= $key ?>"><?= e($flabel) ?></label>

                                            <?php if ($type === 'image'): ?>
                                                <div class="row g-3 align-items-center">
                                                    <div class="col-md-6">
                                                        <div class="logo-stage"><img id="logoPreview" src="../<?= e($settings['logo']) ?>" alt="Current logo"></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="dropzone<?= $bad ? ' is-invalid' : '' ?>">
                                                            <input type="file" id="f-logo" name="logo" accept="image/png,image/jpeg,image/webp">
                                                            <div class="dz-empty"><i class="bi bi-cloud-arrow-up"></i><strong>Upload new logo</strong><br>PNG, JPG or WEBP · max 2 MB</div>
                                                        </label>
                                                        <?php if ($settings['logo'] !== $default): ?>
                                                            <div class="form-check mt-2">
                                                                <input class="form-check-input" type="checkbox" id="reset_logo" name="reset_logo" value="1">
                                                                <label class="form-check-label small" for="reset_logo">Restore the original logo</label>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php elseif ($type === 'textarea'): ?>
                                                <textarea class="<?= $class ?>" id="f-<?= $key ?>" name="<?= $key ?>" rows="3" maxlength="1000"><?= e($settings[$key]) ?></textarea>
                                            <?php elseif ($icon): ?>
                                                <div class="input-icon">
                                                    <i class="bi <?= $icon ?>"></i>
                                                    <input class="<?= $class ?>" type="<?= $html ?>" id="f-<?= $key ?>" name="<?= $key ?>" value="<?= e($settings[$key]) ?>" maxlength="200">
                                                </div>
                                            <?php else: ?>
                                                <input class="<?= $class ?>" type="<?= $html ?>" id="f-<?= $key ?>" name="<?= $key ?>" value="<?= e($settings[$key]) ?>" maxlength="200">
                                            <?php endif; ?>

                                            <?php if ($bad): ?><div class="invalid-feedback d-block"><?= e($errors[$key]) ?></div><?php endif; ?>
                                            <?php if ($hint !== ''): ?><div class="form-hint"><?= e($hint) ?></div><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="savebar" id="savebar">
                <span class="state" id="saveState"><i class="bi bi-check-circle-fill"></i> All changes are live on your website</span>
                <button class="btn btn-primary" type="submit" id="saveBtn"><i class="bi bi-cloud-check"></i> Save &amp; publish</button>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    var form = document.getElementById('settingsForm');
    var bar = document.getElementById('savebar');
    var state = document.getElementById('saveState');
    var dirty = false;

    document.querySelectorAll('[data-tab]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            document.querySelectorAll('[data-tab]').forEach(function (b) { b.classList.toggle('active', b === btn); });
            document.getElementById('activeTab').value = btn.getAttribute('data-tab');
            history.replaceState(null, '', '?tab=' + btn.getAttribute('data-tab'));
        });
    });

    function markDirty() {
        if (dirty) { return; }
        dirty = true;
        bar.classList.add('dirty');
        state.innerHTML = '<i class="bi bi-circle-fill"></i> You have unsaved changes';
    }
    form.addEventListener('input', markDirty);
    form.addEventListener('change', markDirty);
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    form.addEventListener('submit', function () {
        dirty = false;
        var b = document.getElementById('saveBtn');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Publishing…';
    });

    var logo = document.getElementById('f-logo');
    if (logo) {
        logo.addEventListener('change', function () {
            if (logo.files && logo.files[0]) { document.getElementById('logoPreview').src = URL.createObjectURL(logo.files[0]); }
        });
    }
})();
</script>
<?php
rs_admin_footer();
