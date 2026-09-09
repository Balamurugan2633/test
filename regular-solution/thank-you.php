<?php
// Greets the visitor by name when they arrive straight from a submission.
session_start();
$name = isset($_SESSION['booking_name']) ? trim((string) $_SESSION['booking_name']) : '';
unset($_SESSION['booking_name']);
$firstName = $name !== '' ? preg_split('/\s+/', $name)[0] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thank You | Regular Solution</title>
    <meta name="description" content="Thank you for contacting Regular Solution. Our coordinator will call you back shortly to confirm your home service booking in Chennai.">
    <meta name="robots" content="noindex, follow">
    <meta name="theme-color" content="#102552">

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

<a class="skip-link" href="#main">Skip to main content</a>

<!-- ==================== HEADER : TOP INFORMATION BAR ==================== -->
<header>
    <div class="topbar">
        <div class="container">
            <div class="row align-items-center g-3">
                <div class="col-lg-4 d-none d-lg-block">
                    <a class="brand-logo d-inline-block" href="index.html" aria-label="Regular Solution home">
                        <img src="images/logo.png" alt="Regular Solution — all home services in Chennai" width="420" height="215">
                    </a>
                </div>

                <div class="col-lg-8">
                    <ul class="info-list list-clean">
                        <li class="info-item">
                            <span class="info-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                            <span class="info-text">
                                <span class="label">Open Everday</span>
                                <span class="value">Mon &ndash; Sun, 24 Hhrs</span>
                            </span>
                        </li>
                        <li class="info-item">
                            <span class="info-icon" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>
                            <span class="info-text">
                                <span class="label">Our Location</span>
                                <span class="value">Thiruvanmiyur, Ch&#8209;41</span>
                            </span>
                        </li>
                        <li class="info-item">
                            <span class="info-icon" aria-hidden="true"><i class="bi bi-headset"></i></span>
                            <span class="info-text">
                                <span class="label">Call Us Anytime</span>
                                <a class="value" href="tel:18004199090">18004199090</a>
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
                <img src="images/logo.png" alt="Regular Solution" width="420" height="215">
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
                    <li class="nav-item"><a class="nav-link" href="blog.html">Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.html">Contact</a></li>
                </ul>

                <div class="d-flex align-items-center flex-wrap gap-3">
                    <div class="nav-social">
                        <a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Regular Solution on Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Regular Solution on Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="Regular Solution on YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                    <a class="btn btn-accent" href="#download">Download Now</a>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- ==================== FLOATING ACTION BUTTONS ==================== -->
<a class="float-btn float-whatsapp" href="https://wa.me/918000000000" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
    <i class="bi bi-whatsapp" aria-hidden="true"></i>
</a>
<a class="float-btn float-phone" href="tel:18004199090" aria-label="Call Regular Solution">
    <i class="bi bi-telephone-fill" aria-hidden="true"></i>
</a>
<a class="float-btn float-download" href="#download" aria-label="Download the Regular Solution app">
    <i class="bi bi-phone-fill" aria-hidden="true"></i>
</a>

<main id="main">

    <section class="thankyou-wrap">
        <div class="container">
            <div class="thankyou-card">
                <span class="thankyou-mark" aria-hidden="true">
                    <i class="bi bi-check-lg"></i>
                </span>

                <span class="section-tag">Request received</span>
                <h1>
                    Thank you<?php echo $firstName !== '' ? ', ' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') : ''; ?>!
                </h1>
                <p class="thankyou-lead">
                    Your request has reached our team. A Regular Solution coordinator will call you
                    shortly on the number you verified to confirm the details and give you a free quote.
                </p>

                <ul class="thankyou-steps list-clean">
                    <li>
                        <span class="step-dot">1</span>
                        <div>
                            <h2>We review your request</h2>
                            <p>Our coordinator checks the service, date and area you asked for.</p>
                        </div>
                    </li>
                    <li>
                        <span class="step-dot">2</span>
                        <div>
                            <h2>We call you back</h2>
                            <p>Usually within 30 minutes during working hours, with a clear price.</p>
                        </div>
                    </li>
                    <li>
                        <span class="step-dot">3</span>
                        <div>
                            <h2>A verified professional arrives</h2>
                            <p>On the slot you approve — you only pay once the work is done.</p>
                        </div>
                    </li>
                </ul>

                <div class="thankyou-actions">
                    <a class="btn btn-brand btn-lg" href="index.html">Back to Home</a>
                    <a class="btn btn-outline-brand btn-lg" href="tel:18004199090">
                        <i class="bi bi-telephone-fill me-2" aria-hidden="true"></i>Call 1800 419 9090
                    </a>
                </div>

                <p class="thankyou-note">
                    Need it sooner? Message us on
                    <a href="https://wa.me/918000000000" target="_blank" rel="noopener">WhatsApp</a>
                    and we will pick it up right away.
                </p>
            </div>
        </div>
    </section>

</main>

<!-- ==================== FOOTER ==================== -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="footer-logo">
                    <img src="images/logo.png" alt="Regular Solution" width="420" height="215" loading="lazy">
                </div>
                <h2 class="visually-hidden">About Regular Solution</h2>
                <p>
                    Regular Solution is Chennai's single platform for trusted home services. We connect
                    families with verified professionals for AC service, cleaning, electrical, plumbing,
                    painting, shifting and more — at transparent prices, all day, every day.
                </p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.youtube.com/" target="_blank" rel="noopener" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="https://wa.me/918000000000" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
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
                        <span><a href="tel:18004199090">18004199090</a></span>
                    </li>
                    <li>
                        <i class="bi bi-envelope-fill" aria-hidden="true"></i>
                        <span><a href="mailto:info@example.com">info@example.com</a></span>
                    </li>
                    <li>
                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                        <span>No.31/12, Avvai Nagar,<br>Thiruvanmiyur, Chennai &ndash; 600041</span>
                    </li>
                    <li>
                        <i class="bi bi-clock-fill" aria-hidden="true"></i>
                        <span>Mon &ndash; Sun, 24 Hhrs</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="row align-items-center g-2">
                <div class="col-md-6">
                    <p>&copy; <span data-current-year>2026</span> Regular Solution. All Rights Reserved.</p>
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
