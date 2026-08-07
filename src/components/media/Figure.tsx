import { MEDIA, type MediaKey, type MediaSlot } from '../../content/media';
import { ILLUSTRATIONS } from './illustrations';
import { useLocale } from '../../i18n/LocaleContext';

const RATIOS = {
  '16/9': 'aspect-[16/9]',
  '4/3': 'aspect-[4/3]',
  '1/1': 'aspect-square',
  '3/4': 'aspect-[3/4]',
} as const;

/** `/images/team.jpg` + `avif` → `/images/team.avif` */
function swapExtension(src: string, extension: string): string {
  return src.replace(/\.[^./]+$/, `.${extension}`);
}

/** `/images/team.jpg` + [800, 1200] → `/images/team-800.jpg 800w, …` */
function buildSrcSet(src: string, widths: number[], extension?: string): string {
  const target = extension ? swapExtension(src, extension) : src;
  const match = target.match(/^(.*)(\.[^./]+)$/);
  if (!match) return target;
  const [, base, ext] = match;
  return widths.map((width) => `${base}-${width}${ext} ${width}w`).join(', ');
}

interface FigureProps {
  /** Which slot in src/content/media.ts to render. */
  name: MediaKey;
  className?: string;
  /** Overrides the slot's own ratio. */
  ratio?: keyof typeof RATIOS;
  /**
   * Set on the one image most likely to be the largest element in the initial
   * viewport — it drops the lazy attribute and raises fetch priority.
   */
  priority?: boolean;
  /** Rendered hint for the browser's image selection. */
  sizes?: string;
}

/**
 * Renders whatever `src/content/media.ts` says a slot should be — a built-in
 * illustration or a photograph — with the layout, accessibility and loading
 * behaviour handled the same way either way.
 *
 * The alt text is the slot's own, in the reader's language. Illustrations get
 * `role="img"` plus an aria-label rather than an <img>, so assistive tech
 * announces them exactly as it would a photograph.
 */
export function Figure({ name, className = '', ratio, priority = false, sizes }: FigureProps) {
  const locale = useLocale();
  const slot = MEDIA[name] as MediaSlot;
  const alt = slot.alt[locale];
  const aspect = RATIOS[ratio ?? slot.ratio ?? '4/3'];
  const frame = `relative w-full overflow-hidden rounded-lg border border-line/10 bg-surface-1/40 ${aspect} ${className}`;

  if (slot.asset.kind === 'illustration') {
    const Illustration = ILLUSTRATIONS[slot.asset.name];
    return (
      <div className={frame} role="img" aria-label={alt}>
        <div
          className="pointer-events-none absolute inset-0 bg-grid-faint [background-size:32px_32px] [mask-image:radial-gradient(ellipse_at_center,black,transparent_85%)]"
          aria-hidden="true"
        />
        <div className="absolute inset-0 p-4 sm:p-6">
          <Illustration />
        </div>
      </div>
    );
  }

  const { src, formats = [], width, height, responsiveWidths } = slot.asset;
  const defaultSizes = sizes ?? '(min-width: 1024px) 45vw, 100vw';

  return (
    <div className={frame}>
      <picture>
        {formats.map((format) => (
          <source
            key={format}
            type={`image/${format}`}
            srcSet={
              responsiveWidths
                ? buildSrcSet(src, responsiveWidths, format)
                : swapExtension(src, format)
            }
            sizes={responsiveWidths ? defaultSizes : undefined}
          />
        ))}
        <img
          src={src}
          srcSet={responsiveWidths ? buildSrcSet(src, responsiveWidths) : undefined}
          sizes={responsiveWidths ? defaultSizes : undefined}
          alt={alt}
          width={width}
          height={height}
          loading={priority ? 'eager' : 'lazy'}
          decoding={priority ? 'sync' : 'async'}
          fetchPriority={priority ? 'high' : undefined}
          className="h-full w-full object-cover"
        />
      </picture>
    </div>
  );
}
