# Localization

Deliverables go to `Downloads/AmazonMarketplacePro-marketing/<iso>/`. English
stays in the parent folder. Nothing in `marketing/` ships with the module
(`export-ignore`).

| | fr | es | it | pl | de |
| --- | --- | --- | --- | --- | --- |
| ad video, spoken and on screen (`amazon-marketplace-pro-promo-<iso>.mp4` + `.srt`) | ☑ | ☑ | ☑ | ☑ | ☑ |
| YouTube thumbnail (`youtube-thumbnail.png`) | ☑ | ☑ | ☑ | ☑ | ☑ |
| listing images (`listing-images/addons-0N-*.png`) | ☑ | ☑ | ☑ | ☑ | ☑ |
| back-office screenshots (`addons-shot-*.png`) | ☑ | ☑ | ☑ | ☑ | ☑ |
| user guide PDF (`Amazon-Marketplace-Pro-Guide-<iso>.pdf`) | ☑ | ☑ | ☑ | ☑ | ☑ |

## How each piece is built

**One rule for every page:** the English file is the source and is never
edited for a language. `i18n-apply.js <source> <map.json> <out>` makes the
translated copy from exact string pairs, and fails if an English string it
expects has changed.

**Ad** (`ad/`). The picture keeps the English timing (`cues.php`, the scene
table in `promo7.html`), so a translation has to fit each line's slot.

1. `locale/lines.<iso>.json` — the script. `node locale/synth-fit.js <iso>`
   speaks it with the locale's neural voice (Henri, Álvaro, Giuseppe, Marek,
   Conrad) and speeds a line up in 4% steps, at most 14%, until the voice ends
   0.3 s before the next cue. A line that still does not fit is reported;
   shorten the words rather than raising the limit.
2. `locale/screen.<iso>.json` — the on-screen text.
   `i18n-apply.js promo7.html locale/screen.<iso>.json promo7.<iso>.html`,
   then `node probe-locale.js <iso>` for a contact sheet of every scene.
3. `node render.js promo7.<iso>.html 87.4 frames-<iso>` (about 5 minutes).
4. `php mix-locale.php <iso> <outDir>` — mixes the voice at the English voice's
   measured loudness over the same music bed, encodes, writes the `.srt`.

**Listing images** (`cards/`). `locale/cards.<iso>.json`, then
`node render-cards.js out-<iso> cards.<iso>.html`. The map also carries a pass
that widens a pill or chip to a longer translated label.

**Thumbnail** (`thumbnail/`). `node locale/make-maps.js` writes the maps from
one table; `node render-thumb.js out-<iso>.png thumb.<iso>.html`.

**Screenshots** (`shots/`). Taken from the p915 back office in each language
(the employee's language switched between passes), through `snap-page.js` in
the logged-in browser and `snap-render.js out-<iso> <names>`. The same set is
the guide's `docs/user-guide/img/<iso>/`.

**User guide** (`docs/user-guide/user-guide.<iso>.html`). Translated from the
English guide with the same tag sequence; task names, badges and quoted
messages are the module's own translations. `node render.js <out.pdf> <iso>`
prints it with the header and footer in that language.

## Notes

- Register follows the eBay ad for spoken and marketing text (fr vous; es, it,
  pl and de informal) and the module's own strings for the guide (fr and de
  formal; es, it and pl informal).
- Brand marks are artwork, never translated. "Seller Central", report names
  and menu paths stay in English, as in the module.
- The English `.srt` caption for line 11 was corrected on 13 Sep 2026: the
  audio says "…and nothing is shared with any third party", the caption still
  carried the older thirty-day sentence.
