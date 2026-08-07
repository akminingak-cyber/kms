/**
 * Turns the SPA into a set of static HTML files — one per route, per locale.
 *
 * Each file carries the fully rendered page body plus its own <head>: title,
 * description, canonical, hreflang, Open Graph and JSON-LD. Crawlers that never
 * run JavaScript therefore see the real content, and the browser hydrates the
 * same markup rather than rebuilding it.
 *
 * sitemap.xml and .htaccess are generated here too, from the same route table
 * and the same config object the application uses — so the router, the sitemap
 * and the server rules cannot disagree with each other.
 */
import { mkdirSync, readFileSync, writeFileSync, readdirSync, existsSync, rmSync } from 'node:fs';
import path from 'node:path';
import { pathToFileURL } from 'node:url';

const ROOT = process.cwd();
const DIST = path.join(ROOT, 'dist');
const SSR = path.join(ROOT, 'dist-ssr');
const ASSETS = path.join(DIST, 'assets');

const {
  render,
  SITE_ROUTES,
  LOCALES,
  DEFAULT_LOCALE,
  localePath,
  COMPANY,
  ANALYTICS,
  analyticsScriptUrl,
} = await import(pathToFileURL(path.join(SSR, 'entry-server.js')).href);

const template = readFileSync(path.join(DIST, 'index.html'), 'utf8');

/* -------------------------------------------------------------------------- */
/* Asset discovery — chunk names carry a content hash, so they are looked up   */
/* rather than hard-coded.                                                     */
/* -------------------------------------------------------------------------- */

const assetFiles = existsSync(ASSETS) ? readdirSync(ASSETS) : [];
const findAsset = (test) => {
  const file = assetFiles.find(test);
  return file ? `/assets/${file}` : null;
};

const CONTENT_CHUNK = Object.fromEntries(
  LOCALES.map((locale) => [
    locale,
    findAsset((f) => f.startsWith(`content-${locale}-`) && f.endsWith('.js')),
  ]),
);
const FONT_LATIN = findAsset((f) => f.endsWith('.woff2') && /inter/i.test(f));
const FONT_GEORGIAN = findAsset((f) => f.endsWith('.woff2') && /georgian/i.test(f));

/* -------------------------------------------------------------------------- */
/* Head rendering                                                              */
/* -------------------------------------------------------------------------- */

const escapeAttr = (value) =>
  String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');

/** JSON-LD is a data block, but a literal `</script>` inside a string ends it. */
const escapeJsonLd = (value) => JSON.stringify(value).replace(/</g, '\\u003c');

/**
 * Marks a head node as owned by <Seo>. On hydration the component clears
 * everything carrying this attribute before writing its own tags — without it,
 * the prerendered JSON-LD stays put and the client appends a second copy of
 * every block.
 */
const OWNED = 'data-kms-seo';

function renderHead(head, locale) {
  const lines = [`<title>${escapeAttr(head.title)}</title>`];

  for (const meta of head.meta) {
    lines.push(`<meta ${OWNED} name="${meta.name}" content="${escapeAttr(meta.content)}" />`);
  }

  // A noindex page gets neither canonical nor alternates: pointing a crawler at
  // a 404 is how a soft 404 ends up in the index.
  if (!head.noindex) {
    lines.push(`<link ${OWNED} rel="canonical" href="${escapeAttr(head.canonical)}" />`);
    for (const alt of head.alternates) {
      lines.push(
        `<link ${OWNED} rel="alternate" hreflang="${alt.hreflang}" href="${escapeAttr(alt.href)}" />`,
      );
    }
  }

  for (const og of head.og) {
    lines.push(`<meta ${OWNED} property="${og.property}" content="${escapeAttr(og.content)}" />`);
  }

  // Preload only what this locale renders — an English page draws no Georgian
  // glyphs, so fetching that face would be pure waste.
  if (FONT_LATIN) {
    lines.push(`<link rel="preload" as="font" type="font/woff2" href="${FONT_LATIN}" crossorigin />`);
  }
  if (locale === 'ka' && FONT_GEORGIAN) {
    lines.push(`<link rel="preload" as="font" type="font/woff2" href="${FONT_GEORGIAN}" crossorigin />`);
  }
  if (CONTENT_CHUNK[locale]) {
    lines.push(`<link rel="modulepreload" href="${CONTENT_CHUNK[locale]}" />`);
  }

  for (const block of head.jsonLd) {
    lines.push(`<script ${OWNED} type="application/ld+json">${escapeJsonLd(block)}</script>`);
  }

  if (ANALYTICS.enabled) {
    lines.push(`<script defer data-domain="${ANALYTICS.domain}" src="${analyticsScriptUrl()}"></script>`);
  }

  return lines.map((line) => `    ${line}`).join('\n');
}

