<?php
namespace BLL;

require_once 'APIException.php';
class Repository
{
    /**
     * Cache statica per il percorso API, calcolato una sola volta.
     */
    private static ?string $cachedAPIPath = null;

    /**
     * Restituisce il percorso assoluto della directory 'API/'.
     * Calcolato una sola volta tramite __DIR__ (API/BLL → API/) per massima
     * compatibilità con hosting condivisi (evita ricorsione e problemi open_basedir).
     *
     * @return string|null Percorso della directory 'API' se trovata, altrimenti null.
     */
    public static function findAPIPath(): ?string
    {
        if (self::$cachedAPIPath !== null) {
            return self::$cachedAPIPath;
        }

        // __DIR__ è API/BLL, dirname(__DIR__) è API/
        $path = dirname(__DIR__) . '/';
        self::$cachedAPIPath = file_exists($path) ? $path : null;
        return self::$cachedAPIPath;
    }

    /**
     * Ottiene il nome del file JSON partendo da un nome base.
     * 
     * @param string $nome Nome base per il file.
     * @param string $ext estensione per il file.
     * @return string Percorso completo del file.
     */
    public static function getFileName(string $nome, string $ext = "json"): string
    {
        return self::findAPIPath() . 'data/' . $nome . '.' . $ext;
    }

    /**
     * Ottiene il file
     * 
     * @param string $filePath Nome per il file.
     * @return string Contenuto completo del file
     */
    public static function getFileContent(string $filePath): string
    {
        if (file_exists($filePath) && is_readable($filePath)) {
            return file_get_contents($filePath);
        } else {
            throw new NotFoundException(pathinfo($filePath, PATHINFO_FILENAME));
        }
    }

    /**
     * Ottiene un oggetto da un file JSON. Se 'decodeInData' è vero, decodifica il contenuto del file.
     * 
     * @param string $nome Nome base per il file.
     * @param bool $decodeInData Indica se decodificare il contenuto in un array.
     * @return mixed Oggetto o contenuto del file.
     * @throws DecodingException Se la decodifica JSON fallisce.
     * @throws NotFoundException Se il file non può essere letto.
     */
    public static function getObj(string $nome, bool $decodeInData = true): mixed
    {
        $fileContent = self::getFileContent(self::getFileName($nome));
        if ($decodeInData) {
            $jsonData = json_decode($fileContent, true);

            if ($jsonData === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new DecodingException();
            }
            return $jsonData;
        } else {
            return $fileContent;
        }
    }

    /**
     * Ottiene il contenuto di un file di testo.
     * 
     * @param string $nome Nome base per il file.
     * @return string Contenuto del file.
     * @throws NotFoundException Se il file non può essere letto.
     */
    public static function getTxt(string $nome): string
    {
        $filePath = self::getFileName($nome, "txt");
        return self::getFileContent($filePath);
    }

    /**
     * Scrive un oggetto JSON.
     * 
     * @param string $nome Nome base per il file.
     * @param mixed $jsonData Dati da scrivere.
     * @param bool $isDecodedInData Indica se i dati sono già in formato JSON.
     */
    public static function putObj(string $nome, mixed $jsonData, bool $isDecodedInData = true): void
    {
        $filename = self::getFileName($nome);

        if ($isDecodedInData) {
            $fileContent = json_encode($jsonData);
        } else {
            $fileContent = $jsonData;
        }

        if (file_put_contents($filename, $fileContent) === false) {
            throw new \RuntimeException("Impossibile scrivere il file: $filename");
        }
    }

    public static function getDefaultLang(): string
    {
        return "it";
    }
}

class Logging
{
    /**
     * Scrive nel file di log con parametri variabili.
     * 
     * @param string $tipo Tipo di log (usato nel nome file).
     * @param string $stringa Messaggio di log (supporta sprintf).
     * @param mixed ...$oggetti Parametri per sprintf.
     */
    public static function log(string $tipo, string $stringa, mixed ...$oggetti): void
    {
        $file = Repository::getFileName($tipo . '_log', 'txt');
        $timestamp = date('Y-m-d H:i:s');

        if (!empty($oggetti)) {
            $stringa = sprintf($stringa, ...$oggetti);
        }

        $log = sprintf("[%s] %s\n", $timestamp, $stringa);
        file_put_contents($file, $log, FILE_APPEND);
    }

    public static function logError(string $stringa, mixed ...$oggetti): void
    {
        self::log('error', $stringa, ...$oggetti);
    }

    public static function logInfo(string $stringa, mixed ...$oggetti): void
    {
        self::log('info', $stringa, ...$oggetti);
    }

    public static function logWarning(string $stringa, mixed ...$oggetti): void
    {
        self::log('warning', $stringa, ...$oggetti);
    }
}
