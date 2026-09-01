<?php
/**
 * Round the corners of a square PNG, writing a new file.
 *
 * The mask is built at 8x and downsampled, so the curve is antialiased rather
 * than stair-stepped - at 57px a hard-edged mask is very visible.
 */

function round_corners($srcFile, $dstFile, $radiusRatio = 0.16, $ss = 8)
{
    $src = imagecreatefrompng($srcFile);
    $size = imagesx($src);
    $W = $size * $ss;
    $r = $radiusRatio * $W;

    $big = imagecreatetruecolor($W, $W);
    imagealphablending($big, false);
    imagesavealpha($big, true);
    imagecopyresampled($big, $src, 0, 0, 0, 0, $W, $W, $size, imagesy($src));

    // Punch out everything beyond the rounded rectangle.
    $clear = imagecolorallocatealpha($big, 0, 0, 0, 127);
    for ($y = 0; $y < $W; $y++) {
        for ($x = 0; $x < $W; $x++) {
            // Distance from the nearest corner centre, only inside the corner boxes.
            $cx = null;
            if ($x < $r && $y < $r) { $cx = $r; $cy = $r; }
            elseif ($x >= $W - $r && $y < $r) { $cx = $W - $r; $cy = $r; }
            elseif ($x < $r && $y >= $W - $r) { $cx = $r; $cy = $W - $r; }
            elseif ($x >= $W - $r && $y >= $W - $r) { $cx = $W - $r; $cy = $W - $r; }

            if ($cx !== null) {
                $dx = $x + 0.5 - $cx;
                $dy = $y + 0.5 - $cy;
                if (($dx * $dx + $dy * $dy) > $r * $r) {
                    imagesetpixel($big, $x, $y, $clear);
                }
            }
        }
    }

    $out = imagecreatetruecolor($size, $size);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
    imagecopyresampled($out, $big, 0, 0, 0, 0, $size, $size, $W, $W);
    imagepng($out, $dstFile);

    imagedestroy($src);
    imagedestroy($big);
    imagedestroy($out);

    printf("%s  %dx%d  radius %.1fpx (%.0f%%)\n",
        $dstFile, $size, $size, $radiusRatio * $size, $radiusRatio * 100);
}

$mod = 'C:/xampp/7.0.33/htdocs/p16124/modules/amazonmarketplacepro/';
$dir = dirname(__FILE__) . '/';

// Three strengths to compare before committing to one.
round_corners($mod . 'logo.png', $dir . 'r-subtle.png', 0.12);
round_corners($mod . 'logo.png', $dir . 'r-medium.png', 0.16);
round_corners($mod . 'logo.png', $dir . 'r-strong.png', 0.22);
