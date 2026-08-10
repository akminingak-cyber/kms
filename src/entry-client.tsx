import { StrictMode } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { App } from './App';
import { loadContent } from './i18n/LocaleContext';
import { splitLocale } from './i18n/locales';
import './styles/index.css';

const container = document.getElementById('root');
if (!container) throw new Error('Root container #root not found');

const { locale } = splitLocale(window.location.pathname);

/**
 * The locale chunk has to be resolved before the first render — the content is
 * read synchronously from a module cache, and on a prerendered page hydration
 * must produce exactly the markup the server produced.
 */
void loadContent(locale).then(() => {
  const tree = (
    <StrictMode>
      <BrowserRouter>
        <App />
      </BrowserRouter>
    </StrictMode>
  );

  // Every route is prerendered, so #root normally already holds real markup and
  // we adopt it. The createRoot branch only runs if a page somehow arrives
  // without it — a 404 served by a misconfigured host, say.
  if (container.childElementCount > 0) {
    hydrateRoot(container, tree);
  } else {
    createRoot(container).render(tree);
  }
});
