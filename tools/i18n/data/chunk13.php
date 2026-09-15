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
 * Messages built in the plain classes and the front controllers, plus the
 * marketplace names, the Amazon condition names and a few module-class notices.
 *
 * The classes translate through AmazonI18n, which undoes PrestaShop's HTML
 * escaping, so a straight double quote or a > from a menu path is safe here
 * where the English has one. No translation may contain & or <. Placeholders
 * are filled in with sprintf() after translation; positional ones may move.
 */
return array(
  'In cart' => 
  array(
    'fr' => 'Dans le panier',
    'es' => 'En el carrito',
    'de' => 'Im Warenkorb',
    'it' => 'Nel carrello',
    'pl' => 'W koszyku',
  ),
  'Full order cancellation' => 
  array(
    'fr' => 'Annulation complète de la commande',
    'es' => 'Cancelación completa del pedido',
    'de' => 'Vollständige Bestellstornierung',
    'it' => 'Annullamento completo dell\'ordine',
    'pl' => 'Całkowite anulowanie zamówienia',
  ),
  'Unknown error' => 
  array(
    'fr' => 'Erreur inconnue',
    'es' => 'Error desconocido',
    'de' => 'Unbekannter Fehler',
    'it' => 'Errore sconosciuto',
    'pl' => 'Nieznany błąd',
  ),
  'Amazon.com (US)' => 
  array(
    'fr' => 'Amazon.com (États-Unis)',
    'es' => 'Amazon.com (EE. UU.)',
    'de' => 'Amazon.com (USA)',
    'it' => 'Amazon.com (Stati Uniti)',
    'pl' => 'Amazon.com (USA)',
  ),
  'Amazon.ca (Canada)' => 
  array(
    'fr' => 'Amazon.ca (Canada)',
    'es' => 'Amazon.ca (Canadá)',
    'de' => 'Amazon.ca (Kanada)',
    'it' => 'Amazon.ca (Canada)',
    'pl' => 'Amazon.ca (Kanada)',
  ),
  'Amazon.com.mx (Mexico)' => 
  array(
    'fr' => 'Amazon.com.mx (Mexique)',
    'es' => 'Amazon.com.mx (México)',
    'de' => 'Amazon.com.mx (Mexiko)',
    'it' => 'Amazon.com.mx (Messico)',
    'pl' => 'Amazon.com.mx (Meksyk)',
  ),
  'Amazon.com.br (Brazil)' => 
  array(
    'fr' => 'Amazon.com.br (Brésil)',
    'es' => 'Amazon.com.br (Brasil)',
    'de' => 'Amazon.com.br (Brasilien)',
    'it' => 'Amazon.com.br (Brasile)',
    'pl' => 'Amazon.com.br (Brazylia)',
  ),
  'Amazon.co.uk (UK)' => 
  array(
    'fr' => 'Amazon.co.uk (Royaume-Uni)',
    'es' => 'Amazon.co.uk (Reino Unido)',
    'de' => 'Amazon.co.uk (UK)',
    'it' => 'Amazon.co.uk (Regno Unito)',
    'pl' => 'Amazon.co.uk (Wielka Brytania)',
  ),
  'Amazon.de (Germany)' => 
  array(
    'fr' => 'Amazon.de (Allemagne)',
    'es' => 'Amazon.de (Alemania)',
    'de' => 'Amazon.de (Deutschland)',
    'it' => 'Amazon.de (Germania)',
    'pl' => 'Amazon.de (Niemcy)',
  ),
  'Amazon.fr (France)' => 
  array(
    'fr' => 'Amazon.fr (France)',
    'es' => 'Amazon.fr (Francia)',
    'de' => 'Amazon.fr (Frankreich)',
    'it' => 'Amazon.fr (Francia)',
    'pl' => 'Amazon.fr (Francja)',
  ),
  'Amazon.it (Italy)' => 
  array(
    'fr' => 'Amazon.it (Italie)',
    'es' => 'Amazon.it (Italia)',
    'de' => 'Amazon.it (Italien)',
    'it' => 'Amazon.it (Italia)',
    'pl' => 'Amazon.it (Włochy)',
  ),
  'Amazon.es (Spain)' => 
  array(
    'fr' => 'Amazon.es (Espagne)',
    'es' => 'Amazon.es (España)',
    'de' => 'Amazon.es (Spanien)',
    'it' => 'Amazon.es (Spagna)',
    'pl' => 'Amazon.es (Hiszpania)',
  ),
  'Amazon.nl (Netherlands)' => 
  array(
    'fr' => 'Amazon.nl (Pays-Bas)',
    'es' => 'Amazon.nl (Países Bajos)',
    'de' => 'Amazon.nl (Niederlande)',
    'it' => 'Amazon.nl (Paesi Bassi)',
    'pl' => 'Amazon.nl (Holandia)',
  ),
  'Amazon.pl (Poland)' => 
  array(
    'fr' => 'Amazon.pl (Pologne)',
    'es' => 'Amazon.pl (Polonia)',
    'de' => 'Amazon.pl (Polen)',
    'it' => 'Amazon.pl (Polonia)',
    'pl' => 'Amazon.pl (Polska)',
  ),
  'Amazon.se (Sweden)' => 
  array(
    'fr' => 'Amazon.se (Suède)',
    'es' => 'Amazon.se (Suecia)',
    'de' => 'Amazon.se (Schweden)',
    'it' => 'Amazon.se (Svezia)',
    'pl' => 'Amazon.se (Szwecja)',
  ),
  'Amazon.com.be (Belgium)' => 
  array(
    'fr' => 'Amazon.com.be (Belgique)',
    'es' => 'Amazon.com.be (Bélgica)',
    'de' => 'Amazon.com.be (Belgien)',
    'it' => 'Amazon.com.be (Belgio)',
    'pl' => 'Amazon.com.be (Belgia)',
  ),
  'Amazon.ie (Ireland)' => 
  array(
    'fr' => 'Amazon.ie (Irlande)',
    'es' => 'Amazon.ie (Irlanda)',
    'de' => 'Amazon.ie (Irland)',
    'it' => 'Amazon.ie (Irlanda)',
    'pl' => 'Amazon.ie (Irlandia)',
  ),
  'Amazon.eg (Egypt)' => 
  array(
    'fr' => 'Amazon.eg (Égypte)',
    'es' => 'Amazon.eg (Egipto)',
    'de' => 'Amazon.eg (Ägypten)',
    'it' => 'Amazon.eg (Egitto)',
    'pl' => 'Amazon.eg (Egipt)',
  ),
  'Amazon.co.za (South Africa)' => 
  array(
    'fr' => 'Amazon.co.za (Afrique du Sud)',
    'es' => 'Amazon.co.za (Sudáfrica)',
    'de' => 'Amazon.co.za (Südafrika)',
    'it' => 'Amazon.co.za (Sudafrica)',
    'pl' => 'Amazon.co.za (RPA)',
  ),
  'Amazon.com.tr (Turkey)' => 
  array(
    'fr' => 'Amazon.com.tr (Turquie)',
    'es' => 'Amazon.com.tr (Turquía)',
    'de' => 'Amazon.com.tr (Türkei)',
    'it' => 'Amazon.com.tr (Turchia)',
    'pl' => 'Amazon.com.tr (Turcja)',
  ),
  'Amazon.in (India)' => 
  array(
    'fr' => 'Amazon.in (Inde)',
    'es' => 'Amazon.in (India)',
    'de' => 'Amazon.in (Indien)',
    'it' => 'Amazon.in (India)',
    'pl' => 'Amazon.in (Indie)',
  ),
  'Amazon.ae (UAE)' => 
  array(
    'fr' => 'Amazon.ae (Émirats arabes unis)',
    'es' => 'Amazon.ae (EAU)',
    'de' => 'Amazon.ae (VAE)',
    'it' => 'Amazon.ae (Emirati Arabi Uniti)',
    'pl' => 'Amazon.ae (ZEA)',
  ),
  'Amazon.sa (Saudi Arabia)' => 
  array(
    'fr' => 'Amazon.sa (Arabie saoudite)',
    'es' => 'Amazon.sa (Arabia Saudí)',
    'de' => 'Amazon.sa (Saudi-Arabien)',
    'it' => 'Amazon.sa (Arabia Saudita)',
    'pl' => 'Amazon.sa (Arabia Saudyjska)',
  ),
  'Amazon.sg (Singapore)' => 
  array(
    'fr' => 'Amazon.sg (Singapour)',
    'es' => 'Amazon.sg (Singapur)',
    'de' => 'Amazon.sg (Singapur)',
    'it' => 'Amazon.sg (Singapore)',
    'pl' => 'Amazon.sg (Singapur)',
  ),
  'Amazon.com.au (Australia)' => 
  array(
    'fr' => 'Amazon.com.au (Australie)',
    'es' => 'Amazon.com.au (Australia)',
    'de' => 'Amazon.com.au (Australien)',
    'it' => 'Amazon.com.au (Australia)',
    'pl' => 'Amazon.com.au (Australia)',
  ),
  'Amazon.co.jp (Japan)' => 
  array(
    'fr' => 'Amazon.co.jp (Japon)',
    'es' => 'Amazon.co.jp (Japón)',
    'de' => 'Amazon.co.jp (Japan)',
    'it' => 'Amazon.co.jp (Giappone)',
    'pl' => 'Amazon.co.jp (Japonia)',
  ),
  'Nothing to send. Press "Sync PS to Amazon" first: it marks what differs from Amazon.' => 
  array(
    'fr' => 'Rien à envoyer. Cliquez d\'abord sur « Synchroniser PS vers Amazon » : cela signale ce qui diffère d\'Amazon.',
    'es' => 'No hay nada que enviar. Pulsa primero «Sincronizar PS con Amazon»: marca lo que difiere de Amazon.',
    'de' => 'Nichts zu senden. Klicken Sie zuerst auf „PS mit Amazon abgleichen“: Das markiert, was von Amazon abweicht.',
    'it' => 'Niente da inviare. Premi prima «Sincronizza PS verso Amazon»: segnala ciò che differisce da Amazon.',
    'pl' => 'Nic do wysłania. Najpierw kliknij „Synchronizuj PS do Amazon”: to zaznaczy, co różni się od Amazon.',
  ),
  'The category mapping could not be saved.' => 
  array(
    'fr' => 'La correspondance de catégorie n\'a pas pu être enregistrée.',
    'es' => 'No se ha podido guardar la correspondencia de categoría.',
    'de' => 'Die Kategoriezuordnung konnte nicht gespeichert werden.',
    'it' => 'Non è stato possibile salvare la corrispondenza di categoria.',
    'pl' => 'Nie udało się zapisać mapowania kategorii.',
  ),
  'PHP\'s OpenSSL extension is not enabled on this server, so buyer replies cannot be read. Ask your host to enable it.' =>
  array(
    'fr' => 'L\'extension OpenSSL de PHP n\'est pas activée sur ce serveur, les réponses des acheteurs ne peuvent donc pas être lues. Demandez à votre hébergeur de l\'activer.',
    'es' => 'La extensión OpenSSL de PHP no está habilitada en este servidor, así que no se pueden leer las respuestas del comprador. Pide a tu proveedor que la habilite.',
    'de' => 'Die PHP-Erweiterung OpenSSL ist auf diesem Server nicht aktiviert, daher können Antworten der Käufer nicht gelesen werden. Bitten Sie Ihren Hoster, sie zu aktivieren.',
    'it' => 'L\'estensione PHP OpenSSL non è abilitata su questo server, quindi le risposte degli acquirenti non possono essere lette. Chiedi al tuo hosting di abilitarla.',
    'pl' => 'Rozszerzenie PHP OpenSSL nie jest włączone na tym serwerze, więc nie można odczytać odpowiedzi kupujących. Poproś hostingodawcę o jego włączenie.',
  ),
  'Inbound buyer messages are disabled in the module settings.' => 
  array(
    'fr' => 'La réception des messages des acheteurs est désactivée dans les paramètres du module.',
    'es' => 'Los mensajes entrantes de los compradores están desactivados en los ajustes del módulo.',
    'de' => 'Eingehende Käufernachrichten sind in den Moduleinstellungen deaktiviert.',
    'it' => 'I messaggi in arrivo degli acquirenti sono disattivati nelle impostazioni del modulo.',
    'pl' => 'Wiadomości przychodzące od kupujących są wyłączone w ustawieniach modułu.',
  ),
  'The mailbox host and user must be configured first.' => 
  array(
    'fr' => 'L\'hôte et l\'utilisateur de la boîte mail doivent d\'abord être configurés.',
    'es' => 'El host y el usuario del buzón deben configurarse primero.',
    'de' => 'Postfach-Host und -Benutzer müssen zuerst konfiguriert werden.',
    'it' => 'Devi prima configurare l\'host e l\'utente della casella.',
    'pl' => 'Najpierw skonfiguruj host i użytkownika skrzynki.',
  ),
  'Could not open the mailbox: %s' => 
  array(
    'fr' => 'Impossible d\'ouvrir la boîte mail : %s',
    'es' => 'No se ha podido abrir el buzón: %s',
    'de' => 'Das Postfach konnte nicht geöffnet werden: %s',
    'it' => 'Non è stato possibile aprire la casella: %s',
    'pl' => 'Nie udało się otworzyć skrzynki: %s',
  ),
  '%s: message received for an order this shop has not imported.' => 
  array(
    'fr' => '%s : message reçu pour une commande que cette boutique n\'a pas importée.',
    'es' => '%s: mensaje recibido para un pedido que esta tienda no ha importado.',
    'de' => '%s: Nachricht für eine Bestellung erhalten, die dieser Shop nicht importiert hat.',
    'it' => '%s: messaggio ricevuto per un ordine che questo negozio non ha importato.',
    'pl' => '%s: otrzymano wiadomość dotyczącą zamówienia, którego ten sklep nie zaimportował.',
  ),
  '%s: could not open a customer service thread.' => 
  array(
    'fr' => '%s : impossible d\'ouvrir un fil du Service client.',
    'es' => '%s: no se ha podido abrir un hilo del Servicio de atención al cliente.',
    'de' => '%s: Kundenservice-Thread konnte nicht geöffnet werden.',
    'it' => '%s: non è stato possibile aprire una discussione del servizio clienti.',
    'pl' => '%s: nie udało się otworzyć wątku Obsługi klienta.',
  ),
  'Order id and message type are required.' => 
  array(
    'fr' => 'Le numéro de commande et le type de message sont obligatoires.',
    'es' => 'El ID de pedido y el tipo de mensaje son obligatorios.',
    'de' => 'Bestellnummer und Nachrichtentyp sind erforderlich.',
    'it' => 'L\'ID dell\'ordine e il tipo di messaggio sono obbligatori.',
    'pl' => 'Numer zamówienia i typ wiadomości są wymagane.',
  ),
  'This message type requires a text body.' => 
  array(
    'fr' => 'Ce type de message doit comporter un texte.',
    'es' => 'Este tipo de mensaje requiere un cuerpo de texto.',
    'de' => 'Dieser Nachrichtentyp erfordert einen Nachrichtentext.',
    'it' => 'Questo tipo di messaggio richiede un testo.',
    'pl' => 'Ten typ wiadomości wymaga treści tekstowej.',
  ),
  'No operation selected.' => 
  array(
    'fr' => 'Aucune opération sélectionnée.',
    'es' => 'Ninguna operación seleccionada.',
    'de' => 'Keine Aktion ausgewählt.',
    'it' => 'Nessuna operazione selezionata.',
    'pl' => 'Nie wybrano żadnej operacji.',
  ),
  'Prices were converted from Amazon\'s tax-inclusive figures using each product\'s tax rule — check a few before relying on them.' => 
  array(
    'fr' => 'Les prix ont été convertis à partir des montants TTC d\'Amazon à l\'aide de la règle de taxe de chaque produit — vérifiez-en quelques-uns avant de vous y fier.',
    'es' => 'Los precios se han convertido a partir de las cifras de Amazon con impuestos incluidos, usando la regla fiscal de cada producto — revisa algunos antes de fiarte de ellos.',
    'de' => 'Die Preise wurden aus Amazons Bruttopreisen anhand der Steuerregel des jeweiligen Produkts umgerechnet — prüfen Sie einige, bevor Sie sich darauf verlassen.',
    'it' => 'I prezzi sono stati convertiti dagli importi IVA inclusa di Amazon usando la regola fiscale di ciascun prodotto — controllane alcuni prima di fidartene.',
    'pl' => 'Ceny zostały przeliczone z kwot brutto podanych przez Amazon według reguły podatkowej każdego produktu — sprawdź kilka z nich, zanim zaczniesz na nich polegać.',
  ),
  '%d product(s) deactivated because Amazon no longer carries them or shows zero stock. They were not deleted — re-enable them from the catalogue.' => 
  array(
    'fr' => '%d produit(s) désactivé(s) car Amazon ne les propose plus ou affiche un stock à zéro. Ils n\'ont pas été supprimés — réactivez-les depuis le catalogue.',
    'es' => '%d producto(s) desactivado(s) porque Amazon ya no los ofrece o muestra 0 existencias. No se han eliminado — vuelve a activarlos desde el catálogo.',
    'de' => '%d Produkt(e) deaktiviert, weil Amazon sie nicht mehr führt oder keinen Bestand mehr zeigt. Sie wurden nicht gelöscht — aktivieren Sie sie im Katalog wieder.',
    'it' => '%d prodotti disattivati perché Amazon non li offre più o mostra giacenza zero. Non sono stati eliminati: riattivali dal catalogo.',
    'pl' => 'Dezaktywowane produkty: %d — Amazon już ich nie oferuje lub pokazuje zerowy stan. Nie zostały usunięte: włącz je ponownie w katalogu.',
  ),
  'SKU %1$s: already exists as product #%2$d — linked.' => 
  array(
    'fr' => 'SKU %1$s : existe déjà en tant que produit #%2$d — lié.',
    'es' => 'SKU %1$s: ya existe como producto #%2$d — vinculado.',
    'de' => 'SKU %1$s: existiert bereits als Produkt #%2$d — verknüpft.',
    'it' => 'SKU %1$s: esiste già come prodotto #%2$d — collegato.',
    'pl' => 'SKU %1$s: istnieje już jako produkt nr %2$d — powiązano.',
  ),
  'SKU %1$s: %2$s' => 
  array(
    'fr' => 'SKU %1$s : %2$s',
    'es' => 'SKU %1$s: %2$s',
    'de' => 'SKU %1$s: %2$s',
    'it' => 'SKU %1$s: %2$s',
    'pl' => 'SKU %1$s: %2$s',
  ),
  'Amazon listing has no title.' => 
  array(
    'fr' => 'La fiche Amazon n\'a pas de titre.',
    'es' => 'El anuncio de Amazon no tiene título.',
    'de' => 'Das Amazon-Angebot hat keinen Titel.',
    'it' => 'L\'inserzione Amazon non ha un titolo.',
    'pl' => 'Oferta w Amazon nie ma tytułu.',
  ),
  'Could not create the product.' => 
  array(
    'fr' => 'Impossible de créer le produit.',
    'es' => 'No se ha podido crear el producto.',
    'de' => 'Das Produkt konnte nicht angelegt werden.',
    'it' => 'Non è stato possibile creare il prodotto.',
    'pl' => 'Nie udało się utworzyć produktu.',
  ),
  'More FBA inventory pages available. Run again to fetch more.' => 
  array(
    'fr' => 'D\'autres pages d\'inventaire FBA sont disponibles. Relancez pour en récupérer davantage.',
    'es' => 'Hay más páginas de inventario FBA disponibles. Vuelve a ejecutarlo para obtener más.',
    'de' => 'Weitere FBA-Bestandsseiten verfügbar. Erneut ausführen, um mehr abzurufen.',
    'it' => 'Altre pagine di inventario FBA disponibili. Esegui di nuovo per recuperarne altre.',
    'pl' => 'Dostępne są kolejne strony zapasów FBA. Uruchom ponownie, aby pobrać więcej.',
  ),
  'PrestaShop order #%d not found.' => 
  array(
    'fr' => 'Commande PrestaShop #%d introuvable.',
    'es' => 'Pedido de PrestaShop #%d no encontrado.',
    'de' => 'PrestaShop-Bestellung #%d nicht gefunden.',
    'it' => 'Ordine PrestaShop #%d non trovato.',
    'pl' => 'Nie znaleziono zamówienia PrestaShop nr %d.',
  ),
  'Delivery address not found for order #%d' => 
  array(
    'fr' => 'Adresse de livraison introuvable pour la commande #%d',
    'es' => 'Dirección de entrega no encontrada para el pedido #%d',
    'de' => 'Lieferadresse für Bestellung #%d nicht gefunden',
    'it' => 'Indirizzo di consegna non trovato per l\'ordine #%d',
    'pl' => 'Nie znaleziono adresu dostawy dla zamówienia nr %d',
  ),
  'No items in order #%d' => 
  array(
    'fr' => 'Aucun article dans la commande #%d',
    'es' => 'No hay artículos en el pedido #%d',
    'de' => 'Keine Artikel in Bestellung #%d',
    'it' => 'Nessun articolo nell\'ordine #%d',
    'pl' => 'Brak pozycji w zamówieniu nr %d',
  ),
  'SKU %1$s: Amazon holds %2$d in FBA stock, but %3$d were ordered.' => 
  array(
    'fr' => 'SKU %1$s : Amazon détient %2$d en stock FBA, mais %3$d ont été commandés.',
    'es' => 'SKU %1$s: Amazon tiene %2$d en el stock de FBA, pero se pidieron %3$d.',
    'de' => 'SKU %1$s: Amazon hält %2$d im FBA-Bestand, aber %3$d wurden bestellt.',
    'it' => 'SKU %1$s: Amazon ha %2$d unità in giacenza FBA, ma ne sono stati ordinati %3$d.',
    'pl' => 'SKU %1$s: Amazon ma na stanie FBA %2$d, ale zamówiono %3$d.',
  ),
  'No FBA-eligible items (no reference/SKU) in order #%d' => 
  array(
    'fr' => 'Aucun article éligible FBA (pas de référence/SKU) dans la commande #%d',
    'es' => 'No hay artículos aptos para FBA (sin referencia/SKU) en el pedido #%d',
    'de' => 'Keine FBA-fähigen Artikel (keine Artikelnummer/SKU) in Bestellung #%d',
    'it' => 'Nessun articolo idoneo per FBA (nessun riferimento/SKU) nell\'ordine #%d',
    'pl' => 'Brak pozycji kwalifikujących się do FBA (brak referencji/SKU) w zamówieniu nr %d',
  ),
  'Nothing to submit: no pending listing changes.' => 
  array(
    'fr' => 'Rien à envoyer : aucune modification d\'offre en attente.',
    'es' => 'No hay nada que enviar: no hay cambios pendientes en los anuncios.',
    'de' => 'Nichts zu übermitteln: keine ausstehenden Angebotsänderungen.',
    'it' => 'Niente da inviare: nessuna modifica alle inserzioni in sospeso.',
    'pl' => 'Nic do wysłania: brak oczekujących zmian ofert.',
  ),
  'Your seller ID is missing. Click "Connect to Amazon" in Settings > Connection to fill it in.' => 
  array(
    'fr' => 'Votre identifiant vendeur est manquant. Cliquez sur « Se connecter à Amazon » dans Paramètres > Connexion pour le renseigner.',
    'es' => 'Falta tu ID de vendedor. Pulsa «Conectar con Amazon» en Ajustes > Conexión para completarlo.',
    'de' => 'Ihre Verkäufer-ID fehlt. Klicken Sie unter Einstellungen > Verbindung auf „Mit Amazon verbinden“, um sie einzutragen.',
    'it' => 'Il tuo ID venditore manca. Fai clic su «Connetti ad Amazon» in Impostazioni > Connessione per completarlo.',
    'pl' => 'Brakuje Twojego identyfikatora sprzedawcy. Kliknij „Połącz z Amazon” w Ustawienia > Połączenie, aby go uzupełnić.',
  ),
  'No orders pending fee tracking.' => 
  array(
    'fr' => 'Aucune commande en attente de suivi des frais.',
    'es' => 'No hay pedidos pendientes de seguimiento de tarifas.',
    'de' => 'Keine Bestellungen für die Gebührenverfolgung ausstehend.',
    'it' => 'Nessun ordine in attesa del monitoraggio dei costi.',
    'pl' => 'Brak zamówień oczekujących na śledzenie opłat.',
  ),
  'No PrestaShop product with this SKU' => 
  array(
    'fr' => 'Aucun produit PrestaShop avec ce SKU',
    'es' => 'No hay ningún producto de PrestaShop con este SKU',
    'de' => 'Kein PrestaShop-Produkt mit dieser SKU',
    'it' => 'Nessun prodotto PrestaShop con questo SKU',
    'pl' => 'Brak produktu PrestaShop dla tego SKU',
  ),
  'PrestaShop product is inactive' => 
  array(
    'fr' => 'Produit PrestaShop inactif',
    'es' => 'El producto de PrestaShop está inactivo',
    'de' => 'PrestaShop-Produkt ist inaktiv',
    'it' => 'Il prodotto PrestaShop è inattivo',
    'pl' => 'Produkt PrestaShop jest nieaktywny',
  ),
  'PrestaShop product was deleted' => 
  array(
    'fr' => 'Produit PrestaShop supprimé',
    'es' => 'El producto de PrestaShop se eliminó',
    'de' => 'PrestaShop-Produkt wurde gelöscht',
    'it' => 'Il prodotto PrestaShop è stato eliminato',
    'pl' => 'Produkt PrestaShop został usunięty',
  ),
  'Marketplace ID is required.' => 
  array(
    'fr' => 'L\'identifiant de la place de marché est obligatoire.',
    'es' => 'El ID de marketplace es obligatorio.',
    'de' => 'Die Marktplatz-ID ist erforderlich.',
    'it' => 'L\'ID del marketplace è obbligatorio.',
    'pl' => 'Identyfikator marketplace\'u jest wymagany.',
  ),
  '%s: skipped (no matched products)' => 
  array(
    'fr' => '%s : ignoré (aucun produit rapproché)',
    'es' => '%s: omitido (sin productos emparejados)',
    'de' => '%s: übersprungen (keine zugeordneten Produkte)',
    'it' => '%s: saltato (nessun prodotto abbinato)',
    'pl' => '%s: pominięto (brak dopasowanych produktów)',
  ),
  '%1$s: moved to Pending Orders (%2$s)' => 
  array(
    'fr' => '%1$s : déplacée vers Commandes en attente (%2$s)',
    'es' => '%1$s: pasó a Pedidos pendientes (%2$s)',
    'de' => '%1$s: unter Ausstehende Bestellungen verschoben (%2$s)',
    'it' => '%1$s: spostato in Ordini in sospeso (%2$s)',
    'pl' => '%1$s: przeniesiono do Zamówień oczekujących (%2$s)',
  ),
  'No matched items for order %s' => 
  array(
    'fr' => 'Aucun article rapproché pour la commande %s',
    'es' => 'No hay artículos emparejados para el pedido %s',
    'de' => 'Keine zugeordneten Artikel für Bestellung %s',
    'it' => 'Nessun articolo abbinato per l\'ordine %s',
    'pl' => 'Brak dopasowanych pozycji dla zamówienia %s',
  ),
  'insufficient stock: %s' => 
  array(
    'fr' => 'stock insuffisant : %s',
    'es' => 'existencias insuficientes: %s',
    'de' => 'unzureichender Bestand: %s',
    'it' => 'giacenza insufficiente: %s',
    'pl' => 'niewystarczający stan: %s',
  ),
  'Could not create customer for %s' => 
  array(
    'fr' => 'Impossible de créer le client pour %s',
    'es' => 'No se ha podido crear el cliente para %s',
    'de' => 'Kunde für %s konnte nicht angelegt werden',
    'it' => 'Non è stato possibile creare il cliente per %s',
    'pl' => 'Nie udało się utworzyć klienta dla %s',
  ),
  'Could not create address for %s' => 
  array(
    'fr' => 'Impossible de créer l\'adresse pour %s',
    'es' => 'No se ha podido crear la dirección para %s',
    'de' => 'Adresse für %s konnte nicht angelegt werden',
    'it' => 'Non è stato possibile creare l\'indirizzo per %s',
    'pl' => 'Nie udało się utworzyć adresu dla %s',
  ),
  'Could not create cart for %s' => 
  array(
    'fr' => 'Impossible de créer le panier pour %s',
    'es' => 'No se ha podido crear el carrito para %s',
    'de' => 'Warenkorb für %s konnte nicht angelegt werden',
    'it' => 'Non è stato possibile creare il carrello per %s',
    'pl' => 'Nie udało się utworzyć koszyka dla %s',
  ),
  'Could not save order for %s' => 
  array(
    'fr' => 'Impossible d\'enregistrer la commande pour %s',
    'es' => 'No se ha podido guardar el pedido para %s',
    'de' => 'Bestellung für %s konnte nicht gespeichert werden',
    'it' => 'Non è stato possibile salvare l\'ordine per %s',
    'pl' => 'Nie udało się zapisać zamówienia dla %s',
  ),
  '%1$s: FBA order (fulfilled by Amazon) — PrestaShop order #%2$d' => 
  array(
    'fr' => '%1$s : commande FBA (expédiée par Amazon) — commande PrestaShop #%2$d',
    'es' => '%1$s: pedido FBA (gestionado por Amazon) — pedido de PrestaShop #%2$d',
    'de' => '%1$s: FBA-Bestellung (Versand durch Amazon) — PrestaShop-Bestellung #%2$d',
    'it' => '%1$s: ordine FBA (gestito da Amazon) — ordine PrestaShop #%2$d',
    'pl' => '%1$s: zamówienie FBA (realizowane przez Amazon) — zamówienie PrestaShop nr %2$d',
  ),
  '%1$s ordered %2$d, available %3$d' => 
  array(
    'fr' => '%1$s : %2$d commandé(s), %3$d disponible(s)',
    'es' => '%1$s: se pidieron %2$d, disponibles %3$d',
    'de' => '%1$s: %2$d bestellt, %3$d verfügbar',
    'it' => '%1$s: ordinati %2$d, disponibili %3$d',
    'pl' => '%1$s — zamówiono: %2$d, dostępne: %3$d',
  ),
  'Could not clear buyer data from the imported Amazon orders.' => 
  array(
    'fr' => 'Impossible d\'effacer les données acheteur des commandes Amazon importées.',
    'es' => 'No se han podido borrar los datos del comprador de los pedidos de Amazon importados.',
    'de' => 'Käuferdaten der importierten Amazon-Bestellungen konnten nicht gelöscht werden.',
    'it' => 'Non è stato possibile cancellare i dati dell\'acquirente dagli ordini Amazon importati.',
    'pl' => 'Nie udało się wyczyścić danych kupującego z zaimportowanych zamówień Amazon.',
  ),
  'No PrestaShop products with a reference (SKU) were found.' => 
  array(
    'fr' => 'Aucun produit PrestaShop avec une référence (SKU) n\'a été trouvé.',
    'es' => 'No se han encontrado productos de PrestaShop con referencia (SKU).',
    'de' => 'Keine PrestaShop-Produkte mit Artikelnummer (SKU) gefunden.',
    'it' => 'Nessun prodotto PrestaShop con riferimento (SKU) trovato.',
    'pl' => 'Nie znaleziono produktów PrestaShop z referencją (SKU).',
  ),
  'Amazon side skipped: your seller ID is missing. Click "Connect to Amazon" in Settings > Connection to fill it in.' => 
  array(
    'fr' => 'Partie Amazon ignorée : votre identifiant vendeur est manquant. Cliquez sur « Se connecter à Amazon » dans Paramètres > Connexion pour le renseigner.',
    'es' => 'Se omite la parte de Amazon: falta tu ID de vendedor. Pulsa «Conectar con Amazon» en Ajustes > Conexión para completarlo.',
    'de' => 'Amazon-Seite übersprungen: Ihre Verkäufer-ID fehlt. Klicken Sie unter Einstellungen > Verbindung auf „Mit Amazon verbinden“, um sie einzutragen.',
    'it' => 'Lato Amazon saltato: il tuo ID venditore manca. Fai clic su «Connetti ad Amazon» in Impostazioni > Connessione per completarlo.',
    'pl' => 'Pominięto stronę Amazon: brakuje Twojego identyfikatora sprzedawcy. Kliknij „Połącz z Amazon” w Ustawienia > Połączenie, aby go uzupełnić.',
  ),
  'No SKUs to check yet. Run "Sync PS to Amazon" first to collect your PrestaShop SKUs. Finding listings that exist only on Amazon is coming soon.' => 
  array(
    'fr' => 'Aucun SKU à vérifier pour le moment. Lancez d\'abord « Synchroniser PS vers Amazon » pour collecter vos SKU PrestaShop. La recherche des offres présentes uniquement sur Amazon arrive bientôt.',
    'es' => 'Todavía no hay SKU que comprobar. Ejecuta primero «Sincronizar PS con Amazon» para recopilar tus SKU de PrestaShop. Encontrar los anuncios que solo existen en Amazon llegará próximamente.',
    'de' => 'Noch keine SKUs zum Prüfen. Führen Sie zuerst „PS mit Amazon abgleichen“ aus, um Ihre PrestaShop-SKUs zu erfassen. Das Auffinden von Angeboten, die es nur bei Amazon gibt, kommt bald.',
    'it' => 'Nessuno SKU da controllare per ora. Esegui prima «Sincronizza PS verso Amazon» per raccogliere i tuoi SKU PrestaShop. La ricerca delle inserzioni presenti solo su Amazon è in arrivo.',
    'pl' => 'Brak SKU do sprawdzenia. Najpierw uruchom „Synchronizuj PS do Amazon”, aby zebrać SKU z PrestaShop. Wyszukiwanie ofert istniejących tylko w Amazon pojawi się wkrótce.',
  ),
  'Cannot list Amazon products: your seller ID is missing. Click "Connect to Amazon" in Settings > Connection to fill it in.' => 
  array(
    'fr' => 'Impossible de lister les produits Amazon : votre identifiant vendeur est manquant. Cliquez sur « Se connecter à Amazon » dans Paramètres > Connexion pour le renseigner.',
    'es' => 'No se pueden listar los productos de Amazon: falta tu ID de vendedor. Pulsa «Conectar con Amazon» en Ajustes > Conexión para completarlo.',
    'de' => 'Amazon-Produkte können nicht aufgelistet werden: Ihre Verkäufer-ID fehlt. Klicken Sie unter Einstellungen > Verbindung auf „Mit Amazon verbinden“, um sie einzutragen.',
    'it' => 'Non è possibile elencare i prodotti Amazon: il tuo ID venditore manca. Fai clic su «Connetti ad Amazon» in Impostazioni > Connessione per completarlo.',
    'pl' => 'Nie można wyświetlić listy produktów Amazon: brakuje Twojego identyfikatora sprzedawcy. Kliknij „Połącz z Amazon” w Ustawienia > Połączenie, aby go uzupełnić.',
  ),
  'Amazon returned no products for this seller/marketplace.' => 
  array(
    'fr' => 'Amazon n\'a renvoyé aucun produit pour ce vendeur/cette place de marché.',
    'es' => 'Amazon no ha devuelto ningún producto para este vendedor/marketplace.',
    'de' => 'Amazon hat keine Produkte für diesen Verkäufer/Marktplatz zurückgegeben.',
    'it' => 'Amazon non ha restituito prodotti per questo venditore/marketplace.',
    'pl' => 'Amazon nie zwrócił żadnych produktów dla tego sprzedawcy/marketplace\'u.',
  ),
  'Product #%d has variation combinations but no base reference (SKU) — pushed as standalone listings, not an Amazon variation family.' => 
  array(
    'fr' => 'Le produit #%d a des combinaisons de déclinaisons mais aucune référence (SKU) de base — envoyé sous forme d\'offres indépendantes, pas comme famille de déclinaisons Amazon.',
    'es' => 'El producto #%d tiene combinaciones de variación pero no tiene una referencia base (SKU) — se envía como anuncios independientes, no como una familia de variación de Amazon.',
    'de' => 'Produkt #%d hat Variationskombinationen, aber keine Basis-Artikelnummer (SKU) — wird als eigenständige Angebote gesendet, nicht als Amazon-Variationsfamilie.',
    'it' => 'Il prodotto #%d ha combinazioni di varianti ma nessun riferimento di base (SKU) — pubblicato come inserzioni indipendenti, non come famiglia di varianti Amazon.',
    'pl' => 'Produkt nr %d ma kombinacje wariantów, ale nie ma referencji bazowej (SKU) — wysłano jako osobne oferty, a nie rodzinę wariantów Amazon.',
  ),
  '%d product(s) excluded by export filters (price min/max, quantity min, or sync switched off).' => 
  array(
    'fr' => '%d produit(s) exclu(s) par les filtres d\'export (prix min/max, quantité min, ou synchronisation désactivée).',
    'es' => '%d producto(s) excluido(s) por los filtros de exportación (precio mínimo/máximo, cantidad mínima o sincronización desactivada).',
    'de' => '%d Produkt(e) durch Exportfilter ausgeschlossen (Preis min./max., Mindestmenge oder Abgleich ausgeschaltet).',
    'it' => '%d prodotti esclusi dai filtri di esportazione (prezzo minimo/massimo, quantità minima o sincronizzazione disattivata).',
    'pl' => 'Produkty wykluczone przez filtry eksportu (cena min./maks., ilość min. lub wyłączona synchronizacja): %d.',
  ),
  'Delta export: only products updated in the last %d hour(s) or queued by rule changes were collected.' => 
  array(
    'fr' => 'Export différentiel : seuls les produits modifiés au cours des %d dernière(s) heure(s) ou mis en file d\'attente par un changement de règle ont été collectés.',
    'es' => 'Exportación diferencial: solo se recopilaron los productos modificados en las últimas %d hora(s) o encolados por un cambio de reglas.',
    'de' => 'Delta-Export: Es wurden nur Produkte erfasst, die in den letzten %d Stunde(n) aktualisiert wurden oder durch Regeländerungen in die Warteschlange kamen.',
    'it' => 'Esportazione differenziale: sono stati raccolti solo i prodotti modificati nelle ultime %d ore o messi in coda da una modifica delle regole.',
    'pl' => 'Eksport różnicowy: zebrano tylko produkty zmienione w ciągu ostatnich %d godz. lub dodane do kolejki przez zmianę reguł.',
  ),
  'Nothing to push. Run "Sync PS to Amazon" first, and make sure some rows are marked PS only or Conflict.' => 
  array(
    'fr' => 'Rien à envoyer. Lancez d\'abord « Synchroniser PS vers Amazon », et assurez-vous que des lignes soient marquées PS uniquement ou Conflit.',
    'es' => 'No hay nada que enviar. Ejecuta primero «Sincronizar PS con Amazon» y comprueba que algunas filas estén marcadas como Solo en PS o Conflicto.',
    'de' => 'Nichts zu übertragen. Führen Sie zuerst „PS mit Amazon abgleichen“ aus, und stellen Sie sicher, dass einige Zeilen als Nur PS oder Konflikt markiert sind.',
    'it' => 'Niente da inviare. Esegui prima «Sincronizza PS verso Amazon» e assicurati che alcune righe siano contrassegnate come Solo PS o Conflitto.',
    'pl' => 'Nic do wysłania. Najpierw uruchom „Synchronizuj PS do Amazon” i upewnij się, że niektóre wiersze są oznaczone jako Tylko w PS lub Konflikt.',
  ),
  'Note: "Export only products with ASIN" is enabled.' => 
  array(
    'fr' => 'Remarque : « N\'exporter que les produits avec un ASIN » est activé.',
    'es' => 'Nota: «Exportar solo los productos con ASIN» está activado.',
    'de' => 'Hinweis: „Nur Produkte mit ASIN exportieren“ ist aktiviert.',
    'it' => 'Nota: «Esporta solo i prodotti con ASIN» è attiva.',
    'pl' => 'Uwaga: włączona jest opcja „Eksportuj tylko produkty z ASIN”.',
  ),
  'Out of stock: listing deletion requested.' => 
  array(
    'fr' => 'Rupture de stock : suppression de l\'offre demandée.',
    'es' => 'Agotado: eliminación del anuncio solicitada.',
    'de' => 'Nicht auf Lager: Löschung des Angebots angefordert.',
    'it' => 'Esaurito: eliminazione dell\'inserzione richiesta.',
    'pl' => 'Brak towaru: zażądano usunięcia oferty.',
  ),
  'PrestaShop product is disabled' => 
  array(
    'fr' => 'Produit PrestaShop désactivé',
    'es' => 'El producto de PrestaShop está desactivado',
    'de' => 'PrestaShop-Produkt ist deaktiviert',
    'it' => 'Il prodotto PrestaShop è disattivato',
    'pl' => 'Produkt PrestaShop jest wyłączony',
  ),
  'Excluded from Amazon sync on the product' => 
  array(
    'fr' => 'Exclu de la synchronisation Amazon sur le produit',
    'es' => 'Excluido de la sincronización con Amazon en el producto',
    'de' => 'Am Produkt vom Amazon-Abgleich ausgeschlossen',
    'it' => 'Escluso dalla sincronizzazione con Amazon sul prodotto',
    'pl' => 'Wykluczony z synchronizacji z Amazon na karcie produktu',
  ),
  'Variation parent needs a category mapping (Amazon product type). Map category #%d first.' => 
  array(
    'fr' => 'Le parent de la déclinaison a besoin d\'une correspondance de catégorie (type de produit Amazon). Faites d\'abord correspondre la catégorie #%d.',
    'es' => 'El producto padre de la variación necesita una correspondencia de categoría (tipo de producto de Amazon). Primero, haz corresponder la categoría #%d.',
    'de' => 'Übergeordnete Variation benötigt eine Kategoriezuordnung (Amazon-Produkttyp). Ordnen Sie zuerst Kategorie #%d zu.',
    'it' => 'Il prodotto padre della variante ha bisogno di una corrispondenza di categoria (tipo di prodotto Amazon). Associa prima la categoria #%d.',
    'pl' => 'Wariant nadrzędny wymaga mapowania kategorii (typu produktu Amazon). Najpierw zmapuj kategorię nr %d.',
  ),
  'Extra attributes must be a valid JSON object.' => 
  array(
    'fr' => 'Les attributs supplémentaires doivent être un objet JSON valide.',
    'es' => 'Los atributos adicionales deben ser un objeto JSON válido.',
    'de' => 'Zusätzliche Attribute müssen ein gültiges JSON-Objekt sein.',
    'it' => 'Gli attributi aggiuntivi devono essere un oggetto JSON valido.',
    'pl' => 'Dodatkowe atrybuty muszą być poprawnym obiektem JSON.',
  ),
  'No product type given.' => 
  array(
    'fr' => 'Aucun type de produit indiqué.',
    'es' => 'No se ha indicado ningún tipo de producto.',
    'de' => 'Kein Produkttyp angegeben.',
    'it' => 'Nessun tipo di prodotto specificato.',
    'pl' => 'Nie podano typu produktu.',
  ),
  'Amazon did not send the list of fields for product type %s.' => 
  array(
    'fr' => 'Amazon n\'a pas envoyé la liste des champs pour le type de produit %s.',
    'es' => 'Amazon no ha enviado la lista de campos del tipo de producto %s.',
    'de' => 'Amazon hat die Feldliste für den Produkttyp %s nicht gesendet.',
    'it' => 'Amazon non ha inviato l\'elenco dei campi per il tipo di prodotto %s.',
    'pl' => 'Amazon nie przesłał listy pól dla typu produktu %s.',
  ),
  'Could not download the list of fields for this product type from Amazon: %s' => 
  array(
    'fr' => 'Impossible de télécharger la liste des champs de ce type de produit depuis Amazon : %s',
    'es' => 'No se ha podido descargar de Amazon la lista de campos de este tipo de producto: %s',
    'de' => 'Die Feldliste für diesen Produkttyp konnte nicht von Amazon heruntergeladen werden: %s',
    'it' => 'Non è stato possibile scaricare l\'elenco dei campi per questo tipo di prodotto da Amazon: %s',
    'pl' => 'Nie udało się pobrać z Amazon listy pól dla tego typu produktu: %s',
  ),
  'The list of fields downloaded from Amazon could not be read. Please try again.' => 
  array(
    'fr' => 'La liste des champs téléchargée depuis Amazon n\'a pas pu être lue. Réessayez.',
    'es' => 'No se ha podido leer la lista de campos descargada de Amazon. Inténtalo de nuevo.',
    'de' => 'Die von Amazon heruntergeladene Feldliste konnte nicht gelesen werden. Bitte versuchen Sie es erneut.',
    'it' => 'Non è stato possibile leggere l\'elenco dei campi scaricato da Amazon. Riprova.',
    'pl' => 'Nie udało się odczytać listy pól pobranej z Amazon. Spróbuj ponownie.',
  ),
  'Product name' => 
  array(
    'fr' => 'Nom du produit',
    'es' => 'Nombre del producto',
    'de' => 'Produktname',
    'it' => 'Nome del prodotto',
    'pl' => 'Nazwa produktu',
  ),
  'Description' => 
  array(
    'fr' => 'Description',
    'es' => 'Descripción',
    'de' => 'Beschreibung',
    'it' => 'Descrizione',
    'pl' => 'Opis',
  ),
  'Short description' => 
  array(
    'fr' => 'Description courte',
    'es' => 'Descripción corta',
    'de' => 'Kurzbeschreibung',
    'it' => 'Descrizione breve',
    'pl' => 'Krótki opis',
  ),
  'Brand / Manufacturer' => 
  array(
    'fr' => 'Marque / Fabricant',
    'es' => 'Marca / Fabricante',
    'de' => 'Marke / Hersteller',
    'it' => 'Marca / Produttore',
    'pl' => 'Marka / producent',
  ),
  'Supplier' => 
  array(
    'fr' => 'Fournisseur',
    'es' => 'Proveedor',
    'de' => 'Lieferant',
    'it' => 'Fornitore',
    'pl' => 'Dostawca',
  ),
  'Reference (SKU)' => 
  array(
    'fr' => 'Référence (SKU)',
    'es' => 'Referencia (SKU)',
    'de' => 'Artikelnummer (SKU)',
    'it' => 'Riferimento (SKU)',
    'pl' => 'Referencja (SKU)',
  ),
  'EAN / barcode' => 
  array(
    'fr' => 'EAN / code-barres',
    'es' => 'EAN / código de barras',
    'de' => 'EAN / Barcode',
    'it' => 'EAN / codice a barre',
    'pl' => 'EAN / kod kreskowy',
  ),
  'UPC' => 
  array(
    'fr' => 'Code UPC',
    'es' => 'Código UPC',
    'de' => 'UPC-Code',
    'it' => 'Codice UPC',
    'pl' => 'Kod UPC',
  ),
  'Width' => 
  array(
    'fr' => 'Largeur',
    'es' => 'Ancho',
    'de' => 'Breite',
    'it' => 'Larghezza',
    'pl' => 'Szerokość',
  ),
  'Height' => 
  array(
    'fr' => 'Hauteur',
    'es' => 'Alto',
    'de' => 'Höhe',
    'it' => 'Altezza',
    'pl' => 'Wysokość',
  ),
  'Depth' => 
  array(
    'fr' => 'Profondeur',
    'es' => 'Profundidad',
    'de' => 'Tiefe',
    'it' => 'Profondità',
    'pl' => 'Głębokość',
  ),
  'Default category name' => 
  array(
    'fr' => 'Nom de la catégorie par défaut',
    'es' => 'Nombre de categoría predeterminada',
    'de' => 'Name der Standard-Kategorie',
    'it' => 'Nome della categoria predefinita',
    'pl' => 'Domyślna nazwa kategorii',
  ),
  'No orders with promotion discounts found.' => 
  array(
    'fr' => 'Aucune commande avec remise promotionnelle trouvée.',
    'es' => 'No se han encontrado pedidos con descuentos de promoción.',
    'de' => 'Keine Bestellungen mit Aktionsrabatten gefunden.',
    'it' => 'Nessun ordine con sconti promozionali trovato.',
    'pl' => 'Nie znaleziono zamówień z rabatami promocyjnymi.',
  ),
  '%d PrestaShop cart rule(s) exported to promotion tracking. They are not created on Amazon: manage your Amazon promotions in Seller Central.' => 
  array(
    'fr' => '%d règle(s) panier PrestaShop exportée(s) vers le suivi des promotions. Elles ne sont pas créées sur Amazon : gérez vos promotions Amazon dans Seller Central.',
    'es' => '%d regla(s) de carrito de PrestaShop exportada(s) para el seguimiento de promociones. No se crean en Amazon: gestiona tus promociones de Amazon en Seller Central.',
    'de' => '%d PrestaShop-Warenkorbregel(n) zur Aktionsverfolgung exportiert. Sie werden nicht bei Amazon angelegt: Verwalten Sie Ihre Amazon-Aktionen in Seller Central.',
    'it' => '%d regole del carrello PrestaShop esportate per il monitoraggio delle promozioni. Non vengono create su Amazon: gestisci le tue promozioni Amazon in Seller Central.',
    'pl' => 'Reguły koszyka PrestaShop wyeksportowane do śledzenia promocji: %d. Nie są tworzone w Amazon: zarządzaj promocjami Amazon w Seller Central.',
  ),
  'The file has no data rows.' => 
  array(
    'fr' => 'Le fichier ne contient aucune ligne de données.',
    'es' => 'El archivo no tiene filas de datos.',
    'de' => 'Die Datei enthält keine Datenzeilen.',
    'it' => 'Il file non contiene righe di dati.',
    'pl' => 'Plik nie zawiera wierszy z danymi.',
  ),
  'The first column must be \'key\' — export a fresh file and edit that.' => 
  array(
    'fr' => 'La première colonne doit être \'key\' — exportez un nouveau fichier et modifiez celui-ci.',
    'es' => 'La primera columna debe ser \'key\' — exporta un archivo nuevo y edítalo.',
    'de' => 'Die erste Spalte muss \'key\' sein — exportieren Sie eine neue Datei und bearbeiten Sie diese.',
    'it' => 'La prima colonna deve essere \'key\' — esporta un file nuovo e modifica quello.',
    'pl' => 'Pierwsza kolumna musi nazywać się „key” — wyeksportuj nowy plik i edytuj go.',
  ),
  'Line %1$d: reference \'%2$s\' is already used on row %3$s.' => 
  array(
    'fr' => 'Ligne %1$d : la référence \'%2$s\' est déjà utilisée à la ligne %3$s.',
    'es' => 'Línea %1$d: la referencia \'%2$s\' ya se usa en la fila %3$s.',
    'de' => 'Zeile %1$d: Artikelnummer \'%2$s\' wird bereits in Zeile %3$s verwendet.',
    'it' => 'Riga %1$d: il riferimento \'%2$s\' è già usato alla riga %3$s.',
    'pl' => 'Wiersz %1$d: referencja „%2$s” jest już użyta w wierszu %3$s.',
  ),
  'Line %1$d: could not update %2$s.' => 
  array(
    'fr' => 'Ligne %1$d : impossible de mettre à jour %2$s.',
    'es' => 'Línea %1$d: no se ha podido actualizar %2$s.',
    'de' => 'Zeile %1$d: %2$s konnte nicht aktualisiert werden.',
    'it' => 'Riga %1$d: non è stato possibile aggiornare %2$s.',
    'pl' => 'Wiersz %1$d: nie udało się zaktualizować %2$s.',
  ),
  'This shop has no cron token yet. Save the settings once and try again.' => 
  array(
    'fr' => 'Cette boutique n\'a pas encore de jeton cron. Enregistrez les paramètres une fois, puis réessayez.',
    'es' => 'Esta tienda todavía no tiene token de cron. Guarda los ajustes una vez y vuelve a intentarlo.',
    'de' => 'Dieser Shop hat noch keinen Cron-Token. Speichern Sie die Einstellungen einmal und versuchen Sie es erneut.',
    'it' => 'Questo negozio non ha ancora un token cron. Salva le impostazioni una volta e riprova.',
    'pl' => 'Ten sklep nie ma jeszcze tokenu crona. Zapisz ustawienia raz i spróbuj ponownie.',
  ),
  'The scheduler needs the shop to be reachable over HTTPS, and this shop\'s address is %s. Enable SSL in Shop Parameters > General, then register again.' => 
  array(
    'fr' => 'Le planificateur a besoin que la boutique soit joignable en HTTPS, or l\'adresse de cette boutique est %s. Activez le SSL dans Paramètres de la boutique > Paramètres généraux, puis enregistrez à nouveau.',
    'es' => 'El programador necesita que la tienda sea accesible por HTTPS, y la dirección de esta tienda es %s. Activa SSL en Parámetros de la tienda > Configuración y vuelve a registrarla.',
    'de' => 'Der Scheduler benötigt, dass der Shop über HTTPS erreichbar ist, und die Adresse dieses Shops lautet %s. Aktivieren Sie SSL unter Shop-Einstellungen > Allgemein und registrieren Sie sich dann erneut.',
    'it' => 'Lo scheduler richiede che il negozio sia raggiungibile via HTTPS, e l\'indirizzo di questo negozio è %s. Attiva SSL in Parametri Negozio > Generale, poi registra di nuovo.',
    'pl' => 'Harmonogram wymaga, aby sklep był dostępny przez HTTPS, a adres tego sklepu to %s. Włącz SSL w Preferencje > Ogólny, a potem zarejestruj się ponownie.',
  ),
  'This server cannot reach the scheduler because the PHP cURL extension is missing. Ask your hosting provider to enable it.' => 
  array(
    'fr' => 'Ce serveur ne peut pas joindre le planificateur car l\'extension PHP cURL est manquante. Demandez à votre hébergeur de l\'activer.',
    'es' => 'Este servidor no puede conectar con el programador porque falta la extensión cURL de PHP. Pide a tu proveedor de alojamiento que la active.',
    'de' => 'Dieser Server kann den Scheduler nicht erreichen, weil die PHP-cURL-Erweiterung fehlt. Bitten Sie Ihren Hosting-Anbieter, sie zu aktivieren.',
    'it' => 'Questo server non riesce a raggiungere lo scheduler perché manca l\'estensione PHP cURL. Chiedi al tuo provider di hosting di abilitarla.',
    'pl' => 'Ten serwer nie może połączyć się z harmonogramem, ponieważ brakuje rozszerzenia PHP cURL. Poproś dostawcę hostingu o jego włączenie.',
  ),
  'Could not reach the scheduler: %s' => 
  array(
    'fr' => 'Impossible de joindre le planificateur : %s',
    'es' => 'No se ha podido conectar con el programador: %s',
    'de' => 'Der Scheduler konnte nicht erreicht werden: %s',
    'it' => 'Non è stato possibile raggiungere lo scheduler: %s',
    'pl' => 'Nie udało się połączyć z harmonogramem: %s',
  ),
  'The scheduler answered with HTTP %d.' => 
  array(
    'fr' => 'Le planificateur a répondu avec le code HTTP %d.',
    'es' => 'El programador ha respondido con HTTP %d.',
    'de' => 'Der Scheduler hat mit HTTP %d geantwortet.',
    'it' => 'Lo scheduler ha risposto con HTTP %d.',
    'pl' => 'Harmonogram odpowiedział kodem HTTP %d.',
  ),
  'No products with ASINs found. Sync products first.' => 
  array(
    'fr' => 'Aucun produit avec un ASIN trouvé. Synchronisez d\'abord les produits.',
    'es' => 'No se han encontrado productos con ASIN. Sincroniza primero los productos.',
    'de' => 'Keine Produkte mit ASINs gefunden. Gleichen Sie zuerst die Produkte ab.',
    'it' => 'Nessun prodotto con ASIN trovato. Sincronizza prima i prodotti.',
    'pl' => 'Nie znaleziono produktów z ASIN. Najpierw zsynchronizuj produkty.',
  ),
  'No active pricing rules found.' => 
  array(
    'fr' => 'Aucune règle tarifaire active trouvée.',
    'es' => 'No se han encontrado reglas de precio activas.',
    'de' => 'Keine aktiven Preisregeln gefunden.',
    'it' => 'Nessuna regola di prezzo attiva trovata.',
    'pl' => 'Nie znaleziono aktywnych reguł cenowych.',
  ),
  'Cannot push prices: your seller ID is missing. Click "Connect to Amazon" in Settings > Connection to fill it in.' => 
  array(
    'fr' => 'Impossible d\'envoyer les prix : votre identifiant vendeur est manquant. Cliquez sur « Se connecter à Amazon » dans Paramètres > Connexion pour le renseigner.',
    'es' => 'No se pueden enviar los precios: falta tu ID de vendedor. Pulsa «Conectar con Amazon» en Ajustes > Conexión para completarlo.',
    'de' => 'Preise können nicht übertragen werden: Ihre Verkäufer-ID fehlt. Klicken Sie unter Einstellungen > Verbindung auf „Mit Amazon verbinden“, um sie einzutragen.',
    'it' => 'Non è possibile inviare i prezzi: il tuo ID venditore manca. Fai clic su «Connetti ad Amazon» in Impostazioni > Connessione per completarlo.',
    'pl' => 'Nie można wysłać cen: brakuje Twojego identyfikatora sprzedawcy. Kliknij „Połącz z Amazon” w Ustawienia > Połączenie, aby go uzupełnić.',
  ),
  'Return #%1$d: PrestaShop order #%2$d not found' => 
  array(
    'fr' => 'Retour #%1$d : commande PrestaShop #%2$d introuvable',
    'es' => 'Devolución #%1$d: pedido de PrestaShop #%2$d no encontrado',
    'de' => 'Retoure #%1$d: PrestaShop-Bestellung #%2$d nicht gefunden',
    'it' => 'Reso #%1$d: ordine PrestaShop #%2$d non trovato',
    'pl' => 'Zwrot nr %1$d: nie znaleziono zamówienia PrestaShop nr %2$d',
  ),
  'Order #%1$d (Amazon: %2$s) cancelled' => 
  array(
    'fr' => 'Commande #%1$d (Amazon : %2$s) annulée',
    'es' => 'Pedido #%1$d (Amazon: %2$s) cancelado',
    'de' => 'Bestellung #%1$d (Amazon: %2$s) storniert',
    'it' => 'Ordine #%1$d (Amazon: %2$s) annullato',
    'pl' => 'Zamówienie nr %1$d (Amazon: %2$s) anulowane',
  ),
  'Credit slip #%1$d created for order #%2$d' => 
  array(
    'fr' => 'Avoir #%1$d créé pour la commande #%2$d',
    'es' => 'Factura por abono #%1$d creada para el pedido #%2$d',
    'de' => 'Gutschrift #%1$d für Bestellung #%2$d erstellt',
    'it' => 'Nota di credito #%1$d creata per l\'ordine #%2$d',
    'pl' => 'Utworzono potwierdzenie zwrotu nr %1$d dla zamówienia nr %2$d',
  ),
  'Return processed for order #%d (no credit slip — partial return)' => 
  array(
    'fr' => 'Retour traité pour la commande #%d (pas d\'avoir — retour partiel)',
    'es' => 'Devolución procesada para el pedido #%d (sin factura por abono — devolución parcial)',
    'de' => 'Retoure für Bestellung #%d verarbeitet (keine Gutschrift — Teilretoure)',
    'it' => 'Reso elaborato per l\'ordine #%d (nessuna nota di credito — reso parziale)',
    'pl' => 'Przetworzono zwrot dla zamówienia nr %d (bez potwierdzenia zwrotu — zwrot częściowy)',
  ),
  'Order id is required.' => 
  array(
    'fr' => 'Le numéro de commande est obligatoire.',
    'es' => 'El ID de pedido es obligatorio.',
    'de' => 'Die Bestellnummer ist erforderlich.',
    'it' => 'L\'ID dell\'ordine è obbligatorio.',
    'pl' => 'Numer zamówienia jest wymagany.',
  ),
  'Amazon does not allow a review request for this order (already sent, outside the 5-30 day window, or buyer opted out).' => 
  array(
    'fr' => 'Amazon n\'autorise pas de demande d\'avis pour cette commande (déjà envoyée, hors de la fenêtre de 5 à 30 jours, ou l\'acheteur s\'y est opposé).',
    'es' => 'Amazon no permite solicitar una valoración para este pedido (ya enviada, fuera del plazo de 5 a 30 días, o el comprador ha optado por no recibirlas).',
    'de' => 'Amazon lässt für diese Bestellung keine Bewertungsanfrage zu (bereits gesendet, außerhalb des Zeitfensters von 5 bis 30 Tagen, oder der Käufer hat widersprochen).',
    'it' => 'Amazon non consente una richiesta di recensione per questo ordine (già inviata, fuori dalla finestra di 5-30 giorni o l\'acquirente ha rifiutato le comunicazioni).',
    'pl' => 'Amazon nie pozwala wysłać prośby o opinię dla tego zamówienia (już wysłana, poza oknem 5-30 dni albo kupujący zrezygnował).',
  ),
  'No such task.' => 
  array(
    'fr' => 'Tâche introuvable.',
    'es' => 'No existe esa tarea.',
    'de' => 'Aufgabe nicht gefunden.',
    'it' => 'Nessuna attività di questo tipo.',
    'pl' => 'Nie znaleziono takiego zadania.',
  ),
  'Not connected to Amazon yet. Use the "Connect to Amazon" button in the module settings.' => 
  array(
    'fr' => 'Pas encore connecté à Amazon. Utilisez le bouton « Se connecter à Amazon » dans les paramètres du module.',
    'es' => 'Aún no hay conexión con Amazon. Usa el botón «Conectar con Amazon» en los ajustes del módulo.',
    'de' => 'Noch nicht mit Amazon verbunden. Verwenden Sie die Schaltfläche „Mit Amazon verbinden“ in den Moduleinstellungen.',
    'it' => 'Non ancora connesso ad Amazon. Usa il pulsante «Connetti ad Amazon» nelle impostazioni del modulo.',
    'pl' => 'Jeszcze nie połączono z Amazon. Użyj przycisku „Połącz z Amazon” w ustawieniach modułu.',
  ),
  'The file downloaded from Amazon could not be unpacked.' => 
  array(
    'fr' => 'Le fichier téléchargé depuis Amazon n\'a pas pu être décompressé.',
    'es' => 'No se ha podido descomprimir el archivo descargado de Amazon.',
    'de' => 'Die von Amazon heruntergeladene Datei konnte nicht entpackt werden.',
    'it' => 'Non è stato possibile decomprimere il file scaricato da Amazon.',
    'pl' => 'Nie udało się rozpakować pliku pobranego z Amazon.',
  ),
  'This server cannot connect to Amazon because the PHP cURL extension is missing. Ask your hosting provider to enable it.' => 
  array(
    'fr' => 'Ce serveur ne peut pas se connecter à Amazon car l\'extension PHP cURL est manquante. Demandez à votre hébergeur de l\'activer.',
    'es' => 'Este servidor no puede conectar con Amazon porque falta la extensión cURL de PHP. Pide a tu proveedor de alojamiento que la active.',
    'de' => 'Dieser Server kann keine Verbindung zu Amazon herstellen, weil die PHP-cURL-Erweiterung fehlt. Bitten Sie Ihren Hosting-Anbieter, sie zu aktivieren.',
    'it' => 'Questo server non riesce a connettersi ad Amazon perché manca l\'estensione PHP cURL. Chiedi al tuo provider di hosting di abilitarla.',
    'pl' => 'Ten serwer nie może połączyć się z Amazon, ponieważ brakuje rozszerzenia PHP cURL. Poproś dostawcę hostingu o jego włączenie.',
  ),
  'Skipped: automatic review requests are disabled in module settings.' => 
  array(
    'fr' => 'Ignoré : les demandes d\'avis automatiques sont désactivées dans les paramètres du module.',
    'es' => 'Omitido: las solicitudes de valoración automáticas están desactivadas en los ajustes del módulo.',
    'de' => 'Übersprungen: automatische Bewertungsanfragen sind in den Moduleinstellungen deaktiviert.',
    'it' => 'Saltato: le richieste di recensione automatiche sono disattivate nelle impostazioni del modulo.',
    'pl' => 'Pominięto: automatyczne prośby o opinię są wyłączone w ustawieniach modułu.',
  ),
  'Skipped: VCS invoice upload is disabled in module settings.' => 
  array(
    'fr' => 'Ignoré : l\'envoi des factures VCS est désactivé dans les paramètres du module.',
    'es' => 'Omitido: la subida de facturas VCS está desactivada en los ajustes del módulo.',
    'de' => 'Übersprungen: der VCS-Rechnungsupload ist in den Moduleinstellungen deaktiviert.',
    'it' => 'Saltato: il caricamento fatture VCS è disattivato nelle impostazioni del modulo.',
    'pl' => 'Pominięto: przesyłanie faktur VCS jest wyłączone w ustawieniach modułu.',
  ),
  'This shop is not connected to Amazon yet. Use the "Connect to Amazon" button in the module settings.' => 
  array(
    'fr' => 'Cette boutique n\'est pas encore connectée à Amazon. Utilisez le bouton « Se connecter à Amazon » dans les paramètres du module.',
    'es' => 'Esta tienda aún no está conectada con Amazon. Usa el botón «Conectar con Amazon» en los ajustes del módulo.',
    'de' => 'Dieser Shop ist noch nicht mit Amazon verbunden. Verwenden Sie die Schaltfläche „Mit Amazon verbinden“ in den Moduleinstellungen.',
    'it' => 'Questo negozio non è ancora connesso ad Amazon. Usa il pulsante «Connetti ad Amazon» nelle impostazioni del modulo.',
    'pl' => 'Ten sklep nie jest jeszcze połączony z Amazon. Użyj przycisku „Połącz z Amazon” w ustawieniach modułu.',
  ),
  'Connection failed' => 
  array(
    'fr' => 'Échec de la connexion',
    'es' => 'Error de conexión',
    'de' => 'Verbindung fehlgeschlagen',
    'it' => 'Connessione non riuscita',
    'pl' => 'Połączenie nieudane',
  ),
  'This connection link is invalid or has expired. Please go back to your PrestaShop admin and click "Connect to Amazon" again.' => 
  array(
    'fr' => 'Ce lien de connexion n\'est plus valide ou a expiré. Retournez dans votre back-office PrestaShop et cliquez de nouveau sur « Se connecter à Amazon ».',
    'es' => 'Este enlace de conexión no es válido o ha caducado. Vuelve a la administración de PrestaShop y pulsa «Conectar con Amazon» de nuevo.',
    'de' => 'Dieser Verbindungslink ist ungültig oder abgelaufen. Bitte kehren Sie zu Ihrem PrestaShop-Backoffice zurück und klicken Sie erneut auf „Mit Amazon verbinden“.',
    'it' => 'Questo link di connessione non è valido o è scaduto. Torna al pannello di amministrazione di PrestaShop e fai di nuovo clic su «Connetti ad Amazon».',
    'pl' => 'Ten link połączenia jest nieprawidłowy albo wygasł. Wróć do panelu administracyjnego PrestaShop i kliknij ponownie „Połącz z Amazon”.',
  ),
  'Amazon connection was not completed' => 
  array(
    'fr' => 'La connexion à Amazon n\'a pas abouti',
    'es' => 'La conexión con Amazon no se ha completado',
    'de' => 'Amazon-Verbindung wurde nicht abgeschlossen',
    'it' => 'Connessione ad Amazon non completata',
    'pl' => 'Połączenie z Amazon nie zostało zakończone',
  ),
  'Amazon reported: %s — You can retry from the module settings.' => 
  array(
    'fr' => 'Amazon a signalé : %s — Vous pouvez réessayer depuis les paramètres du module.',
    'es' => 'Amazon indicó: %s — puedes volver a intentarlo desde los ajustes del módulo.',
    'de' => 'Amazon hat gemeldet: %s — Sie können es über die Moduleinstellungen erneut versuchen.',
    'it' => 'Amazon ha comunicato: %s — Puoi riprovare dalle impostazioni del modulo.',
    'pl' => 'Amazon zgłosił: %s — możesz spróbować ponownie w ustawieniach modułu.',
  ),
  'No valid token was received from Amazon. Please retry from the module settings.' => 
  array(
    'fr' => 'Aucun jeton valide n\'a été reçu d\'Amazon. Veuillez réessayer depuis les paramètres du module.',
    'es' => 'No se ha recibido ningún token válido de Amazon. Vuelve a intentarlo desde los ajustes del módulo.',
    'de' => 'Es wurde kein gültiges Token von Amazon empfangen. Bitte versuchen Sie es über die Moduleinstellungen erneut.',
    'it' => 'Nessun token valido ricevuto da Amazon. Riprova dalle impostazioni del modulo.',
    'pl' => 'Nie otrzymano prawidłowego tokenu od Amazon. Spróbuj ponownie w ustawieniach modułu.',
  ),
  'Your shop is now connected to Amazon (seller %s). You can close this tab and return to the Amazon Marketplace Pro settings in your shop admin.' => 
  array(
    'fr' => 'Votre boutique est maintenant connectée à Amazon (vendeur %s). Vous pouvez fermer cet onglet et retourner aux paramètres d\'Amazon Marketplace Pro dans le back-office de votre boutique.',
    'es' => 'Tu tienda ya está conectada con Amazon (vendedor %s). Puedes cerrar esta pestaña y volver a los ajustes de Amazon Marketplace Pro en la administración de tu tienda.',
    'de' => 'Ihr Shop ist jetzt mit Amazon verbunden (Verkäufer %s). Sie können diesen Tab schließen und zu den Einstellungen von Amazon Marketplace Pro in Ihrem Shop-Backoffice zurückkehren.',
    'it' => 'Il tuo negozio è ora connesso ad Amazon (venditore %s). Puoi chiudere questa scheda e tornare alle impostazioni di Amazon Marketplace Pro nel pannello di amministrazione del tuo negozio.',
    'pl' => 'Twój sklep jest teraz połączony z Amazon (sprzedawca %s). Możesz zamknąć tę kartę i wrócić do ustawień Amazon Marketplace Pro w panelu administracyjnym sklepu.',
  ),
  'Your shop is now connected to Amazon. You can close this tab and return to the Amazon Marketplace Pro settings in your shop admin.' => 
  array(
    'fr' => 'Votre boutique est maintenant connectée à Amazon. Vous pouvez fermer cet onglet et retourner aux paramètres d\'Amazon Marketplace Pro dans le back-office de votre boutique.',
    'es' => 'Tu tienda ya está conectada con Amazon. Puedes cerrar esta pestaña y volver a los ajustes de Amazon Marketplace Pro en la administración de tu tienda.',
    'de' => 'Ihr Shop ist jetzt mit Amazon verbunden. Sie können diesen Tab schließen und zu den Einstellungen von Amazon Marketplace Pro in Ihrem Shop-Backoffice zurückkehren.',
    'it' => 'Il tuo negozio è ora connesso ad Amazon. Puoi chiudere questa scheda e tornare alle impostazioni di Amazon Marketplace Pro nel pannello di amministrazione del tuo negozio.',
    'pl' => 'Twój sklep jest teraz połączony z Amazon. Możesz zamknąć tę kartę i wrócić do ustawień Amazon Marketplace Pro w panelu administracyjnym sklepu.',
  ),
);
