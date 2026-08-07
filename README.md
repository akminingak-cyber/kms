# KMS

Bilingual (Georgian / English) marketing site for KMS — an IT infrastructure and
smart systems integrator. React + TypeScript + Tailwind, prerendered to static
HTML at build time and deployed to Apache shared hosting.

```bash
npm install
npm run dev        # dev server
npm run build      # client + SSR + prerender → dist/
npm run serve:dist # serve dist/ exactly as Apache will
npm run typecheck
npm run lint
```

## How it is put together

The site is a single-page React app that is **rendered to static HTML at build
time** — one file per route, per language, 57 in all. A visitor (or a crawler
that never runs JavaScript) gets the complete page in the initial response; the
bundle then hydrates that same markup instead of rebuilding it.

`npm run build` runs three steps:

| Step | What it does |
| --- | --- |
| `build:client` | Vite builds the browser bundle and pre-compresses assets to `.br` / `.gz` |
| `build:server` | Vite builds `src/entry-server.tsx` for Node |
| `prerender` | Renders every route to HTML, then generates `sitemap.xml` and `.htaccess` |

`scripts/prerender.mjs` imports the route table and config **from the app
itself**, so the router, the sitemap and the server rewrite rules cannot drift
apart. Upload the contents of `dist/` — including the generated `.htaccess` — to
`public_html`.

### Where things live

```
src/
  config/site.ts        Company facts, analytics, form endpoint — edit here, once
  content/
    ka.ts  en.ts        Every user-facing string on the site
    media.ts            Which illustration or photo fills each visual slot
    types.ts            The shape both locale files must satisfy
  i18n/                 Locale routing, content loading
  components/
    layout/  ui/        Shell, primitives
    media/              <Figure> + the built-in vector illustrations
    seo/                <Seo> and the JSON-LD builders
    forms/              Contact form
  pages/                One component per route
  styles/index.css      Design tokens, both colour schemes, component classes
```

No copy is hard-coded in a component. Changing wording means editing
`src/content/ka.ts` and `src/content/en.ts` and nothing else.

## Replacing an illustration with a photograph

Every visual slot is listed in **`src/content/media.ts`**, which is the only file
you need to touch.

1. Drop the file in `public/images/` — e.g. `public/images/team.jpg`. Exporting
   `team.webp` and `team.avif` alongside it is worthwhile but optional.
2. Change the slot from the illustration form to the image form:

```ts
teamPhoto: {
  asset: {
    kind: 'image',
    src: '/images/team.jpg',
    formats: ['avif', 'webp'],  // omit if you only have the .jpg
    width: 1600,                // the file's real pixel size — this is what
    height: 1200,               // reserves the space and prevents layout shift
  },
  alt: { ka: '…', en: '…' },
},
```

3. Rewrite `alt` to describe the new photo in both languages. It sits next to the
   asset precisely so it cannot be forgotten when the asset changes.

`<Figure>` handles the `<picture>` element, formats, aspect ratio, lazy loading
and decoding. Nothing else needs editing.

## Configuration

Everything below lives in `src/config/site.ts`.

**Company facts.** Phone, email, address, and the optional fields that are
omitted from the structured data until they are filled in:

- `legalName` / `taxId` — appear in the privacy policy and JSON-LD once set
- `address.postalCode`, `geo.latitude` / `geo.longitude` — needed for Google to
  place the business on a map
- `openingHours` — `null` publishes no `openingHoursSpecification` rather than
  guessing; fill it in to emit real hours
- `social` — any non-empty URL is rendered in the footer and emitted as `sameAs`

**Analytics.** Plausible, cookieless. Because it stores nothing on the visitor's
device, the site still sets no cookies and needs no consent banner — which is
what the `/cookies` page says. Set `enabled: false` and the script tag and its
CSP entry both disappear from the build. Pointing `host` at a self-hosted
instance updates the CSP automatically.

> Plausible needs an account with `kms.ge` added as a site before the dashboard
> shows anything. The script is already wired up.

**Contact form.** Web3Forms. The access key is public by design — it identifies
the inbox, it authorises nothing. What prevents abuse is the **domain
restriction** in the Web3Forms dashboard, which rejects submissions that do not
originate from this site. Set that before going live.

## Deployment

Upload `dist/` to `public_html`. The generated `.htaccess` handles:

- one canonical origin — HTTPS, apex host, hard-coded (not echoed from the
  request, which would make the redirect an open redirect)
- `301` from `/services.html` and `/services/` onto `/services`
- pre-compressed `.br` / `.gz` assets with the right `Content-Type` and `Vary`
- year-long immutable caching for hashed assets, no caching for HTML
- a real `404` status for missing pages, not a `200` shell
- CSP with `script-src` free of `unsafe-inline`, plus HSTS, COOP, nosniff,
  `X-Frame-Options`, `Referrer-Policy` and `Permissions-Policy`

## Notes

**`npm audit` reports one advisory** against `react-router` (GHSA-qwww-vcr4-c8h2,
RSC-mode CSRF). It does not apply: this is a static prerendered site with no
server, no server actions and no RSC entry point. Do not "fix" it by downgrading
to 7.11 — that release carries fourteen other advisories.
