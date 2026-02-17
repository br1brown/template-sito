<?php
$title = "dashboardHome";
include('FE_utils/TopPage.php');

$apiOnline = !empty($irl);
$apiStatus = $service->traduci($apiOnline ? "online" : "offline");
$lingueDisponibili = $service->getLingueDisponibili();
$totalLanguages = count($lingueDisponibili);
?>

<div class="container-fluid">
	<div class="row">
		<div class="col-12 text-center <?= $isDarkTextPreferred ? "text-dark" : "text-light" ?>">
			<h1><b><?= $service->traduci($title) ?></b></h1>
			<?php if (isset($meta->author)): ?>
				<p class="mb-0"><?= $service->traduci("createdBy", htmlspecialchars($meta->author)) ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="row">
		<div class="offset-1 col-10 offset-md-1 col-md-10 shadow rounded tutto" role="main">
			<div class="row">
				<div class="col-12 text-center">
					<p class="lead mb-2"><?= $irl['infoBase'] ?? $service->traduci("welcomeConsole") ?></p>
				</div>
			</div>

			<ul class="nav nav-tabs justify-content-center mb-3" id="homeTabs" role="tablist">
				<li class="nav-item" role="presentation">
					<button class="nav-link active" id="demo-tab" data-bs-toggle="tab" data-bs-target="#demo-pane"
						type="button" role="tab" aria-controls="demo-pane" aria-selected="true">
						<?= $service->traduci("tabOperationalLab") ?>
					</button>
				</li>
				<li class="nav-item" role="presentation">
					<button class="nav-link" id="internals-tab" data-bs-toggle="tab" data-bs-target="#internals-pane"
						type="button" role="tab" aria-controls="internals-pane" aria-selected="false">
						<?= $service->traduci("tabSystemApi") ?>
					</button>
				</li>
			</ul>

			<div class="tab-content" id="homeTabsContent">
				<div class="tab-pane fade show active" id="demo-pane" role="tabpanel" aria-labelledby="demo-tab">
					<div class="mb-3">
						<p class="small text-muted mb-0"><?= $service->traduci("operationalLabIntro") ?></p>
					</div>

					<div class="row g-3 text-start">
						<div class="col-12">
							<div class="card border-0 shadow-sm">

								<div class="card-body p-4">
									<div class="d-flex align-items-center mb-4">
										<div class="bg-primary text-white rounded-circle p-2 me-3">
											<i class="bi bi-palette"></i>
										</div>
										<div>
											<h5 class="text-uppercase fw-bold small text-primary mb-1">
												<?= $service->traduci("markdownLabTitle") ?>
											</h5>
											<small class="text-muted">
												<?= $service->traduci("markdownLabDesc") ?>
											</small>
										</div>
									</div>
									<hr class="opacity-10 mb-4">

									<label for="markdown_input"
										class="form-label fw-bold small text-primary"><?= $service->traduci("markdownInputLabel") ?></label>
									<textarea id="markdown_input" class="form-control mb-3" rows="6"
										placeholder="<?= $service->traduci("markdownPlaceholder") ?>">## <?= $service->traduci("tabOperationalLab") ?>

