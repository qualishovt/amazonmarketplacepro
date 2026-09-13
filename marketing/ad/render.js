/**
 * Render scene.html frame by frame at 1920x1080.
 *
 * The page exposes render(t) where t is seconds. Time is driven from here
 * rather than by the browser clock, so a slow frame changes nothing about
 * the output - the render is deterministic and repeatable.
 *
 *   node render.js [scene.html] [seconds] [outDir]
 */
const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

const FPS = 30;
const scene = process.argv[2] || 'scene.html';
const seconds = parseFloat(process.argv[3] || '10');
const outDir = process.argv[4] || 'frames';

(async () => {
  fs.rmSync(outDir, { recursive: true, force: true });
  fs.mkdirSync(outDir, { recursive: true });

  const browser = await puppeteer.launch({
    args: ['--no-sandbox', '--force-device-scale-factor=1', '--font-render-hinting=none'],
  });
  const page = await browser.newPage();
  await page.setViewport({ width: 1920, height: 1080 });
  await page.goto('file://' + path.resolve(scene).replace(/\\/g, '/'), { waitUntil: 'networkidle0' });

  const total = Math.round(seconds * FPS);
  const started = Date.now();

  for (let f = 0; f < total; f++) {
    await page.evaluate(t => window.render(t), f / FPS);
    await page.screenshot({
      path: path.join(outDir, String(f).padStart(5, '0') + '.png'),
      optimizeForSpeed: true,
    });
    if (f % 30 === 0) {
      const pct = ((f / total) * 100).toFixed(0);
      const rate = f ? (f / ((Date.now() - started) / 1000)).toFixed(1) : '0';
      process.stdout.write(`\r  ${pct}%  frame ${f}/${total}  ${rate} fps`);
    }
  }

  await browser.close();
  process.stdout.write(`\r  done: ${total} frames in ${((Date.now() - started) / 1000).toFixed(0)}s\n`);
})();
