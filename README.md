<p align="center">
  <img src="public/images/logo-sm.webp" width="220" alt="BookMyMovie">
</p>

<p align="center">
  <strong>Cinema tickets for Pakistan: live seat maps, trailers, QR e-tickets, food add-ons, loyalty points and Urdu, with no page reloads.</strong>
</p>

<p align="center">
  <img alt="Laravel 12" src="https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white">
  <img alt="React 19" src="https://img.shields.io/badge/React-19-61DAFB?style=flat-square&logo=react&logoColor=black">
  <img alt="Inertia 2" src="https://img.shields.io/badge/Inertia-2-9553E9?style=flat-square">
  <img alt="TypeScript" src="https://img.shields.io/badge/TypeScript-strict-3178C6?style=flat-square&logo=typescript&logoColor=white">
  <img alt="Tailwind CSS 4" src="https://img.shields.io/badge/Tailwind-4-38BDF8?style=flat-square&logo=tailwindcss&logoColor=white">
  <img alt="Three.js" src="https://img.shields.io/badge/Three.js-r186-000000?style=flat-square&logo=threedotjs">
  <img alt="MIT licence" src="https://img.shields.io/badge/licence-MIT-e3ff3b?style=flat-square">
</p>

---

BookMyMovie is a full-stack cinema booking platform. Visitors browse films, watch trailers, pick seats on a live map (or in a 3D model of the hall), add snacks and book. Customers get a QR e-ticket that also works offline and can go into Apple or Google Wallet. Box-office staff scan tickets, issue refunds and gift cards, and can see every change in an audit log.

