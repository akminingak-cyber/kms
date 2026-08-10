import { Fragment } from 'react';
import { Link } from 'react-router-dom';
import { ROUTES } from '../../i18n/routes';
import { useLocalePath, useUi } from '../../i18n/LocaleContext';
import { Icon, type IconName } from '../ui/Icon';
import { Container, Eyebrow, Reveal } from '../ui/primitives';

export interface Crumb {
  label: string;
  path?: string;
}

export function Breadcrumbs({ items }: { items: Crumb[] }) {
  const path = useLocalePath();
  const ui = useUi();

  return (
    // The landmark is named for what it is, not for its first link — labelling
    // it "Home" made a screen reader announce "Home navigation" on every page.
    <nav aria-label={ui.breadcrumbNav}>
      <ol className="flex flex-wrap items-center gap-1.5 text-caption text-content-tertiary">
        <li>
          <Link to={path(ROUTES.home)} className="transition-colors duration-fast hover:text-content-primary">
            {ui.breadcrumbHome}
          </Link>
        </li>
        {items.map((item, index) => (
          <Fragment key={item.label}>
            <li aria-hidden="true">
              <Icon name="chevron-right" className="h-3.5 w-3.5" />
            </li>
            <li>
              {item.path && index < items.length - 1 ? (
                <Link
                  to={path(item.path)}
                  className="transition-colors duration-fast hover:text-content-primary"
                >
                  {item.label}
                </Link>
              ) : (
                <span aria-current="page" className="text-content-secondary">
                  {item.label}
                </span>
              )}
            </li>
          </Fragment>
        ))}
      </ol>
    </nav>
  );
}

interface PageHeroProps {
  content: { eyebrow: string; headline: string; description: string; highlights?: string[] };
  crumbs: Crumb[];
  icon?: IconName;
}

export function PageHero({ content, crumbs, icon }: PageHeroProps) {
  return (
    <section className="relative overflow-hidden border-b border-line/10 bg-gradient-to-b from-primary-950/50 via-surface-base to-surface-base pb-16 pt-[calc(var(--header-h)+3rem)] lg:pb-20 lg:pt-[calc(var(--header-h)+5rem)]">
      <div
        className="pointer-events-none absolute inset-0 bg-grid-faint [background-size:56px_56px] [mask-image:linear-gradient(to_bottom,black,transparent_85%)]"
        aria-hidden="true"
      />
      <div
        className="pointer-events-none absolute -right-24 -top-24 h-[420px] w-[420px] rounded-full bg-primary-500/10 blur-3xl"
        aria-hidden="true"
      />
      <Container>
        <div className="relative flex flex-col gap-6">
          <Breadcrumbs items={crumbs} />
          <div className="flex items-center gap-4">
            {icon && (
              <span className="icon-badge inline-flex h-12 w-12 items-center justify-center rounded-md">
                <Icon name={icon} className="h-5 w-5" />
              </span>
            )}
            <Eyebrow>{content.eyebrow}</Eyebrow>
          </div>
          <h1 className="max-w-4xl text-h1 font-semibold text-content-primary">{content.headline}</h1>
          <p className="max-w-3xl text-body-lg text-content-secondary">{content.description}</p>
          {content.highlights && content.highlights.length > 0 && (
            <Reveal delay={80}>
              <ul className="flex flex-wrap gap-2.5">
                {content.highlights.map((item) => (
                  <li
                    key={item}
                    className="flex items-center gap-2 rounded-md border border-line/10 bg-surface-1/60 px-3.5 py-2 text-caption text-content-secondary"
                  >
                    <Icon name="check" className="h-3.5 w-3.5 shrink-0 text-accent-400" />
                    {item}
                  </li>
                ))}
              </ul>
            </Reveal>
          )}
        </div>
      </Container>
    </section>
  );
}
