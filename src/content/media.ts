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
 *     changes — a stale alt is worse than none.
 *
 * Nothing else needs touching: <Figure> handles the <picture> element, the
 * aspect ratio, lazy loading and decoding for you.
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
  ratio?: '16/9' | '4/3' | '1/1' | '3/4';
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
    asset: { kind: 'illustration', name: 'NetworkTopology' },
    alt: {
      ka: 'დაკავშირებული ინფრასტრუქტურის ტოპოლოგიის სქემა — ობიექტები, ქსელის კვანძები და მათ შორის კავშირები',
      en: 'Connected infrastructure topology — sites, network nodes and the links between them',
    },
    ratio: '4/3',
  },

  /** Home "who we are", and the About page. */
  teamPhoto: {
    asset: { kind: 'illustration', name: 'RackElevation' },
    alt: {
      ka: 'სერვერული კარადის სქემა — პაჩ-პანელები, კომუტატორები, უწყვეტი კვების წყარო და კაბელების ორგანიზაცია',
      en: 'Server rack elevation — patch panels, switches, UPS and cable management',
    },
    ratio: '4/3',
  },

  /** Home "smart systems", and the smart-home service page. */
  smartPhoto: {
    asset: { kind: 'illustration', name: 'SmartHomeZones' },
    alt: {
      ka: 'ჭკვიანი სახლის ზონების გეგმა — განათება, კლიმატი, დაცვა და ფარდები ერთ სცენარში',
      en: 'Smart home zone plan — lighting, climate, security and blinds in a single scenario',
    },
    ratio: '4/3',
  },

  /** Process page, and the project handover section. */
  deliveryPhoto: {
    asset: { kind: 'illustration', name: 'HandoverDocs' },
    alt: {
      ka: 'პროექტის ჩაბარების დოკუმენტაცია — სქემები, პორტების რეესტრი და ნიშნვის სისტემა',
      en: 'Project handover documentation — diagrams, port register and labelling scheme',
    },
    ratio: '4/3',
  },

  /* ---- One per service, keyed by slug ------------------------------------ */

  'service:it-infrastructure': {
    asset: { kind: 'illustration', name: 'ServerRoom' },
    alt: {
      ka: 'სერვერული ოთახის გეგმა — რეკების ორი რიგი, გამიჯნული ცივი დერეფანი, UPS, გაგრილება და ზედა კაბელგატარი',
      en: 'Server room plan — two rack rows, contained cold aisle, UPS, cooling and overhead cable tray',
    },
    ratio: '4/3',
  },
  'service:networking': {
    asset: { kind: 'illustration', name: 'NetworkSegments' },
    alt: {
      ka: 'ქსელის სეგმენტაცია — ფაირვოლი, ბირთვის კომუტატორი და ერთმანეთისგან იზოლირებული VLAN-ები პერსონალის, სტუმრების, ვიდეოკონტროლისა და შენობის სისტემებისთვის',
      en: 'Network segmentation — firewall, core switch and isolated VLANs for staff, guests, video surveillance and building systems',
    },
    ratio: '4/3',
  },
  'service:cctv': {
    asset: { kind: 'illustration', name: 'CameraCoverage' },
    alt: {
      ka: 'კამერების დაფარვის რუკა — ხედვის კონუსები გადაფარვით, აღნიშნული უხილავი ზონა, NVR არქივით და დაცული დისტანციური წვდომა',
      en: 'Camera coverage plan — overlapping fields of view, a marked blind spot, NVR with retention and secured remote access',
    },
    ratio: '4/3',
  },
  'service:access-control': {
    asset: { kind: 'illustration', name: 'AccessZones' },
    alt: {
      ka: 'დაშვების ზონები უფლებების დონეების მიხედვით, კონტროლირებადი კარები წამკითხველებით და მოვლენების ჟურნალი',
      en: 'Access zones by permission level, controlled doors with readers, and the event log',
    },
    ratio: '4/3',
  },
  'service:smart-home': {
    asset: { kind: 'illustration', name: 'SmartHomeZones' },
    alt: {
      ka: 'ჭკვიანი სახლის ზონების გეგმა — განათება, კლიმატი, დაცვა და ფარდები ერთ სცენარში',
      en: 'Smart home zone plan — lighting, climate, security and blinds in a single scenario',
    },
    ratio: '4/3',
  },
  'service:smart-building': {
    asset: { kind: 'illustration', name: 'BuildingSystems' },
    alt: {
      ka: 'შენობის ჭრილი — სართულები განათებით, ვენტილაციითა და სენსორებით, ერთიან მართვის სისტემასთან დაკავშირებული',
      en: 'Building section — floors with lighting, ventilation and sensors, all tied to one management layer',
    },
    ratio: '4/3',
  },
  'service:audio-visual': {
    asset: { kind: 'illustration', name: 'MeetingRoom' },
    alt: {
      ka: 'სათათბირო ოთახის სქემა — ეკრანი, ჭერის მიკროფონი აღების ზონით, დინამიკები და ერთი ღილაკით შეხვედრის დაწყება',
      en: 'Meeting room layout — display, ceiling microphone with its pickup pattern, speakers and one-touch meeting join',
    },
    ratio: '4/3',
  },
  'service:managed-it': {
    asset: { kind: 'illustration', name: 'MonitoringDashboard' },
    alt: {
      ka: 'მონიტორინგის პანელი — 24-საათიანი ხელმისაწვდომობა, შეტყობინებების ნაკადი და შეთანხმებული რეაგირების ვადა',
      en: 'Monitoring dashboard — 24-hour uptime, alert feed and the agreed response time',
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
