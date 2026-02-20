</main>

<?php
if (isset($footer) && $footer == true): ?>
	<footer style="font-size: 0.8rem;bottom: 0;" class="container-fluid mt-3 fillColoreSfondo <?= $clsTxt ?>">
		<div class="container py-1">
			<?php if (
				(isset($irl['numeroWA']) && !empty($irl['numeroWA'])) ||
				(isset($irl['ig']) && !empty($irl['ig'])) ||
				(isset($irl['yt']) && !empty($irl['yt']))
			): ?>
				<div class="row text-center">
					<?php if (isset($irl['numeroWA'])): ?>
						<div class="col">
							<a href="https://wa.me/<?= htmlspecialchars(str_replace(' ', '', $irl['numeroWA'])) ?>" target="_blank"
								title="Whatsapp" aria-label="<?= $service->traduci("CTA_whatsapp"); ?>">
								<i class="social-icon fab fa-whatsapp"></i> Whatsapp
							</a>
						</div>
					<?php endif; ?>
					<?php if (isset($irl['ig'])): ?>
						<div class="col">
							<a href="<?= htmlspecialchars($irl['ig']) ?>" target="_blank" title="Instagram"
								aria-label="<?= $service->traduci("CTA_instagram"); ?>">
								<i class="social-icon fab fa-instagram"></i> Instagram
							</a>
						</div>
					<?php endif; ?>
					<?php if (isset($irl['yt'])): ?>
						<div class="col">
							<a href="<?= htmlspecialchars($irl['yt']) ?>" target="_blank" title="Youtube"
								aria-label="<?= $service->traduci("CTA_youtube"); ?>">
								<i class="social-icon fab fa-youtube"></i> Youtube
							</a>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php
			$robaFooter = "";
			if (isset($irl)) {
				$robaFooter .= VoceInformazione::renderInfos([
					new VoceInformazione('ragioneSociale', null, null),
					new VoceInformazione('indirizzoSedeLegale', null, null),
					new VoceInformazione('numeroTelefono', 'telefono', function ($val) use ($service) {
						return $service->creaLinkCodificato(str_replace(' ', '', $val), 'tel:');
					}),
					new VoceInformazione('pec', 'PEC', function ($val) use ($service) {
						return $service->creaLinkCodificato($val, 'mailto:');
					}),
					new VoceInformazione('mail', 'mail', function ($val) use ($service) {
						return $service->creaLinkCodificato($val, 'mailto:');
					}),
				], $irl, $service);
			}
			if (isset($irl)) {
				$robaFooter .= VoceInformazione::renderInfos([
					new VoceInformazione('partitaIVA', 'partitaiva', function ($val) {
						return "<code>$val</code>";
					}),
					new VoceInformazione('codiceFiscale', 'codiceFiscale', function ($val) {
						return "<code>$val</code>";
					}),
					new VoceInformazione('registroImprese', 'registroimprese', function ($val) {
						return "<code>$val</code>";
					}),
					new VoceInformazione('numeroIscrizione', 'iscrizionealbo', function ($val) {
						return "<code>$val</code>";
					}),
					new VoceInformazione('numeroREA', 'numerorea', function ($val) {
						return "<code>$val</code>";
					}),
				], $irl, $service);
			}
			if (isset($url)) {
				$arrayURLFooter = [];
				foreach ($url as $key => $val) {
					$arrayURLFooter[] = new VoceInformazione($key, null, function ($val) use ($key, $service) {
						if (!empty($val)) {
							return $service->createRouteLinkHTML($key, $val);
						}
						return null;
					});
				}

				$robaFooter .= VoceInformazione::renderInfos($arrayURLFooter, $url, $service);
			}

			if (!empty($robaFooter)) {
				?>
				<div class="row mb-1">
					<?= $robaFooter ?>
				</div>
			<?php } ?>

			<div class="row">
				<div class="col text-center">
					<p>© <?= date('Y') ?>
						<?= $service->createRouteLinkHTML($AppName, "index") ?> |
						<?= $service->traduci("dirittiriservati"); ?>.
					</p>
					<hr>
					<p>
						<?= $description ?>
					</p>
				</div>
			</div>
		</div>
	</footer>
<?php endif; ?>

</body>
</html>