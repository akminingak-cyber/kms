/**
 * Serves dist/ the way the generated .htaccess does, so what you test locally
 * is what Apache will do: pre-compressed assets, extensionless routes,
 * .html/trailing-slash redirects, and a real 404 status for missing pages.
 *
 *   node scripts/serve-dist.mjs [port]
 */
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';

const ROOT = path.join(process.cwd(), 'dist');
const PORT = Number(process.argv[2] ?? 4173);

/**
 * Replays the security headers from the generated .htaccess.
 *
 * Without this the local server is more permissive than production, and a
 * Content-Security-Policy mistake — an inline script whose hash is missing, say
 * — passes every local check and only shows up as a console error on the live
 * site, where the page still renders and nothing looks broken.
 */
function securityHeaders() {
  const htaccess = path.join(ROOT, '.htaccess');
  if (!fs.existsSync(htaccess)) return {};
  const source = fs.readFileSync(htaccess, 'utf8');
  const headers = {};
  for (const [, name, value] of source.matchAll(
    /^\s*Header always set ([\w-]+) "([^"]*)"/gm,
  )) {
    // HSTS is deliberately skipped: telling a browser to force HTTPS on
    // localhost for a year would break every other local project on this
    // machine, and it is not what we are testing here.
    if (name.toLowerCase() === 'strict-transport-security') continue;
    headers[name] = value;
  }
  return headers;
}

const SECURITY = securityHeaders();

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.js': 'application/javascript; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.webp': 'image/webp',
  '.avif': 'image/avif',
  '.woff2': 'font/woff2',
  '.xml': 'application/xml',
  '.txt': 'text/plain; charset=utf-8',
  '.webmanifest': 'application/manifest+json',
};

/**
 * Walks up from the requested path to the document root and returns the
 * ErrorDocument of the nearest directory that declares one — Apache's own
 * resolution order, which is what makes the per-locale 404 work.
 */
function errorDocumentFor(url) {
  const parts = url.split('/').filter(Boolean).slice(0, -1);
  for (let i = parts.length; i >= 0; i--) {
    const dir = path.join(ROOT, ...parts.slice(0, i));
    const file = path.join(dir, '.htaccess');
    if (fs.existsSync(file)) {
      const m = fs.readFileSync(file, 'utf8').match(/^\s*ErrorDocument\s+404\s+(\S+)/m);
      if (m) return m[1].replace(/^\//, '');
    }
  }
  return '404.html';
}

/**
 * The Cache-Control that applies to a file, resolved the way Apache does it:
 * the nearest directory wins, and within a file any <FilesMatch> whose pattern
 * matches the basename takes precedence over an unscoped rule. Without the
 * FilesMatch part this replay handed HTML the immutable policy meant for hashed
 * build output — the exact mistake it exists to catch.
 */
function cacheControlFor(file) {
  const name = path.basename(file);
  let dir = path.dirname(file);
  while (dir.startsWith(ROOT)) {
    const ht = path.join(dir, '.htaccess');
    if (fs.existsSync(ht)) {
      const text = fs.readFileSync(ht, 'utf8');
      let unscoped;
      for (const m of text.matchAll(
        /<FilesMatch "([^"]+)">([\s\S]*?)<\/FilesMatch>|^[ \t]*Header set Cache-Control "([^"]*)"/gm,
      )) {
        if (m[3]) { unscoped ??= m[3]; continue; }
        const inner = m[2].match(/Header set Cache-Control "([^"]*)"/);
        if (inner && new RegExp(m[1]).test(name)) return inner[1];
      }
      if (unscoped) return unscoped;
    }
    if (dir === ROOT) break;
    dir = path.dirname(dir);
  }
  return undefined;
}

const send = (res, status, headers, body) => {
  res.writeHead(status, headers);
  res.end(body);
};

http
  .createServer((req, res) => {
    const url = decodeURIComponent((req.url ?? '/').split('?')[0]);
    const accept = req.headers['accept-encoding'] ?? '';

    // .htaccess rule 2 — collapse duplicate spellings onto the canonical URL.
    if (url !== '/' && (url.endsWith('.html') || url.endsWith('/'))) {
      return send(res, 301, { Location: url.replace(/\.html$/, '').replace(/\/$/, '') || '/' }, '');
    }

    let file = path.join(ROOT, url);
    if (url === '/') file = path.join(ROOT, 'index.html');
    else if (!fs.existsSync(file) || fs.statSync(file).isDirectory()) {
      const candidate = path.join(ROOT, `${url}.html`);
      if (fs.existsSync(candidate)) file = candidate;
      else {
        // A miss returns 404, not a 200 shell — that distinction is the whole
        // reason the 404 page exists as its own file. Which 404 comes from the
        // nearest directory's ErrorDocument, the same way Apache resolves it,
        // so a miss under /en/ answers in English.
        return send(
          res,
          404,
          { 'Content-Type': MIME['.html'] },
          fs.readFileSync(path.join(ROOT, errorDocumentFor(url))),
        );
      }
    }

    const ext = path.extname(file);
    const headers = { 'Content-Type': MIME[ext] ?? 'application/octet-stream', ...SECURITY };
    const cc = cacheControlFor(file);
    if (cc) headers['Cache-Control'] = cc;

    // Compressed on the fly, exactly as the output filters in .htaccess do —
    // nothing is served from a pre-compressed twin on disk.
    if (['.html', '.css', '.js', '.xml', '.svg', '.txt'].includes(ext) && accept.includes('gzip')) {
      headers['Content-Encoding'] = 'gzip';
      headers.Vary = 'Accept-Encoding';
      return send(res, 200, headers, zlib.gzipSync(fs.readFileSync(file)));
    }

    send(res, 200, headers, fs.readFileSync(file));
  })
  .listen(PORT, () => console.log(`dist/ on http://localhost:${PORT}`));
