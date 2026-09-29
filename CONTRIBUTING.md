# Contributing to BookMyMovie

Thanks for taking the time to look at the project. BookMyMovie is a finished portfolio project, feature-complete and mainly here to read, run and learn from. The repository is archived (read-only) and the site is not hosted; run it locally. Forks are welcome, and so are the notes below if you want to build on it.

> Found a security problem? Do **not** open an issue. Follow [SECURITY.md](SECURITY.md).

## Ways to help

| You want to… | Do this |
| --- | --- |
| Report a bug | Open an issue with steps to reproduce on `main` (if the repository is archived, describe it in your fork) |
| Fix something | Fork, branch, fix, and keep the change focused on one problem |
| Learn from it | Start with the [README](README.md): the data model, booking lifecycle, locking and caching sections explain the hard parts |
| Reuse code | It is MIT licensed; keep the copyright notice |

## Local setup

**Requirements:** PHP 8.2+ (`gd`, `intl`, `pdo_mysql`, `openssl`), Composer 2, Node 20+, MySQL 8 or MariaDB 10.4+.

```bash
git clone https://github.com/ahmershahdev/bookmymovie.git
cd bookmymovie
composer install
npm install
cp .env.example .env
php artisan key:generate
# set DB_DATABASE, DB_USERNAME, DB_PASSWORD in .env
php artisan migrate --seed
npm run dev          # in one terminal
php artisan serve    # in another
```

Optional services (reCAPTCHA, payments, wallet passes, web push) stay off until their keys are in `.env`; the site works without them. On `localhost`, set `RECAPTCHA_SKIP_GOOGLE_ON_LOCALHOST=true` to skip Google checks while developing.

## Branches and commits

- Branch from `main`: `fix/seat-map-focus`, `feat/imax-filter`, `docs/erd`.
- One logical change per commit. Write the subject in the imperative, under 72 characters: `Fix double refund when two admins cancel at once`.
- Explain *why* in the body when it is not obvious from the diff.
- `main` has linear history: rebase, don't merge.

## Code style

### PHP (Laravel)

- Follow the existing code: PSR-12, typed properties and return types, `final` is not used.
- Controllers stay thin. Rules that must hold together (cancel + refund + release seats) live in `app/Support` (see `BookingLifecycle`).
- Anything that changes money, seats or a status runs in `DB::transaction()` with row locks taken in the documented order: **user → show → seats → cart → booking → payment**. Status changes go through `Booking::claimStatus()`.
- Validate every input in the controller; never trust IDs from the client without scoping them to the signed-in user.
- Format with `vendor/bin/pint` before committing.

### TypeScript / React

- Strict TypeScript, function components, hooks. Pages live in `resources/js/pages`, shared pieces in `resources/js/components`.
- Use the design tokens (`bg-ink`, `text-paper`, `text-mute`, `accent`, …), never raw hex colours, so both themes keep working and stay above WCAG AA contrast.
- Every input needs a visible label and a placeholder showing an example value; every icon-only button needs an `aria-label`.
- Build to 320 px wide. Nothing may scroll sideways on a phone.
- Run `npx tsc --noEmit` before committing.

### Text

UI copy is plain, short and specific: "Seats are held for 10 minutes", not "Your selection has been temporarily reserved". English strings are the keys for Urdu (`resources/js/lang/ur.ts`); add the Urdu line when you add a customer-facing string.

## Tests

```bash
php artisan test
```

- Every bug fix comes with a test that fails before the fix.
- Booking, payment and admin-role changes need a feature test (see `tests/Feature/BookingFlowTest.php` for the pattern, including the race-condition tests).
- Frontend changes: run `npm run build` and check the page at 320, 768 and 1280 px in both themes.

## Pull request checklist

- [ ] `php artisan test` passes
- [ ] `npx tsc --noEmit` and `npm run build` pass
- [ ] New or changed UI checked on a phone width, in dark and light themes, with the keyboard
- [ ] No secrets, `.env` values or personal data in the diff
- [ ] README or docs updated if behaviour changed

## Code of conduct

Be kind, assume good intent, keep feedback about the code. Harassment of any kind is not tolerated.
