<?php

define('RT2027_ROOT', realpath(__DIR__ . '/..'));

$configFile = __DIR__ . '/../config/config.php';
if (!file_exists($configFile)) {
    header('Location: install.php');
    exit;
}

$config = require $configFile;
$sessionName = $config['session_name'] ?? 'rt2027_session';
$httpsEnabled = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443);
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $httpsEnabled,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
}
session_name($sessionName);
session_start();
if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) < 32) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(16));
}
if (!empty($_SESSION['admin_id']) && empty($_SESSION['_session_regenerated'])) {
    session_regenerate_id(true);
    $_SESSION['_session_regenerated'] = 1;
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) < 32) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/MigrationRunner.php';
require_once __DIR__ . '/modules/registration.php';
require_once __DIR__ . '/modules/tasks.php';
require_once __DIR__ . '/services/FinancialService.php';
require_once __DIR__ . '/services/TaskService.php';
require_once __DIR__ . '/services/RegistrationService.php';
require_once __DIR__ . '/services/ReportService.php';
require_once __DIR__ . '/services/CheckinService.php';


function rt2027_log_bootstrap_error(string $message): void {
    $dir = RT2027_ROOT . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/bootstrap.log', '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}

function rt2027_table_exists_safe(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

$pdo = Database::connect($config);
try {
    MigrationRunner::runPending($pdo, RT2027_ROOT . '/database/migrations');
} catch (Throwable $e) {
    rt2027_log_bootstrap_error('MigrationRunner: ' . $e->getMessage());
}


function setting(PDO $pdo, string $key, $default = null) {
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        $cache[$key] = $value !== false ? $value : $default;
    } catch (Throwable $e) {
        rt2027_log_bootstrap_error('setting(' . $key . '): ' . $e->getMessage());
        $cache[$key] = $default;
    }
    return $cache[$key];
}

function set_setting(PDO $pdo, string $key, string $value): void {
    $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $stmt->execute([$key, $value]);
}


if (rt2027_table_exists_safe($pdo, 'food_meals') && rt2027_table_exists_safe($pdo, 'participants')) {
    try { rt2027_seed_food_meals($pdo); } catch (Throwable $e) { rt2027_log_bootstrap_error('rt2027_seed_food_meals: ' . $e->getMessage()); }
    try { rt2027_food_refresh_meal_estimates($pdo); } catch (Throwable $e) { rt2027_log_bootstrap_error('rt2027_food_refresh_meal_estimates: ' . $e->getMessage()); }
}
if (rt2027_table_exists_safe($pdo, 'food_categories')) {
    try { rt2027_food_seed_categories($pdo); } catch (Throwable $e) { rt2027_log_bootstrap_error('rt2027_food_seed_categories: ' . $e->getMessage()); }
}
if (rt2027_table_exists_safe($pdo, 'food_menu_items') && rt2027_table_exists_safe($pdo, 'food_pantry_items') && rt2027_table_exists_safe($pdo, 'food_purchase_items')) {
    try { rt2027_food_seed_sample_data($pdo); } catch (Throwable $e) { rt2027_log_bootstrap_error('rt2027_food_seed_sample_data: ' . $e->getMessage()); }
}


function rt2027_root_path(string $relative = ''): string {
    $base = RT2027_ROOT;
    return $relative === '' ? $base : $base . '/' . ltrim(str_replace('\\', '/', $relative), '/');
}

function rt2027_storage_path(string $relative = ''): string {
    $path = rt2027_root_path('storage' . ($relative !== '' ? '/' . ltrim($relative, '/') : ''));
    return $path;
}

function rt2027_template_path(string $relative): string {
    return rt2027_root_path('templates/' . ltrim($relative, '/'));
}


function rt2027_base_url(): string {
    global $config;
    $configured = trim((string)($config['base_url'] ?? ($config['app_url'] ?? '')));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? null) == 443)) ? 'https' : 'http';
    $host = preg_replace('/[^a-z0-9\.:-]/i', '', (string)($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? '/')), '/');
    return $scheme . '://' . $host . ($basePath !== '' ? $basePath : '');
}

