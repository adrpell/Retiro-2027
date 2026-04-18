<?php
if (php_sapi_name() !== 'cli') {
    $secret = $_GET['secret'] ?? '';
    $configured = $config['cron_secret'] ?? '';
    if ($configured === '' || !hash_equals($configured, $secret)) {
        http_response_code(403); exit;
    }
}
require __DIR__ . '/app/bootstrap.php';
if ((string)setting($pdo, 'backup_auto_enabled', '0') !== '1') { echo "Backup automático desativado.\n"; exit; }
$info = rt2027_generate_backup($pdo, $config, 'auto');
rt2027_log_activity($pdo, 'backup', 'Backup automático', 'Backup automático gerado: ' . $info['file_name']);
echo "Backup gerado: {$info['file_name']}\n";
