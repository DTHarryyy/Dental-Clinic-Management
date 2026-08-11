# Resend Deployment Checklist

Complete these steps before transactional emails can be sent in production. Resend is called only by the protected Supabase Edge Function.

## New Supabase production project

- [x] Initialize the `aquilizan` Tokyo project with the complete application schema, indexes, RLS, Laravel migration history, and default services.
- [ ] Replace `DB_PASSWORD` in the deployment environment with the password for project `kmgzcxuwlsplbuzbpame`.
- [ ] Replace `SUPABASE_SERVICE_ROLE_KEY` with the service-role secret for the same project. Never commit or paste it into chat.
- [ ] Install/enable PHP `pdo_pgsql`, then run `php artisan migrate:status` and confirm every migration is marked as run.
- [ ] Run `php artisan app:make-admin` once and enter the production administrator credentials privately.
- [ ] Confirm the administrator can log in and that its local `users.supabase_uid` matches the new Supabase Auth user ID.

## Resend account and domain

- [ ] Add and verify `aquilizan.com` in the Resend dashboard.
- [ ] Add the DNS records supplied by Resend and wait for verification to complete.
- [ ] Create a sending-only Resend API key for the production application.
- [x] Deploy the protected `send-transactional-email` Edge Function with JWT verification enabled.
- [ ] In Supabase Dashboard, add `RESEND_API_KEY` under Edge Function secrets. Never place this key in Laravel or commit it.

## Production environment

- [ ] Set the following values in the production environment:

```dotenv
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=appointments@aquilizan.com
MAIL_FROM_NAME="Aquilizan Dental Clinic"
SUPABASE_EMAIL_FUNCTION=send-transactional-email
APP_URL=https://aquilizan.com
QUEUE_CONNECTION=redis
```

## Redis and navigation performance

- [ ] Provision highly available managed Redis in Tokyo, near the application server and Supabase.
- [ ] Confirm PhpRedis is enabled; if unavailable, set `REDIS_CLIENT=predis` (the package is included as a portable fallback).
- [ ] Configure `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `SESSION_CONNECTION=session`, `QUEUE_CONNECTION=redis`, and `REDIS_QUEUE_CONNECTION=queue`.
- [ ] Configure separate cache, session, and queue Redis databases/prefixes. Do not use Supabase tables for these runtime concerns.
- [ ] Run `php artisan app:performance-check` and confirm the Supabase and all three Redis checks succeed with low latency.
- [ ] Keep the Supabase direct connection when IPv6 is available; otherwise use the Tokyo session pooler on port 5432. Do not use transaction pooling on port 6543 for this persistent server.
- [ ] Enable `DB_PERSISTENT=true` only after confirming the PHP worker count remains below the Supabase connection-pool limit.
- [ ] Set `TURBO_ENABLED=true` after smoke-testing navigation. Set it to `false` and rebuild configuration for immediate traditional-navigation fallback.
- [ ] Run `npm ci && npm run build`; verify Turbo navigation, back/forward, filters, pagination, dialogs, validation, logout, and PDF/print downloads.
- [ ] Warm dashboard, billing, patient, appointment, and report pages, then restart both PHP and queue workers.
- [ ] Verify cached page p95 TTFB is below 300 ms, uncached primary pages below 600 ms, and primary lists remain within five business-data queries.

- [ ] Confirm the clinic name, email, phone, address, and website are filled in under Clinic Settings. The clinic email is used as the confirmation email's reply-to address.
- [ ] Remove any old `RESEND_API_KEY` from Laravel `.env` after the Edge Function test succeeds.
- [ ] Never commit the Resend API key or Supabase service-role key to Git.

## Deploy and migrate

- [ ] Before migrating, identify any existing invoices marked `partial` and record the actual amount already paid for manual reconciliation. The migration logs their invoice IDs but does not invent payment amounts.

- [ ] Install production dependencies:

```shell
composer install --no-dev --optimize-autoloader
```

- [ ] Apply all outstanding migrations:

```shell
php artisan migrate --force
```

- [ ] Confirm the migration created `email_deliveries`, added `users.must_change_password`, and preserved the billing/appointment tracking tables.
- [ ] Confirm `barryvdh/laravel-dompdf` is installed by the production Composer install.
- [ ] Confirm the latency-index migration completed, including `pg_trgm` patient search indexes.

- [ ] Refresh Laravel's cached configuration:

```shell
php artisan optimize:clear
php artisan config:cache
```

- [ ] Start or restart a persistent queue worker using the production host's process manager:

```shell
php artisan queue:work --tries=3 --timeout=120
```

## Live verification

- [ ] Submit a public test booking using an email address you can access.
- [ ] Confirm the booking-received email arrives once.
- [ ] Approve the pending appointment once.
- [ ] Confirm the approval completes immediately.
- [ ] Confirm the queued job is processed and the email arrives.
- [ ] Check that the message is from `Aquilizan Dental Clinic <appointments@aquilizan.com>` and that replies go to the configured clinic email.
- [ ] Verify the email contains the correct reference, service, date, time, dentist when assigned, and clinic contact details.
- [ ] Verify the patient's submitted concern is not included.
- [ ] Confirm `confirmation_email_sent_at` and `confirmation_email_message_id` are populated and `confirmation_email_error` is empty.
- [ ] Confirm Resend shows the message as accepted or delivered.
- [ ] Complete the confirmed appointment, create its invoice, and verify the invoice email arrives with a readable PDF attachment.
- [ ] Record a partial payment and verify an updated invoice is sent with the correct paid amount and balance.
- [ ] Record the final payment and verify a receipt is sent with complete payment history and a zero balance.
- [ ] Verify billing delivery history shows the recipient, document type, status, and Resend message ID.
- [ ] Create an active staff account and confirm its generated temporary credentials arrive; sign in and replace the password through the profile prompt.
- [ ] Request a password reset, confirm the response does not reveal whether an account exists, and use the single-use link successfully.
- [ ] Cancel a test appointment and confirm the cancellation message arrives once.
- [ ] Verify concerns, clinical notes, prescriptions, and medical information do not appear in either email or PDF.

## If delivery fails

- [ ] Inspect the failed queue jobs and application logs.
- [ ] Inspect `confirmation_email_error` on the appointment.
- [ ] Confirm the API key, verified sender domain, queue worker, and cached environment configuration.
- [ ] Retry a failed queue job only after correcting the underlying configuration:

```shell
php artisan queue:retry all
```
