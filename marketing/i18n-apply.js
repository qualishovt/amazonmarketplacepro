/**
 * Build a translated copy of a page from an ordered list of exact replacements.
 *
 *   node i18n-apply.js <source.html> <map.json> <out.html>
 *
 * map.json is { "pairs": [[from, to], ...], "css": "optional extra rules" }.
 *
 * The English pages stay the source of truth and are never edited for a
 * language. Every `from` must occur in the source, so an English string that
 * changes upstream fails the build instead of quietly staying English in one
 * locale. Pairs apply in order: put a string before any shorter string it
 * contains, and delimit short JS strings with their quotes ('Orders').
 *
 * Translations use typographic apostrophes (’) so they never close the
 * single-quoted JavaScript strings most of them sit in.
 */
const fs = require('fs');

const [, , src, mapFile, out] = process.argv;
if (!src || !mapFile || !out) {
  console.error('usage: node i18n-apply.js <source.html> <map.json> <out.html>');
  process.exit(1);
}

let s = fs.readFileSync(src, 'utf8');
const map = JSON.parse(fs.readFileSync(mapFile, 'utf8'));
const missing = [];
let applied = 0;

for (const [from, to] of map.pairs) {
  const n = s.split(from).length - 1;
  if (n === 0) { missing.push(from); continue; }
  if (/(^|[^\\])'/.test(to.replace(/^'|'$/g, '')) && /^'.*'$/.test(from)) {
    missing.push('unescaped apostrophe in translation of ' + from);
    continue;
  }
  s = s.split(from).join(to);
  applied += n;
}

if (map.css) {
  if (!s.includes('</style>')) { missing.push('</style> (for css)'); }
  s = s.replace('</style>', map.css + '\n</style>');
}

if (missing.length) {
  console.error('not found in ' + src + ':\n  ' + missing.join('\n  '));
  process.exit(1);
}
fs.writeFileSync(out, s);
console.log(`${out}: ${map.pairs.length} strings, ${applied} replacements`);
