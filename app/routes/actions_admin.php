<?php

if ($route === 'admin/checkin/toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $participantId = (int)($_POST['participant_id'] ?? 0);
    $status = (string)($_POST['checkin_status'] ?? 'nao');
    $returnQuery = trim((string)($_POST['return_query'] ?? ''));
    try {
        checkin_service($pdo)->toggleParticipant($participantId, $status, (int)($_SESSION['admin_id'] ?? 0));
        flash('success', $status === 'sim' ? 'Presença marcada com sucesso.' : 'Check-in desfeito com sucesso.');
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/../../storage/logs/checkin.log', '[' . date('c') . '] toggle: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        flash('error', 'Não foi possível salvar o check-in.');
    }
    if ($returnQuery !== '') {
        header('Location: index.php?' . ltrim($returnQuery, '?'));
        exit;
    }
    redirect_to('admin/checkin');
}

if ($route === 'admin/checkin/group-toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $groupId = (int)($_POST['group_id'] ?? 0);
    $status = (string)($_POST['checkin_status'] ?? 'nao');
    $returnQuery = trim((string)($_POST['return_query'] ?? ''));
    try {
        $result = checkin_service($pdo)->toggleGroup($groupId, $status, (int)($_SESSION['admin_id'] ?? 0));
        flash('success', 'Check-in em lote aplicado para ' . (int)($result['participants_updated'] ?? 0) . ' participante(s).');
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/../../storage/logs/checkin.log', '[' . date('c') . '] group-toggle: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        flash('error', 'Não foi possível atualizar o check-in da família.');
    }
    if ($returnQuery !== '') {
        header('Location: index.php?' . ltrim($returnQuery, '?'));
        exit;
    }
    redirect_to('admin/checkin');
}

if ($route === 'admin/checkin/qr' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_admin();
    $accessCode = trim((string)($_GET['access_code'] ?? ''));
    if ($accessCode === '') {
        flash('error', 'QR inválido.');
        redirect_to('admin/checkin');
    }
    $safeCode = h($accessCode);
    $formAction = h(route_url('admin/checkin/qr'));
    $csrf = h(csrf_token());
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>Confirmar check-in por QR</title>'
        . '<style>body{font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;padding:24px} .card{max-width:560px;margin:40px auto;background:#fff;border:1px solid #e2e8f0;border-radius:24px;padding:28px;box-shadow:0 10px 30px rgba(15,23,42,.08)} .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px} .btn{display:inline-block;border-radius:16px;padding:12px 18px;font-weight:700;text-decoration:none;border:1px solid #cbd5e1;background:#fff;color:#0f172a;cursor:pointer} .btn-primary{background:#0f766e;border-color:#0f766e;color:#fff}</style>'
        . '</head><body><div class="card"><h1 style="margin:0 0 12px">Confirmar check-in por QR</h1><p>Você está prestes a marcar como presente a inscrição <strong>' . $safeCode . '</strong>.</p><p>Para continuar com segurança, confirme a operação abaixo.</p><form method="post" action="' . $formAction . '"><input type="hidden" name="_csrf" value="' . $csrf . '"><input type="hidden" name="access_code" value="' . $safeCode . '"><div class="actions"><button type="submit" class="btn btn-primary">Confirmar check-in</button><a class="btn" href="' . h(route_url('admin/checkin', ['search' => $accessCode])) . '">Cancelar</a></div></form></div></body></html>';
    exit;
}

if ($route === 'admin/checkin/qr' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $accessCode = trim((string)($_POST['access_code'] ?? ''));
    try {
        $result = checkin_service($pdo)->processQrAccessCode($accessCode, (int)($_SESSION['admin_id'] ?? 0));
        flash('success', 'QR processado com sucesso. Família ' . h($result['access_code'] ?? $accessCode) . ' marcada como presente.');
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/../../storage/logs/checkin.log', '[' . date('c') . '] qr: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        flash('error', 'Não foi possível processar o QR informado.');
    }
    redirect_to('admin/checkin', ['search' => $accessCode]);
}

if ($route === 'admin/checkin/sync' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    header('Content-Type: application/json; charset=UTF-8');
    try {
        $participant = checkin_service($pdo)->syncParticipant($_POST, (int)($_SESSION['admin_id'] ?? 0));
        echo json_encode([
            'ok' => true,
            'participant_id' => (int)($participant['id'] ?? 0),
            'checkin_status' => (string)($participant['checkin_status'] ?? 'nao'),
            'checked_in_at' => $participant['checked_in_at'] ?? null,
            'checked_in_by_name' => $participant['checked_in_by_name'] ?? null,
            'synced_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        @file_put_contents(__DIR__ . '/../../storage/logs/checkin.log', '[' . date('c') . '] sync: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Falha ao sincronizar o check-in.'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if ($route === 'admin/checkin/history-csv') {
    require_admin();
    $filters = q_filters(['search','arrival_from','arrival_to']);
    $rows = checkin_service($pdo)->recentHistory(5000, $filters);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=checkin-historico.csv');
    $out = fopen('php://output', 'w');
    fwrite($out, "ï»¿");
    fputcsv($out, ['Data/Hora', 'Código', 'Responsável', 'Participante', 'Status anterior', 'Novo status', 'Origem', 'Contexto', 'Administrador'], ';');
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['created_at'] ?? '',
            $row['access_code'] ?? '',
            $row['responsible_name'] ?? '',
            $row['full_name'] ?? '',
            $row['previous_status'] ?? '',
            $row['new_status'] ?? '',
            $row['change_source'] ?? '',
            $row['change_context'] ?? '',
            $row['admin_name'] ?? '',
        ], ';');
    }
    fclose($out);
    exit;
}

