<?php
/**
 * Public page for one blog published from the admin panel: post.php?slug=...
 */
declare(strict_types=1);

require_once __DIR__ . '/api/blog-lib.php';

$post = rs_post_find_published((string) ($_GET['slug'] ?? ''));
if (!$post) {
    http_response_code(404);
    readfile(__DIR__ . '/404.html');
    exit;
}

$title   = $post['title'];
$excerpt = $post['excerpt'];
$image   = $post['image'] !== '' ? $post['image'] : 'images/blog/blog-5.jpg';
$related = array_values(array_filter(rs_posts('published', '', 4), fn ($p) => $p['id'] !== $post['id']));
$related = array_slice($related, 0, 3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | Regular Solution Blog</title>
    <meta name="description" content="<?= e($excerpt) ?>">
    <meta name="author" content="Regular Solution">
    <meta name="theme-color" content="#102552">

    <!-- Open Graph -->
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($excerpt) ?>">
    <meta property="og:image" content="<?= e($image) ?>">

    <link rel="icon" href="images/favicon-32.png" sizes="32x32" type="image/png">
    <link rel="icon" href="images/rs-icon.png" sizes="512x512" type="image/png">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 + Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Project styles -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body>

<a class="skip-link" href="#main">Skip to main content</a>

<!-- ==================== HEADER : TOP INFORMATION BAR ==================== -->
<header>
    <div class="topbar">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-lg-4 d-none d-lg-block">
                    <a class="brand-logo d-inline-block" href="index.html" aria-label="Regular Solution home">
                        <img src="images/logo.png" data-site-src="logo" alt="Regular Solution — all home services in Chennai" width="420" height="215">
                    </a>
                </div>

                <div class="col-lg-8">
                    <ul class="info-list list-clean">
                        <li class="info-item">
                            <span class="info-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                            <span class="info-text">
                                <span class="label">Open Everday</span>
                                <span class="value" data-site="hours">Mon – Sun, 24 Hhrs</span>
                            </span>
                        </li>
                        <li class="info-item">
                            <span class="info-icon" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>
                            <span class="info-text">
                                <span class="label">Our Location</span>
                                <span class="value" data-site="location_short">Thiruvanmiyur, Ch‑41</span>
                            </span>
                        </li>
                        <li class="info-item">
                            <span class="info-icon" aria-hidden="true"><i class="bi bi-headset"></i></span>
                            <span class="info-text">
                                <span class="label">Call Us Anytime</span>
                                <a class="value" href="tel:8925516635" data-site-href="tel:phone" data-site="phone">8925516635</a>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== NAVIGATION ==================== -->
    <nav class="navbar navbar-expand-lg main-nav" aria-label="Main navigation">
        <div class="container">
            <a class="navbar-brand nav-brand-mobile d-lg-none" href="index.html" aria-label="Regular Solution home">
                <img src="images/logo.png" data-site-src="logo" alt="Regular Solution" width="420" height="215">
            </a>

            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNavbar" aria-controls="mainNavbar"
                    aria-expanded="false" aria-label="Toggle navigation menu">
                <i class="bi bi-list fs-3"></i>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav mx-lg-auto">
                    <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.html">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="services.html">Services</a></li>
                    <li class="nav-item"><a class="nav-link active" href="blog.html">Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.html">Contact</a></li>
                </ul>

                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="nav-social">
                        <a href="https://www.facebook.com/profile.php?id=100093007427833" data-site-href="url:facebook" target="_blank" rel="noopener" aria-label="Regular Solution on Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/regularsolution23/" data-site-href="url:instagram" target="_blank" rel="noopener" aria-label="Regular Solution on Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.youtube.com/@RegularSolution" data-site-href="url:youtube" target="_blank" rel="noopener" aria-label="Regular Solution on YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                    <a class="btn btn-accent" href="#download">Download Now</a>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- ==================== FLOATING ACTION BUTTONS ==================== -->
<a class="float-btn float-whatsapp" href="https://wa.me/918925516635" data-site-href="wa:whatsapp" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
    <i class="bi bi-whatsapp" aria-hidden="true"></i>
</a>
<a class="float-btn float-phone" href="tel:8925516635" data-site-href="tel:phone" aria-label="Call Regular Solution">
    <i class="bi bi-telephone-fill" aria-hidden="true"></i>
</a>
<a class="float-btn float-download" href="#download" aria-label="Download the Regular Solution app">
    <i class="bi bi-phone-fill" aria-hidden="true"></i>
</a>

<main id="main">

    <!-- ==================== PAGE BANNER ==================== -->
    <section class="page-banner">
        <div class="container">
            <h1><?= e($title) ?></h1>
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                    <li class="breadcrumb-item"><a href="blog.html">Blog</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($post['category'] ?: 'Article') ?></li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- ==================== ARTICLE ==================== -->
    <section class="section-pad">
        <div class="container">
            <article class="post-article">
                <div class="blog-meta mb-3">
                    <?php if ($post['category']): ?><span class="blog-tag position-static me-2"><?= e($post['category']) ?></span><?php endif; ?>
                    <i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(rs_format_date($post['published_at'])) ?>
                    <span aria-hidden="true">&bull;</span> <?= rs_reading_minutes($post['content']) ?> min read
                </div>
                <img class="post-cover" src="<?= e($image) ?>" alt="<?= e($title) ?>" width="800" height="500">
                <div class="post-content">
                    <?= rs_render_content($post['content']) ?>
                </div>
                <div class="post-cta">
                    <p class="mb-0">Need help with this at home? Our Chennai team is available 24 hours.</p>
                    <a class="btn btn-accent" href="contact.html">Get a Free Quote</a>
                </div>
                <a class="link-arrow mt-4 d-inline-block" href="blog.html"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to all blogs</a>
            </article>

            <?php if ($related): ?>
                <div class="section-head mt-5 pt-4">
                    <span class="section-tag">Keep Reading</span>
                    <h2>More From Our Blog</h2>
                </div>
                <div class="row g-4">
                    <?php foreach ($related as $r): $url = 'post.php?slug=' . rawurlencode($r['slug']); ?>
                        <div class="col-lg-4 col-md-6">
                            <article class="blog-card">
                                <div class="blog-media">
                                    <img src="<?= e($r['image'] ?: 'images/blog/blog-5.jpg') ?>" alt="<?= e($r['title']) ?>" width="800" height="500" loading="lazy">
                                    <?php if ($r['category']): ?><span class="blog-tag"><?= e($r['category']) ?></span><?php endif; ?>
                                </div>
                                <div class="blog-body">
                                    <div class="blog-meta">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(rs_format_date($r['published_at'])) ?>
                                    </div>
                                    <h3><a href="<?= e($url) ?>"><?= e($r['title']) ?></a></h3>
                                    <p><?= e($r['excerpt']) ?></p>
                                    <a class="link-arrow" href="<?= e($url) ?>">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

</main>

<!-- ==================== FOOTER ==================== -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="footer-logo">
                    <img src="images/logo.png" data-site-src="logo" alt="Regular Solution" width="420" height="215" loading="lazy">
                </div>
                <h2 class="visually-hidden">About Regular Solution</h2>
                <p data-site="footer_about">Regular Solution is Chennai&#039;s single platform for trusted home services. We connect families with verified professionals for AC service, cleaning, electrical, plumbing, painting, shifting and more — at transparent prices, all day, every day.</p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/profile.php?id=100093007427833" data-site-href="url:facebook" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/regularsolution23/" data-site-href="url:instagram" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.youtube.com/@RegularSolution" data-site-href="url:youtube" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="https://wa.me/918925516635" data-site-href="wa:whatsapp" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h3>Our Services</h3>
                <ul class="footer-links list-clean">
                    <li><a href="services.html">AC Services</a></li>
                    <li><a href="services.html">Home Cleaning</a></li>
                    <li><a href="services.html">Electrician</a></li>
                    <li><a href="services.html">Plumbing</a></li>
                    <li><a href="services.html">Painting</a></li>
                    <li><a href="services.html">Carpentry</a></li>
                    <li><a href="services.html">Catering</a></li>
                    <li><a href="services.html">Beautician</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 col-6">
                <h3>More Services</h3>
                <ul class="footer-links list-clean">
                    <li><a href="services.html">Pet Grooming</a></li>
                    <li><a href="services.html">Rental House</a></li>
                    <li><a href="services.html">Home Help</a></li>
                    <li><a href="services.html">Construction</a></li>
                    <li><a href="services.html">Travels &amp; Transport</a></li>
                    <li><a href="services.html">Packers &amp; Movers</a></li>
                    <li><a href="services.html">Interior Design</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h3>Contact Us</h3>
                <ul class="footer-contact list-clean">
                    <li>
                        <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                        <span><a href="tel:8925516635" data-site-href="tel:phone" data-site="phone">8925516635</a> / <a href="tel:8925516636" data-site-href="tel:phone2" data-site="phone2">8925516636</a></span>
                    </li>
                    <li>
                        <i class="bi bi-envelope-fill" aria-hidden="true"></i>
                        <span><a href="mailto:info@regularsolution.com" data-site-href="mailto:email" data-site="email">info@regularsolution.com</a></span>
                    </li>
                    <li>
                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                        <span data-site="address">No.31/12, Avvai Nagar,<br>Thiruvanmiyur, Chennai – 600041</span>
                    </li>
                    <li>
                        <i class="bi bi-clock-fill" aria-hidden="true"></i>
                        <span data-site="hours">Mon – Sun, 24 Hhrs</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="row align-items-center g-2">
                <div class="col-md-6">
                    <p>&copy; <span data-current-year>2026</span> <span data-site="copyright">Regular Solution. All Rights Reserved.</span></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>Designed for Chennai homes &bull; <a href="contact.html">Get in touch</a></p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/script.js"></script>
</body>
</html>
