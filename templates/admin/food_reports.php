<?php $pageTitle = 'Alimentação • Relatórios'; $mealTypes = rt2027_food_meal_types(); ?>
<div class="rt-admin-shell"><?php include __DIR__ . '/../partials/admin_sidebar.php'; ?><main class="rt-admin-content p-4 lg:p-8"><div class="rt-admin-topbar rounded-3xl"><div><div class="rt-admin-title">Relatório alimentar</div><div class="rt-admin-subtitle">Resumo completo por dia e refeição, com cardápio, pessoas previstas, consumo planejado, consumo real e comparativo entre previsto e executado.</div></div></div>
<div class="grid grid-cols-1 xl:grid-cols-4 gap-6"> 
  <div class="xl:col-span-1 rt-card rounded-3xl p-5">
    <h2 class="text-xl font-semibold mb-4">Filtros</h2>
    <form method="get" class="space-y-4">
      <input type="hidden" name="route" value="admin/food-reports">
      <div><label>Dia</label><select name="day"><option value="">Todos</option><?php for ($d=1;$d<=4;$d++): ?><option value="<?= $d ?>" <?= ((string)$reportDay === (string)$d) ? 'selected' : '' ?>><?= h(rt2027_food_day_label($d)) ?></option><?php endfor; ?></select></div>
      <div><label>Refeição</label><select name="meal_type"><option value="">Todas</option><?php foreach ($mealTypes as $k=>$label): ?><option value="<?= h($k) ?>" <?= ($reportMealType === $k) ? 'selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select></div>
      <div class="flex gap-3 flex-wrap"><button class="rt-btn-primary rounded-full px-5 py-3 font-semibold" type="submit">Aplicar</button><a class="rt-btn-secondary rounded-full px-5 py-3 font-semibold" href="<?= h(route_url('admin/food-reports')) ?>">Limpar</a></div>
    </form>
    <div class="mt-5 pt-5 border-t border-slate-200">
      <div class="flex flex-wrap gap-3">
        <a class="rt-btn-secondary rounded-full px-5 py-3 font-semibold inline-flex" href="<?= h(route_url('admin/food-reports', array_filter(['export' => 'csv', 'day' => $reportDay !== null ? (int)$reportDay : null, 'meal_type' => $reportMealType ?: null], fn($v) => $v !== null && $v !== ''))) ?>">Exportar CSV</a>
        <a class="rt-btn-primary rounded-full px-5 py-3 font-semibold inline-flex" target="_blank" href="<?= h(route_url('admin/food-reports', array_filter(['mode' => 'html', 'day' => $reportDay !== null ? (int)$reportDay : null, 'meal_type' => $reportMealType ?: null], fn($v) => $v !== null && $v !== ''))) ?>">Abrir HTML</a>
        <a class="rt-btn-secondary rounded-full px-5 py-3 font-semibold inline-flex" href="<?= h(route_url('admin/food-reports', array_filter(['mode' => 'download_html', 'day' => $reportDay !== null ? (int)$reportDay : null, 'meal_type' => $reportMealType ?: null], fn($v) => $v !== null && $v !== ''))) ?>">Exportar HTML</a>
        <a class="rt-btn-secondary rounded-full px-5 py-3 font-semibold inline-flex" target="_blank" href="<?= h(route_url('admin/food-reports', array_filter(['mode' => 'pdf', 'day' => $reportDay !== null ? (int)$reportDay : null, 'meal_type' => $reportMealType ?: null], fn($v) => $v !== null && $v !== ''))) ?>">Baixar PDF</a>
      </div>
    </div>
  </div>
  <div class="xl:col-span-3 rt-card rounded-3xl p-5">
    <h2 class="text-xl font-semibold mb-4">Resultado</h2><div class="grid grid-cols-2 xl:grid-cols-5 gap-3 mb-5"><div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3"><div class="text-xs text-slate-500">Refeições</div><div class="text-xl font-bold"><?= (int)($foodReportSummary['meals'] ?? 0) ?></div></div><div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3"><div class="text-xs text-slate-500">Dias</div><div class="text-xl font-bold"><?= (int)($foodReportSummary['days'] ?? 0) ?></div></div><div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3"><div class="text-xs text-slate-500">Pessoas previstas</div><div class="text-xl font-bold"><?= (int)($foodReportSummary['planned_people'] ?? 0) ?></div></div><div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3"><div class="text-xs text-slate-500">Pessoas executadas</div><div class="text-xl font-bold"><?= (int)($foodReportSummary['executed_people'] ?? 0) ?></div></div><div class="rounded-2xl bg-slate-50 border border-slate-200 px-4 py-3"><div class="text-xs text-slate-500">Diferença total</div><div class="text-xl font-bold <?= (($foodReportSummary['diff_total'] ?? 0) > 0) ? 'text-red-600' : 'text-emerald-600' ?>"><?= number_format((float)($foodReportSummary['diff_total'] ?? 0), 3, ',', '.') ?></div></div></div>
    <?php if (!$foodReportRows): ?><div class="text-slate-500">Nenhum dado encontrado para os filtros aplicados.</div><?php else: ?>
      <div class="overflow-x-auto"><table class="min-w-full text-sm"><thead><tr class="text-left text-slate-500"><th class="py-2 pr-4">Dia</th><th class="py-2 pr-4">Refeição</th><th class="py-2 pr-4">Título</th><th class="py-2 pr-4">Pessoas</th><th class="py-2 pr-4">Status</th><th class="py-2 pr-4">Item do cardápio</th><th class="py-2 pr-4">Ingrediente</th><th class="py-2 pr-4">Previsto</th><th class="py-2 pr-4">Executado</th><th class="py-2 pr-4">Diferença</th></tr></thead><tbody>
        <?php foreach ($foodReportRows as $row): ?>
          <tr class="border-t border-slate-200 align-top">
            <td class="py-3 pr-4"><?= h(rt2027_food_day_label((int)$row['retreat_day'])) ?></td>
            <td class="py-3 pr-4"><?= h($mealTypes[$row['meal_type']] ?? $row['meal_type']) ?></td>
            <td class="py-3 pr-4"><div class="font-semibold"><?= h($row['title']) ?></div><div class="text-xs text-slate-500"><?= h($row['meal_date'] ?: '—') ?> · <?= h($row['meal_time'] ?: '--:--') ?></div></td>
            <td class="py-3 pr-4"><div class="font-semibold"><?= (int)$row['estimated_people'] ?></div><div class="text-xs text-slate-500">real: <?= isset($row['executed_people']) && $row['executed_people'] !== null ? (int)$row['executed_people'] : (int)$row['estimated_people'] ?></div></td>
            <td class="py-3 pr-4"><span class="px-3 py-1 rounded-full text-xs <?= status_badge_class($row['status']) ?>"><?= h(ucfirst($row['status'])) ?></span></td>
            <td class="py-3 pr-4"><?= h($row['menu_item_name'] ?: '—') ?></td>
            <td class="py-3 pr-4"><?= h($row['pantry_item_name'] ?: '—') ?></td>
            <td class="py-3 pr-4"><?= $row['planned_quantity'] !== null ? rt2027_format_quantity($row['planned_quantity'], $row['ingredient_unit'] ?: '') . ' ' . h($row['ingredient_unit'] ?: '') . ' · ' . h($row['consumption_mode'] === 'per_person' ? 'por pessoa' : 'fixo') : '—' ?></td><td class="py-3 pr-4"><?= $row['executed_quantity'] !== null ? rt2027_format_quantity($row['executed_quantity'], $row['ingredient_unit'] ?: '') . ' ' . h($row['ingredient_unit'] ?: '') : '—' ?></td><td class="py-3 pr-4 <?= (($row['quantity_diff'] ?? 0) > 0) ? 'text-red-600' : 'text-emerald-700' ?>"><?= $row['quantity_diff'] !== null ? rt2027_format_quantity($row['quantity_diff'], $row['ingredient_unit'] ?: '') . ' ' . h($row['ingredient_unit'] ?: '') : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody></table></div>
    <?php endif; ?>
  </div>
</div></main></div>
