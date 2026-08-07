import type { Locale } from '../i18n/locales';

/**
 * Every fact about the company lives here.
 *
 * The site, the structured data and the legal pages all read from this object,
 * so a phone number or an address is changed in exactly one place.
 */
export const COMPANY = {
  name: 'KMS',

  /**
   * Registered entity name and tax ID. Leave empty and the site simply omits
   * them; fill them in and they appear in the privacy policy (which has to name
   * the data controller) and in the structured data.
   */
  legalName: '',
  taxId: '',

  url: 'https://kms.ge',

  phone: '+995597439797',
  phoneDisplay: '+995 597 43 97 97',
  emailSales: 'sales@kms.ge',
  emailSupport: 'support@kms.ge',

  country: 'GE',
  region: 'Georgia',

  address: {
    street: { ka: 'ალ. მირცხულავას ქუჩა 16', en: '16 Al. Mirtskhulava Street' },
    locality: { ka: 'თბილისი', en: 'Tbilisi' },
    country: { ka: 'საქართველო', en: 'Georgia' },
    /** Optional. Emitted as PostalAddress.postalCode when set. */
    postalCode: '',
  },

  /**
   * Optional map pin. Emitted as schema.org GeoCoordinates when both are set —
   * this is what lets Google place the business on a map.
   */
  geo: {
    latitude: '',
    longitude: '',
  },

  /**
   * Opening hours in schema.org form. `null` means "not published": the site
   * then falls back to the prose in the contact page content and emits no
   * openingHoursSpecification rather than guessing.
   */
  openingHours: null as null | Array<{
    days: Array<'Monday' | 'Tuesday' | 'Wednesday' | 'Thursday' | 'Friday' | 'Saturday' | 'Sunday'>;
    opens: string;
    closes: string;
  }>,

  /**
   * Public profiles. Every non-empty URL is emitted as schema.org `sameAs` and
   * rendered in the footer; an empty object renders nothing.
   */
  social: {} as Partial<Record<'facebook' | 'linkedin' | 'instagram' | 'youtube', string>>,
} as const;

/**
 * Contact form delivery.
 *
 * The Web3Forms access key is public by design — it identifies the inbox, it
 * does not authorise anything. What stops it being abused is the domain
 * restriction configured in the Web3Forms dashboard, which rejects submissions
 * that do not originate from this site.
 */
export const FORM = {
  endpoint: 'https://api.web3forms.com/submit',
  accessKey: '2720eb8f-e4a1-40ca-837b-c41d3e5a2d9f',
} as const;

export const FORM_ENABLED = FORM.accessKey.trim().length > 0;

/**
 * Analytics.
 *
 * Plausible is cookieless and stores nothing on the visitor's device, which is
 * why the cookie policy can still say the site sets no cookies and why no
 * consent banner is required. Set `enabled: false` and every trace of it —
 * script tag and CSP entry alike — disappears from the build.
 *
 * `host` points at Plausible's cloud by default; change it to a self-hosted
 * instance and the CSP in .htaccess follows automatically (it is generated
 * from this value by scripts/prerender.mjs).
 */
export const ANALYTICS = {
  enabled: true,
  provider: 'plausible' as const,
  host: 'https://plausible.io',
  domain: 'kms.ge',
} as const;

export function analyticsScriptUrl(): string {
  return `${ANALYTICS.host}/js/script.js`;
}

export function addressLine(locale: Locale): string {
  const { street, locality, country } = COMPANY.address;
  return `${street[locale]}, ${locality[locale]}, ${country[locale]}`;
}

export const WHATSAPP_URL = `https://wa.me/${COMPANY.phone.replace(/[^0-9]/g, '')}`;
export const TEL_URL = `tel:${COMPANY.phone}`;
export const MAILTO_SALES = `mailto:${COMPANY.emailSales}`;
export const MAILTO_SUPPORT = `mailto:${COMPANY.emailSupport}`;
export const MAP_URL = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(
  `${COMPANY.address.street.en}, ${COMPANY.address.locality.en}, ${COMPANY.address.country.en}`,
)}`;

/** Non-empty social URLs, in a stable order. */
export function socialLinks(): Array<{ key: string; url: string }> {
  return Object.entries(COMPANY.social)
    .filter(([, url]) => typeof url === 'string' && url.trim().length > 0)
    .map(([key, url]) => ({ key, url: url as string }));
}
