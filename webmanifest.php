<?php
header('Content-Type: application/manifest+json');
require_once __DIR__ . '/FE_utils/Service.php';
$service = new Service();
$settings = $service->getSettings();

// La description in getSettings() è già risolta per lingua (stringa, non array)
$manifest = [
    'name' => $settings['AppName'] ?? 'Template',
    'short_name' => $settings['AppName'] ?? 'Template',
    'description' => $settings['description'] ?? 'Descrizione Default',
    'lang' => $service->currentLang(),
    'start_url' => $service->baseUrl,
    'display' => 'browser',
    'background_color' => $settings["colori"]['colorBase'] ?? '#ffffff',
    'theme_color' => $settings["colori"]['colorTema'] ?? '#212529'
];

// Usiamo JSON_UNESCAPED_UNICODE per caratteri accentati e JSON_UNESCAPED_SLASHES per URL puliti.
echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>