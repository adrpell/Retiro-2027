<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Participantes</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
  <main class="rt-admin-content p-6 lg:p-8">
    <div class="mb-6"><h1 class="text-3xl font-bold">Participantes</h1><p class="text-slate-600">Filtre por sexo, faixa etária e acomodação.</p></div>
    <div class="rt-card rounded-3xl shadow p-6 mb-6">
      <form class="grid md:grid-cols-2 xl:grid-cols-5 gap-4">
        <input type="hidden" name="route" value="admin/participants">
        <input name="search" value="<?= h($filters['search']) ?>" placeholder="Busca" class="rounded-xl border-slate-300">
        <select name="sex" class="rounded-xl border-slate-300"><option value="">Sexo</option><option value="M">Masculino</option><option value="F">Feminino</option></select>
        <select name="age_band" class="rounded-xl border-slate-300"><option value="">Faixa</option><option>Criança (0-11)</option><option>Adolescente (12-17)</option><option>Adulto (18-59)</option><option>Sênior (60+)</option><option>Sem idade</option></select>
        <select name="accommodation_choice" class="rounded-xl border-slate-300"><option value="">Acomodação</option><option value="chale">Chalé</option><option value="alojamento">Alojamento</option><option value="casa">Dormir em casa</option></select>
        <button class="rounded-2xl border border-slate-300 px-4 py-2 font-semibold">Filtrar</button>
      </form>
    </div>
    <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
      <table class="min-w-full text-sm sortable">
        <thead><tr class="text-left text-slate-500"><th class="pb-3"><?= sort_link('full_name','Nome','admin/participants') ?></th><th class="pb-3">Código</th><th class="pb-3">Responsável</th><th class="pb-3"><?= sort_link('age','Idade','admin/participants') ?></th><th class="pb-3">Faixa</th><th class="pb-3">Sexo</th><th class="pb-3"><?= sort_link('accommodation_choice','Acomodação','admin/participants') ?></th><th class="pb-3">Valor</th><th class="pb-3">Ação</th></tr></thead>
        <tbody>
          <?php foreach ($participantsRows as $row): ?>
          <tr class="border-t border-slate-200">
            <td class="py-3"><?= h($row['full_name']) ?><?= $row['is_responsible'] ? ' <span class="text-xs text-slate-500">(responsável)</span>' : '' ?></td>
            <td class="py-3"><?= h($row['access_code']) ?></td>
            <td class="py-3"><?= h($row['responsible_name']) ?></td>
            <td class="py-3"><?= h($row['age'] ?? '-') ?></td>
            <td class="py-3"><?= h($row['age_band']) ?></td>
            <td class="py-3"><?= h($row['sex']) ?></td>
            <td class="py-3"><?= h(accommodation_label($row['accommodation_choice'])) ?></td>
            <td class="py-3"><?= h(money_br($row['calculated_value'])) ?></td><td class="py-3"><a class="underline text-indigo-600" href="index.php?route=admin/participant-edit&id=<?= h($row['id']) ?>">Editar</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
