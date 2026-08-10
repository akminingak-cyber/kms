/**
 * Renders the social share cards — one per locale — into `public/`.
 *
 *     node scripts/og-image.mjs
 *
 * Why a script rather than a saved export: the card repeats the hero headline,
 * and a headline that drifts from the card nobody remembers to re-export is the
 * usual failure. Both strings are read from `src/content/*.ts` below, so
 * regenerating after a copy change is one command.
 *
 * Georgian is the primary locale, and Facebook, LinkedIn, Telegram and iMessage
 * all read og:image from the page they were handed — so a Georgian page needs a
 * Georgian card. The two differ only in text; every colour comes from the same
 * tokens the site uses.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

/**
 * Playwright is deliberately not a dependency of this project — it would add a
 * browser download to every `npm install` for a script that runs when the
 * headline changes, which is roughly never. Resolve it from wherever it happens
 * to be installed instead, and say so plainly when it is nowhere.
 */
async function loadChromium() {
  for (const spec of ['playwright', '/opt/node22/lib/node_modules/playwright/index.mjs']) {
    try {
      return (await import(spec)).chromium;
    } catch {
      /* try the next one */
    }
  }
  throw new Error(
    'Playwright is needed to render the share cards. Install it once with\n' +
      '  npm i -g playwright && npx playwright install chromium\n' +
      'then run this script again. The committed PNGs stay valid until the ' +
      'hero headline changes, so this is not part of the build.',
  );
}

/** Pulls a single quoted string out of a content file by key. */
function stringFrom(file, key) {
  const src = fs.readFileSync(path.join(root, 'src/content', file), 'utf8');
  const m = src.match(new RegExp(`${key}: '((?:[^'\\\\]|\\\\.)*)'`));
  if (!m) throw new Error(`${key} not found in ${file}`);
  return m[1].replace(/\\'/g, "'");
}

const CARDS = [
  {
    out: 'og-image.png',
    lang: 'en',
    lead: stringFrom('en.ts', 'headlineLead'),
    accent: stringFrom('en.ts', 'headlineAccent'),
    tagline: stringFrom('en.ts', 'builtNote'),
    pills: ['Networks & cabling', 'CCTV & access control', 'Smart home & building', 'Managed IT'],
    headlineSize: 54,
    pillSize: 21,
    pillPad: '12px 20px',
    pillGap: 14,
  },
  {
    out: 'og-image-ka.png',
    lang: 'ka',
    lead: stringFrom('ka.ts', 'headlineLead'),
    accent: stringFrom('ka.ts', 'headlineAccent'),
    tagline: stringFrom('ka.ts', 'builtNote'),
    // The four service families, in the site's own words.
    pills: ['ქსელები და კაბელები', 'ვიდეოკონტროლი და დაშვება', 'ჭკვიანი სისტემები', 'მართული მხარდაჭერა'],
    // Georgian sets wider than English at the same size, and mkhedruli has no
    // capitals to lift the line — 46px is what keeps the lead on two lines and
    // the accent on one at this width. The pills are stepped down and tightened
    // for the same reason: at the English metrics the fourth wraps to a second
    // row and the card stops matching its pair.
    headlineSize: 46,
    pillSize: 17,
    pillPad: '10px 15px',
    pillGap: 11,
  },
];

const fontData = (p) => fs.readFileSync(path.join(root, 'node_modules', p)).toString('base64');
const INTER = fontData('@fontsource-variable/inter/files/inter-latin-wght-normal.woff2');
const GEORGIAN = fontData(
  '@fontsource-variable/noto-sans-georgian/files/noto-sans-georgian-georgian-wght-normal.woff2',
);

function html(card) {
  return `<!doctype html><html lang="${card.lang}"><head><meta charset="utf-8"><style>
@font-face {
  font-family: 'Inter'; font-weight: 100 900; font-display: block;
  src: url(data:font/woff2;base64,${INTER}) format('woff2-variations');
}
@font-face {
  font-family: 'NotoGeo'; font-weight: 100 900; font-display: block;
  src: url(data:font/woff2;base64,${GEORGIAN}) format('woff2-variations');
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  width: 1200px; height: 630px; overflow: hidden;
  font-family: 'Inter', 'NotoGeo', sans-serif;
  background: rgb(6 11 24);
  color: rgb(242 246 255);
  -webkit-font-smoothing: antialiased;
}
.card { position: relative; width: 1200px; height: 630px; padding: 60px 76px; display: flex; flex-direction: column; }
/* The same two-layer ground the site's hero uses: a grid at 3.5% and a
   primary-500 glow off the top-right corner. */
.grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgb(255 255 255 / 0.035) 1px, transparent 1px),
    linear-gradient(90deg, rgb(255 255 255 / 0.035) 1px, transparent 1px);
  background-size: 64px 64px;
}
.glow {
  position: absolute; top: -180px; right: -140px; width: 900px; height: 700px;
  background: radial-gradient(closest-side, rgb(29 111 242 / 0.42), rgb(29 111 242 / 0) 100%);
}
.inner { position: relative; display: flex; flex-direction: column; height: 100%; }

.brand { display: flex; align-items: center; gap: 22px; }
.mark {
  width: 66px; height: 66px; border-radius: 14px; display: grid; place-items: center;
  border: 1px solid rgb(75 134 250 / 0.4);
  background: linear-gradient(135deg, rgb(29 111 242 / 0.3), rgb(34 211 238 / 0.1));
}
.word { font-size: 46px; font-weight: 800; letter-spacing: -0.02em; }

.headline { margin-top: auto; font-weight: 800; letter-spacing: -0.015em; line-height: 1.18; }
.accent {
  background: linear-gradient(96deg, rgb(127 168 255), rgb(53 221 240));
  -webkit-background-clip: text; background-clip: text; color: transparent;
}

.pills { display: flex; flex-wrap: wrap; margin-top: 42px; }
.pill {
  border: 1px solid rgb(255 255 255 / 0.11); border-radius: 10px;
  background: rgb(10 18 35 / 0.75);
  color: rgb(210 218 233);
  white-space: nowrap;
}
.foot {
  margin-top: auto; padding-top: 26px; border-top: 1px solid rgb(255 255 255 / 0.11);
  display: flex; justify-content: space-between; align-items: baseline;
  font-size: 22px; color: rgb(174 187 209);
}
.dom { color: rgb(131 148 178); }
</style></head><body>
<div class="card">
  <div class="grid"></div><div class="glow"></div>
  <div class="inner">
    <div class="brand">
      <span class="mark">
        <svg viewBox="0 0 32 32" width="40" height="40" aria-hidden="true">
          <path d="M7 6 L7 26 M7 16 L18 6 M7 16 L18 26" stroke="rgb(226 238 255)" stroke-width="2.4"
                stroke-linecap="round" stroke-linejoin="round" fill="none"/>
          <circle cx="24" cy="9" r="2.6" fill="rgb(34 211 238)"/>
          <circle cx="24" cy="23" r="2.6" fill="rgb(34 211 238)" fill-opacity="0.6"/>
        </svg>
      </span>
      <span class="word">KMS</span>
    </div>

    <h1 class="headline" style="font-size:${card.headlineSize}px">
      ${card.lead}<br><span class="accent">${card.accent}</span>
    </h1>

    <div class="pills" style="font-size:${card.pillSize}px;gap:${card.pillGap}px">
      ${card.pills
        .map((p) => `<span class="pill" style="padding:${card.pillPad}">${p}</span>`)
        .join('')}
    </div>

    <div class="foot"><span>${card.tagline}</span><span class="dom">kms.ge</span></div>
  </div>
</div>
</body></html>`;
}

const chromium = await loadChromium();
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1200, height: 630 }, deviceScaleFactor: 1 });

