// Print user-guide.html to an A4 PDF with running header and page numbers.
//   node render.js "C:/path/to/Amazon-Marketplace-Pro-User-Guide.pdf"
const puppeteer = require('puppeteer');
const path = require('path');

const SRC = path.resolve(__dirname, 'user-guide.html').split(path.sep).join('/');
const OUT = process.argv[2] || path.resolve(__dirname, 'Amazon-Marketplace-Pro-User-Guide.pdf');

(async () => {
  const browser = await puppeteer.launch({ headless: true, args: ['--allow-file-access-from-files'] });
  const page = await browser.newPage();
  await page.goto('file:///' + SRC, { waitUntil: 'networkidle0' });
  await page.pdf({
    path: OUT,
    format: 'A4',
    printBackground: true,
    displayHeaderFooter: true,
    headerTemplate: `<div style="font-family:'Segoe UI',Arial,sans-serif;font-size:8pt;color:#667077;width:100%;padding:0 18mm;display:flex;justify-content:space-between;">
        <span>Amazon Marketplace Pro · User guide</span><span>IntelliPresta</span></div>`,
    footerTemplate: `<div style="font-family:'Segoe UI',Arial,sans-serif;font-size:8pt;color:#667077;width:100%;padding:0 18mm;display:flex;justify-content:space-between;">
        <span>Version 1.5.0</span><span>Page <span class="pageNumber"></span> of <span class="totalPages"></span></span></div>`,
    margin: { top: '22mm', right: '18mm', bottom: '20mm', left: '18mm' },
  });
  await browser.close();
  console.log('written', OUT);
})().catch((e) => { console.error(e.message); process.exit(1); });
