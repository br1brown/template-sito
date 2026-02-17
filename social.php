<?php
$title = "Social";
include('FE_utils/TopPage.php');

$social = $service->callApiEndpoint("social");

?>
<div class="container-fluid">
	<div class="row">
		<div class="col-12 offset-md-1 col-md-10 text-center tutto">
			<a title="Clone"><i class="muovi social-icon fa-solid fa-clone"></i></a>
			<a title="Cut"><i class="muovi social-icon fa-solid fa-scissors"></i></a>
			<a title="Paste"><i class="muovi social-icon fa-solid fa-paste"></i></a>
			<a title="Download"><i class="muovi social-icon fa-solid fa-download"></i></a>
			<a title="Mail"><i class="muovi social-icon fa-solid fa-envelope"></i></a>
			<a title="Pdf"><i class="muovi social-icon fa-solid fa-file-pdf"></i></a>
			<a title="Bell"><i class="muovi social-icon fa-solid fa-bell"></i></a>
			<hr>
			<?php if (isset($social) && is_array($social)): ?>

				<a href="<?= htmlspecialchars($social['telegram']) ?>" target="_blank" rel="noopener noreferrer"
					title="Telegram">
					<i class="social-icon fa-brands fa-telegram"></i>
				</a>

				<a href="<?= htmlspecialchars($social['whatsapp']) ?>" target="_blank" rel="noopener noreferrer"
					title="Whatsapp">
					<i class="social-icon fa-brands fa-whatsapp"></i>
				</a>

				<a href="<?= htmlspecialchars($social['skype']) ?>" target="_blank" rel="noopener noreferrer" title="Skype">
					<i class="social-icon fa-brands fa-skype"></i>
				</a>

				<a href="<?= htmlspecialchars($social['btc']) ?>" target="_blank" rel="noopener noreferrer" title="Bitcoin">
					<i class="social-icon fa-brands fa-bitcoin"></i>
				</a>

				<hr>

				<a href="<?= htmlspecialchars($social['facebook']) ?>" target="_blank" rel="noopener noreferrer"
					title="Facebook">
					<i class="social-icon fa-brands fa-facebook"></i>
				</a>

				<a href="<?= htmlspecialchars($social['instagram']) ?>" target="_blank" rel="noopener noreferrer"
					title="Instagram">
					<i class="social-icon fa-brands fa-instagram"></i>
				</a>

				<a href="<?= htmlspecialchars($social['twitter']) ?>" target="_blank" rel="noopener noreferrer"
					title="Twitter">
					<i class="social-icon fa-brands fa-x-twitter"></i>
				</a>

				<a href="<?= htmlspecialchars($social['linkedin']) ?>" target="_blank" rel="noopener noreferrer"
					title="Linkedin">
					<i class="social-icon fa-brands fa-linkedin"></i>
				</a>

				<a href="<?= htmlspecialchars($social['tumblr']) ?>" target="_blank" rel="noopener noreferrer"
					title="Tumblr">
					<i class="social-icon fa-brands fa-tumblr"></i>
				</a>

				<a href="<?= htmlspecialchars($social['pinterest']) ?>" target="_blank" rel="noopener noreferrer"
					title="Pinterest">
					<i class="social-icon fa-brands fa-pinterest"></i>
				</a>

				<a href="<?= htmlspecialchars($social['snapchat']) ?>" target="_blank" rel="noopener noreferrer"
					title="Snapchat">
					<i class="social-icon fa-brands fa-snapchat"></i>
				</a>

				<a href="<?= htmlspecialchars($social['tiktok']) ?>" target="_blank" rel="noopener noreferrer"
					title="Tiktok">
					<i class="social-icon fa-brands fa-tiktok"></i>
				</a>

				<a href="<?= htmlspecialchars($social['quora']) ?>" target="_blank" rel="noopener noreferrer" title="Quora">
					<i class="social-icon fa-brands fa-quora"></i>
				</a>

				<a href="<?= htmlspecialchars($social['foursquare']) ?>" target="_blank" rel="noopener noreferrer"
					title="Foursquare">
					<i class="social-icon fa-brands fa-foursquare"></i>
				</a>

				<hr>

				<a href="<?= htmlspecialchars($social['youtube']) ?>" target="_blank" rel="noopener noreferrer"
					title="Youtube">
					<i class="social-icon fa-brands fa-youtube"></i>
				</a>

				<a href="<?= htmlspecialchars($social['twitch']) ?>" target="_blank" rel="noopener noreferrer"
					title="Twitch">
					<i class="social-icon fa-brands fa-twitch"></i>
				</a>

				<a href="<?= htmlspecialchars($social['spotify']) ?>" target="_blank" rel="noopener noreferrer"
					title="Spotify">
					<i class="social-icon fa-brands fa-spotify"></i>
				</a>

				<a href="<?= htmlspecialchars($social['deezer']) ?>" target="_blank" rel="noopener noreferrer"
					title="Deezer">
					<i class="social-icon fa-brands fa-deezer"></i>
				</a>

				<a href="<?= htmlspecialchars($social['soundcloud']) ?>" target="_blank" rel="noopener noreferrer"
					title="Soundcloud">
					<i class="social-icon fa-brands fa-soundcloud"></i>
				</a>

				<a href="<?= htmlspecialchars($social['itunes']) ?>" target="_blank" rel="noopener noreferrer"
					title="Itunes">
					<i class="social-icon fa-brands fa-itunes"></i>
				</a>

				<a href="<?= htmlspecialchars($social['vimeo']) ?>" target="_blank" rel="noopener noreferrer" title="Vimeo">
					<i class="social-icon fa-brands fa-vimeo"></i>
				</a>

				<a href="<?= htmlspecialchars($social['dribbble']) ?>" target="_blank" rel="noopener noreferrer"
					title="Dribbble">
					<i class="social-icon fa-brands fa-dribbble"></i>
				</a>

				<a href="<?= htmlspecialchars($social['yahoo']) ?>" target="_blank" rel="noopener noreferrer" title="Yahoo">
					<i class="social-icon fa-brands fa-yahoo"></i>
				</a>

				<a href="<?= htmlspecialchars($social['audible']) ?>" target="_blank" rel="noopener noreferrer"
					title="Audible">
					<i class="social-icon fa-brands fa-audible"></i>
				</a>

				<hr>

				<a href="<?= htmlspecialchars($social['google']) ?>" target="_blank" rel="noopener noreferrer"
					title="Google">
					<i class="social-icon fa-brands fa-google"></i>
				</a>

				<a href="<?= htmlspecialchars($social['chromecast']) ?>" target="_blank" rel="noopener noreferrer"
					title="Chromecast">
					<i class="social-icon fa-brands fa-chromecast"></i>
				</a>

				<a href="<?= htmlspecialchars($social['chrome']) ?>" target="_blank" rel="noopener noreferrer"
					title="Chrome">
					<i class="social-icon fa-brands fa-chrome"></i>
				</a>

				<a href="<?= htmlspecialchars($social['android']) ?>" target="_blank" rel="noopener noreferrer"
					title="Android">
					<i class="social-icon fa-brands fa-android"></i>
				</a>

				<a href="<?= htmlspecialchars($social['apple']) ?>" target="_blank" rel="noopener noreferrer" title="Apple">
					<i class="social-icon fa-brands fa-apple"></i>
				</a>

				<a href="<?= htmlspecialchars($social['playstation']) ?>" target="_blank" rel="noopener noreferrer"
					title="Playstation">
					<i class="social-icon fa-brands fa-playstation"></i>
				</a>

				<a href="<?= htmlspecialchars($social['amazon']) ?>" target="_blank" rel="noopener noreferrer"
					title="Amazon">
					<i class="social-icon fa-brands fa-amazon"></i>
				</a>

				<a href="<?= htmlspecialchars($social['airbnb']) ?>" target="_blank" rel="noopener noreferrer"
					title="Airbnb">
					<i class="social-icon fa-brands fa-airbnb"></i>
				</a>
			<?php endif; ?>


		</div>
	</div>

</div>
<?php include('FE_utils/BottomPage.php'); ?>

<script>
	inizializzazioneApp.then(() => {

	});
</script>

</html>