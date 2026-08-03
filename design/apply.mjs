#!/usr/bin/env node
/**
 * Applies the "Atelier" visual layer to the prerendered site in dist/.
 *
 * The upstream app is shipped as a build, not as source, so the layer is added
 * as two extra assets that load after the build's own bundle. Both are written
 * with a content hash in the filename, matching the build's convention — the
 * .htaccess marks everything under assets/ as immutable for a year, so an
 * unhashed name could never be updated in a browser that had already seen it.
 *
 * Re-running is safe: previous kms-atelier-* assets and their tags are removed
 * before the new ones are written.
 *
 *   node design/apply.mjs [distDir]
 */
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

const HERE = path.dirname(fileURLToPath(import.meta.url));
const DIST = path.resolve(process.argv[2] || path.join(HERE, '..', 'dist'));
const ASSETS = path.join(DIST, 'assets');

const hash = (buf) =>
  crypto.createHash('sha256').update(buf).digest('base64url').replace(/[-_]/g, '').slice(0, 8);

function walk(dir, acc = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(p, acc);
    else if (entry.name.endsWith('.html')) acc.push(p);
  }
  return acc;
}

// 1. Clear anything a previous run left behind.
for (const name of fs.readdirSync(ASSETS)) {
  if (name.startsWith('kms-atelier-')) fs.unlinkSync(path.join(ASSETS, name));
}

// 2. Emit hashed copies plus the pre-compressed variants .htaccess looks for.
const emitted = {};
for (const [key, src] of Object.entries({
  css: path.join(HERE, 'kms-atelier.css'),
  js: path.join(HERE, 'kms-atelier.js'),
})) {
  const body = fs.readFileSync(src);
  const name = `kms-atelier-${hash(body)}.${key}`;
  fs.writeFileSync(path.join(ASSETS, name), body);
  fs.writeFileSync(path.join(ASSETS, name + '.gz'), zlib.gzipSync(body, { level: 9 }));
  fs.writeFileSync(
    path.join(ASSETS, name + '.br'),
    zlib.brotliCompressSync(body, {
      params: { [zlib.constants.BROTLI_PARAM_QUALITY]: 11 },
    })
  );
  emitted[key] = name;
}

const cssTag = `<link rel="stylesheet" href="/assets/${emitted.css}" />`;
const jsTag = `<script src="/assets/${emitted.js}" defer></script>`;

// 3. Inject into every prerendered shell. The stylesheet goes immediately after
//    the build's own, so it wins the cascade on equal specificity.
let injected = 0;
for (const file of walk(DIST)) {
  let html = fs.readFileSync(file, 'utf8');
  html = html
    .replace(/\n?[ \t]*<link\b[^>]*\/assets\/kms-atelier[^>]*>/g, '')
    .replace(/\n?[ \t]*<script\b[^>]*\/assets\/kms-atelier[^>]*><\/script>/g, '');

  const buildCss = html.match(/<link rel="stylesheet"[^>]*assets\/index-[^>]*>/);
  html = buildCss
    ? html.replace(buildCss[0], `${buildCss[0]}\n    ${cssTag}`)
    : html.replace('</head>', `    ${cssTag}\n  </head>`);
  html = html.replace('</head>', `    ${jsTag}\n  </head>`);

  fs.writeFileSync(file, html);
  injected++;
}

console.log(`atelier: ${emitted.css}, ${emitted.js} → ${injected} html files`);
