<?php

function rt2027_task_table_exists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
            $stmt->execute([$table]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e2) {
            return false;
        }
    }
}

function rt2027_task_ensure_schema(PDO $pdo): void {
    static $checked = false;
    if ($checked) {
        return;
    }

    // A estrutura deste módulo agora é garantida por migrations versionadas.
    // Aqui mantemos apenas uma verificação leve para evitar custo em toda requisição.
    $requiredTables = ['task_definitions', 'task_slots', 'task_assignments'];
    foreach ($requiredTables as $table) {
        if (!rt2027_task_table_exists($pdo, $table)) {
            error_log('Módulo de tarefas sem tabela obrigatória: ' . $table);
        }
    }

    $checked = true;
}

function rt2027_task_shift_options(PDO $pdo): array {
    rt2027_task_ensure_schema($pdo);
    $raw = setting($pdo, 'task_shift_labels', '{"manha":"Manhã","tarde":"Tarde","noite":"Noite"}');
    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded) || !$decoded) {
        return ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];
    }
    $out = [];
    foreach ($decoded as $key => $label) {
        $k = preg_replace('/[^a-z0-9_-]/i', '', (string)$key);
        if ($k === '') continue;
        $out[$k] = trim((string)$label) ?: ucfirst($k);
    }
    return $out ?: ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];
}

function rt2027_task_sex_label(string $value): string {
    return match ($value) {
        'M' => 'Masculino',
        'F' => 'Feminino',
        default => 'Qualquer',
    };
}

function rt2027_task_definitions(PDO $pdo, bool $includeInactive = true): array {
    rt2027_task_ensure_schema($pdo);
    if (!rt2027_task_table_exists($pdo, 'task_definitions')) {
        return [];
    }
    $sql = 'SELECT * FROM task_definitions';
    if (!$includeInactive) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    return $pdo->query($sql)->fetchAll();
}

