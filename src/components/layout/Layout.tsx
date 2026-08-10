import { useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { LocaleProvider } from '../../i18n/LocaleContext';
import { useUi } from '../../i18n/LocaleContext';
import type { Locale } from '../../i18n/locales';
import { Header } from './Header';
import { Footer } from './Footer';

/** Client-side navigation does not reset the scroll position on its own. */
function ScrollToTop() {
  const { pathname, hash } = useLocation();
  useEffect(() => {
    if (hash) return;
    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
  }, [pathname, hash]);
  return null;
}

function Shell() {
  const ui = useUi();
  return (
    <div className="flex min-h-dvh flex-col bg-surface-base">
      <a
        href="#main"
        className="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-primary-500 focus:px-4 focus:py-2 focus:text-white"
      >
        {ui.skipToContent}
      </a>
      <Header />
      <main id="main" className="flex-1">
        <Outlet />
      </main>
      <Footer />
    </div>
  );
}

export function Layout({ locale }: { locale: Locale }) {
  return (
    <LocaleProvider locale={locale}>
      <ScrollToTop />
      <Shell />
    </LocaleProvider>
  );
}
