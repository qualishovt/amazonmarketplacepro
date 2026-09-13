/**
 * One frame from every scene of a translated cut, tiled into a contact sheet,
 * so text that overflows or collides shows up before a full render.
 *
 *   node probe-locale.js <iso>     ->  probe-<iso>/sheet.png
 */
const puppeteer = require('puppeteer');
const { execFileSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const ISO = process.argv[2];
const SRC = path.join(__dirname, ISO ? `promo7.${ISO}.html` : 'promo7.html');
const OUT = path.join(__dirname, 'probe-' + (ISO || 'en'));
/* s1 headline, s1 second headline + sub, s2, s3, the four montage slots, the
   claim with its chip grid, s5, s6 with the trust captions, the sign-off */
const TIMES = [2.5, 9.5, 17, 25.5, 30, 37, 44, 51, 60, 66.5, 77, 85];

(async () => {
  fs.rmSync(OUT, { recursive: true, force: true });
  fs.mkdirSync(OUT, { recursive: true });
  const b = await puppeteer.launch({ args: ['--no-sandbox', '--force-device-scale-factor=1', '--font-render-hinting=none'] });
  const p = await b.newPage();
  const errs = [];
  p.on('pageerror', e => errs.push(String(e)));
  await p.setViewport({ width: 1920, height: 1080 });
  await p.goto('file://' + SRC.replace(/\\/g, '/'), { waitUntil: 'networkidle0' });
  for (let i = 0; i < TIMES.length; i++) {
    await p.evaluate(t => window.render(t), TIMES[i]);
    await p.screenshot({ path: path.join(OUT, String(i).padStart(2, '0') + '.png') });
  }
  await b.close();
  execFileSync('ffmpeg', ['-y', '-v', 'error', '-i', path.join(OUT, '%02d.png'),
    '-vf', 'scale=640:-1,tile=3x4:padding=6:color=white', '-frames:v', '1', path.join(OUT, 'sheet.png')]);
  console.log(errs.length ? 'PAGE ERRORS: ' + errs.join(' | ') : OUT + '/sheet.png');
})().catch(e => { console.error(e.message); process.exit(1); });
