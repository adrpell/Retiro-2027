<?php

/**
 * Centraliza os fluxos públicos de inscrição, atualização e pagamento.
 * A ideia é manter as rotas enxutas e preservar a regra atual do sistema.
 */
final class RegistrationService
{
    public function __construct(
        private PDO $pdo,
        private FinancialService $financialService,
    ) {
    }

    /**
     * @return array{group_id:int, access_code:string, responsible_name:string, responsible_email:string}
     */
    public function registerPublic(array $post, array $files = []): array
    {
        $responsibleName = trim((string)($post['responsible_name'] ?? ''));
        $responsibleAge = ($post['responsible_age'] ?? '') !== '' ? (int)$post['responsible_age'] : null;
        $responsiblePhone = normalize_phone(trim((string)($post['responsible_phone'] ?? '')));
        $responsibleEmail = trim((string)($post['responsible_email'] ?? ''));
        $responsibleDietaryNotes = trim((string)($post['responsible_dietary_notes'] ?? ''));
        $paymentMethod = trim((string)($post['payment_method'] ?? 'pix'));
        $installments = max(1, min((int)($post['installments'] ?? 1), (int)setting($this->pdo, 'max_installments', '10')));
        $notes = trim((string)($post['notes'] ?? ''));
        $addFamily = (($post['add_family'] ?? 'nao') === 'sim');
        $status = 'intencao';

        if ($responsibleName === '') {
            throw new InvalidArgumentException('Informe o nome do responsável.');
        }

        $totalCurrent = (int)$this->pdo->query('SELECT COALESCE(SUM(total_people),0) FROM groups')->fetchColumn();
        $participantsIncoming = 1 + ($addFamily ? count($post['participant_name'] ?? []) : 0);
        $maxPeople = (int)setting($this->pdo, 'max_people', '160');
        if (($totalCurrent + $participantsIncoming) > $maxPeople) {
            throw new RuntimeException('O limite total de pessoas do retiro foi atingido.');
        }

        $receiptPath = rt2027_store_uploaded_receipt($files['receipt'] ?? [], rt2027_storage_path('comprovantes'));
        $code = '';
        $regType = $addFamily ? 'familia' : 'individual';
        $responsibleAccommodation = trim((string)($post['responsible_accommodation'] ?? 'alojamento'));

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO groups (access_code, registration_type, responsible_name, responsible_age, responsible_phone, responsible_email, payment_method, installments, receipt_file, notes, status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$code, $regType, $responsibleName, $responsibleAge, $responsiblePhone, $responsibleEmail, $paymentMethod, $installments, $receiptPath, $notes, $status, 'site']);
            $groupId = (int)$this->pdo->lastInsertId();
            $code = generate_access_code_from_group_id($groupId);
            $updateCodeStmt = $this->pdo->prepare('UPDATE groups SET access_code = ? WHERE id = ?');
            $updateCodeStmt->execute([$code, $groupId]);

