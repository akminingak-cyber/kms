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
        // reason the 404 page exists as its own file.
        return send(
          res,
          404,
          { 'Content-Type': MIME['.html'] },
          fs.readFileSync(path.join(ROOT, '404.html')),
        );
      }
    }

    const ext = path.extname(file);
    const headers = { 'Content-Type': MIME[ext] ?? 'application/octet-stream' };

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
