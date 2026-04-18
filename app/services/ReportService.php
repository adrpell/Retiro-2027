<?php

/**
 * Concentra a preparação de relatórios administrativos.
 * Mantém a responsabilidade das rotas apenas para autorização, resposta HTTP e feedback.
 */
final class ReportService
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{rows:array,totals:array,html:string,snapshot:array,generated_at:string,sort_by:string,sort_dir:string,email_to:string} */
    public function buildExecutiveReport(string $sortBy = 'access_code', string $sortDir = 'asc', ?string $emailTo = null, string $mode = 'html'): array
    {
        $pdo = $this->pdo;
        $reportRows = report_executive_rows($pdo, $sortBy, $sortDir);
        $reportTotals = [
            'groups' => count($reportRows),
            'people' => array_sum(array_map(static fn(array $g): int => count($g['membros'] ?? []), $reportRows)),
        ];
        $generatedAt = date('d/m/Y H:i');
        $reportSortBy = $sortBy;
        $reportSortDir = $sortDir;
        $emailTo = trim((string)$emailTo);

        ob_start();
        include rt2027_template_path('admin/report_executive.php');
        $reportHtml = ob_get_clean();

        $snapshot = save_report_snapshot(
            $this->pdo,
            $reportHtml,
            $mode === 'pdf' ? 'pdf_browser' : ($mode === 'email' ? 'email' : 'html'),
            $sortBy,
            $sortDir,
            $emailTo !== '' ? $emailTo : null,
            $mode === 'email' ? 'preparado' : 'gerado'
        );

        return [
            'rows' => $reportRows,
            'totals' => $reportTotals,
            'html' => $reportHtml,
            'snapshot' => $snapshot,
            'generated_at' => $generatedAt,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'email_to' => $emailTo,
        ];
    }

    public function sendExecutiveReportEmail(int $snapshotId, string $emailTo): bool
    {
        $emailTo = trim($emailTo);
        if ($snapshotId <= 0 || $emailTo === '') {
            throw new InvalidArgumentException('Informe um e-mail para envio.');
        }

        $stmt = $this->pdo->prepare('SELECT * FROM report_history WHERE id=? LIMIT 1');
        $stmt->execute([$snapshotId]);
        $snapshot = $stmt->fetch();
        if (!$snapshot || empty($snapshot['file_path'])) {
            throw new RuntimeException('Snapshot do relatório não encontrado.');
        }

        $link = rt2027_build_app_url('index.php?route=admin/report-history-view&id=' . (int)$snapshot['id']);
        $subject = 'Relatório executivo - ' . setting($this->pdo, 'event_name', 'Retiro 2027 - ICNV Catedral');
        $body = '<p>Olá,</p><p>Segue em anexo o relatório executivo gerado pelo sistema.</p><p>Também é possível visualizá-lo por este link: <a href="' . h($link) . '">' . h($link) . '</a></p>';
        $sent = send_report_email($emailTo, $subject, $body, rt2027_root_path($snapshot['file_path']), basename($snapshot['file_path']), setting($this->pdo, 'reports_email_from', 'noreply@icnvcatedral.local'));
        $this->pdo->prepare('UPDATE report_history SET status=?, recipient_email=? WHERE id=?')->execute([$sent ? 'enviado' : 'falha_envio', $emailTo, $snapshotId]);
        return $sent;
    }

    /** @return array{rows:array,summary:array} */
    public function getFoodReportData(?int $day = null, ?string $mealType = null): array
    {
        $rows = rt2027_food_report_rows($this->pdo, $day, $mealType);
        return [
            'rows' => $rows,
            'summary' => rt2027_food_report_summary($rows),
        ];
    }

    public function renderFoodReportHtml(?int $day = null, ?string $mealType = null): array
    {
        $pdo = $this->pdo;
        $data = $this->getFoodReportData($day, $mealType);
        $foodReportRows = $data['rows'];
        $foodReportSummary = $data['summary'];
        $foodReportMeals = rt2027_food_report_sort_meals(rt2027_food_report_grouped($foodReportRows));
        $generatedAt = date('d/m/Y H:i');
        ob_start();
        include rt2027_template_path('admin/food_report_html.php');
        $html = ob_get_clean();
        $saved = rt2027_food_export_html_path($this->pdo, $html);
        return $data + ['html' => $html, 'saved' => $saved, 'generated_at' => $generatedAt, 'meals' => $foodReportMeals];
    }

    public function outputFoodReportCsv(?int $day = null, ?string $mealType = null): void
    {
        $data = $this->getFoodReportData($day, $mealType);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=relatorio_alimentacao.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Dia','Refeição','Título','Data','Horário','Pessoas previstas','Status','Item do cardápio','Qtd. estimada','Ingrediente/Despensa','Consumo base','Unidade','Modo']);
        foreach ($data['rows'] as $row) {
            fputcsv($out, [
                rt2027_food_day_label((int)$row['retreat_day']),
                rt2027_food_meal_types()[$row['meal_type']] ?? $row['meal_type'],
                $row['title'],
                $row['meal_date'],
                $row['meal_time'],
                $row['estimated_people'],
                $row['status'],
                $row['menu_item_name'],
                $row['quantity_estimate'],
                $row['pantry_item_name'],
                $row['quantity_base'],
                $row['ingredient_unit'],
                $row['consumption_mode'] === 'per_person' ? 'por pessoa' : 'fixo',
            ]);
        }
        fclose($out);
    }

    public function renderTasksReportHtml(?string $slotDate = null, ?string $shiftKey = null): array
    {
        rt2027_task_ensure_schema($this->pdo);
        $taskShiftOptions = rt2027_task_shift_options($this->pdo);
        $taskSlots = rt2027_task_schedule_rows($this->pdo, $slotDate ?: null, $shiftKey ?: null);
        $taskSummary = rt2027_task_schedule_summary($taskSlots);
        $taskReportGrouped = rt2027_task_report_grouped($taskSlots);
        $generatedAt = date('d/m/Y H:i');
        ob_start();
        include rt2027_template_path('admin/tasks_report_html.php');
        $html = ob_get_clean();
        return [
            'html' => $html,
            'shift_options' => $taskShiftOptions,
            'slots' => $taskSlots,
            'summary' => $taskSummary,
            'grouped' => $taskReportGrouped,
            'generated_at' => $generatedAt,
        ];
    }
}
