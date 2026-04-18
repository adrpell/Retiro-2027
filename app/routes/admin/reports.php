<?php

// Rotas administrativas do módulo: reports

if ($route === 'admin/reports-executive') {
    require_admin();
    $sortBy = trim((string)($_GET['sort_by'] ?? 'access_code'));
    $sortDir = strtolower(trim((string)($_GET['sort_dir'] ?? 'asc'))) === 'desc' ? 'desc' : 'asc';
    $mode = trim((string)($_GET['mode'] ?? 'html'));
    $emailTo = trim((string)($_POST['email_to'] ?? $_GET['email_to'] ?? ''));

    try {
        $report = report_service($pdo)->buildExecutiveReport($sortBy, $sortDir, $emailTo, $mode);
        log_activity($pdo, $_SESSION['admin_id'] ?? null, 'report_generated', 'Relatório executivo ' . $report['snapshot']['basename'] . ' (' . $mode . ')');
    } catch (Throwable $e) {
        error_log('admin/reports-executive failed: ' . $e->getMessage());
        flash('error', 'Não foi possível gerar o relatório executivo neste momento.');
        redirect_to('admin/reports');
    }

    if ($mode === 'email') {
        validate_csrf();
        try {
            $sent = report_service($pdo)->sendExecutiveReportEmail((int)$report['snapshot']['id'], $emailTo);
            flash($sent ? 'success' : 'error', $sent ? 'Relatório enviado por e-mail.' : 'Não foi possível enviar o e-mail neste servidor. O histórico foi salvo normalmente.');
        } catch (InvalidArgumentException $e) {
            flash('error', $e->getMessage());
        } catch (Throwable $e) {
            flash('error', 'Não foi possível enviar o relatório por e-mail.');
        }
        redirect_to('admin/reports');
    }

    if ($mode === 'pdf') {
        header('Content-Type: text/html; charset=UTF-8');
        echo str_replace('</head>', '<style>@media print{.toolbar{display:none!important}.wrap{max-width:none;padding:0}.hero{border-radius:0}.card{break-inside:avoid}}</style><script>window.addEventListener("load",function(){setTimeout(function(){window.print();},250);});</script></head>', $report['html']);
        exit;
    }

    if ($mode === 'download_html') {
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $report['snapshot']['basename'] . '.html"');
        echo $report['html'];
        exit;
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo $report['html'];
    exit;
}
if ($route === 'admin/report-history-view') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM report_history WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $history = $stmt->fetch();
    if (!$history || empty($history['file_path']) || !is_file(rt2027_root_path($history['file_path']))) {
        http_response_code(404);
        exit('Relatório não encontrado.');
    }
    header('Content-Type: text/html; charset=UTF-8');
    readfile(rt2027_root_path($history['file_path']));
    exit;
}

if ($route === 'admin/report-history-download') {
    require_admin();
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT * FROM report_history WHERE id=? LIMIT 1');
    $stmt->execute([$id]);
    $history = $stmt->fetch();
    if (!$history || empty($history['file_path']) || !is_file(rt2027_root_path($history['file_path']))) {
        http_response_code(404);
        exit('Relatório não encontrado.');
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="relatorio_historico_' . (int)$history['id'] . '.html"');
    readfile(rt2027_root_path($history['file_path']));
    exit;
}

if ($route === 'admin/reports' && isset($_GET['export'])) {
    require_admin();
    $export = (string)$_GET['export'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    if ($export === 'groups') {
        header('Content-Disposition: attachment; filename=inscricoes_retiro_2027.csv');
        fputcsv($out, ['Código','Responsável','Tipo','Total pessoas','Acomodação','Status','Financeiro','Valor sugerido','Pago','Pendente']);
        $rows = $pdo->query('SELECT access_code, responsible_name, registration_type, total_people, group_accommodation, status, financial_status, suggested_value, amount_paid, amount_pending FROM groups ORDER BY access_code ASC')->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['access_code'],$row['responsible_name'],$row['registration_type'],$row['total_people'],$row['group_accommodation'],$row['status'],$row['financial_status'],$row['suggested_value'],$row['amount_paid'],$row['amount_pending']]);
        }
        fclose($out);
        exit;
    }
    if ($export === 'participants') {
        header('Content-Disposition: attachment; filename=participantes_retiro_2027.csv');
        fputcsv($out, ['Código','Responsável','Participante','Sexo','Idade','Faixa etária','Acomodação','Valor calculado','Responsável?','Check-in']);
        $rows = $pdo->query("SELECT g.access_code, g.responsible_name, p.full_name, p.sex, p.age, p.age_band, p.accommodation_choice, p.calculated_value, p.is_responsible, p.checkin_status FROM participants p JOIN groups g ON g.id = p.group_id ORDER BY g.created_at DESC, p.is_responsible DESC, p.full_name ASC")->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['access_code'],$row['responsible_name'],$row['full_name'],$row['sex'],$row['age'],$row['age_band'],$row['accommodation_choice'],$row['calculated_value'],$row['is_responsible'] ? 'Sim' : 'Não',$row['checkin_status']]);
        }
        fclose($out);
        exit;
    }
    if ($export === 'financial') {
        header('Content-Disposition: attachment; filename=financeiro_retiro_2027.csv');
        fputcsv($out, ['Código','Responsável','Forma de pagamento','Parcelas','Valor sugerido','Desconto','Pago','Pendente','Status financeiro','Data criação']);
        $rows = $pdo->query("SELECT access_code, responsible_name, payment_method, installments, suggested_value, discount_value, amount_paid, amount_pending, financial_status, created_at FROM groups ORDER BY access_code ASC")->fetchAll();
        foreach ($rows as $row) {
            fputcsv($out, [$row['access_code'],$row['responsible_name'],$row['payment_method'],$row['installments'],$row['suggested_value'],$row['discount_value'],$row['amount_paid'],$row['amount_pending'],$row['financial_status'],$row['created_at']]);
        }
        fclose($out);
        exit;
    }
    fclose($out);
    http_response_code(400);
    echo 'Exportação inválida.';
    exit;
}