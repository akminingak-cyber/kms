import { createContext, useContext, useEffect } from 'react';
import { COMPANY } from '../../config/site';
import { LOCALES, LOCALE_META, DEFAULT_LOCALE, localePath, type Locale } from '../../i18n/locales';
import { useLocale } from '../../i18n/LocaleContext';
import type { JsonLd } from './jsonld';

export interface HeadData {
  title: string;
  description: string;
  canonical: string;
  alternates: Array<{ hreflang: string; href: string }>;
  og: Array<{ property: string; content: string }>;
  meta: Array<{ name: string; content: string }>;
  jsonLd: JsonLd[];
  noindex: boolean;
}

/**
 * During prerendering the same <Seo> that manipulates document.head in the
 * browser instead writes into this collector, so the static HTML and the
 * client-side navigation can never describe a page differently — there is one
 * definition of a page's metadata, not two.
 */
export const HeadCollector = createContext<{ head: HeadData | null } | null>(null);

interface SeoProps {
  title: string;
  description: string;
  /** Locale-independent route, e.g. `/services/cctv`. */
  path: string;
  jsonLd?: JsonLd[];
  noindex?: boolean;
}

export function buildHead(
  locale: Locale,
  { title, description, path, jsonLd = [], noindex = false }: SeoProps,
): HeadData {
  const canonical = `${COMPANY.url}${localePath(locale, path)}`;
  const image = `${COMPANY.url}${LOCALE_META[locale].shareCard}`;

  const alternates = [
    ...LOCALES.map((code) => ({
      hreflang: code,
      href: `${COMPANY.url}${localePath(code, path)}`,
    })),
    { hreflang: 'x-default', href: `${COMPANY.url}${localePath(DEFAULT_LOCALE, path)}` },
  ];

  const og = [
    { property: 'og:title', content: title },
    { property: 'og:description', content: description },
    { property: 'og:url', content: canonical },
    { property: 'og:image', content: image },
    { property: 'og:image:width', content: '1200' },
    { property: 'og:image:height', content: '630' },
    { property: 'og:image:alt', content: title },
    { property: 'og:locale', content: LOCALE_META[locale].ogLocale },
    ...LOCALES.filter((code) => code !== locale).map((code) => ({
      property: 'og:locale:alternate',
      content: LOCALE_META[code].ogLocale,
    })),
  ];

  const meta = [
    { name: 'description', content: description },
    { name: 'twitter:title', content: title },
    { name: 'twitter:description', content: description },
    { name: 'twitter:image', content: image },
  ];

  if (noindex) meta.push({ name: 'robots', content: 'noindex,follow' });

  return { title, description, canonical, alternates, og, meta, jsonLd, noindex };
}

/** Marks the nodes this component owns, so a route change can clear them. */
const OWNED = 'data-kms-seo';

function setMeta(attribute: 'name' | 'property', key: string, content: string) {
  const existing = document.head.querySelector(`meta[${attribute}="${key}"]`);
  if (existing) {
    existing.setAttribute('content', content);
    return;
  }
  const node = document.createElement('meta');
  node.setAttribute(attribute, key);
  node.setAttribute('content', content);
  node.setAttribute(OWNED, '');
  document.head.appendChild(node);
}

function setLink(rel: string, href: string, hreflang?: string) {
  const selector = hreflang ? `link[rel="${rel}"][hreflang="${hreflang}"]` : `link[rel="${rel}"]`;
  const existing = document.head.querySelector(selector);
  if (existing) {
    existing.setAttribute('href', href);
    return;
  }
  const node = document.createElement('link');
  node.setAttribute('rel', rel);
  node.setAttribute('href', href);
  if (hreflang) node.setAttribute('hreflang', hreflang);
  node.setAttribute(OWNED, '');
  document.head.appendChild(node);
}

export function Seo(props: SeoProps) {
  const locale = useLocale();
  const collector = useContext(HeadCollector);
  const head = buildHead(locale, props);

  // Prerender pass: hand the metadata to the collector and render nothing.
  // Writing during render is safe here because the collector is a per-request
  // object created by the prerenderer, never shared or observed by React.
  if (collector) collector.head = head;

  useEffect(() => {
    if (typeof document === 'undefined') return;

    document.title = head.title;

    // Clears both what a previous route left behind and what the prerenderer
    // wrote into this page's HTML — the two are indistinguishable by design,
    // which is why the prerenderer stamps the same attribute. Skipping the
    // prerendered nodes would leave a second copy of every JSON-LD block.
    document.querySelectorAll(`[${OWNED}]`).forEach((node) => node.remove());

    // Any stray robots tag goes too: a client-side navigation away from the 404
    // must not carry its noindex onto the next page.
    if (!head.noindex) {
      document.head.querySelector('meta[name="robots"]')?.remove();
    }

    head.meta.forEach((entry) => setMeta('name', entry.name, entry.content));
    head.og.forEach((entry) => setMeta('property', entry.property, entry.content));

    // A 404 is not a page anyone should be pointed at, so it gets no canonical
    // and no alternates — only the noindex above.
    if (!head.noindex) {
      setLink('canonical', head.canonical);
      head.alternates.forEach((alt) => setLink('alternate', alt.href, alt.hreflang));
    }

    head.jsonLd.forEach((entry) => {
      const script = document.createElement('script');
      script.type = 'application/ld+json';
      script.textContent = JSON.stringify(entry);
      script.setAttribute(OWNED, '');
      document.head.appendChild(script);
    });

    return () => {
      document.querySelectorAll(`[${OWNED}]`).forEach((node) => node.remove());
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [head.title, head.description, head.canonical, head.noindex, JSON.stringify(head.jsonLd)]);

  return null;
}
