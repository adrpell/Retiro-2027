<?php

final class FinancialService
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Recalcula totais financeiros e métricas derivadas de uma inscrição.
     * Mantém a regra atual do sistema, apenas centralizando a lógica fora das rotas.
     */
    public function recalculateGroup(int $groupId): void
    {
        $stmt = $this->pdo->prepare('SELECT id, age, accommodation_choice FROM participants WHERE group_id = ?');
        $stmt->execute([$groupId]);
        $participants = $stmt->fetchAll();

        $subtotal = 0.0;
        $hasChild = false;
        $hasElderly = false;
        
        $up = $this->pdo->prepare('UPDATE participants SET calculated_value = ? WHERE id = ?');

        foreach ($participants as $participant) {
            $age = $participant['age'] !== null ? (int)$participant['age'] : null;
            $value = calculate_participant_value($this->pdo, $age, (string)$participant['accommodation_choice']);
            $subtotal += $value;
            if (is_child($participant['age'])) {
                $hasChild = true;
            }
            if (is_elderly($participant['age'])) {
                $hasElderly = true;
            }
            $up->execute([$value, (int)$participant['id']]);
        }

        $count = count($participants);
        $discountPercent = $count > 1 ? (float)setting($this->pdo, 'family_discount_percent', '0') : 0.0;
        $fee = (float)setting($this->pdo, 'registration_fee', '0');
        $discountValue = $subtotal * ($discountPercent / 100);
        $total = max(0, $subtotal - $discountValue + $fee);

        $paidStmt = $this->pdo->prepare('SELECT COALESCE(SUM(amount_paid),0) FROM payments WHERE group_id = ?');
        $paidStmt->execute([$groupId]);
        $paid = (float)$paidStmt->fetchColumn();
        $pending = max(0, $total - $paid);

        $groupAccommodation = $this->resolveGroupAccommodation($groupId);
        $statusFinance = $paid <= 0 ? 'pendente' : ($pending <= 0 ? 'quitado' : 'parcial');

        $update = $this->pdo->prepare(
            'UPDATE groups SET total_people = ?, suggested_value = ?, discount_value = ?, amount_paid = ?, amount_pending = ?, financial_status = ?, group_accommodation = ?, has_child = ?, has_elderly = ? WHERE id = ?'
        );
        $update->execute([
            $count,
            $total,
            $discountValue,
            $paid,
            $pending,
            $statusFinance,
            $groupAccommodation,
            $hasChild ? 1 : 0,
            $hasElderly ? 1 : 0,
            $groupId,
        ]);
    }

    public function registerPayment(int $groupId, float $amount, string $method, int $installment, string $date, ?string $receiptPath, string $notes): void
    {
        if ($groupId <= 0 || $amount <= 0) {
            throw new InvalidArgumentException('Pagamento inválido para registro.');
        }

        rt2027_insert_payment($this->pdo, $groupId, $amount, $method, $installment, $date, $receiptPath, $notes);
        $this->recalculateGroup($groupId);
    }

    public function updateGroupFromAdmin(int $groupId, array $post, array $files = []): void
    {
        if ($groupId <= 0) {
            throw new InvalidArgumentException('Inscrição inválida para atualização.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare(
                'UPDATE groups SET responsible_name=?, responsible_age=?, responsible_phone=?, responsible_email=?, registration_type=?, payment_method=?, installments=?, status=?, notes=? WHERE id=?'
            );
            $stmt->execute([
                trim((string)($post['responsible_name'] ?? '')),
                ($post['responsible_age'] ?? '') !== '' ? (int)$post['responsible_age'] : null,
                normalize_phone(trim((string)($post['responsible_phone'] ?? ''))),
                trim((string)($post['responsible_email'] ?? '')),
                trim((string)($post['registration_type'] ?? 'individual')),
                trim((string)($post['payment_method'] ?? 'nao_definido')),
                max(1, (int)($post['installments'] ?? 1)),
                trim((string)($post['status'] ?? 'intencao')),
                trim((string)($post['notes'] ?? '')),
                $groupId,
            ]);

            rt2027_update_responsible_participant($this->pdo, $groupId, $post);
            sync_group_participants($this->pdo, $groupId, $post, false);

            $receiptPath = rt2027_store_uploaded_receipt($files['receipt'] ?? [], rt2027_storage_path('comprovantes'));
            if ($receiptPath) {
                $this->pdo->prepare('UPDATE groups SET receipt_file=? WHERE id=?')->execute([$receiptPath, $groupId]);
            }

            $this->recalculateGroup($groupId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function updateParticipantFromAdmin(int $groupId, int $participantId, array $post): void
    {
        if ($groupId <= 0 || $participantId <= 0) {
            throw new InvalidArgumentException('Participante inválido para atualização.');
        }

        $age = ($post['age'] ?? '') !== '' ? (int)$post['age'] : null;
        $accommodation = trim((string)($post['accommodation_choice'] ?? 'alojamento'));

        $stmt = $this->pdo->prepare(
            'UPDATE participants SET full_name=?, sex=?, age=?, age_band=?, accommodation_choice=?, sleeps_on_site=?, calculated_value=?, dietary_notes=? WHERE id=? AND group_id=?'
        );
        $stmt->execute([
            trim((string)($post['full_name'] ?? '')),
            trim((string)($post['sex'] ?? '')),
            $age,
            age_band($age),
            $accommodation,
            $accommodation === 'casa' ? 0 : 1,
            calculate_participant_value($this->pdo, $age, $accommodation),
            trim((string)($post['dietary_notes'] ?? '')),
            $participantId,
            $groupId,
        ]);

        if (!empty($post['is_responsible'])) {
            $stmt = $this->pdo->prepare('UPDATE groups SET responsible_name=?, responsible_age=? WHERE id=?');
            $stmt->execute([trim((string)($post['full_name'] ?? '')), $age, $groupId]);
        }

        $this->recalculateGroup($groupId);
    }

    public function deleteGroup(int $groupId): void
    {
        if ($groupId <= 0) {
            throw new InvalidArgumentException('Inscrição inválida para exclusão.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmtGroup = $this->pdo->prepare('SELECT receipt_file FROM groups WHERE id = ? LIMIT 1');
            $stmtGroup->execute([$groupId]);
            $groupRow = $stmtGroup->fetch();
            if (!$groupRow) {
                throw new RuntimeException('Inscrição não encontrada.');
            }

            $stmtPayments = $this->pdo->prepare('SELECT receipt_file FROM payments WHERE group_id = ?');
            $stmtPayments->execute([$groupId]);
            $paymentReceipts = $stmtPayments->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $this->deleteTaskAssignmentsForGroup($groupId);
            $this->pdo->prepare('DELETE FROM payments WHERE group_id = ?')->execute([$groupId]);
            $this->pdo->prepare('DELETE FROM participants WHERE group_id = ?')->execute([$groupId]);
            $this->pdo->prepare('DELETE FROM groups WHERE id = ?')->execute([$groupId]);
            $this->pdo->commit();

            rt2027_delete_relative_file($groupRow['receipt_file'] ?? null);
            foreach ($paymentReceipts as $receiptFile) {
                rt2027_delete_relative_file((string)$receiptFile);
            }
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function resolveGroupAccommodation(int $groupId): string
    {
        $accommodationSummaryStmt = $this->pdo->prepare('SELECT COUNT(DISTINCT accommodation_choice) FROM participants WHERE group_id = ?');
        $accommodationSummaryStmt->execute([$groupId]);
        $diff = (int)$accommodationSummaryStmt->fetchColumn();

        if ($diff === 1) {
            $single = $this->pdo->prepare('SELECT accommodation_choice FROM participants WHERE group_id = ? LIMIT 1');
            $single->execute([$groupId]);
            return (string)($single->fetchColumn() ?: 'personalizado');
        }

        return 'personalizado';
    }

    private function deleteTaskAssignmentsForGroup(int $groupId): void
    {
        try {
            $this->pdo->prepare('DELETE ta FROM task_assignments ta JOIN participants p ON p.id = ta.participant_id WHERE p.group_id = ?')
                ->execute([$groupId]);
        } catch (Throwable $e) {
            // O módulo de tarefas é opcional na exclusão; se a tabela não existir, a inscrição ainda deve ser removida.
        }
    }
}
