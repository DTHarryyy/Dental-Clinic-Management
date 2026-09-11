@extends('layouts.public')

@section('page_title', $site->meta_title ?: 'Aquilizan Dental Clinic')
@section('meta_description', $site->meta_description ?: 'Book verified dental appointments and access patient records securely.')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Dentist',
    'name' => $clinic->clinic_name ?: 'Aquilizan Dental Clinic',
    'address' => $clinic->address,
    'telephone' => $clinic->phone,
    'email' => $clinic->email,
    'url' => url('/'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<style>
    :root {
        --dc-ink: #0f172a;
        --dc-ink-2: #1e293b;
        --dc-muted: #64748b;
        --dc-line: #e2e8f0;
        --dc-soft: #f8fafc;
        --dc-soft-2: #f1f5f9;
        --dc-white: #ffffff;
        --dc-green: #10b981;
        --dc-green-dark: #059669;
        --dc-green-soft: #ecfdf5;
        --dc-teal: #0f766e;
        --dc-navy: #020617;
        --dc-shadow: 0 18px 55px rgba(15, 23, 42, .10);
        --dc-shadow-soft: 0 10px 30px rgba(15, 23, 42, .07);
        --dc-radius: 24px;
        --dc-container: 1180px;
    }

    html { scroll-behavior: smooth; }

    .dc-page,
    .dc-page * { box-sizing: border-box; }

    .dc-page {
        width: 100%;
        min-height: 100vh;
        overflow-x: hidden;
        background: var(--dc-white);
        color: var(--dc-ink);
        font-family: inherit;
    }

    .dc-container {
        width: min(calc(100% - 40px), var(--dc-container));
        margin-inline: auto;
    }

    .dc-section {
        padding: 104px 0;
    }

    .dc-eyebrow {
        margin: 0;
        color: var(--dc-green-dark);
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .16em;
        text-transform: uppercase;
    }

    .dc-section-title {
        max-width: 720px;
        margin: 12px auto 0;
        color: var(--dc-ink);
        font-size: clamp(32px, 4vw, 52px);
        line-height: 1.06;
        letter-spacing: -.04em;
        font-weight: 850;
    }

    .dc-section-copy {
        max-width: 680px;
        margin: 20px auto 0;
        color: var(--dc-muted);
        font-size: 17px;
        line-height: 1.75;
    }

    .dc-section-head { text-align: center; }

    .dc-btn {
        display: inline-flex;
        min-height: 48px;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 20px;
        border: 1px solid transparent;
        border-radius: 14px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 800;
        line-height: 1;
        white-space: nowrap;
        transition: transform .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .dc-btn:hover { transform: translateY(-1px); }

    .dc-btn-primary {
        background: var(--dc-green);
        color: #fff;
        box-shadow: 0 8px 22px rgba(16, 185, 129, .20);
    }

    .dc-btn-primary:hover { background: var(--dc-green-dark); }

    .dc-btn-secondary {
        border-color: var(--dc-line);
        background: #fff;
        color: var(--dc-ink-2);
        box-shadow: 0 4px 14px rgba(15, 23, 42, .05);
    }

    .dc-btn-secondary:hover {
        border-color: #cbd5e1;
        background: var(--dc-soft);
    }

    /* NAV */
    .dc-nav-wrap {
        position: sticky;
        top: 0;
        z-index: 50;
        border-bottom: 1px solid rgba(226, 232, 240, .9);
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(16px);
    }

    .dc-nav {
        min-height: 76px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 28px;
    }

    .dc-brand {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--dc-ink);
        text-decoration: none;
    }

    .dc-brand img {
        width: 46px;
        height: 46px;
        flex: 0 0 46px;
        padding: 4px;
        border: 1px solid var(--dc-line);
        border-radius: 14px;
        background: #fff;
        object-fit: contain;
    }

    .dc-brand-copy { min-width: 0; }
    .dc-brand-name {
        display: block;
        max-width: 280px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-size: 15px;
        font-weight: 850;
    }
    .dc-brand-sub {
        display: block;
        margin-top: 2px;
        color: var(--dc-muted);
        font-size: 12px;
        white-space: nowrap;
    }

    .dc-nav-links {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 26px;
        margin-left: auto;
    }

    .dc-nav-links a {
        color: #475569;
        text-decoration: none;
        font-size: 13px;
        font-weight: 750;
        transition: color .18s ease;
    }
    .dc-nav-links a:hover { color: var(--dc-green-dark); }

    .dc-nav-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 0 0 auto;
    }

    .dc-signin {
        color: var(--dc-ink-2);
        text-decoration: none;
        font-size: 13px;
        font-weight: 800;
        padding: 12px 10px;
    }

    /* HERO */
    .dc-hero {
        position: relative;
        overflow: hidden;
        background:
            radial-gradient(circle at 8% 12%, rgba(16, 185, 129, .10), transparent 28%),
            radial-gradient(circle at 92% 8%, rgba(13, 148, 136, .08), transparent 27%),
            #fff;
    }

    .dc-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        opacity: .5;
        background-image:
            linear-gradient(to right, rgba(148, 163, 184, .10) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(148, 163, 184, .10) 1px, transparent 1px);
        background-size: 52px 52px;
        mask-image: linear-gradient(to bottom, #000 0%, transparent 90%);
    }

    .dc-hero-inner {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.03fr) minmax(0, .97fr);
        align-items: center;
        gap: 72px;
        padding-top: 92px;
        padding-bottom: 92px;
    }

    .dc-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border: 1px solid #a7f3d0;
        border-radius: 999px;
        background: var(--dc-green-soft);
        color: #047857;
        font-size: 12px;
        font-weight: 800;
        box-shadow: 0 4px 10px rgba(16, 185, 129, .08);
    }

    .dc-pill-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--dc-green);
    }

    .dc-hero h1 {
        max-width: 700px;
        margin: 24px 0 0;
        color: #020617;
        font-size: clamp(46px, 5.3vw, 74px);
        line-height: 1.01;
        letter-spacing: -.055em;
        font-weight: 900;
    }

    .dc-hero-copy {
        max-width: 620px;
        margin: 24px 0 0;
        color: #475569;
        font-size: 18px;
        line-height: 1.75;
    }

    .dc-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 32px;
    }

    .dc-trust-row {
        display: flex;
        flex-wrap: wrap;
        gap: 14px 24px;
        margin-top: 28px;
        color: #475569;
        font-size: 13px;
        font-weight: 650;
    }

    .dc-trust-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .dc-trust-item i { color: var(--dc-green); }

    .dc-hero-card {
        position: relative;
        min-height: 500px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 32px;
        background: #081226;
        box-shadow: 0 28px 80px rgba(2, 6, 23, .22);
    }

    .dc-hero-card > img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .dc-hero-card-shade {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(2,6,23,.92), rgba(2,6,23,.12) 58%, rgba(2,6,23,.12));
    }

    .dc-hero-art {
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 24% 18%, rgba(16,185,129,.40), transparent 30%),
            radial-gradient(circle at 78% 68%, rgba(20,184,166,.26), transparent 34%),
            linear-gradient(145deg, #0f172a, #020617);
    }

    .dc-hero-art-grid {
        position: absolute;
        inset: 0;
        opacity: .15;
        background-image:
            linear-gradient(to right, rgba(255,255,255,.2) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(255,255,255,.2) 1px, transparent 1px);
        background-size: 44px 44px;
    }

    .dc-hero-card-top {
        position: absolute;
        top: 26px;
        left: 26px;
        right: 26px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    .dc-tooth-box {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 17px;
        background: rgba(255,255,255,.10);
        color: #a7f3d0;
        font-size: 24px;
        backdrop-filter: blur(10px);
    }

    .dc-card-chip {
        padding: 8px 12px;
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 999px;
        background: rgba(255,255,255,.08);
        color: #cbd5e1;
        font-size: 11px;
        font-weight: 700;
        backdrop-filter: blur(10px);
    }

    .dc-hero-card-copy {
        position: absolute;
        left: 30px;
        right: 30px;
        bottom: 118px;
        color: #fff;
    }

    .dc-hero-card-copy h2 {
        max-width: 430px;
        margin: 0;
        font-size: clamp(28px, 3vw, 40px);
        line-height: 1.08;
        letter-spacing: -.03em;
        font-weight: 850;
    }

    .dc-hero-card-copy p {
        max-width: 440px;
        margin: 14px 0 0;
        color: #cbd5e1;
        font-size: 14px;
        line-height: 1.65;
    }

    .dc-hero-mini {
        position: absolute;
        left: 24px;
        right: 24px;
        bottom: 22px;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 14px;
        border: 1px solid rgba(255,255,255,.12);
        border-radius: 18px;
        background: rgba(2,6,23,.72);
        color: #fff;
        backdrop-filter: blur(12px);
    }

    .dc-hero-mini-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;

        display: flex !important;
        align-items: center !important;
        justify-content: center !important;

        position: relative;
        flex-shrink: 0;

        border-radius: 14px;
        background: #10b981;
    }
    .dc-hero-mini-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;

        display: flex !important;
        align-items: center !important;
        justify-content: center !important;

        position: relative;
        flex-shrink: 0;

        border-radius: 14px;
        background: #10b981;
    }
    .dc-hero-mini strong {
        display: block;
        font-size: 13px;
    }
    .dc-hero-mini span {
        display: block;
        margin-top: 3px;
        color: #cbd5e1;
        font-size: 11px;
        line-height: 1.45;
    }

    /* STATS */
    .dc-stats-wrap {
        border-top: 1px solid var(--dc-line);
        border-bottom: 1px solid var(--dc-line);
        background: var(--dc-soft);
    }

    .dc-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        border-left: 1px solid var(--dc-line);
    }

    .dc-stat {
        min-width: 0;
        padding: 30px 20px;
        border-right: 1px solid var(--dc-line);
        background: #fff;
        text-align: center;
    }
    .dc-stat strong {
        display: block;
        color: var(--dc-ink);
        font-size: 28px;
        line-height: 1;
    }
    .dc-stat span {
        display: block;
        margin-top: 9px;
        color: var(--dc-muted);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .11em;
        text-transform: uppercase;
    }

    /* SERVICES */
    .dc-services { background: #fff; }

    .dc-card-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 24px;
        margin-top: 48px;
    }

    .dc-service-card {
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--dc-line);
        border-radius: var(--dc-radius);
        background: #fff;
        box-shadow: var(--dc-shadow-soft);
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }
    .dc-service-card:hover {
        transform: translateY(-4px);
        border-color: #a7f3d0;
        box-shadow: var(--dc-shadow);
    }

    .dc-service-media {
        position: relative;
        width: 100%;
        aspect-ratio: 16 / 10;
        overflow: hidden;
        background: linear-gradient(145deg, #ecfdf5, #f8fafc);
    }
    .dc-service-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .dc-service-placeholder {
        width: 100%;
        height: 100%;
        display: grid;
        place-items: center;
    }
    .dc-service-placeholder span {
        width: 72px;
        height: 72px;
        display: grid;
        place-items: center;
        border: 1px solid #d1fae5;
        border-radius: 22px;
        background: #fff;
        color: var(--dc-green-dark);
        font-size: 28px;
        box-shadow: 0 8px 22px rgba(15,23,42,.06);
    }
    .dc-duration {
        position: absolute;
        top: 14px;
        left: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 10px;
        border: 1px solid rgba(255,255,255,.7);
        border-radius: 999px;
        background: rgba(255,255,255,.92);
        color: #334155;
        font-size: 11px;
        font-weight: 800;
        box-shadow: 0 6px 18px rgba(15,23,42,.07);
    }

    .dc-service-body {
        min-width: 0;
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 24px;
    }
    .dc-service-body h3 {
        margin: 0;
        overflow-wrap: anywhere;
        color: var(--dc-ink);
        font-size: 20px;
        line-height: 1.3;
        letter-spacing: -.02em;
        font-weight: 850;
    }
    .dc-service-body p {
        margin: 12px 0 0;
        overflow-wrap: anywhere;
        color: var(--dc-muted);
        font-size: 14px;
        line-height: 1.7;
    }
    .dc-service-desc {
        display: -webkit-box;
        overflow: hidden;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }
    .dc-service-footer {
        margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid var(--dc-line);
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 16px;
    }
    .dc-price-label {
        color: #94a3b8;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }
    .dc-price {
        display: block;
        margin-top: 5px;
        color: var(--dc-ink);
        font-size: 17px;
        font-weight: 850;
    }
    .dc-arrow-btn {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: var(--dc-ink);
        color: #fff;
        text-decoration: none;
        transition: background .18s ease, transform .18s ease;
    }
    .dc-arrow-btn:hover {
        transform: translateX(2px);
        background: var(--dc-green-dark);
    }
    .dc-center-cta { margin-top: 38px; text-align: center; }

    /* ABOUT */
    .dc-about {
        position: relative;
        overflow: hidden;
        background: var(--dc-navy);
        color: #fff;
    }
    .dc-about::before {
        content: "";
        position: absolute;
        width: 500px;
        height: 500px;
        right: -180px;
        top: -180px;
        border-radius: 50%;
        background: rgba(16,185,129,.12);
        filter: blur(40px);
    }
    .dc-about-grid {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
        align-items: center;
        gap: 72px;
    }
    .dc-about .dc-eyebrow { color: #6ee7b7; }
    .dc-about h2 {
        margin: 14px 0 0;
        color: #fff;
        font-size: clamp(34px, 4vw, 52px);
        line-height: 1.08;
        letter-spacing: -.04em;
        font-weight: 850;
    }
    .dc-about-copy {
        margin: 22px 0 0;
        color: #cbd5e1;
        font-size: 17px;
        line-height: 1.8;
    }
    .dc-about-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 30px;
    }
    .dc-btn-dark-secondary {
        border-color: rgba(255,255,255,.18);
        background: rgba(255,255,255,.06);
        color: #fff;
    }
    .dc-benefits {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
    .dc-benefit {
        min-width: 0;
        min-height: 165px;
        padding: 24px;
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 20px;
        background: rgba(255,255,255,.055);
        backdrop-filter: blur(8px);
    }
    .dc-benefit-icon {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: rgba(52,211,153,.14);
        color: #6ee7b7;
    }
    .dc-benefit p {
        margin: 18px 0 0;
        color: #f8fafc;
        font-size: 14px;
        line-height: 1.6;
        font-weight: 750;
        overflow-wrap: anywhere;
    }

    /* TEAM */
    .dc-team { background: var(--dc-soft); }
    .dc-team-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 24px;
        margin-top: 48px;
    }
    .dc-team-card {
        min-width: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        padding: 28px;
        border: 1px solid var(--dc-line);
        border-radius: var(--dc-radius);
        background: #fff;
        box-shadow: var(--dc-shadow-soft);
    }
    .dc-team-top {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
    }
    .dc-avatar {
        width: 64px;
        height: 64px;
        flex: 0 0 64px;
        display: grid;
        place-items: center;
        overflow: hidden;
        border-radius: 18px;
        background: #d1fae5;
        color: #047857;
        font-size: 18px;
        font-weight: 850;
    }
    .dc-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .dc-team-card h3 {
        margin: 0;
        color: var(--dc-ink);
        font-size: 18px;
        line-height: 1.3;
        font-weight: 850;
        overflow-wrap: anywhere;
    }
    .dc-team-title {
        margin: 5px 0 0;
        color: #047857;
        font-size: 13px;
        font-weight: 700;
    }
    .dc-team-specialties {
        margin: 18px 0 0;
        color: #475569;
        font-size: 12px;
        font-weight: 750;
        line-height: 1.6;
        overflow-wrap: anywhere;
    }
    .dc-team-bio {
        display: -webkit-box;
        overflow: hidden;
        margin: 12px 0 0;
        color: var(--dc-muted);
        font-size: 14px;
        line-height: 1.7;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
    }

    /* HOW IT WORKS */
    .dc-booking { background: #fff; }
    .dc-steps {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 46px;
    }
    .dc-step {
        min-width: 0;
        padding: 26px;
        border: 1px solid var(--dc-line);
        border-radius: 22px;
        background: #fff;
        box-shadow: var(--dc-shadow-soft);
    }
    .dc-step-number {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        background: var(--dc-green-soft);
        color: #047857;
        font-size: 13px;
        font-weight: 900;
    }
    .dc-step h3 {
        margin: 20px 0 0;
        color: var(--dc-ink);
        font-size: 18px;
        font-weight: 850;
    }
    .dc-step p {
        margin: 10px 0 0;
        color: var(--dc-muted);
        font-size: 14px;
        line-height: 1.7;
    }

    /* HOURS */
    .dc-hours-section {
        padding: 0 0 104px;
        background: #fff;
    }
    .dc-hours-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        align-items: stretch;
        gap: 24px;
    }
    .dc-hours-card,
    .dc-book-card {
        min-width: 0;
        border-radius: 28px;
    }
    .dc-hours-card {
        padding: 30px;
        border: 1px solid var(--dc-line);
        background: var(--dc-soft);
    }
    .dc-hours-card h3,
    .dc-book-card h3 {
        margin: 0;
        font-size: 27px;
        line-height: 1.2;
        letter-spacing: -.03em;
        font-weight: 850;
    }
    .dc-closure {
        margin-top: 18px;
        padding: 12px 14px;
        border: 1px solid #fde68a;
        border-radius: 12px;
        background: #fffbeb;
        color: #92400e;
        font-size: 13px;
        line-height: 1.5;
    }
    .dc-hours-list {
        margin: 22px 0 0;
        display: grid;
        gap: 8px;
    }
    .dc-hour-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 12px 14px;
        border: 1px solid var(--dc-line);
        border-radius: 12px;
        background: #fff;
        font-size: 13px;
    }
    .dc-hour-row dt { color: #334155; font-weight: 800; }
    .dc-hour-row dd { margin: 0; color: var(--dc-muted); font-weight: 650; text-align: right; }

    .dc-book-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 38px;
        background: var(--dc-navy);
        color: #fff;
        box-shadow: 0 24px 60px rgba(2,6,23,.16);
    }
    .dc-book-icon {
        width: 56px;
        height: 56px;
        display: grid;
        place-items: center;
        border-radius: 16px;
        background: var(--dc-green);
        font-size: 20px;
    }
    .dc-book-card h3 { margin-top: 28px; color: #fff; }
    .dc-book-card p {
        margin: 16px 0 0;
        color: #cbd5e1;
        font-size: 15px;
        line-height: 1.75;
    }
    .dc-book-card .dc-btn { align-self: flex-start; margin-top: 28px; }
    .dc-book-note { color: #94a3b8 !important; font-size: 11px !important; }

    /* FAQ */
    .dc-faq { background: var(--dc-soft); }
    .dc-faq-list {
        max-width: 850px;
        margin: 42px auto 0;
        display: grid;
        gap: 12px;
    }
    .dc-faq details {
        overflow: hidden;
        border: 1px solid var(--dc-line);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 4px 14px rgba(15,23,42,.04);
    }
    .dc-faq summary {
        cursor: pointer;
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 20px 22px;
        color: var(--dc-ink);
        font-size: 14px;
        font-weight: 800;
    }
    .dc-faq summary::-webkit-details-marker { display: none; }
    .dc-faq-answer {
        padding: 0 22px 20px;
        color: var(--dc-muted);
        font-size: 14px;
        line-height: 1.75;
    }

    /* FINAL CTA */
    .dc-final-wrap { padding: 88px 0; background: #fff; }
    .dc-final {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 50px;
        padding: 54px 58px;
        border-radius: 30px;
        background: linear-gradient(135deg, #10b981, #0f766e);
        color: #fff;
        box-shadow: 0 25px 70px rgba(5,150,105,.22);
    }
    .dc-final h2 {
        max-width: 680px;
        margin: 10px 0 0;
        font-size: clamp(32px, 4vw, 50px);
        line-height: 1.05;
        letter-spacing: -.04em;
        font-weight: 900;
    }
    .dc-final p {
        max-width: 650px;
        margin: 16px 0 0;
        color: #ecfdf5;
        font-size: 15px;
        line-height: 1.75;
    }
    .dc-final-actions {
        display: flex;
        flex: 0 0 auto;
        gap: 10px;
    }
    .dc-btn-white { background: #fff; color: #047857; }
    .dc-btn-ghost { border-color: rgba(255,255,255,.30); background: rgba(255,255,255,.10); color: #fff; }

    /* FOOTER */
    .dc-footer { background: var(--dc-navy); color: #fff; }
    .dc-footer-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, .9fr);
        align-items: stretch;
        gap: 52px;
        padding-top: 68px;
        padding-bottom: 68px;
    }
    .dc-footer-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .dc-footer-brand img {
        width: 50px;
        height: 50px;
        padding: 4px;
        border-radius: 15px;
        background: #fff;
        object-fit: contain;
    }
    .dc-footer-brand strong { display: block; font-size: 16px; }
    .dc-footer-brand span { display: block; margin-top: 3px; color: #94a3b8; font-size: 11px; }
    .dc-contact-list {
        margin-top: 28px;
        display: grid;
        gap: 13px;
        color: #cbd5e1;
        font-size: 13px;
    }
    .dc-contact-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        min-width: 0;
    }
    .dc-contact-item i { width: 18px; margin-top: 3px; color: #6ee7b7; }
    .dc-contact-item span { min-width: 0; overflow-wrap: anywhere; }
    .dc-footer-links {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 22px;
        margin-top: 30px;
    }
    .dc-footer-links a { color: #94a3b8; text-decoration: none; font-size: 12px; font-weight: 700; }
    .dc-footer-links a:hover { color: #fff; }
    .dc-map {
        min-height: 320px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 22px;
        background: rgba(255,255,255,.04);
    }
    .dc-map iframe { width: 100%; height: 100%; min-height: 320px; border: 0; }
    .dc-map-empty {
        min-height: 320px;
        display: grid;
        place-items: center;
        padding: 32px;
        text-align: center;
        color: #94a3b8;
        font-size: 13px;
        line-height: 1.6;
    }
    .dc-footer-bottom { border-top: 1px solid rgba(255,255,255,.10); }
    .dc-footer-bottom-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        padding-top: 22px;
        padding-bottom: 22px;
        color: #64748b;
        font-size: 11px;
    }

    /* RESPONSIVE */
    @media (max-width: 1080px) {
        .dc-nav-links { display: none; }
        .dc-hero-inner { gap: 42px; }
        .dc-card-grid,
        .dc-team-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dc-final { align-items: flex-start; flex-direction: column; }
        .dc-final-actions { width: 100%; }
    }

    @media (max-width: 900px) {
        .dc-section { padding: 82px 0; }
        .dc-hero-inner,
        .dc-about-grid,
        .dc-hours-grid,
        .dc-footer-grid {
            grid-template-columns: minmax(0, 1fr);
        }
        .dc-hero-inner { padding-top: 70px; padding-bottom: 76px; }
        .dc-hero-copy { font-size: 17px; }
        .dc-hero-card { min-height: 460px; }
        .dc-about-grid { gap: 46px; }
        .dc-hours-section { padding-bottom: 82px; }
        .dc-footer-grid { gap: 36px; }
        .dc-map { min-height: 280px; }
        .dc-map iframe { min-height: 280px; }
    }

    @media (max-width: 700px) {
        .dc-container { width: min(calc(100% - 28px), var(--dc-container)); }
        .dc-nav { min-height: 68px; gap: 12px; }
        .dc-brand img { width: 42px; height: 42px; flex-basis: 42px; }
        .dc-brand-name { max-width: 160px; font-size: 13px; }
        .dc-brand-sub { display: none; }
        .dc-signin { display: none; }
        .dc-nav-actions .dc-btn { min-height: 42px; padding: 0 14px; font-size: 12px; }
        .dc-nav-actions .dc-btn span { display: none; }
        .dc-hero-inner { padding-top: 52px; padding-bottom: 58px; }
        .dc-hero h1 { margin-top: 20px; font-size: clamp(40px, 12vw, 56px); }
        .dc-hero-copy { margin-top: 20px; font-size: 16px; }
        .dc-hero-actions { flex-direction: column; align-items: stretch; }
        .dc-hero-actions .dc-btn { width: 100%; }
        .dc-trust-row { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .dc-hero-card { min-height: 420px; border-radius: 24px; }
        .dc-hero-card-top { top: 18px; left: 18px; right: 18px; }
        .dc-hero-card-copy { left: 22px; right: 22px; bottom: 112px; }
        .dc-hero-mini { left: 16px; right: 16px; bottom: 16px; }
        .dc-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dc-stat:nth-child(2) { border-right: 0; }
        .dc-stat:nth-child(-n+2) { border-bottom: 1px solid var(--dc-line); }
        .dc-card-grid,
        .dc-team-grid,
        .dc-steps,
        .dc-benefits { grid-template-columns: minmax(0, 1fr); }
        .dc-card-grid,
        .dc-team-grid { gap: 16px; margin-top: 36px; }
        .dc-benefit { min-height: 0; }
        .dc-service-body { padding: 20px; }
        .dc-team-card { padding: 22px; }
        .dc-hours-card,
        .dc-book-card { padding: 24px; border-radius: 22px; }
        .dc-hour-row { align-items: flex-start; }
        .dc-hour-row dd { max-width: 55%; }
        .dc-final-wrap { padding: 64px 0; }
        .dc-final { padding: 34px 24px; border-radius: 24px; }
        .dc-final-actions { flex-direction: column; }
        .dc-final-actions .dc-btn { width: 100%; }
        .dc-footer-grid { padding-top: 52px; padding-bottom: 52px; }
        .dc-footer-bottom-inner { align-items: flex-start; flex-direction: column; gap: 8px; }
    }

    @media (max-width: 420px) {
        .dc-brand-name { max-width: 120px; }
        .dc-nav-actions .dc-btn { width: 42px; padding: 0; }
        .dc-nav-actions .dc-btn i { margin: 0; }
        .dc-hero-card { min-height: 400px; }
        .dc-card-chip { display: none; }
    }
</style>
@endpush

@php
    $clinicName = $clinic->clinic_name ?: 'Aquilizan Dental Clinic';
    $heroTitle = $site->hero_title ?: 'Friendly, dental care with easier online booking.';
    $heroSubtitle = $site->hero_subtitle ?: 'Choose a service, find an available schedule, and keep your patient experience organized through one secure clinic portal.';
@endphp

@section('content')
<div class="dc-page">
    <nav class="dc-nav-wrap">
        <div class="dc-container dc-nav">
            <a href="{{ route('home') }}" class="dc-brand">
                <img src="{{ asset('images/aquilizan-logo.png') }}" alt="{{ $clinicName }} logo">
                <span class="dc-brand-copy">
                    <span class="dc-brand-name">{{ $clinicName }}</span>
                    <span class="dc-brand-sub">Dental care • Secure patient portal</span>
                </span>
            </a>

            <div class="dc-nav-links">
                @if($services->isNotEmpty())<a href="#services">Services</a>@endif
                @if($site->about_body || $site->benefits)<a href="#about">About</a>@endif
                @if($team->isNotEmpty())<a href="#team">Dental team</a>@endif
                <a href="#booking">How it works</a>
                @if($faqs->isNotEmpty())<a href="#faq">FAQ</a>@endif
                <a href="#contact">Contact</a>
            </div>

            <div class="dc-nav-actions">
                <a href="{{ route('login') }}" class="dc-signin">Sign in</a>
                <a href="{{ route('public.book') }}" class="dc-btn dc-btn-primary">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Book appointment</span>
                </a>
            </div>
        </div>
    </nav>

    <header class="dc-hero">
        <div class="dc-container dc-hero-inner">
            <div>
                <span class="dc-pill">
                    <span class="dc-pill-dot"></span>
                    {{ $site->hero_eyebrow ?: 'Comfortable care. Easier booking.' }}
                </span>

                <h1>{{ $heroTitle }}</h1>
                <p class="dc-hero-copy">{{ $heroSubtitle }}</p>

                <div class="dc-hero-actions">
                    <a href="{{ route('public.book') }}" class="dc-btn dc-btn-primary">
                        <i class="fa-solid fa-calendar-plus"></i>
                        Book an appointment
                    </a>
                    <a href="{{ $services->isNotEmpty() ? '#services' : '#booking' }}" class="dc-btn dc-btn-secondary">
                        Explore services
                        <i class="fa-solid fa-arrow-down"></i>
                    </a>
                </div>

                <div class="dc-trust-row">
                    <span class="dc-trust-item"><i class="fa-solid fa-circle-check"></i> Verified appointments</span>
                    <span class="dc-trust-item"><i class="fa-solid fa-shield-halved"></i> Secure patient access</span>
                    <span class="dc-trust-item"><i class="fa-solid fa-clock"></i> Clear schedules</span>
                </div>
            </div>

            <div class="dc-hero-card">
                @if($site->hero_image_path)
                    <img src="{{ asset('storage/'.$site->hero_image_path) }}" alt="Dental care at {{ $clinicName }}">
                    <div class="dc-hero-card-shade"></div>
                @else
                    <div class="dc-hero-art"></div>
                    <div class="dc-hero-art-grid"></div>
                @endif

                <div class="dc-hero-card-top">
                    <span class="dc-tooth-box"><i class="fa-solid fa-tooth"></i></span>
                    <span class="dc-card-chip">Patient-centered care</span>
                </div>

                <div class="dc-hero-card-copy">
                    <h2>Better dental visits begin before you reach the clinic.</h2>
                    <p>View services, choose your preferred schedule, and keep your clinic experience organized from one secure portal.</p>
                </div>

                <div class="dc-hero-mini">
                    <span class="dc-hero-mini-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </span>
                    <span>
                        <strong>Simple online booking</strong>
                        <span>Choose a service, select an available time, then wait for clinic confirmation.</span>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <section class="dc-stats-wrap">
        <div class="dc-container dc-stats">
            <div class="dc-stat"><strong>{{ $services->count() }}</strong><span>Services</span></div>
            <div class="dc-stat"><strong>{{ $team->count() }}</strong><span>Dental team</span></div>
            <div class="dc-stat"><strong>Online</strong><span>Appointment booking</span></div>
            <div class="dc-stat"><strong>Secure</strong><span>Patient portal</span></div>
        </div>
    </section>

    @if($services->isNotEmpty())
        <section id="services" class="dc-section dc-services">
            <div class="dc-container">
                <div class="dc-section-head">
                    <p class="dc-eyebrow">Our dental services</p>
                    <h2 class="dc-section-title">Care designed around your smile.</h2>
                    <p class="dc-section-copy">Browse available clinic services before booking so you can choose the care that fits your needs.</p>
                </div>

                <div class="dc-card-grid">
                    @foreach($services as $service)
                        <article class="dc-service-card">
                            <div class="dc-service-media">
                                @if($service->public_image_path)
                                    <img src="{{ asset('storage/'.$service->public_image_path) }}" alt="{{ $service->name }}">
                                @else
                                    <div class="dc-service-placeholder"><span><i class="fa-solid fa-tooth"></i></span></div>
                                @endif
                                <span class="dc-duration"><i class="fa-regular fa-clock"></i> {{ $service->duration_minutes }} min</span>
                            </div>

                            <div class="dc-service-body">
                                <h3>{{ $service->name }}</h3>
                                @if($service->public_description)
                                    <p class="dc-service-desc">{{ $service->public_description }}</p>
                                @else
                                    <p class="dc-service-desc">Professional dental care tailored to your appointment needs.</p>
                                @endif

                                <div class="dc-service-footer">
                                    <div>
                                        @if($service->show_public_price)
                                            <span class="dc-price-label">Starting price</span>
                                            <span class="dc-price">₱{{ number_format($service->price, 2) }}</span>
                                        @else
                                            <span class="dc-price-label">Pricing</span>
                                            <span class="dc-price">Ask the clinic</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('public.book') }}" class="dc-arrow-btn" aria-label="Book {{ $service->name }}">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="dc-center-cta">
                    <a href="{{ route('public.book') }}" class="dc-btn dc-btn-secondary">View schedule and book <i class="fa-solid fa-arrow-right"></i></a>
                </div>
            </div>
        </section>
    @endif

    @if($site->about_body || $site->benefits)
        <section id="about" class="dc-section dc-about">
            <div class="dc-container dc-about-grid">
                <div>
                    <p class="dc-eyebrow">About the clinic</p>
                    <h2>{{ $site->about_heading ?: 'Dental care that feels clear, calm, and personal.' }}</h2>
                    @if($site->about_body)
                        <p class="dc-about-copy">{{ $site->about_body }}</p>
                    @endif
                    <div class="dc-about-actions">
                        <a href="{{ route('public.book') }}" class="dc-btn dc-btn-primary">Book your visit <i class="fa-solid fa-arrow-right"></i></a>
                        <a href="{{ route('register') }}" class="dc-btn dc-btn-dark-secondary">Create patient account</a>
                    </div>
                </div>

                <div class="dc-benefits">
                    @php
                        $landingBenefits = $site->benefits ?: [
                            'Secure and organized patient access',
                            'Clear booking and appointment confirmation',
                            'Convenient service and schedule selection',
                            'Patient-centered clinic communication',
                        ];
                    @endphp
                    @foreach($landingBenefits as $benefit)
                        <div class="dc-benefit">
                            <span class="dc-benefit-icon"><i class="fa-solid fa-circle-check"></i></span>
                            <p>{{ $benefit }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($team->isNotEmpty())
        <section id="team" class="dc-section dc-team">
            <div class="dc-container">
                <div class="dc-section-head">
                    <p class="dc-eyebrow">Meet the team</p>
                    <h2 class="dc-section-title">Care from people you can feel comfortable with.</h2>
                    <p class="dc-section-copy">Get to know the dental professionals who help make every visit clear, respectful, and patient-focused.</p>
                </div>

                <div class="dc-team-grid">
                    @foreach($team as $member)
                        <article class="dc-team-card">
                            <div class="dc-team-top">
                                <div class="dc-avatar">
                                    @if($member->photo_path)
                                        <img src="{{ asset('storage/'.$member->photo_path) }}" alt="{{ $member->name }}">
                                    @else
                                        {{ collect(explode(' ', $member->name))->take(2)->map(fn($p) => mb_substr($p, 0, 1))->join('') }}
                                    @endif
                                </div>
                                <div style="min-width:0;">
                                    <h3>{{ $member->name }}</h3>
                                    @if($member->title)<p class="dc-team-title">{{ $member->title }}</p>@endif
                                </div>
                            </div>
                            @if($member->specialties)<p class="dc-team-specialties">{{ $member->specialties }}</p>@endif
                            @if($member->biography)<p class="dc-team-bio">{{ $member->biography }}</p>@endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="booking" class="dc-section dc-booking">
        <div class="dc-container">
            <div class="dc-section-head">
                <p class="dc-eyebrow">How booking works</p>
                <h2 class="dc-section-title">From choosing care to confirming your visit.</h2>
                <p class="dc-section-copy">A simple booking flow helps you know what happens next before you even arrive at the clinic.</p>
            </div>

            <div class="dc-steps">
                <article class="dc-step">
                    <span class="dc-step-number">01</span>
                    <h3>Create your patient account</h3>
                    <p>Register and verify your account so your appointment information stays connected to you securely.</p>
                </article>
                <article class="dc-step">
                    <span class="dc-step-number">02</span>
                    <h3>Choose your service and schedule</h3>
                    <p>Select the dental service you need, then choose from the clinic's available appointment times.</p>
                </article>
                <article class="dc-step">
                    <span class="dc-step-number">03</span>
                    <h3>Wait for clinic confirmation</h3>
                    <p>Your request is reviewed by clinic staff so you receive a clear appointment status before your visit.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="dc-hours-section">
        <div class="dc-container dc-hours-grid">
            <div class="dc-hours-card">
                <h3>Clinic hours</h3>
                @if($closure)
                    <div class="dc-closure"><i class="fa-solid fa-circle-info"></i> Upcoming closure: {{ $closure->closure_date->format('M j, Y') }} · {{ $closure->reason }}</div>
                @endif
                <dl class="dc-hours-list">
                    @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day => $label)
                        <div class="dc-hour-row">
                            <dt>{{ $label }}</dt>
                            <dd>{{ \App\Http\Controllers\PublicSiteController::hoursLabel($hours[$day] ?? null) }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="dc-book-card">
                <span class="dc-book-icon"><i class="fa-solid fa-calendar-check"></i></span>
                <h3>Ready to schedule your visit?</h3>
                <p>Start your booking online, choose an available schedule, and keep your appointment details organized in your patient account.</p>
                <a href="{{ route('public.book') }}" class="dc-btn dc-btn-primary">Book appointment now <i class="fa-solid fa-arrow-right"></i></a>
                <p class="dc-book-note">Appointment requests remain subject to clinic confirmation.</p>
            </div>
        </div>
    </section>

    @if($faqs->isNotEmpty())
        <section id="faq" class="dc-section dc-faq">
            <div class="dc-container">
                <div class="dc-section-head">
                    <p class="dc-eyebrow">Frequently asked questions</p>
                    <h2 class="dc-section-title">Helpful answers before your visit.</h2>
                </div>
                <div class="dc-faq-list">
                    @foreach($faqs as $faq)
                        <details>
                            <summary>
                                <span>{{ $faq->question }}</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </summary>
                            <div class="dc-faq-answer">{{ $faq->answer }}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="dc-final-wrap">
        <div class="dc-container dc-final">
            <div>
                <p class="dc-eyebrow" style="color:#d1fae5;">Your next visit can start here</p>
                <h2>Make dental care easier to plan.</h2>
                <p>Book online, manage your patient access, and stay connected with {{ $clinicName }} from one convenient place.</p>
            </div>
            <div class="dc-final-actions">
                <a href="{{ route('public.book') }}" class="dc-btn dc-btn-white"><i class="fa-solid fa-calendar-check"></i> Book appointment</a>
                <a href="{{ route('register') }}" class="dc-btn dc-btn-ghost">Create account</a>
            </div>
        </div>
    </section>

    <footer id="contact" class="dc-footer">
        <div class="dc-container dc-footer-grid">
            <div>
                <div class="dc-footer-brand">
                    <img src="{{ asset('images/aquilizan-logo.png') }}" alt="{{ $clinicName }} logo">
                    <span>
                        <strong>{{ $clinicName }}</strong>
                        <span>Dental care and secure patient services</span>
                    </span>
                </div>

                <div class="dc-contact-list">
                    @if($clinic->address)<div class="dc-contact-item"><i class="fa-solid fa-location-dot"></i><span>{{ $clinic->address }}</span></div>@endif
                    @if($clinic->phone)<div class="dc-contact-item"><i class="fa-solid fa-phone"></i><span>{{ $clinic->phone }}</span></div>@endif
                    @if($clinic->email)<div class="dc-contact-item"><i class="fa-solid fa-envelope"></i><span>{{ $clinic->email }}</span></div>@endif
                </div>

                <div class="dc-footer-links">
                    <a href="{{ route('privacy') }}">Privacy</a>
                    <a href="{{ route('terms') }}">Terms</a>
                    <a href="{{ route('login') }}">Patient login</a>
                    <a href="{{ route('register') }}">Register</a>
                </div>
            </div>

            @if($site->map_embed_url)
                <div class="dc-map">
                    <iframe src="{{ $site->map_embed_url }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="{{ $clinicName }} location"></iframe>
                </div>
            @else
                <!-- <div class="dc-map dc-map-empty">
                    <div><i class="fa-solid fa-location-dot" style="color:#6ee7b7;font-size:22px;"></i><br><br>Clinic location details will appear here when a map is configured.</div>
                </div> -->
            @endif
        </div>

        <div class="dc-footer-bottom">
            <div class="dc-container dc-footer-bottom-inner">
                <span>© {{ now()->year }} {{ $clinicName }}. All rights reserved.</span>
                <span>Secure online booking and patient portal.</span>
            </div>
        </div>
    </footer>
</div>
@endsection
