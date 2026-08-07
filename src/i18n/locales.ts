export const LOCALES = ['ka', 'en'] as const;
export type Locale = (typeof LOCALES)[number];

/**
 * Georgian is the default, so it owns the bare paths (`/services`) and English
 * is prefixed (`/en/services`). That keeps the primary market's URLs short and
 * makes `/` the x-default target.
 */
export const DEFAULT_LOCALE: Locale = 'ka';

export const LOCALE_META: Record<Locale, { native: string; short: string; htmlLang: string; ogLocale: string }> = {
  ka: { native: 'ქართული', short: 'KA', htmlLang: 'ka-GE', ogLocale: 'ka_GE' },
  // Open Graph wants a full language_TERRITORY tag; a bare "en" is silently
  // ignored by Facebook's parser.
  en: { native: 'English', short: 'EN', htmlLang: 'en', ogLocale: 'en_US' },
};

export function isLocale(value: string): value is Locale {
  return (LOCALES as readonly string[]).includes(value);
}

/** Build the public URL for `path` in `locale`. */
export function localePath(locale: Locale, path = '/'): string {
  const clean = path === '/' ? '' : `/${path.replace(/^\/+|\/+$/g, '')}`;
  return `${locale === DEFAULT_LOCALE ? '' : `/${locale}`}${clean}` || '/';
}

/** Split a pathname into its locale and the locale-independent route. */
export function splitLocale(pathname: string): { locale: Locale; path: string } {
  const [, first = '', ...rest] = pathname.split('/');
  if (isLocale(first) && first !== DEFAULT_LOCALE) {
    return { locale: first, path: `/${rest.join('/')}`.replace(/\/+$/, '') || '/' };
  }
  return { locale: DEFAULT_LOCALE, path: pathname.replace(/\/+$/, '') || '/' };
}
