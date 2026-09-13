/**
 * Writes locale/thumb.<iso>.json for ../../i18n-apply.js. The thumbnail has a
 * dozen strings, so they live here in one table rather than five files.
 *
 *   node locale/make-maps.js
 */
const fs = require('fs');
const path = require('path');

const AMZ = '<span style="color:#FF9900">%A</span>';
const PS = '<span style="color:#241C4E">Presta</span><span style="color:#DF0067">Shop</span>';
const head = (before, amazon, between) => '>' + before + AMZ.replace('%A', amazon) + between + PS + '<';

const EN = {
  head: head('Sell on ', 'Amazon', ' from '),
  sub: '>Listings, stock, prices and orders. One module.<',
  b1: '<b>Publish</b>', i1: '<i>Your whole catalogue</i>',
  b2: '<b>Sync</b>', i2: '<i>Stock and prices</i>',
  b3: '<b>Orders</b>', i3: '<i>Straight into PrestaShop</i>',
  i4: '<i>Stock and shipments</i>',
  p1: '<span>23 marketplaces</span>', p2: '<span>Repricing</span>', p3: '<span>Runs on its own</span>',
  price: "'22.49'",
};

const L = {
  fr: { head: head('Vendez sur ', 'Amazon', ' depuis '), sub: '>Annonces, stock, prix et commandes. Un seul module.<',
        b1: '<b>Publier</b>', i1: '<i>Tout votre catalogue</i>', b2: '<b>Synchro</b>', i2: '<i>Stock et prix</i>',
        b3: '<b>Commandes</b>', i3: '<i>Directement dans PrestaShop</i>', i4: '<i>Stock et expéditions</i>',
        p1: '<span>23 marketplaces</span>', p2: '<span>Repricing</span>', p3: '<span>Tourne automatiquement</span>', price: "'22,49'" },
  es: { head: head('Vende en ', 'Amazon', ' desde '), sub: '>Anuncios, stock, precios y pedidos. Un solo módulo.<',
        b1: '<b>Publicar</b>', i1: '<i>Todo tu catálogo</i>', b2: '<b>Sincronizar</b>', i2: '<i>Stock y precios</i>',
        b3: '<b>Pedidos</b>', i3: '<i>Directos a PrestaShop</i>', i4: '<i>Stock y envíos</i>',
        p1: '<span>23 marketplaces</span>', p2: '<span>Repricing</span>', p3: '<span>Funciona solo</span>', price: "'22,49'" },
  it: { head: head('Vendi su ', 'Amazon', ' da '), sub: '>Inserzioni, stock, prezzi e ordini. Un solo modulo.<',
        b1: '<b>Pubblica</b>', i1: '<i>Tutto il catalogo</i>', b2: '<b>Sincronizza</b>', i2: '<i>Stock e prezzi</i>',
        b3: '<b>Ordini</b>', i3: '<i>Direttamente in PrestaShop</i>', i4: '<i>Stock e spedizioni</i>',
        p1: '<span>23 marketplace</span>', p2: '<span>Repricing</span>', p3: '<span>Funziona da solo</span>', price: "'22,49'" },
  pl: { head: head('Sprzedawaj na ', 'Amazonie', ' z '), sub: '>Oferty, stany, ceny i zamówienia. Jeden moduł.<',
        b1: '<b>Publikuj</b>', i1: '<i>Cały katalog</i>', b2: '<b>Synchronizacja</b>', i2: '<i>Stany i ceny</i>',
        b3: '<b>Zamówienia</b>', i3: '<i>Prosto do PrestaShop</i>', i4: '<i>Stany i wysyłki</i>',
        p1: '<span>23 rynki</span>', p2: '<span>Repricing</span>', p3: '<span>Działa samodzielnie</span>', price: "'22,49'" },
  de: { head: head('Verkaufe auf ', 'Amazon', ' mit '), sub: '>Angebote, Bestand, Preise und Bestellungen. Ein Modul.<',
        b1: '<b>Einstellen</b>', i1: '<i>Dein ganzer Katalog</i>', b2: '<b>Abgleich</b>', i2: '<i>Bestand und Preise</i>',
        b3: '<b>Bestellungen</b>', i3: '<i>Direkt in PrestaShop</i>', i4: '<i>Bestand und Versand</i>',
        p1: '<span>23 Marktplätze</span>', p2: '<span>Repricing</span>', p3: '<span>Läuft von allein</span>', price: "'22,49'" },
};

for (const [iso, t] of Object.entries(L)) {
  const pairs = Object.keys(EN).filter(k => t[k] !== EN[k]).map(k => [EN[k], t[k]]);
  fs.writeFileSync(path.join(__dirname, `thumb.${iso}.json`), JSON.stringify({ pairs }, null, 2) + '\n');
  console.log(iso, pairs.length, 'pairs');
}
