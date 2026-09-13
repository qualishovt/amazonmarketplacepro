/**
 * Adds the "grow the pill to its label" pass to every cards.<iso>.json.
 *
 * The pills and chips in art2.js are drawn at the width the English words
 * need. A translated label can be longer, so after a card's art is drawn the
 * localized page measures each label and widens its shape, keeping it
 * centred. English cards never run this, so they stay exactly as they were.
 *
 *   node locale/fit-pair.js
 */
const fs = require('fs');
const path = require('path');

const FROM = "  $('art').innerHTML = c.art();";
const TO = FROM + `
  // A translated label can set wider than the pill or chip drawn for the
  // English words: grow the shape around the text and keep it centred.
  $('art').querySelectorAll('g').forEach(g => {
    const r = g.querySelector(':scope > rect'), t = g.querySelector(':scope > text');
    if (!r || !t) return;
    const x = +r.getAttribute('x'), w = +r.getAttribute('width'), b = t.getBBox();
    if (t.getAttribute('text-anchor') === 'middle') {
      const need = b.width + 48;
      if (need <= w) return;
      r.setAttribute('x', (x + w / 2 - need / 2).toFixed(1));
      r.setAttribute('width', need.toFixed(1));
    } else {
      const need = b.x + b.width + 28 - x;
      if (need <= w) return;
      const dx = -(need - w) / 2;
      r.setAttribute('x', (x + dx).toFixed(1));
      r.setAttribute('width', need.toFixed(1));
      [...g.children].forEach(el => { if (el !== r) el.setAttribute('transform', 'translate(' + dx.toFixed(1) + ',0)'); });
    }
  });`;

for (const iso of ['fr', 'es', 'it', 'pl', 'de']) {
  const file = path.join(__dirname, 'cards.' + iso + '.json');
  const map = JSON.parse(fs.readFileSync(file, 'utf8'));
  map.pairs = map.pairs.filter(p => p[0] !== FROM);
  map.pairs.unshift([FROM, TO]);
  fs.writeFileSync(file, JSON.stringify(map, null, 2) + '\n');
  console.log(iso, map.pairs.length, 'pairs');
}
