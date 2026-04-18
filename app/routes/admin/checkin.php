<?php

// Rotas administrativas do módulo: check-in

if ($route === 'admin/checkin/toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();

    $returnQuery = trim((string)($_POST['return_query'] ?? ''));
    $fallbackUrl = route_url('admin/checkin');
    $redirectUrl = $fallbackUrl;
    if ($returnQuery !== '') {
        $redirectUrl = rt2027_front_controller() . '?' . ltrim($returnQuery, '?');
    }

    try {
        checkin_service($pdo)->toggleParticipant((int)($_POST['participant_id'] ?? 0), (string)($_POST['checkin_status'] ?? 'nao'), $_SESSION['admin_id'] ?? null);
        flash('success', 'Check-in atualizado com sucesso.');
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        flash('error', 'Não foi possível atualizar o check-in.');
    }

    header('Location: ' . $redirectUrl);
    exit;
}

if ($route === 'admin/checkin/sync' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();

    header('Content-Type: application/json; charset=UTF-8');

    try {
        $participant = checkin_service($pdo)->syncParticipant($_POST, $_SESSION['admin_id'] ?? null);
        echo json_encode([
            'ok' => true,
            'participant_id' => (int)($participant['id'] ?? 0),
            'checkin_status' => (string)($participant['checkin_status'] ?? 'nao'),
            'synced_at' => date('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (InvalidArgumentException $e) {
        http_response_code(422);
        echo json_encode([
            'ok' => false,
            'error' => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'ok' => false,
            'error' => 'Não foi possível sincronizar o check-in.',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    exit;
}
