<?php
$handled = true;
switch ($route) {
    case 'entry':
        $handled = true;
        $pageTitle = 'Acesso ao Retiro';
        $showLandingExtras = false;
        include __DIR__ . '/../../templates/public/home.php';
        break;

    case 'home':
        $handled = true;
        $pageTitle = 'Portal do Retiro';
        $showLandingExtras = true;
        include __DIR__ . '/../../templates/public/home.php';
        break;

    case 'register':
        $pageTitle = 'Nova inscrição';
        include __DIR__ . '/../../templates/public/register.php';
        break;

    case 'lookup':
        $pageTitle = 'Consultar inscrição';
        $group = null;
        $participants = [];
        $login = trim((string)($_GET['login'] ?? ($_GET['code'] ?? '')));
        $name = trim((string)($_GET['responsible_name'] ?? ''));
        $paymentsRows = [];
        $paymentProgress = ['total' => 1, 'paid' => 0, 'remaining' => 1, 'next' => 1, 'amount_total_paid' => 0.0];
        if ($login !== '' && (filter_var($login, FILTER_VALIDATE_EMAIL) || $name !== '')) {
            $group = fetch_group_for_lookup($pdo, $login, $name);
            if ($group) {
                $stmt = $pdo->prepare('SELECT * FROM participants WHERE group_id = ? ORDER BY is_responsible DESC, full_name ASC');
                $stmt->execute([$group['id']]);
                $participants = $stmt->fetchAll();
                $stmt = $pdo->prepare('SELECT * FROM payments WHERE group_id = ? ORDER BY payment_date DESC, id DESC');
                $stmt->execute([$group['id']]);
                $paymentsRows = $stmt->fetchAll();
                $paymentProgress = installments_progress($pdo, (int)$group['id'], (int)$group['installments']);
            }
        }
        include __DIR__ . '/../../templates/public/lookup.php';
        break;

    case 'admin/login':
        $pageTitle = 'Login do administrador';
        include __DIR__ . '/../../templates/admin/login.php';
        break;

    case 'admin/forgot-password':
        $pageTitle = 'Recuperar senha';
        include __DIR__ . '/../../templates/admin/forgot_password.php';
        break;

    case 'admin/reset-password':
        $pageTitle = 'Redefinir senha';
        $token = trim((string)($_GET['token'] ?? ($_POST['token'] ?? '')));
        include __DIR__ . '/../../templates/admin/reset_password.php';
        break;

    case 'admin/dashboard':
        require_admin();
        $prefs = dashboard_preferences($pdo);
        $capacity = available_capacity($pdo);
        $lodgingGender = lodging_gender_capacity($pdo);
        $summary = [
            'groups' => (int)$pdo->query('SELECT COUNT(*) FROM groups')->fetchColumn(),
            'participants' => (int)$pdo->query('SELECT COUNT(*) FROM participants')->fetchColumn(),
            'pending_finance' => (float)$pdo->query("SELECT COALESCE(SUM(GREATEST(g.suggested_value - COALESCE(p.paid_total,0),0)),0) FROM groups g LEFT JOIN (SELECT group_id, SUM(amount_paid) paid_total FROM payments GROUP BY group_id) p ON p.group_id = g.id")->fetchColumn(),
            'paid_total' => (float)$pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM payments")->fetchColumn(),
        ];
        $accommodationData = $pdo->query("SELECT CASE accommodation_choice WHEN 'chale' THEN 'Chalé' WHEN 'alojamento' THEN 'Alojamento' ELSE 'Dormir em casa' END label, COUNT(*) total FROM participants GROUP BY accommodation_choice")->fetchAll();
        $statusData = $pdo->query("SELECT status label, COUNT(*) total FROM groups GROUP BY status")->fetchAll();
        $financeData = $pdo->query("SELECT financial_status label, COUNT(*) total FROM groups GROUP BY financial_status")->fetchAll();
        $lodgingGenderData = [
            ['label' => 'Masculino', 'total' => $lodgingGender['male_occupied']],
            ['label' => 'Feminino', 'total' => $lodgingGender['female_occupied']],
        ];
        $recentGroups = $pdo->query("SELECT * FROM groups ORDER BY access_code ASC LIMIT 8")->fetchAll();
        $recentActivity = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 12")->fetchAll();
        include __DIR__ . '/../../templates/admin/dashboard.php';
        break;

    case 'admin/groups':
        require_admin();
        $filters = q_filters(['search','registration_type','group_accommodation','status','financial_status','payment_method']);
        [$sort, $dir] = table_sort('created_at');
        $where = [];
        $params = [];
        if ($filters['search'] !== '') {
            $where[] = '(responsible_name LIKE ? OR access_code LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        foreach (['registration_type','group_accommodation','status','financial_status','payment_method'] as $k) {
            if ($filters[$k] !== '') {
                $where[] = "$k = ?";
                $params[] = $filters[$k];
            }
        }
        $sql = 'SELECT * FROM groups';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY $sort $dir";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $groupsRows = $stmt->fetchAll();
        include __DIR__ . '/../../templates/admin/groups.php';
        break;

    case 'admin/group-edit':
        require_admin();
        $groupId = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM groups WHERE id=? LIMIT 1');
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        if (!$group) { http_response_code(404); echo '<div class="p-6">Inscrição não encontrada.</div>'; break; }
        $stmt = $pdo->prepare('SELECT * FROM participants WHERE group_id=? ORDER BY is_responsible DESC, full_name ASC');
        $stmt->execute([$groupId]);
        $participants = $stmt->fetchAll();
        include __DIR__ . '/../../templates/admin/group_edit.php';
        break;

    case 'admin/participants':
        require_admin();
        $filters = q_filters(['search','sex','age_band','accommodation_choice']);
        [$sort, $dir] = table_sort('full_name');
        $where = [];
        $params = [];
        if ($filters['search'] !== '') {
            $where[] = '(p.full_name LIKE ? OR g.responsible_name LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        foreach (['sex','age_band','accommodation_choice'] as $k) {
            if ($filters[$k] !== '') {
                $where[] = "p.$k = ?";
                $params[] = $filters[$k];
            }
        }
        $sql = 'SELECT p.*, g.access_code, g.responsible_name FROM participants p JOIN groups g ON g.id = p.group_id';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY p.$sort $dir";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $participantsRows = $stmt->fetchAll();
        include __DIR__ . '/../../templates/admin/participants.php';
        break;

    case 'admin/participant-edit':
        require_admin();
        $participantId = (int)($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT p.*, g.access_code, g.responsible_name FROM participants p JOIN groups g ON g.id=p.group_id WHERE p.id=? LIMIT 1');
        $stmt->execute([$participantId]);
        $participant = $stmt->fetch();
        if (!$participant) { http_response_code(404); echo '<div class="p-6">Participante não encontrado.</div>'; break; }
        include __DIR__ . '/../../templates/admin/participant_edit.php';
        break;

    case 'admin/accommodations':
        require_admin();
        $capacity = available_capacity($pdo);
        $lodgingGender = lodging_gender_capacity($pdo);
        $groupsAccommodation = $pdo->query("SELECT group_accommodation, COUNT(*) total FROM groups GROUP BY group_accommodation")->fetchAll();
        include __DIR__ . '/../../templates/admin/accommodations.php';
        break;

    case 'admin/financial':
        require_admin();
        $groupsFinance = $pdo->query("SELECT id, access_code, responsible_name, suggested_value, amount_paid, amount_pending, financial_status, payment_method FROM groups ORDER BY access_code ASC")->fetchAll();
        $paymentsRows = $pdo->query("SELECT p.*, g.access_code, g.responsible_name FROM payments p JOIN groups g ON g.id = p.group_id ORDER BY p.created_at DESC LIMIT 50")->fetchAll();
        include __DIR__ . '/../../templates/admin/financial.php';
        break;

    case 'admin/receipts':
        require_admin();
        $receipts = $pdo->query("SELECT * FROM (
            SELECT CONCAT('group-', g.id) AS row_key, 'inscricao' AS receipt_type, 'Comprovante da inscrição' AS receipt_type_label, g.access_code, g.responsible_name, g.payment_method, g.receipt_file, g.created_at
            FROM groups g
            WHERE g.receipt_file IS NOT NULL AND g.receipt_file <> ''
            UNION ALL
            SELECT CONCAT('payment-', p.id) AS row_key, 'pagamento' AS receipt_type, CONCAT('Pagamento ', p.installment_number, 'ª parcela') AS receipt_type_label, g.access_code, g.responsible_name, p.payment_method, p.receipt_file, p.created_at
            FROM payments p
            JOIN groups g ON g.id = p.group_id
            WHERE p.receipt_file IS NOT NULL AND p.receipt_file <> ''
        ) receipts ORDER BY created_at DESC")->fetchAll();
        include __DIR__ . '/../../templates/admin/receipts.php';
        break;

    case 'admin/checkin':
        require_admin();
        $filters = q_filters(['search','sex','accommodation_choice','checkin_status','arrival_from','arrival_to']);
        try {
            $checkinData = checkin_service($pdo)->getScreenData($filters);
            $checkinRows = $checkinData['rows'];
            $checkinStats = $checkinData['stats'];
            $checkinHistory = $checkinData['history'];
        } catch (Throwable $e) {
            @file_put_contents(__DIR__ . '/../../storage/logs/checkin.log', '[' . date('c') . '] view-fatal: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            $checkinRows = [];
            $checkinStats = ['checked_in' => 0, 'pending' => 0, 'families' => 0, 'total_listed' => 0, 'last_action_at' => null];
            $checkinHistory = [];
            flash('error', 'Não foi possível carregar o check-in. Verifique o log técnico em storage/logs/checkin.log.');
        }
        include __DIR__ . '/../../templates/admin/checkin.php';
        break;

    case 'admin/checkin/history-print':
        require_admin();
        $filters = q_filters(['search','arrival_from','arrival_to']);
        $historyRows = checkin_service($pdo)->recentHistory(2000, $filters);
        include __DIR__ . '/../../templates/admin/checkin_history_print.php';
        break;

    case 'admin/backups':
        require_admin();
        $backupHistory = $pdo->query('SELECT * FROM backup_history ORDER BY created_at DESC, id DESC LIMIT 30')->fetchAll();
        $prefs = dashboard_preferences($pdo);
        include __DIR__ . '/../../templates/admin/backups.php';
        break;

    case 'admin/reports':
        require_admin();
        $stats = [
            'with_receipt' => (int)$pdo->query("SELECT COUNT(*) FROM groups WHERE receipt_file IS NOT NULL AND receipt_file <> ''")->fetchColumn(),
            'family_groups' => (int)$pdo->query("SELECT COUNT(*) FROM groups WHERE registration_type = 'familia'")->fetchColumn(),
            'individual_groups' => (int)$pdo->query("SELECT COUNT(*) FROM groups WHERE registration_type = 'individual'")->fetchColumn(),
        ];
        $reportHistory = $pdo->query("SELECT * FROM report_history ORDER BY created_at DESC, id DESC LIMIT 15")->fetchAll();
        include __DIR__ . '/../../templates/admin/reports.php';
        break;



    case 'admin/food-stock-history':
        require_admin();
        $stockMovements = rt2027_food_stock_history($pdo, 300);
        include __DIR__ . '/../../templates/admin/food_stock_history.php';
        break;

    case 'admin/food-dashboard':
        require_admin();
        $foodOverview = rt2027_food_overview($pdo);
        $todayMeals = $pdo->query("SELECT * FROM food_meals ORDER BY retreat_day ASC, FIELD(meal_type,'cafe','almoco','lanche','janta') ASC")->fetchAll();
        $lowPantry = $pdo->query('SELECT * FROM food_pantry_items WHERE quantity_current <= minimum_stock ORDER BY item_name ASC LIMIT 8')->fetchAll();
        $pendingPurchases = $pdo->query("SELECT * FROM food_purchase_items WHERE status <> 'comprado' ORDER BY FIELD(priority_level,'alta','media','baixa'), item_name ASC LIMIT 10")->fetchAll();
        include __DIR__ . '/../../templates/admin/food_dashboard.php';
        break;

    case 'admin/food-menus':
        require_admin();
        $mealEditId = (int)($_GET['meal_id'] ?? 0);
        $menuItemEditId = (int)($_GET['menu_item_id'] ?? 0);
        $ingredientEditId = (int)($_GET['ingredient_id'] ?? 0);
        $foodMeals = $pdo->query("SELECT * FROM food_meals ORDER BY retreat_day ASC, FIELD(meal_type,'cafe','almoco','lanche','janta') ASC")->fetchAll();
        $menuItemsRows = $pdo->query("SELECT i.*, m.retreat_day, m.meal_type, m.title AS meal_title FROM food_menu_items i JOIN food_meals m ON m.id=i.meal_id ORDER BY m.retreat_day ASC, FIELD(m.meal_type,'cafe','almoco','lanche','janta') ASC, i.item_name ASC")->fetchAll();
        $pantryLookup = $pdo->query('SELECT id, item_name, category, unit FROM food_pantry_items ORDER BY item_name ASC')->fetchAll();
        $ingredientRows = $pdo->query("SELECT ing.*, p.item_name AS pantry_item_name, p.unit AS pantry_unit FROM food_menu_item_ingredients ing JOIN food_pantry_items p ON p.id=ing.pantry_item_id ORDER BY ing.id DESC")->fetchAll();
        $ingredientsByMenu = [];
        foreach ($ingredientRows as $ing) { $ingredientsByMenu[(int)$ing['menu_item_id']][] = $ing; }
        $mealEdit = null; $menuItemEdit = null; $ingredientEdit = null;
        foreach ($foodMeals as $m) { if ((int)$m['id'] === $mealEditId) { $mealEdit = $m; break; } }
        foreach ($menuItemsRows as $mi) { if ((int)$mi['id'] === $menuItemEditId) { $menuItemEdit = $mi; break; } }
        foreach ($ingredientRows as $ir) { if ((int)$ir['id'] === $ingredientEditId) { $ingredientEdit = $ir; break; } }
        include __DIR__ . '/../../templates/admin/food_menus.php';
        break;

    case 'admin/food-pantry':
    require_admin();
    $pantryEditId = (int)($_GET['id'] ?? 0);
    $pantryItems = $pdo->query("
        SELECT p.*, COALESCE(fc.name, p.category) AS category_display
        FROM food_pantry_items p
        LEFT JOIN food_categories fc ON fc.id = p.category_id
        ORDER BY p.item_name ASC
    ")->fetchAll();
    $pantryEdit = null;
    foreach ($pantryItems as $pi) {
        if ((int)$pi['id'] === $pantryEditId) {
            $pantryEdit = $pi;
            break;
        }
    }
    include __DIR__ . '/../../templates/admin/food_pantry.php';
    break;


    case 'admin/food-restrictions':
        require_admin();
        $participantsRows = $pdo->query("SELECT p.*, g.access_code, g.responsible_name FROM participants p JOIN groups g ON g.id=p.group_id ORDER BY p.full_name ASC")->fetchAll();
        $restrictionSummary = rt2027_food_restriction_summary($pdo);
        include __DIR__ . '/../../templates/admin/food_restrictions.php';
        break;
    case 'admin/food-categories':
        require_admin();
        $categoryEditId = (int)($_GET['id'] ?? 0);
        $foodCategories = rt2027_food_categories($pdo, false);
        $categoryEdit = null; foreach ($foodCategories as $fc) { if ((int)$fc['id'] === $categoryEditId) { $categoryEdit = $fc; break; } }
        include __DIR__ . '/../../templates/admin/food_categories.php';
        break;

    case 'admin/food-reports':
        require_admin();
        $reportDay = isset($_GET['day']) && $_GET['day'] !== '' ? (int)$_GET['day'] : null;
        $reportMealType = trim((string)($_GET['meal_type'] ?? '')) ?: null;
        $foodReportRows = rt2027_food_report_rows($pdo, $reportDay, $reportMealType);
        $foodReportSummary = rt2027_food_report_summary($foodReportRows);

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=relatorio_alimentacao.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Dia','Refeição','Título','Data','Horário','Pessoas previstas','Status','Item do cardápio','Qtd. estimada','Ingrediente/Despensa','Consumo base','Unidade','Modo']);
            foreach ($foodReportRows as $row) {
                fputcsv($out, [
                    rt2027_food_day_label((int)$row['retreat_day']),
                    rt2027_food_meal_types()[$row['meal_type']] ?? $row['meal_type'],
                    $row['title'],
                    $row['meal_date'],
                    $row['meal_time'],
                    $row['estimated_people'],
                    $row['status'],
                    $row['menu_item_name'],
                    $row['quantity_estimate'],
                    $row['pantry_item_name'],
                    $row['quantity_base'],
                    $row['ingredient_unit'],
                    $row['consumption_mode'] === 'per_person' ? 'por pessoa' : 'fixo',
                ]);
            }
            fclose($out);
            exit;
        }

        if (isset($_GET['mode'])) {
            $foodReportMeals = rt2027_food_report_sort_meals(rt2027_food_report_grouped($foodReportRows));
            $generatedAt = date('d/m/Y H:i');
            ob_start();
            include __DIR__ . '/../../templates/admin/food_report_html.php';
            $html = ob_get_clean();
            $saved = rt2027_food_export_html_path($pdo, $html);
            $mode = trim((string)($_GET['mode'] ?? 'html'));

            if ($mode === 'download_html') {
                header('Content-Type: text/html; charset=UTF-8');
                header('Content-Disposition: attachment; filename="' . basename($saved['file_name']) . '"');
                echo $html;
                exit;
            }

            if ($mode === 'pdf') {
                header('Content-Type: text/html; charset=UTF-8');
                echo str_replace('</head>', '<style>@media print{.toolbar{display:none!important}.wrap{max-width:none;padding:0}.hero{border-radius:0}.card{break-inside:avoid}}</style><script>window.addEventListener("load",function(){setTimeout(function(){window.print();},250);});</script></head>', $html);
                exit;
            }

            header('Content-Type: text/html; charset=UTF-8');
            echo $html;
            exit;
        }

        include __DIR__ . '/../../templates/admin/food_reports.php';
        break;

    case 'admin/food-purchases':
    require_admin();
    $purchaseEditId = (int)($_GET['id'] ?? 0);
    $purchaseItems = $pdo->query("
        SELECT c.*, p.item_name AS pantry_name, COALESCE(fc.name, c.category) AS category_display
        FROM food_purchase_items c
        LEFT JOIN food_pantry_items p ON p.id = c.pantry_item_id
        LEFT JOIN food_categories fc ON fc.id = c.category_id
        ORDER BY FIELD(c.status,'pendente','comprado'), FIELD(c.priority_level,'alta','media','baixa'), c.item_name ASC
    ")->fetchAll();
    $pantryLookup = $pdo->query('SELECT id, item_name, category, unit FROM food_pantry_items ORDER BY item_name ASC')->fetchAll();
    $purchaseEdit = null;
    foreach ($purchaseItems as $pc) {
        if ((int)$pc['id'] === $purchaseEditId) {
            $purchaseEdit = $pc;
            break;
        }
    }
    include __DIR__ . '/../../templates/admin/food_purchases.php';
    break;


    case 'admin/tasks':
        require_admin();
        try {
            rt2027_task_ensure_schema($pdo);
            $taskEditId = (int)($_GET['task_id'] ?? 0);
            $slotEditId = (int)($_GET['slot_id'] ?? 0);
            $taskDefinitions = rt2027_task_definitions($pdo, true);
            $taskEdit = null;
            foreach ($taskDefinitions as $taskRow) { if ((int)$taskRow['id'] === $taskEditId) { $taskEdit = $taskRow; break; } }
            $taskSlots = rt2027_task_schedule_rows($pdo, trim((string)($_GET['slot_date'] ?? '')) ?: null, trim((string)($_GET['shift_key'] ?? '')) ?: null);
            $taskSlotEdit = null;
            foreach ($taskSlots as $slotRow) { if ((int)$slotRow['id'] === $slotEditId) { $taskSlotEdit = $slotRow; break; } }
            $taskShiftOptions = rt2027_task_shift_options($pdo);
            $taskSummary = rt2027_task_schedule_summary($taskSlots);
        } catch (Throwable $e) {
            error_log('admin/tasks failed: ' . $e->getMessage());
            flash('error', 'Não foi possível carregar o módulo de tarefas neste momento.');
            redirect_to('admin/dashboard');
        }
        include __DIR__ . '/../../templates/admin/tasks.php';
        break;

    case 'admin/tasks-report':
        require_admin();
        try {
            rt2027_task_ensure_schema($pdo);
            $taskShiftOptions = rt2027_task_shift_options($pdo);
            $taskSlots = rt2027_task_schedule_rows($pdo, trim((string)($_GET['slot_date'] ?? '')) ?: null, trim((string)($_GET['shift_key'] ?? '')) ?: null);
            $taskSummary = rt2027_task_schedule_summary($taskSlots);
            $taskReportGrouped = rt2027_task_report_grouped($taskSlots);
        } catch (Throwable $e) {
            error_log('admin/tasks-report failed: ' . $e->getMessage());
            flash('error', 'Não foi possível gerar o relatório de tarefas.');
            redirect_to('admin/tasks');
        }
        $generatedAt = date('d/m/Y H:i');
        ob_start();
        include __DIR__ . '/../../templates/admin/tasks_report_html.php';
        $html = ob_get_clean();
        if (($_GET['mode'] ?? 'html') === 'download_html') {
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="quadro_tarefas_' . date('Ymd_His') . '.html"');
            echo $html;
            exit;
        }
        if (($_GET['mode'] ?? 'html') === 'pdf') {
            header('Content-Type: text/html; charset=UTF-8');
            echo str_replace('</head>', '<style>@media print{.toolbar{display:none!important}.wrap{max-width:none;padding:0}.card{break-inside:avoid}}</style><script>window.addEventListener("load",function(){setTimeout(function(){window.print();},250);});</script></head>', $html);
            exit;
        }
        header('Content-Type: text/html; charset=UTF-8');
        echo $html;
        exit;

    case 'admin/settings':
        require_admin();
        $prefs = dashboard_preferences($pdo);
        include __DIR__ . '/../../templates/admin/settings.php';
        break;

    default:
        $handled = false;
        break;
}
