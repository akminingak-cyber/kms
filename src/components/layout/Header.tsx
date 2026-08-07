import { useEffect, useId, useRef, useState } from 'react';
import { Link, NavLink, useLocation } from 'react-router-dom';
import { COMPANY } from '../../config/site';
import { ROUTES } from '../../i18n/routes';
import { LOCALES, LOCALE_META, localePath, splitLocale } from '../../i18n/locales';
import { useContent, useLocale, useLocalePath, useUi } from '../../i18n/LocaleContext';
import { Icon } from '../ui/Icon';
import { Button } from '../ui/primitives';

function Logo() {
  const path = useLocalePath();
  const ui = useUi();
  return (
    <Link
      to={path('/')}
      className="group inline-flex shrink-0 items-center gap-3"
      aria-label={`${COMPANY.name} — ${ui.brandTagline}`}
    >
      <span className="relative inline-flex h-10 w-10 items-center justify-center rounded-md border border-primary-400/40 bg-gradient-to-br from-primary-500/30 to-accent-500/10">
        <svg viewBox="0 0 32 32" className="h-6 w-6" aria-hidden="true">
          <path
            d="M7 6 L7 26 M7 16 L18 6 M7 16 L18 26"
            stroke="rgb(226 238 255)"
            strokeWidth="2.4"
            strokeLinecap="round"
            strokeLinejoin="round"
            fill="none"
          />
          <circle cx="24" cy="9" r="2.6" fill="rgb(34 211 238)" />
          <circle cx="24" cy="23" r="2.6" fill="rgb(34 211 238)" fillOpacity="0.6" />
        </svg>
      </span>
      <span className="text-h5 font-extrabold leading-none tracking-tight text-content-primary">
        {COMPANY.name}
      </span>
    </Link>
  );
}

/**
 * Plain anchors, not <Link>: switching language reloads the document so the
 * other locale's content chunk is fetched and `<html lang>` is correct from the
 * very first byte, which matters for both screen readers and crawlers.
 */
function LanguageSwitcher({ className = '' }: { className?: string }) {
  const active = useLocale();
  const ui = useUi();
  const { pathname } = useLocation();
  const { path } = splitLocale(pathname);

  return (
    <div className={`flex items-center gap-1 ${className}`} role="group" aria-label={ui.language}>
      {LOCALES.map((code) => {
        const current = code === active;
        return (
          <a
            key={code}
            href={localePath(code, path)}
            hrefLang={LOCALE_META[code].htmlLang}
            aria-current={current ? 'true' : undefined}
            className={`rounded-sm px-2.5 py-1.5 text-caption font-semibold uppercase tracking-[0.08em] transition-colors duration-fast ${
              current
                ? 'bg-primary-500/15 text-primary-200'
                : 'text-content-tertiary hover:text-content-primary'
            }`}
          >
            {LOCALE_META[code].short}
          </a>
        );
      })}
    </div>
  );
}

function useScrolled(threshold = 16) {
  const [scrolled, setScrolled] = useState(false);
  useEffect(() => {
    const update = () => setScrolled(window.scrollY > threshold);
    update();
    window.addEventListener('scroll', update, { passive: true });
    return () => window.removeEventListener('scroll', update);
  }, [threshold]);
  return scrolled;
}

function MegaMenu({
  kind,
  onNavigate,
}: {
  kind: 'services' | 'industries';
  onNavigate: () => void;
}) {
  const content = useContent();
  const path = useLocalePath();
  const items = kind === 'services' ? content.services : content.industries;
  const to = kind === 'services' ? ROUTES.service : ROUTES.industry;

  return (
    <div className="grid gap-2 sm:grid-cols-2">
      {items.map((item) => (
        <Link
          key={item.slug}
          to={path(to(item.slug))}
          onClick={onNavigate}
          className="group flex items-start gap-3 rounded-md p-3 transition-colors duration-fast hover:bg-surface-2/70"
        >
          <span className="icon-badge inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md">
            <Icon name={item.icon} className="h-4 w-4" />
          </span>
          <span className="flex flex-col gap-1">
            <span className="text-label font-semibold text-content-primary">{item.name}</span>
            <span className="line-clamp-2 text-caption text-content-tertiary">{item.summary}</span>
          </span>
        </Link>
      ))}
    </div>
  );
}

