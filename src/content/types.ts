import type { IconName } from '../components/ui/Icon';

export interface Seo {
  title: string;
  description: string;
}

export interface QA {
  q: string;
  a: string;
}

export interface Feature {
  title: string;
  description: string;
  icon: IconName;
}

/** A titled list of short strings — used for capability and standards grids. */
export interface Group {
  title: string;
  items: string[];
}

export interface Hero {
  eyebrow: string;
  headline: string;
  description: string;
  highlights?: string[];
}

export interface Ui {
  brandTagline: string;
  skipToContent: string;
  menu: string;
  close: string;
  language: string;
  languageNote: string;
  allServices: string;
  allIndustries: string;
  readMore: string;
  learnMore: string;
  viewAll: string;
  backTo: string;
  relatedServices: string;
  relatedIndustries: string;
  onThisPage: string;
  breadcrumbHome: string;
  ctaPrimary: string;
  ctaSecondary: string;
  callUs: string;
  emailSales: string;
  emailSupport: string;
  whatsapp: string;
  address: string;
  viewOnMap: string;
  notFoundTitle: string;
  notFoundText: string;
  goHome: string;
  projectKind: Record<'reference-architecture' | 'concept-design' | 'delivered', string>;
  form: {
    name: string;
    company: string;
    email: string;
    phone: string;
    service: string;
    servicePlaceholder: string;
    message: string;
    messagePlaceholder: string;
    consent: string;
    submit: string;
    submitting: string;
    successTitle: string;
    successText: string;
    errorTitle: string;
    errorText: string;
    fallbackNotice: string;
    fallbackAction: string;
    required: string;
    invalidEmail: string;
    tooShort: string;
    /** Screen-reader label for the live region that announces submit state. */
    statusLabel: string;
  };
}

export interface Nav {
  home: string;
  about: string;
  services: string;
  industries: string;
  portfolio: string;
  process: string;
  technologies: string;
  faq: string;
  contact: string;
}

export interface Footer {
  description: string;
  columns: Array<{ title: string; links: Array<{ label: string; to: string }> }>;
  contactTitle: string;
  legalNote: string;
  copyright: string;
  builtNote: string;
}

export interface HomeContent {
  hero: {
    eyebrow: string;
    headlineLead: string;
    headlineAccent: string;
    description: string;
    primaryCta: string;
    secondaryCta: string;
    scrollHint: string;
    pillars: Array<{ label: string; icon: IconName }>;
  };
  trust: { heading: string; items: Feature[] };
  intro: { eyebrow: string; heading: string; paragraphs: string[]; points: string[]; cta: string };
  services: { eyebrow: string; heading: string; description: string; cta: string };
  industries: { eyebrow: string; heading: string; description: string; cta: string };
  why: { eyebrow: string; heading: string; description: string; items: Feature[] };
  smart: {
    eyebrow: string;
    heading: string;
    description: string;
    scenarios: Feature[];
    cta: string;
  };
  technologies: {
    eyebrow: string;
    heading: string;
    description: string;
    disclaimer: string;
    cta: string;
  };
  process: { eyebrow: string; heading: string; description: string; cta: string };
  projects: {
    eyebrow: string;
    heading: string;
    description: string;
    notice: string;
    cta: string;
  };
  metrics: {
    eyebrow: string;
    heading: string;
    description: string;
    note: string;
    items: Array<{ value: string; label: string; description: string; icon: IconName }>;
  };
  faq: { eyebrow: string; heading: string; description: string; cta: string };
  cta: {
    eyebrow: string;
    heading: string;
    description: string;
    primary: string;
    secondary: string;
    points: string[];
  };
  seo: Seo;
}

export interface AboutContent {
  hero: Hero;
  story: { heading: string; paragraphs: string[] };
  mission: { heading: string; intro?: string; items: Feature[] };
  principles: { heading: string; intro?: string; items: Feature[] };
  disciplines: { heading: string; intro: string; groups: Group[] };
  /** Plain strings here, not Features — this block renders as a checklist. */
  standards: { heading: string; intro: string; items: string[]; note: string };
  seo: Seo;
}

