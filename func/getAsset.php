<?php
/**
 * getAsset.php — Endpoint per servire file dalla cartella asset/.
 *
 * Parametri GET:
 *   ID      (string, obbligatorio) Chiave dell'asset definita in asset/mapping.json
 *   w       (int,    opzionale)    Larghezza massima in pixel
 *   h       (int,    opzionale)    Altezza massima in pixel
 *   stretch (1|0,    opzionale)    Se 1 + w + h: forza dimensioni esatte ignorando l'aspect ratio
 *
 * Comportamento per le immagini:
 *   - Se w o h sono presenti → ridimensiona rispettando i vincoli (proporzionale di default)
 *   - Se nessun parametro    → cap automatico a 1280×720 (safety net per immagini enormi)
 *   - In entrambi i casi il risultato viene cachato in asset/cache/ al primo accesso
 *   - Immagini già dentro i limiti vengono servite direttamente dall'originale (nessun file di cache scritto)
 *
 * Cache su disco (asset/cache/):
 *   favIcon_w512.png        ← w=512
 *   favIcon_w512_h512.png   ← w=512 & h=512 (proporzionale, fit nel box)
 *   favIcon_w512_h512_s.png ← w=512 & h=512 & stretch=1 (esattamente 512×512)
 *   favIcon_hd.png          ← nessun param, immagine era sopra HD → cachato ridimensionato
 *
 * Per invalidare la cache dopo aver cambiato un'immagine sorgente:
 *   eliminare asset/cache/ o il singolo file {ID}_*.ext
 *
 * File non-immagine (PDF, SVG, font…): serviti direttamente senza alcuna elaborazione.
 * Asset con URL esterno nel mapping: risposta 302 redirect.
 */

require_once(dirname(__DIR__) . "/FE_utils/Asset.php");

// ---------------------------------------------------------------------------
// Funzioni helper GD
// ---------------------------------------------------------------------------

/**
 * Crea una risorsa GD leggendo il file in base al suo MIME type.
 * Restituisce false se il formato non è supportato (es. TIFF, ICO).
 */
function createImageFromPath(string $file_path, string $content_type)
{
    switch ($content_type) {
        case 'image/jpeg':
            return imagecreatefromjpeg($file_path);
        case 'image/png':
            return imagecreatefrompng($file_path);
        case 'image/gif':
            return imagecreatefromgif($file_path);
        case 'image/webp':
            return imagecreatefromwebp($file_path);
        case 'image/bmp':
            return imagecreatefrombmp($file_path);
        default:
            return false;
    }
}

/**
 * Scrive una risorsa GD su file (se $targetPath fornito) oppure direttamente
 * nell'output HTTP. Restituisce false se il formato non è supportato.
 */
function writeImageByMime($image, string $content_type, ?string $targetPath = null): bool
{
    switch ($content_type) {
        case 'image/jpeg':
            return $targetPath !== null ? imagejpeg($image, $targetPath) : imagejpeg($image);
        case 'image/png':
            return $targetPath !== null ? imagepng($image, $targetPath) : imagepng($image);
        case 'image/gif':
            return $targetPath !== null ? imagegif($image, $targetPath) : imagegif($image);
        case 'image/webp':
            return $targetPath !== null ? imagewebp($image, $targetPath) : imagewebp($image);
        case 'image/bmp':
            return $targetPath !== null ? imagebmp($image, $targetPath) : imagebmp($image);
        default:
            return false;
    }
}

/**
 * Abilita la gestione della trasparenza sulla canvas GD per i formati che la supportano
 * (PNG, GIF, WebP). Va chiamato sulla canvas di destinazione PRIMA di imagecopyresampled.
 */
function enableAlphaIfNeeded($image, string $content_type): void
{
    if (in_array($content_type, ['image/png', 'image/gif', 'image/webp'], true)) {
        imagealphablending($image, false); // non mescolare con lo sfondo: preserva l'alpha del sorgente
        imagesavealpha($image, true);      // salva il canale alpha nell'output
    }
}

// ---------------------------------------------------------------------------
// Funzione principale di ridimensionamento
// ---------------------------------------------------------------------------

/**
 * Ridimensiona un'immagine tramite GD e restituisce i byte del risultato.
 *
 * Il calcolo delle dimensioni target avviene in questo ordine:
 *   1. Se w e/o h presenti + stretch=true  → dimensioni esatte (distorce se necessario)
 *   2. Se w e/o h presenti + stretch=false → fit proporzionale nel box w×h
 *   3. Se nessun parametro                 → cap automatico a 1280×720
 *
 * Se dopo il calcolo le dimensioni coincidono con l'originale, restituisce null:
 * il chiamante deve servire il file originale.
 *
 * @param string      $file_path    Percorso assoluto al file sorgente
 * @param string      $content_type MIME type dell'immagine (es. "image/png")
 * @param array       $resize       Opzioni: ['w' => int|null, 'h' => int|null, 'stretch' => bool]
 * @return string|null Binary dell'immagine ridimensionata, oppure null se non serve ridimensionare
 */
