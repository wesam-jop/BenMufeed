import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Tajawal', ...defaultTheme.fontFamily.sans],
                display: ['El Messiri', 'Tajawal', ...defaultTheme.fontFamily.serif],
            },
            fontSize: {
                '2xs': ['0.6875rem', { lineHeight: '1rem' }],
                caption: ['0.75rem', { lineHeight: '1.125rem' }],
                body: ['0.875rem', { lineHeight: '1.375rem' }],
                'body-lg': ['1rem', { lineHeight: '1.625rem' }],
                h6: ['0.875rem', { lineHeight: '1.25rem', fontWeight: '600' }],
                h5: ['1rem', { lineHeight: '1.5rem', fontWeight: '600' }],
                h4: ['1.125rem', { lineHeight: '1.625rem', fontWeight: '600' }],
                h3: ['1.25rem', { lineHeight: '1.75rem', fontWeight: '700' }],
                h2: ['1.5rem', { lineHeight: '2rem', fontWeight: '700' }],
                h1: ['2rem', { lineHeight: '2.5rem', fontWeight: '700' }],
                display: ['2.75rem', { lineHeight: '3.25rem', fontWeight: '700' }],
            },
            spacing: {
                4.5: '1.125rem',
                13: '3.25rem',
                15: '3.75rem',
                18: '4.5rem',
            },
            borderRadius: {
                xs: '0.375rem',
                sm: '0.5rem',
                md: '0.625rem',
                lg: '0.75rem',
                xl: '1rem',
                '2xl': '1.25rem',
                '3xl': '1.5rem',
            },
            colors: {
                /** واجهة الكافيتيريا — بن مفيد Ben Mufeed */
                store: {
                    primary: 'rgb(var(--store-primary-rgb) / <alpha-value>)',
                    'primary-hover': 'rgb(var(--store-primary-hover-rgb) / <alpha-value>)',
                    secondary: 'rgb(var(--store-secondary-rgb) / <alpha-value>)',
                    'secondary-hover': 'rgb(var(--store-secondary-hover-rgb) / <alpha-value>)',
                    accent: 'rgb(var(--store-accent-rgb) / <alpha-value>)',
                    surface: 'rgb(var(--store-surface-rgb) / <alpha-value>)',
                    'surface-raised': 'rgb(var(--store-surface-raised-rgb) / <alpha-value>)',
                    muted: 'rgb(var(--store-muted-rgb) / <alpha-value>)',
                    cream: 'rgb(var(--store-cream-rgb) / <alpha-value>)',
                    border: 'var(--color-border)',
                    'border-strong': 'var(--color-border-strong)',
                },
                neutral: {
                    50: 'var(--neutral-50)',
                    100: 'var(--neutral-100)',
                    200: 'var(--neutral-200)',
                    300: 'var(--neutral-300)',
                    400: 'var(--neutral-400)',
                    500: 'var(--neutral-500)',
                    600: 'var(--neutral-600)',
                    700: 'var(--neutral-700)',
                    800: 'var(--neutral-800)',
                    900: 'var(--neutral-900)',
                },
                semantic: {
                    success: 'rgb(var(--semantic-success-rgb) / <alpha-value>)',
                    'success-bg': 'var(--color-success-bg)',
                    'success-border': 'var(--color-success-border)',
                    error: 'rgb(var(--semantic-error-rgb) / <alpha-value>)',
                    'error-bg': 'var(--color-error-bg)',
                    'error-border': 'var(--color-error-border)',
                    warning: 'rgb(var(--semantic-warning-rgb) / <alpha-value>)',
                    'warning-bg': 'var(--color-warning-bg)',
                    info: 'rgb(var(--semantic-info-rgb) / <alpha-value>)',
                    'info-bg': 'var(--color-info-bg)',
                },
                /** KANJO — ألوان الهوية من الشعار */
                kanjo: {
                    navy: '#1E2A5E',
                    'navy-soft': '#2d3d72',
                    bluemid: '#3B71A3',
                    bluelight: '#5DADE2',
                    gold: '#FFD700',
                    orange: '#FF8C00',
                    silver: '#D3D3D3',
                    steel: '#808080',
                    paper: '#F8F9FA',
                    'paper-muted': '#F0F2F5',
                    'paper-deep': '#E8EBF0',
                },
                brand: {
                    400: '#FFD700',
                    500: '#FF8C00',
                    600: '#E67E00',
                },
            },
            backgroundImage: {
                'kanjo-accent': 'linear-gradient(135deg, #FFD700 0%, #FF8C00 100%)',
                'kanjo-hero': 'linear-gradient(145deg, #F8F9FA 0%, #E8EBF0 45%, #dce4f0 100%)',
                'restaurant-hero': 'linear-gradient(145deg, #1a0f0a 0%, #2c1810 42%, #4a2f1c 100%)',
                'restaurant-gold': 'linear-gradient(135deg, #d4a574 0%, #f0e0c8 48%, #c4956a 100%)',
                'store-page':
                    'radial-gradient(ellipse 90% 60% at 100% 0%, rgb(212 165 116 / 0.12), transparent 55%), radial-gradient(ellipse 70% 50% at 0% 100%, rgb(44 24 16 / 0.05), transparent 50%), linear-gradient(180deg, var(--color-cream) 0%, var(--neutral-100) 100%)',
                'store-shimmer':
                    'linear-gradient(90deg, var(--neutral-100) 0%, var(--neutral-50) 50%, var(--neutral-100) 100%)',
            },
            boxShadow: {
                xs: 'var(--shadow-xs)',
                sm: 'var(--shadow-sm)',
                md: 'var(--shadow-md)',
                lg: 'var(--shadow-lg)',
                xl: 'var(--shadow-xl)',
                kanjo: '0 8px 30px -12px rgba(30, 42, 94, 0.18)',
                'kanjo-card': '0 2px 12px rgba(30, 42, 94, 0.06)',
                restaurant: 'var(--shadow-lg)',
                'restaurant-card': 'var(--shadow-md)',
                'store-float': 'var(--shadow-xl)',
                focus: '0 0 0 3px var(--color-focus-ring)',
            },
            transitionDuration: {
                fast: '150ms',
                DEFAULT: '200ms',
                slow: '250ms',
            },
            transitionTimingFunction: {
                smooth: 'cubic-bezier(0.4, 0, 0.2, 1)',
                spring: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
            },
            keyframes: {
                'category-marquee': {
                    '0%': { transform: 'translateX(0)' },
                    '100%': { transform: 'translateX(-50%)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
                'fade-in': {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                'slide-up': {
                    from: { opacity: '0', transform: 'translateY(0.5rem)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                'scale-in': {
                    from: { opacity: '0', transform: 'scale(0.96)' },
                    to: { opacity: '1', transform: 'scale(1)' },
                },
            },
            animation: {
                'category-marquee': 'category-marquee var(--category-marquee-duration, 40s) linear infinite',
                shimmer: 'shimmer 1.8s ease-in-out infinite',
                'fade-in': 'fade-in 200ms ease-out',
                'slide-up': 'slide-up 250ms cubic-bezier(0.4, 0, 0.2, 1)',
                'scale-in': 'scale-in 200ms cubic-bezier(0.4, 0, 0.2, 1)',
            },
        },
    },

    plugins: [forms],
};
