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
 * Strings 1-4: the environment selector, open to every merchant so that
 * PrestaShop's reviewers and merchants without a seller account can test
 * against the sandbox. Selecting Sandbox shows one notice, and the
 * credentials panel a one-line hint; the longer technical texts are for
 * developer mode only.
 *
 * Strings 5-8: "Check connection" renamed "Test connection", since the banner
 * beside it already says the shop is connected; and the notes shown while
 * the Environment select differs from the saved environment.
 */
return array(

'Amazon\'s test environment: sample data only, never a real seller account.' => array(
    'fr' => 'L\'environnement de test d\'Amazon : uniquement des données d\'exemple, jamais un vrai compte vendeur.',
    'es' => 'El entorno de pruebas de Amazon: solo datos de ejemplo, nunca una cuenta de vendedor real.',
    'de' => 'Die Testumgebung von Amazon: nur Beispieldaten, nie ein echtes Verkäuferkonto.',
    'it' => 'L\'ambiente di test di Amazon: solo dati di esempio, mai un vero account venditore.',
    'pl' => 'Środowisko testowe Amazon: tylko przykładowe dane, nigdy prawdziwe konto sprzedawcy.',
),
'Enter a sandbox app\'s client ID, client secret and refresh token below, then click Save Settings.' => array(
    'fr' => 'Saisissez ci-dessous l\'ID client, le secret client et le jeton d\'actualisation d\'une application bac à sable, puis cliquez sur Enregistrer les paramètres.',
    'es' => 'Introduce abajo el ID de cliente, el secreto de cliente y el token de actualización de una aplicación sandbox y pulsa Guardar los ajustes.',
    'de' => 'Geben Sie unten die Client-ID, das Client-Secret und das Refresh-Token einer Sandbox-App ein und klicken Sie auf Einstellungen speichern.',
    'it' => 'Inserisci qui sotto l\'ID client, il segreto client e il token di aggiornamento di un\'app sandbox, poi fai clic su Salva le impostazioni.',
    'pl' => 'Wpisz poniżej identyfikator klienta, sekret klienta i token odświeżania aplikacji sandbox, a następnie kliknij Zapisz ustawienia.',
),
'Keep Production to sell on Amazon. Each environment keeps its own connection, so switching back does not require connecting again.' => array(
    'fr' => 'Gardez Production pour vendre sur Amazon. Chaque environnement garde sa propre connexion, donc y revenir ne demande pas de se reconnecter.',
    'es' => 'Deja Producción para vender en Amazon. Cada entorno guarda su propia conexión, así que al volver no hace falta conectar de nuevo.',
    'de' => 'Behalten Sie Produktiv bei, um auf Amazon zu verkaufen. Jede Umgebung behält ihre eigene Verbindung, ein Zurückwechseln erfordert also keine neue Verbindung.',
    'it' => 'Mantieni Produzione per vendere su Amazon. Ogni ambiente conserva la propria connessione, quindi tornare indietro non richiede di connettersi di nuovo.',
    'pl' => 'Pozostaw Produkcję, aby sprzedawać na Amazon. Każde środowisko zachowuje własne połączenie, więc powrót nie wymaga ponownego łączenia.',
),
'All three values must come from the same app.' => array(
    'fr' => 'Les trois valeurs doivent provenir de la même application.',
    'es' => 'Los tres valores deben proceder de la misma aplicación.',
    'de' => 'Alle drei Werte müssen von derselben App stammen.',
    'it' => 'I tre valori devono provenire dalla stessa app.',
    'pl' => 'Wszystkie trzy wartości muszą pochodzić z tej samej aplikacji.',
),
'Test connection' => array(
    'fr' => 'Tester la connexion',
    'es' => 'Probar la conexión',
    'de' => 'Verbindung testen',
    'it' => 'Testa la connessione',
    'pl' => 'Przetestuj połączenie',
),
'Reads your recent orders from Amazon to show that the connection still works.' => array(
    'fr' => 'Lit vos commandes récentes sur Amazon pour montrer que la connexion fonctionne toujours.',
    'es' => 'Lee tus pedidos recientes de Amazon para mostrar que la conexión sigue funcionando.',
    'de' => 'Liest Ihre letzten Bestellungen bei Amazon, um zu zeigen, dass die Verbindung noch funktioniert.',
    'it' => 'Legge i tuoi ordini recenti da Amazon per mostrare che la connessione funziona ancora.',
    'pl' => 'Odczytuje Twoje ostatnie zamówienia z Amazon, aby pokazać, że połączenie nadal działa.',
),
'Click Save Settings to switch to Sandbox.' => array(
    'fr' => 'Cliquez sur Enregistrer les paramètres pour passer au bac à sable.',
    'es' => 'Pulsa Guardar los ajustes para cambiar al sandbox.',
    'de' => 'Klicken Sie auf Einstellungen speichern, um zur Sandbox zu wechseln.',
    'it' => 'Fai clic su Salva le impostazioni per passare alla sandbox.',
    'pl' => 'Kliknij Zapisz ustawienia, aby przełączyć się na Sandbox.',
),
'Click Save Settings to switch to Production.' => array(
    'fr' => 'Cliquez sur Enregistrer les paramètres pour passer en Production.',
    'es' => 'Pulsa Guardar los ajustes para cambiar a Producción.',
    'de' => 'Klicken Sie auf Einstellungen speichern, um zur Produktivumgebung zu wechseln.',
    'it' => 'Fai clic su Salva le impostazioni per passare a Produzione.',
    'pl' => 'Kliknij Zapisz ustawienia, aby przełączyć się na Produkcję.',
),

);