/* -------------------------------------------------------------------------- */
/* Page emission                                                               */
/* -------------------------------------------------------------------------- */

const HTML_LANG = { ka: 'ka-GE', en: 'en' };

async function emit(url, locale) {
  const { html, head } = await render(url);

  const page = template
    .replace('<html lang="ka-GE"', `<html lang="${HTML_LANG[locale]}"`)
    .replace('    <title>KMS</title>', renderHead(head, locale))
    .replace('<div id="root"></div>', `<div id="root">${html}</div>`);

  const file = url === '/' ? path.join(DIST, 'index.html') : path.join(DIST, `${url.slice(1)}.html`);
  mkdirSync(path.dirname(file), { recursive: true });
  writeFileSync(file, page);
  return file;
}

/* -------------------------------------------------------------------------- */
/* sitemap.xml                                                                 */
/* -------------------------------------------------------------------------- */

function buildSitemap(lastmod) {
  const entries = [];
  for (const locale of LOCALES) {
    for (const route of SITE_ROUTES) {
      const alternates = [
        ...LOCALES.map(
          (code) =>
            `    <xhtml:link rel="alternate" hreflang="${code}" href="${COMPANY.url}${localePath(code, route.path)}" />`,
        ),
        `    <xhtml:link rel="alternate" hreflang="x-default" href="${COMPANY.url}${localePath(DEFAULT_LOCALE, route.path)}" />`,
      ].join('\n');

      entries.push(
        [
          '  <url>',
          `    <loc>${COMPANY.url}${localePath(locale, route.path)}</loc>`,
          `    <lastmod>${lastmod}</lastmod>`,
          '    <changefreq>monthly</changefreq>',
          `    <priority>${route.priority.toFixed(1)}</priority>`,
          alternates,
          '  </url>',
        ].join('\n'),
      );
    }
  }

  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">
${entries.join('\n')}
</urlset>
`;
}

/* -------------------------------------------------------------------------- */
/* .htaccess                                                                   */
/* -------------------------------------------------------------------------- */

function buildHtaccess() {
  const hostPattern = new URL(COMPANY.url).host.replace(/\./g, '\\.');
  const analyticsOrigin = ANALYTICS.enabled ? new URL(ANALYTICS.host).origin : null;

  // Plausible is cookieless and first-party-only in what it stores, but the
  // script and its event endpoint are still a separate origin, so both have to
  // be named. When analytics is switched off these entries vanish entirely.
  const scriptSrc = ["'self'", analyticsOrigin].filter(Boolean).join(' ');
  const connectSrc = ["'self'", 'https://api.web3forms.com', analyticsOrigin].filter(Boolean).join(' ');

  return `# KMS — Apache / cPanel configuration
#
# GENERATED by scripts/prerender.mjs — edit that script, not this file.
# Upload the contents of dist/ (including this file) into public_html.

Options -Indexes

# /services and /en are both route paths AND directories on disk. Without this,
# mod_dir would 301 them to /services/ and break the canonical URLs.
<IfModule mod_dir.c>
  DirectorySlash Off
  DirectoryIndex index.html
</IfModule>

# ---------------------------------------------------------------------------
# Routing. Every route has a prerendered .html file carrying its own <head>
# and its fully rendered body, so a crawler that does not run JavaScript still
# gets the real page.
# ---------------------------------------------------------------------------
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /

  # 0a. One canonical hostname. Folds www (and any other alias) into the apex,
  #     so a page is never reachable on two hostnames. The target is hard-coded
  #     rather than echoed from %{HTTP_HOST}, which a request can set to
  #     anything — echoing it back into a 301 is an open redirect. This cannot
  #     loop: after the redirect the host matches and the rule stops firing.
  RewriteCond %{HTTP_HOST} !^${hostPattern}$ [NC]
  RewriteRule ^ ${COMPANY.url}%{REQUEST_URI} [L,R=301]

  # 0b. HTTPS. %{HTTPS} is not set on every host — behind a proxy or on
  #     LiteSpeed the scheme arrives in a forwarded header instead, and a rule
  #     that checked only %{HTTPS} would redirect an already-secure request to
  #     itself forever. All four signals must say "not secure" before this
  #     fires, so an unknown-but-secure setup fails safe by doing nothing.
  RewriteCond %{HTTPS} !=on
  RewriteCond %{HTTP:X-Forwarded-Proto} !=https
  RewriteCond %{HTTP:X-Forwarded-SSL} !=on
  RewriteCond %{HTTP:CF-Visitor} !'"scheme":"https"'
  RewriteRule ^ ${COMPANY.url}%{REQUEST_URI} [L,R=301]

  # NOTE — there is deliberately no rewrite to pre-compressed .br/.gz files
  # here.
  #
  # That trick serves assets/app.css.br in place of assets/app.css and relies
  # on a <FilesMatch "\\.br$"> block to attach Content-Encoding: br. On cPanel's
  # LiteSpeed the header is matched against the *requested* path rather than the
  # rewritten one, so it never gets attached: the browser receives brotli bytes
  # labelled text/css and silently discards the stylesheet. The page then
  # renders as unstyled HTML — content intact, every style and script gone —
  # and nothing in the server log looks wrong.
  #
  # Compression still happens, just in the output filters below, where the
  # server sets Content-Encoding itself and cannot get it wrong. With hashed
  # filenames cached for a year, it costs one gzip per asset per visitor.

  # 1. Collapse the duplicate spellings of a page onto its canonical URL.
  #    /services.html and /services/ both used to answer 200 with identical
  #    content, which is three URLs for one page. Redirecting is cheaper than
  #    asking every crawler to work it out from the canonical tag.
  #    The internal rewrite in rule 4 is exempt: it carries no query string
  #    marker and REDIRECT_STATUS is only set once Apache has re-entered.
  RewriteCond %{ENV:REDIRECT_STATUS} ^$
  RewriteRule ^(.+)\\.html$ /$1 [L,R=301]

  RewriteCond %{ENV:REDIRECT_STATUS} ^$
  RewriteCond %{REQUEST_URI} !^/$
  RewriteRule ^(.+)/$ /$1 [L,R=301]

  # 2. Real files (hashed assets, sitemap.xml, robots.txt, og-image.png).
  RewriteCond %{REQUEST_FILENAME} -f
  RewriteRule ^ - [L]

  # 3. Prerendered route:  /services/cctv  ->  /services/cctv.html
  RewriteCond %{DOCUMENT_ROOT}/$1.html -f
  RewriteRule ^(.+?)/?$ /$1.html [L]

  # 4. The root. In a per-directory context the path for "/" is the empty
  #    string, which none of the rules above can match: the document root is a
  #    directory so -f fails, and rule 3 requires at least one character.
  #    DirectoryIndex cannot rescue it either, because mod_rewrite's fixup hook
  #    runs before mod_dir's — the 404 below would already have fired.
  RewriteRule ^$ index.html [L]

  # 5. Genuinely missing. Every real route has a prerendered file, so nothing
  #    legitimate reaches this line. The pattern is \`.+\` rather than \`^\`:
  #    a bare \`^\` matches the empty path too, which is precisely how this rule
  #    once 404'd the homepage.
  RewriteRule .+ - [L,R=404]
</IfModule>

# ---------------------------------------------------------------------------
# Compression
# ---------------------------------------------------------------------------
<IfModule mod_deflate.c>
  # This is the only thing compressing responses, so the type list has to be
  # complete. text/javascript is listed because that is what a host serves .js
  # as when our AddType directive does not take effect, and missing it would
  # ship the largest files on the site uncompressed.
  AddOutputFilterByType DEFLATE text/html text/plain text/css text/xml
  AddOutputFilterByType DEFLATE text/javascript application/javascript application/x-javascript
  AddOutputFilterByType DEFLATE application/json application/xml application/rss+xml
  AddOutputFilterByType DEFLATE application/manifest+json image/svg+xml image/x-icon
</IfModule>

<IfModule mod_brotli.c>
  AddOutputFilterByType BROTLI_COMPRESS text/html text/plain text/css text/xml
  AddOutputFilterByType BROTLI_COMPRESS text/javascript application/javascript
  AddOutputFilterByType BROTLI_COMPRESS application/json application/manifest+json
  AddOutputFilterByType BROTLI_COMPRESS image/svg+xml
</IfModule>

# ---------------------------------------------------------------------------
# Caching. Build assets carry a content hash, so they can be cached forever;
# HTML must never be cached or users get a stale page.
# ---------------------------------------------------------------------------
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css                "access plus 1 year"
  ExpiresByType application/javascript  "access plus 1 year"
  ExpiresByType image/svg+xml           "access plus 1 year"
  ExpiresByType image/webp              "access plus 1 year"
  ExpiresByType image/avif              "access plus 1 year"
  ExpiresByType image/png               "access plus 1 year"
  ExpiresByType image/jpeg              "access plus 1 year"
  ExpiresByType font/woff2              "access plus 1 year"
  ExpiresByType text/html               "access plus 0 seconds"
</IfModule>

<IfModule mod_headers.c>
  # Compressed responses must not be cached as if they were the only variant.
  <FilesMatch "\\.(css|js)$">
    Header append Vary Accept-Encoding
  </FilesMatch>

  # Every one of these carries a content hash in its filename, so a change
  # produces a new URL and the old one can be cached indefinitely.
  <FilesMatch "\\.(css|js|svg|woff2|png|jpe?g|webp|avif)$">
    Header set Cache-Control "public, max-age=31536000, immutable"
  </FilesMatch>

  # HTML has no hash, so it must be revalidated or visitors keep a stale page.
  <FilesMatch "\\.(html)$">
    Header set Cache-Control "no-cache, must-revalidate"
  </FilesMatch>

  # Baseline security headers
  Header always set X-Content-Type-Options "nosniff"
  Header always set X-Frame-Options "SAMEORIGIN"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Permissions-Policy "geolocation=(), microphone=(), camera=(), interest-cohort=()"

  # Puts the site in its own browsing-context group, so a window it opened —
  # or one that opened it — cannot reach into it. Safe here: nothing on the
  # site depends on cross-origin window handles.
  Header always set Cross-Origin-Opener-Policy "same-origin"

  # Only enable this while HTTPS is known good — it tells browsers to refuse
  # plain HTTP for a year. "preload" is deliberately omitted: getting off the
  # preload list takes months.
  Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

  # style-src needs 'unsafe-inline' because the reveal-on-scroll wrapper sets an
  # inline transition-delay and a static host cannot mint a per-request nonce.
  # script-src carries no such exemption, which is where it matters: an injected
  # <script> is blocked. JSON-LD is a data block, not executable, so it is
  # unaffected.
  Header always set Content-Security-Policy "default-src 'self'; script-src ${scriptSrc}; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src ${connectSrc}; form-action 'self' https://api.web3forms.com; frame-ancestors 'self'; base-uri 'self'; object-src 'none'; upgrade-insecure-requests"
</IfModule>

# ---------------------------------------------------------------------------
# Correct MIME types (some shared hosts still miss these)
# ---------------------------------------------------------------------------
<IfModule mod_mime.c>
  AddType application/javascript          .js .mjs
  AddType image/svg+xml                   .svg
  AddType image/avif                      .avif
  AddType font/woff2                      .woff2
  AddType application/manifest+json       .webmanifest
</IfModule>

ErrorDocument 404 /404.html
`;
}

/* -------------------------------------------------------------------------- */
/* Run                                                                         */
/* -------------------------------------------------------------------------- */

let count = 0;
for (const locale of LOCALES) {
  for (const route of SITE_ROUTES) {
    await emit(localePath(locale, route.path), locale);
    count += 1;
  }
}

// The 404 shell. Apache serves it via ErrorDocument at whatever URL was missed,
// so it has to stand alone; it carries noindex and no canonical.
await emit('/404', DEFAULT_LOCALE);
count += 1;

const lastmod = new Date().toISOString().slice(0, 10);
writeFileSync(path.join(DIST, 'sitemap.xml'), buildSitemap(lastmod));
writeFileSync(path.join(DIST, '.htaccess'), buildHtaccess());

/* -------------------------------------------------------------------------- */
/* Guard: does every asset the HTML asks for actually exist?                   */
/*                                                                            */
/* A missing stylesheet does not fail loudly — the page still renders, just    */
/* unstyled — so the build checks rather than trusting.                        */
/* -------------------------------------------------------------------------- */

const referenced = new Set(
  [...readFileSync(path.join(DIST, 'index.html'), 'utf8').matchAll(/\/assets\/[A-Za-z0-9._-]+/g)].map(
    (m) => m[0],
  ),
);
const missing = [...referenced].filter((ref) => !existsSync(path.join(DIST, ref.slice(1))));
if (missing.length > 0) {
  console.error(`✗ HTML references assets that were not built:\n  ${missing.join('\n  ')}`);
  process.exit(1);
}

// dist-ssr is a build artefact, not something to upload.
rmSync(SSR, { recursive: true, force: true });

console.log(`✓ Prerendered ${count} pages`);
console.log(`✓ sitemap.xml — ${SITE_ROUTES.length * LOCALES.length} URLs, lastmod ${lastmod}`);
console.log(`✓ .htaccess — analytics ${ANALYTICS.enabled ? `on (${ANALYTICS.host})` : 'off'}`);
console.log(`✓ ${referenced.size} referenced assets all present`);
