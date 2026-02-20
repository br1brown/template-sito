// Aggrega tutte le dipendenze in una singola Promessa.
// inizializzazioneApp.then() è sicuro da usare in qualsiasi script inline
// delle pagine: lingua.js e base.js sono sincroni, quindi questa variabile
// è già definita quando lo script inline viene eseguito.
// La Promise si risolve quando la traduzione è caricata e jQuery è pronto.
var inizializzazioneApp = Promise.all([
	traduzioneCaricata,
	new Promise(resolve => window.addEventListener('load', resolve))
]);


/**
 * Funzione di inizializzazione eseguita al caricamento completo del DOM.
 */
inizializzazioneApp.then(() => {

	/**
	 * Gestisce l'evento di scorrimento della finestra.
	 */
	$(window).scroll(function () {
		if ($(this).scrollTop() > 50) {
			$('#back-to-top').fadeIn();
		} else {
			$('#back-to-top').fadeOut();
		}
	});

	/**
	 * Gestisce il click sul pulsante 'back-to-top'.
	 */
	$('#back-to-top').click(function () {
		$('body,html').animate({
			scrollTop: 0
		}, 400);
		return false;
	});

	/**
	 * Imposta l'effetto fumo su un elemento canvas specificato.
	 */
	var fumo = $('#smoke-effect-canvas');
	if (fumo.length)
		fumo.SmokeEffect({
			color: fumo.data('color'),
			opacity: fumo.data('opacity'),
			maximumVelocity: fumo.data('maximumVelocity'),
			particleRadius: fumo.data('particleRadius'),
			density: fumo.data('density')
		});

	/**
	 * Gestisce lo scorrimento orizzontale su elementi con classe 'horizontal-scroll'.
	 */
	$('.horizontal-scroll').on('wheel', function (event) {
		event.preventDefault();
		this.scrollLeft += event.originalEvent.deltaY + event.originalEvent.deltaX;
	});

});

/**
 * Apre un link codificato.
 * 
 * @param {string} prefix - Prefisso da aggiungere alla stringa decodificata.
 * @param {string} encodedStr - Stringa codificata da decodificare e aprire come link.
 */
function openEncodedLink(prefix, encodedStr) {
	var decodedString = encodedStr;
	var url = prefix ? prefix + decodedString : decodedString;
	window.location.href = url;
}

/**
 * Imposta lo sfondo della pagina con il colore medio di un'immagine.
 * 
 * @param {string} src - Percorso dell'immagine da cui estrarre il colore medio.
 */
function setBackgroundWithAverageRgb(src) {
	var canvas = document.createElement('canvas');
	var context = canvas.getContext('2d');
	var img = new Image();

	img.crossOrigin = 'Anonymous';

	img.onload = function () {
		context.drawImage(img, 0, 0, 1, 1);
		var data = context.getImageData(0, 0, 1, 1).data;
		var colorStr = 'rgb(' + data[0] + ',' + data[1] + ',' + data[2] + ')';
		document.body.style.backgroundColor = colorStr;
	};

	img.onerror = function () {
		// Gestione dell'errore
	};

	img.src = src;
}

/**
 * Copia un testo nella clipboard del sistema.
 * Usa l'API Clipboard (moderno) con fallback per contesti non sicuri.
 * 
 * @param {string} testoDaCopiare - Testo da copiare nella clipboard.
 * @returns {Promise} - Promise che risolve se la copia è riuscita.
 */
function copyToClipboard(testoDaCopiare) {
	// Clipboard API: disponibile in contesti sicuri (HTTPS)
	if (navigator.clipboard && window.isSecureContext) {
		return navigator.clipboard.writeText(testoDaCopiare);
	}

	// Fallback: usa textarea nascosta con Selection API (non deprecato, a differenza di execCommand)
	return new Promise((resolve, reject) => {
		const textarea = document.createElement('textarea');
		textarea.value = testoDaCopiare;
		// Posiziona fuori dallo schermo per evitare flash visivo
		textarea.style.position = 'fixed';
		textarea.style.left = '-9999px';
		textarea.style.top = '-9999px';
		textarea.style.opacity = '0';
		document.body.appendChild(textarea);
		textarea.focus();
		textarea.select();

		try {
			// Nota: execCommand è deprecated ma è l'unico fallback
			// per contesti non-HTTPS. In produzione, usa sempre HTTPS.
			const successful = document.execCommand('copy');
			document.body.removeChild(textarea);
			successful ? resolve() : reject(new Error('Copy failed'));
		} catch (err) {
			document.body.removeChild(textarea);
			reject(err);
		}
	});
}

/**
 * Gestisce l'animazione e lo stato di un oggetto durante un periodo di tempo definito.
 * 
 * @param {Object} myobj - Selettore jQuery per identificare l'oggetto.
 * @param {number} durata - Durata dell'animazione in millisecondi.
 */
function disattivaPer(myobj, durata) {
	function iniziaCaricamento() {
		myobj.prop('disabled', true).addClass('obj-loading');
	}

	function updateProgress(value) {
		var percentage = (value / durata) * 100;
		myobj.css('background-size', percentage + '% 100%');
	}

	function terminaCaricamento() {
		myobj.prop('disabled', false).removeClass('obj-loading');
	}

	iniziaCaricamento();

	var startTime = Date.now();
	var interval = setInterval(function () {
		var elapsedTime = Date.now() - startTime;
		updateProgress(elapsedTime);

		if (elapsedTime >= durata) {
			clearInterval(interval);
			terminaCaricamento();
		}
	}, 100);
}

/**
 * Imposta la lingua dell'applicativo
 * @param {string} lang - codice lingua
 */
function setLanguage(lang) {
	let searchParams = new URLSearchParams(window.location.search);
	searchParams.set('lang', lang);
	let newUrl = window.location.pathname + '?' + searchParams.toString() + window.location.hash;
	window.location.href = newUrl;
}
