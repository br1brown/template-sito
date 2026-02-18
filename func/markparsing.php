<?php
$k = 'text';
require_once dirname(__DIR__) . '/FE_utils/funzioni.php';

$input = isset($_GET[$k]) ? $_GET[$k] : '';

// NON sanitizzare con htmlspecialchars PRIMA del parsing Markdown,
// altrimenti la sintassi Markdown viene distrutta (es. **bold** → **bold**)
// Parsedown gestisce internamente la sanitizzazione dell'output HTML.
echo Markdown_HTML($input);
