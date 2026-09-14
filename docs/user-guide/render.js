// Print the user guide to an A4 PDF with running header and page numbers.
//   node render.js "C:/path/to/Amazon-Marketplace-Pro-User-Guide.pdf"
//   node render.js "C:/path/to/Amazon-Marketplace-Pro-Guide-fr.pdf" fr
// A language code prints user-guide.<iso>.html with the header and footer in
// that language.
const puppeteer = require('puppeteer');
const path = require('path');

const ISO = process.argv[3] || 'en';
const WORDS = {
  en: ['User guide', 'Version', 'Page', 'of'],
  fr: ["Guide d'utilisation", 'Version', 'Page', 'sur'],
  es: ['Guía del usuario', 'Versión', 'Página', 'de'],
  it: ['Guida utente', 'Versione', 'Pagina', 'di'],
  pl: ['Podręcznik użytkownika', 'Wersja', 'Strona', 'z'],
  de: ['Benutzerhandbuch', 'Version', 'Seite', 'von'],
}[ISO];
if (!WORDS) { console.error('unknown language ' + ISO); process.exit(1); }
const [guide, version, pageWord, ofWord] = WORDS;

const file = ISO === 'en' ? 'user-guide.html' : 'user-guide.' + ISO + '.html';
const SRC = path.resolve(__dirname, file).split(path.sep).join('/');
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
        <span>Amazon Marketplace Pro · ${guide}</span><span>IntelliPresta</span></div>`,
    footerTemplate: `<div style="font-family:'Segoe UI',Arial,sans-serif;font-size:8pt;color:#667077;width:100%;padding:0 18mm;display:flex;justify-content:space-between;">
        <span>${version} 1.6.0</span><span>${pageWord} <span class="pageNumber"></span> ${ofWord} <span class="totalPages"></span></span></div>`,
    margin: { top: '22mm', right: '18mm', bottom: '20mm', left: '18mm' },
  });
  await browser.close();
  console.log('written', OUT);
})().catch((e) => { console.error(e.message); process.exit(1); });
