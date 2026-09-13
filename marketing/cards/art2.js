/**
 * Additional pieces for the second promo, in the same flat style as art.js.
 * Everything is centred on (0,0) and coloured from the shared palette.
 */
(function () {
  const A = window.ART, C = A.C;
  const F = 'font-family="Segoe UI,sans-serif"';

  /** A seated merchant, facing right, arms towards a keyboard. */
  A.person = function (tilt) {
    return `<g>
      <rect x="-64" y="12" width="78" height="60" rx="18" fill="${C.ink}" opacity=".85"/>
      <path d="M -34 -34 h 58 a 16 16 0 0 1 16 16 v 46 h -90 v -46 a 16 16 0 0 1 16 -16 z" fill="${C.green}"/>
      <rect x="-6" y="-14" width="62" height="15" rx="7.5" fill="#F1C7A6"/>
      <g transform="rotate(${tilt || 0})">
        <circle cx="0" cy="-58" r="22" fill="#F1C7A6"/>
        <path d="M -23 -62 a 23 23 0 0 1 46 0 v 4 h -46 z" fill="#4A3B32"/>
        <circle cx="8" cy="-58" r="2.6" fill="${C.ink}"/>
        <path d="M 6 -49 q 6 4 12 0" stroke="${C.ink}" stroke-width="2.2" fill="none" stroke-linecap="round"/>
      </g>
    </g>`;
  };

  /** A phone, upright, with a screen area to draw into. */
  A.phone = function (inner) {
    return `<g>
      <rect x="-46" y="-92" width="92" height="184" rx="18" fill="${C.ink}"/>
      <rect x="-39" y="-84" width="78" height="168" rx="12" fill="${C.white}"/>
      <rect x="-16" y="-84" width="32" height="8" rx="4" fill="${C.ink}"/>
      <rect x="-28" y="-60" width="56" height="9" rx="4.5" fill="${C.line}"/>
      <rect x="-28" y="-42" width="40" height="9" rx="4.5" fill="${C.line}"/>
      ${inner || ''}
    </g>`;
  };

  /** A notification card, for a phone or beside a pin. */
  A.notice = function (title, body, colour) {
    return `<g>
      <rect x="-118" y="-38" width="236" height="76" rx="14" fill="${C.white}" stroke="${C.line}" stroke-width="2.5"/>
      <circle cx="-86" cy="0" r="16" fill="${colour || C.amber}"/>
      <path d="M -92 0 l 4 5 l 9 -10" fill="none" stroke="${C.white}" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
      <text x="-60" y="-6" ${F} font-size="20" font-weight="700" fill="${C.ink}">${title}</text>
      <text x="-60" y="20" ${F} font-size="17" font-weight="500" fill="${C.grey}">${body}</text>
    </g>`;
  };

  /** A pill with a green tick and a label. */
  A.tick = function (text, w) {
    w = w || 200;
    return `<g>
      <rect x="${-w/2}" y="-28" width="${w}" height="56" rx="28" fill="${C.white}" stroke="${C.line}" stroke-width="2.5"/>
      <circle cx="${-w/2+30}" cy="0" r="15" fill="${C.green}"/>
      <path d="M ${-w/2+23} 0 l 5 5 l 9 -10" fill="none" stroke="${C.white}" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
      <text x="${-w/2+56}" y="8" ${F} font-size="24" font-weight="600" fill="${C.ink}">${text}</text>
    </g>`;
  };

  /** A price tag in any colour, with an id on the number so it can be rolled. */
  A.tag2 = function (text, colour, id) {
    return `<g transform="rotate(-10)">
      <path d="M -70 -32 L 32 -32 L 70 0 L 32 32 L -70 32 Z" fill="${colour || C.green}"/>
      <circle cx="-50" cy="0" r="8" fill="${C.white}" opacity=".9"/>
      <text ${id ? `id="${id}"` : ''} x="6" y="10" text-anchor="middle" ${F}
            font-size="28" font-weight="700" fill="${C.white}">${text}</text>
    </g>`;
  };

  /** The Buy Box badge: a small crown over a label. */
  A.buybox = function () {
    return `<g>
      <rect x="-96" y="-30" width="192" height="60" rx="30" fill="${C.amber}"/>
      <path d="M -60 6 l -6 -22 l 12 9 l 8 -16 l 8 16 l 12 -9 l -6 22 z" fill="${C.white}" transform="translate(-14,-2) scale(.9)"/>
      <text x="22" y="9" text-anchor="middle" ${F} font-size="24" font-weight="700" fill="${C.white}">Buy Box</text>
    </g>`;
  };

  /** A mouse pointer. */
  A.cursor = function () {
    return `<g>
      <path d="M 0 0 L 0 30 L 8 23 L 14 36 L 20 33 L 14 21 L 24 20 Z" fill="${C.ink}" stroke="${C.white}" stroke-width="2.5" stroke-linejoin="round"/>
    </g>`;
  };

  /** A stock counter card: label above, number below. The number carries an id. */
  A.counter = function (label, id, colour) {
    return `<g>
      <rect x="-90" y="-46" width="180" height="92" rx="16" fill="${C.white}" stroke="${C.line}" stroke-width="2.5"/>
      <text x="0" y="-16" text-anchor="middle" ${F} font-size="19" font-weight="600" fill="${C.grey}">${label}</text>
      <text id="${id}" x="0" y="26" text-anchor="middle" ${F} font-size="40" font-weight="700" fill="${colour || C.ink}">0</text>
    </g>`;
  };

  /** An eye, for "watched". */
  A.eye = function () {
    return `<g>
      <path d="M -40 0 Q 0 -34 40 0 Q 0 34 -40 0 Z" fill="${C.white}" stroke="${C.green}" stroke-width="5"/>
      <circle cx="0" cy="0" r="12" fill="${C.green}"/>
      <circle cx="4" cy="-4" r="4" fill="${C.white}"/>
    </g>`;
  };

  /** A road: dashed centre line on a grey bar. */
  A.road = function (w) {
    return `<g>
      <rect x="${-w/2}" y="-10" width="${w}" height="20" rx="10" fill="${C.grey}" opacity=".22"/>
      <line x1="${-w/2+30}" y1="0" x2="${w/2-30}" y2="0" stroke="${C.white}" stroke-width="4" stroke-dasharray="34 26" opacity=".9"/>
    </g>`;
  };

  /** A small Amazon-side chip with the smile. */
  A.amazonChip = function (text) {
    return `<g>
      <rect x="-110" y="-30" width="220" height="60" rx="30" fill="${C.ink}"/>
      <text x="-4" y="9" text-anchor="middle" ${F} font-size="24" font-weight="700" fill="${C.white}">${text}</text>
      <path d="M -70 12 q 24 16 48 0" fill="none" stroke="${C.amber}" stroke-width="3.5" stroke-linecap="round"/>
    </g>`;
  };

  /** A four-point sparkle. */
  A.spark = function (r) {
    r = r || 12;
    return `<path d="M 0 ${-r} Q 0 0 ${r} 0 Q 0 0 0 ${r} Q 0 0 ${-r} 0 Q 0 0 0 ${-r} Z" fill="${C.amber}"/>`;
  };
})();
