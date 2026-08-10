import { COMPANY, socialLinks } from '../../config/site';
import { LOCALE_META, localePath, type Locale } from '../../i18n/locales';

export type JsonLd = Record<string, unknown>;

const absolute = (locale: Locale, path: string) => `${COMPANY.url}${localePath(locale, path)}`;

/**
 * Strips keys whose value is empty, so an unconfigured postal code or set of
 * social profiles produces no key at all rather than an empty string — which
 * validators flag and which tells Google nothing.
 */
function compact<T extends Record<string, unknown>>(input: T): T {
  return Object.fromEntries(
    Object.entries(input).filter(([, value]) => {
      if (value == null) return false;
      if (typeof value === 'string') return value.trim().length > 0;
      if (Array.isArray(value)) return value.length > 0;
      return true;
    }),
  ) as T;
}

export function localBusiness(locale: Locale, description: string): JsonLd {
  const sameAs = socialLinks().map((link) => link.url);

  const geo =
    COMPANY.geo.latitude && COMPANY.geo.longitude
      ? {
          '@type': 'GeoCoordinates',
          latitude: COMPANY.geo.latitude,
          longitude: COMPANY.geo.longitude,
        }
      : undefined;

  const openingHours = COMPANY.openingHours?.map((slot) => ({
    '@type': 'OpeningHoursSpecification',
    dayOfWeek: slot.days,
    opens: slot.opens,
    closes: slot.closes,
  }));

  return compact({
    '@context': 'https://schema.org',
    '@type': 'LocalBusiness',
    '@id': `${COMPANY.url}/#organization`,
    name: COMPANY.name,
    legalName: COMPANY.legalName,
    taxID: COMPANY.taxId,
    url: COMPANY.url,
    description,
    telephone: COMPANY.phone,
    email: COMPANY.emailSales,
    image: `${COMPANY.url}${LOCALE_META[locale].shareCard}`,
    logo: `${COMPANY.url}/favicon.svg`,
    areaServed: COMPANY.region,
    address: compact({
      '@type': 'PostalAddress',
      streetAddress: COMPANY.address.street[locale],
      addressLocality: COMPANY.address.locality[locale],
      postalCode: COMPANY.address.postalCode,
      addressCountry: COMPANY.country,
    }),
    geo,
    openingHoursSpecification: openingHours,
    sameAs,
    contactPoint: [
      {
        '@type': 'ContactPoint',
        contactType: 'sales',
        telephone: COMPANY.phone,
        email: COMPANY.emailSales,
        availableLanguage: ['ka', 'en'],
      },
      {
        '@type': 'ContactPoint',
        contactType: 'technical support',
        email: COMPANY.emailSupport,
        availableLanguage: ['ka', 'en'],
      },
    ],
    inLanguage: locale,
  });
}

export function webPage(locale: Locale, path: string, name: string, description: string): JsonLd {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebPage',
    '@id': `${absolute(locale, path)}#webpage`,
    url: absolute(locale, path),
    name,
    description,
    inLanguage: locale,
    isPartOf: { '@id': `${COMPANY.url}/#organization` },
  };
}

export function breadcrumbs(
  locale: Locale,
  trail: Array<{ label: string; path: string }>,
): JsonLd {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: trail.map((crumb, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: crumb.label,
      item: absolute(locale, crumb.path),
    })),
  };
}

export function service(locale: Locale, path: string, name: string, description: string): JsonLd {
  return {
    '@context': 'https://schema.org',
    '@type': 'Service',
    name,
    description,
    serviceType: name,
    url: absolute(locale, path),
    areaServed: COMPANY.region,
    provider: { '@id': `${COMPANY.url}/#organization` },
  };
}

export function faqPage(items: Array<{ q: string; a: string }>): JsonLd {
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: items.map((item) => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: { '@type': 'Answer', text: item.a },
    })),
  };
}
