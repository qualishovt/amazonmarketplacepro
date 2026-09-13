<?php
/**
 * The one cue sheet for promo 1.
 *
 * Both the mix and the subtitle sidecar read it, so a timing change cannot
 * land in the audio and be forgotten in the captions. `scene` gives the second
 * at which each line's scene fades; mix4.php refuses to build if a line would
 * still be talking when its scene goes.
 *
 * This is the cut with the claim scene only - the back-office screenshots and
 * the SP-API version scene were dropped - so the film is 87.4s and everything
 * after the claim sits earlier than it did in the 104s version. The scene
 * table in promo7.html has to agree with the numbers here.
 */
return array(
    'length' => 87.4,
    'cues' => array(
        'l01' => 0.8,   'l02' => 4.4,   'l03' => 7.6,   'l04' => 13.4,
        'l05' => 20.6,  'l06' => 27.3,  'l07' => 34.1,  'l08' => 40.8,
        'l09' => 47.8,
        'l13' => 54.4,                  // sA  the claim, then the grid
        'l10' => 63.3,                  // s5  23 marketplaces
        'l11' => 70.0,                  // s6  built for the rules
        'l12' => 81.3,                  // s7  sign-off
    ),
    'scene' => array(
        'l13' => 62.9, 'l10' => 69.6, 'l11' => 80.9, 'l12' => 87.4,
    ),
);
