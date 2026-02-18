<?php

class Asset
{

    private static function loadPaths()
    {
        $jsonPath = dirname(__DIR__) . '/asset/mapping.json'; // Percorso al file JSON
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("File asset mapping non trovato");
        }
        return json_decode(file_get_contents($jsonPath), true);
    }

    public static function getPath($id)
    {
        $paths = self::loadPaths(); // Assicurati che i percorsi siano caricati
        if (isset($paths[$id])) {
            return $paths[$id];
        } else {
            return null; // Ritorna null se l'ID non esiste
        }
    }

    public static function getMimeType($filePath)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        return $mime_type;
    }
}
