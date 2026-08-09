import type { Locale } from '../i18n/locales';
import type { IllustrationName } from '../components/media/illustrations';

/**
 * ============================================================================
 *  THE ONE PLACE IMAGES ARE CONFIGURED
 * ============================================================================
 *
 * Every visual slot on the site is listed below. Each one is either a built-in
 * vector illustration or a real photograph, and switching between the two is a
 * single edit.
 *
 * ── To replace an illustration with a photo ─────────────────────────────────
 *
 *  1. Put the file in `public/images/` — e.g. `public/images/team.jpg`.
 *     Optional but worth it: export `team.webp` and `team.avif` beside it and
 *     list them under `formats`; browsers pick the smallest they understand and
 *     fall back to the .jpg on their own.
 *
 *  2. Change the slot from this:
 *
 *         teamPhoto: {
 *           asset: { kind: 'illustration', name: 'RackElevation' },
 *           alt: { ka: '…', en: '…' },
 *         }
 *
 *     to this:
 *
 *         teamPhoto: {
 *           asset: {
 *             kind: 'image',
 *             src: '/images/team.jpg',
 *             formats: ['avif', 'webp'],   // omit if you only have the .jpg
 *             width: 1600,                 // the file's real pixel size —
 *             height: 1200,                // this is what prevents layout shift
 *           },
 *           alt: { ka: '…', en: '…' },
 *         }
 *
 *  3. Update `alt` to describe the new photo in both languages. Alt text lives
 *     next to the asset precisely so it cannot be forgotten when the asset
 *     changes — a stale alt is worse than none, and nothing in the type system
 *     will catch it for you. This is the step people skip.
 *
 * Nothing else needs touching: <Figure> handles the <picture> element, the
 * aspect ratio, lazy loading and decoding for you.
 *
 * This path is tested, not assumed: swapping a slot to a .jpg with a .webp
 * beside it produces a <picture> whose <source> the browser honours — it
 * fetches the WebP alone, never both — with width/height on the <img> so the
 * space is reserved before it arrives, and loading="lazy" below the fold.
 */

export type MediaAsset =
  | { kind: 'illustration'; name: IllustrationName }
  | {
      kind: 'image';
      /** Path under `public/`, e.g. `/images/team.jpg`. */
      src: string;
      /** Extra formats sitting next to `src` with the same basename. */
      formats?: Array<'avif' | 'webp'>;
      /** Intrinsic pixel dimensions. Required — they reserve the space. */
      width: number;
      height: number;
      /**
       * Widths available as `name-800.jpg`, `name-1200.jpg`, … If set, a
       * srcset is generated so phones do not download a desktop-sized file.
       */
      responsiveWidths?: number[];
    };

export interface MediaSlot {
  asset: MediaAsset;
  alt: Record<Locale, string>;
  /** Overrides the ratio the illustration or image would otherwise use. */
  ratio?: '16/9' | '3/2' | '4/3' | '1/1' | '3/4';
}

/**
 * Typed as a plain Record rather than declared `as const`: the widened type is
 * what lets <Figure> narrow on `asset.kind`. With `as const` every slot's kind
 * collapses to the literal it currently holds, and the image branch becomes
 * unreachable the moment you have not switched any slot over yet.
 */