if ($route === 'admin/food-restrictions-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    foreach (($_POST['dietary_notes'] ?? []) as $participantId => $notes) {
        $pdo->prepare('UPDATE participants SET dietary_notes=? WHERE id=?')->execute([trim((string)$notes), (int)$participantId]);
    }
    flash('success', 'Restrições alimentares atualizadas.');
    redirect_to('admin/food-restrictions');
}

if ($route === 'admin/food-ingredient-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $menuItemId = (int)($_POST['menu_item_id'] ?? 0);
    $pantryItemId = (int)($_POST['pantry_item_id'] ?? 0);
    $quantityBase = (float)($_POST['quantity_base'] ?? 0);
    $actualQuantity = ($_POST['actual_quantity_used'] ?? '') !== '' ? (float)$_POST['actual_quantity_used'] : null;
    $unit = trim((string)($_POST['unit'] ?? ''));
    $mode = trim((string)($_POST['consumption_mode'] ?? 'fixed'));
    if (!in_array($mode, ['fixed','per_person'], true)) { $mode = 'fixed'; }
    $notes = trim((string)($_POST['notes'] ?? ''));
    if ($menuItemId <= 0 || $pantryItemId <= 0 || $quantityBase <= 0) {
        flash('error', 'Informe item do cardápio, item da despensa e quantidade base.');
        redirect_to('admin/food-menus');
    }
    if ($id > 0) {
        $pdo->prepare('UPDATE food_menu_item_ingredients SET menu_item_id=?, pantry_item_id=?, quantity_base=?, actual_quantity_used=?, unit=?, consumption_mode=?, notes=? WHERE id=?')->execute([$menuItemId, $pantryItemId, $quantityBase, $actualQuantity, $unit, $mode, $notes, $id]);
    } else {
        $pdo->prepare('INSERT INTO food_menu_item_ingredients (menu_item_id, pantry_item_id, quantity_base, actual_quantity_used, unit, consumption_mode, notes) VALUES (?, ?, ?, ?, ?, ?, ?)')->execute([$menuItemId, $pantryItemId, $quantityBase, $actualQuantity, $unit, $mode, $notes]);
    }
    flash('success', 'Ingrediente vinculado ao item do cardápio.');
    redirect_to('admin/food-menus');
}

if ($route === 'admin/food-ingredient-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) { $pdo->prepare('DELETE FROM food_menu_item_ingredients WHERE id=?')->execute([$id]); flash('success', 'Vínculo de ingrediente removido.'); }
    redirect_to('admin/food-menus');
}

if ($route === 'admin/food-purchases-generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $generated = rt2027_food_purchase_auto_generate($pdo);
    flash('success', 'Lista automática gerada/atualizada com ' . $generated . ' item(ns) em falta.');
    redirect_to('admin/food-purchases');
}

if ($route === 'admin/food-meal-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $retreatDay = max(1, (int)($_POST['retreat_day'] ?? 1));
    $mealType = trim((string)($_POST['meal_type'] ?? 'cafe'));
    $title = trim((string)($_POST['title'] ?? '')) ?: (rt2027_food_meal_types()[$mealType] ?? 'Refeição');
    $mealDate = trim((string)($_POST['meal_date'] ?? '')) ?: null;
    $mealTime = trim((string)($_POST['meal_time'] ?? '')) ?: null;
    $estimatedPeople = max(0, (int)($_POST['estimated_people'] ?? 0));
    $executedPeople = ($_POST['executed_people'] ?? '') !== '' ? max(0, (int)$_POST['executed_people']) : null;
    $responsibleName = trim((string)($_POST['responsible_name'] ?? '')) ?: null;
    $statusMeal = trim((string)($_POST['status'] ?? 'planejado')) ?: 'planejado';
    $notes = trim((string)($_POST['notes'] ?? '')) ?: null;

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE food_meals SET retreat_day=?, meal_type=?, title=?, meal_date=?, meal_time=?, estimated_people=?, executed_people=?, responsible_name=?, status=?, notes=? WHERE id=?');
        $stmt->execute([$retreatDay, $mealType, $title, $mealDate, $mealTime, $estimatedPeople, $executedPeople, $responsibleName, $statusMeal, $notes, $id]);
        $mealId = $id;
        $message = 'Refeição atualizada.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO food_meals (retreat_day, meal_type, title, meal_date, meal_time, estimated_people, executed_people, responsible_name, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$retreatDay, $mealType, $title, $mealDate, $mealTime, $estimatedPeople, $executedPeople, $responsibleName, $statusMeal, $notes]);
        $mealId = (int)$pdo->lastInsertId();
        $message = 'Refeição cadastrada.';
    }

    if ($statusMeal === 'concluido') {
        $apply = rt2027_food_apply_meal_stock($pdo, $mealId);
        if (!empty($apply['applied']) && ($apply['items'] ?? 0) > 0) {
            $message .= ' Estoque baixado automaticamente.';
        } elseif (($apply['reason'] ?? '') === 'already_applied') {
            $message .= ' Estoque desta refeição já havia sido baixado anteriormente.';
        }
    }

    flash('success', $message);
    redirect_to('admin/food-menus');
}