for (const card of CARDS) {
  await page.setContent(html(card), { waitUntil: 'load' });
  await page.evaluate(() => document.fonts.ready);

  // A card that overflows its own frame ships a cropped headline to every
  // social feed, so fail loudly rather than write it. Measure the content
  // column, not the body: the glow is deliberately positioned past the right
  // edge and would report 140px of "overflow" that `overflow: hidden` on the
  // frame already handles.
  const overflow = await page.evaluate(() => {
    const inner = document.querySelector('.inner');
    const box = inner.getBoundingClientRect();
    let worst = { x: 0, y: 0, what: '' };
    for (const el of inner.querySelectorAll('.headline, .pills, .foot, .brand')) {
      const r = el.getBoundingClientRect();
      const x = Math.round(r.right - box.right);
      const y = Math.round(r.bottom - box.bottom);
      if (x > worst.x || y > worst.y) {
        worst = { x: Math.max(x, worst.x), y: Math.max(y, worst.y), what: el.className };
      }
    }
    return { ...worst, tall: Math.round(inner.scrollHeight - inner.clientHeight) };
  });
  if (overflow.x > 1 || overflow.y > 1 || overflow.tall > 1) {
    throw new Error(
      `${card.out}: .${overflow.what} overflows by ${overflow.x}×${overflow.y}px ` +
        `(column ${overflow.tall}px too tall) — reduce headlineSize or pillSize`,
    );
  }

  const file = path.join(root, 'public', card.out);
  await page.screenshot({ path: file, clip: { x: 0, y: 0, width: 1200, height: 630 } });
  console.log(`${card.out}  ${(fs.statSync(file).size / 1024).toFixed(0)} KB`);
}

await browser.close();