export const MEDIA: Record<string, MediaSlot> = {
  /** Home hero, right-hand side. */
  heroDiagram: {
    asset: {
      kind: 'image',
      src: '/images/hero-building.jpg',
      formats: ['webp'],
      width: 405,
      height: 270,
    },
    alt: {
      ka: 'თანამედროვე საოფისე შენობა შებინდებისას — განათებული სართულები მინის ფასადს მიღმა',
      en: 'A modern office building at dusk, lit floors behind a glazed facade',
    },
    ratio: '4/3',
  },

  /** Home "who we are", and the About page. */
  teamPhoto: {
    asset: {
      kind: 'image',
      src: '/images/team-cabling.jpg',
      formats: ['webp'],
      width: 1536,
      height: 1024,
      responsiveWidths: [600, 1200, 1536],
    },
    alt: {
      ka: 'ტექნიკოსი შეკიდულ ჭერქვეშ ლურჯ კაბელებს კაბელგატარში ამაგრებს',
      en: 'A technician terminating blue cable into overhead containment above a suspended ceiling',
    },
    ratio: '4/3',
  },

  /** Home "smart systems". */
  smartPhoto: {
    asset: {
      kind: 'image',
      src: '/images/smart-living.jpg',
      formats: ['webp'],
      width: 368,
      height: 245,
    },
    alt: {
      ka: 'მისაღები ოთახი კედელზე დამონტაჟებული მართვის პანელით — განათება, კლიმატი და დაცვა ერთ ეკრანზე',
      en: 'A living room with a wall-mounted control panel — lighting, climate and security on one screen',
    },
    ratio: '4/3',
  },

  /** Process page, and the project handover section. */
  deliveryPhoto: {
    asset: {
      kind: 'image',
      src: '/images/handover-drawing.jpg',
      formats: ['webp'],
      width: 1536,
      height: 1024,
      responsiveWidths: [600, 1200, 1536],
    },
    alt: {
      ka: 'ხელში გაშლილი შესრულებული ნახაზები ობიექტის ჩაბარებისას',
      en: 'As-built drawings held open at handover',
    },
    ratio: '4/3',
  },

  /** About page — the "one accountable supplier" argument, drawn. */
  accountability: {
    asset: { kind: 'illustration', name: 'SingleVendor' },
    alt: {
      ka: 'შედარება — ოთხი ცალკეული კონტრაქტორი, რომელთა შორის პასუხისმგებლობის ხარვეზებია, და ერთი გუნდი, რომელიც მთელ სისტემაზე აგებს პასუხს',
      en: 'A comparison — four separate contractors with accountability gaps between them, against one team answerable for the whole system',
    },
    ratio: '4/3',
  },

  /** Process page — six stages and the artefact each one hands over. */
  processTimeline: {
    asset: { kind: 'illustration', name: 'ProcessTimeline' },
    alt: {
      ka: 'პროექტის ექვსი ეტაპი ერთ ღერძზე — კონსულტაციიდან მხარდაჭერამდე, თითოეულის ქვეშ მითითებული დოკუმენტით, რომელსაც იღებთ',
      en: 'Six project stages on one axis — from consultation to support, each marked with the document it hands over',
    },
    ratio: '4/3',
  },

  /* ---- One per service, keyed by slug ------------------------------------ */

  'service:it-infrastructure': {
    asset: {
      kind: 'image',
      src: '/images/svc-infrastructure.jpg',
      formats: ['webp'],
      width: 405,
      height: 270,
    },
    alt: {
      ka: 'სერვერული კარადების რიგი — კომუტატორები და მოწესრიგებული პაჩ-კაბელები',
      en: 'A row of server cabinets — switches and dressed patch leads',
    },
    ratio: '4/3',
  },
  'service:networking': {
    asset: {
      kind: 'image',
      src: '/images/svc-networking.jpg',
      formats: ['webp'],
      width: 362,
      height: 241,
    },
    alt: {
      ka: 'პაჩ-პანელი დანომრილი პორტებით და თანაბრად გაყვანილი ლურჯი კაბელებით',
      en: 'A patch panel with numbered ports and evenly dressed blue patch leads',
    },
    ratio: '4/3',
  },
  'service:cctv': {
    asset: {
      kind: 'image',
      src: '/images/svc-cctv.jpg',
      formats: ['webp'],
      width: 405,
      height: 270,
    },
    alt: {
      ka: 'ვიდეოკამერა ბეტონის სვეტზე, დაცულ კაბელგაყვანილობასთან, საწარმოო სივრცის ხედით',
      en: 'A bullet camera on a concrete column beside protected cabling, overlooking a production space',
    },
    ratio: '4/3',
  },
  'service:access-control': {
    asset: {
      kind: 'image',
      src: '/images/svc-access.jpg',
      formats: ['webp'],
      width: 1200,
      height: 800,
      responsiveWidths: [600, 1200],
    },
    alt: {
      ka: 'ხელი ბარათს წამკითხველთან მიიტანს კონტროლირებად კართან',
      en: 'A card presented to a reader at a controlled door',
    },
    ratio: '4/3',
  },
  'service:smart-home': {
    asset: {
      kind: 'image',
      src: '/images/svc-smart-home.jpg',
      formats: ['webp'],
      width: 396,
      height: 264,
    },
    alt: {
      ka: 'ჭკვიანი სახლის მართვის პანელი მისაღებ ოთახში — განათება, დაცვა და კლიმატი ერთ ადგილას',
      en: 'A smart home control panel in a living room — lighting, security and climate in one place',
    },
    ratio: '4/3',
  },
  'service:smart-building': {
    asset: {
      kind: 'image',
      src: '/images/svc-smart-building.jpg',
      formats: ['webp'],
      width: 327,
      height: 218,
    },
    alt: {
      ka: 'შენობის საინჟინრო სისტემები ჭერქვეშ — ვენტილაციის არხები, კაბელგატარი და მილგაყვანილობა',
      en: 'Building services above the ceiling — ventilation ducts, cable containment and pipework',
    },
    ratio: '4/3',
  },
  'service:audio-visual': {
    asset: {
      kind: 'image',
      src: '/images/svc-audio-visual.jpg',
      formats: ['webp'],
      width: 362,
      height: 241,
    },
    alt: {
      ka: 'სათათბირო ოთახი დიდი ეკრანით, ვიდეო-პანელით და მაგიდის საკონფერენციო მოწყობილობით',
      en: 'A meeting room with a large display, video bar and a table conferencing unit',
    },
    ratio: '4/3',
  },
  'service:managed-it': {
    asset: {
      kind: 'image',
      src: '/images/svc-managed-it.jpg',
      formats: ['webp'],
      width: 396,
      height: 264,
    },
    alt: {
      ka: 'მონიტორინგის სამუშაო ადგილი — კამერების ვიდეოკედელი და ოპერატორის ეკრანები',
      en: 'A monitoring position — a wall of camera feeds and operator screens',
    },
    ratio: '4/3',
  },

  /* ---- One per industry, keyed by slug ----------------------------------- */

  'industry:healthcare': {
    asset: {
      kind: 'image',
      src: '/images/ind-healthcare.jpg',
      formats: ['webp'],
      width: 1200,
      height: 800,
      responsiveWidths: [600, 1200],
    },
    alt: {
      ka: 'საავადმყოფოს პალატა — მონიტორი, სამედიცინო აღჭურვილობის კვების ხაზი და ეკრანი კედელზე',
      en: 'A hospital room — patient monitor, a medical services rail and a wall-mounted screen',
    },
    ratio: '4/3',
  },
  'industry:corporate': {
    asset: {
      kind: 'image',
      src: '/images/ind-corporate.jpg',
      formats: ['webp'],
      width: 1200,
      height: 800,
      responsiveWidths: [600, 1200],
    },
    alt: {
      ka: 'საოფისე სართული მინის სათათბირო ოთახებით და ღია სამუშაო სივრცით',
      en: 'An office floor with glazed meeting rooms and an open work area',
    },
    ratio: '4/3',
  },
  'industry:hospitality': {
    asset: {
      kind: 'image',
      src: '/images/ind-hospitality.jpg',
      formats: ['webp'],
      width: 276,
      height: 184,
    },
    alt: {
      ka: 'სასტუმროს ნომერი — განათება, კლიმატი და ქსელი სტუმრისთვის მზად',
      en: 'A hotel room with lighting, climate and network ready for the guest',
    },
    ratio: '4/3',
  },
  'industry:education': {
    asset: {
      kind: 'image',
      src: '/images/ind-education.jpg',
      formats: ['webp'],
      width: 1200,
      height: 800,
      responsiveWidths: [600, 1200],
    },
    alt: {
      ka: 'საკლასო ოთახი — ინტერაქტიული დაფა, ჭერის პროექტორი და მასწავლებლის სამუშაო ადგილი',
      en: 'A classroom — interactive whiteboard, ceiling projector and the teaching position',
    },
    ratio: '4/3',
  },
  'industry:industrial': {
    asset: {
      kind: 'image',
      src: '/images/ind-industrial.jpg',
      formats: ['webp'],
      width: 1200,
      height: 800,
      responsiveWidths: [600, 1200],
    },
    alt: {
      ka: 'საწარმოო ცეხი — ჩარხების რიგები, ამწე-ბილიკი და მონიშნული სატრანსპორტო დერეფანი',
      en: 'A production hall — rows of machine tools, a crane rail and a marked transport aisle',
    },
    ratio: '4/3',
  },
  'industry:government': {
    asset: {
      kind: 'image',
      src: '/images/ind-government.jpg',
      formats: ['webp'],
      width: 1200,
      height: 800,
      responsiveWidths: [600, 1200],
    },
    alt: {
      ka: 'საჯარო დაწესებულების შესასვლელი — კონტროლირებადი პერიმეტრი, საშვის პუნქტი და შლაგბაუმი',
      en: 'The entrance to a public institution — controlled perimeter, gatehouse and barrier',
    },
    ratio: '4/3',
  },
  'industry:retail': {
    asset: {
      kind: 'image',
      src: '/images/ind-retail.jpg',
      formats: ['webp'],
      width: 1200,
      height: 800,
      responsiveWidths: [600, 1200],
    },
    alt: {
      ka: 'სავაჭრო სივრცე — სექციური განათება, დახლები და შესასვლელის ზონა',
      en: 'A retail floor — track lighting, display tables and the entrance zone',
    },
    ratio: '4/3',
  },
  'industry:residential': {
    asset: {
      kind: 'image',
      src: '/images/ind-residential.jpg',
      formats: ['webp'],
      width: 396,
      height: 264,
    },
    alt: {
      ka: 'თანამედროვე ვილა შებინდებისას — განათებული ინტერიერი და აუზი ეზოში',
      en: 'A contemporary villa at dusk — lit interior and a pool in the garden',
    },
    ratio: '4/3',
  },
};

export type MediaKey = string;

/** The illustration for a service page, or undefined if it has none yet. */
export function serviceMedia(slug: string): MediaKey | undefined {
  const key = `service:${slug}`;
  return key in MEDIA ? key : undefined;
}

/** The illustration for an industry page, or undefined if it has none yet. */
export function industryMedia(slug: string): MediaKey | undefined {
  const key = `industry:${slug}`;
  return key in MEDIA ? key : undefined;
}
