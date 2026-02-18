// Questo script utilizza la libreria SweetAlert2 per mostrare avvisi personalizzati.
// La documentazione della libreria è disponibile su: https://sweetalert2.github.io/

// Attende che la app sia completamente caricata
inizializzazioneApp.then(() => {
    $(".bottone").click(function () {
        $(this).blur();
        var val = traduci($(this).val());
        var tipo = $(this).data("type");

        if (tipo && tipo != "") {
            Swal.fire(val, "", tipo);
        } else {
            Swal.fire(val);
        }
    });
});
