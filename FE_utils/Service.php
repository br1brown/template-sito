<?php
require_once __DIR__ . '/DTOWebsite.php';
require_once __DIR__ . '/Traduzione.php';
require_once __DIR__ . '/ServerToServer.php';

class Service
{

    /**
     * @var array Impostazioni dell'applicativo
     */
    private array $settings;

    /**
     * @var array Chiavi da escludere dalle impostazioni quando richiesto.
     */
    private $excludeKeys = ['API', "meta", 'lang', "description", "ExternalLink"];

    /**
     * Restituisce le impostazioni dell'applicativo necessarie
     *
     * @return array Impostazioni filtrate.
     */
    public function getSettings(): array
    {
        $data = array_filter($this->settings, function ($key) {
            return !in_array($key, $this->excludeKeys);
        }, ARRAY_FILTER_USE_KEY);

        $data["description"] = $this->settings["description"][$this->currentLang()] ?? "";

        if (!isset($data['colorTema']) || empty($data['colorTema'])) {
            $data['colorTema'] = "#606060";
        }
        if (!isset($data['colorBase']) || empty($data['colorBase'])) {
            $data['colorBase'] = $this->lightenColor($data['colorTema']);
        }

        $data['isDarkTextPreferred'] = $this->isDarkTextPreferred($data['colorTema']);
        $colorPrimary = $this->darkenColor($data['colorTema'], $data['isDarkTextPreferred'] ? 0.6 : 0);
        $colorLinkScuro = '#000029';
        $colorLinkChiaro = '#c4c4ff';

        $data["colori"] = [
            'colorLinkScuro' => $colorLinkScuro,
            'colorLinkChiaro' => $colorLinkChiaro,
            'colorLink' => $this->isDarkTextPreferred($data['colorTema']) ? $colorLinkScuro : $colorLinkChiaro,
            'colorBase' => $data['colorBase'],
            'colorTema' => $data['colorTema'],
            'colorPrimary' => $colorPrimary,
            'colorPrimaryScuro' => $this->darkenColor($colorPrimary, 0.2),
        ];
        unset($data['colorBase'], $data['colorTema']);

        $havesmoke = isset($data['smoke']) && $data['smoke']["enable"];
        $data['havesmoke'] = $havesmoke;

        $escludiroutes = ["getLang.php"];
        if (!$this->EsternaAPI)
            $escludiroutes[] = "gateway.php";

        $data['routes'] = $this->prepareAssets("func", "php", excludeFiles: $escludiroutes);

        unset(
            $data['GLOBALS'],
            $data['_SERVER'],
            $data['_GET'],
            $data['_POST'],
            $data['_FILES'],
            $data['_COOKIE'],
            $data['_SESSION'],
            $data['_REQUEST'],
            $data['_ENV']
        );

        return $data;
    }

    /**
     * Restituisce le impostazioni dei metatag e header
     *
     * @return MetaDTO Impostazioni filtrate.
     */
    public function getMeta(): MetaDTO
    {
        $metaDTO = new MetaDTO($this->settings['meta'] ?? []);

        // Dipendenze CDN — caricate su ogni pagina tramite preload hint + tag effettivo
        $metaDTO->linkRel = [
            new RelLink('css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'),
            // jQuery mantenuto per compatibilità con il codebase JS esistente
            new RelLink('js', 'https://code.jquery.com/jquery-3.7.1.min.js'),
            // Bootstrap bundle: include già Popper.js, non richiede dipendenze aggiuntive
            new RelLink('css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'),
            new RelLink('js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'),
            new RelLink('js', 'https://cdn.jsdelivr.net/npm/sweetalert2@11'),
        ];

        $havesmoke = isset($this->settings['smoke']) && $this->settings['smoke']["enable"];

        $excludeJs = $havesmoke ? [] : ["jquery_bloodforge_smoke_effect.js"];

        foreach ($this->prepareAssets("style", "css", ["base.css"], ["addon.css"]) as $css) {
            $metaDTO->linkRel[] = new RelLink("css", $this->baseURL("style/" . $css));
        }

        foreach ($this->prepareAssets("script", "js", ["lingua.js", "base.js"], ["addon.js"], $excludeJs) as $js) {
            $metaDTO->linkRel[] = new RelLink("js", $this->baseURL("script/" . $js));
        }

        if (isset($this->settings["ExternalLink"])) {
            foreach ($this->settings['ExternalLink'] as $est) {
                $metaDTO->linkRel[] = new RelLink($est["type"], $est["value"]);
            }
        }
        return $metaDTO;
    }

