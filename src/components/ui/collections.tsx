import { Link } from 'react-router-dom';
import { ROUTES } from '../../i18n/routes';
import { Figure } from '../media/Figure';
import { industryMedia, serviceMedia } from '../../content/media';
import { useLocalePath, useUi } from '../../i18n/LocaleContext';
import type { Industry, Service } from '../../content/types';
import { Icon } from './Icon';
import { Reveal } from './primitives';

const CARD_BASE =
  'group flex h-full flex-col overflow-hidden rounded-lg border border-line/10 bg-surface-1/50 transition-all duration-base ease-out-soft hover:-translate-y-1 hover:border-primary-400/40 hover:shadow-lg';

/**
 * `illustrated` gives each card the drawing from its detail page as a header
 * band. It is off in the four-column layouts, where the band would be too
 * small to read as anything but noise.
 */
export function ServiceGrid({
  services,
  illustrated = false,
}: {
  services: Service[];
  illustrated?: boolean;
}) {
  const path = useLocalePath();
  const ui = useUi();
  // Two across when illustrated. The service groups hold 2, 2, 3 and 1 items,
  // so a three-column grid would leave a hole in every one of them; two also
  // gives the drawing enough width to stay readable.
  const columns = illustrated ? 'sm:grid-cols-2' : 'sm:grid-cols-2 lg:grid-cols-4';

  return (
    <div className={`grid gap-5 ${columns}`}>
      {services.map((service, index) => {
        const media = illustrated ? serviceMedia(service.slug) : undefined;
        return (
          <Reveal key={service.slug} delay={index * 50} className="h-full">
            <Link to={path(ROUTES.service(service.slug))} className={CARD_BASE}>
              {media && (
                <div className="relative border-b border-line/10 bg-surface-2/40">
                  <Figure name={media} thumb />
                </div>
              )}
              <div className="flex flex-1 flex-col gap-4 p-6">
                <span className="icon-badge inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md">
                  <Icon name={service.icon} className="h-5 w-5" />
                </span>
                <h3 className="text-h6 font-semibold text-content-primary">{service.name}</h3>
                <p className="flex-1 text-body-sm text-content-tertiary">{service.summary}</p>
                <span className="inline-flex items-center gap-1.5 text-caption font-medium text-link">
                  {ui.readMore}
                  <Icon
                    name="arrow-right"
                    className="h-3.5 w-3.5 transition-transform duration-base group-hover:translate-x-0.5"
                  />
                </span>
              </div>
            </Link>
          </Reveal>
        );
      })}
    </div>
  );
}

export function IndustryGrid({
  industries,
  illustrated = false,
}: {
  industries: Industry[];
  illustrated?: boolean;
}) {
  const path = useLocalePath();
  const ui = useUi();

  return (
    // Four across either way: eight industries fill two clean rows, and the
    // thumbnail reads as texture at this width, which is all it needs to do.
    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {industries.map((industry, index) => {
        const media = illustrated ? industryMedia(industry.slug) : undefined;
        return (
          <Reveal key={industry.slug} delay={index * 50} className="h-full">
            <Link to={path(ROUTES.industry(industry.slug))} className={CARD_BASE}>
              {media && (
                <div className="relative border-b border-line/10 bg-surface-2/40">
                  <Figure name={media} thumb />
                </div>
              )}
              <div className="flex flex-1 flex-col gap-4 p-6">
                <Icon name={industry.icon} className="h-6 w-6 text-accent-400" />
                <h3 className="text-h6 font-semibold text-content-primary">{industry.name}</h3>
                <p className="flex-1 text-body-sm text-content-tertiary">{industry.summary}</p>
                <span className="inline-flex items-center gap-1.5 text-caption font-medium text-link">
                  {ui.learnMore}
                  <Icon
                    name="arrow-right"
                    className="h-3.5 w-3.5 transition-transform duration-base group-hover:translate-x-0.5"
                  />
                </span>
              </div>
            </Link>
          </Reveal>
        );
      })}
    </div>
  );
}

