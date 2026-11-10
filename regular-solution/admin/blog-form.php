<?php
/**
 * Create a blog (no ?id) or update one (?id=123).
 */
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$admin = rs_require_admin();

$id   = (int) ($_GET['id'] ?? 0);
$post = $id > 0 ? rs_post_find($id) : null;
if ($id > 0 && !$post) {
    rs_flash('error', 'That blog no longer exists.');
    rs_redirect('blogs.php');
}

$form = $post ?? [
    'title' => '', 'slug' => '', 'category' => '', 'excerpt' => '',
    'content' => '', 'image' => '', 'status' => 'published',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rs_csrf_check();

    $form['title']    = rs_post('title');
    $form['category'] = rs_post('category');
    $form['excerpt']  = rs_post('excerpt');
    $form['content']  = trim((string) ($_POST['content'] ?? ''));
    $form['status']   = rs_post('status') === 'draft' ? 'draft' : 'published';
    $slugInput        = rs_post('slug');

    if ($form['title'] === '') {
        $errors['title'] = 'Give your blog a title.';
    } elseif (mb_strlen($form['title']) > 150) {
        $errors['title'] = 'Keep the title under 150 characters.';
    }
    if ($form['content'] === '') {
        $errors['content'] = 'Write the blog content before publishing.';
    }
    if (mb_strlen($form['excerpt']) > 300) {
        $errors['excerpt'] = 'Keep the summary under 300 characters.';
    }
    if (mb_strlen($form['category']) > 40) {
        $errors['category'] = 'Keep the category under 40 characters.';
    }

    $oldImage = $post['image'] ?? '';
    [$uploaded, $uploadError] = rs_handle_upload($_FILES['image'] ?? []);
    if ($uploadError !== '') {
        $errors['image'] = $uploadError;
    }

    if (!$errors) {
        if ($uploaded !== '') {
            $form['image'] = $uploaded;
        } elseif (!empty($_POST['remove_image'])) {
            $form['image'] = '';
        }

        $form['slug'] = rs_unique_slug(rs_slugify($slugInput !== '' ? $slugInput : $form['title']), $id);
        if ($form['excerpt'] === '') {
            $plain           = trim(preg_replace('/\s+/', ' ', preg_replace('/^(#+|[-*])\s+/m', '', $form['content'])));
            $form['excerpt'] = mb_strlen($plain) > 160 ? rtrim(mb_substr($plain, 0, 157)) . '...' : $plain;
        }

        $savedId = rs_post_save($form, $id);
        if ($oldImage !== '' && $oldImage !== $form['image']) {
            rs_delete_upload($oldImage);
        }

        $verb = $id > 0 ? 'updated' : ($form['status'] === 'published' ? 'published — it is live on your website' : 'saved as a draft');
        rs_flash('success', '"' . $form['title'] . '" was ' . $verb . '.');
        rs_redirect($id > 0 ? 'blog-form.php?id=' . $savedId : 'blogs.php');
    }

    if ($uploaded !== '') {
        rs_delete_upload($uploaded);
    }
    $form['slug'] = $slugInput;
}

$isEdit = $post !== null;
rs_admin_header($isEdit ? 'Edit blog' : 'Write new blog', $isEdit ? 'blogs' : 'new', $admin, ['Blogs' => 'blogs.php']);

