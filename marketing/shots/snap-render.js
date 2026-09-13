/**
 * Render back-office snapshots to the 1440x900 @2x screenshots the Addons
 * listing and the user guide share.
 *
 *   node snap-render.js <outDir> <name[:selector]> ...
 *
 * The admin session cookie is httpOnly, so a headless browser cannot open the
 * module page itself. Instead the logged-in browser posts the page it already
 * shows (see snap-page.js); the receiver stores it next to the shop as
 * amz-snap-<name>.html, where the admin CSS and fonts load from the same
 * origin. A name may carry a CSS selector after a colon: the page is scrolled
 * so that element sits under the fixed header, as the task-table shot needs.
 */
const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

const BASE = 'http://localhost/p915/';
const [, , outDir, ...shots] = process.argv;

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  const b = await puppeteer.launch({ args: ['--no-sandbox', '--font-render-hinting=none'] });
  const p = await b.newPage();
  await p.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
  for (const shot of shots) {
    const [name, selector] = shot.split(/:(.+)/);
    await p.goto(BASE + 'amz-snap-' + name + '.html', { waitUntil: 'networkidle0', timeout: 120000 });
    await p.evaluate(() => document.fonts.ready);
    if (selector) {
      await p.evaluate(sel => {
        const el = document.querySelector(sel);
        if (el) { window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - 135); }
      }, selector);
    }
    await p.screenshot({ path: path.join(outDir, name + '.png') });
    console.log('  ' + name + '.png');
  }
  await b.close();
})().catch(e => { console.error(e.message); process.exit(1); });
