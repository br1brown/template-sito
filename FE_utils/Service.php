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
        $data["description"] = "";

        if (isset($this->settings["description"])) {
            if (is_array($this->settings["description"]) && isset($this->settings["description"][$this->currentLang()])) {
                // Sostituisce il valore con la traduzione trovata
                $data["description"] = $this->settings["description"][$this->currentLang()];
            }
        }
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

        return $data;
    }

    /**
     * Restituisce le impostazioni dei metatag e header
     *
     * @return MetaDTO Impostazioni filtrate.
     */
    public function getMeta(): MetaDTO
    {
        $meta = $this->settings['meta'];
        $metaDTO = new MetaDTO($meta);

        // Impostazione dei link esterni
        $metaDTO->linkRel = [
            //ROBE PER IL MENU + SOCIAL
            new RelLink('css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'),
            new RelLink('css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'),
            //jquery vari
            new RelLink('js', 'https://code.jquery.com/jquery-3.5.1.js'),
            new RelLink('js', 'https://code.jquery.com/ui/1.12.1/jquery-ui.js'),
            //bootstrap
            new RelLink('js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js'),
            new RelLink('css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'),
            //GLI ALERT
            new RelLink('js', 'https://cdn.jsdelivr.net/npm/sweetalert2@10'),
            //include a polyfill for ES6 Promises for IE11
            new RelLink('js', 'https://cdn.jsdelivr.net/npm/promise-polyfill'),
        ];

        $havesmoke = isset($this->settings['smoke']) && $this->settings['smoke']["enable"];

        $excludeJs = $havesmoke ? [] : ["jquery_bloodforge_smoke_effect.js"];

        // Preparazione e impostazione dei CSS e JS locali

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
     * @var Traduzione Correnete della pagina
     */
    public Traduzione $_traduzione;

    /**
     * @var string URL dellendpoint con le traduzioni
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
     * @var bool è loggato?
     */
    public bool $isLoggedIn;



    /**
     * @var string Chiave dell'API di servizio
     */
    public string $APIkey;

    /**
     * @var string URL dell'Host
     */
    public string $baseUrl;

    /**
     * Costruttore della classe Service.
     * Legge le impostazioni dal file JSON e inizializza l'URL dell'API.
     */
    public function __construct()
    {
        session_start();

        // Controlla se l'utente è loggato
        $this->isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

        // Controllo prima la variabile $_SERVER['HTTPS']
        $protocol = 'http'; // Impostazione predefinita a 'http'
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $protocol = 'https'; // Se 'HTTPS' è attivo, usa 'https'
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $protocol = 'https'; // Se 'HTTP_X_FORWARDED_PROTO' è 'https', usa 'https'
        }

        // Costruzione dell'URL
        $this->baseUrl = $protocol . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/";

        $this->settings = json_decode(file_get_contents('websettings.json'), true);

        $this->caricaLingua();

        $this->APIkey = $this->settings['API']['key'];

        $APIEndPoint = $this->settings['API']['EndPoint'];
        $this->EsternaAPI = strpos($APIEndPoint, "http://") === 0 || strpos($APIEndPoint, "https://") === 0;
        if ($this->EsternaAPI) {
            $this->urlAPI = $APIEndPoint;
        } else {
            $this->urlAPI = $this->baseUrl . $APIEndPoint;
        }
    }

    /**
     * Carica le traduzioni per la lingua impostata se il file esiste.
     */
    private function caricaLingua()
    {
        $lang = strtolower($this->settings['lang']);
        if (isset($_GET["lang"]) && !empty($_GET["lang"]))
            $lang = strtolower($_GET["lang"]);

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
     * @param string $sz L'identificatore della stringa da tradurre
     * @return string La stringa tradotta
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
     * Prepara e ordina gli asset (CSS o JS) per il caricamento, basandosi su file specifici da caricare per primi e per ultimi,
     * e includendo file addizionali dalla directory specificata, escludendo quelli non necessari.
     *
     * @param string $directory Il percorso della directory da esplorare per file addizionali.
     * @param string $extension L'estensione dei file (ad esempio, 'css' o 'js').
     * @param array $firstLoad Array di file da caricare per primi.
     * @param array $lastLoad Array di file da caricare per ultimi.
     * @param array $excludeFiles Array di file da escludere dall'elenco addizionale.
     * @return array Array ordinato di percorsi di file da caricare.
     */
    private function prepareAssets(string $directory, string $extension, array $firstLoad = [], array $lastLoad = [], array $excludeFiles = []): array
    {
        // Ottiene un elenco di file dalla directory specificata, escludendo i file non necessari
        $getFileList = function ($directory, $extension, $excludeFiles) {
            $fileList = array();
            $absolutePath = realpath($directory) . '/';
            foreach (glob($absolutePath . "*." . $extension) as $file) {
                $relativePath = str_replace($absolutePath, '', $file);
                $fileName = basename($relativePath);
                if (!in_array($fileName, $excludeFiles) && !in_array($relativePath, $excludeFiles)) {
                    $fileList[] = $relativePath;
                }
            }
            return $fileList;
        };

        // Combina i file da caricare per primi, file addizionali e file da caricare per ultimi
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
        if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) {
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
        if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0) {
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
        $current = parse_url($_SERVER['REQUEST_URI']);
        $completo = $this->baseUrl($route);
        // Parsa l'URL e decomponilo nei suoi componenti
        $parsedUrl = parse_url($completo);

        // Definizione della closure per "whitchPage"
        $whitchPage = function (string $path): string {
            return empty($path) || str_ends_with($path, '/') ? $path . "index" : $path;
        };

        $RequestPage = $whitchPage($parsedUrl['path']);
        $RenderPage = $whitchPage($current['path']);

        // Verifica se la lingua è quella di default
        if ($this->settings['lang'] == $this->_traduzione->lang) {
            // Se l'URL da generare è lo stesso della pagina corrente, restituisci solo il frammento
            if ($RenderPage === $RequestPage) {
                if (isset($parsedUrl['fragment'])) {
                    return '#' . $parsedUrl['fragment'];
                }
            }
            return $completo;
        }

        // Prepara l'array dei parametri della query
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }

        // Aggiungi o modifica il parametro della lingua
        $queryParams['lang'] = $this->_traduzione->lang;

        // Ricostruisci la query string
        $queryString = http_build_query($queryParams);

        // Ricostruisci l'URL
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
     * Restituisce il percorso completo dell'URL per una risorsa.
     * 
     * @param string $path Percorso della risorsa.
     * @return string URL completo della risorsa.
     */
    public function UrlAsset(string $ID): string
    {
        return self::baseURL("func/getAsset?ID=" . $ID);
    }

    /**
     * Esegue una chiamata all'endpoint dell'API utilizzando il metodo HTTP specificato e restituisce la risposta.
     * 
     * @param string $pathOrEndpoint Il percorso dell'endpoint o interno dell'API.
     * @param string $metodo Il metodo HTTP da utilizzare per la chiamata (ad es. 'GET', 'POST', 'PUT', 'DELETE', 'PATCH'). Di default è 'GET'.
     * @param array $dati I dati da inviare con la richiesta, utili per i metodi come 'POST', 'PUT'.
     * @param string $contentType Il Content Type della richiesta.
     * @return array Risposta dell'API decodificata in formato array.
     * @throws InvalidArgumentException Se i parametri obbligatori non sono validi.
     * @throws Exception In caso di errore nella chiamata all'endpoint o nella risposta dell'API.
     */
    public function callApiEndpoint(string $pathOrEndpoint, string $metodo = "GET", array $dati = [], ?string $contentType = null)
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

        $risultati = ServerToServer::callURL($url, $metodo, $dati, $contentType, ["X-Api-Key: " . $this->APIkey]);
        $response = $risultati->Response;
        $ResponseContentType = $risultati->ResponseContentType;

        // Elaborazione della risposta in base al suo tipo di contenuto
        $managedContentTypes = ['application/json', 'text/xml', 'application/xml'];
        $processAs = in_array($ResponseContentType, $managedContentTypes) ? $ResponseContentType : $contentType;

        switch ($processAs) {
            case 'application/json':
                // Gestione della risposta JSON
                $oggetto = json_decode($response, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Errore nella decodifica JSON: " . json_last_error_msg());
                }
                break;

            case 'text/xml':
            case 'application/xml':
                // Gestione della risposta XML
                libxml_use_internal_errors(true);
                $oggetto = simplexml_load_string($response);
                if ($oggetto === false) {
                    $error = libxml_get_errors();
                    libxml_clear_errors();
                    throw new Exception("Errore nel parsing XML: " . implode(", ", $error));
                }
                break;

            default:
                // Gestione di altri tipi di contenuto (come testo semplice o HTML)
                $oggetto = $response;
                break;
        }
        // Restituisci l'oggetto decodificato o la risposta grezza
        return $oggetto;

    }


    /**
     * Converte una stringa in entità HTML.
     *
     * Questa funzione prende una stringa come input e la converte
     * in entità HTML, utilizzando il valore ASCII di ogni carattere
     * della stringa per formare l'entità. Questo è utile per
     * visualizzare i caratteri speciali in HTML.
     *
     * @param string $stringa La stringa da convertire in entità HTML.
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
     * Crea un link HTML con opzioni di personalizzazione.
     *
     * @param string $keyTranslate La chiave per la traduzione del testo del link.
     * @param string $route Il percorso o URL del link.
     * @param string $cls (Opzionale) La classe CSS da aggiungere al link.
     * @param bool $labelStrong (Opzionale) Se true, usa il tag <strong> per il testo del link; altrimenti usa <a>.
     * @param string $ariaLabel (Opzionale) L'attributo aria-label per il link per migliorare l'accessibilità.
     * @return string Il codice HTML del link.
     */
    public function CreateRouteLinkHTML(string $keyTranslate, string $route, string $cls = "", bool $labelStrong = true, string $ariaLabel = ""): string
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
                $target = ' target="_blank"';
            }

            // Aggiungi attributi ARIA se forniti
            $aria = ' aria-label="' . htmlspecialchars(!empty($ariaLabel) ? $ariaLabel : 'Link ' . $label) . '"';

            return "<a" . $class . $target . $aria . " href=\"" . htmlspecialchars($this->createRoute($route)) . "\">" . htmlspecialchars($label) . "</a>";
        }
    }

    /**
     * Crea un link HTML con l'URL codificato e attributi personalizzabili.
     *
     * Questa funzione genera un link HTML che, quando cliccato, attiva una funzione JavaScript
     * 'openEncodedLink' con un prefisso e un URL codificato come parametri.
     * Gli attributi aggiuntivi come class, target, title, rel, id, style, data-* e aria-*
     * possono essere inclusi per personalizzare ulteriormente il link.
     *
     * @param string $url L'URL da includere nel link.
     * @param string $prefisso Il prefisso da utilizzare (es: 'mailto:', 'tel:'). Se non specificato, non viene usato alcun prefisso.
     * @param array $attributiExtra Un array associativo di attributi HTML aggiuntivi e i loro valori. Esempio: ['class' => 'my-class', 'id' => 'my-id'].
     * @return string Il codice HTML del link generato.
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

        // Se aria-label non è presente, aggiungilo
        if (!$ariaLabelPresente) {
            $attributi .= 'aria-label="Link ' . htmlspecialchars($url) . '" ';
        }

        foreach ($attributiExtra as $chiave => $valore) {
            $attributi .= $chiave . '="' . htmlspecialchars($valore) . '" ';
        }

        return "<a href=\"#\" onClick=\"openEncodedLink('$prefisso', '$urlCodificato')\" $attributi>$urlCodificato</a>";
    }

    /**
     * Verifica le credenziali dell'utente e gestisce l'autenticazione.
     *
     * Questa funzione viene utilizzata per autenticare un utente attraverso 
     * Se la password è valida, imposta lo stato di autenticazione nella 
     * sessione e salva il token Bearer. In caso contrario, restituisce un 
     * errore per gestire una risposta adeguata.
     *
     * @param string $password La password inviata tramite il form di login.
     * 
     * @return array Ritorna un array con i seguenti dati:
     *               - valid (bool): Indica se l'autenticazione ha avuto successo.
     *               - token (string|null): Il token Bearer se l'autenticazione è valida.
     *               - error (string|null): Il messaggio di errore in caso di autenticazione fallita.
     */
    function loggati($password): array
    {
        $itok = $this->callApiEndpoint("logging", "POST", ["pwd" => $password], "application/x-www-form-urlencoded");
        if ($itok["valid"] == true) {
            $_SESSION['logged_in'] = true;
            $_SESSION['Bearertoken'] = $itok["token"];
        }

        return $itok;
    }


    /**
     * Determina se è preferibile il testo di colore scuro o chiaro basato sulla luminosità del colore di sfondo.
     *
     * Questa funzione calcola la luminosità di un colore dato in formato HEX e restituisce un valore booleano.
     * Restituisce 'true' se un testo scuro (nero) è preferibile per garantire una buona leggibilità sullo sfondo,
     * altrimenti 'false' per un testo chiaro (bianco). Utilizza una formula di luminosità relativa che
     * tiene conto della diversa sensibilità dell'occhio umano ai colori rosso, verde e blu.
     *
     * @param string $hexColor Il colore di sfondo in formato HEX, come una stringa (es. '#ffcc00').
     * @return bool Restituisce 'true' se il testo scuro è preferibile, altrimenti 'false'.
     */
    function isDarkTextPreferred(string $hexColor): bool
    {
        // Rimuove il carattere # se presente
        $hex = ltrim($hexColor, '#');

        // Converte HEX in RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Calcola la luminosità
        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        // Restituisce true per il testo scuro se la luminosità è superiore a 0.5, altrimenti false per il testo chiaro
        return $luminance > 0.5;
    }

    /**
     * Scurisce un colore HEX dato di un fattore specificato.
     *
     * Questa funzione converte il colore HEX in formato RGB, applica un fattore di scurimento ai valori RGB,
     * e poi converte i valori RGB scuriti di nuovo in formato HEX. È utile per creare varianti di colore più scure.
     *
     * @param string $hexColor Il colore originale in formato HEX (es. '#ffcc00').
     * @param float $darkenFactor Il fattore di scurimento, dove 1.0 lascia il colore invariato e 0.0 lo rende nero. Default a 0.2.
     * @return string Il colore HEX scurito.
     */
    function darkenColor(string $hexColor, float $darkenFactor = 0.2): string
    {
        // Converti HEX in RGB
        $hex = ltrim($hexColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Applica il fattore di scurimento
        $r = max(0, $r - $r * $darkenFactor);
        $g = max(0, $g - $g * $darkenFactor);
        $b = max(0, $b - $b * $darkenFactor);

        // Converti di nuovo in HEX e restituisci
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

    /**
     * Schiarisce un colore HEX dato di un fattore specificato.
     *
     * Questa funzione converte il colore HEX in formato RGB, applica un fattore di schiarimento ai valori RGB,
     * e poi converte i valori RGB schiariti di nuovo in formato HEX. È utile per creare varianti di colore più chiare.
     *
     * @param string $hexColor Il colore originale in formato HEX (es. '#ffcc00').
     * @param float $lightenFactor Il fattore di schiarimento, dove 1.0 lascia il colore invariato e 2.0 lo rende il più chiaro possibile. Default a 1.2.
     * @return string Il colore HEX schiarito.
     */
    function lightenColor(string $hexColor, float $lightenFactor = 1.2): string
    {
        // Converti HEX in RGB
        $hex = ltrim($hexColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Applica il fattore di schiarimento
        $r = min(255, $r * $lightenFactor);
        $g = min(255, $g * $lightenFactor);
        $b = min(255, $b * $lightenFactor);

        // Converti di nuovo in HEX e restituisci
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    }

}