if ($route === 'admin/food-menu-item-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $mealId = (int)($_POST['meal_id'] ?? 0);
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $quantity = ($_POST['quantity_estimate'] ?? '') !== '' ? (float)$_POST['quantity_estimate'] : null;
    $unit = trim((string)($_POST['unit'] ?? '')) ?: null;
    $notes = trim((string)($_POST['notes'] ?? '')) ?: null;

    if ($mealId <= 0 || $itemName === '') {
        flash('error', 'Informe a refeição e o item do cardápio.');
        redirect_to('admin/food-menus');
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE food_menu_items SET meal_id=?, item_name=?, quantity_estimate=?, unit=?, notes=? WHERE id=?');
        $stmt->execute([$mealId, $itemName, $quantity, $unit, $notes, $id]);
        flash('success', 'Item do cardápio atualizado.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO food_menu_items (meal_id, item_name, quantity_estimate, unit, notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$mealId, $itemName, $quantity, $unit, $notes]);
        flash('success', 'Item do cardápio adicionado.');
    }

    redirect_to('admin/food-menus');
}

if ($route === 'admin/food-menu-item-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM food_menu_items WHERE id=?')->execute([$id]);
        flash('success', 'Item do cardápio removido.');
    }
    redirect_to('admin/food-menus');
}

if ($route === 'admin/food-meal-revert' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $mealId = (int)($_POST['meal_id'] ?? 0);
    $result = rt2027_food_revert_meal_stock($pdo, $mealId);
    if (!empty($result['reverted'])) {
        flash('success', 'Baixa de estoque revertida com sucesso.');
    } else {
        flash('error', 'Não foi possível reverter a baixa: ' . ($result['reason'] ?? 'erro'));
    }
    redirect_to('admin/food-menus');
}

if ($route === 'admin/food-pantry-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $category = null;
    $unit = trim((string)($_POST['unit'] ?? '')) ?: null;
    $qty = (float)($_POST['quantity_current'] ?? 0);
    $min = (float)($_POST['minimum_stock'] ?? 0);
    $exp = trim((string)($_POST['expiration_date'] ?? '')) ?: null;
    $place = trim((string)($_POST['storage_place'] ?? '')) ?: null;
    $notes = trim((string)($_POST['notes'] ?? '')) ?: null;

    if ($itemName === '') {
        flash('error', 'Informe o item da despensa.');
        redirect_to('admin/food-pantry');
    }

    if ($categoryId > 0) {
        $stmtCat = $pdo->prepare('SELECT name FROM food_categories WHERE id = ? LIMIT 1');
        $stmtCat->execute([$categoryId]);
        $category = $stmtCat->fetchColumn() ?: null;
    } else {
        $categoryId = null;
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE food_pantry_items SET item_name=?, category=?, category_id=?, unit=?, quantity_current=?, minimum_stock=?, expiration_date=?, storage_place=?, notes=? WHERE id=?')
            ->execute([$itemName, $category, $categoryId, $unit, $qty, $min, $exp, $place, $notes, $id]);
        flash('success', 'Item da despensa atualizado.');
    } else {
        $pdo->prepare('INSERT INTO food_pantry_items (item_name, category, category_id, unit, quantity_current, minimum_stock, expiration_date, storage_place, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$itemName, $category, $categoryId, $unit, $qty, $min, $exp, $place, $notes]);
        flash('success', 'Item da despensa cadastrado.');
    }

    redirect_to('admin/food-pantry');
}

if ($route === 'admin/food-pantry-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM food_pantry_items WHERE id=?')->execute([$id]);
        flash('success', 'Item da despensa removido.');
    }
    redirect_to('admin/food-pantry');
}

