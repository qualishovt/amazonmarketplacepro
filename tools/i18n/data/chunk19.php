<?php
/**
 * Amazon Marketplace Pro
 *
 * NOTICE OF LICENCE
 *
 * This software is commercial and licensed, not sold. The licence is
 * bundled with this package in the file LICENSE.txt. Redistribution,
 * resale and publication of the source are prohibited.
 *
 *  @author    IntelliPresta
 *  @copyright 2026 IntelliPresta
 *  @license   Proprietary. See LICENSE.txt - redistribution prohibited.
 */

/**
 * A markup per product, ahead of the category, manufacturer and supplier ones.
 */
return array(

'This product\'s own markup on Amazon: a percentage ("10%") or an amount ("2.50"). It replaces the category, manufacturer and supplier markups for this product. Empty = use those.' => array(
    'fr' => 'Majoration propre à ce produit sur Amazon : un pourcentage (« 10% ») ou un montant (« 2.50 »). Elle remplace pour ce produit les majorations de catégorie, de fabricant et de fournisseur. Vide = utiliser celles-ci.',
    'es' => 'Margen propio de este producto en Amazon: un porcentaje («10%») o un importe («2.50»). Sustituye para este producto los márgenes de categoría, fabricante y proveedor. Vacío = usar esos.',
    'de' => 'Eigener Aufschlag dieses Produkts bei Amazon: ein Prozentsatz („10%“) oder ein Betrag („2.50“). Er ersetzt für dieses Produkt die Aufschläge von Kategorie, Hersteller und Lieferant. Leer = diese verwenden.',
    'it' => 'Ricarico proprio di questo prodotto su Amazon: una percentuale («10%») o un importo («2.50»). Sostituisce per questo prodotto i ricarichi di categoria, produttore e fornitore. Vuoto = usare quelli.',
    'pl' => 'Własny narzut tego produktu na Amazon: procent („10%”) lub kwota („2.50”). Zastępuje dla tego produktu narzuty kategorii, producenta i dostawcy. Puste = użyj tamtych.',
),
'Switch individual products out of the Amazon sync, give a product its own GPSR contact (overrides manufacturer/supplier contacts), or its own price markup ("10%" or "2.50", ahead of the category, manufacturer and supplier markups). The same settings are on each product page, in the Amazon section.' => array(
    'fr' => 'Excluez des produits de la synchronisation Amazon, donnez à un produit son propre contact GPSR (prioritaire sur ceux du fabricant/fournisseur), ou sa propre majoration de prix (« 10% » ou « 2.50 », prioritaire sur les majorations de catégorie, de fabricant et de fournisseur). Les mêmes réglages se trouvent sur chaque fiche produit, dans la section Amazon.',
    'es' => 'Excluye productos de la sincronización con Amazon, da a un producto su propio contacto GPSR (prioritario sobre los del fabricante/proveedor) o su propio margen de precio («10%» o «2.50», prioritario sobre los márgenes de categoría, fabricante y proveedor). Los mismos ajustes están en la ficha de cada producto, en la sección Amazon.',
    'de' => 'Nehmen Sie einzelne Produkte aus der Amazon-Synchronisierung, geben Sie einem Produkt einen eigenen GPSR-Kontakt (vor Hersteller-/Lieferantenkontakten) oder einen eigenen Preisaufschlag („10%“ oder „2.50“, vor den Aufschlägen von Kategorie, Hersteller und Lieferant). Dieselben Einstellungen finden Sie auf jeder Produktseite im Amazon-Bereich.',
    'it' => 'Escludi singoli prodotti dalla sincronizzazione Amazon, assegna a un prodotto un proprio contatto GPSR (prioritario su quelli di produttore/fornitore) o un proprio ricarico di prezzo («10%» o «2.50», prioritario sui ricarichi di categoria, produttore e fornitore). Le stesse impostazioni sono in ogni scheda prodotto, nella sezione Amazon.',
    'pl' => 'Wyłącz pojedyncze produkty z synchronizacji z Amazon, nadaj produktowi własny kontakt GPSR (przed kontaktami producenta/dostawcy) lub własny narzut cenowy („10%” lub „2.50”, przed narzutami kategorii, producenta i dostawcy). Te same ustawienia są na stronie każdego produktu, w sekcji Amazon.',
),
'Markups are fixed values ("5.99") or percentages ("10%"), applied to the exported price. Rules cascade per product: its own markup (product page, Amazon section), then its default category, then manufacturer, then supplier — the first source with a value wins; the defaults below apply when none matches.' => array(
    'fr' => 'Les majorations sont des valeurs fixes (« 5.99 ») ou des pourcentages (« 10% »), appliqués au prix exporté. Les règles se cascadent produit par produit : sa propre majoration (fiche produit, section Amazon), puis sa catégorie par défaut, puis le fabricant, puis le fournisseur — la première source pourvue d\'une valeur l\'emporte ; les valeurs par défaut ci-dessous s\'appliquent lorsqu\'aucune ne correspond.',
    'es' => 'Los márgenes son valores fijos («5.99») o porcentajes («10%») que se aplican al precio exportado. Las reglas se aplican en cascada por producto: su propio margen (ficha del producto, sección Amazon), luego su categoría por defecto, luego el fabricante y luego el proveedor; gana la primera fuente que tenga un valor, y si ninguna coincide se usan los valores por defecto de abajo.',
    'de' => 'Aufschläge sind feste Werte („5.99“) oder Prozentsätze („10%“), die auf den exportierten Preis angewendet werden. Die Regeln greifen je Produkt kaskadierend: sein eigener Aufschlag (Produktseite, Amazon-Bereich), dann seine Standardkategorie, dann Hersteller, dann Lieferant — die erste Quelle mit einem Wert gewinnt; passt keine, gelten die Standardwerte unten.',
    'it' => 'I ricarichi sono valori fissi («5.99») o percentuali («10%») applicati al prezzo esportato. Le regole si applicano a cascata per prodotto: il suo ricarico (scheda prodotto, sezione Amazon), poi la sua categoria predefinita, poi produttore, poi fornitore — vince la prima fonte che ha un valore; se nessuna corrisponde valgono i valori predefiniti qui sotto.',
    'pl' => 'Narzuty to wartości stałe („5.99”) lub procentowe („10%”), stosowane do ceny eksportowanej. Reguły działają kaskadowo dla każdego produktu: jego własny narzut (strona produktu, sekcja Amazon), potem jego kategoria domyślna, potem producent, potem dostawca — wygrywa pierwsze źródło z wartością; gdy żadne nie pasuje, obowiązują wartości domyślne poniżej.',
),

);
