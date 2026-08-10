import { Route, Routes } from 'react-router-dom';
import { LOCALES, DEFAULT_LOCALE } from './i18n/locales';
import { Layout } from './components/layout/Layout';
import { HomePage } from './pages/Home';
import {
  AboutPage,
  ContactPage,
  FaqPage,
  IndustriesIndexPage,
  IndustryDetailPage,
  LegalPage,
  NotFoundPage,
  PortfolioPage,
  ProcessPage,
  ServiceDetailPage,
  ServicesIndexPage,
  TechnologiesPage,
} from './pages/content-pages';

/** The route table, rendered once per locale under its own prefix. */
function LocaleRoutes() {
  return (
    <>
      <Route index element={<HomePage />} />
      <Route path="about" element={<AboutPage />} />
      <Route path="services" element={<ServicesIndexPage />} />
      <Route path="services/:slug" element={<ServiceDetailPage />} />
      <Route path="industries" element={<IndustriesIndexPage />} />
      <Route path="industries/:slug" element={<IndustryDetailPage />} />
      <Route path="portfolio" element={<PortfolioPage />} />
      <Route path="process" element={<ProcessPage />} />
      <Route path="technologies" element={<TechnologiesPage />} />
      <Route path="faq" element={<FaqPage />} />
      <Route path="contact" element={<ContactPage />} />
      <Route path="privacy" element={<LegalPage slug="privacy" />} />
      <Route path="terms" element={<LegalPage slug="terms" />} />
      <Route path="cookies" element={<LegalPage slug="cookies" />} />
      <Route path="*" element={<NotFoundPage />} />
    </>
  );
}

const PREFIXED = LOCALES.filter((locale) => locale !== DEFAULT_LOCALE);

export function App() {
  return (
    <Routes>
      {PREFIXED.map((locale) => (
        <Route key={locale} path={`/${locale}`} element={<Layout locale={locale} />}>
          {LocaleRoutes()}
        </Route>
      ))}
      <Route path="/" element={<Layout locale={DEFAULT_LOCALE} />}>
        {LocaleRoutes()}
      </Route>
    </Routes>
  );
}
