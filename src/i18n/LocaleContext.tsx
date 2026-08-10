import { createContext, useCallback, useContext, useEffect, useMemo, type ReactNode } from 'react';
import type { LocaleContent } from '../content/types';
import { DEFAULT_LOCALE, LOCALE_META, localePath, type Locale } from './locales';

interface LocaleValue {
  locale: Locale;
  content: LocaleContent;
}

const LocaleContext = createContext<LocaleValue | null>(null);

/**
 * Locale content is code-split: `content-ka` and `content-en` are ~120 KB of
 * copy each, and a visitor should only ever download the one they are reading.
 * The loader is a plain module-level cache rather than React state so the
 * prerenderer and the client can both prime it before the first render.
 */
const LOADERS: Record<Locale, () => Promise<LocaleContent>> = {
  ka: () => import('../content/ka').then((module) => module.ka),
  en: () => import('../content/en').then((module) => module.en),
};

const cache = new Map<Locale, LocaleContent>();

export async function loadContent(locale: Locale): Promise<LocaleContent> {
  const cached = cache.get(locale);
  if (cached) return cached;
  const content = await LOADERS[locale]();
  cache.set(locale, content);
  return content;
}

export function getLoadedContent(locale: Locale): LocaleContent {
  const content = cache.get(locale);
  if (!content) {
    throw new Error(`Content for locale "${locale}" was requested before it finished loading.`);
  }
  return content;
}

export function LocaleProvider({ locale, children }: { locale: Locale; children: ReactNode }) {
  const value = useMemo(() => ({ locale, content: getLoadedContent(locale) }), [locale]);

  useEffect(() => {
    document.documentElement.lang = LOCALE_META[locale].htmlLang;
  }, [locale]);

  return <LocaleContext.Provider value={value}>{children}</LocaleContext.Provider>;
}

function useLocaleContext(): LocaleValue {
  const value = useContext(LocaleContext);
  if (!value) throw new Error('useLocaleContext must be used inside <LocaleProvider>');
  return value;
}

export function useLocale(): Locale {
  return useLocaleContext().locale;
}

export function useContent(): LocaleContent {
  return useLocaleContext().content;
}

export function useUi() {
  return useLocaleContext().content.ui;
}

/** Prefixes a route with the active locale: `/services` → `/en/services`. */
export function useLocalePath() {
  const { locale } = useLocaleContext();
  return useCallback((path = '/') => localePath(locale, path), [locale]);
}

export { DEFAULT_LOCALE };
