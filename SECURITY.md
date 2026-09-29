# Security policy

BookMyMovie handles accounts, bookings and payments, so security reports are taken seriously and answered quickly.

## Supported versions

| Version | Supported |
| --- | --- |
| `main` (latest) | Yes |
| Anything older | No: please reproduce on `main` first |

BookMyMovie is a finished portfolio project. Security reports are still welcome for `main`, even if the repository is archived.

## Reporting a vulnerability

**Please do not open a public issue, pull request or discussion for a security problem.**

Report it privately in one of these ways:

1. **GitHub private vulnerability reporting**: the repository's *Security* tab → *Report a vulnerability*.
2. **Email**: [support@ahmershah.dev](mailto:support@ahmershah.dev) with the subject `SECURITY: BookMyMovie`.

Include as much of this as you can:

- what the problem is and what an attacker could do with it (the impact);
- the exact steps, request or script to reproduce it, against a local install;
- the affected route, file or component, and the commit you tested;
- whether you believe it is being exploited anywhere.

### What to expect

| Step | Target time |
| --- | --- |
| Acknowledgement of your report | 3 working days |
| First assessment (confirmed / need more info / not a vulnerability) | 7 working days |
| Fix for a confirmed high or critical issue | 30 days |
| Public disclosure | After the fix ships, credited to you unless you prefer otherwise |

## Scope

In scope: the code in this repository, including the Laravel backend, the React front end, the service worker (`public/sw.js`), database migrations and the default configuration.

Out of scope:

- the live demo's hosting, DNS, e-mail or third-party services (Google reCAPTCHA, JazzCash, Easypaisa, Resend, wallet providers);
- denial of service, load or volumetric testing;
- social engineering, phishing or physical attacks;
- reports from automated scanners without a demonstrated impact;
- missing best-practice headers on static files where there is no exploitable effect;
- the seeded demo credentials (`admin@bookmymovie.ahmershah.dev` / `Admin@1234`) on a local install: change them with `BOOKMYMOVIE_ADMIN_PASSWORD` before deploying.

### Safe harbour

Testing done in good faith, on your own local install, that does not access other people's data, degrade the service or keep data longer than needed to show the problem, will not lead to any complaint or legal action from the author.

## How the application defends itself

Knowing what is already in place helps you focus on what might be missing.

| Area | Controls |
| --- | --- |
| Authentication | Argon2id password hashing, optional e-mailed 6-digit sign-in codes for customers, TOTP two-step sign-in with recovery codes for staff, session regeneration on sign-in |
| Authorisation | Admin roles (owner / manager / box office) enforced per route by `AuthorizeAdminRole`; every customer query is scoped to the signed-in user (bookings, tickets, carts, reviews), so a guessed booking number returns 404 |
| Bot and abuse protection | Honeypot, minimum fill time, a server-drawn single-use image code, Google reCAPTCHA v3 with a v2 checkbox step-up, rate limits on every sensitive route, account + IP + device bans, disposable e-mail blocking |
| Booking integrity | Row locks in a fixed order, a booking status state machine with compare-and-set updates, a database unique key that makes double-selling a seat impossible, idempotency keys on checkout |
| Payments | Amounts recomputed on the server, signed gateway callbacks verified before any state change, payment and booking rows locked while settling |
| Tickets | QR payloads carry an HMAC signature derived from `APP_KEY`; the box office recomputes it before admitting anyone |
| Browser | Strict nonce-based Content-Security-Policy, HSTS, `X-Frame-Options: DENY`, `nosniff`, a restrictive `Permissions-Policy`, CSRF tokens on every form |
| Uploads | Images are re-encoded to WebP (which drops EXIF/GPS data), size-capped, and never executed (`storage/` blocks script extensions) |
| Privacy | Personal assistant data is sent `Cache-Control: private, no-store`; saved offline tickets are wiped from the device on sign-out |
| Audit | Every data-changing action by staff and customers is written to `audit_logs` with before/after values; failed captchas and bans to `security_events` |

## Handling secrets

- Never commit `.env`. Keys for reCAPTCHA, payments, wallet passes, web push (`VAPID_*`) and mail belong only there.
- Rotate `APP_KEY` only with care: it signs ticket QR codes and encrypts TOTP secrets.
- If you find a secret committed anywhere in this repository's history, report it privately as above.
