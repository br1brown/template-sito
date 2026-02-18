<?php
/**
 * Gateway proxy — inoltra chiamate API dal frontend quando l'endpoint è esterno.
 *
 * Questo file viene usato SOLO quando EsternaAPI = true, cioè quando le API
 * risiedono su un dominio diverso dal frontend. Il browser del client non può
 * chiamare direttamente l'API esterna (CORS), quindi passa tramite questo proxy.
 *
 * SICUREZZA: Valida che l'URL di destinazione appartenga all'endpoint API configurato,
 * per evitare che il gateway venga usato come proxy SSRF aperto.
 */
require_once dirname(__DIR__) . '/FE_utils/Service.php';
require_once dirname(__DIR__) . '/FE_utils/ServerToServer.php';

// Verifica che il payload esista
if (!isset($_POST["payload"])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload mancante']);
    exit;
}

$data = json_decode($_POST["payload"], true);
if ($data === null || !isset($data['url'], $data['type'], $data['XApiKey'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Payload non valido o campi mancanti']);
    exit;
}

// Protezione SSRF: verifica che l'URL richiesto sia sotto l'endpoint API configurato
$service = new Service();
$apiBase = rtrim($service->urlAPI, '/');

if (!str_starts_with($data['url'], $apiBase)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'URL non autorizzato: il gateway accetta solo chiamate verso l\'endpoint API configurato']);
    exit;
}

// Metodi HTTP consentiti
$metodiConsentiti = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
$metodo = strtoupper($data['type'] ?? 'GET');
if (!in_array($metodo, $metodiConsentiti, true)) {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metodo HTTP non consentito']);
    exit;
}

$headers = [
    "X-Api-Key: " . $data['XApiKey']
];
if (isset($data['Bearertoken']) && $data['Bearertoken'] != null) {
    $headers[] = "Bearertoken: " . $data['Bearertoken'];
}

try {
    $risultati = ServerToServer::callURL(
        $data['url'],
        $metodo,
        $data['data'] ?? [],
        $data['dataType'] ?? null,
        $headers
    );

    echo $risultati->Response;
} catch (Exception $e) {
    http_response_code(502);
    echo json_encode(['status' => 'error', 'message' => 'Errore gateway: ' . $e->getMessage()]);
}