/**
 * Render the YouTube thumbnail at 1920x1080.
 *
 *   node render-thumb.js <out.png> [source.html]
 */
const puppeteer = require('puppeteer');
const path = require('path');
const { pathToFileURL } = require('url');

const out = process.argv[2] || 'youtube-thumbnail.png';
const src = process.argv[3] || 'thumb.html';

(async () => {
  const b = await puppeteer.launch({ args: ['--no-sandbox', '--force-device-scale-factor=1', '--font-render-hinting=none'] });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(String(e)));
  await p.setViewport({ width: 1920, height: 1080 });
  await p.goto(pathToFileURL(path.resolve(__dirname, src)).href, { waitUntil: 'networkidle0' });
  await p.screenshot({ path: out });
  await b.close();
  console.log(errs.length ? 'PAGE ERRORS: ' + errs.join(' | ') : 'wrote ' + out);
})();