            $participantStmt = $this->pdo->prepare('INSERT INTO participants (group_id, full_name, sex, age, age_band, accommodation_choice, sleeps_on_site, is_responsible, calculated_value, dietary_notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $responsibleValue = calculate_participant_value($this->pdo, $responsibleAge, $responsibleAccommodation);
            $participantStmt->execute([
                $groupId,
                $responsibleName,
                trim((string)($post['responsible_sex'] ?? '')),
                $responsibleAge,
                age_band($responsibleAge),
                $responsibleAccommodation,
                $responsibleAccommodation === 'casa' ? 0 : 1,
                1,
                $responsibleValue,
                $responsibleDietaryNotes,
            ]);

            if ($addFamily) {
                $names = $post['participant_name'] ?? [];
                $ages = $post['participant_age'] ?? [];
                $sexes = $post['participant_sex'] ?? [];
                $accommodations = $post['participant_accommodation'] ?? [];
                $dietaryNotes = $post['participant_dietary_notes'] ?? [];
                foreach ($names as $idx => $nameRaw) {
                    $name = trim((string)$nameRaw);
                    if ($name === '') {
                        continue;
                    }
                    $age = ($ages[$idx] ?? '') !== '' ? (int)$ages[$idx] : null;
                    $acc = trim((string)($accommodations[$idx] ?? 'alojamento'));
                    $sex = trim((string)($sexes[$idx] ?? ''));
                    $value = calculate_participant_value($this->pdo, $age, $acc);
                    $participantStmt->execute([
                        $groupId,
                        $name,
                        $sex,
                        $age,
                        age_band($age),
                        $acc,
                        $acc === 'casa' ? 0 : 1,
                        0,
                        $value,
                        trim((string)($dietaryNotes[$idx] ?? '')),
                    ]);
                }
            }

            $this->financialService->recalculateGroup($groupId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return [
            'group_id' => $groupId,
            'access_code' => $code,
            'responsible_name' => $responsibleName,
            'responsible_email' => $responsibleEmail,
        ];
    }

    public function sendRegistrationConfirmation(int $groupId, string $responsibleEmail): void
    {
        if ($groupId <= 0 || $responsibleEmail === '' || !filter_var($responsibleEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $freshGroupStmt = $this->pdo->prepare('SELECT * FROM groups WHERE id=? LIMIT 1');
        $freshGroupStmt->execute([$groupId]);
        $freshGroup = $freshGroupStmt->fetch();
        if (!$freshGroup) {
            return;
        }
        $freshParticipantsStmt = $this->pdo->prepare('SELECT * FROM participants WHERE group_id=? ORDER BY is_responsible DESC, full_name ASC');
        $freshParticipantsStmt->execute([$groupId]);
        $freshParticipants = $freshParticipantsStmt->fetchAll();
        $subject = 'Confirmação da inscrição - ' . setting($this->pdo, 'event_name', 'Retiro 2027 - ICNV Catedral');
        $body = build_registration_confirmation_email($this->pdo, $freshGroup, $freshParticipants);
        @send_simple_html_email($responsibleEmail, $subject, $body, setting($this->pdo, 'reports_email_from', 'noreply@icnvcatedral.local'));
    }

    /**
     * Atualiza a inscrição a partir do fluxo "já inscrito".
     * @return array{id:int,responsible_name:string,responsible_email:string,access_code:string}
     */
    public function updateViaLookup(int $groupId, array $post): array
    {
        $group = $this->fetchValidatedLookupGroup($groupId, $post);
        $responsibleName = trim((string)($post['responsible_name'] ?? ''));
        $responsibleAge = ($post['responsible_age'] ?? '') !== '' ? (int)$post['responsible_age'] : null;
        $responsiblePhone = normalize_phone(trim((string)($post['responsible_phone'] ?? '')));
        $responsibleEmail = trim((string)($post['responsible_email'] ?? ''));
        $paymentMethod = trim((string)($post['payment_method'] ?? 'nao_definido'));
        $installments = max(1, min((int)($post['installments'] ?? 1), (int)setting($this->pdo, 'max_installments', '10')));
        $notes = trim((string)($post['notes'] ?? ''));

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('UPDATE groups SET responsible_name=?, responsible_age=?, responsible_phone=?, responsible_email=?, payment_method=?, installments=?, notes=? WHERE id=?');
            $stmt->execute([$responsibleName, $responsibleAge, $responsiblePhone, $responsibleEmail, $paymentMethod, $installments, $notes, $groupId]);
            rt2027_update_responsible_participant($this->pdo, $groupId, $post);
            sync_group_participants($this->pdo, $groupId, $post, true);
            $this->financialService->recalculateGroup($groupId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        $stmt = $this->pdo->prepare('SELECT id, responsible_name, responsible_email, access_code FROM groups WHERE id=? LIMIT 1');
        $stmt->execute([$groupId]);
        return $stmt->fetch() ?: [
            'id' => $groupId,
            'responsible_name' => $responsibleName,
            'responsible_email' => $responsibleEmail,
            'access_code' => $group['access_code'],
        ];
    }

    /**
     * @return array{id:int,responsible_name:string,responsible_email:string,access_code:string}
     */
    public function registerLookupPayment(int $groupId, array $post, array $files = []): array
    {
        $group = $this->fetchValidatedLookupGroup($groupId, $post);
        $amount = parse_money_to_float((string)($post['amount_paid'] ?? '0'));
        if ($amount <= 0) {
            throw new InvalidArgumentException('Informe um valor válido para o pagamento.');
        }

        $installments = max(1, (int)$group['installments']);
        $progress = installments_progress($this->pdo, $groupId, $installments);
        $installmentNumber = max(1, min((int)($post['installment_number'] ?? $progress['next']), $installments));
        $paymentMethod = trim((string)($post['payment_method'] ?? ($group['payment_method'] ?? 'pix')));
        if ($paymentMethod === 'nao_definido') {
            $paymentMethod = 'pix';
        }
        $date = trim((string)($post['payment_date'] ?? date('Y-m-d')));
        $notes = trim((string)($post['notes'] ?? ''));
        $receiptPath = rt2027_store_uploaded_receipt($files['payment_receipt'] ?? [], rt2027_storage_path('comprovantes'));

        $this->financialService->registerPayment($groupId, $amount, $paymentMethod, $installmentNumber, $date, $receiptPath, $notes);

        return [
            'id' => (int)$group['id'],
            'responsible_name' => (string)$group['responsible_name'],
            'responsible_email' => (string)($group['responsible_email'] ?? ''),
            'access_code' => (string)$group['access_code'],
        ];
    }

    private function fetchValidatedLookupGroup(int $groupId, array $post): array
    {
        $loginValue = trim((string)($post['lookup_login'] ?? ''));
        $searchName = trim((string)($post['lookup_name'] ?? ''));
        $group = fetch_group_for_lookup($this->pdo, $loginValue, $searchName);
        if (!$group || (int)$group['id'] !== $groupId) {
            throw new RuntimeException('Não foi possível validar o acesso para atualizar a inscrição.');
        }
        return $group;
    }
}
