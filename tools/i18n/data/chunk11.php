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
 * Strings 748-767: "Check connection" (renamed "Run a test" in chunk15) in
 * the Connected banner, which replaced the Connection Test panel, and its
 * plain-language results.
 *
 * Button and tab names quoted inside a message use the label the merchant
 * sees on screen in that language (Disconnect, Connect to Amazon, Logs).
 * "Seller Central" stays in English, as everywhere else. Two strings are
 * embedded in JavaScript with js=1 and must not contain a literal double
 * quote; the results travel as JSON into textContent, so they may.
 */
return array(

'Checking the connection to Amazon...' => array(
    'fr' => 'Vérification de la connexion à Amazon...',
    'es' => 'Comprobando la conexión con Amazon...',
    'de' => 'Verbindung zu Amazon wird geprüft...',
    'it' => 'Verifica della connessione ad Amazon in corso...',
    'pl' => 'Sprawdzanie połączenia z Amazon...',
),
'The check could not be completed. Try again.' => array(
    'fr' => 'La vérification n\'a pas pu aboutir. Réessayez.',
    'es' => 'No se ha podido completar la comprobación. Inténtalo de nuevo.',
    'de' => 'Die Prüfung konnte nicht abgeschlossen werden. Versuchen Sie es erneut.',
    'it' => 'Non è stato possibile completare la verifica. Riprova.',
    'pl' => 'Nie udało się dokończyć sprawdzania. Spróbuj ponownie.',
),
'A refresh token is stored for this environment. The "Connect to Amazon" button is only for the OAuth flow and is not used in manual mode — use "Run a test" to verify the link.' => array(
    'fr' => 'Un jeton de rafraîchissement est enregistré pour cet environnement. Le bouton « Se connecter à Amazon » ne sert qu\'au parcours OAuth et n\'est pas utilisé en mode manuel — utilisez « Lancer un test » pour vérifier le lien.',
    'es' => 'Hay un token de actualización guardado para este entorno. El botón «Conectar con Amazon» solo sirve para el flujo OAuth y no se usa en modo manual: utiliza «Hacer una prueba» para verificar el enlace.',
    'de' => 'Für diese Umgebung ist ein Refresh-Token gespeichert. Die Schaltfläche „Mit Amazon verbinden“ gilt nur für den OAuth-Ablauf und wird im manuellen Modus nicht verwendet — prüfen Sie die Verbindung mit „Test ausführen“.',
    'it' => 'Per questo ambiente è memorizzato un refresh token. Il pulsante «Connetti ad Amazon» serve solo al flusso OAuth e non viene usato in modalità manuale: usa «Esegui un test» per controllare il collegamento.',
    'pl' => 'Dla tego środowiska zapisano token odświeżania. Przycisk „Połącz z Amazon” dotyczy wyłącznie procesu OAuth i nie jest używany w trybie ręcznym — użyj przycisku „Uruchom test”, aby sprawdzić powiązanie.',
),
'The Amazon credentials are not filled in. Enter them in the Manual SP-API Credentials panel.' => array(
    'fr' => 'Les identifiants Amazon ne sont pas renseignés. Saisissez-les dans le panneau Identifiants SP-API manuels.',
    'es' => 'Las credenciales de Amazon no están completadas. Introdúcelas en el panel Credenciales SP-API manuales.',
    'de' => 'Die Amazon-Zugangsdaten sind nicht ausgefüllt. Tragen Sie sie im Bereich Manuelle SP-API-Zugangsdaten ein.',
    'it' => 'Le credenziali Amazon non sono compilate. Inseriscile nel pannello Credenziali SP-API manuali.',
    'pl' => 'Dane dostępowe Amazon nie są uzupełnione. Wpisz je w panelu Ręczne dane SP-API.',
),
'This shop is not connected to Amazon yet. Press Connect to Amazon.' => array(
    'fr' => 'Cette boutique n\'est pas encore connectée à Amazon. Cliquez sur Se connecter à Amazon.',
    'es' => 'Esta tienda aún no está conectada con Amazon. Pulsa Conectar con Amazon.',
    'de' => 'Dieser Shop ist noch nicht mit Amazon verbunden. Klicken Sie auf Mit Amazon verbinden.',
    'it' => 'Questo negozio non è ancora connesso ad Amazon. Premi Connetti ad Amazon.',
    'pl' => 'Ten sklep nie jest jeszcze połączony z Amazon. Kliknij Połącz z Amazon.',
),
'Connecting to Amazon...' => array(
    'fr' => 'Connexion à Amazon...',
    'es' => 'Conectando con Amazon...',
    'de' => 'Verbindung zu Amazon wird hergestellt...',
    'it' => 'Connessione ad Amazon in corso...',
    'pl' => 'Łączenie z Amazon...',
),
'Connected.' => array(
    'fr' => 'Connecté.',
    'es' => 'Conectado.',
    'de' => 'Verbunden.',
    'it' => 'Connesso.',
    'pl' => 'Połączono.',
),
'Reading your recent orders...' => array(
    'fr' => 'Lecture de vos commandes récentes...',
    'es' => 'Leyendo tus pedidos recientes...',
    'de' => 'Ihre letzten Bestellungen werden gelesen...',
    'it' => 'Lettura dei tuoi ordini recenti in corso...',
    'pl' => 'Odczytywanie ostatnich zamówień...',
),
'Orders found: %d.' => array(
    'fr' => 'Commandes trouvées : %d.',
    'es' => 'Pedidos encontrados: %d.',
    'de' => 'Gefundene Bestellungen: %d.',
    'it' => 'Ordini trovati: %d.',
    'pl' => 'Znalezione zamówienia: %d.',
),
'Everything works: the module can reach your Amazon account.' => array(
    'fr' => 'Tout fonctionne : le module accède à votre compte Amazon.',
    'es' => 'Todo funciona: el módulo puede acceder a tu cuenta de Amazon.',
    'de' => 'Alles funktioniert: Das Modul erreicht Ihr Amazon-Konto.',
    'it' => 'Tutto funziona: il modulo raggiunge il tuo account Amazon.',
    'pl' => 'Wszystko działa: moduł ma dostęp do Twojego konta Amazon.',
),
'Amazon rejected the refresh token: it is wrong, expired, or belongs to a different app.' => array(
    'fr' => 'Amazon a refusé le jeton de rafraîchissement : il est erroné, expiré ou appartient à une autre application.',
    'es' => 'Amazon ha rechazado el token de actualización: es incorrecto, ha caducado o pertenece a otra aplicación.',
    'de' => 'Amazon hat das Refresh-Token abgelehnt: Es ist falsch, abgelaufen oder gehört zu einer anderen App.',
    'it' => 'Amazon ha rifiutato il refresh token: è errato, scaduto o appartiene a un\'altra app.',
    'pl' => 'Amazon odrzucił token odświeżania: jest błędny, wygasł albo należy do innej aplikacji.',
),
'Amazon no longer accepts this connection, usually because it was removed in Seller Central. Press Disconnect, then Connect to Amazon again.' => array(
    'fr' => 'Amazon n\'accepte plus cette connexion, généralement parce qu\'elle a été supprimée dans Seller Central. Cliquez sur Déconnecter, puis de nouveau sur Se connecter à Amazon.',
    'es' => 'Amazon ya no acepta esta conexión, normalmente porque se eliminó en Seller Central. Pulsa Desconectar y después otra vez Conectar con Amazon.',
    'de' => 'Amazon akzeptiert diese Verbindung nicht mehr, meist weil sie in Seller Central entfernt wurde. Klicken Sie auf Trennen und danach erneut auf Mit Amazon verbinden.',
    'it' => 'Amazon non accetta più questa connessione, di solito perché è stata rimossa in Seller Central. Premi Disconnetti e poi di nuovo Connetti ad Amazon.',
    'pl' => 'Amazon nie akceptuje już tego połączenia, zwykle dlatego, że usunięto je w Seller Central. Kliknij Odłącz, a następnie ponownie Połącz z Amazon.',
),
'Amazon rejected the client ID or the client secret.' => array(
    'fr' => 'Amazon a refusé l\'ID client ou le secret client.',
    'es' => 'Amazon ha rechazado el ID de cliente o el secreto de cliente.',
    'de' => 'Amazon hat die Client-ID oder das Client-Secret abgelehnt.',
    'it' => 'Amazon ha rifiutato il client ID o il client secret.',
    'pl' => 'Amazon odrzucił identyfikator klienta lub klucz tajny klienta.',
),
'Amazon refused access to your seller account. Check in Seller Central that the account is active and on the Professional selling plan. If it is, press Disconnect, then Connect to Amazon again.' => array(
    'fr' => 'Amazon a refusé l\'accès à votre compte vendeur. Vérifiez dans Seller Central que le compte est actif et sur l\'offre Vendeur Professionnel. Si c\'est le cas, cliquez sur Déconnecter, puis de nouveau sur Se connecter à Amazon.',
    'es' => 'Amazon ha denegado el acceso a tu cuenta de vendedor. Comprueba en Seller Central que la cuenta está activa y en el plan de venta Profesional. Si lo está, pulsa Desconectar y después otra vez Conectar con Amazon.',
    'de' => 'Amazon hat den Zugriff auf Ihr Verkäuferkonto verweigert. Prüfen Sie in Seller Central, ob das Konto aktiv ist und den Tarif Professional nutzt. Falls ja, klicken Sie auf Trennen und danach erneut auf Mit Amazon verbinden.',
    'it' => 'Amazon ha negato l\'accesso al tuo account venditore. Verifica in Seller Central che l\'account sia attivo e abbia il piano di vendita Professionale. Se è così, premi Disconnetti e poi di nuovo Connetti ad Amazon.',
    'pl' => 'Amazon odmówił dostępu do Twojego konta sprzedawcy. Sprawdź w Seller Central, czy konto jest aktywne i korzysta z planu Profesjonalnego. Jeśli tak, kliknij Odłącz, a następnie ponownie Połącz z Amazon.',
),
'Amazon refused access. Check that the seller account is active and on the Professional selling plan, and that the client ID, client secret and refresh token all come from the same app.' => array(
    'fr' => 'Amazon a refusé l\'accès. Vérifiez que le compte vendeur est actif et sur l\'offre Vendeur Professionnel, et que l\'ID client, le secret client et le jeton de rafraîchissement proviennent tous de la même application.',
    'es' => 'Amazon ha denegado el acceso. Comprueba que la cuenta de vendedor está activa y en el plan de venta Profesional, y que el ID de cliente, el secreto de cliente y el token de actualización proceden de la misma aplicación.',
    'de' => 'Amazon hat den Zugriff verweigert. Prüfen Sie, ob das Verkäuferkonto aktiv ist und den Tarif Professional nutzt und ob Client-ID, Client-Secret und Refresh-Token alle zur selben App gehören.',
    'it' => 'Amazon ha negato l\'accesso. Verifica che l\'account venditore sia attivo e abbia il piano di vendita Professionale, e che client ID, client secret e refresh token provengano tutti dalla stessa app.',
    'pl' => 'Amazon odmówił dostępu. Sprawdź, czy konto sprzedawcy jest aktywne i korzysta z planu Profesjonalnego oraz czy identyfikator klienta, klucz tajny klienta i token odświeżania pochodzą z tej samej aplikacji.',
),
'This server cannot open a secure connection to Amazon because the certificate check failed. Ask your hosting provider to update the CA certificates on the server.' => array(
    'fr' => 'Ce serveur ne peut pas établir de connexion sécurisée avec Amazon, car la vérification du certificat a échoué. Demandez à votre hébergeur de mettre à jour les certificats CA du serveur.',
    'es' => 'Este servidor no puede abrir una conexión segura con Amazon porque ha fallado la comprobación del certificado. Pide a tu proveedor de alojamiento que actualice los certificados CA del servidor.',
    'de' => 'Dieser Server kann keine sichere Verbindung zu Amazon aufbauen, weil die Zertifikatsprüfung fehlgeschlagen ist. Bitten Sie Ihren Hosting-Anbieter, die CA-Zertifikate auf dem Server zu aktualisieren.',
    'it' => 'Questo server non riesce ad aprire una connessione sicura con Amazon perché il controllo del certificato non è riuscito. Chiedi al tuo provider di hosting di aggiornare i certificati CA del server.',
    'pl' => 'Ten serwer nie może nawiązać bezpiecznego połączenia z Amazon, ponieważ weryfikacja certyfikatu się nie powiodła. Poproś dostawcę hostingu o aktualizację certyfikatów CA na serwerze.',
),
'Amazon or the IntelliPresta connection service could not be reached. Try again in a few minutes; if it keeps happening, contact support.' => array(
    'fr' => 'Impossible de joindre Amazon ou le service de connexion IntelliPresta. Réessayez dans quelques minutes ; si le problème persiste, contactez le support.',
    'es' => 'No se ha podido contactar con Amazon ni con el servicio de conexión de IntelliPresta. Inténtalo de nuevo en unos minutos; si sigue ocurriendo, contacta con soporte.',
    'de' => 'Amazon oder der Verbindungsdienst von IntelliPresta war nicht erreichbar. Versuchen Sie es in einigen Minuten erneut; falls das weiterhin passiert, wenden Sie sich an den Support.',
    'it' => 'Non è stato possibile raggiungere Amazon o il servizio di connessione IntelliPresta. Riprova tra qualche minuto; se continua a succedere, contatta l\'assistenza.',
    'pl' => 'Nie udało się połączyć z Amazon ani z usługą połączenia IntelliPresta. Spróbuj ponownie za kilka minut; jeśli problem się powtarza, skontaktuj się z pomocą techniczną.',
),
'Amazon returned an error.' => array(
    'fr' => 'Amazon a renvoyé une erreur.',
    'es' => 'Amazon ha devuelto un error.',
    'de' => 'Amazon hat einen Fehler zurückgegeben.',
    'it' => 'Amazon ha restituito un errore.',
    'pl' => 'Amazon zwrócił błąd.',
),
'The technical details are in the Logs tab.' => array(
    'fr' => 'Les détails techniques figurent dans l\'onglet Journaux.',
    'es' => 'Los detalles técnicos están en la pestaña Registros.',
    'de' => 'Die technischen Details finden Sie im Tab Protokolle.',
    'it' => 'I dettagli tecnici sono nella scheda Log.',
    'pl' => 'Szczegóły techniczne znajdziesz w zakładce Dzienniki.',
),

);
