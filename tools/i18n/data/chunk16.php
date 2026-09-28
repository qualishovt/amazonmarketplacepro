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
 * The comparison tells the merchant what it holds: how much of the catalogue
 * it could read, how many rows it lists out of the total, which rows left the
 * export, and how to empty it and start again.
 */
return array(

'Only the first %1$d of %2$d active products were read into the comparison. Switch off what you do not sell on Amazon under Catalog rules, or ask support to raise this limit.' => array(
    'fr' => 'Seuls les %1$d premiers produits actifs sur %2$d ont été lus dans la comparaison. Désactivez sous Règles catalogue ce que vous ne vendez pas sur Amazon, ou demandez au support d\'augmenter cette limite.',
    'es' => 'Solo se han leído los primeros %1$d productos activos de %2$d en la comparación. Desactiva en Reglas de catálogo lo que no vendes en Amazon, o pide al soporte que amplíe este límite.',
    'de' => 'Es wurden nur die ersten %1$d von %2$d aktiven Produkten in den Vergleich eingelesen. Schalten Sie unter Katalogregeln ab, was Sie nicht bei Amazon verkaufen, oder bitten Sie den Support, dieses Limit zu erhöhen.',
    'it' => 'Sono stati letti nel confronto solo i primi %1$d prodotti attivi su %2$d. Disattiva nelle Regole catalogo ciò che non vendi su Amazon, oppure chiedi al supporto di aumentare questo limite.',
    'pl' => 'Do porównania wczytano tylko pierwsze %1$d z %2$d aktywnych produktów. Wyłącz w Regułach katalogu to, czego nie sprzedajesz na Amazon, albo poproś wsparcie o zwiększenie tego limitu.',
),
'Only the first %1$d of %2$d active combinations were read into the comparison.' => array(
    'fr' => 'Seules les %1$d premières déclinaisons actives sur %2$d ont été lues dans la comparaison.',
    'es' => 'Solo se han leído las primeras %1$d combinaciones activas de %2$d en la comparación.',
    'de' => 'Es wurden nur die ersten %1$d von %2$d aktiven Varianten in den Vergleich eingelesen.',
    'it' => 'Sono state lette nel confronto solo le prime %1$d combinazioni attive su %2$d.',
    'pl' => 'Do porównania wczytano tylko pierwsze %1$d z %2$d aktywnych kombinacji.',
),
'%d row(s) are no longer exported (product deleted, disabled, filtered out or switched off) and will not be pushed.' => array(
    'fr' => '%d ligne(s) ne sont plus exportées (produit supprimé, désactivé, filtré ou exclu) et ne seront pas envoyées.',
    'es' => '%d fila(s) ya no se exportan (producto eliminado, desactivado, filtrado o excluido) y no se enviarán.',
    'de' => '%d Zeile(n) werden nicht mehr exportiert (Produkt gelöscht, deaktiviert, herausgefiltert oder abgeschaltet) und werden nicht gesendet.',
    'it' => '%d riga/righe non sono più esportate (prodotto eliminato, disattivato, filtrato o escluso) e non verranno inviate.',
    'pl' => '%d wiersz(y) nie jest już eksportowanych (produkt usunięty, wyłączony, odfiltrowany lub wykluczony) i nie zostanie wysłanych.',
),
'Showing the first %1$s of %2$s rows. The push works on all of them, not only the ones listed here.' => array(
    'fr' => 'Affichage des %1$s premières lignes sur %2$s. L\'envoi porte sur toutes, pas seulement sur celles affichées ici.',
    'es' => 'Mostrando las primeras %1$s filas de %2$s. El envío afecta a todas, no solo a las que se ven aquí.',
    'de' => 'Es werden die ersten %1$s von %2$s Zeilen angezeigt. Gesendet werden alle, nicht nur die hier gezeigten.',
    'it' => 'Vengono mostrate le prime %1$s righe su %2$s. L\'invio riguarda tutte, non solo quelle elencate qui.',
    'pl' => 'Pokazano pierwsze %1$s z %2$s wierszy. Wysyłka obejmuje wszystkie, nie tylko te widoczne tutaj.',
),
'Reset comparison' => array(
    'fr' => 'Réinitialiser la comparaison',
    'es' => 'Reiniciar la comparación',
    'de' => 'Vergleich zurücksetzen',
    'it' => 'Reimposta il confronto',
    'pl' => 'Zresetuj porównanie',
),
'Empties the list below. The next sync rebuilds it from your catalogue as it is now. Nothing on Amazon changes.' => array(
    'fr' => 'Vide la liste ci-dessous. La prochaine synchronisation la reconstruit à partir de votre catalogue actuel. Rien ne change sur Amazon.',
    'es' => 'Vacía la lista de abajo. La próxima sincronización la reconstruye con tu catálogo actual. En Amazon no cambia nada.',
    'de' => 'Leert die Liste unten. Die nächste Synchronisation baut sie aus Ihrem aktuellen Katalog neu auf. Bei Amazon ändert sich nichts.',
    'it' => 'Svuota l\'elenco qui sotto. La sincronizzazione successiva lo ricostruisce dal tuo catalogo attuale. Su Amazon non cambia nulla.',
    'pl' => 'Czyści poniższą listę. Następna synchronizacja zbuduje ją ponownie z Twojego obecnego katalogu. Na Amazon nic się nie zmienia.',
),
'Empty the comparison? The next "Sync PS to Amazon" rebuilds it from your catalogue as it is now. Nothing on Amazon changes.' => array(
    'fr' => 'Vider la comparaison ? Le prochain « Synchroniser PS vers Amazon » la reconstruit à partir de votre catalogue actuel. Rien ne change sur Amazon.',
    'es' => '¿Vaciar la comparación? El próximo «Sincronizar PS con Amazon» la reconstruye con tu catálogo actual. En Amazon no cambia nada.',
    'de' => 'Vergleich leeren? Das nächste „PS mit Amazon synchronisieren“ baut ihn aus Ihrem aktuellen Katalog neu auf. Bei Amazon ändert sich nichts.',
    'it' => 'Svuotare il confronto? Il prossimo «Sincronizza PS con Amazon» lo ricostruisce dal tuo catalogo attuale. Su Amazon non cambia nulla.',
    'pl' => 'Wyczyścić porównanie? Następne „Synchronizuj PS z Amazon” zbuduje je ponownie z Twojego obecnego katalogu. Na Amazon nic się nie zmienia.',
),
'Comparison emptied: %1$s row(s) removed. Run "Sync PS to Amazon" to rebuild it.' => array(
    'fr' => 'Comparaison vidée : %1$s ligne(s) supprimée(s). Lancez « Synchroniser PS vers Amazon » pour la reconstruire.',
    'es' => 'Comparación vaciada: %1$s fila(s) eliminada(s). Ejecuta «Sincronizar PS con Amazon» para reconstruirla.',
    'de' => 'Vergleich geleert: %1$s Zeile(n) entfernt. Führen Sie „PS mit Amazon synchronisieren“ aus, um ihn neu aufzubauen.',
    'it' => 'Confronto svuotato: %1$s riga/righe rimosse. Esegui «Sincronizza PS con Amazon» per ricostruirlo.',
    'pl' => 'Porównanie wyczyszczone: usunięto %1$s wiersz(y). Uruchom „Synchronizuj PS z Amazon”, aby zbudować je ponownie.',
),

);
