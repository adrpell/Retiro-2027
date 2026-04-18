<?php

final class CheckinService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ensureSchema(): void
    {
        rt2027_ensure_checkin_schema($this->pdo);
    }

    public function getScreenData(array $filters): array
    {
        $this->ensureSchema();

        $filters = array_merge([
            'search' => '',
            'sex' => '',
            'accommodation_choice' => '',
            'checkin_status' => '',
            'arrival_from' => '',
            'arrival_to' => '',
        ], $filters);

        $where = [];
        $params = [];

        if ($filters['search'] !== '') {
            $where[] = '(p.full_name LIKE ? OR g.access_code LIKE ? OR g.responsible_name LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        foreach (['sex', 'accommodation_choice'] as $key) {
            if ($filters[$key] !== '') {
                $where[] = 'p.' . $key . ' = ?';
                $params[] = $filters[$key];
            }
        }

        if ($filters['checkin_status'] !== '') {
            $where[] = "COALESCE(p.checkin_status, 'nao') = ?";
            $params[] = $filters['checkin_status'];
        }

        if ($filters['arrival_from'] !== '') {
            $where[] = 'p.checked_in_at >= ?';
            $params[] = $this->normalizeDateTimeFilter($filters['arrival_from'], true);
        }

        if ($filters['arrival_to'] !== '') {
            $where[] = 'p.checked_in_at <= ?';
            $params[] = $this->normalizeDateTimeFilter($filters['arrival_to'], false);
        }

        $sql = "SELECT p.id, p.group_id, p.full_name, p.sex, p.age, p.accommodation_choice, p.is_responsible,
                       COALESCE(p.checkin_status, 'nao') AS checkin_status,
                       p.checked_in_at,
                       p.checked_in_by_admin_id,
                       g.access_code,
                       g.responsible_name,
                       a.name AS checked_in_by_name
                FROM participants p
                JOIN groups g ON g.id = p.group_id
                LEFT JOIN admins a ON a.id = p.checked_in_by_admin_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY g.access_code ASC, p.is_responsible DESC, p.full_name ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        $groups = [];
        foreach ($rows as $row) {
            $gid = (int)$row['group_id'];
            if (!isset($groups[$gid])) {
                $groups[$gid] = ['total' => 0, 'checked' => 0];
            }
            $groups[$gid]['total']++;
            if (($row['checkin_status'] ?? 'nao') === 'sim') {
                $groups[$gid]['checked']++;
            }
        }

        foreach ($rows as &$row) {
            $gid = (int)$row['group_id'];
            $row['group_total'] = $groups[$gid]['total'] ?? 1;
            $row['group_checked'] = $groups[$gid]['checked'] ?? 0;
            $row['group_all_checked'] = !empty($row['group_total']) && (int)$row['group_total'] === (int)$row['group_checked'];
            $row['scan_url'] = rt2027_build_app_url(route_url('admin/checkin/qr', ['access_code' => (string)$row['access_code']]));
        }
        unset($row);

        $stats = [
            'total_listed' => count($rows),
            'checked_in' => (int)$this->pdo->query("SELECT COUNT(*) FROM participants WHERE COALESCE(checkin_status, 'nao')='sim'")->fetchColumn(),
            'pending' => (int)$this->pdo->query("SELECT COUNT(*) FROM participants WHERE COALESCE(checkin_status, 'nao')<>'sim'")->fetchColumn(),
            'families' => (int)$this->pdo->query('SELECT COUNT(*) FROM groups')->fetchColumn(),
            'last_action_at' => null,
        ];

        if (rt2027_table_exists_safe($this->pdo, 'checkin_history')) {
            $stats['last_action_at'] = $this->pdo->query('SELECT MAX(created_at) FROM checkin_history')->fetchColumn() ?: null;
        } else {
            $stats['last_action_at'] = $this->pdo->query('SELECT MAX(checked_in_at) FROM participants')->fetchColumn() ?: null;
        }

        return [
            'rows' => $rows,
            'stats' => $stats,
            'history' => $this->recentHistory(12, $filters),
        ];
    }

    public function toggleParticipant(int $participantId, string $status, ?int $adminId = null, string $source = 'manual'): array
    {
        $this->ensureSchema();
        $participant = $this->fetchParticipant($participantId);
        if (!$participant) {
            throw new InvalidArgumentException('Participante não encontrado.');
        }

        $status = $this->normalizeStatus($status);
        $previousStatus = (string)($participant['checkin_status'] ?? 'nao');
        $checkedAt = $status === 'sim' ? date('Y-m-d H:i:s') : null;
        $checkedBy = $status === 'sim' ? $adminId : null;

        $stmt = $this->pdo->prepare('UPDATE participants SET checkin_status = ?, checked_in_at = ?, checked_in_by_admin_id = ? WHERE id = ?');
        $stmt->execute([$status, $checkedAt, $checkedBy, $participantId]);

        $updated = $this->fetchParticipant($participantId) ?: $participant;
        $this->recordHistory((int)$participant['group_id'], $participantId, $previousStatus, $status, $adminId, $source, 'participant');
        return $updated;
    }

    public function toggleGroup(int $groupId, string $status, ?int $adminId = null, string $source = 'manual_batch'): array
    {
        $this->ensureSchema();
        $groupId = max(0, $groupId);
        if ($groupId <= 0) {
            throw new InvalidArgumentException('Grupo inválido.');
        }
        $status = $this->normalizeStatus($status);
        $stmt = $this->pdo->prepare("SELECT id, group_id, full_name, COALESCE(checkin_status, 'nao') AS checkin_status FROM participants WHERE group_id = ? ORDER BY is_responsible DESC, full_name ASC");
        $stmt->execute([$groupId]);
        $participants = $stmt->fetchAll() ?: [];
        if (!$participants) {
            throw new InvalidArgumentException('Nenhum participante encontrado para o grupo.');
        }

        $checkedAt = $status === 'sim' ? date('Y-m-d H:i:s') : null;
        $checkedBy = $status === 'sim' ? $adminId : null;

        $this->pdo->beginTransaction();
        try {
            $update = $this->pdo->prepare('UPDATE participants SET checkin_status = ?, checked_in_at = ?, checked_in_by_admin_id = ? WHERE id = ?');
            foreach ($participants as $participant) {
                $update->execute([$status, $checkedAt, $checkedBy, (int)$participant['id']]);
                $this->recordHistory($groupId, (int)$participant['id'], (string)$participant['checkin_status'], $status, $adminId, $source, 'group');
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        return [
            'group_id' => $groupId,
            'participants_updated' => count($participants),
            'status' => $status,
            'checked_in_at' => $checkedAt,
        ];
    }

    public function syncParticipant(array $payload, ?int $adminId = null): array
    {
        return $this->toggleParticipant((int)($payload['participant_id'] ?? 0), (string)($payload['checkin_status'] ?? 'nao'), $adminId, 'offline_sync');
    }

    public function processQrAccessCode(string $accessCode, ?int $adminId = null): array
    {
        $this->ensureSchema();
        $accessCode = trim($accessCode);
        if ($accessCode === '') {
            throw new InvalidArgumentException('Código de acesso inválido.');
        }
        $stmt = $this->pdo->prepare('SELECT id FROM groups WHERE access_code = ? LIMIT 1');
        $stmt->execute([$accessCode]);
        $groupId = (int)$stmt->fetchColumn();
        if ($groupId <= 0) {
            throw new InvalidArgumentException('Inscrição não encontrada para o QR informado.');
        }
        $result = $this->toggleGroup($groupId, 'sim', $adminId, 'qr_scan');
        $result['access_code'] = $accessCode;
        return $result;
    }

    public function recentHistory(int $limit = 12, array $filters = []): array
    {
        $this->ensureSchema();
        if (!rt2027_table_exists_safe($this->pdo, 'checkin_history')) {
            return [];
        }
        $limit = max(1, min(5000, $limit));
        $where = [];
        $params = [];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(p.full_name LIKE ? OR g.access_code LIKE ? OR g.responsible_name LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        $arrivalFrom = trim((string)($filters['arrival_from'] ?? ''));
        if ($arrivalFrom !== '') {
            $where[] = 'h.created_at >= ?';
            $params[] = $this->normalizeDateTimeFilter($arrivalFrom, true);
        }
        $arrivalTo = trim((string)($filters['arrival_to'] ?? ''));
        if ($arrivalTo !== '') {
            $where[] = 'h.created_at <= ?';
            $params[] = $this->normalizeDateTimeFilter($arrivalTo, false);
        }

        $sql = "SELECT h.*, p.full_name, g.access_code, g.responsible_name, a.name AS admin_name
                FROM checkin_history h
                LEFT JOIN participants p ON p.id = h.participant_id
                LEFT JOIN groups g ON g.id = h.group_id
                LEFT JOIN admins a ON a.id = h.changed_by_admin_id";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY h.created_at DESC, h.id DESC LIMIT ' . (int)$limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    private function fetchParticipant(int $participantId): ?array
    {
        $participantId = max(0, $participantId);
        if ($participantId <= 0) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT p.id, p.group_id, p.full_name,
                    COALESCE(p.checkin_status, 'nao') AS checkin_status,
                    p.checked_in_at,
                    p.checked_in_by_admin_id,
                    a.name AS checked_in_by_name
                FROM participants p
                LEFT JOIN admins a ON a.id = p.checked_in_by_admin_id
                WHERE p.id = ? LIMIT 1");
        $stmt->execute([$participantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function recordHistory(int $groupId, int $participantId, string $previousStatus, string $newStatus, ?int $adminId, string $source, string $context): void
    {
        if (!rt2027_table_exists_safe($this->pdo, 'checkin_history')) {
            return;
        }
        $stmt = $this->pdo->prepare('INSERT INTO checkin_history (participant_id, group_id, previous_status, new_status, changed_by_admin_id, change_source, change_context) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$participantId, $groupId, $previousStatus, $newStatus, $adminId, $source, $context]);
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        if (!in_array($status, ['sim', 'nao'], true)) {
            throw new InvalidArgumentException('Status de check-in inválido.');
        }
        return $status;
    }

    private function normalizeDateTimeFilter(string $value, bool $isStart): string
    {
        $value = trim($value);
        if ($value === '') {
            return $value;
        }
        $value = str_replace('T', ' ', $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value . ($isStart ? ' 00:00:00' : ' 23:59:59');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
            return $value . ':00';
        }
        return $value;
    }
}
