<?php

/**
 * Funzioni comuni a tutte le API.
 *
 * Questo file viene incluso dagli endpoint API per operazioni condivise
 * come la lettura di dati JSON con supporto traduzione.
 */

/**
 * Ottiene un oggetto JSON dalla directory data/ e lo restituisce,
 * eventualmente dopo aver applicato una callback (tipicamente per traduzione).
 *
 * Pattern d'uso negli endpoint:
 *   echo echoGetObj("nomeFile", function($data, $lingua) { ... });
 *
 * Se la callback è presente, i dati vengono decodificati come array,
 * passati alla callback insieme alla lingua corrente, e ri-codificati in JSON.
 * Se la callback non c'è, il JSON viene restituito così com'è dal file.
 *
 * @param string $nome Nome dell'oggetto (corrisponde a data/{nome}.json).
 * @param callable|null $callback Funzione di callback($data, $lingua) da applicare ai dati.
 * @return string Risposta JSON con i dati ottenuti o un messaggio di errore.
 */
function echoGetObj($nome, $callback = null)
{
    $ciLavoro = is_callable($callback);

    try {
        // Se c'è una callback, decodifica il JSON in array PHP ($ciLavoro=true);
        // altrimenti restituisce il contenuto raw del file
        $jsonData = BLL\Repository::getObj($nome, $ciLavoro);

        if ($ciLavoro) {
            // Sanitizza il parametro lingua (sostituzione di FILTER_SANITIZE_STRING, deprecato in PHP 8.1)
            $langRaw = $_GET["lang"] ?? BLL\Repository::getDefaultLang();
            $l = htmlspecialchars(strip_tags($langRaw), ENT_QUOTES, 'UTF-8');

            // Applica la callback (es. traduzione) e ri-codifica in JSON
            $jsonData = json_encode($callback($jsonData, $l));
        }

    } catch (Exception $e) {
        return BLL\Response::retError($e->getMessage());
    }

    return $jsonData;
}
