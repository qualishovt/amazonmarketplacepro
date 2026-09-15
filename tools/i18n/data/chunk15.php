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
 * Strings 1-2: the environment selector opened to every merchant, so that
 * PrestaShop's reviewers and merchants without a seller account can test
 * against the sandbox.
 *
 * Strings 3-6: "Check connection" renamed "Test connection", since the banner
 * beside it already says the shop is connected; and the notes shown while
 * the Environment select differs from the saved environment.
 */
return array(

'The sandbox does not use "Connect to Amazon". Enter the client ID, client secret and refresh token of a sandbox app in the Manual SP-API Credentials panel below, then click Save Settings.' => array(
    'fr' => 'Le bac à sable n\'utilise pas « Se connecter à Amazon ». Saisissez l\'ID client, le secret client et le jeton d\'actualisation d\'une application bac à sable dans le panneau Identifiants SP-API manuels ci-dessous, puis cliquez sur Enregistrer les paramètres.',
    'es' => 'El sandbox no usa «Conectar con Amazon». Introduce el ID de cliente, el secreto de cliente y el token de actualización de una aplicación sandbox en el panel Credenciales SP-API manuales de más abajo y pulsa Guardar los ajustes.',
    'de' => 'Die Sandbox verwendet „Mit Amazon verbinden“ nicht. Geben Sie die Client-ID, das Client-Secret und das Refresh-Token einer Sandbox-App unten im Bereich Manuelle SP-API-Zugangsdaten ein und klicken Sie auf Einstellungen speichern.',
    'it' => 'La sandbox non usa «Connetti ad Amazon». Inserisci l\'ID client, il segreto client e il token di aggiornamento di un\'app sandbox nel pannello Credenziali SP-API manuali qui sotto, poi fai clic su Salva le impostazioni.',
    'pl' => 'Sandbox nie korzysta z przycisku „Połącz z Amazon”. Wpisz identyfikator klienta, sekret klienta i token odświeżania aplikacji sandbox w panelu Ręczne dane SP-API poniżej, a następnie kliknij Zapisz ustawienia.',
),
'Keep Production to sell on Amazon. Sandbox is Amazon\'s test environment: it returns sample data and never touches a real seller account. Each environment keeps its own connection, so switching back does not require connecting again.' => array(
    'fr' => 'Gardez Production pour vendre sur Amazon. Le bac à sable est l\'environnement de test d\'Amazon : il renvoie des données d\'exemple et ne touche jamais un vrai compte vendeur. Chaque environnement garde sa propre connexion, donc y revenir ne demande pas de se reconnecter.',
    'es' => 'Deja Producción para vender en Amazon. El sandbox es el entorno de pruebas de Amazon: devuelve datos de ejemplo y nunca toca una cuenta de vendedor real. Cada entorno guarda su propia conexión, así que al volver no hace falta conectar de nuevo.',
    'de' => 'Behalten Sie Produktiv bei, um auf Amazon zu verkaufen. Die Sandbox ist die Testumgebung von Amazon: Sie liefert Beispieldaten und berührt nie ein echtes Verkäuferkonto. Jede Umgebung behält ihre eigene Verbindung, ein Zurückwechseln erfordert also keine neue Verbindung.',
    'it' => 'Mantieni Produzione per vendere su Amazon. La sandbox è l\'ambiente di test di Amazon: restituisce dati di esempio e non tocca mai un vero account venditore. Ogni ambiente conserva la propria connessione, quindi tornare indietro non richiede di connettersi di nuovo.',
    'pl' => 'Pozostaw Produkcję, aby sprzedawać na Amazon. Sandbox to środowisko testowe Amazon: zwraca przykładowe dane i nigdy nie dotyka prawdziwego konta sprzedawcy. Każde środowisko zachowuje własne połączenie, więc powrót nie wymaga ponownego łączenia.',
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
