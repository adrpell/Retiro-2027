<?php

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}


function rt2027_front_controller(): string {
    return defined('RT2027_FRONT_CONTROLLER') ? RT2027_FRONT_CONTROLLER : 'index.php';
}

function route_url(?string $route = null, array $params = [], ?string $frontController = null): string {
    $frontController = $frontController ?: rt2027_front_controller();
    if ($route !== null && $route !== '') {
        $params = array_merge(['route' => $route], $params);
    }
    $query = http_build_query($params);
    return $frontController . ($query !== '' ? '?' . $query : '');
}

function redirect_to(string $route, array $params = [], ?string $frontController = null): void {
    header('Location: ' . route_url($route, $params, $frontController));
    exit;
}

function money_br(float|int|string $value): string {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}

function parse_money_to_float(string $value): float {
    $clean = str_replace(['R$', '.', ' '], '', $value);
    $clean = str_replace(',', '.', $clean);
    return (float)$clean;
}

function normalize_phone(string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) === 11) {
        return sprintf('(%s) %s-%s', substr($digits,0,2), substr($digits,2,5), substr($digits,7,4));
    }
    if (strlen($digits) === 10) {
        return sprintf('(%s) %s-%s', substr($digits,0,2), substr($digits,2,4), substr($digits,6,4));
    }
    return $phone;
}

function age_band($age): string {
    if ($age === null || $age === '') return 'Sem idade';
    $age = (int)$age;
    if ($age <= 2) return 'Cortesia (0-2)';
    if ($age <= 9) return 'Infantil (3-9)';
    if ($age <= 17) return 'Juvenil (10-17)';
    if ($age <= 59) return 'Adulto (18-59)';
    return 'Sênior (60+)';
}

function is_child($age): bool {
    return $age !== null && $age !== '' && (int)$age <= 9;
}

function is_elderly($age): bool {
    return $age !== null && $age !== '' && (int)$age >= 60;
}