$invalid  = fn (string $field) => isset($errors[$field]) ? ' is-invalid' : '';
$feedback = fn (string $field) => isset($errors[$field]) ? '<div class="invalid-feedback d-block">' . e($errors[$field]) . '</div>' : '';
?>
<?php if ($errors): ?>
    <div class="banner danger fade-up">
        <span class="ic"><i class="bi bi-exclamation-lg"></i></span>
        <div><strong>Not saved yet.</strong> Please fix the highlighted fields below.</div>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate id="postForm">
    <?= rs_csrf_field() ?>
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card-x fade-up">
                <div class="card-x-body">
                    <label class="visually-hidden" for="title">Title</label>
                    <input class="form-control title-input<?= $invalid('title') ?>" type="text" id="title" name="title" maxlength="150"
                           value="<?= e($form['title']) ?>" placeholder="Write a catchy title…" required autocomplete="off">
                    <?= $feedback('title') ?>

                    <div class="mt-3 mb-4">
                        <label class="form-label" for="excerpt">Summary <span class="opt">— shown on the blog card</span></label>
                        <textarea class="form-control<?= $invalid('excerpt') ?>" id="excerpt" name="excerpt" rows="2" maxlength="300"
                                  placeholder="One or two sentences that make people want to read more. Leave empty to use the opening lines."><?= e($form['excerpt']) ?></textarea>
                        <div class="d-flex justify-content-between"><?= $feedback('excerpt') ?: '<span></span>' ?><span class="form-hint num" id="excerptCount">0 / 300</span></div>
                    </div>

                    <label class="form-label" for="content">Content</label>
                    <div class="editor<?= $invalid('content') ?>">
                        <div class="editor-bar" role="toolbar" aria-label="Formatting">
                            <button type="button" data-insert="## " title="Add a heading"><i class="bi bi-type-h2"></i> Heading</button>
                            <button type="button" data-insert="- " title="Add a bullet point"><i class="bi bi-list-ul"></i> Bullet</button>
                            <button type="button" data-insert="para" title="Start a new paragraph"><i class="bi bi-paragraph"></i> Paragraph</button>
                            <span class="sep"></span>
                            <span class="stats num" id="stats">0 words · 1 min read</span>
                        </div>
                        <textarea id="content" name="content" required placeholder="Start writing your article…&#10;&#10;Tip: leave an empty line between paragraphs."><?= e($form['content']) ?></textarea>
                    </div>
                    <?= $feedback('content') ?>
                    <div class="form-hint"><i class="bi bi-info-circle"></i> Leave a blank line between paragraphs. Start a line with <code>##</code> for a heading or <code>-</code> for a bullet point.</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="sticky-side">
                <div class="card-x mb-4 fade-up d1">
                    <div class="card-x-head"><h2>Publish</h2><?php if ($isEdit): ?><?= rs_post_pill($post['status']) ?><?php endif; ?></div>
                    <div class="card-x-body">
                        <div class="choice-grid mb-3">
                            <div class="choice">
                                <input type="radio" name="status" id="s-pub" value="published" <?= $form['status'] === 'published' ? 'checked' : '' ?>>
                                <label for="s-pub"><span class="ic"><i class="bi bi-globe2"></i></span><span><strong>Published</strong><small>Visible on your website</small></span></label>
                            </div>
                            <div class="choice">
                                <input type="radio" name="status" id="s-draft" value="draft" <?= $form['status'] === 'draft' ? 'checked' : '' ?>>
                                <label for="s-draft"><span class="ic"><i class="bi bi-file-earmark-lock"></i></span><span><strong>Draft</strong><small>Hidden — only you can see it</small></span></label>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-lg w-100" type="submit" id="saveBtn">
                            <i class="bi bi-<?= $isEdit ? 'check2' : 'send' ?>"></i> <?= $isEdit ? 'Update blog' : 'Submit blog' ?>
                        </button>
                        <?php if ($isEdit && $post['status'] === 'published'): ?>
                            <a class="btn btn-light-x w-100 mt-2" href="../post.php?slug=<?= urlencode($post['slug']) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> View live post</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($isEdit): ?>
                        <div class="card-x-foot small text-3">
                            <div><i class="bi bi-calendar-plus"></i> Created <?= e(date('d M Y, h:i A', strtotime($post['created_at']))) ?></div>
                            <div><i class="bi bi-pencil"></i> Last edited <?= e(rs_time_ago($post['updated_at'])) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-x mb-4 fade-up d2">
                    <div class="card-x-head"><h2>Cover image</h2></div>
                    <div class="card-x-body">
                        <label class="dropzone<?= $invalid('image') ?>" id="dropzone">
                            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" aria-label="Cover image">
                            <img id="coverPreview" src="<?= $form['image'] ? '../' . e($form['image']) : '' ?>" alt="Cover preview" <?= $form['image'] ? '' : 'hidden' ?>>
                            <div class="dz-empty" id="dzEmpty" <?= $form['image'] ? 'hidden' : '' ?>>
                                <i class="bi bi-cloud-arrow-up"></i>
                                <strong>Click to upload</strong> or drag &amp; drop<br>JPG, PNG, WEBP · max 3 MB · best 800 × 500
                            </div>
                        </label>
                        <?= $feedback('image') ?>
                        <?php if ($form['image']): ?>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                <label class="form-check-label small" for="remove_image">Remove cover image</label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-x fade-up d3">
                    <div class="card-x-head"><h2>Organise</h2></div>
                    <div class="card-x-body">
                        <div class="mb-3">
                            <label class="form-label" for="category">Category</label>
                            <input class="form-control<?= $invalid('category') ?>" type="text" id="category" name="category" maxlength="40"
                                   value="<?= e($form['category']) ?>" placeholder="e.g. Shifting" list="categoryList">
                            <datalist id="categoryList">
                                <option value="Shifting"><option value="AC Care"><option value="Cleaning">
                                <option value="Electrical"><option value="Plumbing"><option value="Interiors">
                            </datalist>
                            <?= $feedback('category') ?>
                        </div>
                        <label class="form-label" for="slug">Web address</label>
                        <div class="input-prefix">
                            <span>post.php?slug=</span>
                            <input class="form-control" type="text" id="slug" name="slug" maxlength="80" value="<?= e($form['slug']) ?>" placeholder="made-from-title">
                        </div>
                        <div class="form-hint">Leave empty and we'll create it from the title.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
