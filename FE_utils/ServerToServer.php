<?php

class URLResponse
{
    public mixed $Response;
    public string $ResponseContentType;
    public function __construct(mixed $Response, string $ResponseContentType)
    {
        $this->Response = $Response;
        $this->ResponseContentType = $ResponseContentType;
    }
}

class ServerToServer
{
    /**
     * Esegue una chiamata all'endpoint dell'API utilizzando il metodo HTTP specificato e restituisce la risposta.
     * 
     * @param string $url L'URL dell'endpoint.
     * @param string $metodo Il metodo HTTP da utilizzare (ad es. 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'). Di default è 'GET'.
     * @param array $dati I dati da inviare con la richiesta.
     * @param string|null $contentType Il Content Type della richiesta.
     * @param array $headerPersonalizzati Header HTTP personalizzati.
     * @param int $timeoutTotale Il timeout totale in secondi. Di default è 30.
     * @param int $timeoutConnessione Il timeout per la connessione in secondi. Di default è 10.
     * @param bool $CheckSSL Controllo SSL dell'endpoint.
     * @return URLResponse Risposta dell'API.
     * @throws InvalidArgumentException Se l'URL non è valido.
     * @throws Exception In caso di errore nella chiamata.
     */
    public static function callURL(
        string $url,
        string $metodo = "GET",
        array $dati = [],
        ?string $contentType = null,
        array $headerPersonalizzati = [],
        int $timeoutTotale = 30,
        int $timeoutConnessione = 10,
        bool $CheckSSL = true
    ): URLResponse {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("L'URL fornito non è valido");
        }

        // Imposta il Content-Type predefinito in base al metodo
        if ($contentType === null) {
            $contentType = strtoupper($metodo) === "POST"
                ? 'application/x-www-form-urlencoded'
                : 'application/json';
        }

        // Aggiungi i parametri all'URL per i metodi GET
        if (strtoupper($metodo) === "GET" && !empty($dati)) {
            $url .= '?' . http_build_query($dati);
        }

        $ch = curl_init($url);

        // Configurazione dei timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutTotale);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeoutConnessione);

        // Configurazione SSL
        if (!$CheckSSL) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        }

        // Prepara gli header
        $header = [
            "Content-Type: $contentType",
        ];
        $header = array_merge($header, $headerPersonalizzati);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Configura il metodo HTTP e il body della richiesta
        switch (strtoupper($metodo)) {
            case "POST":
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($dati)) {
                    $postData = ($contentType === 'application/x-www-form-urlencoded')
                        ? http_build_query($dati)
                        : json_encode($dati);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                }
                break;

            case "PUT":
            case "PATCH":
            case "DELETE":
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($metodo));
                if (!empty($dati)) {
                    $postData = ($contentType === 'application/x-www-form-urlencoded')
                        ? http_build_query($dati)
                        : json_encode($dati);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                }
                break;
        }

        // Esegui la richiesta cURL
        $response = curl_exec($ch);

        // Verifica errori nella richiesta
        if ($response === false) {
            $errorCode = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($errorCode === CURLE_OPERATION_TIMEDOUT) {
                throw new Exception("Timeout della richiesta raggiunto: " . $error);
            }
            throw new Exception("Errore EndPoint: " . $error);
        }

        // Controlla il codice HTTP della risposta
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // Recupera il content type PRIMA di chiudere l'handle cURL
        $responseContentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?? '';

        if (!($httpCode >= 200 && $httpCode < 300)) {
            curl_close($ch);
            throw new Exception("Errore HTTP: " . $httpCode . " - Risposta: " . $response);
        }

        // Chiudi la sessione cURL dopo aver letto tutte le info necessarie
        curl_close($ch);

        return new URLResponse($response, $responseContentType);
    }
}
