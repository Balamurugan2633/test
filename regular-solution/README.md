# Regular Solution — Home Services Website

A responsive, frontend-only marketing website for **Regular Solution**, a Chennai home-services
brand. Built with plain HTML5, CSS3, Bootstrap 5 and vanilla JavaScript — no build step, no
framework, no backend.

---

## Quick start

Open `index.html` in any modern browser. That's it.

Because the pages are static, they also run from XAMPP/Apache or any static host:

```
http://localhost/test/regular-solution/index.html
```

> **Note:** Bootstrap, Bootstrap Icons and the Poppins font load from public CDNs, so the first
> load needs an internet connection. To run fully offline, download those three files into
> `css/vendor/` and `js/vendor/` and update the `<link>` / `<script>` tags in each page.

---

## Tech stack

| Layer | Used |
|-------|------|
| Markup | Semantic HTML5 |
| Styling | CSS3 with custom properties + Bootstrap 5.3 |
| Icons | Bootstrap Icons 1.11 |
| Fonts | Poppins (Google Fonts) |
| Scripts | Vanilla JavaScript (no jQuery, no bundler) |

Explicitly **not** used: WordPress, Elementor, React, Angular, Vue, PHP, Tailwind.

---

## Folder structure

```
regular-solution/
│
├── index.html              # Home — hero, services, about, booking, testimonials, FAQ, blog
├── about.html              # Company story, mission/vision/values, stats, why choose us
├── services.html           # All 16 service categories + how it works
├── blog.html               # Blog grid (6 posts) with pagination
├── contact.html            # Contact details, booking form, map, FAQ
│
├── packers-movers-chennai.html  # Google Ads landing page (no nav, single CTA)
├── privacy.html            # Privacy policy
├── terms.html              # Terms & conditions
│
├── css/
│   ├── style.css           # All custom styling (21 numbered sections)
│   └── landing.css         # Landing-page-only layer (12 numbered sections)
│
├── js/
│   └── script.js           # Sticky nav, scroll reveal, counters, slider, form validation
│
├── images/
│   ├── logo.png            # Brand logo
│   ├── hero.png            # Hero worker (transparent PNG)
│   ├── about.png           # About section worker
│   ├── booking.png         # Booking section worker
│   ├── cta.png             # App-download section worker
│   ├── services/           # 16 square service icons (ac.jpg, plumbing.jpg, …)
│   ├── testimonials/       # 6 avatar placeholders (SVG)
│   └── blog/               # 6 blog cover images
│
└── README.md
```

---

## Pages & sections

**index.html**

1. Top information header (logo, hours, location, phone)
2. Navy navigation bar with social icons and *Download Now*
3. Hero — headline, sub-copy, CTAs, worker image on a soft radial backdrop
4. Our Top Home Services (8 featured cards)
5. About — *Your Trusted Home Service Partner*
6. Statistics band with animated counters
7. Booking form — *Ready To Get Service?*
8. Why Choose Regular Solution (6 feature cards)
9. How It Works (7-step timeline)
10. Testimonials slider
11. FAQ accordion
12. Latest blog (3 posts)
13. App download
14. Newsletter
15. Footer (about, services, more services, contact)

Floating buttons (WhatsApp bottom-left, phone bottom-right, orange *Download Now*) appear on
every page.

**packers-movers-chennai.html** — the paid-traffic landing page

A deliberately stripped-down page for Google Ads moving campaigns: announcement bar, logo plus a
single *Get Free Quote* button (no navigation, so a paid click converts instead of browsing),
hero with the quote form, trust strip, six moving-service cards, three-step process, Chennai
service-area chips, FAQ, closing CTA and a short footer. It is `noindex, follow` so it does not
compete with `services.html` for the same query.

It reuses the shared tokens in `style.css`, the OTP markup hooks from the home-page booking form
(so `js/script.js` wires it up with no extra code) and `api/submit-form.php`, and adds only
`css/landing.css` on top. Below 992px the form moves above the hero copy.

---

## Design tokens

All colours live in one place at the top of `css/style.css`:

```css
:root {
    --primary:   #4677E8;  /* brand blue    */
    --secondary: #102552;  /* dark navy     */
    --danger:    #EF2D2D;  /* highlight red */
    --accent:    #FFB511;  /* CTA yellow    */
    --light-bg:  #F3F6FC;  /* soft section  */
    --dark:      #111827;  /* body text     */
    --white:     #FFFFFF;
}
```

Change a value there and it updates across all five pages. Radii, shadows, easing and the font
stack are tokenised the same way.

---

## JavaScript behaviour (`js/script.js`)

| Feature | Notes |
|---------|-------|
| Sticky navbar | Adds `.is-stuck` shadow after 10px of scroll |
| Mobile menu | Auto-closes after a nav link is tapped |
| Scroll reveal | `IntersectionObserver`; optional `data-reveal-delay="120"` for stagger |
| Counters | `data-target` + `data-suffix`, eased count-up when the band scrolls into view |
| Testimonial slider | Custom track — 3 cards desktop / 2 tablet / 1 mobile, autoplay, dots, arrows, pauses on hover and focus |
| Form validation | Bootstrap validation styles plus a phone-length check; shows an inline success message (no backend call) |
| Newsletter | Email validation with an inline confirmation |

Every effect degrades gracefully: without JavaScript the content is still fully readable, and
`prefers-reduced-motion: reduce` disables the animations.

---

## Responsive behaviour

Tested at 1920, 1440, 1200, 992, 768, 576, 500 and 375 px.

- Service cards: 4 per row (≥1200) → 2 (≥768) → 1 (<768)
- Header info stacks under the logo below 992px, then one per line below 576px
- Hero switches to text-first, image-below and centres its content below 992px
- The floating *Download Now* button docks to the bottom-right corner below 1200px so it never
  overlaps the sticky navbar
- No horizontal scrolling at any width

---

## Accessibility

- Skip-to-content link, one `<h1>` per page, ordered heading levels
- `alt` text on every meaningful image; decorative icons marked `aria-hidden="true"`
- Labelled form fields with `invalid-feedback` messages
- Accessible Bootstrap navbar and accordion (`aria-expanded`, `aria-controls`)
- Visible `:focus-visible` outlines on links, buttons and inputs
- Colour combinations chosen for contrast against their backgrounds

---

## Customising

**Add a service card** — copy one `<article class="service-card">` block in `services.html`,
change the title, description and `images/services/<name>.jpg`, then add the option to the
`Select Service` dropdown in the booking form.

**Change contact details** — the phone number appears in the top bar, floating button, footer and
form success message; search for `18004199090`. The address lives in the footer and on
`contact.html`.

**Connect the form to a backend** — in `js/script.js`, replace the success branch of
`initForms()` with your own `fetch()` POST. The markup already carries `name` attributes on every
field.

---

## Image assets

Logo, service icons and worker photographs come from the project's own `Regular Solution` asset
folder. They were cropped and resized for the web (service icons to 320×320, worker photos to
520–900px tall) so the whole site loads in roughly 1.5 MB. Testimonial avatars and blog covers are
generated placeholders — swap in real photography before launch.

`images/services/local-route.jpg` was added for the landing page's *Local Shifting* card. It is
the pin-to-pin route icon from `Regular Solution/icons/Travels and Transport Services.jpg`,
cropped to its ink bounding box and resized to 320×320 to match the other service icons.

---

© 2026 Regular Solution. All rights reserved.
