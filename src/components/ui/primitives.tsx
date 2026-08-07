import { useEffect, useRef, useState, type ReactNode, type ElementType } from 'react';
import { Link } from 'react-router-dom';
import { Icon, type IconName } from './Icon';

/* -------------------------------------------------------------------------- */
/* Layout                                                                      */
/* -------------------------------------------------------------------------- */

export function Container({ children, className = '' }: { children: ReactNode; className?: string }) {
  return <div className={`container-kms ${className}`}>{children}</div>;
}

const TONES = {
  base: 'section-base',
  raised: 'section-raised',
  accent: 'section-accent',
} as const;

interface SectionProps {
  children: ReactNode;
  id?: string;
  tone?: keyof typeof TONES;
  /**
   * Sets `data-scheme`, which swaps the colour tokens for everything inside.
   * Components never branch on this — they just use semantic tokens.
   */
  scheme?: 'dark' | 'light';
  className?: string;
  grid?: boolean;
}

export function Section({
  children,
  id,
  tone = 'base',
  scheme = 'dark',
  className = '',
  grid = false,
}: SectionProps) {
  return (
    <section
      id={id}
      data-scheme={scheme}
      className={`relative scroll-mt-header-h py-section ${TONES[tone]} ${className}`}
    >
      {grid && (
        <div
          className="pointer-events-none absolute inset-0 bg-grid-faint [background-size:64px_64px] [mask-image:radial-gradient(ellipse_at_top_left,black,transparent_70%)]"
          aria-hidden="true"
        />
      )}
      <div className="relative">{children}</div>
    </section>
  );
}

/* -------------------------------------------------------------------------- */
/* Reveal on scroll                                                            */
/* -------------------------------------------------------------------------- */

/**
 * Adds `reveal-in` the first time the element enters the viewport, then stops
 * observing. Falls back to "visible immediately" when IntersectionObserver is
 * missing — and the `.no-js` rule in the stylesheet covers the case where the
 * bundle never runs at all, which matters because these pages are prerendered.
 */
export function useReveal(rootMargin = '-12% 0px') {
  const ref = useRef<HTMLDivElement>(null);
  const [shown, setShown] = useState(false);

  useEffect(() => {
    const node = ref.current;
    if (!node || shown) return;
    if (typeof IntersectionObserver === 'undefined') {
      setShown(true);
      return;
    }
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          setShown(true);
          observer.disconnect();
        }
      },
      { rootMargin, threshold: 0.05 },
    );
    observer.observe(node);
    return () => observer.disconnect();
  }, [rootMargin, shown]);

  return { ref, className: shown ? 'reveal reveal-in' : 'reveal' };
}

interface RevealProps {
  children: ReactNode;
  className?: string;
  delay?: number;
  as?: ElementType;
}

export function Reveal({ children, className = '', delay = 0, as: Tag = 'div' }: RevealProps) {
  const reveal = useReveal();
  return (
    <Tag
      ref={reveal.ref}
      className={`${reveal.className} ${className}`}
      style={delay ? { transitionDelay: `${delay}ms` } : undefined}
    >
      {children}
    </Tag>
  );
}

/* -------------------------------------------------------------------------- */
/* Typography                                                                  */
/* -------------------------------------------------------------------------- */

export function Eyebrow({ children, className = '' }: { children: ReactNode; className?: string }) {
  return (
    <span
      className={`inline-flex items-center gap-2 text-overline font-semibold uppercase tracking-[0.14em] text-accent-400 ${className}`}
    >
      <span className="h-px w-6 bg-accent-400/60" aria-hidden="true" />
      {children}
    </span>
  );
}

interface SectionHeadingProps {
  eyebrow?: string;
  heading: string;
  description?: string;
  align?: 'start' | 'center';
  as?: 'h1' | 'h2' | 'h3';
  className?: string;
  children?: ReactNode;
}

