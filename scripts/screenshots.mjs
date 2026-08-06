/**
 * Renders the built site and writes desktop + mobile screenshots to design-preview/.
 *
 *   npm run build && npm run shots
 *
 * Requires a preview server on http://127.0.0.1:4173 (npm run preview).
 * Looping animations are paused before full-page captures so the shot can settle.
 */
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT = path.join(ROOT, 'design-preview');
const URL = process.env.PREVIEW_URL ?? 'http://127.0.0.1:4173/';

const SECTIONS = [
  ['01-hero', '#top'],
  ['02-services', '#services'],
  ['03-solutions', '#solutions'],
  ['04-process', '#process'],
  ['05-work', '#work'],
  ['06-about', '#about'],
  ['07-contact', '#contact'],
];

fs.mkdirSync(OUT, { recursive: true });

const browser = await chromium.launch({ args: ['--no-sandbox', '--force-color-profile=srgb'] });
const problems = [];

/** Load the page and scroll through it once so every scroll-reveal fires. */
async function prime(page) {
  page.on('console', (m) => m.type() === 'error' && problems.push(m.text()));
  page.on('pageerror', (e) => problems.push(String(e)));
  await page.goto(URL, { waitUntil: 'networkidle' });
  await page.waitForTimeout(2000);
  await page.evaluate(async () => {
    const step = window.innerHeight * 0.8;
    for (let y = 0; y < document.body.scrollHeight; y += step) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 200));
    }
    window.scrollTo(0, 0);
  });
  await page.waitForTimeout(1400);
}

async function freezeAnimations(page) {
  await page.addStyleTag({
    content: '*,*::before,*::after{animation-play-state:paused!important;transition:none!important}',
  });
  await page.waitForTimeout(400);
}

async function checkOverflow(page, label) {
  const { scrollW, clientW } = await page.evaluate(() => ({
    scrollW: document.documentElement.scrollWidth,
    clientW: document.documentElement.clientWidth,
  }));
  const ok = scrollW <= clientW;
  console.log(`  overflow ${label}: ${scrollW}/${clientW} ${ok ? 'ok' : 'HORIZONTAL SCROLL'}`);
  if (!ok) problems.push(`horizontal overflow on ${label}`);
}

// ── desktop ──────────────────────────────────────────────────────────────────
const desktop = await browser.newContext({ viewport: { width: 1440, height: 900 }, deviceScaleFactor: 2 });
const dp = await desktop.newPage();
await prime(dp);

for (const [name, selector] of SECTIONS) {
  const el = await dp.$(selector);
  if (!el) {
    problems.push(`missing section ${selector}`);
    continue;
  }
  await el.scrollIntoViewIfNeeded();
  await dp.waitForTimeout(800);
  await el.screenshot({ path: path.join(OUT, `${name}.png`) });
  console.log('  wrote', name);
}

await checkOverflow(dp, 'desktop');
await freezeAnimations(dp);
await dp.evaluate(() => window.scrollTo(0, 0));
await dp.waitForTimeout(500);
await dp.screenshot({ path: path.join(OUT, '00-full-desktop.png'), fullPage: true, timeout: 60000 });
console.log('  wrote 00-full-desktop');

// ── mobile ───────────────────────────────────────────────────────────────────
const mobile = await browser.newContext({
  viewport: { width: 390, height: 844 },
  deviceScaleFactor: 2,
  isMobile: true,
  hasTouch: true,
});
const mp = await mobile.newPage();
await prime(mp);
await mp.screenshot({ path: path.join(OUT, '08-mobile-hero.png') });
await checkOverflow(mp, 'mobile');

await freezeAnimations(mp);
await mp.evaluate(() => window.scrollTo(0, 0));
await mp.waitForTimeout(500);
await mp.screenshot({ path: path.join(OUT, '09-full-mobile.png'), fullPage: true, timeout: 60000 });

await mp.click('button[aria-label="მენიუს გახსნა"]').catch(() => {});
await mp.waitForTimeout(700);
await mp.screenshot({ path: path.join(OUT, '10-mobile-menu.png') });
console.log('  wrote mobile shots');

await browser.close();

if (problems.length) {
  console.error('\nProblems found:\n - ' + problems.join('\n - '));
  process.exit(1);
}
console.log('\nAll screenshots written to design-preview/');
