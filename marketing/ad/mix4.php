<?php
/**
 * Build promo 1 from frames7, with the three proof scenes in place.
 *
 * Same audio treatment as the last cut: the music sits at one fixed level for
 * the whole film rather than ducking under the voice, because ducking made it
 * swell in every pause. 0.175 is the level measurement put at the same
 * loudness the ducked music had while a line was playing.
 *
 * Everything after the feature montage moved later, so the cue sheet below is
 * the authority on timing - the scene table in promo6.html has to agree with
 * it, and each line has to finish before its scene fades.
 */
$music  = 'C:/Users/tehra/Downloads/Background Music for Presentation.mp3';
$frames = 'frames12/%05d.png';
$out    = 'amazon-marketplace-pro-promo.mp4';
$cfg    = require __DIR__ . "/cues.php";
$length = $cfg["length"];

$cues   = $cfg["cues"];
$scenes = $cfg["scene"];

$inputs = array('-framerate', '30', '-i', $frames);
$filters = array();
$labels = array();

$i = 1;                                  // 0 is the frame sequence
foreach ($cues as $id => $start) {
    $path = 'vo/' . $id . '/audio.mp3';
    if (!file_exists($path)) {
        fwrite(STDERR, "missing $path\n");
        exit(1);
    }
    if (isset($scenes[$id])) {
        $dur = (float) trim(shell_exec(
            'ffprobe -v error -show_entries format=duration -of csv=p=0 ' . escapeshellarg($path)
        ));
        $ends = $start + $dur;
        if ($ends > $scenes[$id] - 0.15) {
            fwrite(STDERR, sprintf(
                "%s runs to %.2fs but its scene ends at %.2fs\n", $id, $ends, $scenes[$id]
            ));
            exit(1);
        }
    }
    $inputs[] = '-i';
    $inputs[] = $path;
    $ms = (int) round($start * 1000);
    $filters[] = "[$i:a]adelay=$ms|$ms,volume=1.7[v$i]";
    $labels[] = "[v$i]";
    $i++;
}
$musicIdx = $i;
$inputs[] = '-i';
$inputs[] = $music;

$filters[] = implode('', $labels) . 'amix=inputs=' . count($labels) . ':normalize=0[vo]';
// One level throughout: only the opening and closing fades move it.
$fadeOut = $length - 3;
$filters[] = "[$musicIdx:a]atrim=0:$length,afade=t=in:st=0:d=1.5,afade=t=out:st=$fadeOut:d=3,volume=0.175[mus]";
$filters[] = '[vo][mus]amix=inputs=2:normalize=0,alimiter=limit=0.95[aout]';

$cmd = array('ffmpeg', '-v', 'error', '-y');
$cmd = array_merge($cmd, $inputs);
$cmd[] = '-filter_complex';
$cmd[] = implode(';', $filters);
$cmd = array_merge($cmd, array(
    '-map', '0:v', '-map', '[aout]',
    '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-crf', '19', '-preset', 'slow',
    '-r', '30', '-movflags', '+faststart',
    '-c:a', 'aac', '-b:a', '192k', '-t', (string) $length,
    $out,
));

$escaped = array();
foreach ($cmd as $part) {
    $escaped[] = (strpbrk($part, " ;[]") !== false) ? '"' . $part . '"' : $part;
}
$line = implode(' ', $escaped);
echo $line, "\n\n";
passthru($line, $rc);
exit($rc);
