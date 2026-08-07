/**
 * Locale-independent route paths. Everything that builds a link — the nav, the
 * sitemap, the breadcrumbs, the prerenderer — reads them from here, so a slug
 * can never drift between the router and the sitemap.
 */
export const ROUTES = {
  home: '/',
  about: '/about',
  services: '/services',
  service: (slug: string) => `/services/${slug}`,
  industries: '/industries',
  industry: (slug: string) => `/industries/${slug}`,
  portfolio: '/portfolio',
  process: '/process',
  technologies: '/technologies',
  faq: '/faq',
  contact: '/contact',
  legal: (slug: string) => `/${slug}`,
} as const;

export const SERVICE_SLUGS = [
  'it-infrastructure',
  'networking',
  'cctv',
  'access-control',
  'smart-home',
  'smart-building',
  'audio-visual',
  'managed-it',
] as const;

export const INDUSTRY_SLUGS = [
  'healthcare',
  'corporate',
  'hospitality',
  'education',
  'industrial',
  'government',
  'retail',
  'residential',
] as const;

export const LEGAL_SLUGS = ['privacy', 'terms', 'cookies'] as const;

/**
 * Every indexable route, with the sitemap weighting each one carries.
 * The prerenderer walks this list; nothing else decides what gets built.
 */
export const SITE_ROUTES: Array<{ path: string; priority: number }> = [
  { path: ROUTES.home, priority: 1.0 },
  { path: ROUTES.about, priority: 0.7 },
  { path: ROUTES.services, priority: 0.9 },
  ...SERVICE_SLUGS.map((slug) => ({ path: ROUTES.service(slug), priority: 0.8 })),
  { path: ROUTES.industries, priority: 0.7 },
  ...INDUSTRY_SLUGS.map((slug) => ({ path: ROUTES.industry(slug), priority: 0.8 })),
  { path: ROUTES.portfolio, priority: 0.7 },
  { path: ROUTES.process, priority: 0.7 },
  { path: ROUTES.technologies, priority: 0.7 },
  { path: ROUTES.faq, priority: 0.7 },
  { path: ROUTES.contact, priority: 0.9 },
  ...LEGAL_SLUGS.map((slug) => ({ path: ROUTES.legal(slug), priority: 0.3 })),
];
