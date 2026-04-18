<?php
function q_filters(array $keys): array {
    $result = [];
    foreach ($keys as $k) {
        $result[$k] = trim((string)($_GET[$k] ?? ''));
    }
    return $result;
}

function table_sort(string $default = 'id'): array {
    $allowed = ['id','access_code','responsible_name','registration_type','total_people','group_accommodation','status','financial_status','suggested_value','amount_pending','created_at','full_name','age','accommodation_choice','payment_method'];
    $sort = $_GET['sort'] ?? $default;
    if (!in_array($sort, $allowed, true)) $sort = $default;
    $dir = strtolower($_GET['dir'] ?? 'desc');
    $dir = $dir === 'asc' ? 'asc' : 'desc';
    return [$sort, $dir];
}

function sort_link(string $column, string $label, string $route, array $extra = []): string {
    $currentSort = $_GET['sort'] ?? '';
    $currentDir = strtolower($_GET['dir'] ?? 'desc');
    $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
    $params = array_merge($_GET, $extra, ['route' => $route, 'sort' => $column, 'dir' => $nextDir]);
    return '<a href="index.php?' . http_build_query($params) . '">' . h($label) . '</a>';
}



function report_executive_rows(PDO $pdo, string $sortBy = 'access_code', string $sortDir = 'asc'): array {
    $orderSql = report_sort_sql($sortBy, $sortDir);
    $sql = "SELECT g.id as group_id, g.access_code, g.responsible_name, g.responsible_phone, g.group_accommodation, g.notes, g.created_at, p.full_name, p.age, p.calculated_value, p.accommodation_choice, p.is_responsible
            FROM groups g
            LEFT JOIN participants p ON p.group_id = g.id
            ORDER BY {$orderSql}";
    $rows = $pdo->query($sql)->fetchAll();
    $grouped = [];
    foreach ($rows as $row) {
        $gid = (int)$row['group_id'];
        if (!isset($grouped[$gid])) {
            $grouped[$gid] = [
                'id' => $gid,
                'codigo' => (string)$row['access_code'],
                'responsavel' => (string)$row['responsible_name'],
                'telefone' => (string)($row['responsible_phone'] ?? ''),
                'acomodacao' => accommodation_label((string)($row['group_accommodation'] ?? 'personalizado')),
                'divideChale' => false,
                'observacoes' => (string)($row['notes'] ?? ''),
                'preferenciaCompartilhar' => '',
                'membros' => [],
                'origem' => 'sistema',
                'created_at' => (string)($row['created_at'] ?? ''),
            ];
        }
        if ($row['full_name'] !== null) {
            $grouped[$gid]['membros'][] = [
                'nome' => (string)$row['full_name'],
                'idade' => $row['age'] !== null ? (int)$row['age'] : null,
                'valor' => $row['calculated_value'] !== null ? (float)$row['calculated_value'] : null,
                'acomodacao' => (string)($row['accommodation_choice'] ?? ''),
                'responsavel' => !empty($row['is_responsible']),
            ];
        }
    }
    return array_values($grouped);
}
