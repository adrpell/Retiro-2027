<?php

final class TaskService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function saveTaskDefinition(array $input): void
    {
        rt2027_task_ensure_schema($this->pdo);

        $id = (int)($input['id'] ?? 0);
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('Informe o nome da tarefa.');
        }

        $description = trim((string)($input['description'] ?? '')) ?: null;
        $minAge = ($input['min_age'] ?? '') !== '' ? (int)$input['min_age'] : null;
        $maxAge = ($input['max_age'] ?? '') !== '' ? (int)$input['max_age'] : null;
        $sexRule = trim((string)($input['sex_rule'] ?? 'any')) ?: 'any';
        $capacity = max(1, (int)($input['capacity_per_slot'] ?? 1));
        $sortOrder = (int)($input['sort_order'] ?? 0);
        $isActive = !empty($input['is_active']) ? 1 : 0;
        $slug = strtolower(trim((string)preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));

        if ($id > 0) {
            $stmt = $this->pdo->prepare('UPDATE task_definitions SET name=?, slug=?, description=?, min_age=?, max_age=?, sex_rule=?, capacity_per_slot=?, sort_order=?, is_active=? WHERE id=?');
            $stmt->execute([$name, $slug, $description, $minAge, $maxAge, $sexRule, $capacity, $sortOrder, $isActive, $id]);
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO task_definitions (name, slug, description, min_age, max_age, sex_rule, capacity_per_slot, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $slug, $description, $minAge, $maxAge, $sexRule, $capacity, $sortOrder, $isActive]);
    }

    public function deleteTaskDefinition(int $id): void
    {
        rt2027_task_ensure_schema($this->pdo);
        if ($id > 0) {
            $this->pdo->prepare('DELETE FROM task_definitions WHERE id = ?')->execute([$id]);
        }
    }

    public function saveSlot(array $input): void
    {
        rt2027_task_ensure_schema($this->pdo);

        $id = (int)($input['id'] ?? 0);
        $taskId = (int)($input['task_id'] ?? 0);
        $slotDate = trim((string)($input['slot_date'] ?? ''));
        $shiftKey = trim((string)($input['shift_key'] ?? ''));
        $shiftOptions = rt2027_task_shift_options($this->pdo);
        $shiftLabel = $shiftOptions[$shiftKey] ?? trim((string)($input['shift_label'] ?? ''));
        $shiftOrder = array_search($shiftKey, array_keys($shiftOptions), true);
        $shiftOrder = $shiftOrder === false ? 99 : ((int)$shiftOrder + 1);
        $capacityOverride = ($input['capacity_override'] ?? '') !== '' ? max(1, (int)$input['capacity_override']) : null;
        $notes = trim((string)($input['notes'] ?? '')) ?: null;

        if ($taskId <= 0 || $slotDate === '' || $shiftKey === '') {
            throw new InvalidArgumentException('Informe tarefa, dia e turno.');
        }

        if ($id > 0) {
            $stmt = $this->pdo->prepare('UPDATE task_slots SET task_id=?, slot_date=?, shift_key=?, shift_label=?, shift_order=?, capacity_override=?, notes=? WHERE id=?');
            $stmt->execute([$taskId, $slotDate, $shiftKey, $shiftLabel, $shiftOrder, $capacityOverride, $notes, $id]);
            return;
        }

        $stmt = $this->pdo->prepare('INSERT INTO task_slots (task_id, slot_date, shift_key, shift_label, shift_order, capacity_override, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$taskId, $slotDate, $shiftKey, $shiftLabel, $shiftOrder, $capacityOverride, $notes]);
    }

    public function deleteSlot(int $id): void
    {
        rt2027_task_ensure_schema($this->pdo);
        if ($id > 0) {
            $this->pdo->prepare('DELETE FROM task_slots WHERE id = ?')->execute([$id]);
        }
    }

    public function generateAssignments(?string $slotDate = null, ?string $shiftKey = null): array
    {
        rt2027_task_ensure_schema($this->pdo);
        return rt2027_task_generate_assignments($this->pdo, $slotDate, $shiftKey);
    }

    public function addAssignment(int $slotId, int $participantId, ?int $adminId = null): void
    {
        rt2027_task_ensure_schema($this->pdo);

        $slot = rt2027_task_fetch_slot($this->pdo, $slotId);
        if (!$slot || $participantId <= 0) {
            throw new InvalidArgumentException('Não foi possível vincular o participante.');
        }

        $stmt = $this->pdo->prepare('SELECT * FROM participants WHERE id = ? LIMIT 1');
        $stmt->execute([$participantId]);
        $participant = $stmt->fetch();
        if (!$participant || !rt2027_task_participant_is_eligible($participant, $slot)) {
            throw new InvalidArgumentException('Participante não atende aos critérios da tarefa.');
        }

        if (rt2027_task_assignment_conflict($this->pdo, $participantId, (string)$slot['slot_date'], (string)$slot['shift_key'], (int)$slot['id'])) {
            throw new RuntimeException('Esse participante já está em outra tarefa no mesmo dia/turno.');
        }

        $checkCount = count(rt2027_task_slot_assignments($this->pdo, $slotId));
        $capacity = max(1, (int)($slot['capacity_override'] ?: $slot['capacity_per_slot'] ?: 1));
        if ($checkCount >= $capacity) {
            throw new RuntimeException('Esse turno já atingiu a capacidade definida.');
        }

        $stmtInsert = $this->pdo->prepare('INSERT IGNORE INTO task_assignments (task_slot_id, participant_id, assignment_mode, assigned_by_admin_id) VALUES (?, ?, ?, ?)');
        $stmtInsert->execute([$slotId, $participantId, 'manual', $adminId]);
    }

    public function deleteAssignment(int $assignmentId): void
    {
        rt2027_task_ensure_schema($this->pdo);
        if ($assignmentId > 0) {
            $this->pdo->prepare('DELETE FROM task_assignments WHERE id = ?')->execute([$assignmentId]);
        }
    }
}
