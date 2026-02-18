function applicaMenu(openMenuSelector, leftClick, opzioniMenu) {
    var eventoClick = leftClick ? 'click' : 'contextmenu';
    var uniqueIdCounter = 0; // Inizializza un contatore per i nomi univoci

    $(document).on(eventoClick, openMenuSelector, function (event) {
        if (!leftClick)
            event.preventDefault(); // Questa linea impedisce al browser di aprire il suo menu contestuale

        event.stopPropagation();
        $('.context-menu').remove();
        var selectorSanitized = openMenuSelector.replace(/[^a-zA-Z0-9]/g, '_');

        let menuHtml = '<div class="context-menu"><ul>';
        opzioniMenu.forEach(opzione => {
            var uniqueFunctionName = 'func_' + selectorSanitized + uniqueIdCounter++;
            window[uniqueFunctionName] = opzione.function;
            menuHtml += `<li class=text-light onclick="${uniqueFunctionName}(${$(openMenuSelector).data('context-args')})">${opzione.text}</li>`;
        });
        menuHtml += '</ul></div>';

        $('body').append(menuHtml);
        $('.context-menu').css({ top: event.pageY, left: event.pageX }).show();
    });

    $(document).on('click', function () {
        $('.context-menu').remove();
    });
};
