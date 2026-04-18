<?php

function fetch_group_with_access(PDO $pdo, string $code, string $namePrefix): ?array {
    $stmt = $pdo->prepare('SELECT * FROM groups WHERE UPPER(access_code) = UPPER(?) AND LOWER(responsible_name) LIKE LOWER(?) ORDER BY id DESC LIMIT 1');
    $stmt->execute([$code, $namePrefix . '%']);
    return $stmt->fetch() ?: null;
}

function fetch_group_for_lookup(PDO $pdo, string $login, string $namePrefix = ''): ?array {
    $login = trim($login);
    if ($login === '') return null;
    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM groups WHERE LOWER(COALESCE(responsible_email,'')) = LOWER(?) ORDER BY id DESC LIMIT 1");
        $stmt->execute([$login]);
        return $stmt->fetch() ?: null;
    }
    if ($namePrefix === '') return null;
    return fetch_group_with_access($pdo, $login, $namePrefix);
}

function rt2027_allowed_receipt_extensions(): array {
    return ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
}

function rt2027_store_uploaded_receipt(array $file, string $dir): ?string {
    if (empty($file['name']) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, rt2027_allowed_receipt_extensions(), true)) {
        return null;
    }

    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return 'storage/comprovantes/' . $filename;
}


function rt2027_delete_relative_file(?string $relativePath): void {
    $relativePath = trim((string)$relativePath);
    if ($relativePath === '') return;
    $clean = ltrim(str_replace(['..\\', '../', '..'], '', str_replace('\\', '/', $relativePath)), '/');
    $absolute = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $clean);
    if ($absolute && is_file($absolute)) {
        @unlink($absolute);
    }
}

function rt2027_update_responsible_participant(PDO $pdo, int $groupId, array $data): void {
    $responsibleName = trim((string)($data['responsible_name'] ?? ''));
    $responsibleAge = ($data['responsible_age'] ?? '') !== '' ? (int)$data['responsible_age'] : null;
    $responsibleSex = trim((string)($data['responsible_sex'] ?? ''));
    $responsibleAccommodation = trim((string)($data['responsible_accommodation'] ?? 'alojamento'));
    $responsibleDietaryNotes = trim((string)($data['responsible_dietary_notes'] ?? ''));

    $stmt = $pdo->prepare('UPDATE participants SET full_name=?, sex=?, age=?, age_band=?, accommodation_choice=?, sleeps_on_site=?, calculated_value=?, dietary_notes=? WHERE group_id=? AND is_responsible=1');
    $stmt->execute([
        $responsibleName,
        $responsibleSex,
        $responsibleAge,
        age_band($responsibleAge),
        $responsibleAccommodation,
        $responsibleAccommodation === 'casa' ? 0 : 1,
        calculate_participant_value($pdo, $responsibleAge, $responsibleAccommodation),
        $responsibleDietaryNotes,
        $groupId,
    ]);
}

function sync_group_participants(PDO $pdo, int $groupId, array $data, bool $publicMode = false): void {
    $participantIds = $data['participant_id'] ?? [];
    $names = $data['participant_name'] ?? [];
    $ages = $data['participant_age'] ?? [];
    $sexes = $data['participant_sex'] ?? [];
    $accs = $data['participant_accommodation'] ?? [];
    $dietary = $data['participant_dietary_notes'] ?? [];
    $deleteFlags = $data['participant_delete'] ?? [];

    foreach ($participantIds as $i => $pidRaw) {
        $pid = (int)$pidRaw;
        if ($pid <= 0) continue;
        $name = trim((string)($names[$i] ?? ''));
        $age = ($ages[$i] ?? '') !== '' ? (int)$ages[$i] : null;
        $sex = trim((string)($sexes[$i] ?? ''));
        $acc = trim((string)($accs[$i] ?? 'alojamento'));
        $delete = !empty($deleteFlags[$i]);
        if ($delete) {
            $stmt = $pdo->prepare('DELETE FROM participants WHERE id=? AND group_id=? AND is_responsible=0');
            $stmt->execute([$pid, $groupId]);
            continue;
        }
        if ($name === '') continue;
        $dietaryNotes = trim((string)($dietary[$i] ?? ''));
        $stmt = $pdo->prepare('UPDATE participants SET full_name=?, sex=?, age=?, age_band=?, accommodation_choice=?, sleeps_on_site=?, calculated_value=?, dietary_notes=? WHERE id=? AND group_id=? AND is_responsible=0');
        $stmt->execute([$name, $sex, $age, age_band($age), $acc, $acc === 'casa' ? 0 : 1, calculate_participant_value($pdo, $age, $acc), $dietaryNotes, $pid, $groupId]);
    }

    $newNames = $data['new_participant_name'] ?? [];
    $newAges = $data['new_participant_age'] ?? [];
    $newSexes = $data['new_participant_sex'] ?? [];
    $newAccs = $data['new_participant_accommodation'] ?? [];
    $newDietary = $data['new_participant_dietary_notes'] ?? [];
    $insertStmt = $pdo->prepare('INSERT INTO participants (group_id, full_name, sex, age, age_band, accommodation_choice, sleeps_on_site, is_responsible, calculated_value, dietary_notes) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)');
    foreach ($newNames as $i => $nameRaw) {
        $name = trim((string)$nameRaw);
        if ($name === '') continue;
        $age = ($newAges[$i] ?? '') !== '' ? (int)$newAges[$i] : null;
        $sex = trim((string)($newSexes[$i] ?? ''));
        $acc = trim((string)($newAccs[$i] ?? 'alojamento'));
        $dietaryNotes = trim((string)($newDietary[$i] ?? ''));
        $insertStmt->execute([$groupId, $name, $sex, $age, age_band($age), $acc, $acc === 'casa' ? 0 : 1, calculate_participant_value($pdo, $age, $acc), $dietaryNotes]);
    }
}

function rt2027_insert_payment(PDO $pdo, int $groupId, float $amount, string $paymentMethod, int $installmentNumber, string $paymentDate, ?string $receiptPath, string $notes): void {
    $stmt = $pdo->prepare('INSERT INTO payments (group_id, amount_paid, payment_method, installment_number, payment_date, receipt_file, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$groupId, $amount, $paymentMethod, $installmentNumber, $paymentDate, $receiptPath, $notes]);

    if ($receiptPath) {
        $pdo->prepare('UPDATE groups SET receipt_file=? WHERE id=?')->execute([$receiptPath, $groupId]);
    }
}
