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
 * Strings 768 onwards: text that used to be hard-coded English.
 *
 * Most of it is what configure.tpl's script writes on screen - progress and
 * result messages, table headings, badges, select options - gathered in the
 * script's T dictionary. The rest is the scheduled-task names and groups and
 * a few AJAX messages from the module class.
 *
 * The script strings are rendered with js=1. PrestaShop 1.6 HTML-escapes
 * those and 9 does not, so no translation here may contain a straight double
 * quote, an ampersand or an angle bracket. Placeholders %1$s, %2$s ... are
 * filled in by the script's fmt() and may be reordered.
 */
return array(
  'Unexpected response' => 
  array(
    'fr' => 'Réponse inattendue',
    'es' => 'Respuesta inesperada',
    'de' => 'Unerwartete Antwort',
    'it' => 'Risposta inattesa',
    'pl' => 'Nieoczekiwana odpowiedź',
  ),
  'Done.' => 
  array(
    'fr' => 'Terminé.',
    'es' => 'Hecho.',
    'de' => 'Fertig.',
    'it' => 'Fatto.',
    'pl' => 'Gotowe.',
  ),
  'Saving...' => 
  array(
    'fr' => 'Enregistrement...',
    'es' => 'Guardando...',
    'de' => 'Wird gespeichert...',
    'it' => 'Salvataggio in corso...',
    'pl' => 'Zapisywanie...',
  ),
  'Working...' => 
  array(
    'fr' => 'Traitement en cours...',
    'es' => 'Procesando...',
    'de' => 'Wird ausgeführt...',
    'it' => 'Elaborazione in corso...',
    'pl' => 'Przetwarzanie...',
  ),
  'Save failed' => 
  array(
    'fr' => 'Échec de l\'enregistrement',
    'es' => 'Error al guardar',
    'de' => 'Speichern fehlgeschlagen',
    'it' => 'Salvataggio non riuscito',
    'pl' => 'Zapis nie powiódł się',
  ),
  'Action failed' => 
  array(
    'fr' => 'Échec de l\'action',
    'es' => 'Error en la acción',
    'de' => 'Aktion fehlgeschlagen',
    'it' => 'Azione non riuscita',
    'pl' => 'Akcja nie powiodła się',
  ),
  'Refresh failed' => 
  array(
    'fr' => 'Échec de l\'actualisation',
    'es' => 'Error al actualizar la lista',
    'de' => 'Aktualisierung fehlgeschlagen',
    'it' => 'Aggiornamento non riuscito',
    'pl' => 'Odświeżanie nie powiodło się',
  ),
  'Update failed' => 
  array(
    'fr' => 'Échec de la mise à jour',
    'es' => 'Error al actualizar',
    'de' => 'Aktualisierung fehlgeschlagen',
    'it' => 'Aggiornamento non riuscito',
    'pl' => 'Aktualizacja nie powiodła się',
  ),
  'Fetch failed' => 
  array(
    'fr' => 'Échec de la récupération',
    'es' => 'Error al recuperar',
    'de' => 'Abruf fehlgeschlagen',
    'it' => 'Recupero non riuscito',
    'pl' => 'Pobieranie nie powiodło się',
  ),
  'Import failed' => 
  array(
    'fr' => 'Échec de l\'import',
    'es' => 'Error al importar',
    'de' => 'Import fehlgeschlagen',
    'it' => 'Importazione non riuscita',
    'pl' => 'Import nie powiódł się',
  ),
  'Deletion failed' => 
  array(
    'fr' => 'Échec de la suppression',
    'es' => 'Error al eliminar',
    'de' => 'Löschen fehlgeschlagen',
    'it' => 'Eliminazione non riuscita',
    'pl' => 'Usuwanie nie powiodło się',
  ),
  'Search failed' => 
  array(
    'fr' => 'Échec de la recherche',
    'es' => 'Error al buscar',
    'de' => 'Suche fehlgeschlagen',
    'it' => 'Ricerca non riuscita',
    'pl' => 'Wyszukiwanie nie powiodło się',
  ),
  'Load failed' => 
  array(
    'fr' => 'Échec du chargement',
    'es' => 'Error al cargar',
    'de' => 'Laden fehlgeschlagen',
    'it' => 'Caricamento non riuscito',
    'pl' => 'Wczytywanie nie powiodło się',
  ),
  'Check failed' => 
  array(
    'fr' => 'Échec de la vérification',
    'es' => 'Error en la comprobación',
    'de' => 'Prüfung fehlgeschlagen',
    'it' => 'Verifica non riuscita',
    'pl' => 'Sprawdzanie nie powiodło się',
  ),
  'Lookup failed' => 
  array(
    'fr' => 'Échec de la consultation',
    'es' => 'Error en la búsqueda',
    'de' => 'Abfrage fehlgeschlagen',
    'it' => 'Ricerca non riuscita',
    'pl' => 'Wyszukiwanie ofert nie powiodło się',
  ),
  'Error: %1$s' => 
  array(
    'fr' => 'Erreur : %1$s',
    'es' => 'Error: %1$s',
    'de' => 'Fehler: %1$s',
    'it' => 'Errore: %1$s',
    'pl' => 'Błąd: %1$s',
  ),
  'PS only' => 
  array(
    'fr' => 'PS uniquement',
    'es' => 'Solo en PS',
    'de' => 'Nur PS',
    'it' => 'Solo PS',
    'pl' => 'Tylko w PS',
  ),
  'Amazon only' => 
  array(
    'fr' => 'Amazon uniquement',
    'es' => 'Solo en Amazon',
    'de' => 'Nur Amazon',
    'it' => 'Solo Amazon',
    'pl' => 'Tylko w Amazon',
  ),
  'Conflict' => 
  array(
    'fr' => 'Conflit',
    'es' => 'Conflicto',
    'de' => 'Konflikt',
    'it' => 'Conflitto',
    'pl' => 'Konflikt',
  ),
  'In sync' => 
  array(
    'fr' => 'Synchronisé',
    'es' => 'Sincronizado',
    'de' => 'Synchron',
    'it' => 'Sincronizzato',
    'pl' => 'Zsynchronizowane',
  ),
  'Amazon Order ID' => 
  array(
    'fr' => 'Numéro de commande Amazon',
    'es' => 'ID de pedido de Amazon',
    'de' => 'Amazon-Bestellnummer',
    'it' => 'ID ordine Amazon',
    'pl' => 'Numer zamówienia Amazon',
  ),
  'Tax' => 
  array(
    'fr' => 'Taxe',
    'es' => 'Impuestos',
    'de' => 'Steuer',
    'it' => 'Imposte',
    'pl' => 'Podatek',
  ),
  'Channel' => 
  array(
    'fr' => 'Canal',
    'es' => 'Canal',
    'de' => 'Kanal',
    'it' => 'Canale',
    'pl' => 'Kanał',
  ),
  'Import' => 
  array(
    'fr' => 'Import',
    'es' => 'Importación',
    'de' => 'Import',
    'it' => 'Importazione',
    'pl' => 'Import',
  ),
  'Feed' => 
  array(
    'fr' => 'Flux',
    'es' => 'Feed',
    'de' => 'Feed',
    'it' => 'Feed',
    'pl' => 'Feed',
  ),
  'Messages' => 
  array(
    'fr' => 'Messages',
    'es' => 'Mensajes',
    'de' => 'Nachrichten',
    'it' => 'Messaggi',
    'pl' => 'Komunikaty',
  ),
  'Accepted' => 
  array(
    'fr' => 'Acceptés',
    'es' => 'Aceptados',
    'de' => 'Angenommen',
    'it' => 'Accettati',
    'pl' => 'Przyjęte',
  ),
  'Errors' => 
  array(
    'fr' => 'Erreurs',
    'es' => 'Errores',
    'de' => 'Fehler',
    'it' => 'Errori',
    'pl' => 'Błędy',
  ),
  'Warnings' => 
  array(
    'fr' => 'Avertissements',
    'es' => 'Advertencias',
    'de' => 'Warnungen',
    'it' => 'Avvisi',
    'pl' => 'Ostrzeżenia',
  ),
  'Brand/Mfr' => 
  array(
    'fr' => 'Marque/Fab.',
    'es' => 'Marca/Fab.',
    'de' => 'Marke/Herst.',
    'it' => 'Marca/Produttore',
    'pl' => 'Marka/producent',
  ),
  'Brand' => 
  array(
    'fr' => 'Marque',
    'es' => 'Marca',
    'de' => 'Marke',
    'it' => 'Marca',
    'pl' => 'Marka',
  ),
  'Issues' => 
  array(
    'fr' => 'Problèmes',
    'es' => 'Incidencias',
    'de' => 'Probleme',
    'it' => 'Problemi',
    'pl' => 'Problemy',
  ),
  'Created' => 
  array(
    'fr' => 'Créée',
    'es' => 'Creado',
    'de' => 'Angelegt',
    'it' => 'Creato',
    'pl' => 'Utworzone',
  ),
  'Staged' => 
  array(
    'fr' => 'Préparée',
    'es' => 'Preparado',
    'de' => 'Bereitgestellt',
    'it' => 'Preparato',
    'pl' => 'Przygotowane',
  ),
  'No orders staged yet.' => 
  array(
    'fr' => 'Aucune commande préparée pour le moment.',
    'es' => 'Todavía no hay pedidos preparados.',
    'de' => 'Noch keine Bestellungen bereitgestellt.',
    'it' => 'Nessun ordine preparato per ora.',
    'pl' => 'Brak przygotowanych zamówień.',
  ),
  'Fetching orders from Amazon...' => 
  array(
    'fr' => 'Récupération des commandes depuis Amazon...',
    'es' => 'Recuperando los pedidos de Amazon...',
    'de' => 'Bestellungen werden von Amazon geholt...',
    'it' => 'Recupero degli ordini da Amazon in corso...',
    'pl' => 'Pobieranie zamówień z Amazon...',
  ),
  'Fetched %1$s · imported %2$s new · %3$s already staged · items matched %4$s / unmatched %5$s' => 
  array(
    'fr' => '%1$s récupérée(s) · %2$s nouvelle(s) importée(s) · %3$s déjà préparée(s) · articles rapprochés %4$s / non rapprochés %5$s',
    'es' => 'Recuperados %1$s · %2$s nuevos importados · %3$s ya preparados · artículos emparejados %4$s / sin emparejar %5$s',
    'de' => '%1$s geholt · %2$s neu importiert · %3$s bereits bereitgestellt · Artikel zugeordnet %4$s / nicht zugeordnet %5$s',
    'it' => 'Recuperati %1$s · %2$s nuovi importati · %3$s già preparati · articoli abbinati %4$s / non abbinati %5$s',
    'pl' => 'Pobrane: %1$s · nowe zaimportowane: %2$s · już przygotowane: %3$s · pozycje dopasowane: %4$s / niedopasowane: %5$s',
  ),
  'Creating PrestaShop orders from staged Amazon orders...' => 
  array(
    'fr' => 'Création des commandes PrestaShop à partir des commandes Amazon préparées...',
    'es' => 'Creando pedidos de PrestaShop a partir de los pedidos de Amazon preparados...',
    'de' => 'PrestaShop-Bestellungen werden aus bereitgestellten Amazon-Bestellungen angelegt...',
    'it' => 'Creazione degli ordini PrestaShop dagli ordini Amazon preparati in corso...',
    'pl' => 'Tworzenie zamówień PrestaShop z przygotowanych zamówień Amazon...',
  ),
  '%1$s pending · %2$s created · %3$s skipped · %4$s failed' => 
  array(
    'fr' => '%1$s en attente · %2$s créée(s) · %3$s ignorée(s) · %4$s en échec',
    'es' => '%1$s pendientes · %2$s creados · %3$s omitidos · %4$s con error',
    'de' => '%1$s ausstehend · %2$s angelegt · %3$s übersprungen · %4$s fehlgeschlagen',
    'it' => '%1$s in sospeso · %2$s creati · %3$s saltati · %4$s non riusciti',
    'pl' => 'Oczekujące: %1$s · utworzone: %2$s · pominięte: %3$s · nieudane: %4$s',
  ),
  'No pending orders to create.' => 
  array(
    'fr' => 'Aucune commande en attente à créer.',
    'es' => 'No hay pedidos pendientes que crear.',
    'de' => 'Keine ausstehenden Bestellungen zum Anlegen.',
    'it' => 'Nessun ordine in sospeso da creare.',
    'pl' => 'Brak oczekujących zamówień do utworzenia.',
  ),
  'Enter an Amazon order id first.' => 
  array(
    'fr' => 'Saisissez d\'abord un numéro de commande Amazon.',
    'es' => 'Introduce primero un ID de pedido de Amazon.',
    'de' => 'Geben Sie zuerst eine Amazon-Bestellnummer ein.',
    'it' => 'Inserisci prima un ID ordine Amazon.',
    'pl' => 'Najpierw wpisz numer zamówienia Amazon.',
  ),
  'Asking Amazon which message types are allowed...' => 
  array(
    'fr' => 'Interrogation d\'Amazon sur les types de messages autorisés...',
    'es' => 'Consultando a Amazon qué tipos de mensaje están permitidos...',
    'de' => 'Zulässige Nachrichtentypen werden bei Amazon abgefragt...',
    'it' => 'Richiesta ad Amazon dei tipi di messaggio consentiti in corso...',
    'pl' => 'Sprawdzanie w Amazon dozwolonych typów wiadomości...',
  ),
  'Amazon allows no messages for this order right now.' => 
  array(
    'fr' => 'Amazon n\'autorise aucun message pour cette commande pour le moment.',
    'es' => 'Ahora mismo Amazon no permite ningún mensaje para este pedido.',
    'de' => 'Amazon lässt für diese Bestellung derzeit keine Nachrichten zu.',
    'it' => 'Al momento Amazon non consente messaggi per questo ordine.',
    'pl' => 'Amazon nie zezwala obecnie na żadne wiadomości dla tego zamówienia.',
  ),
  '%1$s message type(s) allowed.' => 
  array(
    'fr' => '%1$s type(s) de message autorisé(s).',
    'es' => '%1$s tipo(s) de mensaje permitido(s).',
    'de' => '%1$s Nachrichtentyp(en) zulässig.',
    'it' => 'Tipi di messaggio consentiti: %1$s.',
    'pl' => 'Dozwolone typy wiadomości: %1$s.',
  ),
  'Requesting a review from the buyer...' => 
  array(
    'fr' => 'Demande d\'avis à l\'acheteur en cours...',
    'es' => 'Solicitando una valoración al comprador...',
    'de' => 'Bewertung wird beim Käufer angefordert...',
    'it' => 'Richiesta di una recensione all\'acquirente in corso...',
    'pl' => 'Wysyłanie prośby o opinię do kupującego...',
  ),
  'Review request sent.' => 
  array(
    'fr' => 'Demande d\'avis envoyée.',
    'es' => 'Solicitud de valoración enviada.',
    'de' => 'Bewertungsanfrage gesendet.',
    'it' => 'Richiesta di recensione inviata.',
    'pl' => 'Prośba o opinię wysłana.',
  ),
  'Load message types and pick one first.' => 
  array(
    'fr' => 'Chargez d\'abord les types de messages et choisissez-en un.',
    'es' => 'Carga primero los tipos de mensaje y elige uno.',
    'de' => 'Laden Sie zuerst die Nachrichtentypen und wählen Sie einen aus.',
    'it' => 'Carica prima i tipi di messaggio e scegline uno.',
    'pl' => 'Najpierw wczytaj typy wiadomości i wybierz jeden z nich.',
  ),
  'Sending message...' => 
  array(
    'fr' => 'Envoi du message...',
    'es' => 'Enviando el mensaje...',
    'de' => 'Nachricht wird gesendet...',
    'it' => 'Invio del messaggio in corso...',
    'pl' => 'Wysyłanie wiadomości...',
  ),
  'Message sent to the buyer.' => 
  array(
    'fr' => 'Message envoyé à l\'acheteur.',
    'es' => 'Mensaje enviado al comprador.',
    'de' => 'Nachricht an den Käufer gesendet.',
    'it' => 'Messaggio inviato all\'acquirente.',
    'pl' => 'Wiadomość wysłana do kupującego.',
  ),
  'Searching the Amazon catalog by EAN...' => 
  array(
    'fr' => 'Recherche dans le catalogue Amazon par EAN...',
    'es' => 'Buscando en el catálogo de Amazon por EAN...',
    'de' => 'Amazon-Katalog wird über die EAN durchsucht...',
    'it' => 'Ricerca nel catalogo Amazon tramite EAN in corso...',
    'pl' => 'Wyszukiwanie w katalogu Amazon po EAN...',
  ),
  '%1$s checked · %2$s ASIN(s) matched · %3$s not in the Amazon catalog' => 
  array(
    'fr' => '%1$s vérifié(s) · %2$s ASIN associé(s) · %3$s absent(s) du catalogue Amazon',
    'es' => '%1$s comprobados · %2$s ASIN emparejado(s) · %3$s no están en el catálogo de Amazon',
    'de' => '%1$s geprüft · %2$s ASIN(s) zugeordnet · %3$s nicht im Amazon-Katalog',
    'it' => '%1$s verificati · %2$s ASIN abbinati · %3$s non presenti nel catalogo Amazon',
    'pl' => 'Sprawdzone: %1$s · dopasowane ASIN: %2$s · brak w katalogu Amazon: %3$s',
  ),
  'Creating PrestaShop products from Amazon listings...' => 
  array(
    'fr' => 'Création des produits PrestaShop à partir des offres Amazon...',
    'es' => 'Creando productos de PrestaShop a partir de los anuncios de Amazon...',
    'de' => 'PrestaShop-Produkte werden aus Amazon-Angeboten angelegt...',
    'it' => 'Creazione dei prodotti PrestaShop dalle inserzioni Amazon in corso...',
    'pl' => 'Tworzenie produktów PrestaShop z ofert Amazon...',
  ),
  '%1$s Amazon-only listing(s) · %2$s product(s) created (inactive) · %3$s linked to existing · %4$s failed' => 
  array(
    'fr' => '%1$s offre(s) présente(s) uniquement sur Amazon · %2$s produit(s) créé(s) (inactifs) · %3$s lié(s) à un produit existant · %4$s en échec',
    'es' => '%1$s anuncio(s) solo en Amazon · %2$s producto(s) creado(s) (inactivos) · %3$s vinculado(s) a productos existentes · %4$s con error',
    'de' => '%1$s Angebot(e) nur bei Amazon · %2$s Produkt(e) angelegt (inaktiv) · %3$s mit vorhandenen verknüpft · %4$s fehlgeschlagen',
    'it' => '%1$s inserzioni solo su Amazon · %2$s prodotti creati (non attivi) · %3$s collegati a prodotti esistenti · %4$s non riusciti',
    'pl' => 'Oferty tylko w Amazon: %1$s · utworzone produkty (nieaktywne): %2$s · powiązane z istniejącymi: %3$s · nieudane: %4$s',
  ),
  '%1$s image(s) failed' => 
  array(
    'fr' => '%1$s image(s) en échec',
    'es' => '%1$s imagen(es) con error',
    'de' => '%1$s Bild(er) fehlgeschlagen',
    'it' => '%1$s immagini non riuscite',
    'pl' => 'zdjęcia z błędem: %1$s',
  ),
  'Checking feed status with Amazon...' => 
  array(
    'fr' => 'Vérification de l\'état des flux auprès d\'Amazon...',
    'es' => 'Comprobando el estado del feed con Amazon...',
    'de' => 'Feed-Status wird bei Amazon geprüft...',
    'it' => 'Verifica dello stato dei feed presso Amazon in corso...',
    'pl' => 'Sprawdzanie stanu feedu w Amazon...',
  ),
  '%1$s feed(s) checked · %2$s completed · %3$s still processing · %4$s failed' => 
  array(
    'fr' => '%1$s flux vérifié(s) · %2$s terminé(s) · %3$s en cours de traitement · %4$s en échec',
    'es' => '%1$s feed(s) comprobado(s) · %2$s completado(s) · %3$s aún en proceso · %4$s con error',
    'de' => '%1$s Feed(s) geprüft · %2$s abgeschlossen · %3$s noch in Verarbeitung · %4$s fehlgeschlagen',
    'it' => '%1$s feed verificati · %2$s completati · %3$s ancora in elaborazione · %4$s non riusciti',
    'pl' => 'Sprawdzone feedy: %1$s · zakończone: %2$s · nadal przetwarzane: %3$s · nieudane: %4$s',
  ),
  'No products with a reference (SKU) found.' => 
  array(
    'fr' => 'Aucun produit avec une référence (SKU) trouvé.',
    'es' => 'No se han encontrado productos con referencia (SKU).',
    'de' => 'Keine Produkte mit Artikelnummer (SKU) gefunden.',
    'it' => 'Nessun prodotto con riferimento (SKU) trovato.',
    'pl' => 'Nie znaleziono produktów z referencją (SKU).',
  ),
  'qty %1$s' => 
  array(
    'fr' => 'qté %1$s',
    'es' => 'cant. %1$s',
    'de' => 'Menge %1$s',
    'it' => 'qtà %1$s',
    'pl' => 'ilość: %1$s',
  ),
  'Pulling from Amazon...' => 
  array(
    'fr' => 'Récupération depuis Amazon...',
    'es' => 'Trayendo los datos de Amazon...',
    'de' => 'Daten werden von Amazon geholt...',
    'it' => 'Lettura da Amazon in corso...',
    'pl' => 'Pobieranie z Amazon...',
  ),
  'Scanning PrestaShop...' => 
  array(
    'fr' => 'Analyse de PrestaShop...',
    'es' => 'Analizando PrestaShop...',
    'de' => 'PrestaShop wird durchsucht...',
    'it' => 'Analisi di PrestaShop in corso...',
    'pl' => 'Skanowanie PrestaShop...',
  ),
  '%1$s products · %2$s PS only · %3$s Amazon only · %4$s conflicts · %5$s in sync' => 
  array(
    'fr' => '%1$s produits · %2$s PS uniquement · %3$s Amazon uniquement · %4$s conflits · %5$s synchronisés',
    'es' => '%1$s productos · %2$s solo en PS · %3$s solo en Amazon · %4$s conflictos · %5$s sincronizados',
    'de' => '%1$s Produkte · %2$s nur PS · %3$s nur Amazon · %4$s Konflikte · %5$s synchron',
    'it' => '%1$s prodotti · %2$s solo PS · %3$s solo Amazon · %4$s conflitti · %5$s sincronizzati',
    'pl' => 'Produkty: %1$s · tylko w PS: %2$s · tylko w Amazon: %3$s · konflikty: %4$s · zsynchronizowane: %5$s',
  ),
  'Sending pending changes to Amazon...' => 
  array(
    'fr' => 'Envoi des modifications en attente vers Amazon...',
    'es' => 'Enviando los cambios pendientes a Amazon...',
    'de' => 'Ausstehende Änderungen werden an Amazon gesendet...',
    'it' => 'Invio ad Amazon delle modifiche in attesa in corso...',
    'pl' => 'Wysyłanie oczekujących zmian do Amazon...',
  ),
  '%1$s SKU(s) skipped' => 
  array(
    'fr' => '%1$s SKU ignoré(s)',
    'es' => '%1$s SKU omitido(s)',
    'de' => '%1$s SKU(s) übersprungen',
    'it' => '%1$s SKU saltati',
    'pl' => 'pominięte SKU: %1$s',
  ),
  '%1$s SKUs were pending, so they went as one feed. Feed %2$s was submitted with %3$s message(s). Amazon processes it in the background: use Check feed status below.' => 
  array(
    'fr' => '%1$s SKU étaient en attente : ils sont donc partis en un seul flux. Le flux %2$s a été envoyé avec %3$s message(s). Amazon le traite en arrière-plan : utilisez « Vérifier l\'état du flux » ci-dessous.',
    'es' => 'Había %1$s SKU pendientes, así que se han enviado en un único feed. El feed %2$s se ha enviado con %3$s mensaje(s). Amazon lo procesa en segundo plano: usa Comprobar el estado del feed más abajo.',
    'de' => '%1$s SKUs standen aus und wurden daher als ein Feed gesendet. Feed %2$s wurde mit %3$s Nachricht(en) übermittelt. Amazon verarbeitet ihn im Hintergrund: Verwenden Sie unten „Feed-Status prüfen“.',
    'it' => '%1$s SKU erano in attesa, quindi sono partiti con un unico feed. Il feed %2$s è stato inviato con %3$s messaggi. Amazon lo elabora in background: usa «Verifica lo stato del feed» qui sotto.',
    'pl' => 'Liczba oczekujących SKU: %1$s, dlatego wysłano je jako jeden feed. Feed %2$s został wysłany (komunikaty: %3$s). Amazon przetwarza go w tle: użyj przycisku Sprawdź stan feedu poniżej.',
  ),
  '%1$s SKU(s) skipped (no category mapping).' => 
  array(
    'fr' => '%1$s SKU ignoré(s) (aucune correspondance de catégorie).',
    'es' => '%1$s SKU omitido(s) (sin correspondencia de categoría).',
    'de' => '%1$s SKU(s) übersprungen (keine Kategoriezuordnung).',
    'it' => '%1$s SKU saltati (nessuna corrispondenza di categoria).',
    'pl' => 'Pominięte SKU: %1$s (brak mapowania kategorii).',
  ),
  '%1$s candidates · %2$s accepted · %3$s failed' => 
  array(
    'fr' => '%1$s candidats · %2$s acceptés · %3$s en échec',
    'es' => '%1$s candidatos · %2$s aceptados · %3$s con error',
    'de' => '%1$s Kandidaten · %2$s angenommen · %3$s fehlgeschlagen',
    'it' => '%1$s candidati · %2$s accettati · %3$s non riusciti',
    'pl' => 'Do wysłania: %1$s · przyjęte: %2$s · nieudane: %3$s',
  ),
  'Nothing to send.' => 
  array(
    'fr' => 'Rien à envoyer.',
    'es' => 'No hay nada que enviar.',
    'de' => 'Nichts zu senden.',
    'it' => 'Niente da inviare.',
    'pl' => 'Nic do wysłania.',
  ),
  'Fetching products from Amazon...' => 
  array(
    'fr' => 'Récupération des produits depuis Amazon...',
    'es' => 'Recuperando los productos de Amazon...',
    'de' => 'Produkte werden von Amazon geholt...',
    'it' => 'Recupero dei prodotti da Amazon in corso...',
    'pl' => 'Pobieranie produktów z Amazon...',
  ),
  '%1$s product(s) found.' => 
  array(
    'fr' => '%1$s produit(s) trouvé(s).',
    'es' => '%1$s producto(s) encontrado(s).',
    'de' => '%1$s Produkt(e) gefunden.',
    'it' => 'Prodotti trovati: %1$s.',
    'pl' => 'Znalezione produkty: %1$s.',
  ),
  'No products to display.' => 
  array(
    'fr' => 'Aucun produit à afficher.',
    'es' => 'No hay productos que mostrar.',
    'de' => 'Keine Produkte zum Anzeigen.',
    'it' => 'Nessun prodotto da visualizzare.',
    'pl' => 'Brak produktów do wyświetlenia.',
  ),
  'Please select a category and enter an Amazon Product Type.' => 
  array(
    'fr' => 'Choisissez une catégorie et saisissez un type de produit Amazon.',
    'es' => 'Elige una categoría e introduce un tipo de producto de Amazon.',
    'de' => 'Bitte wählen Sie eine Kategorie und geben Sie einen Amazon-Produkttyp ein.',
    'it' => 'Seleziona una categoria e inserisci un tipo di prodotto Amazon.',
    'pl' => 'Wybierz kategorię i wpisz typ produktu Amazon.',
  ),
  'Extra attributes must be valid JSON: %1$s' => 
  array(
    'fr' => 'Les attributs supplémentaires doivent être du JSON valide : %1$s',
    'es' => 'Los atributos adicionales deben ser JSON válido: %1$s',
    'de' => 'Zusätzliche Attribute müssen gültiges JSON sein: %1$s',
    'it' => 'Gli attributi aggiuntivi devono essere JSON valido: %1$s',
    'pl' => 'Dodatkowe atrybuty muszą być poprawnym JSON-em: %1$s',
  ),
  'Failed to save mapping.' => 
  array(
    'fr' => 'Impossible d\'enregistrer la correspondance.',
    'es' => 'No se ha podido guardar la correspondencia.',
    'de' => 'Die Zuordnung konnte nicht gespeichert werden.',
    'it' => 'Non è stato possibile salvare la corrispondenza.',
    'pl' => 'Nie udało się zapisać mapowania.',
  ),
  'Mapping saved.' => 
  array(
    'fr' => 'Correspondance enregistrée.',
    'es' => 'Correspondencia guardada.',
    'de' => 'Zuordnung gespeichert.',
    'it' => 'Corrispondenza salvata.',
    'pl' => 'Mapowanie zapisane.',
  ),
  'Importing returns from Amazon...' => 
  array(
    'fr' => 'Import des retours depuis Amazon...',
    'es' => 'Importando las devoluciones de Amazon...',
    'de' => 'Retouren werden von Amazon importiert...',
    'it' => 'Importazione dei resi da Amazon in corso...',
    'pl' => 'Importowanie zwrotów z Amazon...',
  ),
  'Checked %1$s · %2$s new returns · %3$s new cancellations · %4$s already imported' => 
  array(
    'fr' => '%1$s vérifié(s) · %2$s nouveaux retours · %3$s nouvelles annulations · %4$s déjà importé(s)',
    'es' => 'Comprobados %1$s · %2$s devoluciones nuevas · %3$s cancelaciones nuevas · %4$s ya importadas',
    'de' => '%1$s geprüft · %2$s neue Retouren · %3$s neue Stornierungen · %4$s bereits importiert',
    'it' => 'Verificati %1$s · %2$s nuovi resi · %3$s nuovi annullamenti · %4$s già importati',
    'pl' => 'Sprawdzone: %1$s · nowe zwroty: %2$s · nowe anulowania: %3$s · już zaimportowane: %4$s',
  ),
  'No new returns found.' => 
  array(
    'fr' => 'Aucun nouveau retour trouvé.',
    'es' => 'No se han encontrado devoluciones nuevas.',
    'de' => 'Keine neuen Retouren gefunden.',
    'it' => 'Nessun nuovo reso trovato.',
    'pl' => 'Nie znaleziono nowych zwrotów.',
  ),
  'Processing pending returns...' => 
  array(
    'fr' => 'Traitement des retours en attente...',
    'es' => 'Procesando las devoluciones pendientes...',
    'de' => 'Ausstehende Retouren werden verarbeitet...',
    'it' => 'Elaborazione dei resi in sospeso in corso...',
    'pl' => 'Przetwarzanie oczekujących zwrotów...',
  ),
  '%1$s pending · %2$s processed · %3$s skipped · %4$s failed' => 
  array(
    'fr' => '%1$s en attente · %2$s traité(s) · %3$s ignoré(s) · %4$s en échec',
    'es' => '%1$s pendientes · %2$s procesadas · %3$s omitidas · %4$s con error',
    'de' => '%1$s ausstehend · %2$s verarbeitet · %3$s übersprungen · %4$s fehlgeschlagen',
    'it' => '%1$s in sospeso · %2$s elaborati · %3$s saltati · %4$s non riusciti',
    'pl' => 'Oczekujące: %1$s · przetworzone: %2$s · pominięte: %3$s · nieudane: %4$s',
  ),
  'No pending returns to process.' => 
  array(
    'fr' => 'Aucun retour en attente à traiter.',
    'es' => 'No hay devoluciones pendientes que procesar.',
    'de' => 'Keine ausstehenden Retouren zu verarbeiten.',
    'it' => 'Nessun reso in sospeso da elaborare.',
    'pl' => 'Brak oczekujących zwrotów do przetworzenia.',
  ),
  'Syncing FBA inventory from Amazon...' => 
  array(
    'fr' => 'Synchronisation du stock FBA depuis Amazon...',
    'es' => 'Sincronizando el inventario FBA desde Amazon...',
    'de' => 'FBA-Bestand wird mit Amazon abgeglichen...',
    'it' => 'Sincronizzazione dell\'inventario FBA da Amazon in corso...',
    'pl' => 'Synchronizowanie zapasów FBA z Amazon...',
  ),
  'Fetched %1$s SKUs · %2$s new · %3$s updated · %4$s matched in PrestaShop' => 
  array(
    'fr' => '%1$s SKU récupérés · %2$s nouveaux · %3$s mis à jour · %4$s rapprochés dans PrestaShop',
    'es' => '%1$s SKU recuperados · %2$s nuevos · %3$s actualizados · %4$s emparejados en PrestaShop',
    'de' => '%1$s SKUs geholt · %2$s neu · %3$s aktualisiert · %4$s in PrestaShop zugeordnet',
    'it' => 'Recuperati %1$s SKU · %2$s nuovi · %3$s aggiornati · %4$s abbinati in PrestaShop',
    'pl' => 'Pobrane SKU: %1$s · nowe: %2$s · zaktualizowane: %3$s · dopasowane w PrestaShop: %4$s',
  ),
  'No FBA inventory data returned.' => 
  array(
    'fr' => 'Aucune donnée de stock FBA renvoyée.',
    'es' => 'No se han recibido datos de inventario FBA.',
    'de' => 'Keine FBA-Bestandsdaten erhalten.',
    'it' => 'Nessun dato di inventario FBA restituito.',
    'pl' => 'Nie zwrócono danych o zapasach FBA.',
  ),
  'Updating PrestaShop stock from FBA quantities...' => 
  array(
    'fr' => 'Mise à jour du stock PrestaShop à partir des quantités FBA...',
    'es' => 'Actualizando el stock de PrestaShop con las cantidades de FBA...',
    'de' => 'PrestaShop-Bestand wird aus den FBA-Mengen aktualisiert...',
    'it' => 'Aggiornamento della giacenza PrestaShop dalle quantità FBA in corso...',
    'pl' => 'Aktualizowanie stanów PrestaShop na podstawie ilości FBA...',
  ),
  '%1$s FBA SKUs · %2$s PrestaShop stock updated · %3$s skipped' => 
  array(
    'fr' => '%1$s SKU FBA · %2$s stocks PrestaShop mis à jour · %3$s ignorés',
    'es' => '%1$s SKU de FBA · stock de PrestaShop actualizado en %2$s · %3$s omitidos',
    'de' => '%1$s FBA-SKUs · %2$s PrestaShop-Bestände aktualisiert · %3$s übersprungen',
    'it' => '%1$s SKU FBA · %2$s giacenze PrestaShop aggiornate · %3$s saltati',
    'pl' => 'SKU FBA: %1$s · zaktualizowane stany PrestaShop: %2$s · pominięte: %3$s',
  ),
  'Please enter a PrestaShop order ID.' => 
  array(
    'fr' => 'Saisissez un ID de commande PrestaShop.',
    'es' => 'Introduce un ID de pedido de PrestaShop.',
    'de' => 'Bitte geben Sie eine PrestaShop-Bestell-ID ein.',
    'it' => 'Inserisci un ID ordine PrestaShop.',
    'pl' => 'Wpisz identyfikator zamówienia PrestaShop.',
  ),
  'Creating the MCF fulfilment order...' => 
  array(
    'fr' => 'Création de la commande d\'expédition MCF...',
    'es' => 'Creando el pedido de logística MCF...',
    'de' => 'MCF-Fulfillment-Auftrag wird angelegt...',
    'it' => 'Creazione dell\'ordine di evasione MCF in corso...',
    'pl' => 'Tworzenie zamówienia realizacji MCF...',
  ),
  'MCF order created. Seller fulfilment order ID: %1$s' => 
  array(
    'fr' => 'Commande MCF créée. ID de la commande d\'expédition vendeur : %1$s',
    'es' => 'Pedido MCF creado. ID del pedido de logística del vendedor: %1$s',
    'de' => 'MCF-Bestellung angelegt. Fulfillment-Auftrags-ID des Verkäufers: %1$s',
    'it' => 'Ordine MCF creato. ID dell\'ordine di evasione del venditore: %1$s',
    'pl' => 'Zamówienie MCF utworzone. Identyfikator zamówienia realizacji sprzedawcy: %1$s',
  ),
  'Failed to create the MCF order.' => 
  array(
    'fr' => 'Impossible de créer la commande MCF.',
    'es' => 'No se ha podido crear el pedido MCF.',
    'de' => 'Die MCF-Bestellung konnte nicht angelegt werden.',
    'it' => 'Non è stato possibile creare l\'ordine MCF.',
    'pl' => 'Nie udało się utworzyć zamówienia MCF.',
  ),
  'Fetching competitive pricing from Amazon...' => 
  array(
    'fr' => 'Récupération des prix concurrents depuis Amazon...',
    'es' => 'Recuperando los precios de la competencia de Amazon...',
    'de' => 'Wettbewerbspreise werden von Amazon geholt...',
    'it' => 'Recupero dei prezzi della concorrenza da Amazon in corso...',
    'pl' => 'Pobieranie cen konkurencji z Amazon...',
  ),
  '%1$s ASINs processed · %2$s Buy Box wins · %3$s updated' => 
  array(
    'fr' => '%1$s ASIN traités · %2$s Buy Box remportées · %3$s mis à jour',
    'es' => '%1$s ASIN procesados · %2$s Buy Box ganadas · %3$s actualizados',
    'de' => '%1$s ASINs verarbeitet · %2$s Buy-Box-Gewinne · %3$s aktualisiert',
    'it' => '%1$s ASIN elaborati · %2$s Buy Box vinte · %3$s aggiornati',
    'pl' => 'Przetworzone ASIN: %1$s · wygrane Buy Box: %2$s · zaktualizowane: %3$s',
  ),
  'Applying pricing rules...' => 
  array(
    'fr' => 'Application des règles tarifaires...',
    'es' => 'Aplicando las reglas de precio...',
    'de' => 'Preisregeln werden angewendet...',
    'it' => 'Applicazione delle regole di prezzo in corso...',
    'pl' => 'Stosowanie reguł cenowych...',
  ),
  '%1$s products evaluated · %2$s repriced · %3$s capped at min/max' => 
  array(
    'fr' => '%1$s produits évalués · %2$s prix ajustés · %3$s bornés au min/max',
    'es' => '%1$s productos evaluados · %2$s con precio ajustado · %3$s limitados al mín./máx.',
    'de' => '%1$s Produkte ausgewertet · %2$s neu bepreist · %3$s auf Min./Max. begrenzt',
    'it' => '%1$s prodotti valutati · %2$s con nuovo prezzo · %3$s limitati al min/max',
    'pl' => 'Ocenione produkty: %1$s · zmienione ceny: %2$s · ograniczone do min/maks.: %3$s',
  ),
  'Pushing suggested prices to Amazon...' => 
  array(
    'fr' => 'Envoi des prix suggérés vers Amazon...',
    'es' => 'Enviando los precios sugeridos a Amazon...',
    'de' => 'Vorgeschlagene Preise werden an Amazon gesendet...',
    'it' => 'Invio dei prezzi suggeriti ad Amazon in corso...',
    'pl' => 'Wysyłanie sugerowanych cen do Amazon...',
  ),
  '%1$s candidates · %2$s pushed · %3$s failed' => 
  array(
    'fr' => '%1$s candidats · %2$s envoyés · %3$s en échec',
    'es' => '%1$s candidatos · %2$s enviados · %3$s con error',
    'de' => '%1$s Kandidaten · %2$s gesendet · %3$s fehlgeschlagen',
    'it' => '%1$s candidati · %2$s inviati · %3$s non riusciti',
    'pl' => 'Do wysłania: %1$s · wysłane: %2$s · nieudane: %3$s',
  ),
  'Please enter a rule name.' => 
  array(
    'fr' => 'Saisissez un nom de règle.',
    'es' => 'Introduce un nombre para la regla.',
    'de' => 'Bitte geben Sie einen Regelnamen ein.',
    'it' => 'Inserisci un nome per la regola.',
    'pl' => 'Wpisz nazwę reguły.',
  ),
  'Pricing rule saved. Refresh the page to see it in the table.' => 
  array(
    'fr' => 'Règle tarifaire enregistrée. Actualisez la page pour la voir dans le tableau.',
    'es' => 'Regla de precio guardada. Recarga la página para verla en la tabla.',
    'de' => 'Preisregel gespeichert. Laden Sie die Seite neu, um sie in der Tabelle zu sehen.',
    'it' => 'Regola di prezzo salvata. Ricarica la pagina per vederla nella tabella.',
    'pl' => 'Reguła cenowa zapisana. Odśwież stronę, aby zobaczyć ją w tabeli.',
  ),
  'Failed to save the rule.' => 
  array(
    'fr' => 'Impossible d\'enregistrer la règle.',
    'es' => 'No se ha podido guardar la regla.',
    'de' => 'Die Regel konnte nicht gespeichert werden.',
    'it' => 'Non è stato possibile salvare la regola.',
    'pl' => 'Nie udało się zapisać reguły.',
  ),
  'Fetching order fees from the Amazon Finances API...' => 
  array(
    'fr' => 'Récupération des frais des commandes depuis l\'Amazon Finances API...',
    'es' => 'Recuperando las tarifas de los pedidos desde la Amazon Finances API...',
    'de' => 'Bestellgebühren werden aus der Amazon Finances API geholt...',
    'it' => 'Recupero dei costi degli ordini tramite Amazon Finances API in corso...',
    'pl' => 'Pobieranie opłat zamówień z Amazon Finances API...',
  ),
  '%1$s orders checked · %2$s fee entries recorded · %3$s orders updated' => 
  array(
    'fr' => '%1$s commandes vérifiées · %2$s lignes de frais enregistrées · %3$s commandes mises à jour',
    'es' => '%1$s pedidos comprobados · %2$s tarifas registradas · %3$s pedidos actualizados',
    'de' => '%1$s Bestellungen geprüft · %2$s Gebühreneinträge erfasst · %3$s Bestellungen aktualisiert',
    'it' => '%1$s ordini verificati · %2$s voci di costo registrate · %3$s ordini aggiornati',
    'pl' => 'Sprawdzone zamówienia: %1$s · zapisane pozycje opłat: %2$s · zaktualizowane zamówienia: %3$s',
  ),
  'Requesting the %1$s report...' => 
  array(
    'fr' => 'Demande du rapport %1$s...',
    'es' => 'Solicitando el informe %1$s...',
    'de' => 'Bericht %1$s wird angefordert...',
    'it' => 'Richiesta del report %1$s in corso...',
    'pl' => 'Zamawianie raportu %1$s...',
  ),
  'Report requested. ID: %1$s. Use Poll Pending Reports to check its status.' => 
  array(
    'fr' => 'Rapport demandé. ID : %1$s. Utilisez « Interroger les rapports en attente » pour suivre son état.',
    'es' => 'Informe solicitado. ID: %1$s. Usa Consultar los informes pendientes para comprobar su estado.',
    'de' => 'Bericht angefordert. ID: %1$s. Prüfen Sie seinen Status mit „Ausstehende Berichte abfragen“.',
    'it' => 'Report richiesto. ID: %1$s. Usa «Interroga i report in sospeso» per verificarne lo stato.',
    'pl' => 'Raport zamówiony. Identyfikator: %1$s. Użyj przycisku Sprawdź oczekujące raporty, aby poznać jego stan.',
  ),
  'Failed to request the report.' => 
  array(
    'fr' => 'Impossible de demander le rapport.',
    'es' => 'No se ha podido solicitar el informe.',
    'de' => 'Der Bericht konnte nicht angefordert werden.',
    'it' => 'Non è stato possibile richiedere il report.',
    'pl' => 'Nie udało się zamówić raportu.',
  ),
  'Polling pending reports...' => 
  array(
    'fr' => 'Interrogation des rapports en attente...',
    'es' => 'Consultando los informes pendientes...',
    'de' => 'Ausstehende Berichte werden abgefragt...',
    'it' => 'Interrogazione dei report in sospeso in corso...',
    'pl' => 'Sprawdzanie oczekujących raportów...',
  ),
  '%1$s reports checked · %2$s completed · %3$s still pending' => 
  array(
    'fr' => '%1$s rapports vérifiés · %2$s terminés · %3$s toujours en attente',
    'es' => '%1$s informes comprobados · %2$s completados · %3$s aún pendientes',
    'de' => '%1$s Berichte geprüft · %2$s abgeschlossen · %3$s noch ausstehend',
    'it' => '%1$s report verificati · %2$s completati · %3$s ancora in sospeso',
    'pl' => 'Sprawdzone raporty: %1$s · zakończone: %2$s · nadal oczekujące: %3$s',
  ),
  'Importing promotions from Amazon orders...' => 
  array(
    'fr' => 'Import des promotions depuis les commandes Amazon...',
    'es' => 'Importando las promociones de los pedidos de Amazon...',
    'de' => 'Aktionen werden aus Amazon-Bestellungen importiert...',
    'it' => 'Importazione delle promozioni dagli ordini Amazon in corso...',
    'pl' => 'Importowanie promocji z zamówień Amazon...',
  ),
  '%1$s orders scanned · %2$s promotions found · %3$s new · %4$s updated' => 
  array(
    'fr' => '%1$s commandes analysées · %2$s promotions trouvées · %3$s nouvelles · %4$s mises à jour',
    'es' => '%1$s pedidos analizados · %2$s promociones encontradas · %3$s nuevas · %4$s actualizadas',
    'de' => '%1$s Bestellungen durchsucht · %2$s Aktionen gefunden · %3$s neu · %4$s aktualisiert',
    'it' => '%1$s ordini analizzati · %2$s promozioni trovate · %3$s nuove · %4$s aggiornate',
    'pl' => 'Przeskanowane zamówienia: %1$s · znalezione promocje: %2$s · nowe: %3$s · zaktualizowane: %4$s',
  ),
  'Exporting PrestaShop cart rules as promotions...' => 
  array(
    'fr' => 'Export des règles panier PrestaShop en tant que promotions...',
    'es' => 'Exportando las reglas de carrito de PrestaShop como promociones...',
    'de' => 'PrestaShop-Warenkorbregeln werden als Aktionen exportiert...',
    'it' => 'Esportazione delle regole del carrello PrestaShop come promozioni in corso...',
    'pl' => 'Eksportowanie reguł koszyka PrestaShop jako promocji...',
  ),
  '%1$s cart rules scanned · %2$s exported' => 
  array(
    'fr' => '%1$s règles panier analysées · %2$s exportées',
    'es' => '%1$s reglas de carrito analizadas · %2$s exportadas',
    'de' => '%1$s Warenkorbregeln durchsucht · %2$s exportiert',
    'it' => '%1$s regole del carrello analizzate · %2$s esportate',
    'pl' => 'Przeskanowane reguły koszyka: %1$s · wyeksportowane: %2$s',
  ),
  'Creating PrestaShop cart rules from promotions...' => 
  array(
    'fr' => 'Création des règles panier PrestaShop à partir des promotions...',
    'es' => 'Creando reglas de carrito de PrestaShop a partir de las promociones...',
    'de' => 'PrestaShop-Warenkorbregeln werden aus Aktionen angelegt...',
    'it' => 'Creazione delle regole del carrello PrestaShop dalle promozioni in corso...',
    'pl' => 'Tworzenie reguł koszyka PrestaShop z promocji...',
  ),
  '%1$s promotions · %2$s cart rules created · %3$s errors' => 
  array(
    'fr' => '%1$s promotions · %2$s règles panier créées · %3$s erreurs',
    'es' => '%1$s promociones · %2$s reglas de carrito creadas · %3$s errores',
    'de' => '%1$s Aktionen · %2$s Warenkorbregeln angelegt · %3$s Fehler',
    'it' => '%1$s promozioni · %2$s regole del carrello create · %3$s errori',
    'pl' => 'Promocje: %1$s · utworzone reguły koszyka: %2$s · błędy: %3$s',
  ),
  'Please select a marketplace.' => 
  array(
    'fr' => 'Choisissez une place de marché.',
    'es' => 'Elige un marketplace.',
    'de' => 'Bitte wählen Sie einen Marktplatz.',
    'it' => 'Seleziona un marketplace.',
    'pl' => 'Wybierz marketplace.',
  ),
  'Marketplace saved. Refresh the page to see it in the table.' => 
  array(
    'fr' => 'Place de marché enregistrée. Actualisez la page pour la voir dans le tableau.',
    'es' => 'Marketplace guardado. Recarga la página para verlo en la tabla.',
    'de' => 'Marktplatz gespeichert. Laden Sie die Seite neu, um ihn in der Tabelle zu sehen.',
    'it' => 'Marketplace salvato. Ricarica la pagina per vederlo nella tabella.',
    'pl' => 'Marketplace zapisany. Odśwież stronę, aby zobaczyć go w tabeli.',
  ),
  'Failed to save the marketplace.' => 
  array(
    'fr' => 'Impossible d\'enregistrer la place de marché.',
    'es' => 'No se ha podido guardar el marketplace.',
    'de' => 'Der Marktplatz konnte nicht gespeichert werden.',
    'it' => 'Non è stato possibile salvare il marketplace.',
    'pl' => 'Nie udało się zapisać marketplace\'u.',
  ),
  'Not set' => 
  array(
    'fr' => 'Non défini',
    'es' => 'Sin definir',
    'de' => 'Nicht festgelegt',
    'it' => 'Non impostato',
    'pl' => 'Nie ustawiono',
  ),
  'Amazon value' => 
  array(
    'fr' => 'Valeur Amazon',
    'es' => 'Valor de Amazon',
    'de' => 'Amazon-Wert',
    'it' => 'Valore Amazon',
    'pl' => 'Wartość z Amazon',
  ),
  'PrestaShop field' => 
  array(
    'fr' => 'Champ PrestaShop',
    'es' => 'Campo de PrestaShop',
    'de' => 'PrestaShop-Feld',
    'it' => 'Campo PrestaShop',
    'pl' => 'Pole PrestaShop',
  ),
  'Fixed text' => 
  array(
    'fr' => 'Texte fixe',
    'es' => 'Texto fijo',
    'de' => 'Fester Text',
    'it' => 'Testo fisso',
    'pl' => 'Stały tekst',
  ),
  '-- pick a value --' => 
  array(
    'fr' => '-- choisir une valeur --',
    'es' => '-- elige un valor --',
    'de' => '-- Wert wählen --',
    'it' => '-- scegli un valore --',
    'pl' => '-- wybierz wartość --',
  ),
  '-- pick a field --' => 
  array(
    'fr' => '-- choisir un champ --',
    'es' => '-- elige un campo --',
    'de' => '-- Feld wählen --',
    'it' => '-- scegli un campo --',
    'pl' => '-- wybierz pole --',
  ),
  'Product feature (type the name)' => 
  array(
    'fr' => 'Caractéristique produit (saisissez le nom)',
    'es' => 'Característica del producto (escribe el nombre)',
    'de' => 'Produktmerkmal (Namen eingeben)',
    'it' => 'Caratteristica prodotto (digita il nome)',
    'pl' => 'Cecha produktu (wpisz nazwę)',
  ),
  'Combination attribute (type the group)' => 
  array(
    'fr' => 'Attribut de déclinaison (saisissez le groupe)',
    'es' => 'Atributo de combinación (escribe el grupo)',
    'de' => 'Kombinationsattribut (Gruppe eingeben)',
    'it' => 'Attributo della combinazione (digita il gruppo)',
    'pl' => 'Atrybut kombinacji (wpisz grupę)',
  ),
  'Feature or attribute group name' => 
  array(
    'fr' => 'Nom de la caractéristique ou du groupe d\'attributs',
    'es' => 'Nombre de la característica o del grupo de atributos',
    'de' => 'Name des Merkmals oder der Attributgruppe',
    'it' => 'Nome della caratteristica o del gruppo di attributi',
    'pl' => 'Nazwa cechy lub grupy atrybutów',
  ),
  'Amazon lists no strictly required extra attributes for this product type.' => 
  array(
    'fr' => 'Amazon n\'indique aucun attribut supplémentaire strictement obligatoire pour ce type de produit.',
    'es' => 'Amazon no indica ningún atributo adicional estrictamente obligatorio para este tipo de producto.',
    'de' => 'Amazon nennt für diesen Produkttyp keine zwingend erforderlichen zusätzlichen Attribute.',
    'it' => 'Amazon non indica attributi aggiuntivi strettamente obbligatori per questo tipo di prodotto.',
    'pl' => 'Amazon nie wymienia dla tego typu produktu żadnych bezwzględnie wymaganych dodatkowych atrybutów.',
  ),
  'No optional attributes.' => 
  array(
    'fr' => 'Aucun attribut facultatif.',
    'es' => 'No hay atributos opcionales.',
    'de' => 'Keine optionalen Attribute.',
    'it' => 'Nessun attributo facoltativo.',
    'pl' => 'Brak atrybutów opcjonalnych.',
  ),
  'Asking Amazon for matching product types...' => 
  array(
    'fr' => 'Recherche des types de produits correspondants auprès d\'Amazon...',
    'es' => 'Buscando en Amazon los tipos de producto que coinciden...',
    'de' => 'Passende Produkttypen werden bei Amazon abgefragt...',
    'it' => 'Ricerca presso Amazon dei tipi di prodotto corrispondenti in corso...',
    'pl' => 'Wyszukiwanie pasujących typów produktów w Amazon...',
  ),
  '-- %1$s result(s) --' => 
  array(
    'fr' => '-- %1$s résultat(s) --',
    'es' => '-- %1$s resultado(s) --',
    'de' => '-- %1$s Ergebnis(se) --',
    'it' => '-- %1$s risultati --',
    'pl' => '-- wyniki: %1$s --',
  ),
  '%1$s product type(s) found. Pick one and load its fields.' => 
  array(
    'fr' => '%1$s type(s) de produit trouvé(s). Choisissez-en un et chargez ses champs.',
    'es' => '%1$s tipo(s) de producto encontrado(s). Elige uno y carga sus campos.',
    'de' => '%1$s Produkttyp(en) gefunden. Wählen Sie einen aus und laden Sie seine Felder.',
    'it' => 'Tipi di prodotto trovati: %1$s. Scegline uno e carica i suoi campi.',
    'pl' => 'Znalezione typy produktów: %1$s. Wybierz jeden i wczytaj jego pola.',
  ),
  'Pick a product type first.' => 
  array(
    'fr' => 'Choisissez d\'abord un type de produit.',
    'es' => 'Elige primero un tipo de producto.',
    'de' => 'Wählen Sie zuerst einen Produkttyp.',
    'it' => 'Scegli prima un tipo di prodotto.',
    'pl' => 'Najpierw wybierz typ produktu.',
  ),
  'Downloading the attribute schema from Amazon...' => 
  array(
    'fr' => 'Téléchargement du schéma d\'attributs depuis Amazon...',
    'es' => 'Descargando el esquema de atributos de Amazon...',
    'de' => 'Attributschema wird von Amazon heruntergeladen...',
    'it' => 'Download dello schema degli attributi da Amazon in corso...',
    'pl' => 'Pobieranie schematu atrybutów z Amazon...',
  ),
  '%1$s: %2$s required, %3$s optional attribute(s).' => 
  array(
    'fr' => '%1$s : %2$s attribut(s) obligatoire(s), %3$s facultatif(s).',
    'es' => '%1$s: %2$s atributo(s) obligatorio(s), %3$s opcional(es).',
    'de' => '%1$s: Pflichtattribute %2$s, optionale Attribute %3$s.',
    'it' => '%1$s: %2$s attributi obbligatori, %3$s facoltativi.',
    'pl' => '%1$s — wymagane atrybuty: %2$s, opcjonalne: %3$s.',
  ),
  'Could not load the profile.' => 
  array(
    'fr' => 'Impossible de charger le profil.',
    'es' => 'No se ha podido cargar el perfil.',
    'de' => 'Das Profil konnte nicht geladen werden.',
    'it' => 'Non è stato possibile caricare il profilo.',
    'pl' => 'Nie udało się wczytać profilu.',
  ),
  'Edit profile: %1$s' => 
  array(
    'fr' => 'Modifier le profil : %1$s',
    'es' => 'Editar perfil: %1$s',
    'de' => 'Profil bearbeiten: %1$s',
    'it' => 'Modifica profilo: %1$s',
    'pl' => 'Edytuj profil: %1$s',
  ),
  'Delete this profile? Its categories fall back to the Category Mapping.' => 
  array(
    'fr' => 'Supprimer ce profil ? Ses catégories reviennent à la correspondance des catégories.',
    'es' => '¿Eliminar este perfil? Sus categorías volverán a usar la correspondencia de categorías.',
    'de' => 'Dieses Profil löschen? Seine Kategorien fallen auf die Kategoriezuordnung zurück.',
    'it' => 'Eliminare questo profilo? Le sue categorie torneranno alla corrispondenza delle categorie.',
    'pl' => 'Usunąć ten profil? Jego kategorie będą wtedy korzystać z mapowania kategorii.',
  ),
  'Profile saved. %1$s product(s) queued for the next sync. Reload the page to refresh the list.' => 
  array(
    'fr' => 'Profil enregistré. %1$s produit(s) mis en file d\'attente pour la prochaine synchronisation. Rechargez la page pour actualiser la liste.',
    'es' => 'Perfil guardado. %1$s producto(s) en cola para la próxima sincronización. Recarga la página para actualizar la lista.',
    'de' => 'Profil gespeichert. %1$s Produkt(e) für die nächste Synchronisierung in die Warteschlange gestellt. Laden Sie die Seite neu, um die Liste zu aktualisieren.',
    'it' => 'Profilo salvato. Prodotti messi in coda per la prossima sincronizzazione: %1$s. Ricarica la pagina per aggiornare l\'elenco.',
    'pl' => 'Profil zapisany. Produkty dodane do kolejki na następną synchronizację: %1$s. Przeładuj stronę, aby odświeżyć listę.',
  ),
  '%1$s rule(s) saved, %2$s product(s) queued.' => 
  array(
    'fr' => '%1$s règle(s) enregistrée(s), %2$s produit(s) mis en file d\'attente.',
    'es' => '%1$s regla(s) guardada(s), %2$s producto(s) en cola.',
    'de' => '%1$s Regel(n) gespeichert, %2$s Produkt(e) in die Warteschlange gestellt.',
    'it' => '%1$s regole salvate, %2$s prodotti messi in coda.',
    'pl' => 'Zapisane reguły: %1$s, produkty dodane do kolejki: %2$s.',
  ),
  '%1$s product rule(s) saved.' => 
  array(
    'fr' => '%1$s règle(s) par produit enregistrée(s).',
    'es' => '%1$s regla(s) de producto guardada(s).',
    'de' => '%1$s Produktregel(n) gespeichert.',
    'it' => 'Regole per prodotto salvate: %1$s.',
    'pl' => 'Zapisane reguły produktów: %1$s.',
  ),
  'Select an action first.' => 
  array(
    'fr' => 'Choisissez d\'abord une action.',
    'es' => 'Elige primero una acción.',
    'de' => 'Wählen Sie zuerst eine Aktion.',
    'it' => 'Scegli prima un\'azione.',
    'pl' => 'Najpierw wybierz akcję.',
  ),
  'Remove ALL queue entries?' => 
  array(
    'fr' => 'Supprimer TOUTES les entrées de la file d\'attente ?',
    'es' => '¿Eliminar TODAS las entradas de la cola?',
    'de' => 'ALLE Warteschlangeneinträge entfernen?',
    'it' => 'Rimuovere TUTTE le voci della coda?',
    'pl' => 'Usunąć WSZYSTKIE wpisy z kolejki?',
  ),
  'Looking for orphaned listings...' => 
  array(
    'fr' => 'Recherche des offres orphelines...',
    'es' => 'Buscando anuncios huérfanos...',
    'de' => 'Verwaiste Angebote werden gesucht...',
    'it' => 'Ricerca delle inserzioni orfane in corso...',
    'pl' => 'Wyszukiwanie osieroconych ofert...',
  ),
  '%1$s orphaned listing(s).' => 
  array(
    'fr' => '%1$s offre(s) orpheline(s).',
    'es' => '%1$s anuncio(s) huérfano(s).',
    'de' => 'Verwaiste Angebote: %1$s.',
    'it' => 'Inserzioni orfane: %1$s.',
    'pl' => 'Osierocone oferty: %1$s.',
  ),
  'No orphaned listings found.' => 
  array(
    'fr' => 'Aucune offre orpheline trouvée.',
    'es' => 'No se han encontrado anuncios huérfanos.',
    'de' => 'Keine verwaisten Angebote gefunden.',
    'it' => 'Nessuna inserzione orfana trovata.',
    'pl' => 'Nie znaleziono osieroconych ofert.',
  ),
  'Create this PrestaShop order even though stock is insufficient?' => 
  array(
    'fr' => 'Créer cette commande PrestaShop malgré un stock insuffisant ?',
    'es' => '¿Crear este pedido de PrestaShop aunque no haya existencias suficientes?',
    'de' => 'Diese PrestaShop-Bestellung trotz unzureichenden Bestands anlegen?',
    'it' => 'Creare questo ordine PrestaShop anche se la giacenza è insufficiente?',
    'pl' => 'Utworzyć to zamówienie PrestaShop mimo niewystarczającego stanu?',
  ),
  'Remove this pending order? It will not become a PrestaShop order.' => 
  array(
    'fr' => 'Supprimer cette commande en attente ? Elle ne deviendra pas une commande PrestaShop.',
    'es' => '¿Quitar este pedido pendiente? No se convertirá en un pedido de PrestaShop.',
    'de' => 'Diese ausstehende Bestellung entfernen? Sie wird dann keine PrestaShop-Bestellung.',
    'it' => 'Rimuovere questo ordine in sospeso? Non diventerà un ordine PrestaShop.',
    'pl' => 'Usunąć to oczekujące zamówienie? Nie stanie się ono zamówieniem PrestaShop.',
  ),
  'Enter the template name exactly as in Seller Central.' => 
  array(
    'fr' => 'Saisissez le nom du modèle exactement comme dans Seller Central.',
    'es' => 'Introduce el nombre de la plantilla exactamente como aparece en Seller Central.',
    'de' => 'Geben Sie den Vorlagennamen genau wie in Seller Central ein.',
    'it' => 'Inserisci il nome del modello esattamente come in Seller Central.',
    'pl' => 'Wpisz nazwę szablonu dokładnie tak, jak w Seller Central.',
  ),
  'Range saved.' => 
  array(
    'fr' => 'Tranche enregistrée.',
    'es' => 'Intervalo guardado.',
    'de' => 'Bereich gespeichert.',
    'it' => 'Fascia salvata.',
    'pl' => 'Zakres zapisany.',
  ),
  'Tick at least one thing to update.' => 
  array(
    'fr' => 'Cochez au moins un élément à mettre à jour.',
    'es' => 'Marca al menos un elemento que actualizar.',
    'de' => 'Haken Sie mindestens ein zu aktualisierendes Element an.',
    'it' => 'Seleziona almeno un elemento da aggiornare.',
    'pl' => 'Zaznacz co najmniej jedną rzecz do zaktualizowania.',
  ),
  'This overwrites PrestaShop data for products that exist on both sides. Continue?' => 
  array(
    'fr' => 'Cette opération écrase les données PrestaShop des produits présents des deux côtés. Continuer ?',
    'es' => 'Esto sobrescribe los datos de PrestaShop de los productos que existen en ambos lados. ¿Continuar?',
    'de' => 'Dadurch werden PrestaShop-Daten für Produkte überschrieben, die auf beiden Seiten vorhanden sind. Fortfahren?',
    'it' => 'Questa operazione sovrascrive i dati PrestaShop dei prodotti presenti su entrambi i lati. Continuare?',
    'pl' => 'To nadpisze dane PrestaShop dla produktów istniejących po obu stronach. Kontynuować?',
  ),
  'Updating from Amazon...' => 
  array(
    'fr' => 'Mise à jour depuis Amazon...',
    'es' => 'Actualizando desde Amazon...',
    'de' => 'Wird von Amazon aktualisiert...',
    'it' => 'Aggiornamento da Amazon in corso...',
    'pl' => 'Aktualizowanie z Amazon...',
  ),
  '%1$s matched product(s) · %2$s content · %3$s price · %4$s stock · %5$s feature(s) · %6$s deactivated · %7$s failed' => 
  array(
    'fr' => '%1$s produit(s) rapproché(s) · %2$s contenu · %3$s prix · %4$s stock · %5$s caractéristique(s) · %6$s désactivé(s) · %7$s en échec',
    'es' => '%1$s producto(s) emparejado(s) · %2$s contenido · %3$s precio · %4$s existencias · %5$s característica(s) · %6$s desactivado(s) · %7$s con error',
    'de' => '%1$s Produkt(e) zugeordnet · %2$s Inhalt · %3$s Preis · %4$s Bestand · %5$s Merkmal(e) · %6$s deaktiviert · %7$s fehlgeschlagen',
    'it' => '%1$s prodotti abbinati · %2$s contenuto · %3$s prezzo · %4$s giacenza · %5$s caratteristiche · %6$s disattivati · %7$s non riusciti',
    'pl' => 'Dopasowane produkty: %1$s · treść: %2$s · cena: %3$s · stan: %4$s · cechy: %5$s · dezaktywowane: %6$s · nieudane: %7$s',
  ),
  'Connecting to the mailbox...' => 
  array(
    'fr' => 'Connexion à la boîte mail...',
    'es' => 'Conectando con el buzón...',
    'de' => 'Verbindung zum Postfach wird hergestellt...',
    'it' => 'Connessione alla casella di posta in corso...',
    'pl' => 'Łączenie ze skrzynką pocztową...',
  ),
  '%1$s unread message(s) scanned · %2$s quoting an Amazon order · %3$s filed into Customer Service · %4$s left alone' => 
  array(
    'fr' => '%1$s message(s) non lu(s) analysé(s) · %2$s citant une commande Amazon · %3$s classé(s) dans le Service client · %4$s laissé(s) de côté',
    'es' => '%1$s mensaje(s) no leído(s) analizado(s) · %2$s citan un pedido de Amazon · %3$s archivado(s) en el Servicio de atención al cliente · %4$s sin tocar',
    'de' => '%1$s ungelesene Nachricht(en) durchsucht · %2$s mit Bezug auf eine Amazon-Bestellung · %3$s im Kundenservice abgelegt · %4$s unberührt gelassen',
    'it' => '%1$s messaggi non letti analizzati · %2$s citano un ordine Amazon · %3$s archiviati nel Servizio clienti · %4$s lasciati intatti',
    'pl' => 'Przeskanowane nieprzeczytane wiadomości: %1$s · powołujące się na zamówienie Amazon: %2$s · zapisane w Obsłudze klienta: %3$s · pozostawione bez zmian: %4$s',
  ),
  'Checking...' => 
  array(
    'fr' => 'Vérification...',
    'es' => 'Comprobando...',
    'de' => 'Wird geprüft...',
    'it' => 'Verifica in corso...',
    'pl' => 'Sprawdzanie...',
  ),
  'Active products with no reference' => 
  array(
    'fr' => 'Produits actifs sans référence',
    'es' => 'Productos activos sin referencia',
    'de' => 'Aktive Produkte ohne Artikelnummer',
    'it' => 'Prodotti attivi senza riferimento',
    'pl' => 'Aktywne produkty bez referencji',
  ),
  'Duplicated references' => 
  array(
    'fr' => 'Références en double',
    'es' => 'Referencias duplicadas',
    'de' => 'Doppelte Artikelnummern',
    'it' => 'Riferimenti duplicati',
    'pl' => 'Zduplikowane referencje',
  ),
  'Combinations with no reference' => 
  array(
    'fr' => 'Déclinaisons sans référence',
    'es' => 'Combinaciones sin referencia',
    'de' => 'Kombinationen ohne Artikelnummer',
    'it' => 'Combinazioni senza riferimento',
    'pl' => 'Kombinacje bez referencji',
  ),
  'Active products with no EAN and no UPC' => 
  array(
    'fr' => 'Produits actifs sans EAN ni UPC',
    'es' => 'Productos activos sin EAN ni UPC',
    'de' => 'Aktive Produkte ohne EAN und ohne UPC',
    'it' => 'Prodotti attivi senza EAN né UPC',
    'pl' => 'Aktywne produkty bez EAN i UPC',
  ),
  'Your catalogue is ready to sync.' => 
  array(
    'fr' => 'Votre catalogue est prêt à être synchronisé.',
    'es' => 'Tu catálogo está listo para sincronizarse.',
    'de' => 'Ihr Katalog ist bereit für die Synchronisierung.',
    'it' => 'Il tuo catalogo è pronto per la sincronizzazione.',
    'pl' => 'Twój katalog jest gotowy do synchronizacji.',
  ),
  'Fix these with the CSV editor below before publishing. Amazon cannot match products without a unique reference.' => 
  array(
    'fr' => 'Corrigez-les avec l\'éditeur CSV ci-dessous avant de publier. Amazon ne peut pas rapprocher des produits sans référence unique.',
    'es' => 'Corrígelos con el editor CSV de abajo antes de publicar. Amazon no puede emparejar productos sin una referencia única.',
    'de' => 'Korrigieren Sie diese vor der Veröffentlichung mit dem CSV-Editor unten. Amazon kann Produkte ohne eindeutige Artikelnummer nicht zuordnen.',
    'it' => 'Correggili con l\'editor CSV qui sotto prima di pubblicare. Amazon non può abbinare i prodotti senza un riferimento univoco.',
    'pl' => 'Popraw je w edytorze CSV poniżej przed publikacją. Amazon nie dopasuje produktów bez unikalnej referencji.',
  ),
  'Choose a CSV file first.' => 
  array(
    'fr' => 'Choisissez d\'abord un fichier CSV.',
    'es' => 'Elige primero un archivo CSV.',
    'de' => 'Wählen Sie zuerst eine CSV-Datei.',
    'it' => 'Scegli prima un file CSV.',
    'pl' => 'Najpierw wybierz plik CSV.',
  ),
  'This rewrites references and barcodes in your PrestaShop catalogue. Continue?' => 
  array(
    'fr' => 'Cette opération réécrit les références et les codes-barres de votre catalogue PrestaShop. Continuer ?',
    'es' => 'Esto reescribe las referencias y los códigos de barras de tu catálogo de PrestaShop. ¿Continuar?',
    'de' => 'Dadurch werden Artikelnummern und Barcodes in Ihrem PrestaShop-Katalog überschrieben. Fortfahren?',
    'it' => 'Questa operazione riscrive riferimenti e codici a barre nel tuo catalogo PrestaShop. Continuare?',
    'pl' => 'To nadpisze referencje i kody kreskowe w Twoim katalogu PrestaShop. Kontynuować?',
  ),
  'Importing...' => 
  array(
    'fr' => 'Import en cours...',
    'es' => 'Importando...',
    'de' => 'Wird importiert...',
    'it' => 'Importazione in corso...',
    'pl' => 'Importowanie...',
  ),
  '%1$s row(s) updated · %2$s skipped' => 
  array(
    'fr' => '%1$s ligne(s) mise(s) à jour · %2$s ignorée(s)',
    'es' => '%1$s fila(s) actualizada(s) · %2$s omitida(s)',
    'de' => '%1$s Zeile(n) aktualisiert · %2$s übersprungen',
    'it' => '%1$s righe aggiornate · %2$s saltate',
    'pl' => 'Zaktualizowane wiersze: %1$s · pominięte: %2$s',
  ),
  '...and %1$s more.' => 
  array(
    'fr' => '...et %1$s de plus.',
    'es' => '...y %1$s más.',
    'de' => '...und %1$s weitere.',
    'it' => '...e altri %1$s.',
    'pl' => '...i jeszcze %1$s.',
  ),
  'Looking for listings whose product is gone or excluded...' => 
  array(
    'fr' => 'Recherche des offres dont le produit a disparu ou est exclu...',
    'es' => 'Buscando anuncios cuyo producto ya no existe o está excluido...',
    'de' => 'Angebote, deren Produkt fehlt oder ausgeschlossen ist, werden gesucht...',
    'it' => 'Ricerca delle inserzioni con prodotto eliminato o escluso in corso...',
    'pl' => 'Wyszukiwanie ofert, których produkt zniknął lub został wykluczony...',
  ),
  '%1$s listing(s) would be deleted. Tick the ones you really want gone.' => 
  array(
    'fr' => '%1$s offre(s) à supprimer. Cochez celles que vous voulez vraiment retirer.',
    'es' => 'Se eliminarían %1$s anuncio(s). Marca los que de verdad quieras retirar.',
    'de' => '%1$s Angebot(e) würden gelöscht. Haken Sie die an, die wirklich entfernt werden sollen.',
    'it' => 'Verrebbero eliminate %1$s inserzioni. Seleziona quelle che vuoi davvero rimuovere.',
    'pl' => 'Oferty, które zostałyby usunięte: %1$s. Zaznacz te, które naprawdę chcesz usunąć.',
  ),
  'Nothing selected.' => 
  array(
    'fr' => 'Aucune sélection.',
    'es' => 'No hay nada seleccionado.',
    'de' => 'Nichts ausgewählt.',
    'it' => 'Nessuna selezione.',
    'pl' => 'Nic nie zaznaczono.',
  ),
  'Delete %1$s listing(s) from Amazon? The offers and their history go with them.' => 
  array(
    'fr' => 'Supprimer %1$s offre(s) sur Amazon ? Les offres et leur historique disparaîtront avec elles.',
    'es' => '¿Eliminar %1$s anuncio(s) de Amazon? Las ofertas y su historial se eliminan con ellos.',
    'de' => '%1$s Angebot(e) bei Amazon löschen? Die Angebote werden samt ihrer Historie entfernt.',
    'it' => 'Eliminare %1$s inserzioni da Amazon? Le offerte e la loro cronologia verranno eliminate insieme.',
    'pl' => 'Usunąć z Amazon zaznaczone oferty (%1$s)? Zniknie też ich historia.',
  ),
  'Deleting from Amazon...' => 
  array(
    'fr' => 'Suppression sur Amazon...',
    'es' => 'Eliminando de Amazon...',
    'de' => 'Wird bei Amazon gelöscht...',
    'it' => 'Eliminazione da Amazon in corso...',
    'pl' => 'Usuwanie z Amazon...',
  ),
  '%1$s requested · %2$s deleted · %3$s failed' => 
  array(
    'fr' => '%1$s demandée(s) · %2$s supprimée(s) · %3$s en échec',
    'es' => '%1$s solicitados · %2$s eliminados · %3$s con error',
    'de' => '%1$s angefordert · %2$s gelöscht · %3$s fehlgeschlagen',
    'it' => '%1$s richieste · %2$s eliminate · %3$s non riuscite',
    'pl' => 'Zlecone: %1$s · usunięte: %2$s · nieudane: %3$s',
  ),
  'Enter a feed ID. You will find it on the Products tab after submitting a bulk feed.' => 
  array(
    'fr' => 'Saisissez un ID de flux. Vous le trouverez dans l\'onglet Produits après l\'envoi d\'un flux groupé.',
    'es' => 'Introduce un ID de feed. Lo encontrarás en la pestaña Productos después de enviar un feed masivo.',
    'de' => 'Geben Sie eine Feed-ID ein. Sie finden sie auf der Registerkarte Produkte, nachdem Sie einen Sammelfeed gesendet haben.',
    'it' => 'Inserisci un ID del feed. Lo trovi nella scheda Prodotti dopo aver inviato un feed massivo.',
    'pl' => 'Wpisz identyfikator feedu. Znajdziesz go w zakładce Produkty po wysłaniu feedu zbiorczego.',
  ),
  'Unknown entity type.' => 
  array(
    'fr' => 'Type d\'entité inconnu.',
    'es' => 'Tipo de entidad desconocido.',
    'de' => 'Unbekannter Entitätstyp.',
    'it' => 'Tipo di entità sconosciuto.',
    'pl' => 'Nieznany typ obiektu.',
  ),
  'Invalid rows payload.' => 
  array(
    'fr' => 'Données de lignes non valides.',
    'es' => 'Los datos de las filas no son válidos.',
    'de' => 'Ungültige Zeilendaten.',
    'it' => 'Dati delle righe non validi.',
    'pl' => 'Nieprawidłowe dane wierszy.',
  ),
  'Unknown queue action.' => 
  array(
    'fr' => 'Action de file d\'attente inconnue.',
    'es' => 'Acción de cola desconocida.',
    'de' => 'Unbekannte Warteschlangenaktion.',
    'it' => 'Azione della coda sconosciuta.',
    'pl' => 'Nieznana akcja kolejki.',
  ),
  'Orphans are computed from the last Amazon-side sync. Run the Amazon to PrestaShop sync on the Products tab first for an up-to-date list.' => 
  array(
    'fr' => 'Les offres orphelines sont calculées à partir de la dernière synchronisation côté Amazon. Lancez d\'abord la synchronisation Amazon vers PrestaShop dans l\'onglet Produits pour obtenir une liste à jour.',
    'es' => 'Los huérfanos se calculan a partir de la última sincronización del lado de Amazon. Ejecuta primero la sincronización de Amazon a PrestaShop en la pestaña Productos para obtener una lista actualizada.',
    'de' => 'Verwaiste Angebote werden aus der letzten Synchronisierung auf Amazon-Seite ermittelt. Führen Sie zuerst auf der Registerkarte Produkte die Synchronisierung Amazon → PrestaShop aus, um eine aktuelle Liste zu erhalten.',
    'it' => 'Le inserzioni orfane vengono calcolate dall\'ultima sincronizzazione lato Amazon. Per un elenco aggiornato, esegui prima la sincronizzazione da Amazon a PrestaShop nella scheda Prodotti.',
    'pl' => 'Osierocone oferty są wyznaczane na podstawie ostatniej synchronizacji po stronie Amazon. Aby uzyskać aktualną listę, uruchom najpierw synchronizację z Amazon do PrestaShop w zakładce Produkty.',
  ),
  'Import orders' => 
  array(
    'fr' => 'Importer les commandes',
    'es' => 'Importar pedidos',
    'de' => 'Bestellungen importieren',
    'it' => 'Importa gli ordini',
    'pl' => 'Import zamówień',
  ),
  'Create PrestaShop orders' => 
  array(
    'fr' => 'Créer les commandes PrestaShop',
    'es' => 'Crear pedidos de PrestaShop',
    'de' => 'PrestaShop-Bestellungen anlegen',
    'it' => 'Crea gli ordini PrestaShop',
    'pl' => 'Tworzenie zamówień PrestaShop',
  ),
  'Sync stock' => 
  array(
    'fr' => 'Synchroniser le stock',
    'es' => 'Sincronizar existencias',
    'de' => 'Bestand abgleichen',
    'it' => 'Sincronizza le giacenze',
    'pl' => 'Synchronizacja stanów',
  ),
  'Full product sync' => 
  array(
    'fr' => 'Synchronisation complète des produits',
    'es' => 'Sincronización completa de productos',
    'de' => 'Vollständige Produktsynchronisierung',
    'it' => 'Sincronizzazione completa dei prodotti',
    'pl' => 'Pełna synchronizacja produktów',
  ),
  'Import returns' => 
  array(
    'fr' => 'Importer les retours',
    'es' => 'Importar devoluciones',
    'de' => 'Retouren importieren',
    'it' => 'Importa i resi',
    'pl' => 'Import zwrotów',
  ),
  'Process returns' => 
  array(
    'fr' => 'Traiter les retours',
    'es' => 'Procesar devoluciones',
    'de' => 'Retouren verarbeiten',
    'it' => 'Elabora i resi',
    'pl' => 'Przetwarzanie zwrotów',
  ),
  'FBA inventory sync' => 
  array(
    'fr' => 'Synchronisation du stock FBA',
    'es' => 'Sincronización del inventario FBA',
    'de' => 'FBA-Bestandsabgleich',
    'it' => 'Sincronizzazione dell\'inventario FBA',
    'pl' => 'Synchronizacja zapasów FBA',
  ),
  'Repricing cycle' => 
  array(
    'fr' => 'Cycle de repricing',
    'es' => 'Ciclo de reprecio',
    'de' => 'Repricing-Durchlauf',
    'it' => 'Ciclo di repricing',
    'pl' => 'Cykl repricingu',
  ),
  'Fetch fees' => 
  array(
    'fr' => 'Récupérer les frais',
    'es' => 'Recuperar tarifas',
    'de' => 'Gebühren holen',
    'it' => 'Recupera i costi',
    'pl' => 'Pobieranie opłat',
  ),
  'Poll reports' => 
  array(
    'fr' => 'Interroger les rapports',
    'es' => 'Consultar informes',
    'de' => 'Berichte abfragen',
    'it' => 'Interroga i report',
    'pl' => 'Sprawdzanie raportów',
  ),
  'Sync promotions' => 
  array(
    'fr' => 'Synchroniser les promotions',
    'es' => 'Sincronizar promociones',
    'de' => 'Aktionen abgleichen',
    'it' => 'Sincronizza le promozioni',
    'pl' => 'Synchronizacja promocji',
  ),
  'Import orders, all marketplaces' => 
  array(
    'fr' => 'Importer les commandes, toutes places de marché',
    'es' => 'Importar pedidos, todos los marketplaces',
    'de' => 'Bestellungen importieren, alle Marktplätze',
    'it' => 'Importa gli ordini, tutti i marketplace',
    'pl' => 'Import zamówień, wszystkie marketplace\'y',
  ),
  'Product sync, all marketplaces' => 
  array(
    'fr' => 'Synchronisation des produits, toutes places de marché',
    'es' => 'Sincronización de productos, todos los marketplaces',
    'de' => 'Produktsynchronisierung, alle Marktplätze',
    'it' => 'Sincronizzazione dei prodotti, tutti i marketplace',
    'pl' => 'Synchronizacja produktów, wszystkie marketplace\'y',
  ),
  'Request reviews' => 
  array(
    'fr' => 'Demander des avis',
    'es' => 'Solicitar valoraciones',
    'de' => 'Bewertungen anfordern',
    'it' => 'Richiedi le recensioni',
    'pl' => 'Prośby o opinię',
  ),
  'Process feed queue' => 
  array(
    'fr' => 'Traiter la file d\'attente des flux',
    'es' => 'Procesar la cola de feeds',
    'de' => 'Feed-Warteschlange verarbeiten',
    'it' => 'Elabora la coda dei feed',
    'pl' => 'Przetwarzanie kolejki feedów',
  ),
  'Settle remote carts' => 
  array(
    'fr' => 'Solder les paniers distants',
    'es' => 'Liquidar carritos remotos',
    'de' => 'Externe Warenkörbe auflösen',
    'it' => 'Chiudi i carrelli remoti',
    'pl' => 'Rozliczanie zdalnych koszyków',
  ),
  'Fetch buyer messages' => 
  array(
    'fr' => 'Relever les messages des acheteurs',
    'es' => 'Recoger mensajes de compradores',
    'de' => 'Käufernachrichten abrufen',
    'it' => 'Recupera i messaggi degli acquirenti',
    'pl' => 'Pobieranie wiadomości kupujących',
  ),
  'Upload invoices' => 
  array(
    'fr' => 'Envoyer les factures',
    'es' => 'Subir facturas',
    'de' => 'Rechnungen hochladen',
    'it' => 'Carica le fatture',
    'pl' => 'Przesyłanie faktur',
  ),
  'Purge buyer data past retention' => 
  array(
    'fr' => 'Purger les données acheteur après le délai de conservation',
    'es' => 'Purgar datos de compradores fuera del plazo de conservación',
    'de' => 'Käuferdaten nach Ablauf der Aufbewahrungsfrist löschen',
    'it' => 'Elimina i dati degli acquirenti oltre il periodo di conservazione',
    'pl' => 'Usuwanie danych kupujących po okresie przechowywania',
  ),
  'Catalogue' => 
  array(
    'fr' => 'Catalogue',
    'es' => 'Catálogo',
    'de' => 'Katalog',
    'it' => 'Catalogo',
    'pl' => 'Katalog',
  ),
  'Pricing' => 
  array(
    'fr' => 'Tarification',
    'es' => 'Precios',
    'de' => 'Preise',
    'it' => 'Prezzi',
    'pl' => 'Ceny',
  ),
  'Pending order not found.' => 
  array(
    'fr' => 'Commande en attente introuvable.',
    'es' => 'Pedido pendiente no encontrado.',
    'de' => 'Ausstehende Bestellung nicht gefunden.',
    'it' => 'Ordine in sospeso non trovato.',
    'pl' => 'Nie znaleziono oczekującego zamówienia.',
  ),
  'Pending order removed.' => 
  array(
    'fr' => 'Commande en attente supprimée.',
    'es' => 'Pedido pendiente eliminado.',
    'de' => 'Ausstehende Bestellung entfernt.',
    'it' => 'Ordine in sospeso rimosso.',
    'pl' => 'Oczekujące zamówienie usunięte.',
  ),
  'PrestaShop order #%d created.' => 
  array(
    'fr' => 'Commande PrestaShop #%d créée.',
    'es' => 'Pedido de PrestaShop #%d creado.',
    'de' => 'PrestaShop-Bestellung #%d angelegt.',
    'it' => 'Ordine PrestaShop #%d creato.',
    'pl' => 'Utworzono zamówienie PrestaShop nr %d.',
  ),
  'Unknown pending order action.' => 
  array(
    'fr' => 'Action inconnue pour la commande en attente.',
    'es' => 'Acción desconocida para el pedido pendiente.',
    'de' => 'Unbekannte Aktion für ausstehende Bestellungen.',
    'it' => 'Azione sconosciuta per l\'ordine in sospeso.',
    'pl' => 'Nieznana akcja dla oczekującego zamówienia.',
  ),
  'Template name is required.' => 
  array(
    'fr' => 'Le nom du modèle est obligatoire.',
    'es' => 'El nombre de la plantilla es obligatorio.',
    'de' => 'Der Vorlagenname ist erforderlich.',
    'it' => 'Il nome del modello è obbligatorio.',
    'pl' => 'Nazwa szablonu jest wymagana.',
  ),
  'Category and Amazon Product Type are required.' => 
  array(
    'fr' => 'La catégorie et le type de produit Amazon sont obligatoires.',
    'es' => 'La categoría y el tipo de producto de Amazon son obligatorios.',
    'de' => 'Kategorie und Amazon-Produkttyp sind erforderlich.',
    'it' => 'Categoria e tipo di prodotto Amazon sono obbligatori.',
    'pl' => 'Kategoria i typ produktu Amazon są wymagane.',
  ),
  'No mapping ID provided.' => 
  array(
    'fr' => 'Aucun ID de correspondance fourni.',
    'es' => 'No se ha indicado ningún ID de correspondencia.',
    'de' => 'Keine Zuordnungs-ID angegeben.',
    'it' => 'Nessun ID di corrispondenza fornito.',
    'pl' => 'Nie podano identyfikatora mapowania.',
  ),
  'Rule name is required.' => 
  array(
    'fr' => 'Le nom de la règle est obligatoire.',
    'es' => 'El nombre de la regla es obligatorio.',
    'de' => 'Der Regelname ist erforderlich.',
    'it' => 'Il nome della regola è obbligatorio.',
    'pl' => 'Nazwa reguły jest wymagana.',
  ),
);
