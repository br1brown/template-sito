<?php

/**
 * Dispatcher dei metodi HTTP.
 *
 * Ogni endpoint API definisce funzioni con nome "esegui{METODO}" (es. eseguiGET, eseguiPOST).
 * Questo file viene incluso in fondo a ogni endpoint e chiama automaticamente
 * la funzione corrispondente al metodo HTTP della richiesta.
 * Se la funzione non esiste, il metodo non è supportato → 405.
 */

// Accetta solo metodi HTTP standard per evitare nomi di funzione inaspettati
$metodiAccettati = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];
$metodo = $_SERVER['REQUEST_METHOD'];

if (!in_array($metodo, $metodiAccettati, true)) {
    header("HTTP/1.1 405 Method Not Allowed");
    header('Content-Type: application/json');
    echo BLL\Response::retError("Metodo HTTP '$metodo' non riconosciuto.");
    return;
}

$funzione = "esegui" . $metodo;

if (function_exists($funzione)) {
    try {
        call_user_func($funzione);
    } catch (\Throwable $e) {
        // Throwable cattura sia Exception che Error (es. TypeError in PHP 8+)
        header('Content-Type: application/json');
        http_response_code(500);
        echo BLL\Response::retError($e->getMessage());
    }
} else {
    // Elenca i metodi effettivamente supportati da questo endpoint (standard HTTP: RFC 7231 §6.5.5)
    $metodiDisponibili = [];
    foreach ($metodiAccettati as $m) {
        if (function_exists("esegui" . $m)) {
            $metodiDisponibili[] = $m;
        }
    }
    header("HTTP/1.1 405 Method Not Allowed");
    // Header Allow obbligatorio nella risposta 405
    header('Allow: ' . implode(', ', $metodiDisponibili));
    header('Content-Type: application/json');
    echo BLL\Response::retError("Metodo $metodo non supportato. Metodi disponibili: " . implode(', ', $metodiDisponibili));
}