(function () {
    var content = document.getElementById('content');
    var stats = document.getElementById('stats');
    var excerpt = document.getElementById('excerpt');
    var excerptCount = document.getElementById('excerptCount');

    function updateStats() {
        var words = (content.value.match(/\S+/g) || []).length;
        stats.textContent = words + ' word' + (words === 1 ? '' : 's') + ' · ' + Math.max(1, Math.ceil(words / 200)) + ' min read';
        excerptCount.textContent = excerpt.value.length + ' / 300';
    }
    content.addEventListener('input', updateStats);
    excerpt.addEventListener('input', updateStats);
    updateStats();

    // Toolbar: insert a heading / bullet / paragraph at the cursor line
    document.querySelectorAll('[data-insert]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var token = btn.getAttribute('data-insert');
            var start = content.selectionStart, value = content.value;
            var lineStart = value.lastIndexOf('\n', start - 1) + 1;
            var insert, caret;
            if (token === 'para') {
                // Make sure the cursor sits after one blank line.
                var before = value.slice(0, start);
                insert = before === '' || /\n\n$/.test(before) ? '' : (/\n$/.test(before) ? '\n' : '\n\n');
                content.value = before + insert + value.slice(start);
                caret = start + insert.length;
            } else {
                content.value = value.slice(0, lineStart) + token + value.slice(lineStart);
                caret = start + token.length;
            }
            content.focus();
            content.setSelectionRange(caret, caret);
            updateStats();
        });
    });

    // Cover image preview + drag & drop styling
    var input = document.getElementById('image');
    var zone = document.getElementById('dropzone');
    input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        if (!file) { return; }
        var img = document.getElementById('coverPreview');
        img.src = URL.createObjectURL(file);
        img.hidden = false;
        document.getElementById('dzEmpty').hidden = true;
    });
    ['dragenter', 'dragover'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.add('drag'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { zone.addEventListener(ev, function () { zone.classList.remove('drag'); }); });

    document.getElementById('postForm').addEventListener('submit', function () {
        var b = document.getElementById('saveBtn');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving…';
    });
})();
</script>
<?php
rs_admin_footer();
