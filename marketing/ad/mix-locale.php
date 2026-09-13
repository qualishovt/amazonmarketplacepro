<?php
/**
 * Mix and encode a translated cut, and write its subtitle sidecar.
 *
 *   php mix-locale.php <iso> [outDir]
 *
 * Same picture timing and the same audio treatment as mix4.php - the voice
 * placed at each cue, the music bed at one fixed level with a fade at each
 * end - with one addition: the five voices do not speak at the same level
 * as Ryan, so each locale's voice track is measured (EBU R128 integrated
 * loudness) and brought to the English voice track's loudness before the
 * mix. The listener hears the same balance of voice and music in every
 * language.
 *
 * Needs frames-<iso>/ (render.js promo7.<iso>.html), vo-<iso>/ and
 * locale/fit.<iso>.json (locale/synth-fit.js).
 */
$iso = isset($argv[1]) ? $argv[1] : '';
if (!preg_match('/^(fr|es|it|pl|de)$/', $iso)) {
    fwrite(STDERR, "usage: php mix-locale.php <fr|es|it|pl|de> [outDir]\n");
    exit(1);
}
$here = __DIR__;
$outDir = isset($argv[2]) ? rtrim($argv[2], '/\\') : $here;
$music = 'C:/Users/tehra/Downloads/Background Music for Presentation.mp3';
$cfg = require $here . '/cues.php';
$length = $cfg['length'];
$fit = json_decode(file_get_contents("$here/locale/fit.$iso.json"), true);
$lines = json_decode(file_get_contents("$here/locale/lines.$iso.json"), true);
$frames = "$here/frames-$iso";

if (count(glob("$frames/*.png")) < (int) floor($length * 30)) {
    fwrite(STDERR, "frames-$iso is incomplete\n");
    exit(1);
}
foreach ($cfg['cues'] as $id => $start) {
    if (!is_file("$here/vo-$iso/$id/audio.mp3") || empty($fit[$id]['ok'])) {
        fwrite(STDERR, "$id: missing take or it does not fit (run locale/synth-fit.js $iso $id)\n");
        exit(1);
    }
}

function run($cmd)
{
    $parts = array();
    foreach ($cmd as $p) {
        $parts[] = (strpbrk($p, " ;[]|,=") !== false) ? '"' . $p . '"' : $p;
    }
    return shell_exec(implode(' ', $parts) . ' 2>&1');
}

/** The voice-only track of one set of takes, as it sits in the mix. */
function voiceTrack($dir, $cues, $length, $wav)
{
    $inputs = array();
    $f = array();
    $l = array();
    $i = 0;
    foreach ($cues as $id => $start) {
        $inputs[] = '-i';
        $inputs[] = "$dir/$id/audio.mp3";
        $ms = (int) round($start * 1000);
        $f[] = "[$i:a]adelay=$ms|$ms,volume=1.7[v$i]";
        $l[] = "[v$i]";
        $i++;
    }
    $f[] = implode('', $l) . 'amix=inputs=' . count($l) . ':normalize=0[vo]';
    run(array_merge(array('ffmpeg', '-v', 'error', '-y'), $inputs,
        array('-filter_complex', implode(';', $f), '-map', '[vo]', '-t', (string) $length, '-ar', '48000', $wav)));
}

function loudness($wav)
{
    $log = run(array('ffmpeg', '-hide_banner', '-nostats', '-i', $wav, '-af', 'ebur128', '-f', 'null', '-'));
    if (!preg_match_all('/I:\s+(-?[\d.]+) LUFS/', $log, $m)) {
        fwrite(STDERR, "cannot measure $wav\n");
        exit(1);
    }
    return (float) end($m[1]);
}

$tmp = sys_get_temp_dir();
voiceTrack("$here/vo", $cfg['cues'], $length, "$tmp/amz-vo-en.wav");
voiceTrack("$here/vo-$iso", $cfg['cues'], $length, "$tmp/amz-vo-$iso.wav");
$en = loudness("$tmp/amz-vo-en.wav");
$loc = loudness("$tmp/amz-vo-$iso.wav");
$gain = pow(10, ($en - $loc) / 20);
printf("voice loudness: en %.1f LUFS, %s %.1f LUFS -> gain x%.3f\n", $en, $iso, $loc, $gain);
unlink("$tmp/amz-vo-en.wav");
unlink("$tmp/amz-vo-$iso.wav");

$inputs = array('-framerate', '30', '-i', "$frames/%05d.png");
$filters = array();
$labels = array();
$i = 1;
$vol = sprintf('%.4f', 1.7 * $gain);
foreach ($cfg['cues'] as $id => $start) {
    $inputs[] = '-i';
    $inputs[] = "$here/vo-$iso/$id/audio.mp3";
    $ms = (int) round($start * 1000);
    $filters[] = "[$i:a]adelay=$ms|$ms,volume={$vol}[v$i]";
    $labels[] = "[v$i]";
    $i++;
}
$inputs[] = '-i';
$inputs[] = $music;
$filters[] = implode('', $labels) . 'amix=inputs=' . count($labels) . ':normalize=0[vo]';
$fadeOut = $length - 3;
$filters[] = "[$i:a]atrim=0:$length,afade=t=in:st=0:d=1.5,afade=t=out:st=$fadeOut:d=3,volume=0.175[mus]";
$filters[] = '[vo][mus]amix=inputs=2:normalize=0,alimiter=limit=0.95[aout]';

$out = "$outDir/amazon-marketplace-pro-promo-$iso.mp4";
echo run(array_merge(array('ffmpeg', '-v', 'error', '-y'), $inputs, array(
    '-filter_complex', implode(';', $filters),
    '-map', '0:v', '-map', '[aout]',
    '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-crf', '19', '-preset', 'slow',
    '-r', '30', '-movflags', '+faststart',
    '-c:a', 'aac', '-b:a', '192k', '-t', (string) $length,
    $out,
)));
if (!is_file($out)) {
    fwrite(STDERR, "encode failed\n");
    exit(1);
}

// Subtitles: each caption runs from its cue to the end of the voice.
function stamp($sec)
{
    $h = (int) ($sec / 3600);
    $m = (int) (($sec - $h * 3600) / 60);
    return str_replace('.', ',', sprintf('%02d:%02d:%06.3f', $h, $m, $sec - $h * 3600 - $m * 60));
}
$text = array();
foreach ($lines as $l) {
    $text[$l['id']] = isset($l['caption']) ? $l['caption'] : $l['text'];
}
$srt = array();
$n = 0;
foreach ($cfg['cues'] as $id => $start) {
    $n++;
    $srt[] = $n . "\n" . stamp($start) . ' --> ' . stamp($start + $fit[$id]['speech']) . "\n" . $text[$id] . "\n";
}
file_put_contents("$outDir/amazon-marketplace-pro-promo-$iso.srt", implode("\n", $srt));
printf("wrote %s (%.1f MB) and its .srt\n", $out, filesize($out) / 1048576);