- <?= $service->traduci("markdownServerParser") ?>
- <?= $service->traduci("markdownLivePreview") ?>
- <?= $service->traduci("markdownSupportList") ?></textarea>

									<div class="row g-3">
										<div class="col-12 col-lg-7">
											<label
												class="form-label fw-bold small text-primary mb-1"><?= $service->traduci("markdownPreviewTitle") ?></label>
											<div id="markdown_output" class="border rounded p-3 bg-light"></div>
											<div class="mt-2">
												<button id="copy_markdown_preview"
													class="btn btn-outline-secondary btn-sm"><?= $service->traduci("copyPreview") ?></button>
											</div>
											<p class="small text-muted mt-2 mb-0">
												<?= $service->traduci("markdownPreviewDesc") ?>
											</p>
										</div>
										<div class="col-12 col-lg-5">
											<label for="markdown_html"
												class="form-label fw-bold small text-primary mb-1"><?= $service->traduci("markdownHtmlOutputTitle") ?></label>
											<textarea id="markdown_html" class="form-control bg-light small" rows="8"
												readonly></textarea>
											<div class="mt-2">
												<button id="copy_markdown_html"
													class="btn btn-outline-secondary btn-sm"><?= $service->traduci("copyHtml") ?></button>
											</div>
											<p class="small text-muted mt-2 mb-0">
												<?= $service->traduci("markdownHtmlDesc") ?>
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row g-4 mt-2">
						<div class="col-12">
							<div class="card border-0 shadow-sm">
								<div class="card-body p-4">
									<div class="d-flex align-items-center mb-4">
										<div class="bg-primary text-white rounded-circle p-2 me-3">
											<i class="bi bi-palette"></i>
										</div>
										<div>
											<h5 class="text-uppercase fw-bold small text-primary mb-1">
												<?= $service->traduci("imageBuilderTitle") ?>
											</h5>
											<small class="text-muted"><?= $service->traduci("imageBuilderDesc") ?></small>
										</div>
									</div>

									<hr class="opacity-10 mb-4">

									<div class="row g-4 align-items-start">
										<div class="col-12 col-lg-7">
											<div class="mb-5">
												<h6 class="fw-bold small text-primary mb-3"><?= $service->traduci("textSectionTitle") ?></h6>
												<div class="row g-3">
													<div class="col-12">
														<label class="form-label fw-semibold small"><?= $service->traduci("imageTextPlaceholder") ?></label>
														<input id="img_text" class="form-control form-control-lg shadow-sm" value="<?= htmlspecialchars($service->traduci("imgDinamica")) ?>">
													</div>

													<div class="col-md-5">
														<label class="form-label small"><?= $service->traduci("imageFont") ?></label>
														<select id="img_font_family" class="form-select shadow-sm">
															<option>Verdana</option>
															<option>Arial</option>
															<option>Montserrat</option>
														</select>
													</div>
													<div class="col-6 col-md-3">
														<label class="form-label small"><?= $service->traduci("imageSize") ?></label>
														<input id="img_font_size" type="number" class="form-control shadow-sm" min="10" max="250" value="50">
													</div>
													<div class="col-6 col-md-3">
														<label class="form-label small"><?= $service->traduci("imageColorText") ?></label>
														<input id="img_txt_color" type="color" class="form-control form-control-color w-100 shadow-sm" value="<?= $isDarkTextPreferred ? "#363636" : "#e3e3e3" ?>">
													</div>
												</div>
											</div>

											<div class="mb-4">
												<h6 class="fw-bold small text-primary mb-3"><?= $service->traduci("imageSectionTitle") ?></h6>
												<div class="row g-3">
													<div class="col-6 col-md-6">
														<label class="form-label small"><?= $service->traduci("imageBg") ?></label>
														<input id="img_bg_color" type="color" class="form-control form-control-color w-100 shadow-sm" value="<?= htmlspecialchars($colori["colorTema"]) ?>">
													</div>
													<div class="col-6 col-md-6">
														<label class="form-label small"><?= $service->traduci("imageWidth") ?></label>
														<div class="input-group shadow-sm">
															<input id="img_width" type="number" class="form-control" min="300" max="1800" step="50" value="900">
															<span class="input-group-text small">px</span>
														</div>
													</div>
												</div>
											</div>

											<div class="d-flex gap-2 pt-3 border-top mt-4">
												<button id="download_image" class="btn btn-dark px-4 shadow-sm"><?= $service->traduci("salva") ?></button>
												<button id="share_image" class="btn btn-outline-success px-4 shadow-sm"><?= $service->traduci("condividi") ?></button>
											</div>
										</div>

										<div class="col-12 col-lg-5 text-center">
											<div class="preview-container bg-light rounded-3 p-4 border">
												<p class="small text-muted fw-bold mb-4"><?= $service->traduci("imagePreviewTitle") ?></p>
												<div class="polaroid-wrapper">
													<div class="polaroid ruotadestra d-inline-block shadow-lg">
														<img id="img_generica" src="https://via.placeholder.com/550x360/D3D3D3" class="img-fluid rounded border mb-2" alt="Preview">
														<p class="caption text-dark fst-italic py-2"><?= $service->traduci("imagePreviewTitle") ?></p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row g-3 text-start mt-1">
						<div class="col-12">
							<div class="card border-0 shadow-sm">
								<div class="card-body p-4">
									<div class="d-flex align-items-center mb-4">
										<div class="bg-primary text-white rounded-circle p-2 me-3">
											<i class="bi bi-palette"></i>
										</div>
										<div>
											<h5 class="text-uppercase fw-bold small text-primary mb-1">
												<?= $service->traduci("contextMenuTitle") ?>
											</h5>
											<small class="text-muted">
												<?= $service->traduci("contextMenuDesc") ?>
											</small>
										</div>
									</div>
									<hr class="opacity-10 mb-4">

									<div id="menu_context_target" class="border rounded bg-light px-3 py-3 fw-semibold">
										<?= $service->traduci("contextMenuTargetLabel") ?>
									</div>
									<p class="small text-muted mt-3 mb-0"><?= $service->traduci("contextMenuHint") ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="tab-pane fade" id="internals-pane" role="tabpanel" aria-labelledby="internals-tab">
					<div class="mb-3">
						<p class="small text-muted mb-0"><?= $service->traduci("internalsIntro") ?></p>
					</div>

					<div class="row g-3 text-start">
						<div class="col-12 col-md-4">
							<div class="card border-0 shadow-sm h-100">
								<div class="card-body p-4">
									<h5 class="text-uppercase fw-bold small text-primary mb-3">
										<?= $service->traduci("accessibilityCardTitle") ?>
									</h5>
									<p class="small text-muted mb-3"><?= $service->traduci("internalsThemeDesc") ?></p>
									<div
										class="rounded-pill px-3 py-2 border d-flex align-items-center justify-content-between">
										<span class="small fw-bold"><?= htmlspecialchars($colori["colorTema"]) ?></span>
									</div>
								</div>
							</div>
						</div>

						<div class="col-12 col-md-4">
							<div class="card border-0 shadow-sm h-100">
								<div class="card-body p-4">
									<h5 class="text-uppercase fw-bold small text-primary mb-3">
										<?= $service->traduci("systemLanguagesTitle") ?> <span
											class="badge bg-info ms-1"><?= $totalLanguages ?></span>
									</h5>
									<p class="small text-muted mb-3"><?= $service->traduci("internalsLanguageDesc") ?>
									</p>
									<div class="d-flex flex-wrap gap-2">
										<?php foreach ($lingueDisponibili as $lingua): ?>
											<button
												class="btn btn-sm <?= $service->currentLang() === $lingua ? "btn-primary" : "btn-outline-primary" ?>"
												onclick="setLanguage('<?= htmlspecialchars($lingua) ?>')">
												<?= strtoupper(htmlspecialchars($lingua)) ?>
											</button>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>

						<div class="col-12 col-md-4">
							<div class="card border-0 shadow-sm h-100">
								<div class="card-body p-4">
									<h5 class="text-uppercase fw-bold small text-primary mb-3">
										<?= $service->traduci("apiStatusTitle") ?>
									</h5>
									<p class="small text-muted mb-3"><?= $service->traduci("internalsApiDesc") ?></p>
									<span
										class="badge <?= $apiOnline ? "text-bg-success" : "text-bg-danger" ?> mb-2 d-inline-block">
										<?= $service->traduci("apiStatus", $apiStatus) ?>
									</span>
									<div class="small text-muted text-break"><?= htmlspecialchars($service->urlAPI) ?>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row g-3 text-start mt-1">
						<div class="col-12 col-lg-8">
							<div class="card border-0 shadow-sm h-100">
								<div class="card-body p-4">
									<h5 class="text-uppercase fw-bold small text-primary mb-3">
										<?= $service->traduci("apiPlaygroundTitle") ?>
									</h5>
									<p class="small text-muted mb-3"><?= $service->traduci("apiPlaygroundDesc") ?></p>
									<label for="social_filter"
										class="form-label fw-bold small text-primary"><?= $service->traduci("socialFilterLabel") ?></label>
									<div class="input-group mb-3">
										<input id="social_filter" class="form-control"
											value="Facebook;twitter;Telegram">
										<button id="call_social_api"
											class="btn btn-primary"><?= $service->traduci("carica") ?></button>
									</div>
									<div id="social_output" class="bg-dark text-info p-3 rounded small"></div>
									<button id="copy_social_json"
										class="btn btn-sm btn-link px-0 text-decoration-none mt-2"><?= $service->traduci("copyJson") ?></button>
								</div>
							</div>
						</div>

						<div class="col-12 col-lg-4">
							<div class="card border-0 shadow-sm h-100">
								<div class="card-body p-4">
									<h5 class="text-uppercase fw-bold small text-primary mb-3">
										<?= $service->traduci("assetResolverTitle") ?>
									</h5>
									<p class="small text-muted mb-3"><?= $service->traduci("assetResolverDesc") ?></p>
									<label for="asset_id"
										class="form-label fw-bold small text-primary"><?= $service->traduci("assetResolverInputLabel") ?></label>
									<div class="input-group input-group-sm mb-3">
										<input id="asset_id" class="form-control" value="favIcon">
										<button id="load_asset"
											class="btn btn-primary"><?= $service->traduci("carica") ?></button>
									</div>
									<div class="p-3 border rounded bg-light text-center">
										<img id="asset_preview" class="img-fluid mb-2"
											alt="<?= $service->traduci("assetPreviewAlt") ?>">
										<div class="small text-break"><a id="asset_link" href="#" target="_blank"
												rel="noopener noreferrer" class="text-decoration-none"></a></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php include('FE_utils/BottomPage.php'); ?>