export function SectionHeading({
  eyebrow,
  heading,
  description,
  align = 'start',
  as: Tag = 'h2',
  className = '',
  children,
}: SectionHeadingProps) {
  const reveal = useReveal();
  const alignment = align === 'center' ? 'items-center text-center mx-auto' : 'items-start';
  return (
    <div ref={reveal.ref} className={`${reveal.className} flex flex-col gap-4 ${alignment} max-w-3xl ${className}`}>
      {eyebrow && <Eyebrow>{eyebrow}</Eyebrow>}
      <Tag className="text-h2 font-semibold text-content-primary">{heading}</Tag>
      {description && <p className="text-body-lg text-content-secondary">{description}</p>}
      {children}
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/* Surfaces                                                                    */
/* -------------------------------------------------------------------------- */

export function Card({
  children,
  className = '',
  interactive = false,
}: {
  children: ReactNode;
  className?: string;
  interactive?: boolean;
}) {
  return (
    <div
      className={`panel rounded-lg p-6 transition-all duration-base ease-out-soft ${
        interactive ? 'hover:-translate-y-1 hover:border-primary-400/40 hover:shadow-lg' : ''
      } ${className}`}
    >
      {children}
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/* Actions                                                                     */
/* -------------------------------------------------------------------------- */

const VARIANTS = {
  primary:
    'bg-primary-500 text-white hover:bg-primary-600 shadow-md hover:shadow-glow border border-primary-400/40',
  secondary: 'panel text-content-primary hover:bg-surface-2/80 hover:border-line/20',
  ghost:
    'text-content-secondary hover:text-content-primary border border-transparent hover:border-line/10',
} as const;

const SIZES = {
  md: 'px-5 py-2.5 text-label',
  lg: 'px-7 py-3.5 text-body-md',
} as const;

const BUTTON_BASE =
  'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-all duration-base ease-out-soft disabled:cursor-not-allowed disabled:opacity-60';

interface ButtonProps {
  children: ReactNode;
  to?: string;
  href?: string;
  external?: boolean;
  variant?: keyof typeof VARIANTS;
  size?: keyof typeof SIZES;
  icon?: IconName;
  type?: 'button' | 'submit';
  disabled?: boolean;
  onClick?: () => void;
  className?: string;
}

export function Button({
  children,
  to,
  href,
  external = false,
  variant = 'primary',
  size = 'md',
  icon,
  type = 'button',
  disabled = false,
  onClick,
  className = '',
}: ButtonProps) {
  const classes = `${BUTTON_BASE} ${VARIANTS[variant]} ${SIZES[size]} ${className}`;
  const body = (
    <>
      {children}
      {icon && <Icon name={icon} className="h-4 w-4" />}
    </>
  );

  if (to) {
    return (
      <Link to={to} className={classes}>
        {body}
      </Link>
    );
  }
  if (href) {
    return (
      <a
        href={href}
        className={classes}
        {...(external ? { target: '_blank', rel: 'noopener noreferrer' } : {})}
      >
        {body}
      </a>
    );
  }
  return (
    <button className={classes} type={type} disabled={disabled} onClick={onClick}>
      {body}
    </button>
  );
}

/* -------------------------------------------------------------------------- */
/* Lists                                                                       */
/* -------------------------------------------------------------------------- */

export function CheckList({ items, className = '' }: { items: string[]; className?: string }) {
  return (
    <ul className={`flex flex-col gap-3 ${className}`}>
      {items.map((item) => (
        <li key={item} className="flex items-start gap-3 text-body-md text-content-secondary">
          <Icon name="check" className="mt-1 h-4 w-4 shrink-0 text-accent-400" />
          <span>{item}</span>
        </li>
      ))}
    </ul>
  );
}

export function TagList({ items, className = '' }: { items: string[]; className?: string }) {
  return (
    <ul className={`flex flex-wrap gap-2 ${className}`}>
      {items.map((item) => (
        <li
          key={item}
          className="rounded-sm border border-line/10 bg-surface-2/60 px-2.5 py-1 text-caption text-content-secondary"
        >
          {item}
        </li>
      ))}
    </ul>
  );
}

interface FeatureItem {
  title: string;
  description: string;
  icon?: IconName;
}

export function FeatureGrid({
  items,
  columns = 3,
  variant = 'card',
}: {
  items: FeatureItem[];
  columns?: 2 | 3 | 4;
  variant?: 'card' | 'plain';
}) {
  const cols = {
    2: 'sm:grid-cols-2',
    3: 'sm:grid-cols-2 lg:grid-cols-3',
    4: 'sm:grid-cols-2 lg:grid-cols-4',
  }[columns];

  return (
    <div className={`grid gap-5 ${cols}`}>
      {items.map((item, index) => (
        <Reveal key={item.title} delay={index * 60} className="h-full">
          <div
            className={
              variant === 'card'
                ? 'panel flex h-full flex-col gap-4 rounded-lg p-6'
                : 'flex h-full flex-col gap-3'
            }
          >
            {item.icon && <Icon name={item.icon} className="h-6 w-6 text-accent-400" />}
            <h3 className="text-h6 font-semibold text-content-primary">{item.title}</h3>
            <p className="text-body-sm text-content-tertiary">{item.description}</p>
          </div>
        </Reveal>
      ))}
    </div>
  );
}

export function GroupGrid({
  groups,
  columns = 3,
}: {
  groups: Array<{ title: string; items: string[] }>;
  columns?: 2 | 3;
}) {
  const cols = columns === 2 ? 'sm:grid-cols-2' : 'sm:grid-cols-2 lg:grid-cols-3';
  return (
    <div className={`grid gap-5 ${cols}`}>
      {groups.map((group, index) => (
        <Reveal key={group.title} delay={index * 60} className="h-full">
          <div className="flex h-full flex-col gap-4 rounded-lg border border-line/10 bg-surface-1/50 p-6">
            <h3 className="text-h6 font-semibold text-content-primary">{group.title}</h3>
            <ul className="flex flex-col gap-2">
              {group.items.map((item) => (
                <li key={item} className="flex items-start gap-2.5 text-body-sm text-content-tertiary">
                  <span
                    className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-accent-400/70"
                    aria-hidden="true"
                  />
                  {item}
                </li>
              ))}
            </ul>
          </div>
        </Reveal>
      ))}
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/* Accordion                                                                   */
/* -------------------------------------------------------------------------- */

/**
 * Built on <details>/<summary> rather than buttons and hidden panels: it is
 * open/closed without JavaScript, which matters on prerendered pages, and the
 * browser gives the correct expanded/collapsed semantics for free.
 */
export function Accordion({
  items,
  defaultOpen = 0,
}: {
  items: Array<{ q: string; a: string }>;
  defaultOpen?: number | null;
}) {
  return (
    <div className="flex flex-col gap-3">
      {items.map((item, index) => (
        <details
          key={item.q}
          open={defaultOpen === index}
          className="group rounded-lg border border-line/10 bg-surface-1/50 transition-colors duration-base open:border-primary-400/30"
        >
          <summary className="flex cursor-pointer list-none items-center justify-between gap-4 p-5 text-body-md font-medium text-content-primary marker:content-none">
            <span>{item.q}</span>
            <Icon
              name="chevron-down"
              className="h-5 w-5 shrink-0 text-content-tertiary transition-transform duration-base group-open:rotate-180"
            />
          </summary>
          <div className="px-5 pb-5 text-body-sm text-content-secondary">{item.a}</div>
        </details>
      ))}
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/* Misc                                                                        */
/* -------------------------------------------------------------------------- */

export function StatGrid({
  items,
}: {
  items: Array<{ value: string; label: string; description: string; icon?: IconName }>;
}) {
  return (
    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {items.map((item, index) => (
        <Reveal key={item.label} delay={index * 60} className="h-full">
          <div className="flex h-full flex-col gap-3 rounded-lg border border-line/10 bg-surface-1/50 p-6">
            {item.icon && <Icon name={item.icon} className="h-5 w-5 text-accent-400" />}
            <span className="text-display-lg font-extrabold leading-none text-content-primary">
              {item.value}
            </span>
            <span className="text-label font-semibold text-content-primary">{item.label}</span>
            <span className="text-caption text-content-tertiary">{item.description}</span>
          </div>
        </Reveal>
      ))}
    </div>
  );
}