if ($route === 'admin/food-purchase-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $pantryId = ($_POST['pantry_item_id'] ?? '') !== '' ? (int)($_POST['pantry_item_id']) : null;
    $itemName = trim((string)($_POST['item_name'] ?? ''));
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $category = null;
    $qty = (float)($_POST['quantity_needed'] ?? 0);
    $unit = trim((string)($_POST['unit'] ?? '')) ?: null;
    $priority = trim((string)($_POST['priority_level'] ?? 'media')) ?: 'media';
    $cost = (float)($_POST['estimated_cost'] ?? 0);
    $statusPurchase = trim((string)($_POST['status'] ?? 'pendente')) ?: 'pendente';
    $notes = trim((string)($_POST['notes'] ?? '')) ?: null;

    if ($itemName === '') {
        flash('error', 'Informe o item de compra.');
        redirect_to('admin/food-purchases');
    }

    if ($categoryId > 0) {
        $stmtCat = $pdo->prepare('SELECT name FROM food_categories WHERE id = ? LIMIT 1');
        $stmtCat->execute([$categoryId]);
        $category = $stmtCat->fetchColumn() ?: null;
    } else {
        $categoryId = null;
    }

    if ($id > 0) {
        $pdo->prepare('UPDATE food_purchase_items SET pantry_item_id=?, item_name=?, category=?, category_id=?, quantity_needed=?, unit=?, priority_level=?, estimated_cost=?, status=?, notes=? WHERE id=?')
            ->execute([$pantryId, $itemName, $category, $categoryId, $qty, $unit, $priority, $cost, $statusPurchase, $notes, $id]);
        flash('success', 'Item de compra atualizado.');
    } else {
        $pdo->prepare('INSERT INTO food_purchase_items (pantry_item_id, item_name, category, category_id, quantity_needed, unit, priority_level, estimated_cost, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$pantryId, $itemName, $category, $categoryId, $qty, $unit, $priority, $cost, $statusPurchase, $notes]);
        flash('success', 'Item de compra cadastrado.');
    }

    redirect_to('admin/food-purchases');
}

if ($route === 'admin/food-purchase-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM food_purchase_items WHERE id=?')->execute([$id]);
        flash('success', 'Item de compra removido.');
    }
    redirect_to('admin/food-purchases');
}

if ($route === 'admin/food-category-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = !empty($_POST['is_active']) ? 1 : 0;

    if ($name === '') {
        flash('error', 'Informe o nome da categoria.');
        redirect_to('admin/food-categories');
    }

    $slugBase = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $slugBase = strtolower((string)$slugBase);
    $slugBase = preg_replace('/[^a-z0-9]+/', '-', $slugBase);
    $slug = trim((string)$slugBase, '-') ?: 'categoria';

    try {
        if ($id > 0) {
            $pdo->prepare('UPDATE food_categories SET name=?, slug=?, sort_order=?, is_active=? WHERE id=?')->execute([$name, $slug, $sortOrder, $isActive, $id]);
            flash('success', 'Categoria atualizada.');
        } else {
            $pdo->prepare('INSERT INTO food_categories (name, slug, sort_order, is_active) VALUES (?, ?, ?, ?)')->execute([$name, $slug, $sortOrder, $isActive]);
            flash('success', 'Categoria criada.');
        }
    } catch (Throwable $e) {
        flash('error', 'Não foi possível salvar a categoria. Verifique se já existe outra com o mesmo nome.');
    }

    redirect_to('admin/food-categories');
}

if ($route === 'admin/food-category-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $inUse = 0;
        try {
            $check = $pdo->prepare('SELECT (SELECT COUNT(*) FROM food_pantry_items WHERE category_id=?) + (SELECT COUNT(*) FROM food_purchase_items WHERE category_id=?)');
            $check->execute([$id, $id]);
            $inUse = (int)$check->fetchColumn();
        } catch (Throwable $e) {
            $inUse = 0;
        }

        if ($inUse > 0) {
            $pdo->prepare('UPDATE food_categories SET is_active=0 WHERE id=?')->execute([$id]);
            flash('success', 'Categoria inativada porque já está em uso.');
        } else {
            $pdo->prepare('DELETE FROM food_categories WHERE id=?')->execute([$id]);
            flash('success', 'Categoria removida.');
        }
    }
    redirect_to('admin/food-categories');
}

if ($route === 'admin/settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $keys = [
        'event_name','max_people','chalet_units','chalet_capacity_per_unit','lodging_capacity','lodging_male_capacity','lodging_female_capacity',
        'price_adult_chale','price_adult_alojamento','price_adult_casa',
        'price_child_chale','price_child_alojamento','price_child_casa',
        'family_discount_percent','registration_fee','max_installments',
        'pix_key','pix_beneficiary','pix_copy_paste','payment_receipt_contact','payment_deadline_day','financial_confirmation_note',
        'primary_color','accent_color','surface_color','panel_bg_color','card_shadow','font_family','font_size','font_weight','font_style'
    ];
    foreach ($keys as $key) {
        set_setting($pdo, $key, trim((string)($_POST[$key] ?? '')));
    }
    $widgets = [
        'summary_cards' => !empty($_POST['widget_summary_cards']),
        'occupancy_cards' => !empty($_POST['widget_occupancy_cards']),
        'accommodation_chart' => !empty($_POST['widget_accommodation_chart']),
        'finance_chart' => !empty($_POST['widget_finance_chart']),
        'status_chart' => !empty($_POST['widget_status_chart']),
        'lodging_gender_chart' => !empty($_POST['widget_lodging_gender_chart']),
        'recent_groups' => !empty($_POST['widget_recent_groups']),
        'recent_activity' => !empty($_POST['widget_recent_activity']),
    ];
    set_setting($pdo, 'dashboard_widgets', json_encode($widgets));
    flash('success', 'Configurações salvas.');
    redirect_to('admin/settings');
}

