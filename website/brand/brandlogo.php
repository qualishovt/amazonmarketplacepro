<?php
/**
 * Build square Appstore logos from the horizontal IntelliPresta lockup.
 *
 * The source is 3:1 (mark on the left, wordmark on the right). Squashing that
 * into a square wastes most of the canvas, so the two elements are detected
 * and restacked: mark on top, wordmark underneath.
 */

define('SRC', 'C:/Users/tehra/Downloads/IntelliPresta_logo.png');

/** True when the pixel is visibly darker than the white page. */
function isInk($im, $x, $y)
{
    $c = imagecolorat($im, $x, $y);
    if ((($c >> 24) & 0x7F) > 100) {
        return false;                       // effectively transparent
    }
    return ((($c >> 16) & 255) < 235) || ((($c >> 8) & 255) < 235) || (($c & 255) < 235);
}

/** Tight bounding box of ink inside [x0,x1]. */
function bbox($im, $w, $h, $x0, $x1)
{
    $minX = $x1; $maxX = $x0; $minY = $h; $maxY = 0;
    for ($x = $x0; $x <= $x1; $x++) {
        for ($y = 0; $y < $h; $y++) {
            if (isInk($im, $x, $y)) {
                if ($x < $minX) { $minX = $x; }
                if ($x > $maxX) { $maxX = $x; }
                if ($y < $minY) { $minY = $y; }
                if ($y > $maxY) { $maxY = $y; }
            }
        }
    }
    return array($minX, $minY, $maxX - $minX + 1, $maxY - $minY + 1);
}

$src = imagecreatefrompng(SRC);
$W = imagesx($src);
$H = imagesy($src);

// Column ink profile -> find the blank gutter separating mark from wordmark.
$cols = array();
for ($x = 0; $x < $W; $x++) {
    $n = 0;
    for ($y = 0; $y < $H; $y += 2) {
        if (isInk($src, $x, $y)) { $n++; }
    }
    $cols[$x] = $n;
}

// Walk right from the mark until a sustained empty run appears.
$run = 0; $gapStart = null; $gapEnd = null;
$seenInk = false;
for ($x = 0; $x < $W; $x++) {
    if ($cols[$x] > 0) {
        $seenInk = true;
        if ($run > 60 && $gapStart !== null) { $gapEnd = $x; break; }
        $run = 0; $gapStart = null;
    } elseif ($seenInk) {
        if ($run === 0) { $gapStart = $x; }
        $run++;
    }
}

echo "gutter: $gapStart .. $gapEnd\n";
$split = (int) (($gapStart + $gapEnd) / 2);

$mark = bbox($src, $W, $H, 0, $split);
$word = bbox($src, $W, $H, $split, $W - 1);
echo "mark:  x={$mark[0]} y={$mark[1]} w={$mark[2]} h={$mark[3]}\n";
echo "word:  x={$word[0]} y={$word[1]} w={$word[2]} h={$word[3]}\n";

function build($size, $out, $src, $mark, $word)
{
    $ss = 3;
    $S = $size * $ss;
    $im = imagecreatetruecolor($S, $S);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, $S, $S, $white);

    $pad = 0.085 * $S;
    $inner = $S - 2 * $pad;
    $gap = 0.055 * $S;
    $usable = $inner - $gap;

    // Mark gets the larger share; the wordmark only needs to stay legible.
    $markScale = min(($usable * 0.60) / $mark[3], $inner / $mark[2]);
    $wordScale = min(($usable * 0.40) / $word[3], $inner / $word[2]);

    $mw = $mark[2] * $markScale; $mh = $mark[3] * $markScale;
    $ww = $word[2] * $wordScale; $wh = $word[3] * $wordScale;

    $totalH = $mh + $gap + $wh;
    $top = ($S - $totalH) / 2;

    imagecopyresampled($im, $src,
        (int) round(($S - $mw) / 2), (int) round($top),
        $mark[0], $mark[1],
        (int) round($mw), (int) round($mh), $mark[2], $mark[3]);

    imagecopyresampled($im, $src,
        (int) round(($S - $ww) / 2), (int) round($top + $mh + $gap),
        $word[0], $word[1],
        (int) round($ww), (int) round($wh), $word[2], $word[3]);

    $final = imagecreatetruecolor($size, $size);
    imagecopyresampled($final, $im, 0, 0, 0, 0, $size, $size, $S, $S);
    imagepng($final, $out);
    echo "wrote $out ($size x $size)\n";
}

$dir = dirname(__FILE__);
build(300, $dir . '/ip-300.png', $src, $mark, $word);
build(220, $dir . '/ip-220.png', $src, $mark, $word);
