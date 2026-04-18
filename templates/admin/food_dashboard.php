
<?php $pageTitle = 'Alimentação'; $basisOptions = rt2027_food_people_basis_options(); ?>
<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <main class="rt-admin-content p-4 lg:p-8">
    <div class="rt-admin-topbar rounded-3xl">
      <div>
        <div class="rt-admin-title">Alimentação</div>
        <div class="rt-admin-subtitle">Visão geral do planejamento dos quatro dias do retiro.</div>
      </div>
      <form method="post" action="index.php?route=admin/food-basis-save" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <span class="text-sm text-slate-500">Base:</span>
        <?php foreach ($basisOptions as $key => $label): ?>
          <button type="submit" name="food_people_basis" value="<?= h($key) ?>" class="rounded-full px-4 py-2 text-sm font-semibold <?= ($foodOverview['basis'] ?? 'inscritos') === $key ? 'rt-btn-primary text-white' : 'rt-btn-secondary' ?>"><?= h($label) ?></button>
        <?php endforeach; ?>
      </form>
    </div>
    <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3"><?= $msg ?></div><?php endif; ?>
    <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 gap-4 mb-6">
      <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Base de cálculo</div><div class="text-2xl font-bold mt-2"><?= h($foodOverview['peopleBasisLabel']) ?></div><div class="text-sm text-slate-500 mt-1">Pessoas por refeição: <?= (int)$foodOverview['peopleCount'] ?></div></div>
      <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Refeições planejadas</div><div class="text-3xl font-bold mt-2"><?= (int)$foodOverview['mealsPlanned'] ?></div><div class="text-sm text-slate-500 mt-1">Concluídas: <?= (int)$foodOverview['mealsReady'] ?></div></div>
      <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Itens na despensa</div><div class="text-3xl font-bold mt-2"><?= (int)$foodOverview['pantryCount'] ?></div><div class="text-sm text-amber-600 mt-1">Abaixo do mínimo: <?= (int)$foodOverview['lowStock'] ?></div></div>
      <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Compras pendentes</div><div class="text-3xl font-bold mt-2"><?= (int)$foodOverview['shoppingPending'] ?></div><div class="text-sm text-slate-500 mt-1">Custo estimado: <?= money_br($foodOverview['estimatedCost']) ?></div></div>
          <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Restrições alimentares</div><div class="text-3xl font-bold mt-2"><?= (int)($foodOverview['restrictionPeople'] ?? 0) ?></div><div class="text-sm text-slate-500 mt-1">Resumo da cozinha</div></div>
      <!-- <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Itens em falta</div><div class="text-3xl font-bold mt-2"><?= (int)($foodOverview['autoShortages'] ?? 0) ?></div><div class="text-sm text-slate-500 mt-1">Com base no cardápio + despensa</div>
      <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rt-card rounded-3xl p-5">
          <h2 class="text-xl font-semibold mb-4">Sugestões de cardápio ajustadas às restrições</h2>
          <div class="space-y-3"><?php foreach (($menuSuggestions ?? []) as $s): ?><div class="rounded-2xl border border-slate-200 px-4 py-3"><div class="font-semibold"><?= h($s['title']) ?></div><div class="text-sm text-slate-600 mt-1"><?= h($s['text']) ?></div></div><?php endforeach; ?></div>
        </div>
        <div class="rt-card rounded-3xl p-5">
          <h2 class="text-xl font-semibold mb-4">Próximos passos da cozinha</h2>
          <div class="space-y-3 text-sm text-slate-600">
            <p>• Conclua uma refeição para baixar automaticamente o estoque vinculado.</p>
            <p>• Revise as restrições alimentares antes de fechar almoço e jantar.</p>
            <p>• Gere a lista automática de compras sempre que alterar cardápio ou despensa.</p>
            <p>• Use o relatório alimentar para alinhar cozinha, compras e serviço.</p>
          </div>
        </div>
      </div> -->
    </section>
    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div class="rt-card rounded-3xl p-5">
        <div class="flex items-center justify-between mb-4"><h2 class="text-xl font-semibold">Cardápio por refeição</h2><a class="rt-btn-primary rounded-full px-4 py-2 text-sm font-semibold" href="index.php?route=admin/food-menus">Gerenciar cardápio</a></div>
        <div class="space-y-3">
          <?php foreach ($todayMeals as $meal): ?>
            <div class="rounded-2xl border border-slate-200 p-4 bg-white/70">
              <div class="flex flex-wrap items-center justify-between gap-2"><div class="font-semibold"><?= h(rt2027_food_day_label((int)$meal['retreat_day'])) ?> · <?= h(rt2027_food_meal_types()[$meal['meal_type']] ?? $meal['meal_type']) ?></div><span class="px-3 py-1 rounded-full text-xs <?= status_badge_class($meal['status']) ?>"><?= h(ucfirst($meal['status'])) ?></span></div>
              <div class="text-slate-700 mt-1"><?= h($meal['title']) ?></div>
              <div class="text-sm text-slate-500 mt-1">Estimativa: <?= (int)$meal['estimated_people'] ?> pessoa(s) · <?= h($meal['meal_time'] ?: '--:--') ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="space-y-6">
        <div class="rt-card rounded-3xl p-5">
          <div class="flex items-center justify-between mb-4"><h2 class="text-xl font-semibold">Itens críticos da despensa</h2><a class="rt-btn-secondary rounded-full px-4 py-2 text-sm font-semibold" href="index.php?route=admin/food-pantry">Abrir despensa</a></div>
          <div class="space-y-3">
            <?php if (!$lowPantry): ?><div class="text-slate-500">Nenhum item abaixo do estoque mínimo.</div><?php endif; ?>
            <?php foreach ($lowPantry as $item): ?>
              <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3"><div class="font-semibold"><?= h($item['item_name']) ?></div><div class="text-sm text-amber-700">Atual: <?= rt2027_format_quantity($item['quantity_current'], $item['unit']) ?> <?= h($item['unit']) ?> · Mínimo: <?= rt2027_format_quantity($item['minimum_stock'], $item['unit']) ?> <?= h($item['unit']) ?></div></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="rt-card rounded-3xl p-5">
          <div class="flex items-center justify-between mb-4"><h2 class="text-xl font-semibold">Compras pendentes</h2><a class="rt-btn-secondary rounded-full px-4 py-2 text-sm font-semibold" href="index.php?route=admin/food-purchases">Abrir compras</a></div>
          <div class="space-y-3">
            <?php if (!$pendingPurchases): ?><div class="text-slate-500">Nenhuma compra pendente no momento.</div><?php endif; ?>
            <?php foreach ($pendingPurchases as $purchase): ?>
              <div class="rounded-2xl border border-slate-200 px-4 py-3"><div class="flex items-center justify-between gap-2"><div class="font-semibold"><?= h($purchase['item_name']) ?></div><span class="text-xs px-3 py-1 rounded-full <?= $purchase['priority_level']==='alta' ? 'bg-red-100 text-red-700' : ($purchase['priority_level']==='media' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') ?>"><?= h(ucfirst($purchase['priority_level'])) ?></span></div><div class="text-sm text-slate-500 mt-1">Qtd.: <?= h($purchase['quantity_needed']) ?> <?= h($purchase['unit']) ?> · <?= money_br($purchase['estimated_cost']) ?></div></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="rt-card rounded-3xl p-5">
          <div class="flex items-center justify-between mb-4"><h2 class="text-xl font-semibold">Restrições alimentares</h2><a class="rt-btn-secondary rounded-full px-4 py-2 text-sm font-semibold" href="index.php?route=admin/food-restrictions">Abrir restrições</a></div>
          <div class="space-y-3">
            <?php if (empty($foodOverview['restrictionSummary'])): ?><div class="text-slate-500">Nenhuma restrição cadastrada ainda.</div><?php endif; ?>
            <?php foreach (array_slice($foodOverview['restrictionSummary'] ?? [], 0, 6, true) as $label => $info): ?>
              <div class="rounded-2xl border border-slate-200 px-4 py-3"><div class="font-semibold"><?= h(ucwords(str_replace('_',' ', (string)$label))) ?></div><div class="text-sm text-slate-500 mt-1"><?= (int)$info['count'] ?> participante(s)</div></div>
            <?php endforeach; ?>
          </div>
        </div>
      
      <div class="mt-6 grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rt-card rounded-3xl p-5">
          <h2 class="text-xl font-semibold mb-4">Sugestões de cardápio ajustadas às restrições</h2>
          <div class="space-y-3"><?php foreach (($menuSuggestions ?? []) as $s): ?><div class="rounded-2xl border border-slate-200 px-4 py-3"><div class="font-semibold"><?= h($s['title']) ?></div><div class="text-sm text-slate-600 mt-1"><?= h($s['text']) ?></div></div><?php endforeach; ?></div>
        </div>
        <div class="rt-card rounded-3xl p-5">
          <h2 class="text-xl font-semibold mb-4">Próximos passos da cozinha</h2>
          <div class="space-y-3 text-sm text-slate-600">
            <p>• Conclua uma refeição para baixar automaticamente o estoque vinculado.</p>
            <p>• Revise as restrições alimentares antes de fechar almoço e jantar.</p>
            <p>• Gere a lista automática de compras sempre que alterar cardápio ou despensa.</p>
            <p>• Use o relatório alimentar para alinhar cozinha, compras e serviço.</p>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>
