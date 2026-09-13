// Back-office screenshots for the PrestaShop Addons listing, taken on p915 (PS 9.1.5).
//
// Opens a visible Chrome window on the admin login page. Log in there yourself;
// the script waits until the dashboard appears, then walks the module's tabs and
// saves one PNG per screen into the marketing folder. Nothing is typed by the
// script except tab clicks.
const puppeteer = require('puppeteer');
const path = require('path');
const fs = require('fs');

const ADMIN = 'http://localhost/p915/admin110wiuj69igtdact7ng/';
const OUT = 'C:/Users/tehra/Downloads/AmazonMarketplacePro-marketing';
const W = 1440, H = 900;

const SCREENS = [
  ['01-settings-connection', '#grp-settings', '#set-connection'],
  ['02-settings-listings',   '#grp-settings', '#set-listings'],
  ['03-settings-sync',       '#grp-settings', '#set-sync'],
  ['04-settings-orders',     '#grp-settings', '#set-orders'],
  ['05-catalog-products',    '#grp-catalog',  '#tab-products'],
  ['06-catalog-profiles',    '#grp-catalog',  '#tab-profiles'],
  ['07-catalog-markup',      '#grp-catalog',  '#tab-markup'],
  ['08-catalog-rules',       '#grp-catalog',  '#tab-prodrules'],
  ['09-orders',              '#grp-orders',   '#tab-orders'],
  ['10-fulfilment-fba',      '#grp-fulfilment', '#tab-fba'],
  ['11-money-repricing',     '#grp-money',    '#tab-repricing'],
  ['12-money-fees',          '#grp-money',    '#tab-fees'],
  ['13-insights-reports',    '#grp-insights', '#tab-reports'],
  ['14-system-automation',   '#grp-system',   '#tab-cron'],
];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

(async () => {
  const browser = await puppeteer.launch({
    headless: false,
    defaultViewport: { width: W, height: H, deviceScaleFactor: 2 },
    args: [`--window-size=${W + 16},${H + 120}`],
    userDataDir: path.join(__dirname, 'addons-profile'),
  });
  const page = (await browser.pages())[0];
  await page.goto(ADMIN, { waitUntil: 'domcontentloaded' });
  console.log('Log in in the Chrome window that just opened. Waiting...');

  // Logged in = an admin page that is not the login form.
  for (;;) {
    await sleep(1500);
    const url = page.url();
    const hasLoginForm = await page.$('form#login_form, input[name="email"][type="email"]').catch(() => null);
    if (url.includes('/admin110wiuj69igtdact7ng') && !hasLoginForm) break;
  }
  console.log('Logged in.');

  // The configure page refuses a bare URL; accept the "I understand the risk" link.
  await page.goto(ADMIN + 'improve/modules/manage/action/configure/amazonmarketplacepro', { waitUntil: 'networkidle2' });
  const risk = await page.$('a[href*="configure/amazonmarketplacepro?_token"]');
  if (risk) {
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle2' }), risk.click()]);
  }
  await page.waitForSelector('#mkpro-tabs', { timeout: 30000 });

  await page.evaluate(() => {
    // Collapse the PrestaShop sidebar so the module gets the width.
    document.body.classList.add('page-sidebar-closed');
    // Nothing real leaves the shop: the seller ID, the cron token and the
    // localhost address are replaced with placeholders before capture.
    const swap = (from, to) => {
      const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
      let n; while ((n = w.nextNode())) { if (n.data.includes(from)) n.data = n.data.split(from).join(to); }
    };
    swap('ADNM9OJSM9GI8', 'A1EXAMPLE7SELLER');
    swap('f810bba6156a797b1a26c784', '3f9c1e7a2b4d8e6f5a1c0d9b');
    swap('http://localhost/p915/', 'https://www.my-shop.example/');
  });
  await sleep(700);

  for (const [name, grp, sub] of SCREENS) {
    await page.evaluate((grp, sub) => {
      const g = document.querySelector('#mkpro-tabs a[href="' + grp + '"]');
      if (g) g.click();
      const s = document.querySelector('a[href="' + sub + '"]');
      if (s) s.click();
      window.scrollTo(0, 0);
    }, grp, sub);
    await sleep(700);
    const file = path.join(OUT, `addons-shot-${name}.png`);
    await page.screenshot({ path: file, type: 'png' });
    console.log('saved', file);
  }

  // 15: the task table on its own.
  await page.evaluate(() => {
    const h = [...document.querySelectorAll('#tab-cron .panel-heading')].find((e) => /Scheduled tasks/.test(e.textContent));
    if (h) h.closest('.panel').scrollIntoView({ block: 'start' });
    window.scrollBy(0, -135);
  });
  await sleep(500);
  const f15 = path.join(OUT, 'addons-shot-15-system-schedule-table.png');
  await page.screenshot({ path: f15, type: 'png' });
  console.log('saved', f15);
  await browser.close();
})().catch((e) => { console.error(e); process.exit(1); });
