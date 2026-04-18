<?php

// Rotas administrativas do módulo: settings_groups

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
    $receiptPath = rt2027_store_uploaded_receipt($_FILES['payment_receipt'] ?? [], rt2027_storage_path('comprovantes'));

    try {
        financial_service($pdo)->registerPayment($groupId, $amount, $method, $installment, $date, $receiptPath, $notes);
        flash('success', 'Pagamento registrado.');
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        flash('error', 'Não foi possível registrar o pagamento.');
    }

    redirect_to('admin/financial');
}

if ($route === 'admin/group-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $groupId = (int)($_POST['group_id'] ?? 0);

    try {
        financial_service($pdo)->updateGroupFromAdmin($groupId, $_POST, $_FILES);
        log_activity($pdo, $_SESSION['admin_id'] ?? null, 'group_updated', 'Inscrição #' . $groupId . ' atualizada no painel');
        flash('success', 'Inscrição atualizada no painel.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível salvar a inscrição no painel.');
    }

    header('Location: ' . route_url('admin/group-edit', ['id' => $groupId]));
    exit;
}

if ($route === 'admin/participant-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $participantId = (int)($_POST['participant_id'] ?? 0);
    $groupId = (int)($_POST['group_id'] ?? 0);

    try {
        financial_service($pdo)->updateParticipantFromAdmin($groupId, $participantId, $_POST);
        log_activity($pdo, $_SESSION['admin_id'] ?? null, 'participant_updated', 'Participante #' . $participantId . ' atualizado');
        flash('success', 'Participante atualizado.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível atualizar o participante.');
    }

    header('Location: ' . route_url('admin/participant-edit', ['id' => $participantId]));
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

    try {
        financial_service($pdo)->deleteGroup($groupId);
        log_activity($pdo, $_SESSION['admin_id'] ?? null, 'group_deleted', 'Inscrição #' . $groupId . ' excluída');
        flash('success', 'Inscrição excluída com sucesso.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível excluir a inscrição.');
    }
    redirect_to('admin/groups');
}