function rt2027_task_slots(PDO $pdo, ?string $slotDate = null): array {
    rt2027_task_ensure_schema($pdo);
    if (!rt2027_task_table_exists($pdo, 'task_slots')) {
        return [];
    }
    if ($slotDate !== null && $slotDate !== '') {
        $stmt = $pdo->prepare("SELECT ts.*, td.name AS task_name, td.sex_rule, td.min_age, td.max_age, td.capacity_per_slot, td.description AS task_description
            FROM task_slots ts
            JOIN task_definitions td ON td.id = ts.task_id
            WHERE ts.slot_date = ?
            ORDER BY ts.slot_date ASC, ts.shift_order ASC, td.sort_order ASC, td.name ASC");
        $stmt->execute([$slotDate]);
        return $stmt->fetchAll();
    }
    $sql = "SELECT ts.*, td.name AS task_name, td.sex_rule, td.min_age, td.max_age, td.capacity_per_slot, td.description AS task_description
            FROM task_slots ts
            JOIN task_definitions td ON td.id = ts.task_id
            ORDER BY ts.slot_date ASC, ts.shift_order ASC, td.sort_order ASC, td.name ASC";
    return $pdo->query($sql)->fetchAll();
}

function rt2027_task_fetch_slot(PDO $pdo, int $slotId): ?array {
    rt2027_task_ensure_schema($pdo);
    if (!rt2027_task_table_exists($pdo, 'task_slots')) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT ts.*, td.name AS task_name, td.sex_rule, td.min_age, td.max_age, td.capacity_per_slot
        FROM task_slots ts JOIN task_definitions td ON td.id = ts.task_id WHERE ts.id = ? LIMIT 1");
    $stmt->execute([$slotId]);
    return $stmt->fetch() ?: null;
}

function rt2027_task_slot_assignments(PDO $pdo, int $slotId): array {
    rt2027_task_ensure_schema($pdo);
    if (!rt2027_task_table_exists($pdo, 'task_assignments')) {
        return [];
    }
    // Query compatível com schema legado.
    $stmt = $pdo->prepare("SELECT ta.*, p.full_name, p.sex, p.age,
            COALESCE(p.age_band, '') AS age_band,
            p.group_id, p.is_responsible,
            COALESCE(g.access_code, '') AS access_code,
            COALESCE(g.responsible_name, '') AS responsible_name
        FROM task_assignments ta
        JOIN participants p ON p.id = ta.participant_id
        JOIN groups g ON g.id = p.group_id
        WHERE ta.task_slot_id = ?
        ORDER BY p.full_name ASC");
    $stmt->execute([$slotId]);
    return $stmt->fetchAll();
}

function rt2027_task_assignment_conflict(PDO $pdo, int $participantId, string $slotDate, string $shiftKey, ?int $ignoreSlotId = null): bool {
    rt2027_task_ensure_schema($pdo);
    if (!rt2027_task_table_exists($pdo, 'task_assignments') || !rt2027_task_table_exists($pdo, 'task_slots')) {
        return false;
    }
    $sql = "SELECT COUNT(*)
        FROM task_assignments ta
        JOIN task_slots ts ON ts.id = ta.task_slot_id
        WHERE ta.participant_id = ? AND ts.slot_date = ? AND ts.shift_key = ?";
    $params = [$participantId, $slotDate, $shiftKey];
    if ($ignoreSlotId !== null) {
        $sql .= ' AND ts.id <> ?';
        $params[] = $ignoreSlotId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() > 0;
}

function rt2027_task_participant_is_eligible(array $participant, array $slot): bool {
    $age = $participant['age'] !== null ? (int)$participant['age'] : null;
    $sex = trim((string)($participant['sex'] ?? ''));
    $minAge = $slot['min_age'] !== null ? (int)$slot['min_age'] : null;
    $maxAge = $slot['max_age'] !== null ? (int)$slot['max_age'] : null;
    $sexRule = trim((string)($slot['sex_rule'] ?? 'any')) ?: 'any';
    if ($minAge !== null && ($age === null || $age < $minAge)) return false;
    if ($maxAge !== null && ($age === null || $age > $maxAge)) return false;
    if (in_array($sexRule, ['M','F'], true) && $sex !== $sexRule) return false;
    return true;
}

function rt2027_task_available_participants(PDO $pdo, array $slot): array {
    rt2027_task_ensure_schema($pdo);
    $stmt = $pdo->query("SELECT p.*, COALESCE(g.access_code, '') AS access_code, COALESCE(g.responsible_name, '') AS responsible_name
        FROM participants p
        JOIN groups g ON g.id = p.group_id
        WHERE COALESCE(g.status, '') <> 'cancelado'
        ORDER BY p.is_responsible ASC, p.age ASC, p.full_name ASC");
    $rows = [];
    foreach ($stmt->fetchAll() as $participant) {
        if (!rt2027_task_participant_is_eligible($participant, $slot)) {
            continue;
        }
        if (rt2027_task_assignment_conflict($pdo, (int)$participant['id'], (string)$slot['slot_date'], (string)$slot['shift_key'], (int)$slot['id'])) {
            continue;
        }
        $rows[] = $participant;
    }
    return $rows;
}

function rt2027_task_generate_assignments(PDO $pdo, ?string $slotDate = null, ?string $shiftKey = null): array {
    rt2027_task_ensure_schema($pdo);
    if (!rt2027_task_table_exists($pdo, 'task_slots')) {
        return ['created' => 0, 'skipped' => 0, 'slots' => 0];
    }
    $sql = "SELECT ts.*, td.name AS task_name, td.sex_rule, td.min_age, td.max_age, td.capacity_per_slot, td.sort_order
        FROM task_slots ts
        JOIN task_definitions td ON td.id = ts.task_id
        WHERE td.is_active = 1";
    $params = [];
    if ($slotDate !== null && $slotDate !== '') {
        $sql .= ' AND ts.slot_date = ?';
        $params[] = $slotDate;
    }
    if ($shiftKey !== null && $shiftKey !== '') {
        $sql .= ' AND ts.shift_key = ?';
        $params[] = $shiftKey;
    }
    $sql .= ' ORDER BY ts.slot_date ASC, ts.shift_order ASC, td.sort_order ASC, td.name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $slots = $stmt->fetchAll();

    $created = 0;
    $skipped = 0;
    foreach ($slots as $slot) {
        $slotId = (int)$slot['id'];
        $capacity = max(1, (int)($slot['capacity_override'] ?: $slot['capacity_per_slot'] ?: 1));
        $current = rt2027_task_slot_assignments($pdo, $slotId);
        $remaining = $capacity - count($current);
        if ($remaining <= 0) {
            continue;
        }
        $available = rt2027_task_available_participants($pdo, $slot);
        foreach ($available as $participant) {
            if ($remaining <= 0) break;
            $check = $pdo->prepare('SELECT COUNT(*) FROM task_assignments WHERE task_slot_id = ? AND participant_id = ?');
            $check->execute([$slotId, $participant['id']]);
            if ((int)$check->fetchColumn() > 0) {
                $skipped++;
                continue;
            }
            $ins = $pdo->prepare('INSERT INTO task_assignments (task_slot_id, participant_id, assignment_mode, assigned_by_admin_id) VALUES (?, ?, ?, ?)');
            $ins->execute([$slotId, $participant['id'], 'auto', $_SESSION['admin_id'] ?? null]);
            $created++;
            $remaining--;
        }
    }

    return ['created' => $created, 'skipped' => $skipped, 'slots' => count($slots)];
}

function rt2027_task_schedule_rows(PDO $pdo, ?string $slotDate = null, ?string $shiftKey = null): array {
    rt2027_task_ensure_schema($pdo);
    if (!rt2027_task_table_exists($pdo, 'task_slots')) {
        return [];
    }
    $sql = "SELECT ts.*, td.name AS task_name, td.description AS task_description, td.sex_rule, td.min_age, td.max_age,
                   COALESCE(ts.capacity_override, td.capacity_per_slot) AS slot_capacity
            FROM task_slots ts
            JOIN task_definitions td ON td.id = ts.task_id
            WHERE 1=1";
    $params = [];
    if ($slotDate !== null && $slotDate !== '') {
        $sql .= ' AND ts.slot_date = ?';
        $params[] = $slotDate;
    }
    if ($shiftKey !== null && $shiftKey !== '') {
        $sql .= ' AND ts.shift_key = ?';
        $params[] = $shiftKey;
    }
    $sql .= ' ORDER BY ts.slot_date ASC, ts.shift_order ASC, td.sort_order ASC, td.name ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $slots = $stmt->fetchAll();
    foreach ($slots as &$slot) {
        $slot['assignments'] = rt2027_task_slot_assignments($pdo, (int)$slot['id']);
        $slot['available_participants'] = rt2027_task_available_participants($pdo, $slot);
    }
    unset($slot);
    return $slots;
}

function rt2027_task_schedule_summary(array $slots): array {
    $totalAssignments = 0;
    $totalCapacity = 0;
    $days = [];
    foreach ($slots as $slot) {
        $totalAssignments += count($slot['assignments'] ?? []);
        $totalCapacity += (int)($slot['slot_capacity'] ?? 0);
        $days[(string)$slot['slot_date']] = true;
    }
    return [
        'slots' => count($slots),
        'assignments' => $totalAssignments,
        'capacity' => $totalCapacity,
        'days' => count($days),
    ];
}

function rt2027_task_report_grouped(array $slots): array {
    $grouped = [];
    foreach ($slots as $slot) {
        $date = (string)$slot['slot_date'];
        $shift = (string)$slot['shift_key'];
        if (!isset($grouped[$date])) $grouped[$date] = [];
        if (!isset($grouped[$date][$shift])) $grouped[$date][$shift] = [];
        $grouped[$date][$shift][] = $slot;
    }
    return $grouped;
}