export function Header() {
  const content = useContent();
  const ui = useUi();
  const path = useLocalePath();
  const scrolled = useScrolled();
  const { pathname } = useLocation();

  const [openMenu, setOpenMenu] = useState<'services' | 'industries' | null>(null);
  const [drawerOpen, setDrawerOpen] = useState(false);
  const desktopNavRef = useRef<HTMLDivElement>(null);
  const drawerRef = useRef<HTMLDivElement>(null);
  const toggleRef = useRef<HTMLButtonElement>(null);
  const drawerId = useId();

  // Any navigation closes everything.
  useEffect(() => {
    setOpenMenu(null);
    setDrawerOpen(false);
  }, [pathname]);

  // Desktop dropdown: click-away and Escape.
  useEffect(() => {
    if (!openMenu) return;
    const onPointerDown = (event: MouseEvent) => {
      if (desktopNavRef.current && !desktopNavRef.current.contains(event.target as Node)) {
        setOpenMenu(null);
      }
    };
    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') setOpenMenu(null);
    };
    document.addEventListener('mousedown', onPointerDown);
    document.addEventListener('keydown', onKeyDown);
    return () => {
      document.removeEventListener('mousedown', onPointerDown);
      document.removeEventListener('keydown', onKeyDown);
    };
  }, [openMenu]);

  // Mobile drawer: it covers the page, so it behaves like a dialog — Escape
  // closes it, Tab cycles inside it, and focus returns to the button that
  // opened it rather than being dumped at the top of the document.
  useEffect(() => {
    if (!drawerOpen) return;

    const previouslyFocused = document.activeElement as HTMLElement | null;
    // Captured now, not read in the cleanup: by the time cleanup runs the ref
    // may already point at a different node (or none).
    const toggleAtOpen = toggleRef.current;
    document.body.style.overflow = 'hidden';

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        setDrawerOpen(false);
        return;
      }
      if (event.key !== 'Tab' || !drawerRef.current) return;

      const focusable = drawerRef.current.querySelectorAll<HTMLElement>(
        'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])',
      );
      if (focusable.length === 0) return;
      const first = focusable[0];
      const last = focusable[focusable.length - 1];

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    document.addEventListener('keydown', onKeyDown);
    drawerRef.current?.querySelector<HTMLElement>('a[href], button')?.focus();

    return () => {
      document.removeEventListener('keydown', onKeyDown);
      document.body.style.overflow = '';
      (previouslyFocused ?? toggleAtOpen)?.focus?.();
    };
  }, [drawerOpen]);

  const simpleLinks = [
    { to: ROUTES.about, label: content.nav.about },
    { to: ROUTES.portfolio, label: content.nav.portfolio },
    { to: ROUTES.process, label: content.nav.process },
    { to: ROUTES.technologies, label: content.nav.technologies },
    { to: ROUTES.faq, label: content.nav.faq },
  ];

  const linkClass = ({ isActive }: { isActive: boolean }) =>
    `whitespace-nowrap rounded-sm px-3 py-2 text-label font-medium transition-colors duration-fast ${
      isActive ? 'text-content-primary' : 'text-content-secondary hover:text-content-primary'
    }`;

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 transition-all duration-base ease-out-soft ${
        scrolled || drawerOpen
          ? 'border-b border-line/10 bg-surface-base/95 backdrop-blur-xl'
          : 'border-b border-transparent bg-transparent'
      }`}
    >
      <div className="container-kms flex h-header-h items-center justify-between gap-6">
        <Logo />

        <div ref={desktopNavRef} className="hidden items-center gap-1 lg:flex">
          {(['services', 'industries'] as const).map((kind) => (
            <button
              key={kind}
              type="button"
              aria-expanded={openMenu === kind}
              className="inline-flex items-center gap-1.5 rounded-sm px-3 py-2 text-label font-medium text-content-secondary transition-colors duration-fast hover:text-content-primary"
              onClick={() => setOpenMenu(openMenu === kind ? null : kind)}
            >
              {content.nav[kind]}
              <Icon
                name="chevron-down"
                className={`h-4 w-4 transition-transform duration-base ${
                  openMenu === kind ? 'rotate-180' : ''
                }`}
              />
            </button>
          ))}

          {simpleLinks.map((link) => (
            <NavLink key={link.to} to={path(link.to)} className={linkClass} end>
              {link.label}
            </NavLink>
          ))}

          {openMenu && (
            <div className="absolute inset-x-0 top-header-h">
              <div className="container-kms">
                <div className="menu-panel max-h-[70vh] overflow-y-auto rounded-lg p-6 shadow-lg">
                  <MegaMenu kind={openMenu} onNavigate={() => setOpenMenu(null)} />
                </div>
              </div>
            </div>
          )}
        </div>

        <div className="flex items-center gap-2">
          <LanguageSwitcher className="hidden sm:flex" />
          <Button to={path(ROUTES.contact)} icon="arrow-right" className="hidden sm:inline-flex">
            {content.nav.contact}
          </Button>
          <button
            ref={toggleRef}
            type="button"
            aria-label={drawerOpen ? ui.close : ui.menu}
            aria-expanded={drawerOpen}
            aria-controls={drawerId}
            className="panel inline-flex h-11 w-11 items-center justify-center rounded-md text-content-primary lg:hidden"
            onClick={() => setDrawerOpen((open) => !open)}
          >
            <Icon name={drawerOpen ? 'x' : 'menu'} className="h-5 w-5" />
          </button>
        </div>
      </div>

      {drawerOpen && (
        <div
          ref={drawerRef}
          id={drawerId}
          role="dialog"
          aria-modal="true"
          aria-label={ui.menu}
          className="h-[calc(100dvh-var(--header-h))] overflow-y-auto border-t border-line/10 bg-surface-base lg:hidden"
        >
          <div className="container-kms flex flex-col gap-8 py-8">
            {(['services', 'industries'] as const).map((kind) => (
              <div key={kind} className="flex flex-col gap-3">
                <h2 className="text-overline font-semibold uppercase tracking-[0.14em] text-accent-400">
                  {content.nav[kind]}
                </h2>
                <ul className="flex flex-col">
                  {(kind === 'services' ? content.services : content.industries).map((item) => (
                    <li key={item.slug}>
                      <Link
                        to={path(
                          kind === 'services' ? ROUTES.service(item.slug) : ROUTES.industry(item.slug),
                        )}
                        className="flex items-center gap-3 rounded-md py-2.5 text-body-sm text-content-secondary"
                      >
                        <Icon name={item.icon} className="h-4 w-4 shrink-0 text-accent-400" />
                        {item.name}
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            ))}

            <ul className="flex flex-col gap-1 border-t border-line/10 pt-6">
              {simpleLinks.map((link) => (
                <li key={link.to}>
                  <Link
                    to={path(link.to)}
                    className="block rounded-md py-2.5 text-body-md font-medium text-content-primary"
                  >
                    {link.label}
                  </Link>
                </li>
              ))}
            </ul>

            <div className="flex flex-col gap-4 border-t border-line/10 pt-6">
              <LanguageSwitcher />
              <Button to={path(ROUTES.contact)} icon="arrow-right" size="lg">
                {content.nav.contact}
              </Button>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
