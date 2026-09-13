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
  // The browser locale words native controls, such as the file input button.
  const iso = (outDir.match(/out-([a-z]{2})/) || [])[1];
  const locale = { fr: 'fr-FR', es: 'es-ES', it: 'it-IT', pl: 'pl-PL', de: 'de-DE' }[iso] || 'en-GB';
  const b = await puppeteer.launch({ args: ['--no-sandbox', '--font-render-hinting=none', '--lang=' + locale] });
  const p = await b.newPage();
  await p.setViewport({ width: 1440, height: 900, deviceScaleFactor: 2 });
  for (const shot of shots) {
    // name@selector: crop to that element (with a margin) instead of the viewport
    const crop = shot.indexOf('@') > 0 ? shot.split(/@(.+)/) : null;
    if (crop) {
      await p.goto(BASE + 'amz-snap-' + crop[0] + '.html', { waitUntil: 'networkidle0', timeout: 120000 });
      await p.evaluate(() => document.fonts.ready);
      // Document coordinates, captured beyond the viewport without scrolling:
      // scrolled, the fixed PrestaShop header would sit on top of the element.
      const box = await p.evaluate(sel => {
        const e = document.querySelector(sel);
        if (!e) return null;
        const r = e.getBoundingClientRect();
        return { x: r.left + window.scrollX, y: r.top + window.scrollY, width: r.width, height: r.height };
      }, crop[1]);
      if (!box) { console.log('  ' + crop[0] + ': no ' + crop[1]); continue; }
      const pad = 10;
      await p.screenshot({ path: path.join(outDir, crop[0] + '.png'), captureBeyondViewport: true,
        clip: { x: box.x - pad, y: box.y - pad, width: box.width + 2 * pad, height: box.height + 2 * pad } });
      console.log('  ' + crop[0] + '.png (cropped)');
      continue;
    }
    const [name, selector] = shot.split(/:(.+)/);
    await p.goto(BASE + 'amz-snap-' + name + '.html', { waitUntil: 'networkidle0', timeout: 120000 });
    await p.evaluate(() => document.fonts.ready);
    if (selector) {
      await p.evaluate(sel => {
        const el = document.querySelector(sel);
        if (el) { window.scrollTo(0, el.getBoundingClientRect().top + window.scrollY - 215); }
      }, selector);
    }
    await p.screenshot({ path: path.join(outDir, name + '.png') });
    console.log('  ' + name + '.png');
  }
  await b.close();
})().catch(e => { console.error(e.message); process.exit(1); });
