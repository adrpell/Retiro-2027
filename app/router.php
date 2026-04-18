<?php
require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/routes/shared.php';

$route = $_GET['route'] ?? (defined('RT2027_DEFAULT_ROUTE') ? RT2027_DEFAULT_ROUTE : 'home');
$pageTitle = setting($pdo, 'event_name', 'Retiro 2027');

require __DIR__ . '/routes/actions_public.php';
require __DIR__ . '/routes/actions_admin.php';

ob_start();
$handled = false;
require __DIR__ . '/routes/views.php';
if (!$handled) {
    http_response_code(404);
    echo '<h1>Página não encontrada</h1>';
}
$content = ob_get_clean();

include __DIR__ . '/../templates/partials/header.php';
echo $content;
include __DIR__ . '/../templates/partials/footer.php';
