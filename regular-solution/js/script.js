/* ==========================================================================
   Regular Solution — UI interactions
   1. Current year in footer
   2. Sticky navigation shadow
   3. Mobile menu auto-close
   4. Scroll reveal animations
   5. Animated statistic counters
   6. Testimonial slider
   7. Booking / contact form validation
   8. Newsletter form validation
   ========================================================================== */

(function () {
    'use strict';

    /* 1. Current year in footer =========================================== */
    function setCurrentYear() {
        document.querySelectorAll('[data-current-year]').forEach(function (el) {
            el.textContent = new Date().getFullYear();
        });
    }

    /* 2. Sticky navigation shadow ========================================= */
    function initStickyNav() {
        var nav = document.querySelector('.main-nav');
        if (!nav) return;

        var toggle = function () {
            nav.classList.toggle('is-stuck', window.scrollY > 10);
        };

        toggle();
        window.addEventListener('scroll', toggle, { passive: true });
    }

    /* 3. Mobile menu auto-close =========================================== */
    function initMobileMenuClose() {
        var collapse = document.getElementById('mainNavbar');
        if (!collapse || typeof bootstrap === 'undefined') return;

        collapse.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (collapse.classList.contains('show')) {
                    bootstrap.Collapse.getOrCreateInstance(collapse).hide();
                }
            });
        });
    }

    /* 4. Scroll reveal animations ========================================= */
    function initReveal() {
        var items = document.querySelectorAll('.reveal');
        if (!items.length) return;

        if (!('IntersectionObserver' in window)) {
            items.forEach(function (el) { el.classList.add('is-visible'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                var delay = entry.target.dataset.revealDelay || 0;
                setTimeout(function () {
                    entry.target.classList.add('is-visible');
                }, Number(delay));
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });

        items.forEach(function (el) { observer.observe(el); });
    }

    /* 5. Animated statistic counters ====================================== */
    function runCounter(el) {
        var target = Number(el.dataset.target) || 0;
        var suffix = el.dataset.suffix || '';
        var duration = 1600;
        var start = null;

        function step(timestamp) {
            if (start === null) start = timestamp;
            var progress = Math.min((timestamp - start) / duration, 1);
            // easeOutCubic for a natural slow-down
            var eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.round(target * eased) + suffix;
            if (progress < 1) window.requestAnimationFrame(step);
        }

        window.requestAnimationFrame(step);
    }

    function initCounters() {
        var counters = document.querySelectorAll('[data-target]');
        if (!counters.length) return;

        if (!('IntersectionObserver' in window)) {
            counters.forEach(runCounter);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                runCounter(entry.target);
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.4 });

        counters.forEach(function (el) { observer.observe(el); });
    }

    /* 5b. Image slideshows (hero + contact) =============================== */
    function initSlideshows() {
        var reduceMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        document.querySelectorAll('[data-hero-slides]').forEach(function (wrap) {
            var slides = Array.prototype.slice.call(wrap.querySelectorAll('.hero-slide'));
            if (slides.length < 2 || reduceMotion) return;

            var interval = Number(wrap.dataset.slideInterval) || 4200;
            var index = 0;
            var timer = null;

            // Pull the next photo in before it is shown so the crossfade does
            // not start against a blank frame on a slow connection.
            function preload(i) {
                var next = slides[i];
                if (next && next.loading === 'lazy') next.loading = 'eager';
            }

            function show(i) {
                slides[index].classList.remove('is-active');
                index = i;
                slides[index].classList.add('is-active');
                preload((index + 1) % slides.length);
            }

            function start() {
                stop();
                timer = window.setInterval(function () {
                    show((index + 1) % slides.length);
                }, interval);
            }

            function stop() {
                if (timer) window.clearInterval(timer);
                timer = null;
            }

            preload(1);
            start();

            // Pause while the tab is hidden so the cycle does not race ahead
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) stop();
                else start();
            });
        });
    }

    /* 6. Testimonial slider =============================================== */
    function initTestimonialSlider() {
        var slider = document.querySelector('[data-slider]');
        if (!slider) return;

        var track = slider.querySelector('.testimonial-track');
        var items = Array.prototype.slice.call(slider.querySelectorAll('.testimonial-item'));
        var prevBtn = document.querySelector('[data-slider-prev]');
        var nextBtn = document.querySelector('[data-slider-next]');
        var dotsWrap = document.querySelector('[data-slider-dots]');
        if (!track || !items.length) return;

        var index = 0;
        var perView = 1;
        var maxIndex = 0;
        var autoplayId = null;

        function getPerView() {
            if (window.innerWidth >= 992) return 3;
            if (window.innerWidth >= 576) return 2;
            return 1;
        }

        // Read the gap from CSS so the slide maths follow the responsive
        // column-gap on .testimonial-track instead of a hard-coded 24px.
        function getGap() {
            var gap = parseFloat(window.getComputedStyle(track).columnGap);
            return isNaN(gap) ? 24 : gap;
        }

        // clientWidth includes the slider's own padding, which the slides cannot
        // use — measure the content box so cards never overflow the viewport.
        function getViewportWidth() {
            var styles = window.getComputedStyle(slider);
            return slider.clientWidth
                - parseFloat(styles.paddingLeft)
                - parseFloat(styles.paddingRight);
        }

        function buildDots() {
            if (!dotsWrap) return;
            dotsWrap.innerHTML = '';
            for (var i = 0; i <= maxIndex; i++) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Go to testimonial ' + (i + 1));
                dot.dataset.index = i;
                dot.addEventListener('click', function () {
                    goTo(Number(this.dataset.index));
                });
                dotsWrap.appendChild(dot);
            }
        }

        function updateDots() {
            if (!dotsWrap) return;
            Array.prototype.forEach.call(dotsWrap.children, function (dot, i) {
                dot.classList.toggle('active', i === index);
                dot.setAttribute('aria-current', i === index ? 'true' : 'false');
            });
        }

        function layout() {
            perView = getPerView();
            maxIndex = Math.max(items.length - perView, 0);
            index = Math.min(index, maxIndex);

            var gap = getGap();
            var itemWidth = (getViewportWidth() - gap * (perView - 1)) / perView;
            items.forEach(function (item) {
                item.style.width = itemWidth + 'px';
            });

            buildDots();
            goTo(index);
        }

        function goTo(newIndex) {
            index = Math.max(0, Math.min(newIndex, maxIndex));
            var itemWidth = items[0].getBoundingClientRect().width;
            track.style.transform = 'translateX(' + (-index * (itemWidth + getGap())) + 'px)';
            updateDots();
        }

        function next() {
            goTo(index >= maxIndex ? 0 : index + 1);
        }

        function prev() {
            goTo(index <= 0 ? maxIndex : index - 1);
        }

        function startAutoplay() {
            stopAutoplay();
            autoplayId = window.setInterval(next, 6000);
        }

        function stopAutoplay() {
            if (autoplayId) window.clearInterval(autoplayId);
            autoplayId = null;
        }

        if (nextBtn) nextBtn.addEventListener('click', function () { next(); startAutoplay(); });
        if (prevBtn) prevBtn.addEventListener('click', function () { prev(); startAutoplay(); });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);
        slider.addEventListener('focusin', stopAutoplay);
        slider.addEventListener('focusout', startAutoplay);

        var resizeTimer;
        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(layout, 180);
        });

        layout();
        startAutoplay();
    }

    /* 7. Booking / contact forms + OTP verification ======================== */
    function showFormMessage(form, message, isSuccess) {
        var box = form.querySelector('[data-form-message]');
        if (!box) return;
        box.textContent = message;
        box.className = 'alert mt-3 ' + (isSuccess ? 'alert-success' : 'alert-danger');
        box.classList.remove('d-none');
        box.setAttribute('role', 'alert');
    }

    function postJson(url, data) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });

        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'fetch'
            },
            body: body.toString()
        }).then(function (res) {
            return res.json()
                .catch(function () {
                    // A PHP fatal or a 404 returns HTML, not JSON
                    throw new Error('The server did not return a valid response. Is PHP running?');
                })
                .then(function (json) {
                    if (!res.ok || !json.ok) {
                        throw new Error(json.message || 'Something went wrong. Please try again.');
                    }
                    return json;
                });
        });
    }

    /* Wires the Send OTP / Verify pair inside one form. */
    function initOtp(form) {
        var phoneInput = form.querySelector('[data-otp-phone]');
        var sendBtn    = form.querySelector('[data-otp-send]');
        var box        = form.querySelector('[data-otp-box]');
        var codeInput  = form.querySelector('[data-otp-input]');
        var verifyBtn  = form.querySelector('[data-otp-verify]');
        var note       = form.querySelector('[data-otp-note]');
        if (!phoneInput || !sendBtn || !box || !codeInput || !verifyBtn) return null;

        var state = { verified: false, verifiedNumber: '' };
        var cooldownTimer = null;

        function digitsOf(value) {
            return (value || '').replace(/\D/g, '');
        }

        function setNote(message, kind) {
            if (!note) return;
            note.textContent = message;
            note.className = 'otp-note' + (kind ? ' is-' + kind : '');
        }

        function startCooldown(seconds) {
            var left = seconds;
            sendBtn.disabled = true;
            if (cooldownTimer) window.clearInterval(cooldownTimer);

            cooldownTimer = window.setInterval(function () {
                left -= 1;
                if (left <= 0) {
                    window.clearInterval(cooldownTimer);
                    cooldownTimer = null;
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Resend OTP';
                } else {
                    sendBtn.textContent = 'Resend in ' + left + 's';
                }
            }, 1000);
            sendBtn.textContent = 'Resend in ' + left + 's';
        }

        // Typing a different number invalidates the previous verification.
        phoneInput.addEventListener('input', function () {
            var digits = digitsOf(phoneInput.value);
            phoneInput.setCustomValidity(/^[6-9]\d{9}$/.test(digits) ? '' : 'invalid');

            if (state.verified && digits !== state.verifiedNumber) {
                state.verified = false;
                phoneInput.readOnly = false;
                phoneInput.parentNode.classList.remove('is-verified');
                var tag = form.querySelector('.otp-verified-tag');
                if (tag) tag.replaceWith(sendBtn);
                sendBtn.disabled = false;
                sendBtn.textContent = 'Send OTP';
                box.classList.add('d-none');
                setNote('', '');
            }
        });

        sendBtn.addEventListener('click', function () {
            var digits = digitsOf(phoneInput.value);
            if (!/^[6-9]\d{9}$/.test(digits)) {
                phoneInput.focus();
                setNote('Enter a valid 10-digit mobile number first.', 'err');
                box.classList.remove('d-none');
                return;
            }

            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending…';
            box.classList.remove('d-none');
            setNote('Sending your code…', '');

            postJson('api/send-otp.php', { phone: digits })
                .then(function (res) {
                    setNote(res.message, res.test_mode ? 'test' : 'ok');
                    codeInput.value = '';
                    codeInput.focus();
                    startCooldown(res.resend_in || 30);
                })
                .catch(function (err) {
                    setNote(err.message, 'err');
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Send OTP';
                });
        });

        verifyBtn.addEventListener('click', function () {
            var digits = digitsOf(phoneInput.value);
            var code   = digitsOf(codeInput.value);

            if (code.length < 4) {
                setNote('Enter the code we sent you.', 'err');
                codeInput.focus();
                return;
            }

            verifyBtn.disabled = true;
            verifyBtn.textContent = 'Checking…';

            postJson('api/verify-otp.php', { phone: digits, code: code })
                .then(function () {
                    state.verified = true;
                    state.verifiedNumber = digits;

                    if (cooldownTimer) window.clearInterval(cooldownTimer);
                    phoneInput.readOnly = true;
                    phoneInput.parentNode.classList.add('is-verified');

                    // Swap the send button for a verified badge
                    var tag = document.createElement('span');
                    tag.className = 'otp-verified-tag';
                    tag.innerHTML = '<i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified';
                    sendBtn.replaceWith(tag);

                    box.classList.add('d-none');
                    showFormMessage(form, 'Mobile number verified. You can send your request now.', true);
                })
                .catch(function (err) {
                    setNote(err.message, 'err');
                    codeInput.select();
                })
                .then(function () {
                    verifyBtn.disabled = false;
                    verifyBtn.textContent = 'Verify';
                });
        });

        // Enter inside the code box should verify, not submit the whole form
        codeInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                verifyBtn.click();
            }
        });

        return state;
    }

    function initForms() {
        document.querySelectorAll('form.needs-validation').forEach(function (form) {
            var otp = form.hasAttribute('data-otp-form') ? initOtp(form) : null;

            // Plain tel check for any form without the OTP flow
            if (!otp) {
                var phone = form.querySelector('input[type="tel"]');
                if (phone) {
                    phone.addEventListener('input', function () {
                        var digits = phone.value.replace(/\D/g, '');
                        phone.setCustomValidity(
                            digits.length >= 10 && digits.length <= 13 ? '' : 'invalid'
                        );
                    });
                }
            }

            form.addEventListener('submit', function (event) {
                form.classList.add('was-validated');

                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    var firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) firstInvalid.focus();
                    showFormMessage(form, 'Please complete the highlighted fields before sending.', false);
                    return;
                }

                if (otp && !otp.verified) {
                    event.preventDefault();
                    event.stopPropagation();
                    showFormMessage(form, 'Please verify your mobile number with the OTP before sending.', false);
                    var sendBtn = form.querySelector('[data-otp-send]');
                    if (sendBtn) sendBtn.focus();
                    return;
                }

                // Valid and verified: let it post to the server, which records
                // the request and redirects to the Thank You page.
                var submit = form.querySelector('button[type="submit"]');
                if (submit) {
                    submit.disabled = true;
                    submit.textContent = 'Sending…';
                }
            });
        });
    }

    /* 8. Newsletter form validation ======================================= */
    function initNewsletter() {
        var form = document.querySelector('[data-newsletter]');
        if (!form) return;

        var input = form.querySelector('input[type="email"]');
        var note = form.parentElement.querySelector('[data-newsletter-message]');

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (!input) return;

            var valid = input.checkValidity() && input.value.trim() !== '';
            if (note) {
                note.textContent = valid
                    ? 'Thanks for subscribing! Look out for our service updates.'
                    : 'Please enter a valid email address.';
                note.classList.remove('d-none');
            }
            if (valid) form.reset();
        });
    }

    /* Bootstrapping ======================================================= */
    document.addEventListener('DOMContentLoaded', function () {
        setCurrentYear();
        initStickyNav();
        initMobileMenuClose();
        initReveal();
        initCounters();
        initSlideshows();
        initTestimonialSlider();
        initForms();
        initNewsletter();
    });
})();
