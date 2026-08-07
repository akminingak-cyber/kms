import { renderToString } from 'react-dom/server';
// v7 merged the packages: StaticRouter comes from the main entry point now,
// not the `react-router-dom/server` subpath that v6 used.
import { StaticRouter } from 'react-router-dom';
import { App } from './App';
import { loadContent } from './i18n/LocaleContext';
import { HeadCollector, type HeadData } from './components/seo/Seo';
import { splitLocale, localePath, LOCALES, DEFAULT_LOCALE, type Locale } from './i18n/locales';
import { SITE_ROUTES } from './i18n/routes';
import { COMPANY, ANALYTICS, analyticsScriptUrl } from './config/site';

/**
 * Re-exported so scripts/prerender.mjs reads the route table, the company facts
 * and the analytics settings from the application itself rather than keeping a
 * second copy that can drift.
 */
export { SITE_ROUTES, LOCALES, DEFAULT_LOCALE, localePath, COMPANY, ANALYTICS, analyticsScriptUrl };

export interface RenderResult {
  html: string;
  head: HeadData;
  locale: Locale;
}

/**
 * Renders one URL to static HTML and hands back the metadata the page declared
 * while rendering. Called once per route by scripts/prerender.mjs.
 *
 * The <Seo> component writes into `collector` instead of touching document.head
 * (there is no document here), which is what keeps the static <head> and the
 * client-side one in sync — they come from the same component.
 */
export async function render(url: string): Promise<RenderResult> {
  const { locale } = splitLocale(url);
  await loadContent(locale);

  const collector: { head: HeadData | null } = { head: null };

  const html = renderToString(
    <HeadCollector.Provider value={collector}>
      <StaticRouter location={url}>
        <App />
      </StaticRouter>
    </HeadCollector.Provider>,
  );

  if (!collector.head) {
    throw new Error(`Route "${url}" rendered without declaring <Seo> metadata.`);
  }

  return { html, head: collector.head, locale };
}