    /**
     * @var Traduzione Corrente della pagina
     */
    public Traduzione $_traduzione;

    /**
     * @var string URL dell'endpoint con le traduzioni
     */
    public string $pathLang;

    /**
     * @var string URL dell'API di servizio
     */
    public string $urlAPI;

    /**
     * @var bool URL dell'API è esterna?
     */
    public bool $EsternaAPI;

    /**
     * @var string Chiave dell'API di servizio
     */
    public string $APIKey;

    /**
     * @var string URL dell'Host
     */
    public string $baseUrl;

    /**
     * @var bool La connessione è HTTPS?
     */
    private bool $_isSecure = false;

    /**
     * Avvia la sessione PHP in modo lazy: solo quando serve davvero.
     *
     * PERCHÉ NON NEL COSTRUTTORE:
     * session_start() forza PHP a emettere "Cache-Control: no-store" su TUTTE
     * le pagine, anche quelle pubbliche. Questo blocca la back-forward cache
     * del browser (BFCache), rendendo ogni navigazione avanti/indietro un
     * reload completo. Chiamandola solo quando $requiresAuth = true in TopPage,
     * le pagine pubbliche rimangono cacheable e l'utente percepisce navigazione istantanea.
     *
     * PERCHÉ IL CONTROLLO session_status():
     * Se due metodi (es. isLoggedIn + getToken) vengono chiamati nella stessa
     * request, il secondo session_start() causerebbe un warning PHP.
     * Il controllo rende la funzione idempotente: chiamala quante volte vuoi,
     * la sessione parte una sola volta.
     */
    private function avviaSessione(): void
    {
        if ($this->hasSessionIn()) {
            session_start([
                'cookie_httponly' => true,   // Il cookie non è accessibile da JavaScript (protezione XSS)
                'cookie_secure' => $this->_isSecure, // Cookie solo su HTTPS se il sito è HTTPS
                'cookie_samesite' => 'Lax',  // Protezione CSRF: cookie non inviato su richieste cross-site
            ]);
        }
    }

    /**
     * Costruttore della classe Service.
     * Legge le impostazioni dal file JSON e inizializza l'URL dell'API.
     */
    public function __construct()
    {
        // Controllo protocollo: verifica HTTPS diretto o tramite proxy
        $protocol = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol = 'https';
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https';
        }

        $this->_isSecure = ($protocol === 'https');

        // Costruzione dell'URL base
        $this->baseUrl = $protocol . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/";

        // Caricamento impostazioni dal file JSON (percorso assoluto per compatibilità hosting)
        // Uso __FILE__ per risalire alla root del progetto, più affidabile di DOCUMENT_ROOT su hosting condivisi
        $settingsPath = dirname(__DIR__) . '/websettings.json';
        if (!file_exists($settingsPath)) {
            throw new \RuntimeException("File di configurazione websettings.json non trovato in: $settingsPath");
        }
        $this->settings = json_decode(file_get_contents($settingsPath), true);
        if (!is_array($this->settings)) {
            throw new \RuntimeException("websettings.json non valido o malformato.");
        }

        $this->caricaLingua();

        $this->APIKey = $this->settings['API']['key'];

