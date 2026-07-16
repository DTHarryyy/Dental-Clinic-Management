---
name: verify
description: Build, run, and drive this Laravel dental clinic app in a real browser to observe a change working end-to-end. Use when verifying a change that has a runtime surface (controllers, blade views, dialog JS).
---

# Verifying changes in Dental-Clinic-Management

Laravel 11 + Blade + Alpine.js + Tailwind (CDN) + vanilla JS in `public/js/`.
No build step — Blade and `public/js/*.js` are served as-is, so there is nothing
to compile before driving the app.

## Never drive against the .env database

`.env` points `DB_CONNECTION=pgsql` at the **remote Supabase** project. It is the
real database and it is slow over the wire. Always override to the local SQLite
file, which is already migrated and seeded with realistic data (~120 patients,
~225 appointments, 8 services, 4 dentists):

```powershell
$env:DB_CONNECTION='sqlite'
$env:DB_DATABASE='C:\Projects\Dental-Clinic-Management\database\database.sqlite'
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
$env:DB_CONNECTION='sqlite'; $env:DB_DATABASE='C:\Projects\Dental-Clinic-Management\database\database.sqlite'
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

## Browser

Playwright is not a project dependency. Install it without touching package.json,
and keep the browser binaries out of the repo:

```powershell
$env:PLAYWRIGHT_BROWSERS_PATH='<scratchpad>\pw-browsers'
npm install --no-save playwright@latest
npx playwright install chromium
```

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
  booking, so check a stale/deleted service path too.
