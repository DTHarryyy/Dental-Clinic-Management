---
name: verify
description: Build, run, and drive this Laravel dental clinic app in a real browser to observe a change working end-to-end. Use when verifying a change that has a runtime surface (controllers, blade views, dialog JS).
---

# Verifying changes in Dental-Clinic-Management

Laravel 12 + Blade + Alpine.js + Hotwired Turbo + Tailwind 4 + Chart.js, built through
**Vite** (`resources/css/app.css`, `resources/js/app.js` → `public/build/`).
**There is a build step.** Run `npm run build` after touching anything under
`resources/js` or `resources/css` — the server reads the compiled `public/build/manifest.json`,
not the source files, so a driving session on stale assets silently exercises old JS.

## Never drive against the .env database

`.env` points `DB_CONNECTION=pgsql` at the **remote Supabase** project. It is the
real database and it is slow over the wire. Always override to the local SQLite
file, which is already migrated and seeded with realistic data (~120 patients,
~225 appointments, 8 services, 4 dentists):

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='D:\Projects\Dental-Clinic-Management\database\database.sqlite'
$env:CACHE_STORE='array'    # NOT optional - see below
```

Set these in **every** PowerShell call that touches the DB — shell state does not
persist between tool calls. `php artisan migrate:fresh --seed` restores the local
file if driving leaves it messy.

Note `php artisan migrate` with no override applies to Supabase. That is sometimes
what you want; it is never what you want by accident.

### The cache is partitioned per database — keep it that way

`config/cache.php` fingerprints `DB_CONNECTION|DB_HOST|DB_PORT|DB_DATABASE` into
both `cache.prefix` and the file store's `path`, so each database gets its own
directory (`storage/framework/cache/data/pgsql-8e432643/` vs
`.../sqlite-a2d969a9/`). Pointing a process at SQLite can no longer overwrite what
the Supabase-backed app reads.

This exists because it *did* happen (2026-07-16): a verification run against local
SQLite wrote its seeded fake dentists into the shared `users:dentists` key, and
since `User::cachedDentists()` / `Patient::dropdown()` / `Service::cached()` are
`rememberForever` with event-based invalidation, nothing ever evicted them — a model
event cannot fire for data in another database. The appointment form showed four
dentists that did not exist in Supabase until the user noticed.

`$env:CACHE_STORE='array'` is still worth setting when driving (it keeps the run
hermetic and avoids leaving a stale partition behind), but it is now belt-and-braces
rather than the thing standing between you and corrupting the real app.

Note the file store **ignores `cache.prefix` entirely** (`FileStore::getPrefix()`
returns `''`) — the path partition is what actually does the work here. Do not
"simplify" the config by dropping the path and keeping only the prefix.

Still true: anyone seeding or editing Supabase directly via SQL or the dashboard
gets no model events, so the forever-cache goes stale silently. `cache:clear` is
the fix, and it now clears only the current database's partition.

## Launch

```powershell
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE='D:\Projects\Dental-Clinic-Management\database\database.sqlite'
php artisan config:clear
php artisan serve --port=8741     # run_in_background
```

## Get a login

Seeded users have unknown passwords. Mint one:

```powershell
php artisan tinker --execute="`$u = App\Models\User::where('role','dentist')->first(); `$u->update(['password' => Illuminate\Support\Facades\Hash::make('password'), 'status' => 'active']); echo `$u->email;"
```

Roles matter and gate the UI: `admin`, `dentist`, `receptionist`. Records routes are
`role:admin,dentist`; appointments are open to all three. Verify role-dependent UI as
both a dentist and a receptionist.

### Patient portal login

Don't mint a patient login by hand-setting a local `password` — patients authenticate
through Supabase, and local `users.password` is always `null` for them
(`SupabaseAuth::signIn`). Two better options:

- **`GET /__e2e/login/{role}?token=...`** — only registered when `app()->environment('e2e')`
  (`routes/web.php:35-45`), guarded by `E2E_AUTH_TOKEN`. It logs in the first `active` user
  of the given role with no Supabase round trip at all — works for `admin`, `dentist`,
  `receptionist`, and `patient`. Launch with `APP_ENV=e2e` and `E2E_AUTH_TOKEN=<anything>`
  set alongside the SQLite overrides.
- `DatabaseSeeder` seeds `Patient` rows and staff `User` rows, but **no patient-role
  `User`** — the portal needs one linked (`patient_id` set) and verified
  (`email_verified_at` not null) or you'll bounce to `verify-email`/`account-review`. Mint
  one with `User::create([...])` then **`forceFill(['email_verified_at' => now()])->save()`**
  — `email_verified_at` is not in `User::$fillable`, so passing it to `create()` silently
  drops it and every "verified" assumption downstream quietly fails.

## Browser

`@playwright/test` is already a devDependency (for `npm run test:e2e`) and its nested
`playwright` package exposes `import { chromium } from 'playwright'` with no extra
`npm install` needed — only the browser **binary** is typically missing:

```powershell
npx playwright install chromium
```

If `node_modules` isn't present at all, `npm install` first (touches nothing extra —
`playwright` is already declared). Only fall back to `npm install --no-save playwright@latest`
if `require.resolve('playwright')` actually fails.

**The drive script must live in the project root** (e.g. `_drive.mjs`, deleted after) —
Node resolves ESM imports relative to the script, so a script in the scratchpad cannot
`import { chromium } from 'playwright'`. Clean up with `Remove-Item _drive*.mjs`.

## Gotchas that cost time

- **Dialog forms submit via fetch, not navigation.** `public/js/dialog-forms.js`
  intercepts `[data-ajax-form]`, and on success does `window.location.href = data.redirect`
  (the controller returns `{redirect: url}` via `Controller::respond` so the session
  flash survives). So `page.waitForURL('**/appointments*')` resolves *instantly* when you
  are already on that URL — it does not wait for the submit. Use `waitForTimeout` or wait
  on a response, or a subsequent `page.goto` will abort the in-flight navigation and you
  will wrongly conclude the submit failed. Check the DB to confirm what actually landed.
- **Turbo intercepts plain `<form>` submits too, not just `[data-ajax-form]` dialogs.**
  Hotwired Turbo is loaded globally, so an ordinary `<form method="POST">` (e.g. the
  patient booking form, the withdraw button) is submitted via `fetch` + `pushState`
  under the hood, not a real browser navigation. A fixed `waitForTimeout` after `.click()`
  races the server's actual processing time (a booking POST — creation, notification,
  email dispatch — took ~1s in practice) and can read a stale `page.url()`. Use
  `page.waitForURL(/pattern/, { timeout })` instead of a blind wait, and don't fire a
  `page.goto()` for the next step immediately after — it can outrace Turbo's own
  in-flight fetch on the single-threaded `artisan serve` dev server, and the two requests
  land out of the order you'd expect from the script alone. Confirm success from the DB
  when in doubt, same as the dialog-forms case above.
- **Checkboxes styled `sr-only` need `{ force: true }`.** The booking form's service
  checkboxes are visually hidden with their `<label>` as the clickable surface
  (`resources/views/patient/appointments/create.blade.php`) — a normal Playwright
  `.click()` on the `<input>` fails with "element intercepts pointer events" because the
  label sits on top of it. `page.locator('[data-booking-service]').click({ force: true })`
  dispatches the click directly and still fires the native `change` event the JS listens for.
- **Decimals differ by driver.** `services.price` has no model cast, so Postgres yields
  `"1500.00"` (string) and SQLite `1500` (int). Never assert on the formatted value.
- **Pagination is 9/page.** A row you seeded may not be on page 1; filter with
  `?search=` (matches `full_name` and `service`) rather than hunting.
- Toasts/flash render into the page body; assert on `body.innerText()`.

## Worth driving

- `/appointments` — the status lifecycle (Confirm → Complete → Cancel) and the
  Complete dialog, which is where most of the logic lives.
- `/records`, `/billing` — creation dialogs with the same ajax-form mechanics.
- Any change to `Service` — the catalog is cached (`Cache::rememberForever`,
  event-invalidated) and is the single source of truth validated against on every
  booking, so check a stale/deleted service path too. **Booking also needs at least one
  active dentist** — `AppointmentScheduler` computes availability from
  `dentistCount - busy - held`, so zero active dentists makes every slot look "fully
  booked" rather than "no dentist yet" (fixed 2026-09-17 to say the latter explicitly).
- The **patient portal** (`/patient/*`) — log in via the `e2e` route above. Full walk in
  order: dashboard → appointments index → an appointment's show page → withdraw a pending
  one / request a change on a confirmed one → book a new appointment (services → date →
  slot → submit) → treatments → billing + receipt → notifications → profile (details +
  security). All 13 features were driven end-to-end this way on 2026-09-17; every check
  passed once the gotchas above were accounted for.