<script>
	inizializzazioneApp.then(() => {
		let imageCreata = null;
		let markdownRenderTimer = null;

		function buildImage() {
			const testo = $('#img_text').val() || traduci('imgDinamica');
			const bgColor = $('#img_bg_color').val() || '<?= $colori["colorTema"] ?>';
			const textColor = $('#img_txt_color').val() || '<?= $isDarkTextPreferred ? "#000000" : "#ffffff" ?>';
			const fontSize = parseInt($('#img_font_size').val(), 10) || 50;
			const width = parseInt($('#img_width').val(), 10) || 900;
			const fontFamily = $('#img_font_family').val() || 'Verdana';

			imageCreata = new CreaImmagine(testo, bgColor, textColor)
				.setFontsize(fontSize)
				.setLarghezza(width)
				.setFont(fontFamily)
				.costruisci();

			$('#img_generica').attr('src', imageCreata.urlImmagine());
		}

		function resetImageBuilder() {
			$('#img_text').val(traduci('imgDinamica'));
			$('#img_bg_color').val('<?= htmlspecialchars($colori["colorTema"]) ?>');
			$('#img_txt_color').val('<?= $isDarkTextPreferred ? "#000000" : "#ffffff" ?>');
			$('#img_font_size').val(50);
			$('#img_width').val(900);
			$('#img_font_family').val('Verdana');
			buildImage();
		}

		function renderMarkdown() {
			const txt = $('#markdown_input').val();
			$.get(infoContesto.route.markparsing, { text: txt }, function (html) {
				$('#markdown_output').html(html);
				$('#markdown_html').val(html);
			});
		}

		ApplicaMenu('#menu_context_target', false, [
			{
				text: traduci("condividi"),
				function: function () {
					const testo = $('#menu_context_target').text().trim();
					const shareData = {
						title: document.title,
						text: testo,
						url: window.location.href
					};
					if (navigator.share) {
						navigator.share(shareData).then(() => {
						});
						return;
					}
					copyToClipboard(window.location.href).then(() => {
						Swal.fire(traduci("ottimo"), traduci("operazioneRiuscita"), "success");
					});
				}
			},
			{
				text: traduci("home"),
				function: function () {
					window.scrollTo({ top: 0, behavior: "smooth" });
				}
			},
			{
				text: traduci("salva") + " JSON",
				function: function () {
					const navEntries = performance.getEntriesByType("navigation");
					const payload = {
						pageTitle: document.title,
						currentUrl: window.location.href,
						origin: window.location.origin,
						pathname: window.location.pathname,
						queryString: window.location.search,
						hash: window.location.hash,
						referrer: document.referrer || null,
						language: navigator.language,
						userAgent: navigator.userAgent,
						historyLength: window.history.length,
						viewport: {
							width: window.innerWidth,
							height: window.innerHeight
						},
						navigation: navEntries.length ? {
							type: navEntries[0].type,
							durationMs: Math.round(navEntries[0].duration)
						} : null,
						generatedAt: new Date().toISOString()
					};
					const blob = new Blob([JSON.stringify(payload, null, 2)], { type: "application/json" });
					const link = document.createElement("a");
					link.href = URL.createObjectURL(blob);
					link.download = "site-technical-info.json";
					document.body.appendChild(link);
					link.click();
					document.body.removeChild(link);
					URL.revokeObjectURL(link.href);
					Swal.fire(traduci("ottimo"), traduci("operazioneRiuscita"), "success");
				}
			},
			{
				text: traduci("copia") + " URL",
				function: function () {
					copyToClipboard(window.location.href).then(() => {
						Swal.fire(traduci("ottimo"), traduci("operazioneRiuscita"), "success");
					});
				}
			},
			{
				text: traduci("info"),
				function: function () {
					Swal.fire({
						title: traduci("info"),
						text: traduci("contextMenuAlertMessage"),
						icon: "info",
						confirmButtonText: traduci("chiudi")
					});
				}
			}
		]);

		$('#img_text, #img_bg_color, #img_txt_color, #img_font_size, #img_width, #img_font_family').on('input change', buildImage);
		$('#share_image').on('click', function () {
			if (imageCreata) imageCreata.condividiImmagine("");
		});
		$('#download_image').on('click', function () {
			if (imageCreata) imageCreata.scaricaImmagine();
		});

		$('#markdown_input').on('input', function () {
			clearTimeout(markdownRenderTimer);
			markdownRenderTimer = setTimeout(renderMarkdown, 300);
		});
		$('#copy_markdown_preview').on('click', function () {
			copyToClipboard($('#markdown_output').text()).then(() => {
				Swal.fire(traduci("ottimo"), traduci("operazioneRiuscita"), "success");
			});
		});
		$('#copy_markdown_html').on('click', function () {
			copyToClipboard($('#markdown_html').val()).then(() => {
				Swal.fire(traduci("ottimo"), traduci("htmlCopiato"), "success");
			});
		});

		function loadSocial() {
			const nomi = $('#social_filter').val();
			apiCall("social", { nomi: nomi }, function (response) {
				$('#social_output').text(JSON.stringify(response, null, 2));
			}, 'GET', false);
		}

		function loadAsset() {
			const id = ($('#asset_id').val() || "").trim();
			const url = infoContesto.route.getAsset + "?ID=" + encodeURIComponent(id);
			$('#asset_preview').attr('src', url);
			$('#asset_link').attr('href', url).text(url);
		}

		$('#call_social_api').on('click', loadSocial);
		$('#copy_social_json').on('click', function () {
			copyToClipboard($('#social_output').text()).then(() => {
				Swal.fire(traduci("ottimo"), traduci("jsonCopiato"), "success");
			});
		});
		$('#load_asset').on('click', loadAsset);

		buildImage();
		renderMarkdown();
		loadSocial();
		loadAsset();
	});
</script>

</html>
