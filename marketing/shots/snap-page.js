/**
 * Runs inside the logged-in back office (paste into the console, or inject).
 * Opens one module tab, makes the page ready for a public screenshot and posts
 * a static copy of it to the receiver next to the shop.
 *
 *   await mkproSnap('02-settings-listings', '#grp-settings', '#set-listings', 'Amazon.de')
 *
 * Nothing real leaves the shop: the seller ID, the cron token and the shop
 * address are swapped for placeholders, the result boxes are emptied, and
 * the debug toolbar is removed.
 */
window.mkproSnap = async function (name, group, sub, marketplace, token) {
  const g = document.querySelector('#mkpro-tabs a[href="' + group + '"]');
  if (g) g.click();
  const s = sub ? document.querySelector('a[href="' + sub + '"]') : null;
  if (s) s.click();
  window.scrollTo(0, 0);
  await new Promise(r => setTimeout(r, 500));

  const clone = document.documentElement.cloneNode(true);
  clone.querySelectorAll('script, noscript, iframe, .sf-toolbar, .sf-minitoolbar, [id^="sfwdt"], [id^="sfToolbar"]').forEach(e => e.remove());
  // The module gets the width: the PrestaShop menu is collapsed, as in the English set.
  clone.querySelector('body').classList.add('page-sidebar-closed');
  const res = clone.querySelector('#amazon-connection-result');
  if (res) { res.setAttribute('style', 'display:none'); res.textContent = ''; }
  if (marketplace) {
    const sel = clone.querySelector('select[name="mkpro_marketplace_id"]');
    if (sel) {
      [...sel.options].forEach(o => o.removeAttribute('selected'));
      const pick = [...sel.options].find(o => o.textContent.indexOf(marketplace) === 0);
      if (pick) pick.setAttribute('selected', 'selected');
    }
  }
  const head = clone.querySelector('head');
  const base = document.createElement('base');
  base.href = location.href;
  head.insertBefore(base, head.firstChild);

  let html = '<!DOCTYPE html>\n' + clone.outerHTML;
  [['ADNM9OJSM9GI8', 'A1EXAMPLE7SELLER'],
   ['f810bba6156a797b1a26c784', '3f9c1e7a2b4d8e6f5a1c0d9b'],
   ['http://localhost/p915/', 'https://www.my-shop.example/']].forEach(([a, b]) => { html = html.split(a).join(b); });
  // the <base> must keep pointing at the real admin for CSS and fonts
  html = html.replace('<base href="https://www.my-shop.example/', '<base href="http://localhost/p915/');

  const r = await fetch('/p915/amz-snap-recv.php?t=' + token + '&name=' + encodeURIComponent(name), { method: 'POST', body: html });
  return name + ': ' + r.status + ' ' + (await r.text());
};