function generate_access_code(PDO $pdo): string {
    // Mantido por compatibilidade com trechos legados.
    // Para novos cadastros, prefira generate_access_code_from_group_id() após o INSERT.
    $nextId = ((int)$pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM groups')->fetchColumn());
    return generate_access_code_from_group_id($nextId);
}

function generate_access_code_from_group_id(int $groupId): string {
    $groupId = max(1, $groupId);
    return 'RET' . str_pad((string)$groupId, 3, '0', STR_PAD_LEFT);
}


function installments_progress(PDO $pdo, int $groupId, int $installments): array {
    $stmt = $pdo->prepare('SELECT COUNT(*) qty, COALESCE(SUM(amount_paid),0) total FROM payments WHERE group_id = ?');
    $stmt->execute([$groupId]);
    $row = $stmt->fetch() ?: ['qty' => 0, 'total' => 0];
    $paidCount = (int)($row['qty'] ?? 0);
    $totalInstallments = max(1, $installments);
    return [
        'total' => $totalInstallments,
        'paid' => min($paidCount, $totalInstallments),
        'remaining' => max(0, $totalInstallments - $paidCount),
        'next' => min($paidCount + 1, $totalInstallments),
        'amount_total_paid' => (float)($row['total'] ?? 0),
    ];
}

function payment_label(string $value): string {
    $labels = [
        'pix' => 'Pix',
        'dinheiro' => 'Dinheiro',
        'cartao' => 'Cartão',
        'transferencia' => 'Transferência',
        'nao_definido' => 'Não definido',
    ];
    return $labels[$value] ?? ucfirst($value);
}

function accommodation_label(string $value): string {
    $labels = [
        'chale' => 'Chalé',
        'alojamento' => 'Alojamento',
        'casa' => 'Dormir em casa',
        'personalizado' => 'Personalizado',
    ];
    return $labels[$value] ?? ucfirst($value);
}

function status_badge_class(string $status): string {
    return match ($status) {
        'confirmado', 'quitado' => 'bg-green-100 text-green-700',
        'espera', 'parcial' => 'bg-amber-100 text-amber-700',
        'cancelado' => 'bg-red-100 text-red-700',
        default => 'bg-slate-100 text-slate-700',
    };
}


function rt2027_favicon_links(): string {
    $files = [
        ['file' => 'assets/images/favicon.ico', 'rel' => 'icon', 'type' => 'image/x-icon'],
        ['file' => 'assets/images/favicon.png', 'rel' => 'icon', 'type' => 'image/png'],
        ['file' => 'assets/images/apple-touch-icon.png', 'rel' => 'apple-touch-icon', 'type' => 'image/png'],
    ];
    $html = [];
    $root = realpath(__DIR__ . '/..');
    foreach ($files as $item) {
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $item['file']);
        if (!is_file($path)) continue;
        $href = $item['file'] . '?v=' . ((string)@filemtime($path) ?: date('YmdHis'));
        $extra = $item['rel'] === 'apple-touch-icon' ? '' : ' type="' . h($item['type']) . '"';
        $html[] = '<link rel="' . h($item['rel']) . '" href="' . h($href) . '"' . $extra . '>';
    }
    return implode("
  ", $html);
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || strlen($_SESSION['_csrf']) < 32) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function validate_csrf(): void {
    $sessionToken = (string)($_SESSION['_csrf'] ?? '');
    $requestToken = (string)($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

    if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
        http_response_code(419);
        if (function_exists('flash')) {
            flash('error', 'Sua sessão expirou ou o formulário ficou desatualizado. Atualize a página e tente novamente.');
        }
        $back = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($back !== '') {
            header('Location: ' . $back);
            exit;
        }
        exit('Token CSRF inválido. Atualize a página e tente novamente.');
    }
}

function verify_csrf(): void {
    validate_csrf();
}


if (!function_exists('rt2027_parcelamento_status')) {
    function rt2027_parcelamento_status(PDO $pdo): array
    {
        $inicio = new DateTime('2026-04-01');
        $fim = new DateTime('2027-01-31');
        $hoje = new DateTime('today');
        $totalParcelas = (int)setting($pdo, 'max_installments', '10');
        $prazoDia = (int)setting($pdo, 'payment_deadline_day', '10');

        if ($hoje < $inicio) {
            return [
                'periodo' => 'abr/2026 a jan/2027',
                'total' => $totalParcelas,
                'decorridas' => 0,
                'faltantes' => $totalParcelas,
                'mes_atual' => 0,
                'prazo_dia' => $prazoDia,
                'encerrado' => false,
                'nao_iniciado' => true,
                'progresso' => 0,
                'mensagem' => 'O parcelamento começa em abril/2026.',
            ];
        }

        if ($hoje > $fim) {
            return [
                'periodo' => 'abr/2026 a jan/2027',
                'total' => $totalParcelas,
                'decorridas' => $totalParcelas,
                'faltantes' => 0,
                'mes_atual' => $totalParcelas,
                'prazo_dia' => $prazoDia,
                'encerrado' => true,
                'nao_iniciado' => false,
                'progresso' => 100,
                'mensagem' => 'O período regular de parcelamento foi encerrado.',
            ];
        }

        $anos = (int)$hoje->format('Y') - (int)$inicio->format('Y');
        $meses = (int)$hoje->format('n') - (int)$inicio->format('n');
        $decorridas = ($anos * 12) + $meses + 1;
        $decorridas = max(0, min($totalParcelas, $decorridas));
        $faltantes = max(0, $totalParcelas - $decorridas);
        $progresso = $totalParcelas > 0 ? (int)round(($decorridas / $totalParcelas) * 100) : 0;

        return [
            'periodo' => 'abr/2026 a jan/2027',
            'total' => $totalParcelas,
            'decorridas' => $decorridas,
            'faltantes' => $faltantes,
            'mes_atual' => $decorridas,
            'prazo_dia' => $prazoDia,
            'encerrado' => false,
            'nao_iniciado' => false,
            'progresso' => $progresso,
            'mensagem' => "Estamos na {$decorridas}ª janela de pagamento. Restam {$faltantes} parcela(s) possíveis até janeiro/2027.",
        ];
    }
}

if (!function_exists('rt2027_log_activity')) {
    function rt2027_log_activity(PDO $pdo, string $action, string $context, string $message): void
    {
        try {
            $stmt = $pdo->prepare('INSERT INTO activity_logs (action_type, context_label, description, admin_id) VALUES (?, ?, ?, ?)');
            $stmt->execute([$action, $context, $message, $_SESSION['admin_id'] ?? null]);
        } catch (Throwable $e) {
        }
    }
}

if (!function_exists('rt2027_generate_backup')) {
    function rt2027_generate_backup(PDO $pdo, array $config, string $label = 'manual'): array
    {
        $dir = __DIR__ . '/../storage/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $stamp = date('Ymd_His');
        $filename = 'backup_' . $label . '_' . $stamp . '.sql';
        $fullPath = $dir . '/' . $filename;
        $relativePath = 'storage/backups/' . $filename;

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $sql = "-- Backup Retiro 2027
-- Gerado em " . date('d/m/Y H:i:s') . "

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

";
        foreach ($tables as $table) {
            $create = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_ASSOC);
            $createSql = $create['Create Table'] ?? array_values($create)[1] ?? '';
            $sql .= "DROP TABLE IF EXISTS `{$table}`;
" . $createSql . ";

";
            $rows = $pdo->query('SELECT * FROM `' . str_replace('`', '``', $table) . '`');
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $cols = array_map(fn($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row));
                $vals = [];
                foreach ($row as $val) {
                    $vals[] = $val === null ? 'NULL' : $pdo->quote((string)$val);
                }
                $sql .= 'INSERT INTO `' . $table . '` (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $vals) . ");
";
            }
            $sql .= "
";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;
";
        file_put_contents($fullPath, $sql);
        $size = filesize($fullPath) ?: 0;
        try {
            $stmt = $pdo->prepare('INSERT INTO backup_history (file_name, file_path, file_size, backup_type, status, created_by_admin_id) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$filename, $relativePath, $size, $label, 'gerado', $_SESSION['admin_id'] ?? null]);
        } catch (Throwable $e) {
        }
        return ['file_name' => $filename, 'file_path' => $relativePath, 'file_size' => $size];
    }
}


function rt2027_food_meal_types(): array {
    return [
        'cafe' => 'Café da manhã',
        'almoco' => 'Almoço',
        'lanche' => 'Lanche da tarde',
        'janta' => 'Janta',
    ];
}

function rt2027_food_day_label(int $day): string {
    return 'Dia ' . $day;
}

function rt2027_seed_food_meals(PDO $pdo): void {
    $days = max(1, (int)setting($pdo, 'food_meal_days', '4'));
    $start = setting($pdo, 'retreat_start_date', '2027-02-13');
    $startDate = DateTime::createFromFormat('Y-m-d', $start) ?: new DateTime('2027-02-13');
    $times = ['cafe'=>'08:00', 'almoco'=>'12:30', 'lanche'=>'16:00', 'janta'=>'19:30'];
    $labels = rt2027_food_meal_types();
    $registered = (int)$pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn();
    $stmt = $pdo->prepare('INSERT INTO food_meals (retreat_day, meal_type, title, meal_date, meal_time, estimated_people, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
    for ($day=1; $day<=$days; $day++) {
        $date = clone $startDate;
        $date->modify('+' . ($day-1) . ' day');
        foreach ($labels as $type => $label) {
            $check = $pdo->prepare('SELECT id FROM food_meals WHERE retreat_day=? AND meal_type=? LIMIT 1');
            $check->execute([$day, $type]);
            if ($check->fetchColumn()) continue;
            $stmt->execute([$day, $type, $label, $date->format('Y-m-d'), $times[$type] ?? null, $registered, 'planejado']);
        }
    }
}



function rt2027_food_people_basis_options(): array {
    return [
        'inscritos' => 'Inscritos',
        'presentes' => 'Presença real (check-in)',
    ];
}

function rt2027_food_people_count(PDO $pdo, ?string $basis = null): int {
    $basis = $basis ?: setting($pdo, 'food_people_basis', 'inscritos');
    if ($basis === 'presentes') {
        return (int)$pdo->query("SELECT COUNT(*) FROM participants WHERE checkin_status='sim'")->fetchColumn();
    }
    return (int)$pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn();
}

function rt2027_food_refresh_meal_estimates(PDO $pdo, ?string $basis = null): void {
    $basis = $basis ?: setting($pdo, 'food_people_basis', 'inscritos');
    $count = rt2027_food_people_count($pdo, $basis);
    $stmt = $pdo->prepare('UPDATE food_meals SET estimated_people = ?');
    $stmt->execute([$count]);
}

function rt2027_food_seed_sample_data(PDO $pdo): void {
    $menuCount = (int)$pdo->query('SELECT COUNT(*) FROM food_menu_items')->fetchColumn();
    $pantryCount = (int)$pdo->query('SELECT COUNT(*) FROM food_pantry_items')->fetchColumn();
    $purchaseCount = (int)$pdo->query('SELECT COUNT(*) FROM food_purchase_items')->fetchColumn();

    if ($menuCount === 0) {
        $mealMap = [];
        foreach ($pdo->query('SELECT id, retreat_day, meal_type FROM food_meals')->fetchAll() as $row) {
            $mealMap[(int)$row['retreat_day'] . ':' . $row['meal_type']] = (int)$row['id'];
        }
        $items = [
            [1,'cafe','Pão francês',120,'un','Com manteiga e café'],
            [1,'cafe','Café',10,'L',null],
            [1,'almoco','Arroz',18,'kg',null],
            [1,'almoco','Feijão',12,'kg',null],
            [1,'almoco','Frango assado',35,'kg','Sugestão de cardápio base'],
            [1,'lanche','Bolo simples',12,'un',null],
            [1,'lanche','Suco',20,'L',null],
            [1,'janta','Macarronada',20,'kg',null],
            [2,'cafe','Pão de forma',35,'pct',null],
            [2,'almoco','Carne moída',28,'kg',null],
            [2,'janta','Sopa de legumes',45,'L','Boa opção para noite'],
            [3,'almoco','Lasanha',24,'assadeiras',null],
            [4,'almoco','Arroz',16,'kg','Encerramento'],
            [4,'almoco','Strogonoff',30,'kg',null],
        ];
        $stmt = $pdo->prepare('INSERT INTO food_menu_items (meal_id, item_name, quantity_estimate, unit, notes) VALUES (?, ?, ?, ?, ?)');
        foreach ($items as $item) {
            [$day,$type,$name,$qty,$unit,$notes] = $item;
            $mealId = $mealMap[$day . ':' . $type] ?? null;
            if ($mealId) {
                $stmt->execute([$mealId, $name, $qty, $unit, $notes]);
            }
        }
    }

    if ($pantryCount === 0) {
        $pantry = [
            ['Arroz','Grãos','kg',25,15,null,'Despensa principal',''],
            ['Feijão','Grãos','kg',14,10,null,'Despensa principal',''],
            ['Macarrão','Massas','kg',10,8,null,'Despensa principal',''],
            ['Leite','Laticínios','L',18,12,null,'Refrigeração',''],
            ['Café','Bebidas','kg',4,2,null,'Despensa principal',''],
            ['Açúcar','Mercearia','kg',8,5,null,'Despensa principal',''],
            ['Óleo','Mercearia','L',6,4,null,'Despensa principal',''],
            ['Frango','Carnes','kg',12,20,null,'Freezer','Estoque insuficiente'],
            ['Carne moída','Carnes','kg',6,12,null,'Freezer','Estoque insuficiente'],
            ['Pão francês','Padaria','un',40,80,null,'Cozinha','Comprar fresco'],
        ];
        $stmt = $pdo->prepare('INSERT INTO food_pantry_items (item_name, category, unit, quantity_current, minimum_stock, expiration_date, storage_place, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($pantry as $row) {
            $stmt->execute($row);
        }
    }

    if ($purchaseCount === 0) {
        $purchases = [
            [null,'Frango','Carnes',25,'kg','alta',700,'pendente','Para almoço do 1º dia'],
            [null,'Carne moída','Carnes',22,'kg','alta',520,'pendente','Para almoço do 2º dia'],
            [null,'Pão francês','Padaria',120,'un','media',180,'pendente','Primeiro café da manhã'],
            [null,'Legumes variados','Hortifruti',18,'kg','media',240,'pendente','Saladas e acompanhamento'],
            [null,'Suco','Bebidas',24,'L','baixa',190,'pendente','Lanches da tarde'],
        ];
        $stmt = $pdo->prepare('INSERT INTO food_purchase_items (pantry_item_id, item_name, category, quantity_needed, unit, priority_level, estimated_cost, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        foreach ($purchases as $row) {
            $stmt->execute($row);
        }
    }
}

function rt2027_food_overview(PDO $pdo): array {
    $mealsPlanned = (int)$pdo->query('SELECT COUNT(*) FROM food_meals')->fetchColumn();
    $mealsReady = (int)$pdo->query("SELECT COUNT(*) FROM food_meals WHERE status='concluido'")->fetchColumn();
    $pantryCount = (int)$pdo->query('SELECT COUNT(*) FROM food_pantry_items')->fetchColumn();
    $lowStock = (int)$pdo->query('SELECT COUNT(*) FROM food_pantry_items WHERE quantity_current <= minimum_stock')->fetchColumn();
    $shoppingPending = (int)$pdo->query("SELECT COUNT(*) FROM food_purchase_items WHERE status <> 'comprado'")->fetchColumn();
    $estimatedCost = (float)$pdo->query('SELECT COALESCE(SUM(estimated_cost),0) FROM food_purchase_items')->fetchColumn();
        $basis = setting($pdo, 'food_people_basis', 'inscritos');
    $peopleBasisLabel = rt2027_food_people_basis_options()[$basis] ?? 'Inscritos';
    $peopleCount = rt2027_food_people_count($pdo, $basis);
    $restrictionSummary = rt2027_food_restriction_summary($pdo);
    $restrictionPeople = array_sum(array_map(fn($item) => (int)$item['count'], $restrictionSummary));
    $requirements = rt2027_food_required_ingredients($pdo);
    $autoShortages = count(array_filter($requirements, fn($item) => (float)$item['shortage'] > 0));
    return compact('mealsPlanned','mealsReady','pantryCount','lowStock','shoppingPending','estimatedCost','basis','peopleBasisLabel','peopleCount','restrictionSummary','restrictionPeople','autoShortages');
}


function rt2027_food_restriction_options(): array {
    return [
        'vegetariano' => 'Vegetariano',
        'vegano' => 'Vegano',
        'sem_lactose' => 'Sem lactose',
        'sem_gluten' => 'Sem glúten',
        'diabetico' => 'Diabético',
        'alergia' => 'Alergia alimentar',
        'outro' => 'Outra observação',
    ];
}

function rt2027_food_parse_restrictions(?string $value): array {
    $value = trim((string)$value);
    if ($value === '') return [];
    $parts = preg_split('/[,;
]+/', $value) ?: [];
    return array_values(array_filter(array_map(fn($item) => trim(mb_strtolower($item)), $parts)));
}

function rt2027_food_restriction_summary(PDO $pdo): array {
    $rows = $pdo->query("SELECT full_name, dietary_notes FROM participants WHERE dietary_notes IS NOT NULL AND TRIM(dietary_notes) <> '' ORDER BY full_name ASC")->fetchAll();
    $summary = [];
    foreach ($rows as $row) {
        foreach (rt2027_food_parse_restrictions($row['dietary_notes'] ?? '') as $tag) {
            if (!isset($summary[$tag])) {
                $summary[$tag] = ['count' => 0, 'names' => []];
            }
            $summary[$tag]['count']++;
            $summary[$tag]['names'][] = $row['full_name'];
        }
    }
    uasort($summary, fn($a,$b) => $b['count'] <=> $a['count']);
    return $summary;
}

function rt2027_food_required_ingredients(PDO $pdo): array {
    $sql = "SELECT ing.id, ing.menu_item_id, ing.pantry_item_id, ing.quantity_base, ing.unit, ing.consumption_mode, ing.notes,
                   mi.item_name AS menu_item_name, m.id AS meal_id, m.retreat_day, m.meal_type, m.title AS meal_title, m.estimated_people,
                   p.item_name AS pantry_item_name, p.category, p.quantity_current, p.minimum_stock, p.unit AS pantry_unit
            FROM food_menu_item_ingredients ing
            JOIN food_menu_items mi ON mi.id = ing.menu_item_id
            JOIN food_meals m ON m.id = mi.meal_id
            JOIN food_pantry_items p ON p.id = ing.pantry_item_id
            ORDER BY m.retreat_day ASC, FIELD(m.meal_type,'cafe','almoco','lanche','janta') ASC, mi.item_name ASC";
    $rows = $pdo->query($sql)->fetchAll() ?: [];
    $grouped = [];
    foreach ($rows as $row) {
        $pantryId = (int)$row['pantry_item_id'];
        $required = ((string)$row['consumption_mode'] === 'per_person')
            ? ((float)$row['quantity_base'] * (int)$row['estimated_people'])
            : (float)$row['quantity_base'];
        if (!isset($grouped[$pantryId])) {
            $grouped[$pantryId] = [
                'pantry_item_id' => $pantryId,
                'item_name' => $row['pantry_item_name'],
                'category' => $row['category'],
                'unit' => $row['unit'] ?: $row['pantry_unit'],
                'quantity_current' => (float)$row['quantity_current'],
                'minimum_stock' => (float)$row['minimum_stock'],
                'required_total' => 0.0,
                'shortage' => 0.0,
                'details' => [],
            ];
        }
        $grouped[$pantryId]['required_total'] += $required;
        $grouped[$pantryId]['details'][] = [
            'meal' => rt2027_food_day_label((int)$row['retreat_day']) . ' · ' . (rt2027_food_meal_types()[$row['meal_type']] ?? $row['meal_type']) . ' · ' . $row['meal_title'],
            'menu_item' => $row['menu_item_name'],
            'required' => $required,
            'unit' => $row['unit'] ?: $row['pantry_unit'],
            'mode' => $row['consumption_mode'],
            'estimated_people' => (int)$row['estimated_people'],
        ];
    }
    foreach ($grouped as &$item) {
        $item['shortage'] = max(0, $item['required_total'] - $item['quantity_current']);
    }
    unset($item);
    return array_values($grouped);
}

function rt2027_food_purchase_auto_generate(PDO $pdo): int {
    $requirements = rt2027_food_required_ingredients($pdo);
    $activeRefs = [];
    $count = 0;
    foreach ($requirements as $req) {
        if ($req['shortage'] <= 0) continue;
        $sourceRef = 'pantry:' . (int)$req['pantry_item_id'];
        $activeRefs[] = $sourceRef;
        $notes = 'Gerado automaticamente com base no consumo previsto do cardápio.';
        $estimatedCost = 0;
        $priority = $req['shortage'] > $req['minimum_stock'] ? 'alta' : 'media';
        $existingStmt = $pdo->prepare("SELECT id FROM food_purchase_items WHERE auto_generated=1 AND source_ref=? AND status <> 'comprado' LIMIT 1");
        $existingStmt->execute([$sourceRef]);
        $existingId = (int)$existingStmt->fetchColumn();
        if ($existingId > 0) {
            $pdo->prepare("UPDATE food_purchase_items SET item_name=?, category=?, quantity_needed=?, unit=?, priority_level=?, estimated_cost=?, status='pendente', notes=? WHERE id=?")
                ->execute([$req['item_name'], $req['category'], $req['shortage'], $req['unit'], $priority, $estimatedCost, $notes, $existingId]);
        } else {
            $pdo->prepare("INSERT INTO food_purchase_items (pantry_item_id, item_name, category, quantity_needed, unit, priority_level, estimated_cost, status, notes, auto_generated, source_ref) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente', ?, 1, ?)")
                ->execute([$req['pantry_item_id'], $req['item_name'], $req['category'], $req['shortage'], $req['unit'], $priority, $estimatedCost, $notes, $sourceRef]);
        }
        $count++;
    }
    $autoRows = $pdo->query("SELECT id, source_ref FROM food_purchase_items WHERE auto_generated=1 AND status <> 'comprado'")->fetchAll() ?: [];
    foreach ($autoRows as $row) {
        if (!in_array($row['source_ref'], $activeRefs, true)) {
            $pdo->prepare('DELETE FROM food_purchase_items WHERE id=?')->execute([$row['id']]);
        }
    }
    return $count;
}


function rt2027_food_get_meal(PDO $pdo, int $mealId): ?array {
    $stmt = $pdo->prepare('SELECT * FROM food_meals WHERE id=? LIMIT 1');
    $stmt->execute([$mealId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function rt2027_food_expected_people(array $meal): int {
    $executed = isset($meal['executed_people']) && $meal['executed_people'] !== null ? (int)$meal['executed_people'] : null;
    return $executed !== null && $executed > 0 ? $executed : (int)($meal['estimated_people'] ?? 0);
}

function rt2027_food_planned_quantity(array $row): float {
    return ((string)($row['consumption_mode'] ?? 'fixed') === 'per_person')
        ? ((float)($row['quantity_base'] ?? 0) * (int)($row['estimated_people'] ?? 0))
        : (float)($row['quantity_base'] ?? 0);
}

function rt2027_food_executed_quantity(array $row): float {
    if (isset($row['actual_quantity_used']) && $row['actual_quantity_used'] !== null && $row['actual_quantity_used'] !== '') {
        return (float)$row['actual_quantity_used'];
    }
    $people = isset($row['executed_people']) && $row['executed_people'] !== null && (int)$row['executed_people'] > 0
        ? (int)$row['executed_people']
        : (int)($row['estimated_people'] ?? 0);
    return ((string)($row['consumption_mode'] ?? 'fixed') === 'per_person')
        ? ((float)($row['quantity_base'] ?? 0) * $people)
        : (float)($row['quantity_base'] ?? 0);
}

function rt2027_food_apply_meal_stock(PDO $pdo, int $mealId): array {
    $meal = rt2027_food_get_meal($pdo, $mealId);
    if (!$meal) {
        return ['applied' => false, 'reason' => 'not_found', 'items' => 0];
    }
    if (($meal['status'] ?? '') !== 'concluido') {
        return ['applied' => false, 'reason' => 'not_completed', 'items' => 0];
    }
    if (!empty($meal['stock_applied_at']) && empty($meal['stock_reversed_at'])) {
        return ['applied' => false, 'reason' => 'already_applied', 'items' => 0];
    }

    $sql = "SELECT ing.*, p.quantity_current, p.unit AS pantry_unit, p.item_name AS pantry_item_name, mi.item_name AS menu_item_name, m.estimated_people, m.executed_people
            FROM food_menu_item_ingredients ing
            JOIN food_menu_items mi ON mi.id = ing.menu_item_id
            JOIN food_meals m ON m.id = mi.meal_id
            JOIN food_pantry_items p ON p.id = ing.pantry_item_id
            WHERE m.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$mealId]);
    $rows = $stmt->fetchAll() ?: [];
    if (!$rows) {
        $pdo->prepare('UPDATE food_meals SET stock_applied_at=NOW(), stock_reversed_at=NULL WHERE id=?')->execute([$mealId]);
        return ['applied' => true, 'reason' => 'no_ingredients', 'items' => 0];
    }

    $pdo->beginTransaction();
    try {
        $count = 0;
        foreach ($rows as $row) {
            $required = rt2027_food_executed_quantity($row);
            $newQty = max(0, (float)$row['quantity_current'] - $required);
            $pdo->prepare('UPDATE food_pantry_items SET quantity_current=? WHERE id=?')->execute([$newQty, (int)$row['pantry_item_id']]);
            $notes = 'Baixa automática da refeição: ' . ($meal['title'] ?? 'Refeição') . ' · item do cardápio: ' . ($row['menu_item_name'] ?? '');
            $pdo->prepare('INSERT INTO food_stock_movements (pantry_item_id, meal_id, movement_type, quantity, unit, notes) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([(int)$row['pantry_item_id'], $mealId, 'saida', $required, $row['unit'] ?: $row['pantry_unit'], $notes]);
            $count++;
        }
        $pdo->prepare('UPDATE food_meals SET stock_applied_at=NOW(), stock_reversed_at=NULL WHERE id=?')->execute([$mealId]);
        $pdo->commit();
        return ['applied' => true, 'reason' => 'ok', 'items' => $count];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['applied' => false, 'reason' => 'error', 'items' => 0, 'message' => $e->getMessage()];
    }
}

function rt2027_food_revert_meal_stock(PDO $pdo, int $mealId): array {
    $meal = rt2027_food_get_meal($pdo, $mealId);
    if (!$meal) return ['reverted' => false, 'reason' => 'not_found', 'items' => 0];
    if (empty($meal['stock_applied_at'])) return ['reverted' => false, 'reason' => 'not_applied', 'items' => 0];
    if (!empty($meal['stock_reversed_at'])) return ['reverted' => false, 'reason' => 'already_reverted', 'items' => 0];
    $rows = $pdo->prepare("SELECT pantry_item_id, unit, SUM(quantity) quantity FROM food_stock_movements WHERE meal_id=? AND movement_type='saida' GROUP BY pantry_item_id, unit");
    $rows->execute([$mealId]);
    $movements = $rows->fetchAll() ?: [];
    $pdo->beginTransaction();
    try {
        $count = 0;
        foreach ($movements as $row) {
            $pdo->prepare('UPDATE food_pantry_items SET quantity_current = quantity_current + ? WHERE id=?')->execute([(float)$row['quantity'], (int)$row['pantry_item_id']]);
            $notes = 'Reversão manual da baixa da refeição: ' . ($meal['title'] ?? 'Refeição');
            $pdo->prepare('INSERT INTO food_stock_movements (pantry_item_id, meal_id, movement_type, quantity, unit, notes) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([(int)$row['pantry_item_id'], $mealId, 'reversao', (float)$row['quantity'], $row['unit'], $notes]);
            $count++;
        }
        $pdo->prepare('UPDATE food_meals SET stock_reversed_at=NOW() WHERE id=?')->execute([$mealId]);
        $pdo->commit();
        return ['reverted' => true, 'reason' => 'ok', 'items' => $count];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return ['reverted' => false, 'reason' => 'error', 'items' => 0, 'message' => $e->getMessage()];
    }
}

function rt2027_food_stock_history(PDO $pdo, int $limit = 200): array {
    $limit = max(10, min($limit, 1000));
    $sql = "SELECT sm.*, p.item_name AS pantry_item_name, m.title AS meal_title, m.retreat_day, m.meal_type
            FROM food_stock_movements sm
            JOIN food_pantry_items p ON p.id = sm.pantry_item_id
            LEFT JOIN food_meals m ON m.id = sm.meal_id
            ORDER BY sm.created_at DESC, sm.id DESC
            LIMIT {$limit}";
    try { return $pdo->query($sql)->fetchAll() ?: []; } catch (Throwable $e) { return []; }
}

function rt2027_food_report_rows(PDO $pdo, ?int $day = null, ?string $mealType = null): array {
    $sql = "SELECT m.*, mi.id AS menu_item_id, mi.item_name AS menu_item_name, mi.quantity_estimate, mi.unit AS menu_unit,
                   p.item_name AS pantry_item_name, p.unit AS pantry_unit, ing.quantity_base, ing.actual_quantity_used, ing.consumption_mode,
                   COALESCE(ing.unit, p.unit) AS ingredient_unit
            FROM food_meals m
            LEFT JOIN food_menu_items mi ON mi.meal_id = m.id
            LEFT JOIN food_menu_item_ingredients ing ON ing.menu_item_id = mi.id
            LEFT JOIN food_pantry_items p ON p.id = ing.pantry_item_id
            WHERE 1=1";
    $params = [];
    if ($day !== null) { $sql .= " AND m.retreat_day = ?"; $params[] = $day; }
    if ($mealType !== null && $mealType !== '') { $sql .= " AND m.meal_type = ?"; $params[] = $mealType; }
    $sql .= " ORDER BY m.retreat_day ASC, FIELD(m.meal_type,'cafe','almoco','lanche','janta') ASC, mi.item_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll() ?: [];
    foreach ($rows as &$row) {
        $row['planned_quantity'] = $row['quantity_base'] !== null ? rt2027_food_planned_quantity($row) : null;
        $row['executed_quantity'] = $row['quantity_base'] !== null ? rt2027_food_executed_quantity($row) : null;
        $row['quantity_diff'] = ($row['planned_quantity'] !== null && $row['executed_quantity'] !== null) ? ((float)$row['executed_quantity'] - (float)$row['planned_quantity']) : null;
    }
    unset($row);
    return $rows;
}

function rt2027_food_report_summary(array $rows): array {
    $summary = [
        'meals' => 0,
        'days' => [],
        'planned_people' => 0,
        'executed_people' => 0,
        'ingredients' => 0,
        'planned_total' => 0.0,
        'executed_total' => 0.0,
    ];
    $meals = [];
    foreach ($rows as $row) {
        $mealKey = (int)$row['id'];
        if (!isset($meals[$mealKey])) {
            $meals[$mealKey] = true;
            $summary['meals']++;
            $summary['days'][(int)$row['retreat_day']] = true;
            $summary['planned_people'] += (int)($row['estimated_people'] ?? 0);
            $summary['executed_people'] += isset($row['executed_people']) && $row['executed_people'] !== null ? (int)$row['executed_people'] : (int)($row['estimated_people'] ?? 0);
        }
        if (!empty($row['pantry_item_name'])) $summary['ingredients']++;
        $summary['planned_total'] += (float)($row['planned_quantity'] ?? 0);
        $summary['executed_total'] += (float)($row['executed_quantity'] ?? 0);
    }
    $summary['days'] = count($summary['days']);
    $summary['diff_total'] = $summary['executed_total'] - $summary['planned_total'];
    return $summary;
}

function rt2027_food_report_grouped(array $rows): array {
    $grouped = [];
    foreach ($rows as $row) {
        $mealId = (int)$row['id'];
        if (!isset($grouped[$mealId])) {
            $grouped[$mealId] = [
                'id' => $mealId,
                'day' => (int)$row['retreat_day'],
                'meal_type' => (string)$row['meal_type'],
                'title' => (string)$row['title'],
                'meal_date' => (string)($row['meal_date'] ?? ''),
                'meal_time' => (string)($row['meal_time'] ?? ''),
                'status' => (string)($row['status'] ?? 'planejado'),
                'responsible_name' => (string)($row['responsible_name'] ?? ''),
                'estimated_people' => (int)($row['estimated_people'] ?? 0),
                'executed_people' => isset($row['executed_people']) && $row['executed_people'] !== null ? (int)$row['executed_people'] : null,
                'notes' => (string)($row['notes'] ?? ''),
                'items' => [],
                'planned_total' => 0.0,
                'executed_total' => 0.0,
            ];
        }
        if (!empty($row['menu_item_name'])) {
            $grouped[$mealId]['items'][] = [
                'menu_item_name' => (string)$row['menu_item_name'],
                'pantry_item_name' => (string)($row['pantry_item_name'] ?? ''),
                'ingredient_unit' => (string)($row['ingredient_unit'] ?? ''),
                'consumption_mode' => (string)($row['consumption_mode'] ?? ''),
                'planned_quantity' => $row['planned_quantity'] !== null ? (float)$row['planned_quantity'] : null,
                'executed_quantity' => $row['executed_quantity'] !== null ? (float)$row['executed_quantity'] : null,
                'quantity_diff' => $row['quantity_diff'] !== null ? (float)$row['quantity_diff'] : null,
            ];
            $grouped[$mealId]['planned_total'] += (float)($row['planned_quantity'] ?? 0);
            $grouped[$mealId]['executed_total'] += (float)($row['executed_quantity'] ?? 0);
        }
    }
    return array_values($grouped);
}

function rt2027_food_report_sort_meals(array $meals): array {
    usort($meals, function($a,$b){
        return [$a['day'], array_search($a['meal_type'], array_keys(rt2027_food_meal_types()), true), $a['title']] <=> [$b['day'], array_search($b['meal_type'], array_keys(rt2027_food_meal_types()), true), $b['title']];
    });
    return $meals;
}

function rt2027_food_export_html_path(PDO $pdo, string $html): array {
    $dir = __DIR__ . '/../storage/reports';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $fileName = 'food_report_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.html';
    $fullPath = $dir . '/' . $fileName;
    file_put_contents($fullPath, $html);
    return ['file_name'=>$fileName, 'file_path'=>'storage/reports/' . $fileName];
}

function rt2027_food_movement_badge_class(string $type): string {
    return match($type) {
        'entrada' => 'bg-blue-100 text-blue-700',
        'reversao' => 'bg-amber-100 text-amber-800',
        default => 'bg-emerald-100 text-emerald-700',
    };
}

function rt2027_food_menu_suggestions(PDO $pdo): array {
    $summary = rt2027_food_restriction_summary($pdo);
    $suggestions = [];
    if (isset($summary['vegetariano']) || isset($summary['vegano'])) {
        $suggestions[] = ['title' => 'Opção vegetariana/vegana', 'text' => 'Inclua pelo menos uma preparação sem carne em almoço e jantar, como legumes assados, estrogonofe de grão-de-bico, molho de tomate reforçado ou sopa de legumes.'];
    }
    if (isset($summary['sem_lactose'])) {
        $suggestions[] = ['title' => 'Atenção à lactose', 'text' => 'Planeje bebidas sem leite e uma sobremesa ou lanche sem lactose. Identifique claramente requeijão, leite, creme de leite e queijos.'];
    }
    if (isset($summary['sem_gluten'])) {
        $suggestions[] = ['title' => 'Atenção ao glúten', 'text' => 'Separe uma alternativa segura para pães, bolos e massas. Evite contaminação cruzada em utensílios e superfícies.'];
    }
    if (isset($summary['diabetico'])) {
        $suggestions[] = ['title' => 'Opções com menos açúcar', 'text' => 'Disponibilize frutas, sucos sem açúcar e ao menos uma sobremesa simples sem adição de açúcar.'];
    }
    if (isset($summary['alergia'])) {
        $suggestions[] = ['title' => 'Identificação de alergias', 'text' => 'Revise ingredientes com amendoim, leite, ovo e glúten, e sinalize os pratos com maior risco alérgico.'];
    }
    if (!$suggestions) {
        $suggestions[] = ['title' => 'Cardápio equilibrado', 'text' => 'Mantenha proteínas, carboidratos, saladas, frutas e bebidas em equilíbrio ao longo dos quatro dias.'];
    }
    return $suggestions;
}

function rt2027_food_default_categories(): array {
    return [
        ['name' => 'Grãos', 'slug' => 'graos', 'sort_order' => 10],
        ['name' => 'Carnes', 'slug' => 'carnes', 'sort_order' => 20],
        ['name' => 'Laticínios', 'slug' => 'laticinios', 'sort_order' => 30],
        ['name' => 'Hortifruti', 'slug' => 'hortifruti', 'sort_order' => 40],
        ['name' => 'Bebidas', 'slug' => 'bebidas', 'sort_order' => 50],
        ['name' => 'Padaria', 'slug' => 'padaria', 'sort_order' => 60],
        ['name' => 'Massas', 'slug' => 'massas', 'sort_order' => 70],
        ['name' => 'Mercearia', 'slug' => 'mercearia', 'sort_order' => 80],
        ['name' => 'Temperos', 'slug' => 'temperos', 'sort_order' => 90],
        ['name' => 'Congelados', 'slug' => 'congelados', 'sort_order' => 100],
        ['name' => 'Descartáveis', 'slug' => 'descartaveis', 'sort_order' => 110],
        ['name' => 'Limpeza da cozinha', 'slug' => 'limpeza-cozinha', 'sort_order' => 120],
    ];
}

function rt2027_food_seed_categories(PDO $pdo): void {
    $count = 0;
    try { $count = (int)$pdo->query('SELECT COUNT(*) FROM food_categories')->fetchColumn(); } catch (Throwable $e) { $count = 0; }
    if ($count > 0) return;
    $stmt = $pdo->prepare('INSERT INTO food_categories (name, slug, sort_order, is_active) VALUES (?, ?, ?, 1)');
    foreach (rt2027_food_default_categories() as $row) {
        try { $stmt->execute([$row['name'], $row['slug'], $row['sort_order']]); } catch (Throwable $e) {}
    }
}

function rt2027_food_categories(PDO $pdo, bool $onlyActive = true): array {
    $sql = 'SELECT * FROM food_categories';
    if ($onlyActive) $sql .= ' WHERE is_active = 1';
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    try { return $pdo->query($sql)->fetchAll() ?: []; } catch (Throwable $e) { return []; }
}

function rt2027_food_category_map(PDO $pdo): array {
    $map = [];
    foreach (rt2027_food_categories($pdo, false) as $row) {
        $map[(int)$row['id']] = $row;
    }
    return $map;
}

function rt2027_food_find_category_by_name(PDO $pdo, ?string $name): ?array {
    $name = trim((string)$name);
    if ($name === '') return null;
    $stmt = $pdo->prepare('SELECT * FROM food_categories WHERE LOWER(name) = LOWER(?) LIMIT 1');
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    return $row ?: null;
}

if (!function_exists('rt2027_format_quantity')) {
    function rt2027_format_quantity($value, ?string $unit = null): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $unit = strtolower(trim((string)$unit));
        $number = (float)$value;

        // Unidades inteiras
        $integerUnits = ['un', 'und', 'unidade', 'unidades', 'cx', 'caixa', 'caixas', 'pct', 'pacote', 'pacotes'];

        // Unidades que normalmente aceitam decimal
        $decimalUnits = ['kg', 'g', 'l', 'lt', 'litro', 'litros', 'ml'];

        if (in_array($unit, $integerUnits, true)) {
            return number_format(round($number), 0, ',', '.');
            rt2027_format_quantity($row['planned_quantity'], $row['ingredient_unit']);
        }

        if (in_array($unit, $decimalUnits, true)) {
            // Se for número inteiro, mostra sem casas
            if (floor($number) == $number) {
                return number_format($number, 0, ',', '.');
                rt2027_format_quantity($row['planned_quantity'], $row['ingredient_unit']);
            }

            // Se tiver fração, mostra até 3 casas sem zeros desnecessários
            $formatted = number_format($number, 3, ',', '.');
            $formatted = rtrim($formatted, '0');
            $formatted = rtrim($formatted, ',');
            rt2027_format_quantity($row['planned_quantity'], $row['ingredient_unit']);

            return $formatted;
        }

        // Padrão: sem casas se inteiro, senão até 3 casas
        if (floor($number) == $number) {
            return number_format($number, 0, ',', '.');
            rt2027_format_quantity($row['planned_quantity'], $row['ingredient_unit']);
        }

        $formatted = number_format($number, 3, ',', '.');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, ',');
        rt2027_format_quantity($row['planned_quantity'], $row['ingredient_unit']);

        return $formatted;
    }
}