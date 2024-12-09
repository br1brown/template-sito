<?php

// Configurazione TOKEN
define('CRYPTO_KEY', 'chiave_segretissima');
define('TOKEN_EXPIRATION', 3000);

/**
 * Genera un token crittografato basato sulla password fornita e il timestamp corrente.
 *
 * @param string $password La password fornita dall'utente.
 * @return array Un array contenente:
 *               - 'valid' (bool): Indica se l'operazione è andata a buon fine.
 *               - 'token' (string|null): Il token generato (se valido).
 *               - 'error' (string|null): Messaggio d'errore (se non valido).
 * @throws RuntimeException Se si verifica un errore durante la codifica JSON o la crittografia.
 */
function generaToken(string $password): array
{
    try {
        // Verifica della password
        if ($password !== PASSWORD_TOKEN_CORRETTA) {
            throw new Exception('Password non corretta.');
        }

        $timestamp = time();
        $dati = json_encode([
            'password' => $password,
            'timestamp' => $timestamp
        ]);

        if ($dati === false) {
            throw new RuntimeException('Errore nella codifica JSON dei dati.');
        }

        $iv = substr(CRYPTO_KEY, 0, 16); // Inizializzazione del vettore (IV)
        $token = openssl_encrypt($dati, 'aes-256-cbc', CRYPTO_KEY, 0, $iv);

        if ($token === false) {
            throw new RuntimeException('Errore durante la crittografia.');
        }

        return ['valid' => true, 'token' => $token];
    } catch (Exception $e) {
        return ['valid' => false, 'error' => $e->getMessage()];
    }
}

// Funzione per verificare il token

/**
 * Verifica la validità di un token crittografato.
 *
 * @param string $token Il token crittografato da verificare.
 * @return array Un array contenente:
 *               - 'valid' (bool): Indica se il token è valido.
 *               - 'error' (string|null): Messaggio d'errore (se non valido).
 */
function verificaToken(string $token): array
{
    try {
        $iv = substr(CRYPTO_KEY, 0, 16); // Inizializzazione del vettore (IV)

        // Decifra il token
        $datiDecifrati = openssl_decrypt($token, 'aes-256-cbc', CRYPTO_KEY, 0, $iv);
        if ($datiDecifrati === false) {
            http_response_code(401); // Codice HTTP 401 Unauthorized
            throw new Exception('Token non valido o corrotto.', 1001);
        }

        // Decodifica il JSON
        $dati = json_decode($datiDecifrati, true);
        if (!is_array($dati) || !isset($dati['password'], $dati['timestamp'])) {
            throw new Exception('Struttura del token non valida.', 400);
        }

        // Verifica della password
        if (defined('PASSWORD_TOKEN_CORRETTA') && $dati['password'] !== PASSWORD_TOKEN_CORRETTA) {
            throw new Exception('Password errata.', 403);
        }

        // Verifica della scadenza del token
        if (time() - $dati['timestamp'] > TOKEN_EXPIRATION) {
            throw new Exception('Token scaduto.', 401);
        }

        // Se tutto è valido
        return ['valid' => true, 'error' => null, 'code' => 200];
    } catch (Exception $e) {
        // Gestione delle eccezioni con codici HTTP già impostati sopra
        return [
            'valid' => false,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ];
    }
}



// Includi i file necessari
require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/Response.php';
include __DIR__ . '/funzioni_comuni.php';


$settingsfolder = "BLL/auth_settings/";

$filePWD = BLL\Repository::findAPIPath() . $settingsfolder . 'pwd.txt';
if (file_exists($filePWD))
    define('PASSWORD_TOKEN_CORRETTA', BLL\Repository::getFileContent($filePWD));
else
    define('PASSWORD_TOKEN_CORRETTA', null);


// Ottiene tutti gli header della richiesta HTTP
$headers = getallheaders();

// Legge il file contenente le API keys autorizzate
$paroleSegrete = file(BLL\Repository::findAPIPath() . $settingsfolder . 'APIKeys.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Controlla se l'header 'X-Api-Key' esiste e se corrisponde a una delle API keys autorizzate
if (!isset($headers['X-Api-Key']) || !in_array($headers['X-Api-Key'], $paroleSegrete)) {
    http_response_code(403); // Invia un codice di risposta HTTP 403 (Forbidden)
    exit; // Termina l'esecuzione dello script
}

// Gestione delle impostazioni CORS
$fileconfigCORS = BLL\Repository::findAPIPath() . $settingsfolder . "CORSconfig.json";
// Controlla se esiste il file di configurazione CORS
if (file_exists($fileconfigCORS)) {
    // Decodifica il file JSON di configurazione CORS
    $config = json_decode(file_get_contents($fileconfigCORS), true);
    $applyCORS = $config['applyCORS'];

    // Applica le impostazioni CORS se richiesto
    if ($applyCORS) {
        $allowedOrigins = $config['allowedOrigins'] ?? [];
        // Ottiene l'origine della richiesta
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        // Imposta l'header 'Access-Control-Allow-Origin' in base all'origine
        if (empty($allowedOrigins)) {
            // Permette tutte le origini se non sono specificate origini consentite
            header("Access-Control-Allow-Origin: *");
        } elseif (in_array($origin, $allowedOrigins)) {
            // Permette solo le origini specificate nella lista di origini consentite
            header("Access-Control-Allow-Origin: $origin");
        }

        // Imposta gli altri header CORS per i metodi e gli header consentiti
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
    }
}


/**
 * Recupera i dati in "php://input"
 * @return mixed "php://input" Parsato se possibile
 */
function datiinput()
{
    $result = file_get_contents('php://input');
    try {
        return json_decode($result, true);
    } catch (\Exception $e) {
        parse_str($result, $rawData);
        return $rawData;
    }
}

// Funzione per estrarre e verificare il token dall'header "BearerToken"
function possoProcedere(): void
{
    $headers = getallheaders();

    // Controlla se il token è presente nell'header
    if (!isset($headers['BearerToken'])) {
        http_response_code(401); // Codice HTTP 401 Unauthorized
        echo BLL\Response::retError('Token assente', true);
        exit; // Termina l'esecuzione
    }

    // Verifica il token
    $res = verificaToken($headers['BearerToken']);

    if (!$res['valid']) {
        http_response_code($res['code']); // Imposta il codice HTTP
        echo BLL\Response::retError($res['error'], true);
        exit; // Termina l'esecuzione
    }

}
