<?php
require_once __DIR__ . '/Service.php';

$service = new Service();

$settings = $service->getSettings();
$meta = $service->getMeta();

require_once __DIR__ . '/headeraddon.php';
foreach ($settings as $key => $value) {
	${$key} = $value;
}

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
		<?= ($meta->title == "" ? "" : $meta->title . " | ") . $AppName; ?>
	</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1"> <!--shrink-to-fit=no-->

	<meta name="description" content="<?= $meta->description ?>">

	<?php if (!empty($meta->keywords)): ?>
		<meta name="keywords" content="<?= implode(",", $meta->keywords) ?>">
	<?php endif; ?>

	<?php if (isset($meta->author)): ?>
		<meta name="author" content="<?= $meta->author ?>">
	<?php endif; ?>

	<meta name="robots" content="index, follow">
	<link rel="manifest" href="webmanifest.php">

	<meta name="HandheldFriendly" content="<?= $meta->MobileFriendly ? "true" : "false" ?>">
	<meta name="MobileOptimized" content="<?= $meta->mobileOptimizationWidth ?>">

	<?php if (isset($meta->dataScadenzaGMT)): ?>
		<!-- La data di scadenza dei contenuti nel meta tag -->
		<meta http-equiv="expires" content="<?= $meta->dataScadenzaGMT ?>">
	<?php endif; ?>

	<!-- Colore tematico per il browser sui dispositivi Android -->
	<meta name="theme-color" content="<?= $colori["colorTema"] ?>" />
	<!-- Cambia lo stile della barra di stato su iOS -->
	<meta name="apple-mobile-web-app-status-bar-style" content="<?= $colori["colorTema"] ?>">

	<!-- Definizione del nome del sito web quando salvato come app web sui dispositivi mobili -->
	<meta name="application-name" content="<?= $AppName ?>" />
	<!-- Definisce il titolo dell'app web per iOS -->
	<meta name="apple-mobile-web-app-title" content="<?= $AppName ?>">
	<!-- Permette al sito web di funzionare a schermo intero -->
	<meta name="apple-mobile-web-app-capable" content="<?= $meta->FullScreenWebApp ? "yes" : "no" ?>">
	<meta name="mobile-web-app-capable" content="<?= $meta->FullScreenWebApp ? "yes" : "no" ?>">

	<link rel="icon" type="image/png" href="<?= $service->UrlAsset("favIcon") ?>">

	<!-- Accessibilità -->
	<meta name="accessibility-support" content="WCAG 2.1 Level AA">
	<meta name="accessibility-features" content="visual, auditory, cognitive, mobility">

	<script>

		function getBearertoken() {
			<?php if ($service->isLoggedIn()):
				echo 'return "' . $service->getToken() . '";';
			endif; ?>
			return null;
		}

		infoContesto = {
			clsTxt: '<?= $clsTxt ?>',
			EsternaAPI: <?= $service->EsternaAPI ? "true" : "false" ?>,
			APIKey: '<?= $service->APIkey ?>',
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
	<style>
		:root {
			<?php foreach ($colori as $chiave => $colore): ?>
				<?= "--{$chiave}: {$colore};\n"; ?>
			<?php endforeach; ?>
		}
	</style>

</head>

<body>
	<a id="back-to-top" href="#" aria-hidden="true"
		class="btn btn-<?= $isDarkTextPreferred ? "light" : "dark"; ?> btn-lg back-to-top" role="button">
		<i class="fas fa-chevron-up"></i>
		<span class="sr-only">Back to top</span>
	</a>
	<?php
	if ($havesmoke): ?>
		<canvas id="smoke-effect-canvas" data-color="<?= $smoke['color'] ?>" data-opacity="<?= $smoke['opacity'] ?>"
			data-maximumVelocity="<?= $smoke['maximumVelocity'] ?>" data-particleRadius="<?= $smoke['particleRadius'] ?>"
			data-density="<?= $smoke['density'] ?>"
			style="width:100%; height:100%; position: fixed; top: 0; left: 0; z-index: -100;">
		</canvas>
	<?php endif;
	//se $forceMenu è valorizzata a true lo metti, se non c'è lo metti
	if (isset($itemsMenu) && count($itemsMenu) > 0 && ((isset($forceMenu)) ? ($forceMenu == true) : true)): ?>
		<nav class="navbar navbar-expand-sm <?= $isDarkTextPreferred ? "navbar-light" : "navbar-dark" ?> fillColoreSfondo"
			role="navigation" aria-label="Main navigation">
			<?= $service->CreateRouteLinkHTML($AppName, "index", "navbar-brand", false) ?>
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
				aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarNav">
				<ul class="navbar-nav">
					<?php foreach ($itemsMenu as $key => $value): ?>
						<li
							class="nav-item<?= pathinfo(basename($_SERVER['PHP_SELF']), PATHINFO_FILENAME) == $value['route'] ? " active" : "" ?>">
							<?= $service->CreateRouteLinkHTML($value['nome'], $value['route'], "nav-link", false) ?>
						</li>
					<?php endforeach; ?>
				</ul>
				<?php
				$lingueDisponibili = $service->getLingueDisponibili();

				// Controlla se ci sono più di una lingua disponibile
				if (count($lingueDisponibili) > 1): ?>
					<div class="ml-auto">
						<div class="dropdown fillColoreSfondo">
							<button class="nav-link w-100 dropdown-toggle fillColoreSfondo <?= $clsTxt ?>" type="button"
								id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
								aria-label="Language selection menu">
								<?= $service->traduci("selezionaLingua"); ?>
							</button>
							<div class="dropdown-menu dropdown-menu-right fillColoreSfondo"
								aria-labelledby="dropdownMenuButton">
								<?php foreach ($lingueDisponibili as $lingua): ?>
									<a href="javascript:setLanguage('<?= $lingua ?>')"
										class="dropdown-item<?= $service->currentLang() == $lingua ? " active " : " " ?>fillColoreSfondo <?= $clsTxt ?>"
										role="menuitem">
										<?= strtoupper($lingua) ?>
									</a>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</nav>

	<?php endif; ?>
	<main>