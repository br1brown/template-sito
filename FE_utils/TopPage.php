<?php
/**
 * TopPage.php — Parte superiore del layout composito.
 *
 * Apre <html>, <head>, <body> e <main>.
 * Ogni pagina include questo file in cima; il contenuto specifico
 * viene scritto dalla pagina stessa, e BottomPage.php chiude i tag.
 *
 * Flusso: TopPage → [contenuto pagina] → BottomPage
 *
 * Variabili che la pagina può dichiarare PRIMA dell'include:
 *
 *   $title             (string)  Chiave di traduzione per il <title> della pagina.
 *   $singledescription (string)  Chiave di traduzione per il meta description.
 *   $footer            (bool)    true  → mostra il footer (BottomPage.php).
 *   $forceMenu         (bool)    false → nasconde la navbar anche se $itemsMenu è popolato.
 *   $requiresAuth      (bool)    true  → avvia la sessione e rende disponibile
 *                                        $isLoggedIn (bool) per la pagina.
 *                                        Usare su pagine con contenuto condizionale
 *                                        al login (es. pulsanti modifica, sezioni riservate).
 *                                        Default: false → nessuna sessione, BFCache attiva.
 *
 * Variabili disponibili DOPO l'include (generate da TopPage):
 *
 *   $service           (Service) Istanza del service layer.
 *   $irl               (array)   Dati anagrafici dall'API (array vuoto se API offline).
 *   $isLoggedIn        (bool)    true se l'utente è autenticato. Disponibile solo se
 *                                $requiresAuth = true, altrimenti sempre false.
 *   $colori            (array)   Colori dal websettings.json come variabili CSS.
 *   $clsTxt            (string)  Classe Bootstrap per il colore testo ('text-dark'|'text-light').
 */
require_once __DIR__ . '/Service.php';

// Security Headers — standard obbligatori dal 2024+
// Protezione clickjacking: impedisce l'embedding in iframe di terzi
header('X-Frame-Options: SAMEORIGIN');
// Blocca lo sniffing del MIME type (previene attacchi XSS via content-type errato)
header('X-Content-Type-Options: nosniff');
// Attiva il filtro XSS del browser (legacy, ma zero costi e supportato ovunque)
header('X-XSS-Protection: 1; mode=block');
// Referrer Policy: non inviare URL completo a siti esterni
header('Referrer-Policy: strict-origin-when-cross-origin');
// Permissions Policy: disabilita funzionalità browser non necessarie
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

$service = new Service();

$settings = $service->getSettings();
$meta = $service->getMeta();

// Carica gli addon dell'header (CSS/JS aggiuntivi definiti nel template)
require_once __DIR__ . '/headeraddon.php';

// Estrae le impostazioni come variabili locali
// (es. $colori, $itemsMenu, $havesmoke, $smoke, $routes, $AppName, ecc.)
extract($settings, EXTR_SKIP);

// Stato autenticazione — disponibile solo se la pagina ha dichiarato $requiresAuth = true.
// Su pagine pubbliche è sempre false senza avviare la sessione (preserva BFCache).
$isLoggedIn = ($requiresAuth ?? false) ? $service->isLoggedIn() : false;

// Carica i dati anagrafici dall'API; se fallisce, $irl rimane un array vuoto
// così le pagine che lo usano non vanno in errore
$irl = [];
try {
	$irl = $service->callApiEndpoint("/anagrafica");
} catch (Exception $e) {
}
?>
<!doctype html>
<html lang="<?= $service->currentLang() ?>">