function resizeImage(
    string $file_path,
    string $content_type,
    array $resize = []
): ?string {
    $reqW = $resize['w'] ?? null;
    $reqH = $resize['h'] ?? null;
    $stretch = !empty($resize['stretch']);

    // GD non disponibile: fallback all'originale (gestito dal chiamante)
    if (!extension_loaded('gd')) {
        return null;
    }

    [$width, $height] = getimagesize($file_path);
    if (!$width || !$height) {
        http_response_code(400);
        exit;
    }

    // --- Calcolo dimensioni target ---

    if ($reqW !== null || $reqH !== null) {
        // Resize esplicito richiesto dal chiamante
        if ($stretch && $reqW !== null && $reqH !== null) {
            // Stretch: dimensioni esatte, l'aspect ratio può cambiare
            $new_width = $reqW;
            $new_height = $reqH;
        } else {
            // Fit proporzionale: scala finché entrambi i vincoli sono rispettati
            $max_w = $reqW ?? PHP_INT_MAX;
            $max_h = $reqH ?? PHP_INT_MAX;
            $scale = min($max_w / $width, $max_h / $height);
            $new_width = (int) ceil($scale * $width);
            $new_height = (int) ceil($scale * $height);
        }

        // Nessun resize necessario: le dimensioni calcolate coincidono con l'originale
        if ($new_width === $width && $new_height === $height) {
            return null;
        }
    } else {
        // Nessun parametro: safety net HD — cap a 1280×720 per non servire immagini enormi
        $max_width = 1280;
        $max_height = 720;
        if ($width > $max_width || $height > $max_height) {
            $scale = min($max_width / $width, $max_height / $height);
            $new_width = (int) ceil($scale * $width);
            $new_height = (int) ceil($scale * $height);
        } else {
            // Immagine già dentro i limiti HD: niente resize
            return null;
        }
    }

    // --- Elaborazione GD ---

    $image_resized = imagecreatetruecolor($new_width, $new_height);
    enableAlphaIfNeeded($image_resized, $content_type);

    $image_original = createImageFromPath($file_path, $content_type);
    if ($image_original === false) {
        // Formato non supportato da GD (es. TIFF, ICO)
        http_response_code(415);
        exit;
    }

    imagecopyresampled(
        $image_resized,
        $image_original,
        0,
        0,
        0,
        0,
        $new_width,
        $new_height,
        $width,
        $height
    );

    ob_start();
    if (!writeImageByMime($image_resized, $content_type)) {
        ob_end_clean();
        imagedestroy($image_resized);
        imagedestroy($image_original);
        http_response_code(415);
        exit;
    }
    $encoded = ob_get_clean();

    imagedestroy($image_resized);
    imagedestroy($image_original);

    return $encoded === false ? null : $encoded;
}

// ---------------------------------------------------------------------------
// Gestione della richiesta
// ---------------------------------------------------------------------------

// Sanifica l'ID: solo caratteri alfanumerici, trattino e underscore
$sanitizedID = preg_replace('/[^a-zA-Z0-9_-]/', '', isset($_GET['ID']) ? $_GET['ID'] : '');

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sanitizedID)) {
    http_response_code(400); // Bad Request: ID assente o malformato
    exit;
}

// Risolve l'ID nel percorso reale tramite asset/mapping.json
$path = Asset::getPath($sanitizedID);

if (filter_var($path, FILTER_VALIDATE_URL)) {
    // Asset esterno: redirect 302 all'URL originale
    header("Location: $path");
    exit;
} else {
    $file_path = dirname(__DIR__) . "/asset/" . $path;

    if ($path !== null && file_exists($file_path)) {
        $content_type = Asset::getMimeType($file_path);

        if (strpos($content_type, 'image/') === 0) {
            // --- Immagine: gestione resize + cache ---

            $reqW = isset($_GET['w']) ? (int) $_GET['w'] : null;
            $reqH = isset($_GET['h']) ? (int) $_GET['h'] : null;
            $stretch = isset($_GET['stretch']) && $_GET['stretch'] === '1';

            // Costruzione della cache key:
            //   {ID}             → nessun param (la chiave _hd è aggiunta sotto)
            //   {ID}_w{W}        → solo larghezza
            //   {ID}_h{H}        → solo altezza
            //   {ID}_w{W}_h{H}   → entrambe, fit proporzionale
            //   {ID}_w{W}_h{H}_s → entrambe, stretch esatto
            //   {ID}_hd          → nessun param, ma l'immagine era sopra i limiti HD
            $cacheDir = dirname(__DIR__) . '/asset/cache/';
            $ext = pathinfo($path, PATHINFO_EXTENSION); // ricavato dal mapping, funziona per qualsiasi formato
            $cacheKey = $sanitizedID;
            if ($reqW !== null || $reqH !== null) {
                if ($reqW !== null)
                    $cacheKey .= '_w' . $reqW;
                if ($reqH !== null)
                    $cacheKey .= '_h' . $reqH;
                if ($stretch && $reqW !== null && $reqH !== null)
                    $cacheKey .= '_s';
            } else {
                $cacheKey .= '_hd'; // suffisso per distinguere dall'originale in caso di HD cap
            }
            $cachePath = $cacheDir . $cacheKey . '.' . $ext;

            // Cache hit: servi direttamente il file già ridimensionato, zero GD
            if (file_exists($cachePath)) {
                header("Content-Type: $content_type");
                readfile($cachePath);
                exit;
            }

            // Cache miss: ridimensiona e poi gestisci cache/response fuori dalla funzione
            $resizedContent = resizeImage(
                $file_path,
                $content_type,
                ['w' => $reqW, 'h' => $reqH, 'stretch' => $stretch]
            );

            header("Content-Type: $content_type");
            if ($resizedContent === null) {
                readfile($file_path);
                exit;
            }

            $dir = dirname($cachePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            // Scrivi in cache; se fallisce logga ma servi comunque — il Content-Type
            // è già stato inviato, quindi echo $resizedContent produce la stessa risposta
            // che farebbe readfile(): il browser riceve gli stessi byte.
            if (file_put_contents($cachePath, $resizedContent) === false) {
                error_log("getAsset: impossibile scrivere cache in $cachePath");
            }
            echo $resizedContent;
            exit;
        } else {
            // File non-immagine (PDF, font, SVG…): servito as-is senza elaborazione
            header("Content-Type: $content_type");
            readfile($file_path);
        }
    } else {
        http_response_code(404); // Asset non trovato nel mapping o file mancante
    }
}
