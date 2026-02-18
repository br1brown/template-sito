<?php
$code = $_SERVER['REDIRECT_STATUS'] ?? http_response_code() ?: 500;
$forceMenu = false;
$footer = false;
$title = "Error " . $code;
include('FE_utils/TopPage.php');


$keyInfoTraduci = "errore" . $code . "Info";
$keyDescTraduci = "errore" . $code . "Desc";
$errorInfo = $service->traduci($keyInfoTraduci);
$errorMessage = $service->traduci($keyDescTraduci);

if ($errorMessage == $keyDescTraduci)
    $errorMessage = $service->traduci("erroreImprevisto");

if ($errorInfo == $keyInfoTraduci)
    $errorInfo = $service->traduci("errore") . ' ' . $code;
else {
    $errorInfo = $code . ": " . $errorInfo;

}

// Ricostruzione URL corrente — riusa la logica di protocollo già presente in Service
$source_url = $service->baseUrl . ltrim($_SERVER['REQUEST_URI'], '/');

?>
<div class="container-fluid">
    <div class="row">
        <div id=contenuto class="col-12 offset-md-2 col-md-8 text-center<?= $isDarkTextPreferred ? "" : " tutto" ?>">
            <h2>
                <?= $errorInfo ?>
            </h2>
            <small><i>
                    <?= $source_url ?>
                </i></small>
            <p>
                <?= $errorMessage ?>
            </p>
            <a href="<?= $service->createRoute("index") ?>" class="btn btn-primary btn-lg">
                <?= $service->traduci("home"); ?>
            </a>
        </div>
    </div>
</div>
<?php include('FE_utils/BottomPage.php'); ?>

</html>