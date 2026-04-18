<?php

// Rotas administrativas do módulo: tasks

if ($route === 'admin/task-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();

    try {
        task_service($pdo)->saveTaskDefinition($_POST);
        flash('success', (int)($_POST['id'] ?? 0) > 0 ? 'Tarefa atualizada.' : 'Tarefa cadastrada.');
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        flash('error', 'Não foi possível salvar a tarefa.');
    }

    redirect_to('admin/tasks');
}

if ($route === 'admin/task-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    try {
        task_service($pdo)->deleteTaskDefinition((int)($_POST['id'] ?? 0));
        flash('success', 'Tarefa removida.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível remover a tarefa.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-slot-save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();

    try {
        task_service($pdo)->saveSlot($_POST);
        flash('success', (int)($_POST['id'] ?? 0) > 0 ? 'Escala atualizada.' : 'Turno da escala cadastrado.');
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        flash('error', 'Não foi possível salvar o turno da escala.');
    }

    redirect_to('admin/tasks');
}

if ($route === 'admin/task-slot-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    try {
        task_service($pdo)->deleteSlot((int)($_POST['id'] ?? 0));
        flash('success', 'Turno removido.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível remover o turno.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-generate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    $slotDate = trim((string)($_POST['slot_date'] ?? '')) ?: null;
    $shiftKey = trim((string)($_POST['shift_key'] ?? '')) ?: null;

    try {
        $result = task_service($pdo)->generateAssignments($slotDate, $shiftKey);
        flash('success', 'Distribuição automática concluída: ' . $result['created'] . ' alocação(ões) criada(s).');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível gerar a distribuição automática.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-assignment-add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();

    try {
        task_service($pdo)->addAssignment((int)($_POST['task_slot_id'] ?? 0), (int)($_POST['participant_id'] ?? 0), $_SESSION['admin_id'] ?? null);
        flash('success', 'Participante vinculado à tarefa.');
    } catch (InvalidArgumentException|RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        flash('error', 'Não foi possível vincular o participante.');
    }
    redirect_to('admin/tasks');
}

if ($route === 'admin/task-assignment-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    validate_csrf();
    try {
        task_service($pdo)->deleteAssignment((int)($_POST['id'] ?? 0));
        flash('success', 'Participante removido da tarefa.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível remover o participante da tarefa.');
    }
    redirect_to('admin/tasks');
}
