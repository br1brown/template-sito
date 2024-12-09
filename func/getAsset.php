<?php
require_once(dirname(__DIR__) . "/FE_utils/Asset.php");

function LoadSmallImage($file_path, $content_type)
{
    // Controlla se l'estensione GD Library è caricata
    if (!extension_loaded('gd')) {
        // Se GD non è caricata, mostra l'immagine originale
        header("Content-Type: $content_type");
        readfile($file_path);
        exit;
    }
    list($width, $height) = getimagesize($file_path);
    if (!$width || !$height) {
        // Gestire l'errore, ad esempio loggando l'errore e restituendo un'immagine di fallback o un messaggio di errore
        http_response_code(400);
        exit;
    }
    // Dimensioni massime HD
    $max_width = 1280;
    $max_height = 720;

    if ($width > $max_width || $height > $max_height) {
        // Calcola il rapporto di ridimensionamento
        $scale = min($max_width / $width, $max_height / $height);

        $new_width = ceil($scale * $width);
        $new_height = ceil($scale * $height);

        // Crea una nuova immagine ridimensionata
        $image_resized = imagecreatetruecolor($new_width, $new_height);

        // Se il tipo è PNG, GIF, WebP o altri formati con trasparenza, abilita la gestione della trasparenza
        switch ($content_type) {
            case 'image/jpeg':
                $image_original = imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $image_original = imagecreatefrompng($file_path);
                // Abilita la trasparenza
                imagealphablending($image_resized, false);
                imagesavealpha($image_resized, true);
                break;
            case 'image/gif':
                $image_original = imagecreatefromgif($file_path);
                // Abilita la trasparenza
                imagealphablending($image_resized, false);
                imagesavealpha($image_resized, true);
                break;
            case 'image/webp':
                $image_original = imagecreatefromwebp($file_path);
                // Abilita la trasparenza per WebP
                imagealphablending($image_resized, false);
                imagesavealpha($image_resized, true);
                break;
            case 'image/bmp':
                $image_original = imagecreatefrombmp($file_path);
                break;
            case 'image/tiff':
                //$image_original = imagecreatefromtiff($file_path);
                break;
            case 'image/x-icon':
                //$image_original = imagecreatefromico($file_path);
                break;
            default:
                // Gestire il tipo di immagine non supportato
                http_response_code(415);
                exit;
        }

        // Copia e ridimensiona l'immagine vecchia nella nuova
        imagecopyresampled($image_resized, $image_original, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Output dell'immagine ridimensionata
        header("Content-Type: $content_type");
        switch ($content_type) {
            case 'image/jpeg':
                imagejpeg($image_resized);
                break;
            case 'image/png':
                imagepng($image_resized);
                break;
            case 'image/gif':
                imagegif($image_resized);
                break;
            case 'image/webp':
                imagewebp($image_resized);
                break;
            case 'image/bmp':
                imagebmp($image_resized);
                break;
            case 'image/tiff':
                //imagetiff($image_resized);
                break;
            case 'image/x-icon':
                //imageico($image_resized);
                break;
            default:
                // Gestire il tipo di immagine non supportato
                http_response_code(415);
                exit;
        }

        // Pulizia della memoria
        imagedestroy($image_resized);
        imagedestroy($image_original);
    } else {
        // Se l'immagine è già entro i limiti, mostra come è
        header("Content-Type: $content_type");
        readfile($file_path);
    }
}

$sanitizedID = preg_replace('/[^a-zA-Z0-9_-]/', '', isset($_GET['ID']) ? $_GET['ID'] : '');

// Verifica che l'ID sanificato corrisponda a un formato atteso, ad esempio un numero o un identificativo alfanumerico
if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sanitizedID)) {
    http_response_code(400); // Bad Request
    exit;
}

// Continua con il processo di caricamento dell'immagine usando l'ID sanificato
$path = Asset::getPath($sanitizedID);

if (filter_var($path, FILTER_VALIDATE_URL)) {
    header("Location: $path");
    exit;
} else {
    $file_path = dirname(__DIR__) . "/asset/" . $path;

    if ($path !== null && file_exists($file_path)) {
        $content_type = Asset::getMimeType($file_path);

        // Verifica se il content type è un'immagine prima di procedere
        if (strpos($content_type, 'image/') === 0) {
            LoadSmallImage($file_path, $content_type);
        } else {
            header("Content-Type: $content_type");
            readfile($file_path);
        }
    } else {
        http_response_code(404);
    }
}
