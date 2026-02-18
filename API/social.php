<?php
include __DIR__ . '/BLL/auth_and_cors_middleware.php';
function eseguiGET()
{

    echo echoGetObj("social", function ($data) { // use ($variabile, $variabile2) 
        $nomi = isset($_GET['nomi']) ? $_GET['nomi'] : [];

        if (empty($nomi))
            return $data;

        $nomiSocial = explode(';', $nomi);

        $risultati = [];

        // Converti tutte le chiavi di $data in minuscolo
        $dataLowerCase = array_change_key_case($data, CASE_LOWER);

        foreach ($nomiSocial as $nomeSocial) {
            // Converti anche il nomeSocial in minuscolo
            $nomeSocialLowerCase = strtolower($nomeSocial);

            if (isset($dataLowerCase[$nomeSocialLowerCase])) {
                $risultati[$nomeSocial] = $dataLowerCase[$nomeSocialLowerCase];
            }
        }


        return $risultati;
    });
}
include __DIR__ . '/BLL/gestione_metodi.php';
