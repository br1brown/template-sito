<?php
$title = "Home";
// $singledescription = "Pagina principale";
include ('FE_utils/TopPage.php');

?>
<main class="container-fluid">
	<div class="row">
		<div class="col-12 text-center <?= $isDarkTextPreferred ? "text-dark" : "text-light" ?>">
			<h1><b><?= $title ?></b></h1>
			<?php if (isset($meta->author)): ?>
				<i>By <?= $meta->author ?></i>
			<?php endif; ?>
		</div>
	</div>
	<div class="row">
		<div class="offset-1 col-10 offset-md-2 col-md-8 shadow rounded tutto text-center" role="main">
			<div class="row">
				<p class="col"><?= $irl['infoBase'] ?></p>
			</div>
			<div class="row">
				<div class="p-3 col-xs-4 col-sm-4 col-md-4">
					<div class="polaroid ruotadestra">
						<img id="img_generica" src="https://via.placeholder.com/550x360/D3D3D3" alt="Foto Generica">
						<p class="caption" id="DESCimg_generica" data-context-args="'img_generica'">
							<?= $service->traduci("opzioni") ?>
						</p>
					</div>
				</div>
				<div class="col-xs-8 col-sm-8 col-md-8">
					<input type="button" class="bottone btn btn-primary btn-lg" aria-label="Conferma"
						value="<?= $service->traduci("conferma"); ?>"><br>
					<input type="button" data-type="success" id="success" class="zoomma bottone btn btn-success btn-lg"
						aria-label="Successo" value="<?= $service->traduci("successo"); ?>">
					<input type="button" data-type="error" id="danger" class="zoomma bottone btn btn-danger btn-lg"
						aria-label="Errore" value="<?= $service->traduci("errore"); ?>"><br>
					<input type="button" data-type="warning" id="warning" class="zoomma bottone btn btn-warning btn-sm"
						aria-label="Attenzione" value="<?= $service->traduci("attenzione"); ?>">
					<input type="button" data-type="info" id="info" class="zoomma bottone btn btn-info btn-sm"
						aria-label="Informazioni" value="<?= $service->traduci("info"); ?>">
				</div>
			</div>
			<div class="row">
				<div class="text-center col-xs-12 col-sm-12 col-md-12">
					<input type="button" id="primary" class="bottone btn btn-outline-primary btn-sm" aria-label="Invia"
						value="SUBMIT">
					<input type="button" id="secondary" class="bottone btn btn-outline-secondary btn-sm"
						aria-label="Secondario" value="SECONDARY">
					<input type="button" id="dark" class="bottone btn btn-outline-dark btn-sm" aria-label="Scuro"
						value="DARK">
					<input type="button" id="light" class="bottone btn btn-outline-light btn-sm" aria-label="Chiaro"
						value="LIGHT">
					<input type="button" id="link" class="bottone btn btn-outline-link btn-sm" aria-label="Link"
						value="LINK">
				</div>
			</div>
		</div>
	</div>
	</div>

</main>
<?php include ('FE_utils/BottomPage.php'); ?>

<script>
	inizializzazioneApp.then(() => {
		var testo = traduci('imgDinamica');
		var imageCreata = new CreaImmagine(testo,
			'<?= $colori["colorTema"] ?>',
			'<?= $isDarkTextPreferred ? "black" : "white" ?>'
		)
			.costruisci();

		$('#img_generica').attr('src', imageCreata.urlImmagine());

		ApplicaMenu('#DESCimg_generica', true, [
			{
				text: traduci("condividi"),
				function: function (args) {
					imageCreata.condividiImmagine("");
				}
			},
		]);

		apiCall("social", { nomi: "Facebook;twitter;Telegram" },
			function (response) {
			});
	});
</script>

</html>