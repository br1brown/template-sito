<?php
require_once __DIR__ . '/parsedown-1.7.4/Parsedown.php';

$parser = null;
function Markdown_HTML($mark)
{

    if (!isset($parser))
        $parser = new Parsedown();

    return $parser->text($mark);

}



/**
 * Modifica l'array del menu direttamente per riferimento.
 *
 * @param Service Il servizio per le utilità front end
 * @param array &$itemsMenu Riferimento all'array del menu ottenuto dal JSON statico.
 */
function dynamicMenu($Service, &$itemsMenu)
{
    // Qui puoi modificare direttamente l'array $itemsMenu.

    // Esempio: Aggiungere un nuovo elemento al menu
    // $itemsMenu[] = [
    //     'nome' => 'Nuova Voce',
    //     'route' => 'nuova-voce-route'
    // ];

    // Esempio: Modificare un elemento esistente
    // foreach ($itemsMenu as $key => &$item) {
    //     if ($item['nome'] == 'ElementoDaModificare') {
    //         $item['route'] = 'nuovo-route-modificato';
    //     }
    // }
    // Nota: Non dimenticare di rimuovere il riferimento dopo il ciclo
    // unset($item);

    // Esempio: Rimuovere un elemento
    // $itemsMenu = array_filter($itemsMenu, function($item) {
    //     return $item['nome'] != 'ElementoDaRimuovere';
    // });

    // Non è necessario restituire l'array, poiché è stato passato per riferimento
}


/**
 * Verifica le credenziali dell'utente e gestisce l'autenticazione.
 *
 * Questa funzione viene utilizzata per autenticare un utente attraverso 
 * Se la password è valida, imposta lo stato di autenticazione nella 
 * sessione e salva il token Bearer. In caso contrario, restituisce un 
 * errore per gestire una risposta adeguata.
 *
 * @param Service Il servizio per le utilità front end
 * @param string $password La password inviata tramite il form di login.
 * @return array Ritorna un array con i seguenti dati:
 *               - valid (bool): Indica se l'autenticazione ha avuto successo.
 *               - token (string|null): Il token Bearer se l'autenticazione è valida.
 *               - error (string|null): Il messaggio di errore in caso di autenticazione fallita.
 */
function loggati($Service, $password): array
{
    return $Service->loggati(["pwd" => $password]);
}