if ($route === 'admin/financial' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $groupId = (int)($_POST['group_id'] ?? 0);
    $amount = parse_money_to_float((string)($_POST['amount_paid'] ?? '0'));
    $method = trim((string)($_POST['payment_method'] ?? 'pix'));
    $installment = max(1, (int)($_POST['installment_number'] ?? 1));
    $date = trim((string)($_POST['payment_date'] ?? date('Y-m-d')));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $receiptPath = rt2027_store_uploaded_receipt($_FILES['payment_receipt'] ?? [], __DIR__ . '/storage/comprovantes');
    if ($groupId > 0 && $amount > 0) {
        rt2027_insert_payment($pdo, $groupId, $amount, $method, $installment, $date, $receiptPath, $notes);
        recalculate_group_financials($pdo, $groupId);
        flash('success', 'Pagamento registrado.');
    }
    redirect_to('admin/financial');
}

if ($route === 'admin/group-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $groupId = (int)($_POST['group_id'] ?? 0);
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('UPDATE groups SET responsible_name=?, responsible_age=?, responsible_phone=?, responsible_email=?, registration_type=?, payment_method=?, installments=?, status=?, notes=? WHERE id=?');
        $stmt->execute([
            trim((string)($_POST['responsible_name'] ?? '')),
            ($_POST['responsible_age'] ?? '') !== '' ? (int)$_POST['responsible_age'] : null,
            normalize_phone(trim((string)($_POST['responsible_phone'] ?? ''))),
            trim((string)($_POST['responsible_email'] ?? '')),
            trim((string)($_POST['registration_type'] ?? 'individual')),
            trim((string)($_POST['payment_method'] ?? 'nao_definido')),
            max(1, (int)($_POST['installments'] ?? 1)),
            trim((string)($_POST['status'] ?? 'intencao')),
            trim((string)($_POST['notes'] ?? '')),
            $groupId
        ]);
        rt2027_update_responsible_participant($pdo, $groupId, $_POST);
        sync_group_participants($pdo, $groupId, $_POST, false);

        $receiptPath = rt2027_store_uploaded_receipt($_FILES['receipt'] ?? [], __DIR__ . '/storage/comprovantes');
        if ($receiptPath) {
            $pdo->prepare('UPDATE groups SET receipt_file=? WHERE id=?')->execute([$receiptPath, $groupId]);
        }

        recalculate_group_financials($pdo, $groupId);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Não foi possível salvar a inscrição no painel.');
        header('Location: index.php?route=admin/group-edit&id=' . $groupId);
        exit;
    }
    log_activity($pdo, $_SESSION['admin_id'] ?? null, 'group_updated', 'Inscrição #' . $groupId . ' atualizada no painel');
    flash('success', 'Inscrição atualizada no painel.');
    header('Location: index.php?route=admin/group-edit&id=' . $groupId);
    exit;
}

if ($route === 'admin/participant-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $participantId = (int)($_POST['participant_id'] ?? 0);
    $groupId = (int)($_POST['group_id'] ?? 0);
    $age = ($_POST['age'] ?? '') !== '' ? (int)$_POST['age'] : null;
    $acc = trim((string)($_POST['accommodation_choice'] ?? 'alojamento'));
    $stmt = $pdo->prepare('UPDATE participants SET full_name=?, sex=?, age=?, age_band=?, accommodation_choice=?, sleeps_on_site=?, calculated_value=?, dietary_notes=? WHERE id=? AND group_id=?');
    $stmt->execute([
        trim((string)($_POST['full_name'] ?? '')),
        trim((string)($_POST['sex'] ?? '')),
        $age,
        age_band($age),
        $acc,
        $acc === 'casa' ? 0 : 1,
        calculate_participant_value($pdo, $age, $acc),
        trim((string)($_POST['dietary_notes'] ?? '')),
        $participantId,
        $groupId
    ]);
    if (!empty($_POST['is_responsible'])) {
        $stmt = $pdo->prepare('UPDATE groups SET responsible_name=?, responsible_age=? WHERE id=?');
        $stmt->execute([trim((string)($_POST['full_name'] ?? '')), $age, $groupId]);
    }
    recalculate_group_financials($pdo, $groupId);
    log_activity($pdo, $_SESSION['admin_id'] ?? null, 'participant_updated', 'Participante #' . $participantId . ' atualizado');
    flash('success', 'Participante atualizado.');
    header('Location: index.php?route=admin/participant-edit&id=' . $participantId);
    exit;
}




