<?php
/**
 * Rebuild the subtitle sidecar from the same cue sheet the mix uses, so the
 * captions cannot drift away from the audio.
 *
 * The captions are a sidecar rather than burned in: the merchant can switch
 * them on where the player supports it, and the picture stays clean.
 */
$cfg = require __DIR__ . '/cues.php';
$lines = json_decode(file_get_contents(__DIR__ . '/vo/lines.json'), true);
if (!$lines) {
    fwrite(STDERR, "cannot read vo/lines.json\n");
    exit(1);
}
$text = array();
foreach ($lines as $l) {
    // A line may carry a `caption`: the spoken form needs "F.B.A." and
    // "one point six", the written form wants "FBA" and "1.6".
    $text[$l['id']] = isset($l['caption']) ? $l['caption'] : $l['text'];
}

function stamp($sec)
{
    $h = (int) ($sec / 3600);
    $m = (int) (($sec - $h * 3600) / 60);
    $s = $sec - $h * 3600 - $m * 60;

    return sprintf('%02d:%02d:%06.3f', $h, $m, $s);
}

$out = array();
$n = 0;
foreach ($cfg['cues'] as $id => $start) {
    if (!isset($text[$id])) {
        fwrite(STDERR, "no text for $id in vo/lines.json\n");
        exit(1);
    }
    $path = __DIR__ . '/vo/' . $id . '/audio.mp3';
    $dur = (float) trim(shell_exec(
        'ffprobe -v error -show_entries format=duration -of csv=p=0 ' . escapeshellarg($path)
    ));
    if ($dur <= 0) {
        fwrite(STDERR, "cannot measure $path\n");
        exit(1);
    }
    $n++;
    $out[] = $n . "\n"
        . str_replace('.', ',', stamp($start)) . ' --> '
        . str_replace('.', ',', stamp($start + $dur)) . "\n"
        . $text[$id] . "\n";
}

file_put_contents(__DIR__ . '/amazon-marketplace-pro-promo.srt', implode("\n", $out));
echo "wrote amazon-marketplace-pro-promo.srt - $n captions, film "
    . $cfg['length'] . "s\n";