function rt2027_build_app_url(string $pathAndQuery = ''): string {
    $base = rt2027_base_url();
    if ($pathAndQuery === '') {
        return $base;
    }
    return $base . '/' . ltrim($pathAndQuery, '/');
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void {
    if (!admin_logged_in()) {
        redirect_to('admin/login');
    }
}

function current_admin(PDO $pdo): ?array {
    if (!admin_logged_in()) return null;
    $stmt = $pdo->prepare('SELECT id, name, email, role FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

function dashboard_preferences(PDO $pdo): array {
    $raw = setting($pdo, 'dashboard_widgets', json_encode([
        'summary_cards' => true,
        'occupancy_cards' => true,
        'accommodation_chart' => true,
        'finance_chart' => true,
        'status_chart' => true,
        'lodging_gender_chart' => true,
        'recent_groups' => true,
        'recent_activity' => true,
    ]));
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function style_settings(PDO $pdo): array {
    return [
        'primary_color' => setting($pdo, 'primary_color', '#4f46e5'),
        'accent_color' => setting($pdo, 'accent_color', '#14b8a6'),
        'surface_color' => setting($pdo, 'surface_color', '#ffffff'),
        'panel_bg_color' => setting($pdo, 'panel_bg_color', '#0f172a'),
        'card_shadow' => setting($pdo, 'card_shadow', '0 18px 45px rgba(15,23,42,.08)'),
        'font_family' => setting($pdo, 'font_family', 'Inter, Arial, sans-serif'),
        'font_size' => setting($pdo, 'font_size', '16'),
        'font_weight' => setting($pdo, 'font_weight', '400'),
        'font_style' => setting($pdo, 'font_style', 'normal'),
    ];
}

function financial_service(PDO $pdo): FinancialService {
    static $instances = [];
    $key = spl_object_id($pdo);
    if (!isset($instances[$key])) {
        $instances[$key] = new FinancialService($pdo);
    }
    return $instances[$key];
}


function registration_service(PDO $pdo): RegistrationService {
    static $instances = [];
    $key = spl_object_id($pdo);
    if (!isset($instances[$key])) {
        $instances[$key] = new RegistrationService($pdo, financial_service($pdo));
    }
    return $instances[$key];
}

function report_service(PDO $pdo): ReportService {
    static $instances = [];
    $key = spl_object_id($pdo);
    if (!isset($instances[$key])) {
        $instances[$key] = new ReportService($pdo);
    }
    return $instances[$key];
}

function task_service(PDO $pdo): TaskService {
    static $instances = [];
    $key = spl_object_id($pdo);
    if (!isset($instances[$key])) {
        $instances[$key] = new TaskService($pdo);
    }
    return $instances[$key];
}



function rt2027_column_exists(PDO $pdo, string $table, string $column): bool {
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function rt2027_ensure_checkin_schema(PDO $pdo): void {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        if (!rt2027_column_exists($pdo, 'participants', 'checkin_status')) {
            $pdo->exec("ALTER TABLE participants ADD COLUMN checkin_status VARCHAR(20) NOT NULL DEFAULT 'nao'");
        }
        if (!rt2027_column_exists($pdo, 'participants', 'checked_in_at')) {
            $pdo->exec("ALTER TABLE participants ADD COLUMN checked_in_at DATETIME NULL AFTER checkin_status");
        }
        if (!rt2027_column_exists($pdo, 'participants', 'checked_in_by_admin_id')) {
            $pdo->exec("ALTER TABLE participants ADD COLUMN checked_in_by_admin_id INT NULL AFTER checked_in_at");
        }
        if (!rt2027_table_exists_safe($pdo, 'checkin_history')) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS checkin_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                participant_id INT NOT NULL,
                group_id INT NOT NULL,
                previous_status VARCHAR(20) NOT NULL DEFAULT 'nao',
                new_status VARCHAR(20) NOT NULL DEFAULT 'nao',
                changed_by_admin_id INT NULL,
                change_source VARCHAR(40) NOT NULL DEFAULT 'manual',
                change_context VARCHAR(60) NULL,
                notes VARCHAR(255) NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_checkin_history_participant (participant_id),
                INDEX idx_checkin_history_group (group_id),
                INDEX idx_checkin_history_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    } catch (Throwable $e) {
        @error_log('[rt2027 checkin schema] ' . $e->getMessage());
    }
}

function checkin_service(PDO $pdo): CheckinService {
    static $instances = [];
    $key = spl_object_id($pdo);
    if (!isset($instances[$key])) {
        $instances[$key] = new CheckinService($pdo);
    }
    return $instances[$key];
}
function calculate_participant_value(PDO $pdo, ?int $age, string $accommodation): float {
    $ageNum = $age !== null ? (int)$age : null;
    $accommodation = trim(strtolower($accommodation));
    if (!in_array($accommodation, ['chale', 'alojamento', 'casa'], true)) {
        $accommodation = 'alojamento';
    }
    if ($ageNum !== null && $ageNum <= 2) {
        return 0.0;
    }
    if ($accommodation === 'chale') {
        if ($ageNum !== null && $ageNum <= 9) {
            return (float)setting($pdo, 'price_child_chale', '350');
        }
        return (float)setting($pdo, 'price_adult_chale', '700');
    }
    if ($ageNum !== null && $ageNum <= 9) {
        return (float)setting($pdo, 'price_child_alojamento', '300');
    }
    return (float)setting($pdo, 'price_adult_alojamento', '600');
}

function recalculate_group_financials(PDO $pdo, int $groupId): void {
    financial_service($pdo)->recalculateGroup($groupId);
}

function available_capacity(PDO $pdo): array {
    $chaletUnits = (int)setting($pdo, 'chalet_units', '10');
    $chaletCap = (int)setting($pdo, 'chalet_capacity_per_unit', '6');
    $lodgingCap = (int)setting($pdo, 'lodging_capacity', '80');
    $homeCap = 999999;

    $stmt = $pdo->query("SELECT accommodation_choice, COUNT(*) total FROM participants GROUP BY accommodation_choice");
    $occupied = ['chale' => 0, 'alojamento' => 0, 'casa' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $occupied[$row['accommodation_choice']] = (int)$row['total'];
    }
    return [
        'chale_total' => $chaletUnits * $chaletCap,
        'chale_occupied' => $occupied['chale'] ?? 0,
        'chale_free' => ($chaletUnits * $chaletCap) - ($occupied['chale'] ?? 0),
        'alojamento_total' => $lodgingCap,
        'alojamento_occupied' => $occupied['alojamento'] ?? 0,
        'alojamento_free' => $lodgingCap - ($occupied['alojamento'] ?? 0),
        'casa_occupied' => $occupied['casa'] ?? 0,
    ];
}


function lodging_zone_label(?string $sex): string {
    return strtoupper((string)$sex) === 'F' ? 'Alojamento feminino' : (strtoupper((string)$sex) === 'M' ? 'Alojamento masculino' : 'Alojamento indefinido');
}

function lodging_gender_capacity(PDO $pdo): array {
    $maleCap = (int)setting($pdo, 'lodging_male_capacity', '40');
    $femaleCap = (int)setting($pdo, 'lodging_female_capacity', '40');
    $stmt = $pdo->query("SELECT UPPER(COALESCE(sex,'')) sex, COUNT(*) total FROM participants WHERE accommodation_choice='alojamento' GROUP BY UPPER(COALESCE(sex,''))");
    $occupied = ['M' => 0, 'F' => 0, '' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $occupied[$row['sex']] = (int)$row['total'];
    }
    return [
        'male_total' => $maleCap,
        'male_occupied' => $occupied['M'] ?? 0,
        'male_free' => $maleCap - ($occupied['M'] ?? 0),
        'female_total' => $femaleCap,
        'female_occupied' => $occupied['F'] ?? 0,
        'female_free' => $femaleCap - ($occupied['F'] ?? 0),
        'undefined_occupied' => $occupied[''] ?? 0,
    ];
}


function report_sort_sql(string $sortBy, string $sortDir): string {
    $map = [
        'access_code' => 'g.access_code',
        'responsible_name' => 'g.responsible_name',
        'created_at' => 'g.created_at',
        'suggested_value' => 'g.suggested_value',
    ];
    $col = $map[$sortBy] ?? 'g.access_code';
    $dir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
    return $col . ' ' . $dir . ', g.id ' . $dir . ', p.is_responsible DESC, p.full_name ASC';
}

function save_report_snapshot(PDO $pdo, string $html, string $outputFormat, string $sortBy, string $sortDir, ?string $recipientEmail = null, string $status = 'gerado'): array {
    $storageDir = __DIR__ . '/../storage/reports';
    if (!is_dir($storageDir)) {
        @mkdir($storageDir, 0775, true);
    }
    $stamp = date('Ymd_His');
    $basename = 'relatorio_' . $stamp . '_' . bin2hex(random_bytes(3));
    $filePath = 'storage/reports/' . $basename . '.html';
    file_put_contents(__DIR__ . '/../' . $filePath, $html);
    $stmt = $pdo->prepare('INSERT INTO report_history (report_type, sort_by, sort_dir, output_format, file_path, recipient_email, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(['executive', $sortBy, $sortDir, $outputFormat, $filePath, $recipientEmail, $status]);
    return ['id' => (int)$pdo->lastInsertId(), 'file_path' => $filePath, 'basename' => $basename];
}

function rt2027_sanitize_email_header(string $value): string {
    return trim((string)preg_replace('/[
	]+/', '', $value));
}

function rt2027_build_email_headers(string $from, bool $isHtml = true): string {
    $from = rt2027_sanitize_email_header($from);
    if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = 'noreply@icnvcatedral.local';
    }
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: PHP/' . PHP_VERSION;
    if ($isHtml) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
    }
    return implode("
", $headers);
}

function rt2027_encode_email_body(string $body): string {
    return quoted_printable_encode(str_replace(["
", ""], "
", $body));
}

function send_report_email(string $to, string $subject, string $htmlBody, string $attachmentPath, string $attachmentName, string $from): bool {
    $boundary = 'rt2027_' . md5((string)microtime(true));
    $from = rt2027_sanitize_email_header($from);
    if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = 'noreply@icnvcatedral.local';
    }

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'From: ' . $from;
    $headers[] = 'Reply-To: ' . $from;
    $headers[] = 'X-Mailer: PHP/' . PHP_VERSION;
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $message = '';
    $message .= '--' . $boundary . "
";
    $message .= "Content-Type: text/html; charset=UTF-8
";
    $message .= "Content-Transfer-Encoding: quoted-printable

";
    $message .= rt2027_encode_email_body($htmlBody) . "
";

    if (is_file($attachmentPath)) {
        $attachmentMime = 'text/html';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = @finfo_file($finfo, $attachmentPath);
                if (is_string($detected) && $detected !== '') {
                    $attachmentMime = $detected;
                }
                @finfo_close($finfo);
            }
        }
        $fileData = chunk_split(base64_encode((string)file_get_contents($attachmentPath)));
        $safeAttachmentName = str_replace(['"', "", "
"], '', $attachmentName);
        $message .= '--' . $boundary . "
";
        $message .= 'Content-Type: ' . $attachmentMime . '; name="' . $safeAttachmentName . '"' . "
";
        $message .= "Content-Transfer-Encoding: base64
";
        $message .= 'Content-Disposition: attachment; filename="' . $safeAttachmentName . '"' . "

";
        $message .= $fileData . "
";
    }

    $message .= '--' . $boundary . "--
";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, implode("
", $headers));
}


function send_simple_html_email(string $to, string $subject, string $htmlBody, string $from): bool {
    $headers = rt2027_build_email_headers($from, true);
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', rt2027_encode_email_body($htmlBody), $headers);
}

function build_registration_confirmation_email(PDO $pdo, array $group, array $participants): string {
    $participantRows = '';
    foreach ($participants as $participant) {
        $participantRows .= '<tr>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-weight:600;color:#0f172a;">' . h($participant['full_name']) . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;color:#334155;">' . h($participant['age'] !== null ? $participant['age'] . ' anos' : 'idade não informada') . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;color:#334155;">' . h(accommodation_label((string)$participant['accommodation_choice'])) . '</td>'
            . '<td style="padding:10px 12px;border-bottom:1px solid #e2e8f0;color:#0f766e;font-weight:700;">' . h(money_br((float)$participant['calculated_value'])) . '</td>'
            . '</tr>';
    }

    $eventName = setting($pdo, 'event_name', 'Retiro 2027 - ICNV Catedral');
    $pixKey = setting($pdo, 'pix_key', '03.102.014/0001-04');
    $pixBeneficiary = setting($pdo, 'pix_beneficiary', 'Igreja Cristã Nova Vida Catedral');
    $receiptContact = setting($pdo, 'payment_receipt_contact', 'Diácono Cláudio');

    return '<!DOCTYPE html>'
        . '<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Confirmação da inscrição</title></head>'
        . '<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:720px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">'
        . '<tr><td style="background:#0f766e;padding:24px 28px;color:#ffffff;">'
        . '<div style="font-size:14px;letter-spacing:.06em;text-transform:uppercase;opacity:.9;">Retiro</div>'
        . '<div style="font-size:28px;font-weight:700;margin-top:4px;">Confirmação da inscrição</div>'
        . '</td></tr>'
        . '<tr><td style="padding:28px;">'
        . '<p style="margin:0 0 16px;font-size:16px;">Olá, <strong>' . h($group['responsible_name']) . '</strong>.</p>'
        . '<p style="margin:0 0 20px;font-size:15px;line-height:1.6;">Sua inscrição no <strong>' . h($eventName) . '</strong> foi registrada com sucesso. Guarde este e-mail para consultar ou atualizar sua inscrição futuramente.</p>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;margin:0 0 24px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">'
        . '<tr>'
        . '<td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;"><strong>Código de acesso</strong><br><span style="color:#0f766e;font-size:20px;font-weight:700;">' . h($group['access_code']) . '</span></td>'
        . '<td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;"><strong>Forma de pagamento</strong><br>' . h(payment_label((string)$group['payment_method'])) . '</td>'
        . '</tr>'
        . '<tr>'
        . '<td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;"><strong>E-mail de acesso</strong><br>' . h((string)($group['responsible_email'] ?? '')) . '</td>'
        . '<td style="padding:14px 16px;border-bottom:1px solid #e2e8f0;"><strong>Parcelas</strong><br>' . h((string)$group['installments']) . '</td>'
        . '</tr>'
        . '<tr>'
        . '<td style="padding:14px 16px;"><strong>Telefone</strong><br>' . h((string)($group['responsible_phone'] ?? '')) . '</td>'
        . '<td style="padding:14px 16px;"><strong>Valor sugerido</strong><br><span style="color:#0f766e;font-size:18px;font-weight:700;">' . h(money_br((float)$group['suggested_value'])) . '</span></td>'
        . '</tr>'
        . '</table>'
        . '<h3 style="margin:0 0 12px;font-size:18px;color:#0f172a;">Participantes</h3>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin:0 0 24px;">'
        . '<tr style="background:#f8fafc;">'
        . '<th align="left" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#475569;">Nome</th>'
        . '<th align="left" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#475569;">Idade</th>'
        . '<th align="left" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#475569;">Hospedagem</th>'
        . '<th align="left" style="padding:10px 12px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#475569;">Valor</th>'
        . '</tr>'
        . $participantRows
        . '</table>'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;background:#ecfeff;border:1px solid #99f6e4;border-radius:12px;margin:0 0 12px;">'
        . '<tr><td style="padding:16px 18px;">'
        . '<div style="font-size:16px;font-weight:700;color:#115e59;margin-bottom:8px;">Pagamento via PIX</div>'
        . '<div style="font-size:14px;line-height:1.7;color:#134e4a;">'
        . '<strong>Chave PIX:</strong> ' . h($pixKey) . '<br>'
        . '<strong>Favorecido:</strong> ' . h($pixBeneficiary) . '<br>'
        . '<strong>Comprovantes:</strong> ' . h($receiptContact) . '
'
        . '</div>'
        . '</td></tr></table>'
        . '<p style="margin:20px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Guarde este e-mail. Você poderá acessar sua inscrição futuramente usando o e-mail cadastrado ou o código de acesso informado acima.</p>'
        . '</td></tr>'
        . '</table>'
        . '</body></html>';
}

function create_password_reset(PDO $pdo, string $email): ?string {
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE LOWER(email)=LOWER(?) LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if (!$admin) return null;
    $token = bin2hex(random_bytes(24));
    $hash = password_hash($token, PASSWORD_DEFAULT);
    $expiresAt = date('Y-m-d H:i:s', time() + 3600);
    $pdo->prepare('INSERT INTO password_resets (admin_id, email, token_hash, expires_at) VALUES (?, ?, ?, ?)')->execute([$admin['id'], $admin['email'], $hash, $expiresAt]);
    return $token;
}

function validate_password_reset(PDO $pdo, string $token): ?array {
    $stmt = $pdo->query('SELECT * FROM password_resets WHERE used_at IS NULL AND expires_at >= NOW() ORDER BY id DESC');
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        if (password_verify($token, $row['token_hash'])) return $row;
    }
    return null;
}

function flash(string $key, ?string $message = null): ?string {
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $value;
}


function log_activity(PDO $pdo, ?int $adminId, string $action, string $details = ''): void {
    try {
        $stmt = $pdo->prepare('INSERT INTO activity_logs (admin_id, action, details) VALUES (?, ?, ?)');
        $stmt->execute([$adminId, $action, $details]);
    } catch (Throwable $e) {
        // ignora falhas de log para não quebrar a operação principal
    }
}
