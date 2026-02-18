<?php

// Configurazione TOKEN
// NOTA: cambia CRYPTO_KEY in produzione con una stringa di almeno 32 caratteri random
define('CRYPTO_KEY', 'chiave_segretissima');
define('TOKEN_EXPIRATION', 3000);
define('CRYPTO_ALGO', 'aes-256-cbc');

/**
 * Genera un token crittografato basato sulla password fornita e il timestamp corrente.
 *
 * Il token contiene: IV random (16 byte) + ciphertext, codificati in base64.
 * L'IV random per ogni token garantisce che lo stesso plaintext produca
 * ciphertext diversi (requisito crittografico fondamentale di AES-CBC).
 *
 * @param string $password La password fornita dall'utente.
 * @return array ['valid' => bool, 'token' => string|null, 'error' => string|null]
 */
function generaToken(string $password): array
{
    try {
        if ($password !== PASSWORD_TOKEN_CORRETTA) {
            throw new Exception('Password non corretta.');
        }

        $dati = json_encode([
            'password' => $password,
            'timestamp' => time()
        ]);

        if ($dati === false) {
            throw new RuntimeException('Errore nella codifica JSON dei dati.');
        }

        // IV random per ogni token (standard crittografico — mai riusare lo stesso IV)
        $ivLength = openssl_cipher_iv_length(CRYPTO_ALGO);
        $iv = openssl_random_pseudo_bytes($ivLength);

        $ciphertext = openssl_encrypt($dati, CRYPTO_ALGO, CRYPTO_KEY, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new RuntimeException('Errore durante la crittografia.');
        }

        // Token = base64(IV + ciphertext) — l'IV viene preposto al ciphertext
        $token = base64_encode($iv . $ciphertext);

        return ['valid' => true, 'token' => $token];
    } catch (Exception $e) {
        return ['valid' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Verifica la validità di un token crittografato.
 *
 * Estrae l'IV dai primi 16 byte del token decodificato,
 * poi decifra il ciphertext rimanente.
 *
 * @param string $token Il token crittografato (base64) da verificare.
 * @return array ['valid' => bool, 'error' => string|null, 'code' => int]
 */
function verificaToken(string $token): array
{
    try {
        $raw = base64_decode($token, true);
        if ($raw === false) {
            throw new Exception('Token non valido (decodifica base64 fallita).', 401);
        }

        // Estrai IV (primi N byte) e ciphertext (il resto)
        $ivLength = openssl_cipher_iv_length(CRYPTO_ALGO);
        if (strlen($raw) < $ivLength) {
            throw new Exception('Token troppo corto.', 401);
        }

        $iv = substr($raw, 0, $ivLength);
        $ciphertext = substr($raw, $ivLength);

        // Decifra il token
        $datiDecifrati = openssl_decrypt($ciphertext, CRYPTO_ALGO, CRYPTO_KEY, OPENSSL_RAW_DATA, $iv);
        if ($datiDecifrati === false) {
            http_response_code(401);
            throw new Exception('Token non valido o corrotto.', 401);
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

        return ['valid' => true, 'error' => null, 'code' => 200];
    } catch (Exception $e) {
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


$settingsFolder = "BLL/auth_settings/";

$filePwd = BLL\Repository::findAPIPath() . $settingsFolder . 'pwd.txt';
if (file_exists($filePwd))
    define('PASSWORD_TOKEN_CORRETTA', BLL\Repository::getFileContent($filePwd));
else
    define('PASSWORD_TOKEN_CORRETTA', null);


/**
 * Ottiene il valore di un header HTTP in modo case-insensitive.
 *
 * @param array $headers Array degli header dalla richiesta.
 * @param string $name Nome dell'header da cercare.
 * @return string|null Valore dell'header o null se non trovato.
 */
function getHeaderCaseInsensitive(array $headers, string $name): ?string
{
    $nameLower = strtolower($name);
    foreach ($headers as $key => $value) {
        if (strtolower($key) === $nameLower) {
            return $value;
        }
    }
    return null;
}

// Ottiene tutti gli header della richiesta HTTP
$headers = getallheaders();

// Gestione delle impostazioni CORS (prima del check API key per le preflight OPTIONS)
$fileCorsConfig = BLL\Repository::findAPIPath() . $settingsFolder . "CORSconfig.json";
// Controlla se esiste il file di configurazione CORS
if (file_exists($fileCorsConfig)) {
    // Decodifica il file JSON di configurazione CORS
    $config = json_decode(file_get_contents($fileCorsConfig), true);
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
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Api-Key, Bearertoken");

        // Gestione preflight OPTIONS: rispondi e termina senza validare API key
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}

// Legge il file contenente le API keys autorizzate
$apiKeys = file(BLL\Repository::findAPIPath() . $settingsFolder . 'APIKeys.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Controlla se l'header 'X-Api-Key' esiste e se corrisponde a una delle API keys autorizzate (case-insensitive)
$apiKey = getHeaderCaseInsensitive($headers, 'X-Api-Key');
if ($apiKey === null || !in_array($apiKey, $apiKeys)) {
    http_response_code(403); // Invia un codice di risposta HTTP 403 (Forbidden)
    exit; // Termina l'esecuzione dello script
}


/**
 * Recupera i dati in "php://input"
 * @return mixed "php://input" Parsato se possibile
 */
function datiInput()
{
    $result = file_get_contents('php://input');
    $decoded = json_decode($result, true);

    if ($decoded !== null || json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // Fallback: prova a parsare come form-urlencoded
    parse_str($result, $rawData);
    return $rawData;
}

// Funzione per estrarre e verificare il token dall'header "Bearertoken"
function requiresToken(): void
{
    $headers = getallheaders();

    // Controlla se il token è presente nell'header (case-insensitive)
    $token = getHeaderCaseInsensitive($headers, 'Bearertoken');
    if ($token === null) {
        http_response_code(401); // Codice HTTP 401 Unauthorized
        echo BLL\Response::retError('Token assente', true);
        exit; // Termina l'esecuzione
    }

    // Verifica il token
    $res = verificaToken($token);

    if (!$res['valid']) {
        http_response_code($res['code']); // Imposta il codice HTTP
        echo BLL\Response::retError($res['error'], true);
        exit; // Termina l'esecuzione
    }

}
