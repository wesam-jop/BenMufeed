<style>
    :root {
        --bm-ink: #2f1f14;
        --bm-espresso: #5c3d2e;
        --bm-coffee: #3d281c;
        --bm-gold: #c9a87c;
        --bm-gold-soft: #e8d4b0;
        --bm-cream: #f7f2ea;
        --bm-ivory: #fdfbf7;
        --bm-muted: #8b7355;
        --bm-border: rgb(61 40 28 / 0.1);
        --bm-shadow: 0 16px 48px -24px rgb(47 31 20 / 0.28);
    }

    /* ——— أساس اللوحة ——— */
    .fi-body {
        background-color: var(--bm-cream) !important;
        background-image:
            radial-gradient(ellipse 90% 70% at 100% -10%, rgb(201 168 124 / 0.14), transparent 50%),
            radial-gradient(ellipse 70% 50% at 0% 100%, rgb(92 61 46 / 0.06), transparent 55%);
        color: var(--bm-ink);
    }

    .fi-main {
        max-width: 88rem;
    }

    .fi-page-content {
        gap: 1.75rem;
    }

    .fi-header-heading {
        font-family: 'El Messiri', 'Tajawal', serif !important;
        font-weight: 700 !important;
        color: var(--bm-ink) !important;
        letter-spacing: -0.02em;
    }

    /* ——— الشريط الجانبي ——— */
    .fi-sidebar {
        background: linear-gradient(188deg, #3a2418 0%, #2a1810 48%, #1a1008 100%) !important;
        border-inline-end: 1px solid rgb(201 168 124 / 0.12) !important;
        box-shadow: 4px 0 32px -12px rgb(0 0 0 / 0.35);
    }

    .fi-sidebar-header {
        border-bottom: 1px solid rgb(255 255 255 / 0.06);
    }

    .fi-sidebar .fi-logo {
        color: var(--bm-gold-soft);
    }

    .fi-sidebar .fi-sidebar-group-label {
        color: rgb(201 168 124 / 0.75);
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    /* Filament v4+: .fi-sidebar-item-btn — لا .fi-sidebar-item-button */
    .fi-sidebar .fi-sidebar-item-label {
        color: rgb(253 251 247 / 0.9) !important;
    }

    .fi-sidebar .fi-sidebar-item-btn > .fi-icon {
        color: rgb(201 168 124 / 0.8) !important;
    }

    /* إلغاء hover الأبيض الافتراضي (gray-100) على خلفية داكنة */
    .fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover,
    .fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:focus-visible {
        background-color: rgb(201 168 124 / 0.14) !important;
    }

    .fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover .fi-sidebar-item-label,
    .fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:focus-visible .fi-sidebar-item-label {
        color: var(--bm-gold-soft) !important;
    }

    .fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover > .fi-icon,
    .fi-sidebar .fi-sidebar-item-has-url > .fi-sidebar-item-btn:focus-visible > .fi-icon {
        color: var(--bm-gold) !important;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: linear-gradient(
            90deg,
            rgb(201 168 124 / 0.28),
            rgb(201 168 124 / 0.08)
        ) !important;
        border-inline-start: 3px solid var(--bm-gold);
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn .fi-sidebar-item-label {
        color: var(--bm-gold-soft) !important;
    }

    .fi-sidebar .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-icon {
        color: var(--bm-gold) !important;
    }

    /* ——— الشريط العلوي ——— */
    .fi-topbar {
        background: rgb(253 251 247 / 0.82) !important;
        backdrop-filter: blur(12px);
        border-bottom: 1px solid var(--bm-border) !important;
        box-shadow: 0 1px 0 rgb(255 255 255 / 0.6);
    }

    /* ——— بطاقات الإحصائيات ——— */
    .fi-wi-stats-overview {
        gap: 1rem;
    }

    .fi-wi-widget .fi-section-header-heading,
    .fi-wi-stats-overview + .fi-sc-component .fi-section-header-heading {
        font-family: 'El Messiri', 'Tajawal', serif;
    }

    .fi-wi-stats-overview-stat {
        border-radius: 1rem !important;
        border: 1px solid var(--bm-border) !important;
        background: linear-gradient(165deg, var(--bm-ivory) 0%, #f3ebe0 100%) !important;
        box-shadow: var(--bm-shadow) !important;
        ring: none !important;
        --tw-ring-shadow: 0 0 #0000 !important;
        padding: 1.25rem 1.35rem !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 40px -22px rgb(47 31 20 / 0.35) !important;
    }

    .fi-wi-stats-overview-stat .fi-icon {
        color: var(--bm-gold) !important;
        opacity: 0.95;
    }

    .fi-wi-stats-overview-stat-label {
        color: var(--bm-muted) !important;
        font-weight: 500;
    }

    .fi-wi-stats-overview-stat-value {
        color: var(--bm-ink) !important;
        font-family: 'El Messiri', 'Tajawal', serif;
        font-weight: 700 !important;
    }

    .fi-wi-stats-overview-stat-description {
        color: var(--bm-muted) !important;
    }

    /* ألوان Filament داخل الإحصائيات — نغمات دافئة فقط */
    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-success,
    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-success .fi-icon {
        color: #3d5c4a !important;
    }

    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-warning,
    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-warning .fi-icon {
        color: #a67c52 !important;
    }

    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-danger,
    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-danger .fi-icon {
        color: #8b3a3a !important;
    }

    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-primary,
    .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-description.fi-color-primary .fi-icon {
        color: var(--bm-espresso) !important;
    }

    /* عنوان قسم الودجت */
    .fi-wi-stats-overview .fi-section-header {
        margin-bottom: 0.25rem;
    }

    .fi-wi-stats-overview .fi-section-header-heading {
        font-family: 'El Messiri', 'Tajawal', serif !important;
        font-size: 1.25rem !important;
        color: var(--bm-ink) !important;
    }

    .fi-wi-stats-overview .fi-section-header-description {
        color: var(--bm-muted) !important;
    }

    /* ——— جداول (تباين عالٍ — إصلاح الشارات والنص الذهبي الفاتح) ——— */
    .fi-ta-ctn {
        border-radius: 1rem !important;
        border: 1px solid var(--bm-border) !important;
        background: #ffffff !important;
        box-shadow: 0 8px 28px -16px rgb(47 31 20 / 0.1) !important;
        overflow: hidden;
    }

    .fi-ta-header-cell {
        color: var(--bm-ink) !important;
        background: #f0e8dc !important;
        font-weight: 600 !important;
        font-size: 0.8125rem !important;
    }

    .fi-ta-cell {
        color: var(--bm-ink) !important;
        background: #ffffff !important;
    }

    .fi-ta-row:hover .fi-ta-cell {
        background: #faf6f0 !important;
    }

    .fi-ta-cell .fi-ta-text,
    .fi-ta-cell .fi-ta-text-item,
    .fi-ta-col {
        color: var(--bm-ink) !important;
    }

    .fi-ta-ctn .fi-badge.fi-color {
        background: #f0e6d8 !important;
        color: var(--bm-ink) !important;
        --text: var(--bm-ink) !important;
        --dark-text: var(--bm-ink) !important;
        box-shadow: inset 0 0 0 1px rgb(92 61 46 / 0.14) !important;
        --tw-ring-color: rgb(92 61 46 / 0.12) !important;
        font-weight: 600;
    }

    .fi-ta-ctn .fi-badge.fi-color-success {
        background: #e3ece6 !important;
        color: #1e4a32 !important;
        --text: #1e4a32 !important;
        box-shadow: inset 0 0 0 1px rgb(45 90 64 / 0.18) !important;
    }

    .fi-ta-ctn .fi-badge.fi-color-warning {
        background: #f3e6d0 !important;
        color: #5c3a1e !important;
        --text: #5c3a1e !important;
        box-shadow: inset 0 0 0 1px rgb(139 90 46 / 0.2) !important;
    }

    .fi-ta-ctn .fi-badge.fi-color-danger {
        background: #f2e2e2 !important;
        color: #7a2424 !important;
        --text: #7a2424 !important;
        box-shadow: inset 0 0 0 1px rgb(139 46 46 / 0.18) !important;
    }

    .fi-ta-ctn .fi-badge.fi-color-info,
    .fi-ta-ctn .fi-badge.fi-color-primary,
    .fi-ta-ctn .fi-badge.fi-color-gray {
        background: #ece5db !important;
        color: var(--bm-ink) !important;
        --text: var(--bm-ink) !important;
        box-shadow: inset 0 0 0 1px rgb(92 61 46 / 0.12) !important;
    }

    .fi-ta-actions .fi-ac-btn-action,
    .fi-ta-actions .fi-ac-link-action {
        color: var(--bm-espresso) !important;
        font-weight: 600;
    }

    .fi-ta-actions .fi-ac-link-action .fi-icon,
    .fi-ta-actions .fi-ac-btn-action .fi-icon {
        color: var(--bm-espresso) !important;
    }

    .fi-ta-header-toolbar .fi-input-wrp,
    .fi-ta-header-toolbar .fi-select-input {
        color: var(--bm-ink) !important;
    }

    .fi-ta-pagination {
        color: var(--bm-muted) !important;
    }

    .fi-ta-pagination .fi-active {
        background: var(--bm-espresso) !important;
        color: #fff !important;
    }

    /* ——— أقسام النماذج ——— */
    .fi-section:not(.fi-section-not-contained),
    .fi-fo-section {
        border-radius: 1rem !important;
        border: 1px solid var(--bm-border) !important;
        background: var(--bm-ivory) !important;
        box-shadow: 0 8px 28px -16px rgb(47 31 20 / 0.12) !important;
    }

    .fi-section-header-heading {
        color: var(--bm-ink) !important;
    }

    .fi-fo-field-wrp label,
    .fi-input-wrp input,
    .fi-select-input,
    .fi-fo-placeholder {
        color: var(--bm-ink) !important;
    }

    /* ——— تسجيل الدخول ——— */
    .fi-login-page {
        background: linear-gradient(155deg, var(--bm-cream) 0%, var(--bm-ivory) 45%, var(--bm-gold-soft) 100%);
    }

    .fi-login-card {
        border: 1px solid var(--bm-border);
        box-shadow: var(--bm-shadow);
    }

    /* ——— بطل الداشبورد ——— */
    .bm-dash-hero {
        position: relative;
        margin-bottom: 1.75rem;
        overflow: hidden;
        border-radius: 1.25rem;
        border: 1px solid rgb(201 168 124 / 0.22);
        background:
            linear-gradient(125deg, rgb(255 255 255 / 0.06) 0%, transparent 42%),
            linear-gradient(135deg, #2f1f14 0%, #4a3022 38%, #5c3d2e 68%, #6b4a32 100%);
        padding: 1.75rem 1.5rem;
        color: #fdfbf7;
        box-shadow: var(--bm-shadow);
    }

    .bm-dash-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 80% 120% at 100% 0%, rgb(201 168 124 / 0.2), transparent 55%);
        pointer-events: none;
    }

    .bm-dash-hero::after {
        content: '';
        position: absolute;
        inset-inline: 1.5rem;
        bottom: 0;
        height: 3px;
        border-radius: 3px 3px 0 0;
        background: linear-gradient(90deg, transparent, var(--bm-gold), transparent);
        opacity: 0.85;
    }

    @media (min-width: 640px) {
        .bm-dash-hero {
            padding: 2.25rem 2rem;
        }
    }

    .bm-dash-hero__inner {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1.25rem;
    }

    .bm-dash-hero__greeting {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 500;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--bm-gold-soft);
    }

    .bm-dash-hero__title {
        margin: 0.35rem 0 0;
        font-family: 'El Messiri', 'Tajawal', serif;
        font-size: 1.875rem;
        font-weight: 700;
        line-height: 1.15;
        letter-spacing: -0.02em;
    }

    @media (min-width: 640px) {
        .bm-dash-hero__title {
            font-size: 2.125rem;
        }
    }

    .bm-dash-hero__subtitle {
        margin: 0.65rem 0 0;
        max-width: 34rem;
        font-size: 0.9375rem;
        line-height: 1.6;
        color: rgb(253 251 247 / 0.78);
    }

    .bm-dash-hero__badge {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        background: rgb(201 168 124 / 0.18);
        padding: 0.5rem 1.15rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--bm-gold-soft);
        box-shadow: inset 0 0 0 1px rgb(201 168 124 / 0.35);
        white-space: nowrap;
    }

    /* ——— اختصارات سريعة ——— */
    .bm-dash-links {
        display: grid;
        gap: 1rem;
        margin-bottom: 2rem;
        grid-template-columns: 1fr;
    }

    @media (min-width: 640px) {
        .bm-dash-links {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1280px) {
        .bm-dash-links {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .bm-dash-link {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        padding: 1.1rem 1.15rem;
        border-radius: 1rem;
        border: 1px solid var(--bm-border);
        background: var(--bm-ivory);
        text-decoration: none;
        box-shadow: 0 6px 24px -14px rgb(47 31 20 / 0.14);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .bm-dash-link:hover {
        transform: translateY(-3px);
        border-color: rgb(201 168 124 / 0.45);
        box-shadow: 0 16px 36px -18px rgb(47 31 20 / 0.22);
    }

    .bm-dash-link__icon {
        display: flex;
        flex-shrink: 0;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 0.85rem;
    }

    .bm-dash-link__svg {
        width: 1.4rem;
        height: 1.4rem;
    }

    .bm-dash-link--primary .bm-dash-link__icon {
        background: linear-gradient(145deg, #ebe0d4, #f5efe6);
        color: var(--bm-espresso);
    }

    .bm-dash-link--accent .bm-dash-link__icon {
        background: linear-gradient(145deg, #f0e2c8, #faf3e6);
        color: #7a5a32;
    }

    .bm-dash-link--warm .bm-dash-link__icon {
        background: linear-gradient(145deg, #ead9c8, #f7efe6);
        color: #6b4423;
    }

    .bm-dash-link--muted .bm-dash-link__icon {
        background: linear-gradient(145deg, #e8e2da, #f5f1eb);
        color: var(--bm-muted);
    }

    .bm-dash-link__label {
        display: block;
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--bm-ink);
    }

    .bm-dash-link:hover .bm-dash-link__label {
        color: var(--bm-espresso);
    }

    .bm-dash-link__desc {
        display: block;
        margin-top: 0.2rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        color: var(--bm-muted);
    }
</style>
