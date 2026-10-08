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
 * Marketplaces added under Multi-Account: each keeps its own comparison, is
 * sent to from the Multi-Account tab, and gets prices in its own currency.
 */
return array(

'Prices for this marketplace are converted from %1$s to %2$s, and PrestaShop needs both currencies for that. Add the missing one under International > Localization > Currencies, then sync again.' => array(
    'fr' => 'Les prix pour cette place de marché sont convertis de %1$s en %2$s, et PrestaShop a besoin des deux devises pour cela. Ajoutez celle qui manque sous International > Localisation > Devises, puis synchronisez à nouveau.',
    'es' => 'Los precios de este marketplace se convierten de %1$s a %2$s, y PrestaShop necesita ambas monedas para ello. Añade la que falta en Internacional > Localización > Monedas y vuelve a sincronizar.',
    'de' => 'Die Preise für diesen Marktplatz werden von %1$s in %2$s umgerechnet, und PrestaShop braucht dafür beide Währungen. Legen Sie die fehlende unter International > Lokalisierung > Währungen an und synchronisieren Sie erneut.',
    'it' => 'I prezzi per questo marketplace vengono convertiti da %1$s a %2$s e PrestaShop ha bisogno di entrambe le valute. Aggiungi quella mancante in Internazionale > Localizzazione > Valute, poi sincronizza di nuovo.',
    'pl' => 'Ceny dla tego marketplace\'u są przeliczane z %1$s na %2$s i PrestaShop potrzebuje do tego obu walut. Dodaj brakującą w Międzynarodowy > Lokalizacja > Waluty, a następnie zsynchronizuj ponownie.',
),
'Send products to these marketplaces' => array(
    'fr' => 'Envoyer les produits vers ces places de marché',
    'es' => 'Enviar los productos a estos marketplaces',
    'de' => 'Produkte an diese Marktplätze senden',
    'it' => 'Invia i prodotti a questi marketplace',
    'pl' => 'Wyślij produkty do tych marketplace\'ów',
),
'For each marketplace above with Products on: compares your catalogue with that marketplace and sends what is missing or different. The same export rules, profiles and category mappings apply. Products that already have an ASIN on your main marketplace are offered on the same Amazon page. Prices are converted with the exchange rates in PrestaShop when the marketplace uses another currency. The cron task "Product sync, all marketplaces" does the same automatically.' => array(
    'fr' => 'Pour chaque place de marché ci-dessus avec Produits activé : compare votre catalogue avec cette place de marché et envoie ce qui manque ou diffère. Les mêmes règles d\'export, profils et correspondances de catégories s\'appliquent. Les produits qui ont déjà un ASIN sur votre place de marché principale sont proposés sur la même page Amazon. Les prix sont convertis avec les taux de change de PrestaShop quand la place de marché utilise une autre devise. La tâche cron « Synchronisation des produits, toutes places de marché » fait la même chose automatiquement.',
    'es' => 'Para cada marketplace de arriba con Productos activado: compara tu catálogo con ese marketplace y envía lo que falta o es diferente. Se aplican las mismas reglas de exportación, perfiles y correspondencias de categorías. Los productos que ya tienen un ASIN en tu marketplace principal se ofrecen en la misma página de Amazon. Los precios se convierten con los tipos de cambio de PrestaShop cuando el marketplace usa otra moneda. La tarea cron «Sincronización de productos, todos los marketplaces» hace lo mismo automáticamente.',
    'de' => 'Für jeden Marktplatz oben mit aktivierten Produkten: vergleicht Ihren Katalog mit diesem Marktplatz und sendet, was fehlt oder abweicht. Es gelten dieselben Exportregeln, Profile und Kategoriezuordnungen. Produkte, die auf Ihrem Hauptmarktplatz schon eine ASIN haben, werden auf derselben Amazon-Seite angeboten. Verwendet der Marktplatz eine andere Währung, werden die Preise mit den Wechselkursen in PrestaShop umgerechnet. Die Cron-Aufgabe „Produktsynchronisierung, alle Marktplätze“ macht dasselbe automatisch.',
    'it' => 'Per ogni marketplace qui sopra con Prodotti attivo: confronta il tuo catalogo con quel marketplace e invia ciò che manca o è diverso. Valgono le stesse regole di esportazione, profili e associazioni di categoria. I prodotti che hanno già un ASIN sul tuo marketplace principale vengono offerti sulla stessa pagina Amazon. Quando il marketplace usa un\'altra valuta, i prezzi vengono convertiti con i tassi di cambio di PrestaShop. L\'attività cron «Sincronizzazione dei prodotti, tutti i marketplace» fa lo stesso automaticamente.',
    'pl' => 'Dla każdego marketplace\'u powyżej z włączonymi Produktami: porównuje Twój katalog z tym marketplace\'em i wysyła to, czego brakuje lub co się różni. Obowiązują te same reguły eksportu, profile i przypisania kategorii. Produkty, które mają już ASIN na Twoim głównym marketplace\'ie, są oferowane na tej samej stronie Amazon. Gdy marketplace używa innej waluty, ceny są przeliczane według kursów w PrestaShop. Zadanie cron „Synchronizacja produktów, wszystkie marketplace\'y” robi to samo automatycznie.',
),
'%1$s: %2$s product(s) compared, %3$s sent.' => array(
    'fr' => '%1$s : %2$s produit(s) comparé(s), %3$s envoyé(s).',
    'es' => '%1$s: %2$s producto(s) comparado(s), %3$s enviado(s).',
    'de' => '%1$s: %2$s Produkt(e) verglichen, %3$s gesendet.',
    'it' => '%1$s: %2$s prodotto/i confrontato/i, %3$s inviato/i.',
    'pl' => '%1$s: porównano %2$s produkt(ów), wysłano %3$s.',
),
'Sent as feed %1$s - follow it under Feed status.' => array(
    'fr' => 'Envoyé dans le flux %1$s - suivez-le sous État du flux.',
    'es' => 'Enviado como feed %1$s - síguelo en Estado del feed.',
    'de' => 'Als Feed %1$s gesendet - verfolgen Sie ihn unter Feed-Status.',
    'it' => 'Inviato come feed %1$s - seguilo in Stato del feed.',
    'pl' => 'Wysłano jako feed %1$s - śledź go w sekcji Stan feedu.',
),
'No marketplace has Products switched on.' => array(
    'fr' => 'Aucune place de marché n\'a Produits activé.',
    'es' => 'Ningún marketplace tiene Productos activado.',
    'de' => 'Bei keinem Marktplatz sind Produkte aktiviert.',
    'it' => 'Nessun marketplace ha Prodotti attivo.',
    'pl' => 'Żaden marketplace nie ma włączonych Produktów.',
),

);
