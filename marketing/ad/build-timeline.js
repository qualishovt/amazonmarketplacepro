/**
 * Turn a measured voiceover into one timeline shared by the animation, the
 * burned-in subtitles and the audio mix.
 *
 *   node build-timeline.js promo1
 *   node build-timeline.js promo2
 *
 * Durations are measured from the audio itself rather than taken from any
 * script, because a line can be re-recorded without the script being updated -
 * which had already happened to promo1's line 11.
 *
 * Writes, per promo:
 *   timeline<N>.js    - window.TL for the scene file
 *   timeline<N>.json  - the same, for the PHP mixer
 *   <name>.srt        - the subtitles as a sidecar
 *
 * Long lines are split into subtitle chunks at sentence boundaries, and a
 * sentence too long for one line is split again at its commas, so no subtitle
 * ever wraps past two lines. Chunk timing is proportional to character count,
 * which tracks the voice closely enough at this length.
 */
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const MAX = 72;   // characters per subtitle chunk

const PROMOS = {
  promo1: {
    dir: 'vo',
    out: { js: 'timeline1.js', json: 'timeline1.json', srt: 'amazon-marketplace-pro-promo.srt' },
    tail: 1.2,
    lines: [
      ['l01', 0.8,  'Your products live in PrestaShop.'],
      ['l02', 4.4,  'Your buyers are on Amazon.'],
      ['l03', 7.6,  'Keeping the two in step, by hand, is a job nobody wants.'],
      ['l04', 13.4, 'Stock sells twice. Prices drift. Orders get missed.'],
      ['l05', 20.6, 'Amazon Marketplace Pro connects them, inside the back office you already use.'],
      ['l06', 27.3, 'Publish listings in bulk, with images, attributes and categories.'],
      ['l07', 34.1, 'Stock and prices stay current on their own, and repricing keeps you competitive.'],
      ['l08', 40.8, 'Amazon orders arrive as real PrestaShop orders, with the customer, the address, and the invoice.'],
      ['l09', 47.8, 'Confirm shipments, handle returns, and watch FBA stock, without leaving your shop.'],
      ['l10', 54.4, 'Twenty three marketplaces. One shop to run them from.'],
      // Re-recorded on 2 September 2026; the 30-day retention sentence was cut.
      ['l11', 61.0, 'Connected through official Amazon authorisation. Your data stays on your server, and nothing is shared with any third party.'],
      ['l12', 72.4, 'Amazon Marketplace Pro. On PrestaShop Addons.'],
    ],
  },
  promo2: {
    dir: 'vo2',
    out: { js: 'timeline.js', json: 'timeline.json', srt: 'amazon-marketplace-pro-promo-2.srt' },
    tail: 1.6,
    lines: [
      ['l01', 0.9,  'One product. One day. Here is what Amazon Marketplace Pro does with it.'],
      ['l02', 7.7,  "Eight o'clock. You add it to PrestaShop, and it is published to Amazon. Images, attributes and category, all matched."],
      ['l03', 17.5, 'Half past ten. A competitor cuts their price. Repricing answers within the limits you set, so the Buy Box stays yours.'],
      ['l04', 27.3, 'Quarter past twelve. A buyer in Berlin orders. It lands in PrestaShop as a real order. Customer, address and invoice.'],
      ['l05', 37.5, 'Stock drops on both sides at once, so it can never sell twice.'],
      ['l06', 42.8, "Three o'clock. You ship it. Confirm the tracking in your shop, and Amazon and the buyer are told."],
      ['l07', 50.4, 'Selling through FBA too? That stock is watched from the same screen.'],
      ['l08', 56.4, 'Now do that across twenty-three marketplaces, a thousand times a day, without lifting a finger.'],
      ['l09', 63.5, 'Connected through official Amazon authorisation. Your data stays in your shop.'],
      ['l10', 70.4, 'Amazon Marketplace Pro. On PrestaShop Addons.'],
    ],
  },
};

const which = process.argv[2];
const P = PROMOS[which];
if (!P) {
  console.error('usage: node build-timeline.js ' + Object.keys(PROMOS).join('|'));
  process.exit(1);
}

function duration(file) {
  const out = execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration',
    '-of', 'default=nw=1:nk=1', file]).toString().trim();
  return Math.round(parseFloat(out) * 100) / 100;
}

function sentences(text) {
  return text.match(/[^.?!]+[.?!]+/g).map(s => s.trim());
}

function pieces(text) {
  const out = [];
  for (const s of sentences(text)) {
    if (s.length <= MAX) { out.push(s); continue; }
    // too long for one line: break at commas, keeping the comma
    let cur = '';
    for (const p of s.split(/(?<=,)\s+/)) {
      if (cur && (cur + ' ' + p).length > MAX) { out.push(cur); cur = p; }
      else cur = cur ? cur + ' ' + p : p;
    }
    if (cur) out.push(cur);
  }
  return out;
}

function chunk(text) {
  const out = [];
  let cur = '';
  for (const s of pieces(text)) {
    if (cur && (cur + ' ' + s).length > MAX) { out.push(cur); cur = s; }
    else cur = cur ? cur + ' ' + s : s;
  }
  if (cur) out.push(cur);
  return out;
}

const TL = { lines: [], length: 0 };
for (const [id, start, text] of P.lines) {
  const audio = path.join(P.dir, id, 'audio.mp3');
  if (!fs.existsSync(audio)) { console.error('missing ' + audio); process.exit(1); }
  const dur = duration(audio);
  const parts = chunk(text);
  const total = parts.reduce((a, p) => a + p.length, 0);
  let at = start;
  const chunks = parts.map(p => {
    const d = dur * (p.length / total);
    const c = { text: p, start: Math.round(at * 100) / 100, end: Math.round((at + d) * 100) / 100 };
    at += d;
    return c;
  });
  TL.lines.push({ id, text, start, end: Math.round((start + dur) * 100) / 100, chunks });
}
const last = TL.lines[TL.lines.length - 1];
TL.length = Math.round((last.end + P.tail) * 10) / 10;

fs.writeFileSync(P.out.js, 'window.TL = ' + JSON.stringify(TL, null, 1) + ';\n');
fs.writeFileSync(P.out.json, JSON.stringify(TL, null, 1));

function ts(s) {
  const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
  return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${sec.toFixed(3).padStart(6,'0').replace('.', ',')}`;
}
let srt = '', n = 1;
for (const l of TL.lines) for (const c of l.chunks) {
  srt += `${n++}\n${ts(c.start)} --> ${ts(c.end)}\n${c.text}\n\n`;
}
fs.writeFileSync(P.out.srt, srt);

let prev = null;
for (const l of TL.lines) {
  const gap = prev ? (l.start - prev.end).toFixed(2) : '   -';
  if (prev && l.start < prev.end) console.error(`OVERLAP: ${prev.id} ends ${prev.end} but ${l.id} starts ${l.start}`);
  console.log(`${l.id}  ${l.start.toFixed(2).padStart(6)} - ${l.end.toFixed(2).padStart(6)}  gap ${gap}  chunks ${l.chunks.length}`);
  prev = l;
}
console.log(`\n${which}: ${TL.length}s, ${n - 1} subtitle cues`);