if ($route === 'admin/group-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) {
        flash('error', 'Inscrição inválida para exclusão.');
        redirect_to('admin/groups');
    }
    $pdo->beginTransaction();
    try {
        $stmtGroup = $pdo->prepare('SELECT receipt_file FROM groups WHERE id = ? LIMIT 1');
        $stmtGroup->execute([$groupId]);
        $groupRow = $stmtGroup->fetch();
        if (!$groupRow) {
            throw new RuntimeException('Inscrição não encontrada.');
        }

        $stmtPayments = $pdo->prepare('SELECT receipt_file FROM payments WHERE group_id = ?');
        $stmtPayments->execute([$groupId]);
        $paymentReceipts = $stmtPayments->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $stmtParticipantIds = $pdo->prepare('SELECT id FROM participants WHERE group_id = ?');
        $stmtParticipantIds->execute([$groupId]);
        $participantIds = array_map('intval', $stmtParticipantIds->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if ($participantIds) {
            $in = implode(',', array_fill(0, count($participantIds), '?'));
            $pdo->prepare("DELETE ta FROM task_assignments ta JOIN participants p ON p.id = ta.participant_id WHERE p.group_id = ?")->execute([$groupId]);
        }
        $pdo->prepare('DELETE FROM payments WHERE group_id = ?')->execute([$groupId]);
        $pdo->prepare('DELETE FROM participants WHERE group_id = ?')->execute([$groupId]);
        $pdo->prepare('DELETE FROM groups WHERE id = ?')->execute([$groupId]);
        $pdo->commit();

        rt2027_delete_relative_file($groupRow['receipt_file'] ?? null);
        foreach ($paymentReceipts as $receiptFile) {
            rt2027_delete_relative_file($receiptFile);
        }
        log_activity($pdo, $_SESSION['admin_id'] ?? null, 'group_deleted', 'Inscrição #' . $groupId . ' excluída');
        flash('success', 'Inscrição excluída com sucesso.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('error', 'Não foi possível excluir a inscrição.');
    }
    redirect_to('admin/groups');
}

if ($route === 'admin/task-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    rt2027_task_ensure_schema($pdo);
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? '')) ?: null;
    $minAge = ($_POST['min_age'] ?? '') !== '' ? (int)$_POST['min_age'] : null;
    $maxAge = ($_POST['max_age'] ?? '') !== '' ? (int)$_POST['max_age'] : null;
    $sexRule = trim((string)($_POST['sex_rule'] ?? 'any')) ?: 'any';
    $capacity = max(1, (int)($_POST['capacity_per_slot'] ?? 1));
    $sortOrder = (int)($_POST['sort_order'] ?? 0);
    $isActive = !empty($_POST['is_active']) ? 1 : 0;
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
    if ($name === '') {
        flash('error', 'Informe o nome da tarefa.');
        redirect_to('admin/tasks');
    }
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE task_definitions SET name=?, slug=?, description=?, min_age=?, max_age=?, sex_rule=?, capacity_per_slot=?, sort_order=?, is_active=? WHERE id=?');
        $stmt->execute([$name, $slug, $description, $minAge, $maxAge, $sexRule, $capacity, $sortOrder, $isActive, $id]);
        flash('success', 'Tarefa atualizada.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO task_definitions (name, slug, description, min_age, max_age, sex_rule, capacity_per_slot, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $description, $minAge, $maxAge, $sexRule, $capacity, $sortOrder, $isActive]);
        flash('success', 'Tarefa cadastrada.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    rt2027_task_ensure_schema($pdo);
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM task_definitions WHERE id = ?')->execute([$id]);
        flash('success', 'Tarefa removida.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-slot-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    rt2027_task_ensure_schema($pdo);
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $taskId = (int)($_POST['task_id'] ?? 0);
    $slotDate = trim((string)($_POST['slot_date'] ?? ''));
    $shiftKey = trim((string)($_POST['shift_key'] ?? ''));
    $shiftOptions = rt2027_task_shift_options($pdo);
    $shiftLabel = $shiftOptions[$shiftKey] ?? trim((string)($_POST['shift_label'] ?? ''));
    $shiftOrder = array_search($shiftKey, array_keys($shiftOptions), true);
    $shiftOrder = $shiftOrder === false ? 99 : ((int)$shiftOrder + 1);
    $capacityOverride = ($_POST['capacity_override'] ?? '') !== '' ? max(1, (int)$_POST['capacity_override']) : null;
    $notes = trim((string)($_POST['notes'] ?? '')) ?: null;
    if ($taskId <= 0 || $slotDate === '' || $shiftKey === '') {
        flash('error', 'Informe tarefa, dia e turno.');
        redirect_to('admin/tasks');
    }
    if ($id > 0) {
        $stmt = $pdo->prepare('UPDATE task_slots SET task_id=?, slot_date=?, shift_key=?, shift_label=?, shift_order=?, capacity_override=?, notes=? WHERE id=?');
        $stmt->execute([$taskId, $slotDate, $shiftKey, $shiftLabel, $shiftOrder, $capacityOverride, $notes, $id]);
        flash('success', 'Escala atualizada.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO task_slots (task_id, slot_date, shift_key, shift_label, shift_order, capacity_override, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$taskId, $slotDate, $shiftKey, $shiftLabel, $shiftOrder, $capacityOverride, $notes]);
        flash('success', 'Turno da escala cadastrado.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-slot-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    rt2027_task_ensure_schema($pdo);
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM task_slots WHERE id = ?')->execute([$id]);
        flash('success', 'Turno removido.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    rt2027_task_ensure_schema($pdo);
    validate_csrf();
    $slotDate = trim((string)($_POST['slot_date'] ?? '')) ?: null;
    $shiftKey = trim((string)($_POST['shift_key'] ?? '')) ?: null;
    $result = rt2027_task_generate_assignments($pdo, $slotDate, $shiftKey);
    flash('success', 'Distribuição automática concluída: ' . $result['created'] . ' alocação(ões) criada(s).');
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-assignment-add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    rt2027_task_ensure_schema($pdo);
    validate_csrf();
    $slotId = (int)($_POST['task_slot_id'] ?? 0);
    $participantId = (int)($_POST['participant_id'] ?? 0);
    $slot = rt2027_task_fetch_slot($pdo, $slotId);
    if (!$slot || $participantId <= 0) {
        flash('error', 'Não foi possível vincular o participante.');
        redirect_to('admin/tasks');
    }
    $stmt = $pdo->prepare('SELECT * FROM participants WHERE id = ? LIMIT 1');
    $stmt->execute([$participantId]);
    $participant = $stmt->fetch();
    if (!$participant || !rt2027_task_participant_is_eligible($participant, $slot)) {
        flash('error', 'Participante não atende aos critérios da tarefa.');
        redirect_to('admin/tasks');
    }
    if (rt2027_task_assignment_conflict($pdo, $participantId, (string)$slot['slot_date'], (string)$slot['shift_key'], (int)$slot['id'])) {
        flash('error', 'Esse participante já está em outra tarefa no mesmo dia/turno.');
        redirect_to('admin/tasks');
    }
    $checkCount = count(rt2027_task_slot_assignments($pdo, $slotId));
    $capacity = max(1, (int)($slot['capacity_override'] ?: $slot['capacity_per_slot'] ?: 1));
    if ($checkCount >= $capacity) {
        flash('error', 'Esse turno já atingiu a capacidade definida.');
        redirect_to('admin/tasks');
    }
    $stmtInsert = $pdo->prepare('INSERT IGNORE INTO task_assignments (task_slot_id, participant_id, assignment_mode, assigned_by_admin_id) VALUES (?, ?, ?, ?)');
    $stmtInsert->execute([$slotId, $participantId, 'manual', $_SESSION['admin_id'] ?? null]);
    flash('success', 'Participante vinculado à tarefa.');
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-assignment-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    rt2027_task_ensure_schema($pdo);
    validate_csrf();
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $pdo->prepare('DELETE FROM task_assignments WHERE id = ?')->execute([$id]);
        flash('success', 'Participante removido da tarefa.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/reports-executive') {
    require_admin();
    $sortBy = trim((string)($_GET['sort_by'] ?? 'access_code'));
    $sortDir = strtolower(trim((string)($_GET['sort_dir'] ?? 'asc'))) === 'desc' ? 'desc' : 'asc';
    $mode = trim((string)($_GET['mode'] ?? 'html'));
    $emailTo = trim((string)($_POST['email_to'] ?? $_GET['email_to'] ?? ''));
    $reportRows = report_executive_rows($pdo, $sortBy, $sortDir);
    $reportTotals = [
        'groups' => count($reportRows),
        'people' => array_sum(array_map(fn($g) => count($g['membros']), $reportRows)),
    ];
    $generatedAt = date('d/m/Y H:i');
    $reportSortBy = $sortBy;
    $reportSortDir = $sortDir;

    ob_start();
    include __DIR__ . '/templates/admin/report_executive.php';
    $reportHtml = ob_get_clean();
    $snapshot = save_report_snapshot(
        $pdo,
        $reportHtml,
        $mode === 'pdf' ? 'pdf_browser' : ($mode === 'email' ? 'email' : 'html'),
        $sortBy,
        $sortDir,
        $emailTo ?: null,
        $mode === 'email' ? 'preparado' : 'gerado'
    );
    log_activity($pdo, $_SESSION['admin_id'] ?? null, 'report_generated', 'Relatório executivo ' . $snapshot['basename'] . ' (' . $mode . ')');

    if ($mode === 'email') {
        validate_csrf();
        if ($emailTo === '') {
            flash('error', 'Informe um e-mail para envio.');
            redirect_to('admin/reports');
        }
        $subject = 'Relatório executivo - ' . setting($pdo, 'event_name', 'Retiro 2027 - ICNV Catedral');
        $link = rt2027_build_app_url('index.php?route=admin/report-history-view&id=' . (int)$snapshot['id']);
        $body = '<p>Olá,</p><p>Segue em anexo o relatório executivo gerado pelo sistema.</p><p>Também é possível visualizá-lo por este link: <a href="' . h($link) . '">' . h($link) . '</a></p>';
        $sent = send_report_email($emailTo, $subject, $body, __DIR__ . '/' . $snapshot['file_path'], basename($snapshot['file_path']), setting($pdo, 'reports_email_from', 'noreply@icnvcatedral.local'));
        $pdo->prepare('UPDATE report_history SET status=?, recipient_email=? WHERE id=?')->execute([$sent ? 'enviado' : 'falha_envio', $emailTo, (int)$snapshot['id']]);
        flash($sent ? 'success' : 'error', $sent ? 'Relatório enviado por e-mail.' : 'Não foi possível enviar o e-mail neste servidor. O histórico foi salvo normalmente.');
        redirect_to('admin/reports');
    }

    if ($mode === 'pdf') {
        header('Content-Type: text/html; charset=UTF-8');
        echo str_replace('</head>', '<style>@media print{.toolbar{display:none!important}.wrap{max-width:none;padding:0}.hero{border-radius:0}.card{break-inside:avoid}}</style><script>window.addEventListener("load",function(){setTimeout(function(){window.print();},250);});</script></head>', $reportHtml);
        exit;
    }

    if ($mode === 'download_html') {
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $snapshot['basename'] . '.html"');
        echo $reportHtml;
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo $reportHtml;
    exit;
}

if ($route === 'admin/report-history-view') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM report_history WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $history = $stmt->fetch();
    if (!$history || empty($history['file_path']) || !is_file(__DIR__ . '/' . $history['file_path'])) {
        http_response_code(404);
        exit('Relatório não encontrado.');
    }
    header('Content-Type: text/html; charset=UTF-8');
    readfile(__DIR__ . '/' . $history['file_path']);
    exit;
}

if ($route === 'admin/report-history-download') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM report_history WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $history = $stmt->fetch();
    if (!$history || empty($history['file_path']) || !is_file(__DIR__ . '/' . $history['file_path'])) {
        http_response_code(404);
        exit('Relatório não encontrado.');
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_historico_' . (int)$history['id'] . '.html"');
    readfile(__DIR__ . '/' . $history['file_path']);
    exit;
}

if ($route === 'admin/reports' && isset($_GET['export'])) {
    require_admin();
    $export = (string)$_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    if ($export === 'groups') {
        header('Content-Disposition: attachment; filename=inscricoes_retiro_2027.csv');
        fputcsv($out, ['Código','Responsável','Tipo','Total pessoas','Acomodação','Status','Financeiro','Valor sugerido','Pago','Pendente']);
        $rows = $pdo->query('SELECT access_code, responsible_name, registration_type, total_people, group_accommodation, status, financial_status, suggested_value, amount_paid, amount_pending FROM groups ORDER BY access_code ASC')->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['access_code'],$row['responsible_name'],$row['registration_type'],$row['total_people'],$row['group_accommodation'],$row['status'],$row['financial_status'],$row['suggested_value'],$row['amount_paid'],$row['amount_pending']]);
        }
        fclose($out);
        exit;
    }
    if ($export === 'participants') {
        header('Content-Disposition: attachment; filename=participantes_retiro_2027.csv');
        fputcsv($out, ['Código','Responsável','Participante','Sexo','Idade','Faixa etária','Acomodação','Valor calculado','Responsável?','Check-in']);
        $rows = $pdo->query("SELECT g.access_code, g.responsible_name, p.full_name, p.sex, p.age, p.age_band, p.accommodation_choice, p.calculated_value, p.is_responsible, p.checkin_status FROM participants p JOIN groups g ON g.id = p.group_id ORDER BY g.created_at DESC, p.is_responsible DESC, p.full_name ASC")->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['access_code'],$row['responsible_name'],$row['full_name'],$row['sex'],$row['age'],$row['age_band'],$row['accommodation_choice'],$row['calculated_value'],$row['is_responsible'] ? 'Sim' : 'Não',$row['checkin_status']]);
        }
        fclose($out);
        exit;
    }
    if ($export === 'financial') {
        header('Content-Disposition: attachment; filename=financeiro_retiro_2027.csv');
        fputcsv($out, ['Código','Responsável','Forma de pagamento','Parcelas','Valor sugerido','Desconto','Pago','Pendente','Status financeiro','Data criação']);
        $rows = $pdo->query("SELECT access_code, responsible_name, payment_method, installments, suggested_value, discount_value, amount_paid, amount_pending, financial_status, created_at FROM groups ORDER BY access_code ASC")->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['access_code'],$row['responsible_name'],$row['payment_method'],$row['installments'],$row['suggested_value'],$row['discount_value'],$row['amount_paid'],$row['amount_pending'],$row['financial_status'],$row['created_at']]);
        }
        fclose($out);
        exit;
    }
    fclose($out);
    http_response_code(400);
    echo 'Exportação inválida.';
    exit;
}