export interface Service {
  slug: string;
  icon: IconName;
  category: string;
  name: string;
  summary: string;
  hero: Hero;
  overview: { heading: string; paragraphs: string[]; highlights: Feature[] };
  challenges: { heading: string; intro?: string; items: Feature[] };
  solutions: { heading: string; intro?: string; items: Feature[] };
  benefits: { heading: string; intro?: string; items: Feature[] };
  capabilities: { heading: string; intro?: string; groups: Group[] };
  stack: { heading: string; intro?: string; groups: Group[] };
  /** Industry slugs this service is most relevant to. */
  industries: string[];
  /** Sibling service slugs to cross-link. */
  related: string[];
  faq: QA[];
  seo: Seo;
}

export interface Industry {
  slug: string;
  icon: IconName;
  name: string;
  summary: string;
  hero: Hero;
  context: { heading: string; paragraphs: string[] };
  priorities: { heading: string; intro?: string; items: Feature[] };
  solutions: { heading: string; intro?: string; items: Feature[] };
  scenarios: { heading: string; intro?: string; items: Feature[] };
  /** Service slugs delivered into this sector. */
  services: string[];
  faq: QA[];
  seo: Seo;
}

export interface ServicesIndex {
  hero: Hero;
  /** `key` matches Service.category, which is how services are bucketed. */
  groups: Array<{ key: string; title: string; description: string }>;
  seo: Seo;
}

export interface IndustriesIndex {
  hero: Hero;
  seo: Seo;
}

export interface Project {
  slug: string;
  kind: 'reference-architecture' | 'concept-design' | 'delivered';
  name: string;
  summary: string;
  industry: string;
  services: string[];
  scope: string[];
  outcomes: string[];
  icon: IconName;
}

export interface PortfolioContent {
  hero: Hero;
  notice: { heading: string; text: string };
  filtersAll: string;
  projects: Project[];
  cta: { heading: string; description: string; action: string };
  seo: Seo;
}

export interface ProcessContent {
  hero: Hero;
  intro: { heading: string; paragraphs: string[] };
  steps: Array<{
    /** Pre-formatted "01".."06" — the numbering is content, not a loop index. */
    index: string;
    title: string;
    description: string;
    deliverables: string[];
    icon: IconName;
  }>;
  quality: { heading: string; intro?: string; items: Feature[] };
  /** Handover contents, rendered as a checklist of plain strings. */
  documentation: { heading: string; intro?: string; items: string[] };
  faq: QA[];
  seo: Seo;
}

export interface TechnologiesContent {
  hero: Hero;
  intro: { heading: string; paragraphs: string[] };
  disclaimer: string;
  diagram: {
    heading: string;
    description: string;
    alt: string;
    layers: Group[];
  };
  domains: Array<{ title: string; description: string; icon: IconName; items: string[] }>;
  selection: { heading: string; intro?: string; items: Feature[] };
  standards: { heading: string; intro?: string; groups: Group[] };
  faq: QA[];
  seo: Seo;
}

export interface FaqContent {
  hero: Hero;
  categories: Array<{ title: string; items: QA[] }>;
  cta: { heading: string; description: string; action: string };
  seo: Seo;
}

export interface ContactContent {
  hero: Hero;
  channels: { heading: string; description: string };
  formHeading: string;
  formDescription: string;
  serviceOptions: Array<{ value: string; label: string }>;
  expectations: { heading: string; items: Feature[] };
  hours: { heading: string; lines: string[]; note: string };
  seo: Seo;
}

export interface LegalPage {
  slug: string;
  title: string;
  updated: string;
  intro: string;
  sections: Array<{ heading: string; paragraphs: string[]; bullets?: string[] }>;
  seo: Seo;
}

export interface LocaleContent {
  ui: Ui;
  nav: Nav;
  footer: Footer;
  home: HomeContent;
  about: AboutContent;
  servicesIndex: ServicesIndex;
  services: Service[];
  industriesIndex: IndustriesIndex;
  industries: Industry[];
  portfolio: PortfolioContent;
  process: ProcessContent;
  technologies: TechnologiesContent;
  faq: FaqContent;
  contact: ContactContent;
  legal: LegalPage[];
}