<head>
	<?php
	$clsTxt = $isDarkTextPreferred ? "text-dark" : "text-light";
	?>
	<title>
		<?= ($meta->title == "" ? "" : htmlspecialchars($meta->title) . " | ") . htmlspecialchars($AppName); ?>
	</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<meta name="description" content="<?= htmlspecialchars($meta->description) ?>">

	<?php if (isset($meta->author)): ?>
		<meta name="author" content="<?= htmlspecialchars($meta->author) ?>">
	<?php endif; ?>

	<meta name="robots" content="index, follow">

	<?php
	// Open Graph — standard per le anteprime social (Facebook, WhatsApp, LinkedIn, Telegram)
	$ogTitle = ($meta->title == "" ? $AppName : $meta->title . " | " . $AppName);
	?>
	<meta property="og:type" content="website">
	<meta property="og:title" content="<?= htmlspecialchars($ogTitle) ?>">
	<meta property="og:description" content="<?= htmlspecialchars($meta->description) ?>">
	<meta property="og:url" content="<?= htmlspecialchars($service->baseUrl) ?>">
	<meta property="og:site_name" content="<?= htmlspecialchars($AppName) ?>">
	<meta property="og:locale" content="<?= $service->currentLang() ?>">

	<link rel="manifest" href="webmanifest.php">

	<?php
	// Preload hints per risorse CDN esterne — il browser inizia a scaricarle
	// in parallelo subito, senza aspettare di incontrare il <script>/<link> effettivo.
	// Migliora FCP e LCP senza alterare l'ordine di esecuzione degli script.
	foreach ($meta->linkRel as $rel_link):
		echo $rel_link->preloadHint();
	endforeach;
	?>

	<meta name="theme-color" content="<?= $colori["colorTema"] ?>" />
	<meta name="apple-mobile-web-app-status-bar-style" content="<?= $colori["colorTema"] ?>">

	<meta name="application-name" content="<?= htmlspecialchars($AppName) ?>" />
	<meta name="apple-mobile-web-app-title" content="<?= htmlspecialchars($AppName) ?>">
	<meta name="apple-mobile-web-app-capable" content="<?= $meta->FullScreenWebApp ? "yes" : "no" ?>">
	<meta name="mobile-web-app-capable" content="<?= $meta->FullScreenWebApp ? "yes" : "no" ?>">

	<link rel="icon" type="image/png" href="<?= $service->UrlAsset("favIcon") ?>">

	<!-- Contesto JS globale: espone token, configurazione e rotte al frontend -->
	<script>

		/** Restituisce il token Bearer se l'utente è autenticato, altrimenti null.
		 *  La sessione viene avviata solo se la pagina ha dichiarato $requiresAuth = true,
		 *  preservando la back-forward cache sulle pagine pubbliche. */
		function getBearertoken() {
			<?php if (($requiresAuth ?? false) && $service->isLoggedIn()):
				echo 'return "' . $service->getToken() . '";';
			endif; ?>
			return null;
		}

		/** Oggetto globale con le informazioni di contesto per il JavaScript frontend */
		infoContesto = {
			clsTxt: '<?= $clsTxt ?>',
			EsternaAPI: <?= $service->EsternaAPI ? "true" : "false" ?>,
			APIKey: '<?= $service->APIKey ?>',
			lang: '<?= $service->currentLang() ?>',
			route: {
				traduzione: '<?= $service->pathLang ?>',
				APIEndPoint: '<?= $service->urlAPI ?>',
				<?php foreach ($routes as $singleRouting):
					$v = basename($singleRouting, ".php");
					echo "\n\t\t\t\t{$v}: '" . $service->baseURL('func/' . $v) . "',\n";
				endforeach; ?>
			}
		}
	</script>

	<?php
	foreach ($meta->linkRel as $rel_link):
		echo $rel_link->visualizza();
	endforeach;
	?>
	<!-- CSS custom properties: i colori dal websettings.json diventano variabili CSS -->
	<style>
		:root {
			<?php foreach ($colori as $chiave => $colore): ?>
				<?= "--{$chiave}: {$colore};\n"; ?>
			<?php endforeach; ?>
		}
	</style>

</head>

<body>
	<button id="back-to-top" type="button"
		class="btn btn-<?= $isDarkTextPreferred ? "light" : "dark"; ?> btn-lg back-to-top"
		aria-label="<?= $service->traduci("tornaInAlto") ?>">
		<i class="fas fa-chevron-up" aria-hidden="true"></i>
	</button>
	<?php
	// Canvas per l'effetto fumo decorativo (i parametri vengono da websettings.json → $smoke)
	if ($havesmoke): ?>
		<canvas id="smoke-effect-canvas" data-color="<?= $smoke['color'] ?>" data-opacity="<?= $smoke['opacity'] ?>"
			data-maximumVelocity="<?= $smoke['maximumVelocity'] ?>" data-particleRadius="<?= $smoke['particleRadius'] ?>"
			data-density="<?= $smoke['density'] ?>"
			style="width:100%; height:100%; position: fixed; top: 0; left: 0; z-index: -100;">
		</canvas>
	<?php endif;
	// Navbar Bootstrap 5 — visibile solo se ci sono voci di menu definite in websettings.json
	// e $forceMenu non è esplicitamente disabilitato
	if (isset($itemsMenu) && count($itemsMenu) > 0 && ((isset($forceMenu)) ? ($forceMenu == true) : true)): ?>
		<nav class="navbar navbar-expand-sm <?= $isDarkTextPreferred ? "navbar-light" : "navbar-dark" ?> fillColoreSfondo"
			role="navigation" aria-label="Main navigation">
			<div class="container-fluid">
				<?= $service->createRouteLinkHTML($AppName, "index", "navbar-brand", false) ?>
				<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
					aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarNav">
					<ul class="navbar-nav">
						<?php foreach ($itemsMenu as $key => $value): ?>
							<li
								class="nav-item<?= pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME) == $value['route'] ? " active" : "" ?>">
								<?= $service->createRouteLinkHTML($value['nome'], $value['route'], "nav-link", false) ?>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php
					$lingueDisponibili = $service->getLingueDisponibili();
					if (count($lingueDisponibili) > 1): ?>
						<ul class="navbar-nav ms-auto">
							<li class="nav-item dropdown">
								<a class="nav-link dropdown-toggle <?= $clsTxt ?>" href="#" id="dropdownMenuButton"
									role="button" data-bs-toggle="dropdown" aria-expanded="false"
									aria-label="Language selection menu">
									<?= $service->traduci("selezionaLingua"); ?>
								</a>
								<ul class="dropdown-menu dropdown-menu-end fillColoreSfondo"
									aria-labelledby="dropdownMenuButton">
									<?php foreach ($lingueDisponibili as $lingua): ?>
										<li>
											<button type="button"
												class="dropdown-item<?= $service->currentLang() == $lingua ? " active " : " " ?>fillColoreSfondo <?= $clsTxt ?>"
												onclick="setLanguage('<?= htmlspecialchars($lingua, ENT_QUOTES, 'UTF-8') ?>')">
												<?= strtoupper(htmlspecialchars($lingua, ENT_QUOTES, 'UTF-8')) ?>
											</button>
										</li>
									<?php endforeach; ?>
								</ul>
							</li>
						</ul>
					<?php endif; ?>
				</div>
			</div>
		</nav>

	<?php endif; ?>
	<main>