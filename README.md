# Template Sito Web

Template PHP per siti web con backend API integrato, sistema di traduzioni multilingua, gestione asset, autenticazione Bearer e supporto PWA.

- **Demo:** [occhioalmondo.altervista.org/template-sito](https://occhioalmondo.altervista.org/template-sito/)
- **PHP richiesto:** 8.1+

---

## Indice

1. [Struttura delle cartelle](#struttura-delle-cartelle)
2. [Configurazione iniziale](#configurazione-iniziale)
3. [Frontend â€” come creare una pagina](#frontend--come-creare-una-pagina)
4. [Features del template](#features-del-template)
5. [Sistema di traduzioni](#sistema-di-traduzioni)
6. [Asset](#asset)
7. [Backend API â€” come creare un endpoint](#backend-api--come-creare-un-endpoint)
8. [Autenticazione](#autenticazione)
9. [Effetto fumo](#effetto-fumo)
10. [Policy](#policy)
11. [Creare un nuovo progetto dal template](#creare-un-nuovo-progetto-dal-template)
---

## Struttura delle cartelle

```
template-sito/
├── websettings.json          # Configurazione globale del sito
├── index.php                 # Homepage
├── social.php                # Pagina di esempio
├── error.php                 # Pagina di errore generica
├── privacypolicy.php         # Privacy policy
├── cookiepolicy.php          # Cookie policy
├── webmanifest.php           # Web App Manifest (PWA)
├── robots.txt
│
├── FE_utils/                 # Utilities frontend (PHP lato server)
│   ├── Service.php           # Service layer: unico punto di accesso alle API e alle impostazioni
│   ├── TopPage.php           # Include: apre <html>, <head>, <body>, <main>
│   ├── BottomPage.php        # Include: chiude <main>, aggiunge footer, chiude </body></html>
│   ├── DTOWebsite.php        # DTO: RelLink, MetaDTO, VoceInformazione
│   ├── Traduzione.php        # Gestione traduzioni
│   ├── ServerToServer.php    # Chiamate HTTP server-to-server (cURL)
│   ├── Asset.php             # Risoluzione asset (immagini, favicon, ecc.)
│   ├── funzioni.php          # Funzioni personalizzabili (es. loggati())
│   ├── headeraddon.php       # CSS/JS aggiuntivi caricati nell'<head>
│   ├── _policy.php           # Layout comune per privacy/cookie policy
│   └── lang/                 # File JSON delle traduzioni
│       ├── it.json
│       └── en.json
│
├── func/                     # Funzioni PHP esposte al frontend via route
│   ├── gateway.php           # Proxy per API esterne (evita CORS dal browser)
│   ├── getLang.php           # Endpoint per le traduzioni JS
│   ├── getAsset.php          # Endpoint per gli asset
│   └── markparsing.php       # Conversione Markdown → HTML
│
├── API/                      # Backend API
│   ├── anagrafica.php        # Endpoint dati del sito (nome, contatti, ecc.)
│   ├── social.php            # Endpoint social
│   ├── logging.php           # Endpoint per il login (genera token Bearer)
│   └── BLL/                  # Business Logic Layer
│       ├── auth_and_cors_middleware.php  # Autenticazione API key + Bearer + CORS
│       ├── gestione_metodi.php           # Dispatcher HTTP (GET/POST/PUT/DELETE)
│       ├── Repository.php               # Accesso ai dati (file system)
│       ├── Response.php                 # Standardizza le risposte JSON
│       ├── funzioni_comuni.php          # Funzioni condivise tra gli endpoint
│       ├── APIException.php             # Eccezione custom con HTTP status code
│       └── auth_settings/
│           ├── APIKeys.txt              # API key autorizzate (una per riga)
│           ├── pwd.txt                  # Password per generare token Bearer
│           └── CORSconfig.json         # Configurazione CORS
│
├── script/                   # JavaScript frontend
│   ├── lingua.js             # Caricamento traduzioni, funzione traduci()
│   ├── base.js               # Definisce inizializzazioneApp, utility UI (scroll, clipboard, ecc.)
│   ├── manageAPI.js          # Funzioni per le chiamate API dal frontend
│   ├── manageMenu.js         # Gestione navbar e scroll
│   └── addon.js              # Punto di estensione JS (aggiunto per ultimo)
│
├── style/                    # CSS
│   ├── base.css              # Stili base (aggiunto per primo)
│   ├── social.css            # Stili per la pagina social
│   └── addon.css             # Punto di estensione CSS (aggiunto per ultimo)
│
└── asset/                    # Immagini e risorse statiche
    └── (immagini, favicon, ecc.)
```

---

## Configurazione iniziale

Tutto parte da **`websettings.json`**:

```json
{
    "AppName": "NomeSito",
    "lang": "it",
    "description": {
        "it": "Descrizione in italiano",
        "en": "Description in English"
    },
    "API": {
        "EndPoint": "API",
        "key": "frontend"
    },
    "colorTema": "#212529",
    "itemsMenu": [
        { "nome": "Social", "route": "social" }
    ],
    "footer": true,
    "url": {
        "privacypolicy": "privacypolicy",
        "cookiepolicy": "cookiepolicy"
    },
    "smoke": {
        "enable": false,
        "color": "#7099ff",
        "opacity": 0.4,
        "maximumVelocity": 100,
        "particleRadius": 250,
        "density": 5
    }
}
```

| Chiave | Descrizione |
|---|---|
| `AppName` | Nome del sito, usato nel `<title>`, navbar e manifest |
| `lang` | Lingua di default (codice ISO 639-1, es. `"it"`) |
| `description` | Descrizione multilingua per il meta description |
| `API.EndPoint` | Percorso relativo (es. `"API"`) o URL assoluto esterno per le API |
| `API.key` | Valore dell'header `X-Api-Key` usato dal frontend per autenticarsi con l'API |
| `colorTema` | Colore primario HEX — guida navbar, colori link, theme-color PWA |
| `colorBase` | (opzionale) Colore base; se omesso viene calcolato automaticamente da `colorTema` |
| `itemsMenu` | Voci della navbar. Ogni voce ha `nome` (chiave di traduzione) e `route` (nome file PHP senza estensione) |
| `footer` | `true` mostra il footer in BottomPage |
| `url` | Percorsi delle pagine policy (usati nei link del footer) |
| `smoke` | Effetto fumo decorativo (vedi sezione dedicata) |
| `meta` | (opzionale) Oggetto con campi: `author` (stringa, popola `<meta name="author">`), `FullScreenWebApp` (bool, default `true` — abilita la modalità fullscreen PWA), `dataScadenza` (stringa `"dd/mm/yyyy"` — imposta l'header HTTP `Expires`) |
| `ExternalLink` | (opzionale) Array di `{"type": "css"|"js", "value": "url"}` per includere risorse CDN extra |

**API esterna:** se `API.EndPoint` inizia con `http://` o `https://`, viene considerata esterna. Il frontend passa tutte le chiamate attraverso `func/gateway.php` (proxy SSRF-safe) anziché chiamare l'API direttamente.

---

## Frontend — come creare una pagina

Ogni pagina segue questo schema:

```php
<?php
// Variabili opzionali da dichiarare PRIMA dell'include TopPage:
$title = "chiaveTraduzione";          // Chiave per il <title> (tradotta)
$singledescription = "chiaveDesc";    // Chiave per il meta description
$requiresAuth = false;                // true → avvia sessione, rende disponibile $isLoggedIn
$forceMenu = true;                    // false → nasconde la navbar

include('FE_utils/TopPage.php');
// Dopo TopPage sono disponibili:
// $service    → istanza di Service
// $irl        → dati dall'endpoint /anagrafica (array vuoto se API offline)
// $isLoggedIn → bool (solo se $requiresAuth = true)
// $colori     → array dei colori calcolati da colorTema
// $clsTxt     → "text-dark" o "text-light" in base alla luminosità del tema
// $isDarkTextPreferred → bool
// $itemsMenu, $AppName, $havesmoke, $smoke, $routes, $footer, ecc.
?>

<div class="container">
    <h1><?= $service->traduci("titoloHome") ?></h1>

    <?php
    // Chiamata API server-to-server
    $dati = $service->callApiEndpoint("social");
    foreach ($dati as $item) {
        echo "<p>" . htmlspecialchars($item['nome']) . "</p>";
    }
    ?>
</div>

<script>
inizializzazioneApp.then(() => {
    // codice JS che richiede traduzioni/jQuery
});
</script>

<?php include('FE_utils/BottomPage.php'); ?>
```

### Metodi principali di `$service`

| Metodo | Descrizione |
|---|---|
| `traduci(string $key, ...$params)` | Restituisce la stringa tradotta nella lingua corrente |
| `currentLang()` | Lingua corrente (es. `"it"`) |
| `callApiEndpoint($path, $metodo, $dati, $contentType)` | Chiama un endpoint API server-to-server, gestisce JSON/XML |
| `createRouteLinkHTML($key, $route, $cls)` | Genera un `<a>` localizzato; se la route è la pagina corrente, restituisce il testo senza link |
| `createRoute($route)` | URL assoluto di una route, con il parametro `?lang=` se necessario |
| `baseURL($path)` | URL assoluto per risorse del sito (es. `baseURL("style/base.css")`) |
| `APIbaseURL($path)` | URL assoluto per un endpoint API |
| `UrlAsset($ID)` | URL per un asset registrato (es. `UrlAsset("favIcon")`) |
| `creaLinkCodificato($url, $prefisso)` | Link email/tel offuscato contro scraper (es. `creaLinkCodificato("info@esempio.it", "mailto:")`) |
| `isLoggedIn()` | `true` se l'utente ha una sessione valida con token Bearer |
| `getToken()` | Restituisce il token Bearer dalla sessione |
| `loggati(array $dati)` | Esegue il login chiamando l'API `/logging` e salva il token in sessione |

### Script di pagina inline

`lingua.js` e `base.js` sono caricati **senza `defer`** e definiscono `traduzioneCaricata` e `inizializzazioneApp` in modo sincrono. Tutti gli altri script (jQuery, Bootstrap, SweetAlert, ecc.) usano `defer`.

`inizializzazioneApp` è una Promise che si risolve quando le traduzioni sono caricate **e** tutti gli script `defer` sono eseguiti (`window load`). Per eseguire codice che usa traduzioni, jQuery o le API:

```javascript
inizializzazioneApp.then(() => {
    // qui sono disponibili: traduci(), $(), infoContesto, apiCall(), ecc.
});
```

Lo script inline **deve stare prima** dell'include di `BottomPage.php`:

```php
<script>
    inizializzazioneApp.then(() => {
        // codice pagina
    });
</script>

<?php include('FE_utils/BottomPage.php'); ?>
```

---

## Features del template

Queste sono alcune feature principali offerte dal template, mostrate anche con esempi pratici nella home (`index.php`).

### 1) Markdown to HTML

- Campo input: `#markdown_input`
- Anteprima renderizzata: `#markdown_output`
- HTML finale: `#markdown_html`

Come funziona:

1. Scrivi Markdown nel textarea.
2. Dopo ~300ms di pausa viene chiamato `func/markparsing.php` (route `infoContesto.route.markparsing`).
3. L'HTML restituito viene mostrato in anteprima e copiato nell'output HTML.
4. Puoi copiare sia il testo preview (`#copy_markdown_preview`) sia l'HTML (`#copy_markdown_html`).

### 2) Generazione di immagini dinamiche

- Builder JS: `script/imgBuilder.js` (classe `CreaImmagine`)
- Controlli UI: testo, font, dimensione, colore testo, sfondo, larghezza
- Preview: `#img_generica`

Come usarlo:

1. Modifica i campi del pannello "Image Canvas Builder".
2. L'immagine viene rigenerata in tempo reale su canvas e mostrata nella preview.
3. Usa `Salva` (`#download_image`) per scaricare PNG.
4. Usa `Condividi` (`#share_image`) per inviare l'immagine tramite Web Share API (se disponibile).

### 3) Menu di contesto (click destro desktop)

- Target demo: `#menu_context_target`
- Setup: `applicaMenu('#menu_context_target', false, [...])`
- Utility: `script/manageMenu.js`

Con `false` come secondo parametro, il menu si apre su evento `contextmenu` (tasto destro da desktop). Le azioni demo incluse sono: condividi, torna in alto, salva JSON tecnico, copia URL e info.

### 4) Strumento di condivisione mobile

La home usa `navigator.share` in due punti:

- nel menu contestuale (condivisione di titolo/testo/url pagina),
- nel builder immagine (`imageCreata.condividiImmagine("")`) per condividere il file PNG generato.

Fallback automatico: se Web Share API non e supportata, il codice copia l'URL negli appunti e mostra un feedback utente.

---

## Sistema di traduzioni

Le traduzioni sono file JSON in `FE_utils/lang/`:

```json
// FE_utils/lang/it.json
{
    "titoloHome": "Benvenuto",
    "social": "Social",
    "ultimaRevisione": "Ultima revisione"
}
```

- Il file per la lingua di default (`websettings.json → lang`) **deve** esistere.
- I file con nome che inizia con `_` vengono ignorati.
- Per aggiungere una lingua, creare `FE_utils/lang/en.json` con le stesse chiavi.
- Il cambio lingua avviene via `?lang=en` in query string oppure tramite il dropdown in navbar (compare automaticamente se ci sono più lingue disponibili).

**PHP:**
```php
echo $service->traduci("titoloHome");
```

**JavaScript** (dopo `inizializzazioneApp`):
```javascript
traduci("titoloHome"); // restituisce la stringa tradotta
```

---

## Asset

Gli asset si trovano nella cartella `asset/`. Vengono esposti tramite `func/getAsset.php?ID=nomeAsset`.

La mappatura ID → nome file avviene in **`asset/mapping.json`**:

```json
{
    "favIcon": "favicon.png",
    "miaImmagine": "foto.jpg"
}
```

Per aggiungere un asset: copiare il file in `asset/` e aggiungere la voce nel JSON.

```php
// Usare in pagina:
echo $service->UrlAsset("miaImmagine");
// → "https://example.com/func/getAsset?ID=miaImmagine"
```

La favicon (`favIcon`) è usata automaticamente da TopPage.php.

---

## Backend API — come creare un endpoint

Ogni endpoint segue questo schema minimo:

```php
<?php
// API/mioEndpoint.php

include __DIR__ . '/BLL/auth_and_cors_middleware.php';

function eseguiGET(): void
{
    $data = ["chiave" => "valore"];
    echo BLL\Response::retOK($data);
}

// Opzionale — richiede token Bearer valido:
function eseguiPOST(): void
{
    requiresToken();
    $dati = datiInput();
    // logica...
    echo BLL\Response::retOK(["salvato" => true]);
}

include __DIR__ . '/BLL/gestione_metodi.php';
```

Il file `gestione_metodi.php` legge `$_SERVER['REQUEST_METHOD']` e chiama la funzione corrispondente (`eseguiGET`, `eseguiPOST`, `eseguiPUT`, `eseguiDELETE`, `eseguiPATCH`). Se la funzione non esiste, risponde 405.

### Risposta standardizzata

```php
BLL\Response::retOK($data);          // {"status":"ok", "data": ...}
BLL\Response::retError("msg", true); // {"status":"error", "message":"msg"} + http_response_code
```

### Repository (accesso ai dati)

```php
// Leggi un file relativo alla cartella API/data/
$contenuto = BLL\Repository::getFileContent(BLL\Repository::findAPIPath() . "data/miofile.json");

// Scrivi
BLL\Repository::saveFileContent($path, $contenuto);
```

### Eccezioni con HTTP status

```php
throw new BLL\APIException("Risorsa non trovata", 404);
// gestione_metodi.php intercetta le APIException e imposta il codice HTTP corretto
```

---

## Autenticazione

### Flusso

1. Il frontend chiama `POST /API/logging` con `pwd=<password>`.
2. L'API verifica la password (letta da `API/BLL/auth_settings/pwd.txt`) e restituisce un token AES-256-CBC con IV random, codificato in base64.
3. Il token viene salvato in sessione PHP (lato server, cookie `HttpOnly`).
4. Le chiamate API privilegiate inviano il token nell'header `Bearertoken`.
5. Il middleware verifica il token con `requiresToken()` — controlla validità e scadenza (`TOKEN_EXPIRATION`, default 3000 secondi).

### Configurazione - Di default volutamente semplificata

**`API/BLL/auth_settings/pwd.txt`** — password per generare/verificare i token:
```
miaPasswordSegreta
```

**`API/BLL/auth_settings/APIKeys.txt`** — chiavi API autorizzate (una per riga):
```
frontend
chiaveApp2
```

**`API/BLL/auth_settings/CORSconfig.json`** — politica CORS:
```json
{
    "applyCORS": true,
    "allowedOrigins": ["https://miosito.it"]
}
```
Se `allowedOrigins` è un array vuoto, viene usato `Access-Control-Allow-Origin: *`.

**`API/BLL/auth_and_cors_middleware.php`** — cambia `CRYPTO_KEY` in produzione:
```php
define('CRYPTO_KEY', 'chiave_segretissima_min32caratteri__');
```

### Usare l'autenticazione in una pagina

```php
<?php
$requiresAuth = true;
include('FE_utils/TopPage.php');
?>

<?php if ($isLoggedIn): ?>
    <p>Sei loggato.</p>
<?php else: ?>
    <form id="loginForm">
        <input type="password" id="pwd">
        <button type="submit">Login</button>
    </form>
<?php endif; ?>

<script>
inizializzazioneApp.then(() => {
    document.getElementById('loginForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        apiCall('logging', { pwd: document.getElementById('pwd').value }, function(result) {
            if (result.valid) location.reload();
        }, 'POST', false);
    });
});
</script>

<?php include('FE_utils/BottomPage.php'); ?>
```

In alternativa, lato PHP si può usare direttamente:
```php
$result = $service->loggati(['pwd' => $_POST['pwd']]);
// $result = ['valid' => true|false, 'token' => '...', 'error' => '...']
```

---

## Effetto fumo

Effetto visivo decorativo (canvas) configurabile da `websettings.json`:

```json
"smoke": {
    "enable": true,
    "color": "#7099ff",
    "opacity": 0.4,
    "maximumVelocity": 100,
    "particleRadius": 250,
    "density": 5
}
```

Impostare `"enable": false` per disabilitarlo completamente (non carica lo script relativo).

---

## Policy

`privacypolicy.php` e `cookiepolicy.php` condividono il layout `FE_utils/_policy.php`.

Per personalizzarle, modificare il file corrispondente:

```php
$sz_mail = "info@esempio.it"; // email di contatto (opzionale, lasciare "" per non mostrarla)
$sz_data = "18 feb 2026";     // data ultima revisione

$pagina = [
    [
        "datiPersonali" => [
            "it" => "Testo in italiano...",
            "en" => "English text..."
        ]
    ],
    // aggiungere altre sezioni con la stessa struttura
];
```

La chiave dell'array (es. `"datiPersonali"`) viene usata come chiave di traduzione per il titolo `<h2>` (deve essere presente nei file lang JSON).

---

## Creare un nuovo progetto dal template

```bash
git checkout -b main
git remote add template https://github.com/br1brown/template-sito.git
git fetch template
git branch template template/main
git pull template main --allow-unrelated-histories
git merge --squash template
git commit -m "Template importato"
```

Per ricevere aggiornamenti futuri dal template nel proprio progetto:

```bash
git fetch template
git merge template/main --allow-unrelated-histories
# risolvere i conflitti se necessario
```
