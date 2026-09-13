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
 * Strings 732-747: filling buyer addresses in from an uploaded order report.
 *
 * The Seller Central menu path and the report names (Unshipped Orders, New
 * Orders, Request report, Download) and the order-id column stay in English,
 * as everywhere else: they are what the merchant has to find on the screen.
 * Three strings are embedded in JavaScript with js=1 and must not contain a
 * literal double quote. The %d messages go through the page's esc() once more,
 * so they avoid & < > and " as well.
 */
return array(

'Buyer addresses from an Amazon order report' => array(
    'fr' => 'Adresses des acheteurs depuis un rapport de commandes Amazon',
    'es' => 'Direcciones de compradores desde un informe de pedidos de Amazon',
    'de' => 'Käuferadressen aus einem Amazon-Bestellbericht',
    'it' => 'Indirizzi degli acquirenti da un report ordini Amazon',
    'pl' => 'Adresy kupujących z raportu zamówień Amazon',
),
'Until Amazon approves this app to read buyer addresses, orders arrive without the street and the recipient name. Download the order report in Seller Central and upload it here to fill them in.' => array(
    'fr' => 'Tant qu\'Amazon n\'a pas autorisé cette application à lire les adresses des acheteurs, les commandes arrivent sans la rue ni le nom du destinataire. Téléchargez le rapport de commandes dans Seller Central et importez-le ici pour les compléter.',
    'es' => 'Hasta que Amazon autorice a esta aplicación a leer las direcciones de los compradores, los pedidos llegan sin la calle ni el nombre del destinatario. Descarga el informe de pedidos en Seller Central y súbelo aquí para completarlos.',
    'de' => 'Solange Amazon dieser App das Lesen von Käuferadressen nicht erlaubt hat, kommen Bestellungen ohne Straße und Empfängernamen an. Laden Sie den Bestellbericht in Seller Central herunter und hier hoch, um sie zu vervollständigen.',
    'it' => 'Finché Amazon non autorizza questa app a leggere gli indirizzi degli acquirenti, gli ordini arrivano senza via e senza nome del destinatario. Scarica il report ordini da Seller Central e caricalo qui per completarli.',
    'pl' => 'Dopóki Amazon nie zezwoli tej aplikacji na odczyt adresów kupujących, zamówienia docierają bez ulicy i nazwiska odbiorcy. Pobierz raport zamówień z Seller Central i prześlij go tutaj, aby je uzupełnić.',
),
'In Seller Central: Orders > Order Reports > Unshipped Orders, then Request report and Download.' => array(
    'fr' => 'Dans Seller Central : Orders > Order Reports > Unshipped Orders, puis Request report et Download.',
    'es' => 'En Seller Central: Orders > Order Reports > Unshipped Orders y después Request report y Download.',
    'de' => 'In Seller Central: Orders > Order Reports > Unshipped Orders, dann Request report und Download.',
    'it' => 'In Seller Central: Orders > Order Reports > Unshipped Orders, poi Request report e Download.',
    'pl' => 'W Seller Central: Orders > Order Reports > Unshipped Orders, a następnie Request report i Download.',
),
'Upload report' => array(
    'fr' => 'Importer le rapport',
    'es' => 'Subir informe',
    'de' => 'Bericht hochladen',
    'it' => 'Carica report',
    'pl' => 'Prześlij raport',
),
'Only orders that are already imported are updated. Placeholder values are replaced; an address that has already been filled in or edited is left as it is. The file is read once and not stored.' => array(
    'fr' => 'Seules les commandes déjà importées sont mises à jour. Les valeurs provisoires sont remplacées ; une adresse déjà complétée ou modifiée reste inchangée. Le fichier est lu une seule fois et n\'est pas conservé.',
    'es' => 'Solo se actualizan los pedidos ya importados. Se sustituyen los valores provisionales; una dirección ya completada o editada se deja como está. El archivo se lee una sola vez y no se guarda.',
    'de' => 'Nur bereits importierte Bestellungen werden aktualisiert. Platzhalter werden ersetzt; eine bereits ausgefüllte oder bearbeitete Adresse bleibt unverändert. Die Datei wird einmal gelesen und nicht gespeichert.',
    'it' => 'Vengono aggiornati solo gli ordini già importati. I valori provvisori vengono sostituiti; un indirizzo già compilato o modificato resta invariato. Il file viene letto una sola volta e non viene salvato.',
    'pl' => 'Aktualizowane są tylko zamówienia już zaimportowane. Wartości tymczasowe zostają zastąpione; adres, który został już uzupełniony lub zmieniony, pozostaje bez zmian. Plik jest odczytywany jednorazowo i nie jest zapisywany.',
),
'Choose the order report file first.' => array(
    'fr' => 'Choisissez d\'abord le fichier du rapport de commandes.',
    'es' => 'Elige primero el archivo del informe de pedidos.',
    'de' => 'Wählen Sie zuerst die Datei mit dem Bestellbericht.',
    'it' => 'Scegli prima il file del report ordini.',
    'pl' => 'Najpierw wybierz plik raportu zamówień.',
),
'Reading the report...' => array(
    'fr' => 'Lecture du rapport...',
    'es' => 'Leyendo el informe...',
    'de' => 'Bericht wird gelesen...',
    'it' => 'Lettura del report...',
    'pl' => 'Odczytywanie raportu...',
),
'The report could not be processed.' => array(
    'fr' => 'Le rapport n\'a pas pu être traité.',
    'es' => 'No se ha podido procesar el informe.',
    'de' => 'Der Bericht konnte nicht verarbeitet werden.',
    'it' => 'Non è stato possibile elaborare il report.',
    'pl' => 'Nie udało się przetworzyć raportu.',
),
'This file is not an Amazon order report: it has no order-id column.' => array(
    'fr' => 'Ce fichier n\'est pas un rapport de commandes Amazon : il n\'a pas de colonne order-id.',
    'es' => 'Este archivo no es un informe de pedidos de Amazon: no tiene la columna order-id.',
    'de' => 'Diese Datei ist kein Amazon-Bestellbericht: Sie hat keine Spalte order-id.',
    'it' => 'Questo file non è un report ordini Amazon: non ha la colonna order-id.',
    'pl' => 'Ten plik nie jest raportem zamówień Amazon: nie ma kolumny order-id.',
),
'This report has no shipping address columns. Download the Unshipped Orders or New Orders report instead.' => array(
    'fr' => 'Ce rapport n\'a pas de colonnes d\'adresse de livraison. Téléchargez plutôt le rapport Unshipped Orders ou New Orders.',
    'es' => 'Este informe no tiene columnas de dirección de envío. Descarga en su lugar el informe Unshipped Orders o New Orders.',
    'de' => 'Dieser Bericht hat keine Spalten für die Lieferadresse. Laden Sie stattdessen den Bericht Unshipped Orders oder New Orders herunter.',
    'it' => 'Questo report non ha colonne per l\'indirizzo di spedizione. Scarica invece il report Unshipped Orders o New Orders.',
    'pl' => 'Ten raport nie ma kolumn z adresem wysyłki. Pobierz zamiast niego raport Unshipped Orders lub New Orders.',
),
'The report contains no orders.' => array(
    'fr' => 'Le rapport ne contient aucune commande.',
    'es' => 'El informe no contiene ningún pedido.',
    'de' => 'Der Bericht enthält keine Bestellungen.',
    'it' => 'Il report non contiene ordini.',
    'pl' => 'Raport nie zawiera żadnych zamówień.',
),
'%d order(s) filled in from the report.' => array(
    'fr' => '%d commande(s) complétée(s) à partir du rapport.',
    'es' => '%d pedido(s) completado(s) a partir del informe.',
    'de' => '%d Bestellung(en) aus dem Bericht ergänzt.',
    'it' => '%d ordine/i completato/i dal report.',
    'pl' => 'Zamówienia uzupełnione na podstawie raportu: %d.',
),
'%d order(s) already had a full address.' => array(
    'fr' => 'Adresse déjà complète pour %d commande(s).',
    'es' => 'Dirección ya completa en %d pedido(s).',
    'de' => '%d Bestellung(en) hatten bereits eine vollständige Adresse.',
    'it' => 'Indirizzo già completo per %d ordine/i.',
    'pl' => 'Zamówienia, które miały już pełny adres: %d.',
),
'%d PrestaShop address(es) left unchanged because they had already been filled in or edited.' => array(
    'fr' => '%d adresse(s) PrestaShop laissée(s) telle(s) quelle(s), car déjà complétée(s) ou modifiée(s).',
    'es' => '%d dirección(es) de PrestaShop sin cambios porque ya se habían completado o editado.',
    'de' => '%d PrestaShop-Adresse(n) unverändert gelassen, weil sie bereits ausgefüllt oder bearbeitet waren.',
    'it' => 'Indirizzi PrestaShop lasciati invariati perché già compilati o modificati: %d.',
    'pl' => 'Adresy PrestaShop pozostawione bez zmian, bo były już uzupełnione lub zmienione: %d.',
),
'%d order(s) are not imported yet. Import them first, then upload the report again.' => array(
    'fr' => '%d commande(s) pas encore importée(s). Importez-les d\'abord, puis envoyez à nouveau le rapport.',
    'es' => '%d pedido(s) aún sin importar. Impórtalos primero y vuelve a subir el informe.',
    'de' => '%d Bestellung(en) noch nicht importiert. Importieren Sie sie zuerst und laden Sie den Bericht dann erneut hoch.',
    'it' => 'Ordini non ancora importati: %d. Importali prima, poi carica di nuovo il report.',
    'pl' => 'Zamówienia jeszcze niezaimportowane: %d. Najpierw je zaimportuj, a potem prześlij raport ponownie.',
),
'%d order(s) skipped: their buyer data was already removed under the retention policy.' => array(
    'fr' => '%d commande(s) ignorée(s) : leurs données acheteur ont déjà été supprimées selon la politique de conservation.',
    'es' => '%d pedido(s) omitido(s): sus datos de comprador ya se eliminaron según la política de conservación.',
    'de' => '%d Bestellung(en) übersprungen: deren Käuferdaten wurden gemäß der Aufbewahrungsfrist bereits gelöscht.',
    'it' => 'Ordini saltati: %d. I dati dell\'acquirente erano già stati rimossi secondo la politica di conservazione.',
    'pl' => 'Pominięte zamówienia, których dane kupującego usunięto już zgodnie z zasadami przechowywania: %d.',
),

);
