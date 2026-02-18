<?php
include __DIR__ . '/BLL/auth_and_cors_middleware.php';

function eseguiGET()
{

    echo echoGetObj("irl", function ($data, $lingua) {
        return BLL\Response::traduciElemento($data, ["infoBase"], $lingua);
    });

}
include __DIR__ . '/BLL/gestione_metodi.php';
