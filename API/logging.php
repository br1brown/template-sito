<?php
include __DIR__ . '/BLL/auth_and_cors_middleware.php';

function eseguiPOST()
{
    header('Content-Type: application/json');

    $dati = datiInput();
    $pwd = $dati['pwd'] ?? ($_POST['pwd'] ?? null);

    if ($pwd !== null) {
        echo json_encode(generaToken($pwd));
    } else {
        echo json_encode(['valid' => false, 'error' => 'Password non fornita']);
    }
}

include __DIR__ . '/BLL/gestione_metodi.php';
