//https://sweetalert2.github.io/
$(document).ready(function () {
	$(".bottone").click(function () {
		$(this).blur();
		var val = $(this).val()
		var tipo = $(this).data("type");
		if (tipo && tipo != "")
			swal.fire(tipo + ": Sono io", val, tipo);
		else
			swal.fire("Sono io di Br1Brown", val);
	});


	$(window).scroll(function () {
		if ($(this).scrollTop() > 50) {
			$('#back-to-top').fadeIn();
		} else {
			$('#back-to-top').fadeOut();
		}
	});


	// scroll body to 0px on click
	$('#back-to-top').click(function () {
		$('body,html').animate({
			scrollTop: 0
		}, 400);
		return false;
	});

	var fumo = $('#smoke-effect-canvas');
	fumo.SmokeEffect({
		color: fumo.data('color'),
		opacity: fumo.data('opacity'),
		maximumVelocity: fumo.data('maximumVelocity'),
		particleRadius: fumo.data('particleRadius'),
		density: fumo.data('density')
	});


});


function openEncodedLink(prefix, encodedStr) {
	//var parser = new DOMParser();
	// var decodedString = parser.parseFromString("&#" + encodedStr + ";", "text/html").documentElement.textContent;
	var decodedString = encodedStr; //pare che il browser faccia lui le cose

	var url = "";
	if (prefix) {
		url = prefix + decodedString;
	} else {
		url = decodedString;
	}
	window.location.href = url;
}

function set_background_with_average_rgb(src) {
	var canvas = document.createElement('canvas');
	var context = canvas.getContext('2d');
	var img = new Image();

	// Setto crossOrigin per evitare problemi di CORS su immagini da domini esterni.
	img.crossOrigin = 'Anonymous';

	img.onload = function () {
		// Disegno l'immagine nel canvas. La dimensione 1x1 è sufficiente per il calcolo medio del colore.
		context.drawImage(img, 0, 0, 1, 1);
		var data = context.getImageData(0, 0, 1, 1).data;

		// Calcolo il colore medio e lo imposto come sfondo della pagina.
		var colorStr = 'rgb(' + data[0] + ',' + data[1] + ',' + data[2] + ')';
		document.body.style.backgroundColor = colorStr;
	};

	img.onerror = function () {
	};

	img.src = src;
}
