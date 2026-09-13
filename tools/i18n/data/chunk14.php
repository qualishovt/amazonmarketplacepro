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
 * Multistore (1.6.0): the All shops banner and notes, the Shop columns, and
 * the messages that refuse an action meant for another shop.
 *
 * The classes translate through AmazonI18n, which undoes PrestaShop's HTML
 * escaping, so a straight double quote or a > from a menu path is safe here
 * where the English has one. No translation may contain & or <. Placeholders
 * are filled in with sprintf() after translation; positional ones may move.
 */
return array(
  'You are editing the settings for a shop group:' => 
  array(
    'fr' => 'Vous modifiez les réglages d\'un groupe de boutiques :',
    'es' => 'Estás editando los ajustes de un grupo de tiendas:',
    'de' => 'Sie bearbeiten die Einstellungen für eine Shop-Gruppe:',
    'it' => 'Stai modificando le impostazioni di un gruppo di negozi:',
    'pl' => 'Edytujesz ustawienia grupy sklepów:',
  ),
  'You are editing the settings for all shops.' => 
  array(
    'fr' => 'Vous modifiez les réglages pour toutes les boutiques.',
    'es' => 'Estás editando los ajustes de todas las tiendas.',
    'de' => 'Sie bearbeiten die Einstellungen für alle Shops.',
    'it' => 'Stai modificando le impostazioni per tutti i negozi.',
    'pl' => 'Edytujesz ustawienia dla wszystkich sklepów.',
  ),
  'A shop that saved its own value keeps it.' => 
  array(
    'fr' => 'Une boutique ayant enregistré sa propre valeur la conserve.',
    'es' => 'Una tienda que guardó su propio valor lo conserva.',
    'de' => 'Ein Shop, der einen eigenen Wert gespeichert hat, behält ihn.',
    'it' => 'Un negozio che ha salvato un proprio valore lo mantiene.',
    'pl' => 'Sklep, który zapisał własną wartość, ją zachowuje.',
  ),
  'To connect Amazon, import orders or send products, choose a shop at the top of the page.' => 
  array(
    'fr' => 'Pour connecter Amazon, importer des commandes ou envoyer des produits, choisissez une boutique en haut de la page.',
    'es' => 'Para conectar Amazon, importar pedidos o enviar productos, elige una tienda en la parte superior de la página.',
    'de' => 'Um Amazon zu verbinden, Bestellungen zu importieren oder Produkte zu senden, wählen Sie oben auf der Seite einen Shop.',
    'it' => 'Per connettere Amazon, importare ordini o inviare prodotti, scegli un negozio in alto nella pagina.',
    'pl' => 'Aby połączyć się z Amazon, zaimportować zamówienia lub wysłać produkty, wybierz sklep u góry strony.',
  ),
  'These shops saved their own value for some settings. Each such setting is marked below.' => 
  array(
    'fr' => 'Ces boutiques ont enregistré leur propre valeur pour certains réglages. Chaque réglage concerné est signalé ci-dessous.',
    'es' => 'Estas tiendas guardaron su propio valor para algunos ajustes. Cada ajuste así se indica más abajo.',
    'de' => 'Diese Shops haben für einige Einstellungen einen eigenen Wert gespeichert. Jede betroffene Einstellung ist unten markiert.',
    'it' => 'Questi negozi hanno salvato un proprio valore per alcune impostazioni. Ogni impostazione interessata è segnalata qui sotto.',
    'pl' => 'Te sklepy zapisały własną wartość dla niektórych ustawień. Każde takie ustawienie jest oznaczone poniżej.',
  ),
  'own settings:' => 
  array(
    'fr' => 'réglages propres :',
    'es' => 'ajustes propios:',
    'de' => 'eigene Einstellungen:',
    'it' => 'impostazioni proprie:',
    'pl' => 'własne ustawienia:',
  ),
  'Shop:' => 
  array(
    'fr' => 'Boutique :',
    'es' => 'Tienda:',
    'de' => 'Onlineshop:',
    'it' => 'Negozio:',
    'pl' => 'Sklep:',
  ),
  'Each shop connects its own Amazon seller account. Choose a shop at the top of the page to connect it or to see its connection.' => 
  array(
    'fr' => 'Chaque boutique connecte son propre compte vendeur Amazon. Choisissez une boutique en haut de la page pour la connecter ou pour voir sa connexion.',
    'es' => 'Cada tienda conecta su propia cuenta de vendedor de Amazon. Elige una tienda en la parte superior de la página para conectarla o ver su conexión.',
    'de' => 'Jeder Shop verbindet sich mit einem eigenen Amazon-Verkäuferkonto. Wählen Sie oben auf der Seite einen Shop, um ihn zu verbinden oder seine Verbindung anzusehen.',
    'it' => 'Ogni negozio collega un proprio account venditore Amazon. Scegli un negozio in alto nella pagina per collegarlo o per vedere la sua connessione.',
    'pl' => 'Każdy sklep łączy się z własnym kontem sprzedawcy Amazon. Wybierz sklep u góry strony, aby go połączyć lub zobaczyć jego połączenie.',
  ),
  'All shops' => 
  array(
    'fr' => 'Toutes les boutiques',
    'es' => 'Todas las tiendas',
    'de' => 'Alle Shops',
    'it' => 'Tutti i negozi',
    'pl' => 'Wszystkie sklepy',
  ),
  'Each shop has its own schedule, cron address and cron token. Choose a shop at the top of the page to see and change them.' => 
  array(
    'fr' => 'Chaque boutique a sa propre planification, son adresse cron et son jeton cron. Choisissez une boutique en haut de la page pour les voir et les modifier.',
    'es' => 'Cada tienda tiene su propia programación, dirección de cron y token de cron. Elige una tienda en la parte superior de la página para verlos y cambiarlos.',
    'de' => 'Jeder Shop hat einen eigenen Zeitplan, eine eigene Cron-Adresse und ein eigenes Cron-Token. Wählen Sie oben auf der Seite einen Shop, um sie anzusehen und zu ändern.',
    'it' => 'Ogni negozio ha una propria pianificazione, un indirizzo cron e un token cron. Scegli un negozio in alto nella pagina per vederli e modificarli.',
    'pl' => 'Każdy sklep ma własny harmonogram, adres crona i token crona. Wybierz sklep u góry strony, aby je zobaczyć i zmienić.',
  ),
  'Shop' => 
  array(
    'fr' => 'Boutique',
    'es' => 'Tienda',
    'de' => 'Onlineshop',
    'it' => 'Negozio',
    'pl' => 'Sklep',
  ),
  'Choose a shop at the top of the page to use this.' => 
  array(
    'fr' => 'Choisissez une boutique en haut de la page pour utiliser cette fonction.',
    'es' => 'Elige una tienda en la parte superior de la página para usar esto.',
    'de' => 'Wählen Sie oben auf der Seite einen Shop, um dies zu nutzen.',
    'it' => 'Scegli un negozio in alto nella pagina per usare questa funzione.',
    'pl' => 'Wybierz sklep u góry strony, aby z tego skorzystać.',
  ),
  'These shops keep their own value: %1$s' => 
  array(
    'fr' => 'Ces boutiques conservent leur propre valeur : %1$s',
    'es' => 'Estas tiendas conservan su propio valor: %1$s',
    'de' => 'Diese Shops behalten einen eigenen Wert: %1$s',
    'it' => 'Questi negozi mantengono un proprio valore: %1$s',
    'pl' => 'Te sklepy zachowują własną wartość: %1$s',
  ),
  'Delete failed' => 
  array(
    'fr' => 'Échec de la suppression',
    'es' => 'Error al eliminar',
    'de' => 'Löschen fehlgeschlagen',
    'it' => 'Eliminazione non riuscita',
    'pl' => 'Usuwanie nie powiodło się',
  ),
  'These settings apply to:' => 
  array(
    'fr' => 'Ces réglages s\'appliquent à :',
    'es' => 'Estos ajustes se aplican a:',
    'de' => 'Diese Einstellungen gelten für:',
    'it' => 'Queste impostazioni si applicano a:',
    'pl' => 'Te ustawienia dotyczą:',
  ),
  'all shops' => 
  array(
    'fr' => 'toutes les boutiques',
    'es' => 'todas las tiendas',
    'de' => 'alle Shops',
    'it' => 'tutti i negozi',
    'pl' => 'wszystkich sklepów',
  ),
  'A shop that saved its own settings for this product keeps them.' => 
  array(
    'fr' => 'Une boutique qui a enregistré ses propres réglages pour ce produit les conserve.',
    'es' => 'Una tienda que guardó sus propios ajustes para este producto los conserva.',
    'de' => 'Ein Shop, der eigene Einstellungen für dieses Produkt gespeichert hat, behält sie.',
    'it' => 'Un negozio che ha salvato impostazioni proprie per questo prodotto le mantiene.',
    'pl' => 'Sklep, który zapisał własne ustawienia dla tego produktu, je zachowuje.',
  ),
  'This shop uses the settings saved for all shops until you save the product here.' => 
  array(
    'fr' => 'Cette boutique utilise les réglages enregistrés pour toutes les boutiques, jusqu\'à ce que vous enregistriez le produit ici.',
    'es' => 'Esta tienda usa los ajustes guardados para todas las tiendas hasta que guardes el producto aquí.',
    'de' => 'Dieser Shop verwendet die für alle Shops gespeicherten Einstellungen, bis Sie das Produkt hier speichern.',
    'it' => 'Questo negozio usa le impostazioni salvate per tutti i negozi finché non salvi il prodotto qui.',
    'pl' => 'Ten sklep korzysta z ustawień zapisanych dla wszystkich sklepów, dopóki nie zapiszesz tu produktu.',
  ),
  'The shop %s is already connected to this Amazon seller account on this marketplace. Each shop needs its own seller account or marketplace, so the seller ID and marketplace were not saved.' => 
  array(
    'fr' => 'La boutique %s est déjà connectée à ce compte vendeur Amazon sur cette place de marché. Chaque boutique a besoin de son propre compte vendeur ou de sa propre place de marché : l\'ID vendeur et la place de marché n\'ont pas été enregistrés.',
    'es' => 'La tienda %s ya está conectada a esta cuenta de vendedor de Amazon en este marketplace. Cada tienda necesita su propia cuenta de vendedor o marketplace, así que el ID de vendedor y el marketplace no se han guardado.',
    'de' => 'Der Shop %s ist bereits mit diesem Amazon-Verkäuferkonto auf diesem Marktplatz verbunden. Jeder Shop benötigt ein eigenes Verkäuferkonto oder einen eigenen Marktplatz, daher wurden die Verkäufer-ID und der Marktplatz nicht gespeichert.',
    'it' => 'Il negozio %s è già connesso a questo account venditore Amazon su questo marketplace. Ogni negozio ha bisogno di un proprio account venditore o marketplace, quindi l\'ID venditore e il marketplace non sono stati salvati.',
    'pl' => 'Sklep %s jest już połączony z tym kontem sprzedawcy Amazon na tym marketplace. Każdy sklep potrzebuje własnego konta sprzedawcy lub marketplace\'u, dlatego identyfikator sprzedawcy i marketplace nie zostały zapisane.',
  ),
  'The shop selection changed since this page was opened. Reload the page.' => 
  array(
    'fr' => 'La sélection de boutique a changé depuis l\'ouverture de cette page. Rechargez la page.',
    'es' => 'La selección de tienda ha cambiado desde que se abrió esta página. Recarga la página.',
    'de' => 'Die Shop-Auswahl hat sich geändert, seit diese Seite geöffnet wurde. Laden Sie die Seite neu.',
    'it' => 'La selezione del negozio è cambiata da quando questa pagina è stata aperta. Ricarica la pagina.',
    'pl' => 'Wybór sklepu zmienił się od otwarcia tej strony. Przeładuj stronę.',
  ),
  'Orders that belong to another shop, not changed (upload the report in that shop): %d' => 
  array(
    'fr' => 'Commandes appartenant à une autre boutique, non modifiées (envoyez le rapport dans cette boutique) : %d',
    'es' => 'Pedidos que pertenecen a otra tienda, sin cambios (sube el informe en esa tienda): %d',
    'de' => 'Bestellungen, die zu einem anderen Shop gehören, nicht geändert (Bericht in diesem Shop hochladen): %d',
    'it' => 'Ordini appartenenti a un altro negozio, non modificati (carica il report in quel negozio): %d',
    'pl' => 'Zamówienia należące do innego sklepu, niezmienione (prześlij raport w tamtym sklepie): %d',
  ),
  'Orders that belong to another shop, left there: %d' => 
  array(
    'fr' => 'Commandes appartenant à une autre boutique, laissées sur place : %d',
    'es' => 'Pedidos que pertenecen a otra tienda, dejados allí: %d',
    'de' => 'Bestellungen, die zu einem anderen Shop gehören, dort belassen: %d',
    'it' => 'Ordini appartenenti a un altro negozio, lasciati lì: %d',
    'pl' => 'Zamówienia należące do innego sklepu, pozostawione tam: %d',
  ),
  'The category mapping could not be deleted.' => 
  array(
    'fr' => 'La correspondance de catégorie n\'a pas pu être supprimée.',
    'es' => 'No se ha podido eliminar la correspondencia de categoría.',
    'de' => 'Die Kategoriezuordnung konnte nicht gelöscht werden.',
    'it' => 'Non è stato possibile eliminare la corrispondenza di categoria.',
    'pl' => 'Nie udało się usunąć mapowania kategorii.',
  ),
  'This order belongs to another shop. Select that shop at the top of the page to work on it.' => 
  array(
    'fr' => 'Cette commande appartient à une autre boutique. Sélectionnez cette boutique en haut de la page pour la traiter.',
    'es' => 'Este pedido pertenece a otra tienda. Selecciona esa tienda en la parte superior de la página para trabajar con él.',
    'de' => 'Diese Bestellung gehört zu einem anderen Shop. Wählen Sie diesen Shop oben auf der Seite aus, um sie zu bearbeiten.',
    'it' => 'Questo ordine appartiene a un altro negozio. Seleziona quel negozio in alto nella pagina per gestirlo.',
    'pl' => 'To zamówienie należy do innego sklepu. Wybierz tamten sklep u góry strony, aby nad nim pracować.',
  ),
  'SKU %1$s: product #%2$d already has this reference but is not in this shop. Add it to this shop, then sync again.' => 
  array(
    'fr' => 'SKU %1$s : le produit #%2$d a déjà cette référence mais n\'est pas dans cette boutique. Ajoutez-le à cette boutique, puis resynchronisez.',
    'es' => 'SKU %1$s: el producto #%2$d ya tiene esta referencia pero no está en esta tienda. Añádelo a esta tienda y vuelve a sincronizar.',
    'de' => 'SKU %1$s: Produkt #%2$d hat diese Artikelnummer bereits, ist aber nicht in diesem Shop. Fügen Sie es diesem Shop hinzu und synchronisieren Sie erneut.',
    'it' => 'SKU %1$s: il prodotto #%2$d ha già questo riferimento ma non è in questo negozio. Aggiungilo a questo negozio, poi sincronizza di nuovo.',
    'pl' => 'SKU %1$s: produkt nr %2$d ma już tę referencję, ale nie ma go w tym sklepie. Dodaj go do tego sklepu, a następnie zsynchronizuj ponownie.',
  ),
  'PrestaShop order #%d belongs to another shop. Select that shop at the top of the page and try again.' => 
  array(
    'fr' => 'La commande PrestaShop #%d appartient à une autre boutique. Sélectionnez cette boutique en haut de la page et réessayez.',
    'es' => 'El pedido de PrestaShop #%d pertenece a otra tienda. Selecciona esa tienda en la parte superior de la página e inténtalo de nuevo.',
    'de' => 'Die PrestaShop-Bestellung #%d gehört zu einem anderen Shop. Wählen Sie diesen Shop oben auf der Seite aus und versuchen Sie es erneut.',
    'it' => 'L\'ordine PrestaShop #%d appartiene a un altro negozio. Seleziona quel negozio in alto nella pagina e riprova.',
    'pl' => 'Zamówienie PrestaShop nr %d należy do innego sklepu. Wybierz tamten sklep u góry strony i spróbuj ponownie.',
  ),
  'This shipping template was not found. Reload the page and try again.' => 
  array(
    'fr' => 'Ce modèle de livraison est introuvable. Rechargez la page et réessayez.',
    'es' => 'No se ha encontrado esta plantilla de envío. Recarga la página e inténtalo de nuevo.',
    'de' => 'Diese Versandvorlage wurde nicht gefunden. Laden Sie die Seite neu und versuchen Sie es erneut.',
    'it' => 'Questo modello di spedizione non è stato trovato. Ricarica la pagina e riprova.',
    'pl' => 'Nie znaleziono tego szablonu wysyłki. Przeładuj stronę i spróbuj ponownie.',
  ),
  'This shipping template is shared by all shops. Select "All shops" at the top of the page to change it.' => 
  array(
    'fr' => 'Ce modèle de livraison est partagé par toutes les boutiques. Sélectionnez « Toutes les boutiques » en haut de la page pour le modifier.',
    'es' => 'Esta plantilla de envío es compartida por todas las tiendas. Selecciona «Todas las tiendas» en la parte superior de la página para cambiarla.',
    'de' => 'Diese Versandvorlage wird von allen Shops gemeinsam genutzt. Wählen Sie „Alle Shops“ oben auf der Seite, um sie zu ändern.',
    'it' => 'Questo modello di spedizione è condiviso da tutti i negozi. Seleziona «Tutti i negozi» in alto nella pagina per modificarlo.',
    'pl' => 'Ten szablon wysyłki jest współdzielony przez wszystkie sklepy. Wybierz „Wszystkie sklepy” u góry strony, aby go zmienić.',
  ),
  'The PrestaShop product is not in this shop' => 
  array(
    'fr' => 'Le produit PrestaShop n\'est pas dans cette boutique',
    'es' => 'El producto de PrestaShop no está en esta tienda',
    'de' => 'Das PrestaShop-Produkt ist nicht in diesem Shop',
    'it' => 'Il prodotto PrestaShop non è in questo negozio',
    'pl' => 'Produkt PrestaShop nie znajduje się w tym sklepie',
  ),
  'This Amazon seller account already sells on this marketplace from the shop "%s". Each seller account and marketplace can be used by one shop only.' => 
  array(
    'fr' => 'Ce compte vendeur Amazon vend déjà sur cette place de marché depuis la boutique « %s ». Chaque compte vendeur et chaque place de marché ne peuvent être utilisés que par une seule boutique.',
    'es' => 'Esta cuenta de vendedor de Amazon ya vende en este marketplace desde la tienda «%s». Cada cuenta de vendedor y cada marketplace solo pueden usarse en una tienda.',
    'de' => 'Dieses Amazon-Verkäuferkonto verkauft auf diesem Marktplatz bereits über den Shop „%s“. Jedes Verkäuferkonto und jeder Marktplatz kann nur von einem Shop verwendet werden.',
    'it' => 'Questo account venditore Amazon vende già su questo marketplace dal negozio «%s». Ogni account venditore e ogni marketplace possono essere usati da un solo negozio.',
    'pl' => 'To konto sprzedawcy Amazon już sprzedaje na tym marketplace ze sklepu „%s”. Każde konto sprzedawcy i marketplace może być używane tylko przez jeden sklep.',
  ),
  'Order %1$s belongs to the shop "%2$s". Select that shop at the top of the page, then create it there.' => 
  array(
    'fr' => 'La commande %1$s appartient à la boutique « %2$s ». Sélectionnez cette boutique en haut de la page, puis créez-la là-bas.',
    'es' => 'El pedido %1$s pertenece a la tienda «%2$s». Selecciona esa tienda en la parte superior de la página y créalo allí.',
    'de' => 'Die Bestellung %1$s gehört zum Shop „%2$s“. Wählen Sie diesen Shop oben auf der Seite aus und legen Sie sie dort an.',
    'it' => 'L\'ordine %1$s appartiene al negozio «%2$s». Seleziona quel negozio in alto nella pagina, poi crealo lì.',
    'pl' => 'Zamówienie %1$s należy do sklepu „%2$s”. Wybierz tamten sklep u góry strony, a następnie utwórz je tam.',
  ),
  '%1$s: skipped, this order is already imported in the shop "%2$s".' => 
  array(
    'fr' => '%1$s : ignoré, cette commande est déjà importée dans la boutique « %2$s ».',
    'es' => '%1$s: omitido, este pedido ya está importado en la tienda «%2$s».',
    'de' => '%1$s: übersprungen, diese Bestellung ist bereits im Shop „%2$s“ importiert.',
    'it' => '%1$s: saltato, questo ordine è già importato nel negozio «%2$s».',
    'pl' => '%1$s: pominięto, to zamówienie jest już zaimportowane w sklepie „%2$s”.',
  ),
  'This mapping applies to all shops. Select "All shops" at the top of the page to delete it.' => 
  array(
    'fr' => 'Cette correspondance s\'applique à toutes les boutiques. Sélectionnez « Toutes les boutiques » en haut de la page pour la supprimer.',
    'es' => 'Esta correspondencia se aplica a todas las tiendas. Selecciona «Todas las tiendas» en la parte superior de la página para eliminarla.',
    'de' => 'Diese Zuordnung gilt für alle Shops. Wählen Sie „Alle Shops“ oben auf der Seite, um sie zu löschen.',
    'it' => 'Questa corrispondenza si applica a tutti i negozi. Seleziona «Tutti i negozi» in alto nella pagina per eliminarla.',
    'pl' => 'To mapowanie dotyczy wszystkich sklepów. Wybierz „Wszystkie sklepy” u góry strony, aby je usunąć.',
  ),
  'This mapping belongs to another shop. Select that shop at the top of the page to delete it.' => 
  array(
    'fr' => 'Cette correspondance appartient à une autre boutique. Sélectionnez cette boutique en haut de la page pour la supprimer.',
    'es' => 'Esta correspondencia pertenece a otra tienda. Selecciona esa tienda en la parte superior de la página para eliminarla.',
    'de' => 'Diese Zuordnung gehört zu einem anderen Shop. Wählen Sie diesen Shop oben auf der Seite aus, um sie zu löschen.',
    'it' => 'Questa corrispondenza appartiene a un altro negozio. Seleziona quel negozio in alto nella pagina per eliminarla.',
    'pl' => 'To mapowanie należy do innego sklepu. Wybierz tamten sklep u góry strony, aby je usunąć.',
  ),
  'This profile was not found. Reload the page and try again.' => 
  array(
    'fr' => 'Ce profil est introuvable. Rechargez la page et réessayez.',
    'es' => 'No se ha encontrado este perfil. Recarga la página e inténtalo de nuevo.',
    'de' => 'Dieses Profil wurde nicht gefunden. Laden Sie die Seite neu und versuchen Sie es erneut.',
    'it' => 'Questo profilo non è stato trovato. Ricarica la pagina e riprova.',
    'pl' => 'Nie znaleziono tego profilu. Przeładuj stronę i spróbuj ponownie.',
  ),
  'This profile is shared by all shops. Select "All shops" at the top of the page to change it, or create a profile for this shop.' => 
  array(
    'fr' => 'Ce profil est partagé par toutes les boutiques. Sélectionnez « Toutes les boutiques » en haut de la page pour le modifier, ou créez un profil pour cette boutique.',
    'es' => 'Este perfil es compartido por todas las tiendas. Selecciona «Todas las tiendas» en la parte superior de la página para cambiarlo, o crea un perfil para esta tienda.',
    'de' => 'Dieses Profil wird von allen Shops gemeinsam genutzt. Wählen Sie „Alle Shops“ oben auf der Seite, um es zu ändern, oder legen Sie ein Profil für diesen Shop an.',
    'it' => 'Questo profilo è condiviso da tutti i negozi. Seleziona «Tutti i negozi» in alto nella pagina per modificarlo, oppure crea un profilo per questo negozio.',
    'pl' => 'Ten profil jest współdzielony przez wszystkie sklepy. Wybierz „Wszystkie sklepy” u góry strony, aby go zmienić, albo utwórz profil dla tego sklepu.',
  ),
  'Line %1$d: %2$s is not a product of this shop, so it was left unchanged.' => 
  array(
    'fr' => 'Ligne %1$d : %2$s n\'est pas un produit de cette boutique, il a donc été laissé inchangé.',
    'es' => 'Línea %1$d: %2$s no es un producto de esta tienda, así que se dejó sin cambios.',
    'de' => 'Zeile %1$d: %2$s ist kein Produkt dieses Shops und wurde daher unverändert gelassen.',
    'it' => 'Riga %1$d: %2$s non è un prodotto di questo negozio, quindi è stato lasciato invariato.',
    'pl' => 'Wiersz %1$d: %2$s nie jest produktem tego sklepu, więc pozostał bez zmian.',
  ),
  'This pricing rule was not found. Reload the page and try again.' => 
  array(
    'fr' => 'Cette règle tarifaire est introuvable. Rechargez la page et réessayez.',
    'es' => 'No se ha encontrado esta regla de precio. Recarga la página e inténtalo de nuevo.',
    'de' => 'Diese Preisregel wurde nicht gefunden. Laden Sie die Seite neu und versuchen Sie es erneut.',
    'it' => 'Questa regola di prezzo non è stata trovata. Ricarica la pagina e riprova.',
    'pl' => 'Nie znaleziono tej reguły cenowej. Przeładuj stronę i spróbuj ponownie.',
  ),
  'This pricing rule is shared by all shops. Select "All shops" at the top of the page to change it.' => 
  array(
    'fr' => 'Cette règle tarifaire est partagée par toutes les boutiques. Sélectionnez « Toutes les boutiques » en haut de la page pour la modifier.',
    'es' => 'Esta regla de precio es compartida por todas las tiendas. Selecciona «Todas las tiendas» en la parte superior de la página para cambiarla.',
    'de' => 'Diese Preisregel wird von allen Shops gemeinsam genutzt. Wählen Sie „Alle Shops“ oben auf der Seite, um sie zu ändern.',
    'it' => 'Questa regola di prezzo è condivisa da tutti i negozi. Seleziona «Tutti i negozi» in alto nella pagina per modificarla.',
    'pl' => 'Ta reguła cenowa jest współdzielona przez wszystkie sklepy. Wybierz „Wszystkie sklepy” u góry strony, aby ją zmienić.',
  ),
  'Select a shop at the top of the page first: this works on one shop at a time.' => 
  array(
    'fr' => 'Sélectionnez d\'abord une boutique en haut de la page : ceci fonctionne une boutique à la fois.',
    'es' => 'Primero, selecciona una tienda en la parte superior de la página: esto funciona con una tienda a la vez.',
    'de' => 'Wählen Sie zuerst oben auf der Seite einen Shop aus: Dies funktioniert jeweils nur für einen Shop.',
    'it' => 'Seleziona prima un negozio in alto nella pagina: questa funzione opera su un negozio alla volta.',
    'pl' => 'Najpierw wybierz sklep u góry strony: to działa dla jednego sklepu naraz.',
  ),
  'This order belongs to another shop. Its invoice is uploaded when that shop runs the task.' => 
  array(
    'fr' => 'Cette commande appartient à une autre boutique. Sa facture est envoyée lorsque cette boutique exécute la tâche.',
    'es' => 'Este pedido pertenece a otra tienda. Su factura se sube cuando esa tienda ejecuta la tarea.',
    'de' => 'Diese Bestellung gehört zu einem anderen Shop. Ihre Rechnung wird hochgeladen, sobald dieser Shop die Aufgabe ausführt.',
    'it' => 'Questo ordine appartiene a un altro negozio. La sua fattura viene caricata quando quel negozio esegue l\'attività.',
    'pl' => 'To zamówienie należy do innego sklepu. Jego faktura zostanie przesłana, gdy tamten sklep uruchomi zadanie.',
  ),
  'This Amazon seller account is already connected to the shop "%s" for the same marketplace. Disconnect it in that shop first, or choose another marketplace for this shop, then click "Connect to Amazon" again.' => 
  array(
    'fr' => 'Ce compte vendeur Amazon est déjà connecté à la boutique « %s » pour cette même place de marché. Déconnectez-le d\'abord dans cette boutique, ou choisissez une autre place de marché pour cette boutique, puis cliquez de nouveau sur « Se connecter à Amazon ».',
    'es' => 'Esta cuenta de vendedor de Amazon ya está conectada a la tienda «%s» para el mismo marketplace. Desconéctala primero en esa tienda, o elige otro marketplace para esta tienda y vuelve a pulsar «Conectar con Amazon».',
    'de' => 'Dieses Amazon-Verkäuferkonto ist bereits mit dem Shop „%s“ für denselben Marktplatz verbunden. Trennen Sie es zuerst in diesem Shop, oder wählen Sie einen anderen Marktplatz für diesen Shop und klicken Sie dann erneut auf „Mit Amazon verbinden“.',
    'it' => 'Questo account venditore Amazon è già connesso al negozio «%s» per lo stesso marketplace. Disconnettilo prima in quel negozio, oppure scegli un altro marketplace per questo negozio, poi fai di nuovo clic su «Connetti ad Amazon».',
    'pl' => 'To konto sprzedawcy Amazon jest już połączone ze sklepem „%s” dla tego samego marketplace. Najpierw odłącz je w tamtym sklepie albo wybierz inny marketplace dla tego sklepu, a następnie ponownie kliknij „Połącz z Amazon”.',
  ),
);
