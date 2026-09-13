/**
 * Render the Addons listing infographics at 1000x1000.
 *
 * PrestaShop Addons wants square images that are readable rather than
 * decorative, so these are rendered at 2x and downscaled by the encoder
 * rather than screenshotted at final size - text edges stay clean.
 *
 *   node render-cards.js [outDir] [source.html]
 */
const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');
const { pathToFileURL } = require('url');

const outDir = process.argv[2] || 'cards';
const src = process.argv[3] || 'cards.html';

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  const b = await puppeteer.launch({
    args: ['--no-sandbox', '--force-device-scale-factor=1', '--font-render-hinting=none'],
  });
  const p = await b.newPage();
  await p.setViewport({ width: 1000, height: 1000, deviceScaleFactor: 2 });
  const errs = [];
  p.on('pageerror', e => errs.push(String(e)));
  p.on('console', m => { if (m.type() === 'error') errs.push('console: ' + m.text()); });
  await p.goto(pathToFileURL(path.resolve(src)).href, { waitUntil: 'networkidle0' });

  const n = await p.evaluate(() => window.CARD_COUNT);
  for (let i = 0; i < n; i++) {
    const slug = await p.evaluate(x => window.card(x), i);
    const name = `addons-${String(i + 1).padStart(2, '0')}-${slug}.png`;
    await p.screenshot({ path: path.join(outDir, name) });
    console.log('  ' + name);
  }

  await b.close();
  console.log(errs.length ? 'PAGE ERRORS:\n' + errs.join('\n') : `\n${n} cards, no page errors`);
})();