export function StepList({
  steps,
  compact = false,
}: {
  steps: Array<{ index: string; title: string; description: string; deliverables: string[]; icon: string }>;
  compact?: boolean;
}) {
  return (
    <ol className="flex flex-col gap-4">
      {steps.map((step, position) => (
        <Reveal key={step.title} delay={position * 60} as="li">
          <div className="relative flex gap-5 rounded-lg border border-line/10 bg-surface-1/50 p-6">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-primary-400/30 bg-primary-500/10 text-label font-bold text-accent-400">
              {step.index}
            </span>
            <div className="flex flex-col gap-3">
              <h3 className="text-h6 font-semibold text-content-primary">{step.title}</h3>
              <p className="text-body-sm text-content-tertiary">{step.description}</p>
              {!compact && step.deliverables.length > 0 && (
                <ul className="mt-1 flex flex-wrap gap-2">
                  {step.deliverables.map((item) => (
                    <li
                      key={item}
                      className="rounded-sm border border-line/10 bg-surface-2/60 px-2.5 py-1 text-caption text-content-secondary"
                    >
                      {item}
                    </li>
                  ))}
                </ul>
              )}
            </div>
          </div>
        </Reveal>
      ))}
    </ol>
  );
}

/**
 * The four-layer stack from the technologies page. Drawn as an ordered list
 * rather than a picture so the layers are readable as text — the connecting
 * arrows are the only decorative part.
 */
export function LayerDiagram({
  layers,
  alt,
  className = '',
}: {
  layers: Array<{ title: string; items: string[] }>;
  alt: string;
  className?: string;
}) {
  const icons = ['gauge', 'layers', 'network', 'cable'] as const;

  return (
    <div
      className={`relative overflow-hidden rounded-lg border border-line/10 bg-surface-1/50 p-6 sm:p-8 ${className}`}
      role="img"
      aria-label={alt}
    >
      <div
        className="pointer-events-none absolute inset-0 bg-grid-faint [background-size:40px_40px] [mask-image:radial-gradient(ellipse_at_center,black,transparent_80%)]"
        aria-hidden="true"
      />
      <ol className="relative flex flex-col">
        {layers.map((layer, index) => {
          const last = index === layers.length - 1;
          return (
            <li key={layer.title} className="relative">
              <div className="flex flex-col gap-4 rounded-md border border-line/10 bg-surface-2/60 p-5 sm:flex-row sm:items-center sm:gap-6">
                <div className="flex min-w-0 items-center gap-3 sm:w-56 sm:shrink-0">
                  <span className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-primary-400/30 bg-primary-500/10 text-accent-400">
                    <Icon name={icons[index] ?? 'layers'} className="h-5 w-5" />
                  </span>
                  <span className="text-h6 font-semibold text-content-primary">{layer.title}</span>
                </div>
                <ul className="flex flex-wrap gap-2">
                  {layer.items.map((item) => (
                    <li
                      key={item}
                      className="rounded-sm border border-line/10 bg-surface-3/60 px-3 py-1.5 text-caption text-content-secondary"
                    >
                      {item}
                    </li>
                  ))}
                </ul>
              </div>
              {!last && (
                <div className="flex h-8 items-center justify-center" aria-hidden="true">
                  <svg viewBox="0 0 24 32" className="h-8 w-6" preserveAspectRatio="none" aria-hidden="true">
                    <line
                      x1="12"
                      y1="0"
                      x2="12"
                      y2="32"
                      stroke="rgb(var(--c-accent-500))"
                      strokeOpacity="0.5"
                      strokeWidth="1.5"
                      strokeDasharray="4 6"
                      className="animate-dash-flow"
                    />
                  </svg>
                </div>
              )}
            </li>
          );
        })}
      </ol>
    </div>
  );
}
