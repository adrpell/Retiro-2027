<?php

// Rotas administrativas do módulo: food

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