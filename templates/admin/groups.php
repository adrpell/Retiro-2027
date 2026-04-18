<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Inscrições</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
  <main class="rt-admin-content p-6 lg:p-8">
    <div class="flex items-center justify-between gap-4 mb-6">
      <div><h1 class="text-3xl font-bold">Inscrições</h1><p class="text-slate-600">Lista organizada e ordenável por colunas.</p></div>
      <a href="index.php?route=admin/reports&export=groups" class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold">Exportar CSV</a>
    </div>
    <div class="rt-card rounded-3xl shadow p-6 mb-6">
      <form class="grid md:grid-cols-3 xl:grid-cols-6 gap-4">
        <input type="hidden" name="route" value="admin/groups">
        <input name="search" value="<?= h($filters['search']) ?>" placeholder="Busca" class="rounded-xl border-slate-300">
        <select name="registration_type" class="rounded-xl border-slate-300"><option value="">Tipo</option><option value="individual" <?= $filters['registration_type']==='individual'?'selected':'' ?>>Individual</option><option value="familia" <?= $filters['registration_type']==='familia'?'selected':'' ?>>Família</option></select>
        <select name="group_accommodation" class="rounded-xl border-slate-300"><option value="">Acomodação</option><option value="chale">Chalé</option><option value="alojamento">Alojamento</option><option value="casa">Dormir em casa</option><option value="personalizado">Personalizado</option></select>
        <select name="status" class="rounded-xl border-slate-300"><option value="">Status</option><option value="intencao">Intenção</option><option value="confirmado">Confirmado</option><option value="espera">Espera</option><option value="cancelado">Cancelado</option></select>
        <select name="financial_status" class="rounded-xl border-slate-300"><option value="">Financeiro</option><option value="pendente">Pendente</option><option value="parcial">Parcial</option><option value="quitado">Quitado</option></select>
        <button class="rounded-2xl border border-slate-300 px-4 py-2 font-semibold">Filtrar</button>
      </form>
    </div>
    <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
      <table class="min-w-full text-sm sortable">
        <thead>
          <tr class="text-left text-slate-500">
            <th class="pb-3"><?= sort_link('access_code','Código','admin/groups') ?></th>
            <th class="pb-3"><?= sort_link('responsible_name','Responsável','admin/groups') ?></th>
            <th class="pb-3"><?= sort_link('registration_type','Tipo','admin/groups') ?></th>
            <th class="pb-3"><?= sort_link('total_people','Pessoas','admin/groups') ?></th>
            <th class="pb-3"><?= sort_link('group_accommodation','Acomodação','admin/groups') ?></th>
            <th class="pb-3"><?= sort_link('financial_status','Financeiro','admin/groups') ?></th>
            <th class="pb-3"><?= sort_link('suggested_value','Valor','admin/groups') ?></th>
            <th class="pb-3"><?= sort_link('created_at','Data','admin/groups') ?></th><th class="pb-3">Ação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($groupsRows as $row): ?>
          <tr class="border-t border-slate-200">
            <td class="py-3 font-semibold"><?= h($row['access_code']) ?></td>
            <td class="py-3"><?= h($row['responsible_name']) ?></td>
            <td class="py-3"><?= h(ucfirst($row['registration_type'])) ?></td>
            <td class="py-3"><?= h($row['total_people']) ?></td>
            <td class="py-3"><?= h(accommodation_label($row['group_accommodation'])) ?></td>
            <td class="py-3"><span class="inline-flex rounded-full px-2 py-1 text-xs <?= h(status_badge_class($row['financial_status'])) ?>"><?= h(ucfirst($row['financial_status'])) ?></span></td>
            <td class="py-3"><?= h(money_br($row['suggested_value'])) ?></td>
            <td class="py-3"><?= h($row['created_at']) ?></td><td class="py-3"><div class="flex items-center gap-3"><a class="underline text-indigo-600" href="index.php?route=admin/group-edit&id=<?= h($row['id']) ?>">Editar</a><form method="post" action="index.php?route=admin/group-delete" onsubmit="return confirm('Excluir esta inscrição e todos os vínculos associados?');">
<?= csrf_field() ?>
<input type="hidden" name="group_id" value="<?= h($row['id']) ?>">
<button class="underline text-red-600">Excluir</button></form></div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
