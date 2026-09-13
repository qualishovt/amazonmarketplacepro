/**
 * Flat-vector illustration library for the promo.
 *
 * Every function returns an SVG fragment centred on (0,0) so it can be
 * positioned with a transform and animated as a unit. Colours come from one
 * palette so the whole film reads as a single piece of artwork.
 */
window.ART = (function () {
  const C = {
    ink:   '#1F2933',
    grey:  '#8B95A1',
    line:  '#DFE3E6',
    pale:  '#EDEFF0',
    white: '#FFFFFF',
    green: '#51954B',
    green2:'#EAF3E9',
    amber: '#FF9900',
    amber2:'#FFE9C7',
    red:   '#E05252',
    red2:  '#FDECEC',
    card:  '#F7F8F8',
    box:   '#E3C08A',
    box2:  '#D2A96E',
    box3:  '#C0955A',
    // Brand colours, used only where a brand name is set as its own wordmark.
    psDark: '#241C4E',   // PrestaShop "Presta"
    psPink: '#DF0067',   // PrestaShop "Shop"
    amzInk: '#232F3E',   // Amazon wordmark
  };

  /**
   * A brand name set the way its owner sets it: PrestaShop's two-tone
   * wordmark, Amazon's lowercase one. Any other label comes back untouched and
   * keeps whatever fill the calling <text> already carries.
   */
  function brand(label) {
    if (label === 'PrestaShop') {
      return `<tspan fill="${C.psDark}">Presta</tspan><tspan fill="${C.psPink}">Shop</tspan>`;
    }
    if (label === 'Amazon') {
      return `<tspan fill="${C.amzInk}">amazon</tspan>`;
    }
    return label;
  }

  /** A parcel, seen head on. */
  function parcel(s) {
    s = s || 1;
    return `<g transform="scale(${s})">
      <rect x="-44" y="-34" width="88" height="68" rx="7" fill="${C.box}"/>
      <rect x="-44" y="-34" width="88" height="20" rx="6" fill="${C.box2}"/>
      <rect x="-9"  y="-34" width="18" height="68" fill="${C.box3}" opacity=".8"/>
      <rect x="-30" y="6"   width="26" height="7" rx="3.5" fill="${C.box3}" opacity=".55"/>
    </g>`;
  }

  /** A desktop monitor on a stand, with a little content inside. */
  function monitor(label, accent) {
    return `<g>
      <rect x="-150" y="-110" width="300" height="200" rx="14" fill="${C.white}" stroke="${C.line}" stroke-width="3"/>
      <rect x="-150" y="-110" width="300" height="34" rx="14" fill="${C.pale}"/>
      <circle cx="-130" cy="-93" r="5" fill="${C.line}"/>
      <circle cx="-113" cy="-93" r="5" fill="${C.line}"/>
      <circle cx="-96"  cy="-93" r="5" fill="${C.line}"/>
      <text x="0" y="-86" text-anchor="middle" font-family="Segoe UI,sans-serif"
            font-size="17" font-weight="700" fill="${C.grey}">${brand(label)}</text>
      <g>
        <rect x="-120" y="-56" width="34" height="34" rx="9" fill="${accent}" opacity=".2"/>
        <rect x="-74"  y="-46" width="150" height="12" rx="6" fill="${C.line}"/>
        <rect x="-120" y="-6"  width="34" height="34" rx="9" fill="${accent}" opacity=".2"/>
        <rect x="-74"  y="4"   width="120" height="12" rx="6" fill="${C.line}"/>
        <rect x="-120" y="44"  width="34" height="34" rx="9" fill="${accent}" opacity=".2"/>
        <rect x="-74"  y="54"  width="140" height="12" rx="6" fill="${C.line}"/>
      </g>
      <rect x="-26" y="90"  width="52" height="30" fill="${C.line}"/>
      <rect x="-80" y="118" width="160" height="13" rx="6.5" fill="${C.grey}" opacity=".45"/>
    </g>`;
  }

  /** A potted plant, for the corner of a desk. */
  function plant() {
    return `<g>
      <path d="M 0 10 C -8 -20, -34 -30, -40 -54 C -14 -50, -4 -26, 0 -4 Z" fill="${C.green}" opacity=".75"/>
      <path d="M 0 10 C 8 -24, 32 -34, 40 -60 C 14 -54, 4 -30, 0 -4 Z" fill="${C.green}" opacity=".55"/>
      <path d="M 0 12 C -2 -14, 2 -34, 1 -50" stroke="${C.green}" stroke-width="4" fill="none" opacity=".8"/>
      <path d="M -22 12 L 22 12 L 17 46 L -17 46 Z" fill="${C.amber}" opacity=".85"/>
    </g>`;
  }

  /** A coffee mug. */
  function mug() {
    return `<g>
      <rect x="-20" y="-16" width="38" height="34" rx="6" fill="${C.white}" stroke="${C.line}" stroke-width="3"/>
      <path d="M 18 -6 q 14 0 14 11 q 0 11 -14 11" fill="none" stroke="${C.line}" stroke-width="3"/>
      <rect x="-20" y="-16" width="38" height="9" rx="4" fill="${C.green}" opacity=".5"/>
    </g>`;
  }

  /** A delivery van, facing right. */
  function van() {
    return `<g>
      <rect x="-96" y="-44" width="120" height="66" rx="9" fill="${C.white}" stroke="${C.line}" stroke-width="3"/>
      <path d="M 24 -22 L 62 -22 L 86 6 L 86 22 L 24 22 Z" fill="${C.pale}" stroke="${C.line}" stroke-width="3"/>
      <rect x="34" y="-14" width="30" height="20" rx="4" fill="${C.white}"/>
      <rect x="-84" y="-32" width="42" height="10" rx="5" fill="${C.green}" opacity=".35"/>
      <rect x="-84" y="-14" width="60" height="8" rx="4" fill="${C.line}"/>
      <circle cx="-52" cy="26" r="17" fill="${C.ink}"/><circle cx="-52" cy="26" r="7" fill="${C.white}"/>
      <circle cx="58"  cy="26" r="17" fill="${C.ink}"/><circle cx="58"  cy="26" r="7" fill="${C.white}"/>
    </g>`;
  }

  /** A price tag, tilted. */
  function tag(text) {
    return `<g transform="rotate(-12)">
      <path d="M -64 -30 L 30 -30 L 64 0 L 30 30 L -64 30 Z" fill="${C.green}"/>
      <circle cx="-44" cy="0" r="8" fill="${C.white}" opacity=".9"/>
      <text x="6" y="9" text-anchor="middle" font-family="Segoe UI,sans-serif"
            font-size="26" font-weight="700" fill="${C.white}">${text}</text>
    </g>`;
  }

  /** A globe with meridians. */
  function globe(r) {
    r = r || 90;
    return `<g>
      <circle cx="0" cy="0" r="${r}" fill="${C.green2}"/>
      <circle cx="0" cy="0" r="${r}" fill="none" stroke="${C.green}" stroke-width="3" opacity=".55"/>
      <ellipse cx="0" cy="0" rx="${r*0.42}" ry="${r}" fill="none" stroke="${C.green}" stroke-width="2.5" opacity=".45"/>
      <ellipse cx="0" cy="0" rx="${r*0.78}" ry="${r}" fill="none" stroke="${C.green}" stroke-width="2.5" opacity=".3"/>
      <line x1="${-r}" y1="0" x2="${r}" y2="0" stroke="${C.green}" stroke-width="2.5" opacity=".45"/>
      <path d="M ${-r*0.94} ${-r*0.35} L ${r*0.94} ${-r*0.35}" stroke="${C.green}" stroke-width="2" opacity=".28"/>
      <path d="M ${-r*0.94} ${ r*0.35} L ${r*0.94} ${ r*0.35}" stroke="${C.green}" stroke-width="2" opacity=".28"/>
    </g>`;
  }

  /** A map pin. */
  function pin(colour) {
    return `<g>
      <path d="M 0 16 C -14 -4, -20 -12, -20 -22 A 20 20 0 1 1 20 -22 C 20 -12, 14 -4, 0 16 Z"
            fill="${colour || C.amber}"/>
      <circle cx="0" cy="-22" r="7.5" fill="${C.white}"/>
    </g>`;
  }

  /** A shield with a tick. */
  function shield() {
    return `<g>
      <path d="M 0 -56 L 46 -38 L 46 6 C 46 36, 24 54, 0 62 C -24 54, -46 36, -46 6 L -46 -38 Z"
            fill="${C.green2}" stroke="${C.green}" stroke-width="3.5"/>
      <path d="M -19 2 L -6 16 L 21 -14" fill="none" stroke="${C.green}"
            stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
    </g>`;
  }

  /** A padlock. */
  function lock() {
    return `<g>
      <path d="M -20 -14 V -28 a 20 20 0 0 1 40 0 V -14" fill="none" stroke="${C.green}" stroke-width="8" stroke-linecap="round"/>
      <rect x="-32" y="-14" width="64" height="52" rx="11" fill="${C.green}"/>
      <circle cx="0" cy="8" r="7" fill="${C.white}"/>
      <rect x="-3.5" y="8" width="7" height="15" rx="3.5" fill="${C.white}"/>
    </g>`;
  }

  /** A clock face, hands at a given hour fraction. */
  function clock(turn) {
    const a = (turn || 0) * Math.PI * 2;
    return `<g>
      <circle cx="0" cy="0" r="42" fill="${C.white}" stroke="${C.green}" stroke-width="5"/>
      <line x1="0" y1="0" x2="0" y2="-24" stroke="${C.ink}" stroke-width="5" stroke-linecap="round"
            transform="rotate(${(a*180/Math.PI).toFixed(1)})"/>
      <line x1="0" y1="0" x2="18" y2="0" stroke="${C.grey}" stroke-width="4" stroke-linecap="round"
            transform="rotate(${(a*180/Math.PI*12).toFixed(1)})"/>
      <circle cx="0" cy="0" r="4.5" fill="${C.ink}"/>
    </g>`;
  }

  /** A share glyph struck through: nothing leaves for anyone else. */
  function noShare() {
    return `<g>
      <circle cx="0" cy="-26" r="13" fill="${C.green}"/>
      <circle cx="-28" cy="18" r="13" fill="${C.green}"/>
      <circle cx="28" cy="18" r="13" fill="${C.green}"/>
      <line x1="-6" y1="-14" x2="-22" y2="7" stroke="${C.green}" stroke-width="7" stroke-linecap="round"/>
      <line x1="6" y1="-14" x2="22" y2="7" stroke="${C.green}" stroke-width="7" stroke-linecap="round"/>
      <line x1="-16" y1="18" x2="16" y2="18" stroke="${C.green}" stroke-width="7" stroke-linecap="round"/>
      <line x1="-46" y1="40" x2="46" y2="-46" stroke="${C.red}" stroke-width="9" stroke-linecap="round"/>
    </g>`;
  }

  /** A receipt / order document. */
  function receipt() {
    return `<g>
      <path d="M -38 -52 H 38 V 44 l -12 -8 l -13 8 l -13 -8 l -13 8 l -12 -8 Z"
            fill="${C.white}" stroke="${C.line}" stroke-width="3"/>
      <rect x="-24" y="-38" width="48" height="9" rx="4.5" fill="${C.green}" opacity=".45"/>
      <rect x="-24" y="-20" width="36" height="7" rx="3.5" fill="${C.line}"/>
      <rect x="-24" y="-6"  width="44" height="7" rx="3.5" fill="${C.line}"/>
      <rect x="-24" y="8"   width="28" height="7" rx="3.5" fill="${C.line}"/>
    </g>`;
  }

  /** A bar chart. Heights are 0..1; pass a progress to grow them. */
  function bars(heights, prog, colour) {
    const w = 26, gap = 16, h = 150;
    return heights.map((v, i) => {
      const bh = Math.max(4, v * h * (prog === undefined ? 1 : prog));
      const x = i * (w + gap);
      return `<rect x="${x}" y="${-bh}" width="${w}" height="${bh}" rx="5" fill="${colour || C.green}"/>`;
    }).join('');
  }

  /** A callout bubble with a stem going down-left. */
  function callout(text, colour) {
    return `<g>
      <rect x="-78" y="-34" width="156" height="62" rx="12" fill="${C.white}" stroke="${C.line}" stroke-width="2.5"/>
      <text x="0" y="8" text-anchor="middle" font-family="Segoe UI,sans-serif"
            font-size="30" font-weight="700" fill="${colour || C.red}">${text}</text>
      <path d="M -30 28 L -30 52 L -12 28 Z" fill="${C.white}" stroke="${C.line}" stroke-width="2.5"/>
    </g>`;
  }

  /** A shelf holding parcels. */
  function shelf() {
    return `<g>
      <rect x="-120" y="-4" width="240" height="12" rx="4" fill="${C.grey}" opacity=".35"/>
      <rect x="-120" y="-96" width="240" height="12" rx="4" fill="${C.grey}" opacity=".35"/>
      <rect x="-126" y="-96" width="12" height="104" rx="4" fill="${C.grey}" opacity=".25"/>
      <rect x="114"  y="-96" width="12" height="104" rx="4" fill="${C.grey}" opacity=".25"/>
    </g>`;
  }

  return { C, brand, parcel, monitor, plant, mug, van, tag, globe, pin, shield, lock, clock, noShare, receipt, bars, callout, shelf };
})();