        $APIEndPoint = $this->settings['API']['EndPoint'];
        $this->EsternaAPI = str_starts_with($APIEndPoint, "http://") || str_starts_with($APIEndPoint, "https://");
        if ($this->EsternaAPI) {
            $this->urlAPI = $APIEndPoint;
        } else {
            $this->urlAPI = $this->baseUrl . $APIEndPoint;
        }
    }

    /**
     * Carica le traduzioni per la lingua impostata se il file esiste.
     */
    private function caricaLingua(): void
    {
        $lang = strtolower($this->settings['lang']);
        if (isset($_GET["lang"]) && !empty($_GET["lang"])) {
            $candidate = strtolower(preg_replace('/[^a-z]/i', '', $_GET["lang"]));
            $lingueDisponibili = Traduzione::listaLingue(__DIR__ . "/lang");
            if (in_array($candidate, $lingueDisponibili, true))
                $lang = $candidate;
        }

        $this->pathLang = $this->baseURL("func/getLang?lang=" . $lang);
        $this->_traduzione = new Traduzione($lang);
    }

    /**
     * Restituisce l'elenco delle lingue disponibili basato sui file nella cartella lang.
     * @return array Un array con le lingue disponibili.
     */
    public function getLingueDisponibili(): array
    {
        $lingue = [];
        $lingue[] = $this->_traduzione->lang;

        return array_unique(array_merge($lingue, Traduzione::listaLingue(__DIR__ . "/lang")));
    }

    /**
     * Restituisce la stringa tradotta nella lingua corrente.
     * È il metodo più usato nelle pagine: ogni testo visibile all'utente
     * dovrebbe passare da qui per supportare il multilingua.
     *
     * @param string $sz L'identificatore della stringa da tradurre (chiave nel file lang JSON).
     * @param mixed  ...$parametri Valori opzionali da sostituire nei placeholder della stringa.
     * @return string La stringa tradotta.
     *
     * @example echo $service->traduci("titoloPagina");
     * @example echo $service->traduci("benvenuto", $nomeUtente); // se la stringa ha un placeholder
     */
    public function traduci(string $sz, ...$parametri): string
    {
        return $this->_traduzione->traduci($sz, ...$parametri);
    }

    /**
     * @return string Lingua corrente
     */
    public function currentLang(): string
    {
        return $this->_traduzione->lang;
    }

    /**
     * Prepara e ordina gli asset per il caricamento.
     *
     * @param string $directory Il percorso della directory da esplorare.
     * @param string $extension L'estensione dei file.
     * @param array $firstLoad File da caricare per primi.
     * @param array $lastLoad File da caricare per ultimi.
     * @param array $excludeFiles File da escludere.
     * @return array Array ordinato di percorsi di file da caricare.
     */
    private function prepareAssets(string $directory, string $extension, array $firstLoad = [], array $lastLoad = [], array $excludeFiles = []): array
    {
        $getFileList = function ($directory, $extension, $excludeFiles) {
            $fileList = array();
            $resolved = realpath($directory);
            if ($resolved === false) return $fileList;
            $absolutePath = $resolved . '/';
            foreach (glob($absolutePath . "*." . $extension) as $file) {
                $relativePath = str_replace($absolutePath, '', $file);
                $fileName = basename($relativePath);
                if (!in_array($fileName, $excludeFiles) && !in_array($relativePath, $excludeFiles)) {
                    $fileList[] = $relativePath;
                }
            }
            return $fileList;
        };

        $allFiles = array_merge($firstLoad, $excludeFiles, $lastLoad);
        $additionalFiles = $getFileList($directory, $extension, $allFiles);
        return array_merge($firstLoad, $additionalFiles, $lastLoad);
    }

    /**
     * Restituisce il percorso completo dell'URL per una risorsa nelle API.
     * 
     * @param string $path Percorso della risorsa.
     * @return string URL completo della risorsa.
     */
    public function APIbaseURL(string $path): string
    {
        if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
            return $path;
        } else {
            return rtrim($this->urlAPI, '/') . '/' . $path;
        }
    }

    /**
     * Restituisce il percorso completo dell'URL per una risorsa.
     * 
     * @param string $path Percorso della risorsa.
     * @return string URL completo della risorsa.
     */
    public function baseURL(string $path): string
    {
        if (str_starts_with($path, "http://") || str_starts_with($path, "https://")) {
            return $path;
        } else {
            return rtrim($this->baseUrl, '/') . '/' . $path;
        }
    }

    /**
     * Restituisce la route con la lingua settata
     * 
     * @param string $route route
     * @return string route
     */
    public function createRoute(string $route): string
    {
        // Parsa l'URL corrente e quello richiesto
        $current = parse_url($_SERVER['REQUEST_URI']);
        $completo = $this->baseUrl($route);
        $parsedUrl = parse_url($completo);

        // Closure per determinare la pagina da un path
        $whitchPage = function (string $path): string {
            return empty($path) || str_ends_with($path, '/') ? $path . "index" : $path;
        };

        $RequestPage = $whitchPage($parsedUrl['path']);
        $RenderPage = $whitchPage($current['path']);

        // Se la lingua è quella di default, restituisci l'URL senza parametro lang
        if ($this->settings['lang'] == $this->_traduzione->lang) {
            // Se siamo sulla stessa pagina, restituisci solo il frammento
            if ($RenderPage === $RequestPage) {
                if (isset($parsedUrl['fragment'])) {
                    return '#' . $parsedUrl['fragment'];
                }
            }
            return $completo;
        }

        // Prepara i parametri della query e aggiungi/modifica il parametro della lingua
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        $queryParams['lang'] = $this->_traduzione->lang;
        $queryString = http_build_query($queryParams);
        // Ricostruisci l'URL con la nuova query string
        $newUrl = $parsedUrl['path'] . '?' . $queryString;

        // Aggiungi il frammento, se presente
        if (isset($parsedUrl['fragment'])) {
            $newUrl .= '#' . $parsedUrl['fragment'];
        }

        if (isset($parsedUrl['scheme'])) {
            $newUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $newUrl;
        }

        return $newUrl;
    }

    /**
     * Restituisce il percorso completo dell'URL per un asset.
     * 
     * @param string $ID Identificativo dell'asset.
     * @return string URL completo dell'asset.
     */
    public function UrlAsset(string $ID): string
    {
        return self::baseURL("func/getAsset?ID=" . $ID);
    }

    /**
     * Esegue una chiamata all'endpoint dell'API e restituisce la risposta decodificata.
     * È il metodo principale per recuperare dati dal backend nelle pagine del sito.
     * Gestisce automaticamente JSON, XML e testo semplice in base al Content-Type della risposta.
     * Il parametro 'lang' viene aggiunto automaticamente a ogni chiamata.
     *
     * @param string      $pathOrEndpoint Il percorso dell'endpoint (es. "anagrafica", "social")
     *                                    o URL completo per API esterne.
     * @param string      $metodo         Il metodo HTTP ('GET', 'POST', ecc.). Default 'GET'.
     * @param array       $dati           I dati da inviare nel body (POST) o query string (GET).
     * @param string|null $contentType    Content-Type della richiesta. Se null, viene dedotto dal metodo.
     * @return mixed Risposta decodificata (array per JSON, SimpleXMLElement per XML, string altrimenti).
     * @throws InvalidArgumentException Se $pathOrEndpoint è vuoto.
     * @throws Exception In caso di errore di rete o parsing della risposta.
     *
     * @example $dati = $service->callApiEndpoint("anagrafica");           // GET
     * @example $service->callApiEndpoint("logging", "POST", ["pwd"=>$p]); // POST form
     */
    public function callApiEndpoint(string $pathOrEndpoint, string $metodo = "GET", array $dati = [], ?string $contentType = null): mixed
    {
        // Validazione del parametro $pathOrEndpoint
        if (empty($pathOrEndpoint)) {
            throw new InvalidArgumentException("Il parametro 'pathOrEndpoint' non può essere vuoto.");
        }
        $url = $this->APIbaseURL($pathOrEndpoint);

        if ($contentType === null) {
            $contentType = strtoupper($metodo) === "POST"
                ? 'application/x-www-form-urlencoded'
                : 'application/json';
        }

        $dati['lang'] = $this->_traduzione->lang;

        $risultati = ServerToServer::callURL($url, $metodo, $dati, $contentType, ["X-Api-Key: " . $this->APIKey]);
        $response = $risultati->Response;
        $ResponseContentType = $risultati->ResponseContentType;

        // Elaborazione della risposta in base al suo tipo di contenuto
        $managedContentTypes = ['application/json', 'text/xml', 'application/xml'];
        $processAs = in_array($ResponseContentType, $managedContentTypes) ? $ResponseContentType : $contentType;

        switch ($processAs) {
            // Gestione della risposta JSON
            case 'application/json':
                $oggetto = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Errore nella decodifica JSON: " . json_last_error_msg());
                }
                break;

            // Gestione della risposta XML
            case 'text/xml':
            case 'application/xml':
                libxml_use_internal_errors(true);
                $oggetto = simplexml_load_string($response);
                if ($oggetto === false) {
                    $error = libxml_get_errors();
                    libxml_clear_errors();
                    throw new Exception("Errore nel parsing XML: " . implode(", ", $error));
                }
                break;

            // Gestione di altri tipi di contenuto (testo semplice, HTML, ecc.)
            default:
                $oggetto = $response;
                break;
        }

        // Restituisci l'oggetto decodificato o la risposta grezza
        return $oggetto;
    }

    /**
     * Converte una stringa in entità HTML.
     *
     * @param string $stringa La stringa da convertire.
     * @return string La stringa convertita in entità HTML.
     */
    public function convertiInEntitaHTML(string $stringa): string
    {
        $risultato = '';
        $lunghezza = strlen($stringa);
        for ($i = 0; $i < $lunghezza; $i++) {
            $risultato .= '&#' . ord($stringa[$i]) . ';';
        }
        return $risultato;
    }

    /**
     * Genera un link HTML localizzato con gestione automatica della pagina corrente.
     * Se la route corrisponde alla pagina attiva, restituisce solo il testo (senza <a>),
     * evitando link "a se stessi" (buona pratica accessibilità e SEO).
     * Per URL esterni aggiunge automaticamente target="_blank" e rel="noopener noreferrer".
     * Usare per tutti i link di navigazione interna (menu, breadcrumb, CTA).
     *
     * @param string $keyTranslate Chiave di traduzione per il testo del link.
     * @param string $route        Nome della pagina (es. "index", "social") o URL esterno.
     * @param string $cls          Classi CSS aggiuntive (es. "nav-link", "btn btn-primary").
     * @param bool   $labelStrong  true → testo in <strong> se pagina corrente. Default true.
     * @param string $ariaLabel    aria-label personalizzato. Se vuoto, usa "Link {testo}".
     * @return string Markup HTML del link o del testo (se pagina corrente).
     *
     * @example echo $service->createRouteLinkHTML("home", "index", "nav-link");
     * @example echo $service->createRouteLinkHTML("contatti", "social", "btn btn-primary");
     */
    public function createRouteLinkHTML(string $keyTranslate, string $route, string $cls = "", bool $labelStrong = true, string $ariaLabel = ""): string
    {
        $tagLabel = $labelStrong === true ? "strong" : "a";
        $label = $this->traduci($keyTranslate);
        $class = empty($cls) ? "" : " class='" . htmlspecialchars($cls) . "'";
        $currentFile = pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME);

        if ($currentFile === $route && !str_starts_with($route, '#')) {
            return "<" . $tagLabel . $class . ">" . htmlspecialchars($label) . "</" . $tagLabel . "> ";
        } else {
            $parsedUrl = parse_url($route);
            $target = "";
            if (isset($parsedUrl['scheme'])) {
                $target = ' target="_blank" rel="noopener noreferrer"';
            }

            $aria = ' aria-label="' . htmlspecialchars(!empty($ariaLabel) ? $ariaLabel : 'Link ' . $label) . '"';

            return "<a" . $class . $target . $aria . " href=\"" . htmlspecialchars($this->createRoute($route)) . "\">" . htmlspecialchars($label) . "</a>";
        }
    }

    /**
     * Genera un link con email/telefono offuscati per proteggerli dagli scraper.
     * L'URL viene convertito in entità HTML numeriche e decodificato lato client via JS.
     * Usare per ogni indirizzo email o numero di telefono mostrato in pagina.
     *
     * @param string $url            L'indirizzo da proteggere (email, telefono, URL).
     * @param string $prefisso       Prefisso del protocollo (es: 'mailto:', 'tel:').
     * @param array  $attributiExtra Attributi HTML aggiuntivi (es. ['class' => 'btn']).
     * @return string Markup HTML del link con href="#" e onclick offuscato.
     *
     * @example echo $service->creaLinkCodificato("info@esempio.it", "mailto:");
     * @example echo $service->creaLinkCodificato("+39012345678", "tel:");
     */
    function creaLinkCodificato(string $url, string $prefisso = '', array $attributiExtra = []): string
    {
        $urlCodificato = $this->convertiInEntitaHTML($url);
        $attributi = '';

        // Verifica se aria-label è già presente negli attributi extra
        $ariaLabelPresente = false;
        foreach ($attributiExtra as $chiave => $valore) {
            if (strtolower($chiave) === 'aria-label') {
                $ariaLabelPresente = true;
                break;
            }
        }

        // Se aria-label non è presente, aggiungilo automaticamente
        if (!$ariaLabelPresente) {
            $attributi .= 'aria-label="Link ' . htmlspecialchars($url) . '" ';
        }

        // Aggiungi gli attributi extra forniti
        foreach ($attributiExtra as $chiave => $valore) {
            $attributi .= $chiave . '="' . htmlspecialchars($valore) . '" ';
        }

        $prefissoSafe = htmlspecialchars($prefisso, ENT_QUOTES, 'UTF-8');
        $urlCodificatoSafe = htmlspecialchars($urlCodificato, ENT_QUOTES, 'UTF-8');
        return "<a href=\"#\" onClick=\"openEncodedLink('$prefissoSafe', '$urlCodificatoSafe')\" $attributi>$urlCodificato</a>";
    }

    /**
     * Autentica l'utente e salva il token Bearer in sessione se valido.
     * Non chiamare direttamente dalle pagine: usare la funzione wrapper loggati()
     * definita in funzioni.php, che è il punto di personalizzazione previsto dal template.
     *
     * @param array $_dati Dati per l'autenticazione (tipicamente ['pwd' => $password]).
     * @return array Risultato con chiavi: 'valid' (bool), 'token' (string|null), 'error' (string|null).
     */
    function loggati(array $_dati): array
    {
        $this->avviaSessione();
        $itok = $this->callApiEndpoint("logging", "POST", $_dati, "application/x-www-form-urlencoded");
        if ($itok["valid"] == true) {
            $_SESSION['logged_in'] = true;
            $_SESSION['Bearertoken'] = $itok["token"];
        }

        return $itok;
    }

    /**
     * Restituisce il token di login
     * @return string il token
     */
    public function getToken(): string
    {
        $this->avviaSessione();
        return $_SESSION['Bearertoken'] ?? '';
    }

    /**
     * @return bool è loggato?
     */
    public function isLoggedIn(): bool
    {
        $this->avviaSessione();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['Bearertoken']);
    }

    /**
     * @return bool ha una sessione
     */
    public function hasSessionIn(): bool
    {
        return session_status() === PHP_SESSION_NONE;
    }

    /**
     * Determina se è preferibile il testo scuro basato sulla luminosità del colore di sfondo.
     *
     * @param string $hexColor Il colore di sfondo in formato HEX.
     * @return bool True se il testo scuro è preferibile.
     */
    function isDarkTextPreferred(string $hexColor): bool
    {
        $hex = ltrim($hexColor, '#');
        // Converte HEX in RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        // Calcola la luminosità percepita (formula W3C)
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;
        // Restituisce true per testo scuro se luminosità > 0.5
        return $luminance > 0.5;
    }

    /**
     * Scurisce un colore HEX.
     *
     * @param string $hexColor Il colore originale in formato HEX.
     * @param float $darkenFactor Il fattore di scurimento (0.0 = nero, 1.0 = invariato). Default 0.2.
     * @return string Il colore HEX scurito.
     */
    function darkenColor(string $hexColor, float $darkenFactor = 0.2): string
    {
        $hex = ltrim($hexColor, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) * (1 - $darkenFactor));
        $g = max(0, hexdec(substr($hex, 2, 2)) * (1 - $darkenFactor));
        $b = max(0, hexdec(substr($hex, 4, 2)) * (1 - $darkenFactor));
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Schiarisce un colore HEX.
     *
     * @param string $hexColor Il colore originale in formato HEX.
     * @param float $lightenFactor Il fattore di schiarimento. Default 1.2.
     * @return string Il colore HEX schiarito.
     */
    function lightenColor(string $hexColor, float $lightenFactor = 1.2): string
    {
        $hex = ltrim($hexColor, '#');
        $r = min(255, hexdec(substr($hex, 0, 2)) * $lightenFactor);
        $g = min(255, hexdec(substr($hex, 2, 2)) * $lightenFactor);
        $b = min(255, hexdec(substr($hex, 4, 2)) * $lightenFactor);
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }
}
