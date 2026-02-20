<?php

class RelLink
{
    public string $type;
    public string $url;

    public function __construct(string $type, string $url)
    {
        $this->type = $type;
        $this->url = $url;
    }

    /**
     * Mappa il tipo della risorsa agli attributi necessari per i tag HTML.
     * Centralizza la logica tipo→tag in un unico posto: aggiungere un nuovo
     * tipo (es. 'font') richiede solo aggiornare questo metodo.
     *
     * @return array{tag: string, as: string, attrs: string}|null
     *   'tag'   → nome del tag HTML da usare ('link' o 'script')
     *   'as'    → valore dell'attributo "as" per i preload hint
     *   'attrs' → attributi extra del tag di inclusione (es. 'defer', 'rel="stylesheet"')
     *   null se il tipo non è gestito.
     */
    private function tipoAttrs(): ?array
    {
        // lingua.js e base.js sono sincroni: definiscono traduzioneCaricata e
        // inizializzazioneApp che gli script inline delle pagine usano direttamente.
        // Tutti gli altri script usano defer.
        $coreScripts = ['lingua.js', 'base.js'];
        $isCore = false;
        foreach ($coreScripts as $core) {
            if (str_ends_with($this->url, $core)) {
                $isCore = true;
                break;
            }
        }

        return match ($this->type) {
            'css' => ['tag' => 'link', 'as' => 'style', 'attrs' => 'rel="stylesheet"'],
            'js' => ['tag' => 'script', 'as' => 'script', 'attrs' => $isCore ? '' : 'defer'],
            default => null,
        };
    }

    /**
     * Genera il tag HTML per includere la risorsa (<script> o <link rel="stylesheet">).
     *
     * lingua.js e base.js sono sincroni (no defer): definiscono traduzioneCaricata e
     * inizializzazioneApp che gli script inline delle pagine usano direttamente.
     * Tutti gli altri script JS usano defer per non bloccare il rendering.
     */
    public function visualizza(): string
    {
        $attrs = $this->tipoAttrs();
        if ($attrs === null)
            return "";

        if ($attrs['tag'] === 'link') {
            return "\t<link {$attrs['attrs']} href=\"{$this->url}\">\n";
        } else {
            return "\t<script src=\"{$this->url}\" {$attrs['attrs']}></script>\n";
        }
    }

    /**
     * Genera un tag <link rel="preload"> per le risorse CDN esterne.
     *
     * I preload hints permettono al browser di iniziare a scaricare le risorse
     * CDN in parallelo appena incontra il tag nel <head>, SENZA modificare
     * l'ordine di esecuzione degli script. Questo migliora il First Contentful Paint
     * perché il browser non deve aspettare di incontrare il <script> per iniziare il download.
     *
     * Si applica solo a risorse esterne (CDN) perché le risorse locali
     * sono già servite dallo stesso server con latenza minima.
     *
     * Nota: NON si usa l'attributo crossorigin perché i tag <script> e
     * <link rel="stylesheet"> effettivi non lo hanno. Il credentials mode
     * del preload deve corrispondere a quello del tag che consuma la risorsa,
     * altrimenti il browser ignora il preload e riscarica da zero.
     *
     * @return string Tag <link rel="preload"> o stringa vuota se la risorsa è locale.
     */
    public function preloadHint(): string
    {
        $attrs = $this->tipoAttrs();
        if ($attrs === null)
            return '';

        // Non basta controllare http:// perché anche le risorse locali hanno URL assoluti 
        // Si confronta l'host dell'URL con HTTP_HOST per distinguere i CDN.
        $parsedHost = parse_url($this->url, PHP_URL_HOST);
        $serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        if ($parsedHost === null || strcasecmp($parsedHost, $serverHost) === 0) {
            return '';
        }

        return "\t<link rel=\"preload\" href=\"{$this->url}\" as=\"{$attrs['as']}\">\n";
    }
}

class MetaDTO
{
    public bool $MobileFriendly = true;
    public bool $FullScreenWebApp = true;
    public int $mobileOptimizationWidth = 320;
    private string $dataScadenza = '';
    public ?string $dataScadenzaGMT = null;
    /** @var RelLink[] */
    public array $linkRel = [];
    public string $title = '';
    public string $description = '';
    public ?string $author = null;

    public function __construct($data = null)
    {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }

            if (isset($this->dataScadenza) && $this->dataScadenza !== '') {
                $dateScadenza = DateTime::createFromFormat('d/m/Y', $this->dataScadenza, new DateTimeZone('Europe/Rome'));
                $this->dataScadenzaGMT = $dateScadenza ? $dateScadenza->format('D, d M Y H:i:s') . ' GMT' : null;
            }
        }
    }
}

class VoceInformazione
{
    public $chiave;
    public $traduzioneKey;
    public $callback;

    public function __construct($chiave, $traduzioneKey, $callback)
    {
        $this->chiave = $chiave;
        $this->traduzioneKey = $traduzioneKey;
        $this->callback = $callback;
    }

    public function visualizza($dati, $service)
    {
        if (isset($dati[$this->chiave]) && !empty($dati[$this->chiave])) {
            $valore = htmlspecialchars($dati[$this->chiave], ENT_QUOTES, 'UTF-8'); // Sanitize output
            $testo = $this->traduzioneKey ? htmlspecialchars($service->traduci($this->traduzioneKey), ENT_QUOTES, 'UTF-8') . ": " : "";
            $testo .= is_callable($this->callback) ? call_user_func($this->callback, $valore) : $valore;
            return "" . $testo . "";
        }
        return null;
    }


    public static function verificaPresenzaDati($arrayVoceInformazione, $dati): bool
    {
        if (isset($dati))
            foreach ($arrayVoceInformazione as $voce) {
                if (isset($dati[$voce->chiave]) && !empty($dati[$voce->chiave])) {
                    return true;
                }
            }
        return false;
    }

    /**
     * Funzione per rendere un array di oggetti VoceInformazione.
     *
     * @param array $informazioni Array di oggetti VoceInformazione.
     * @param mixed $dati Informazioni della risorsa/logica specifica da passare a visualizza.
     * @param mixed $service Servizio/utilità per operazioni come la creazione di link.
     */
    public static function renderInfos($informazioni, $dati, $service, $forceFluid = false)
    {
        if (!self::verificaPresenzaDati($informazioni, $dati))
            return "";
        ob_start();
        ?>
        <div class="col-12 col-sm<?= $forceFluid === true ? "" : "-6" ?> pt-1">
            <ul class="list-unstyled">
                <?php foreach ($informazioni as $voce): ?>
                    <?php $output = $voce->visualizza($dati, $service); ?>
                    <?php if ($output !== null): ?>
                        <li>
                            <?= $output ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        $html = ob_get_clean();
        return $html;
    }


}



