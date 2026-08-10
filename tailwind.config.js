/**
 * Every colour is declared as a bare `R G B` triplet in styles/index.css and
 * consumed here through `rgb(var(--token) / <alpha-value>)`. That indirection is
 * what lets `[data-scheme="light"]` swap an entire section's palette by
 * redefining custom properties, while Tailwind's opacity modifiers
 * (`bg-surface-1/50`) keep working.
 */
const rgb = (token) => `rgb(var(${token}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50: rgb('--c-primary-50'),
          100: rgb('--c-primary-100'),
          200: rgb('--c-primary-200'),
          300: rgb('--c-primary-300'),
          400: rgb('--c-primary-400'),
          500: rgb('--c-primary-500'),
          600: rgb('--c-primary-600'),
          700: rgb('--c-primary-700'),
          800: rgb('--c-primary-800'),
          900: rgb('--c-primary-900'),
          950: rgb('--c-primary-950'),
        },
        accent: {
          300: rgb('--c-accent-300'),
          400: rgb('--c-accent-400'),
          500: rgb('--c-accent-500'),
          600: rgb('--c-accent-600'),
        },
        neutral: {
          50: rgb('--c-neutral-50'),
          100: rgb('--c-neutral-100'),
          200: rgb('--c-neutral-200'),
          300: rgb('--c-neutral-300'),
          400: rgb('--c-neutral-400'),
          500: rgb('--c-neutral-500'),
          600: rgb('--c-neutral-600'),
          700: rgb('--c-neutral-700'),
          800: rgb('--c-neutral-800'),
          900: rgb('--c-neutral-900'),
          950: rgb('--c-neutral-950'),
        },
        surface: {
          base: rgb('--c-surface-base'),
          1: rgb('--c-surface-1'),
          2: rgb('--c-surface-2'),
          3: rgb('--c-surface-3'),
          4: rgb('--c-surface-4'),
        },
        content: {
          primary: rgb('--c-text-primary'),
          secondary: rgb('--c-text-secondary'),
          tertiary: rgb('--c-text-tertiary'),
          inverse: rgb('--c-text-inverse'),
        },
        line: rgb('--c-line'),
        link: {
          DEFAULT: 'var(--c-link)',
          hover: 'var(--c-link-hover)',
        },
        success: rgb('--c-success'),
        warning: rgb('--c-warning'),
        error: rgb('--c-error'),
        info: rgb('--c-info'),
      },
      fontFamily: {
        sans: 'var(--font-sans)',
        mono: 'var(--font-mono)',
      },
      fontSize: {
        'display-xl': ['var(--fs-display-xl)', { lineHeight: '1.04', letterSpacing: '-0.02em' }],
        'display-lg': ['var(--fs-display-lg)', { lineHeight: '1.08', letterSpacing: '-0.018em' }],
        h1: ['var(--fs-h1)', { lineHeight: '1.12', letterSpacing: '-0.016em' }],
        h2: ['var(--fs-h2)', { lineHeight: '1.18', letterSpacing: '-0.012em' }],
        h3: ['var(--fs-h3)', { lineHeight: '1.24', letterSpacing: '-0.008em' }],
        h4: ['var(--fs-h4)', { lineHeight: '1.3' }],
        h5: ['var(--fs-h5)', { lineHeight: '1.36' }],
        h6: ['var(--fs-h6)', { lineHeight: '1.44' }],
        'body-lg': ['var(--fs-body-lg)', { lineHeight: '1.72' }],
        'body-md': ['var(--fs-body-md)', { lineHeight: '1.75' }],
        'body-sm': ['var(--fs-body-sm)', { lineHeight: '1.7' }],
        label: ['var(--fs-label)', { lineHeight: '1.45' }],
        caption: ['var(--fs-caption)', { lineHeight: '1.55' }],
        overline: ['var(--fs-overline)', { lineHeight: '1.4' }],
      },
      borderRadius: {
        sm: 'var(--radius-sm)',
        md: 'var(--radius-md)',
        lg: 'var(--radius-lg)',
        xl: 'var(--radius-xl)',
      },
      boxShadow: {
        sm: 'var(--shadow-sm)',
        md: 'var(--shadow-md)',
        lg: 'var(--shadow-lg)',
        glow: 'var(--shadow-glow)',
      },
      transitionTimingFunction: {
        'out-soft': 'var(--ease-out-soft)',
        'in-out-soft': 'var(--ease-in-out-soft)',
      },
      transitionDuration: {
        fast: 'var(--dur-fast)',
        base: 'var(--dur-base)',
        slow: 'var(--dur-slow)',
      },
      spacing: {
        'header-h': 'var(--header-h)',
      },
      maxWidth: {
        container: 'var(--container-max)',
      },
      keyframes: {
        'dash-flow': { to: { strokeDashoffset: '-24' } },
        'fade-up': {
          '0%': { opacity: '0', transform: 'translateY(16px)' },
          '100%': { opacity: '1', transform: 'none' },
        },
        'pulse-node': {
          '0%, 100%': { opacity: '.35', transform: 'scale(1)' },
          '50%': { opacity: '1', transform: 'scale(1.35)' },
        },
        'scroll-hint': {
          '0%, 100%': { transform: 'translateY(0)', opacity: '.4' },
          '50%': { transform: 'translateY(6px)', opacity: '1' },
        },
      },
      animation: {
        'dash-flow': 'dash-flow 1.4s linear infinite',
        'fade-up': 'fade-up var(--dur-slow) var(--ease-out-soft) both',
        'pulse-node': 'pulse-node 3.2s var(--ease-in-out-soft) infinite',
        'scroll-hint': 'scroll-hint 2s var(--ease-in-out-soft) infinite',
      },
    },
  },
  plugins: [],
};
