# BookMyMovie

<p align="center">
  <img src="public/images/header-logo-3.png" width="96" alt="BookMyMovie logo">
</p>

<p align="center">
  <strong>A premium Laravel cinema booking project for movie discovery, seat selection, carts, wishlists, checkout, bookings, support, and admin workflows.</strong>
</p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-11-red?style=for-the-badge">
  <img alt="Blade" src="https://img.shields.io/badge/Blade-Views-orange?style=for-the-badge">
  <img alt="Tailwind" src="https://img.shields.io/badge/Tailwind-CDN-38BDF8?style=for-the-badge">
  <img alt="Alpine" src="https://img.shields.io/badge/Alpine.js-Interactive-77C1D2?style=for-the-badge">
</p>

## Overview

BookMyMovie is a full-stack movie ticket booking system built with Laravel, Blade, Tailwind CSS, and Alpine.js. It is designed as a complete academic portfolio project with a polished public experience and practical admin/user workflows.

The interface uses a dark cinema theme, glass panels, 3D tilt effects, rich tables, responsive cards, breadcrumbs, validation states, and captcha-ready forms.

## Feature Matrix

| Area | Features |
| --- | --- |
| Public site | Home, movies, movie details, compare, search, about, contact, FAQ, legal pages |
| Booking flow | 4 showtimes per seeded movie, row-tier seat pricing, visual seats, cart, checkout, booking tracking |
| User account | Login, register, forgot password, reset password, profile, wishlist, bookings |
| Security UX | CSRF protection, min/max validation, password strength UI, disposable email blocking |
| Captcha | Google reCAPTCHA v2 checkbox and v3 score verification when keys are configured |
| Support | Detailed contact page, Pakistan map, message limits, placeholders, clean feedback |
| Legal content | Privacy policy, refund policy, terms of service with detailed tables and cards |
| Admin | Unified dashboard, theme selector, movie CRUD, media uploads, hero carousel, SEO, coupons, notifications |

## UI Highlights

| Detail | Implementation |
| --- | --- |
| 3D feel | Reusable `premium-tilt` and `auth-shell` hover transforms |
| Navigation clarity | Global breadcrumbs on every non-home page |
| Admin theming | Persistent admin theme selector and custom scrollbar styling |
| Auth polish | Social buttons, password visibility toggles, strength meter, captcha block |
| Contact UX | Pakistan service map, live message counter, validation limits |
| Legal readability | Tables, cards, sectioned policy groups, responsive layout |

## Tech Stack

| Layer | Tools |
| --- | --- |
| Backend | Laravel, PHP, Eloquent, Blade |
| Frontend | Tailwind CDN, Alpine.js, Chart.js on selected pages |
| Database | SQLite by default, MySQL compatible with Laravel config |
| Auth | Laravel session auth plus optional Socialite provider hooks |
| Security | CSRF, hashed passwords, validation rules, optional Google reCAPTCHA |

## Project Structure

```text
app/
  Http/Controllers/      Public, movie, auth, account, and admin controllers
  Models/                Movie, booking, user, contact, FAQ, theater models
  Support/FormSecurity.php
resources/views/
  auth/                  Login, register, forgot password, reset password
  public/                Home, contact, FAQ, about, compare, search
  public/static/         Privacy, refund, terms, e-ticket info
  components/            Navbar, cards, breadcrumbs, captcha, shared UI
routes/web.php           Public, auth, account, and admin web routes
database/                Migrations, seeders, SQL export
public/images/           Logos and public assets
```

## Setup

1. Install dependencies.

```bash
composer install
npm install
```

2. Create the environment file.

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure the database in `.env`.

```env
DB_CONNECTION=sqlite
```

For MySQL, set `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD`.

4. Run migrations and seeders.

```bash
php artisan migrate --seed
```

5. Start the app.

```bash
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Google reCAPTCHA

The project is captcha-ready but local development can run without keys. Add these values to `.env` to enable live verification:

```env
RECAPTCHA_V2_SITE_KEY=
RECAPTCHA_V2_SECRET_KEY=
RECAPTCHA_V3_SITE_KEY=
RECAPTCHA_V3_SECRET_KEY=
RECAPTCHA_V3_MIN_SCORE=0.5
```

Protected forms:

| Form | v3 action |
| --- | --- |
| Login | `user_login` |
| Register | `user_register` |
| Forgot password | `forgot_password` |
| Reset password | `reset_password` |
| Contact | `contact` |

## Social Login

Google and Facebook buttons are present in the auth UI. To enable OAuth, install/configure Socialite and set:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
```

## Validation Rules

| Field | Rules |
| --- | --- |
| Name | Required, 3 to 100 characters |
| Email | Required, valid email, 6 to 150 characters, disposable domains blocked |
| Phone | Optional, 10 to 20 characters, numbers and phone symbols only |
| Password | Required, 8 to 72 characters, confirmation required where applicable |
| Contact message | Required, 20 to 1,200 characters |

## Main Routes

| Route | Purpose |
| --- | --- |
| `/` | Home |
| `/movies` | Movie listing |
| `/movies/{slug}` | Movie details |
| `/movies/{slug}/book/{show}` | Seat selection |
| `/compare` | Movie comparison |
| `/contact` | Contact support |
| `/faq` | Help center |
| `/login` | User login |
| `/register` | User registration |
| `/account` | User dashboard |
| `/admin/dashboard` | Admin dashboard |

## Testing

```bash
php artisan test
```

## Seat Pricing

Seat prices are assigned per show and row. Row A is the highest front premium tier, then B, C, D, E, and F decrease progressively. Each row tier stores benefits such as priority entry, extra legroom, central sound coverage, family pricing, value seating, or group-friendly rear seating.

## Notes

This project was created by Syed Ahmer Shah for an Aptech DISM project. Policy pages and support workflows are written for project completeness and should be reviewed before any production deployment.
