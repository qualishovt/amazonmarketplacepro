/**
 * Speak a translated script with the locale's neural voice, fitting every
 * line into the slot the English film gives it.
 *
 *   node locale/synth-fit.js <iso>
 *
 * The picture is not re-timed per language: scenes, the feature montage and
 * every animation offset are the English cut's. So each translated line must
 * finish speaking before the next cue (or before its scene fades, for the
 * lines cues.php lists under 'scene'). A line that runs long is re-spoken a
 * little faster, up to MAX_STEP; if it still does not fit it is reported and
 * the words have to get shorter.
 *
 * "Finish speaking" is measured to the end of the voice, not the end of the
 * file: the engines leave most of a second of silence after a sentence, and
 * that tail may run under the next line's cue without anyone hearing it.
 *
 * Writes vo-<iso>/<id>/audio.mp3 and locale/fit.<iso>.json.
 */
const { MsEdgeTTS, OUTPUT_FORMAT } = require('msedge-tts');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const AD = path.resolve(__dirname, '..');
const ISO = process.argv[2];
const ONLY = process.argv[3] ? process.argv[3].split(',') : null;   // re-speak just these ids
const VOICES = { fr: 'fr-FR-HenriNeural', es: 'es-ES-AlvaroNeural', it: 'it-IT-GiuseppeMultilingualNeural',
                 pl: 'pl-PL-MarekNeural', de: 'de-DE-ConradNeural' };
/* Giuseppe reads noticeably slower than the others (see the eBay ad's synth.js),
   so Italian starts from the rate that ad settled on. */
const BASE = { it: 6 };
const STEP = 4, MAX_STEP = 14;
const MARGIN = 0.30;    // seconds of quiet kept before the next cue or the fade
const PHP = 'C:/xampp/8.2.12/php/php.exe';

if (!VOICES[ISO]) { console.error('usage: node locale/synth-fit.js <fr|es|it|pl|de> [ids]'); process.exit(1); }

const cfg = JSON.parse(execFileSync(PHP, ['-r', 'echo json_encode(require "cues.php");'], { cwd: AD, encoding: 'utf8' })
  .replace(/^[\s\S]*?(\{)/, '$1'));
const lines = JSON.parse(fs.readFileSync(path.join(__dirname, 'lines.' + ISO + '.json'), 'utf8'));
const byId = Object.fromEntries(lines.map(l => [l.id, l]));

/* The slot of a line: from its cue to the next cue, or to its scene's fade. */
const order = Object.entries(cfg.cues);
const slotEnd = {};
order.forEach(([id, start], i) => {
  const next = i + 1 < order.length ? order[i + 1][1] : cfg.length;
  slotEnd[id] = cfg.scene && cfg.scene[id] !== undefined ? Math.min(next, cfg.scene[id]) : next;
});

function probe(file) {
  return parseFloat(execFileSync('ffprobe', ['-v', 'error', '-show_entries', 'format=duration',
    '-of', 'csv=p=0', file], { encoding: 'utf8' }).trim());
}
async function speak(text, rate, dir) {
  fs.mkdirSync(dir, { recursive: true });
  const tts = new MsEdgeTTS();
  await tts.setMetadata(VOICES[ISO], OUTPUT_FORMAT.AUDIO_24KHZ_96KBITRATE_MONO_MP3);
  await tts.toFile(dir, text, { rate: (rate >= 0 ? '+' : '') + rate + '%' });
  return path.join(dir, 'audio.mp3');
}

/* The file's length, and where the voice ends: the start of the silence that
   runs to the end of the file (ffmpeg logs silencedetect on stderr). */
function measureEnd(file) {
  const r = require('child_process').spawnSync('ffmpeg', ['-hide_banner', '-nostats', '-i', file,
    '-af', 'silencedetect=noise=-42dB:d=0.15', '-f', 'null', '-'], { encoding: 'utf8' });
  const log = (r.stderr || '') + (r.stdout || '');
  const total = probe(file);
  const starts = [...log.matchAll(/silence_start: ([\d.]+)/g)].map(m => parseFloat(m[1]));
  const ends = [...log.matchAll(/silence_end: ([\d.]+)/g)].map(m => parseFloat(m[1]));
  return { total, end: starts.length > ends.length ? starts[starts.length - 1] : total };
}

(async () => {
  const outDir = path.join(AD, 'vo-' + ISO);
  const fitFile = path.join(__dirname, 'fit.' + ISO + '.json');
  const report = fs.existsSync(fitFile) ? JSON.parse(fs.readFileSync(fitFile, 'utf8')) : {};
  let bad = 0;
  for (const [id, start] of order) {
    if (ONLY && !ONLY.includes(id)) continue;
    const line = byId[id];
    if (!line) { console.error('no ' + id + ' in lines.' + ISO + '.json'); process.exit(1); }
    const allowed = slotEnd[id] - start - MARGIN;
    let rate = BASE[ISO] || 0, m, file;
    for (;;) {
      file = await speak(line.text, rate, path.join(outDir, id));
      m = measureEnd(file);
      if (m.end <= allowed || rate + STEP > MAX_STEP) break;
      rate += STEP;
    }
    const ok = m.end <= allowed;
    if (!ok) bad++;
    report[id] = { rate, speech: +m.end.toFixed(2), file: +m.total.toFixed(2), allowed: +allowed.toFixed(2), ok };
    console.log(`${id}  rate ${String(rate).padStart(3)}%  speech ${m.end.toFixed(2)}s / ${allowed.toFixed(2)}s  ${ok ? 'ok' : 'TOO LONG by ' + (m.end - allowed).toFixed(2) + 's'}`);
  }
  fs.writeFileSync(fitFile, JSON.stringify(report, null, 1));
  console.log(bad ? `\n${bad} line(s) do not fit - shorten them in lines.${ISO}.json and re-run with those ids` : '\nall lines fit');
  process.exit(bad ? 2 : 0);
})().catch(e => { console.error('ERR', e.message); process.exit(1); });
