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
 * One barcode field holding EAN-13, UPC-12, EAN-8 and GTIN-14 codes.
 */
return array(

'Automatic: by the number of digits' => array(
    'fr' => 'Automatique : selon le nombre de chiffres',
    'es' => 'Automático: según el número de dígitos',
    'de' => 'Automatisch: nach Anzahl der Ziffern',
    'it' => 'Automatico: in base al numero di cifre',
    'pl' => 'Automatycznie: według liczby cyfr',
),
'Always UPC' => array(
    'fr' => 'Toujours UPC',
    'es' => 'Siempre UPC',
    'de' => 'Immer UPC',
    'it' => 'Sempre UPC',
    'pl' => 'Zawsze UPC',
),
'Automatic reads each product\'s barcode on its own: 13 or 8 digits are sent as EAN, 12 digits as UPC, 14 digits as GTIN, so one field can hold all of them, as ERPs often do. Spaces are ignored. Amazon treats a 12-digit UPC and the same code with a leading 0 as one product. Choose "Always UPC" only if every code in the field is a US UPC.' => array(
    'fr' => 'Automatique lit le code-barres de chaque produit séparément : 13 ou 8 chiffres sont envoyés comme EAN, 12 chiffres comme UPC, 14 chiffres comme GTIN, si bien qu\'un même champ peut tous les contenir, comme le font souvent les ERP. Les espaces sont ignorés. Amazon considère un UPC à 12 chiffres et le même code précédé d\'un 0 comme un seul produit. Choisissez « Toujours UPC » uniquement si tous les codes du champ sont des UPC américains.',
    'es' => 'Automático lee el código de barras de cada producto por separado: 13 u 8 dígitos se envían como EAN, 12 dígitos como UPC y 14 dígitos como GTIN, así que un mismo campo puede contenerlos todos, como suelen hacer los ERP. Los espacios se ignoran. Amazon considera un UPC de 12 dígitos y el mismo código con un 0 delante como un solo producto. Elige «Siempre UPC» solo si todos los códigos del campo son UPC estadounidenses.',
    'de' => 'Automatisch liest den Barcode jedes Produkts einzeln: 13 oder 8 Ziffern werden als EAN gesendet, 12 Ziffern als UPC, 14 Ziffern als GTIN, sodass ein Feld alle enthalten kann, wie es ERP-Systeme oft tun. Leerzeichen werden ignoriert. Amazon behandelt einen 12-stelligen UPC und denselben Code mit führender 0 als ein Produkt. Wählen Sie „Immer UPC“ nur, wenn alle Codes im Feld US-UPCs sind.',
    'it' => 'Automatico legge il codice a barre di ogni prodotto separatamente: 13 o 8 cifre vengono inviate come EAN, 12 cifre come UPC, 14 cifre come GTIN, così un unico campo può contenerli tutti, come fanno spesso gli ERP. Gli spazi vengono ignorati. Amazon considera un UPC a 12 cifre e lo stesso codice preceduto da uno 0 come un solo prodotto. Scegli «Sempre UPC» solo se tutti i codici del campo sono UPC statunitensi.',
    'pl' => 'Automatycznie odczytuje kod kreskowy każdego produktu osobno: 13 lub 8 cyfr jest wysyłanych jako EAN, 12 cyfr jako UPC, 14 cyfr jako GTIN, więc jedno pole może zawierać je wszystkie, jak często robią systemy ERP. Spacje są pomijane. Amazon traktuje 12-cyfrowy UPC i ten sam kod z wiodącym 0 jako jeden produkt. Wybierz „Zawsze UPC” tylko wtedy, gdy wszystkie kody w polu to amerykańskie UPC.',
),

);