Built by **[Syed Ahmer Shah](https://ahmershah.dev/)**, originally for the Aptech DISM project.

## Features

### For moviegoers

| | |
| --- | --- |
| **Films and trailers** | 4:3 card art on every listing, a 16:9 hero, and 10-second trailers that play silently behind the home carousel and film pages. Films with two cuts list both, labelled, side by side. |
| **Seats** | Row-tier pricing, a live seat map, **adult and child tickets in one booking**, and **"best 2 (or 4) together"** recommendations. Seats are held for 10 minutes, with a live countdown in the navbar. |
| **See it from your seat** | A Three.js model of the hall: sit in any seat, watch the trailer play on the screen as the house lights dim, get a view score (distance, how much of your view the screen fills, off-centre angle, neck tilt) and hop to the seat next door, one row closer or further back. |
| **Sold out?** | Join a first-come **waitlist**; you are told the moment seats come back (a cancellation or an expired hold). |
| **Group bookings** | **Split the bill** into equal shares; each friend gets a private link and pays by card online or at the counter. The booking is paid when every share is. |
| **Alerts** | In-app notifications and optional **web push**: a watchlisted film opening for booking, seats opening on your waitlist. |
| **Checkout** | Food and drink add-ons, coupons, **gift cards** and **loyalty points** in one step. Cash at the counter, or JazzCash / Easypaisa / card once gateway keys are set. |
| **E-tickets** | A signed **QR code** on every ticket, **Apple Wallet** and **Google Wallet** passes (when credentials are configured), and **offline access** via a service worker (installable PWA). |
| **Account** | Bookings, tracking, watchlist, profile photo, points history, **review photos** (re-encoded, location data removed), and optional **two-step sign-in** with an emailed 6-digit code. |
| **Look and feel** | A scroll-driven intro that flies down a projector beam into a wall of posters, posters that tilt and catch the light, a WebGL film-reel page transition, a light-bulb marquee spelling each film's title, and an e-ticket whose stub tears off when you are scanned in. |
| **Urdu** | A full English/Urdu switch with right-to-left layout and Noto Naskh Arabic. Film titles, prices and booking numbers stay in Latin script so they match the ticket. |

### For the box office

| | |
| --- | --- |
| **Dashboard** | Movies, media, hero carousel, shows, seat pricing, users, reviews, SEO and site settings. |
| **Revenue & occupancy** | Revenue per day, tickets, average ticket and seats filled by film and cinema over 7, 30 or 90 days, with a table view. |
| **Coupons, stock & sales** | Coupons with hard use limits, snack stock with low-stock warnings, and a per-film switch for ticket sales. |
| **Staff & roles** | Owner, Manager and Box office roles, enforced for every admin route; **two-step sign-in with an authenticator app** and recovery codes. |
| **Bans** | Banning a member also blocks every IP and browser they used; accounts sharing a browser are flagged. |
| **Audit log** | Every data-changing action by admins and customers, searchable, with the before/after changes. |
| **Refunds** | Cancel a confirmed booking in one step: seats, coupon use, gift card balance and points all return, and the customer is emailed. |
| **Gift cards** | Issue cards with a balance, expiry and message, emailed to the recipient; switch them off at any time. |
| **Ticket check** | Scanning a ticket's QR opens a check page that verifies its signature, shows seats and snacks, and admits the guests (taking payment first if it is due). |

### Under the hood

- **No page reloads**: Inertia 2 with React 19 and strict TypeScript; Lenis smooth scrolling; Motion for animation.
- **Emails never block a page**: receipts and notices go through Laravel's database queue with retries; one-time codes are sent immediately.
- **Security**: strict nonce-based Content-Security-Policy, rate limiting on every sensitive route, Argon2id password hashing, reCAPTCHA v2/v3 support, disposable-email blocking, signed ticket QR codes.
- **SEO**: server-rendered meta, Open Graph, JSON-LD, sitemap and `llms.txt`.
- **Optimised media**: artwork is WebP (about 85 MB of PNG down to 5.4 MB); trailers are 720p H.264 with fast-start (34 MB down to 9.4 MB).

## Tech stack

| Layer | Tools |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12, Eloquent, Inertia (server), Ziggy |
| Frontend | React 19, TypeScript, Tailwind CSS 4, Motion, Lenis, Three.js with @react-three/fiber, uqr |
| Type | Archivo (variable width), Geist Mono, Noto Naskh Arabic |
| Data | MySQL (SQLite works for local development) |
| Mail and jobs | Resend or any Laravel mailer; database queue |

## Getting started

```bash
git clone https://github.com/ahmershahdev/bookmymovie.git
cd bookmymovie

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Set the database in `.env` (`DB_CONNECTION`, `DB_DATABASE`, and so on), then:

```bash
php artisan migrate --seed    # films, cinemas, shows, artwork and trailers
npm run build                 # or: npm run dev
php artisan serve
```

Open <http://127.0.0.1:8000>.

### Background email

Emails are queued (`QUEUE_CONNECTION=database`). Run a worker:

```bash
php artisan queue:work --tries=3
```

Or, where a long-running worker is not available, let the scheduler drain the queue every minute:

```cron
* * * * * php /path/to/artisan schedule:run
```

### Optional integrations

Everything below is off until its keys are set; the site works without them.

| Feature | `.env` keys |
| --- | --- |
| reCAPTCHA | `RECAPTCHA_V2_SITE_KEY`, `RECAPTCHA_V2_SECRET_KEY`, `RECAPTCHA_V3_SITE_KEY`, `RECAPTCHA_V3_SECRET_KEY` |
| Apple Wallet | `APPLE_WALLET_PASS_TYPE_ID`, `APPLE_WALLET_TEAM_ID`, `APPLE_WALLET_CERTIFICATE`, `APPLE_WALLET_CERTIFICATE_PASSWORD`, `APPLE_WALLET_WWDR` |
| Google Wallet | `GOOGLE_WALLET_ISSUER_ID`, `GOOGLE_WALLET_KEY_FILE` |
| Online payments | JazzCash, Easypaisa and card keys (see `config/payments.php`) |
| Social sign-in | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `FACEBOOK_CLIENT_ID`, `FACEBOOK_CLIENT_SECRET` |
| Web push | `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` (generate with `php artisan push:keys`), `VAPID_SUBJECT` |

The scheduler (`schedule:run` every minute) also clears expired seat holds and alerts show waitlists.

## Film artwork and trailers

Drop files into `public/` using the film's slug, then run `php artisan migrate` (or re-seed). `App\Support\MovieMedia` links whatever it finds:

```text
public/images/movies/{slug}/card.webp     4:3 card, used on listings (1200×900)
public/images/movies/{slug}/hero.webp     16:9 backdrop (1600×900)
public/videos/trailers/{slug}-1.mp4       first trailer
public/videos/trailers/{slug}-2.mp4       optional second cut
public/videos/trailers/{slug}-N.webp      optional still shown before playback
```

A film without a card falls back to its hero, then to a generated typographic poster, so nothing shows an empty box. Admin uploads are never overwritten.

## Project layout

```text
app/Http/Controllers/     Public, movie, cinema, account, auth, payment, ticket and admin controllers
app/Support/              Booking lifecycle, wallet passes, media, mail, security helpers
resources/js/pages/       Inertia pages (Home, Movies, Account, Admin, Auth, Public)
resources/js/components/  UI kit, shell (navbar, footer), trailer player, QR code
resources/js/three/       Poster ring and 3D cinema hall
resources/js/lang/ur.ts   Urdu strings (English text is the key)
public/sw.js              Service worker for offline tickets
```

## Tests

```bash
php artisan test
```

## Licence

[MIT](LICENSE) © 2026 Syed Ahmer Shah. Policy pages and support copy were written for the project and should be reviewed before a production launch.
