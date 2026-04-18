<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-[1.75rem] mx-4 mt-4 lg:mx-6 lg:mt-6">
      <div>
        <div class="rt-admin-title">Dashboard</div>
        <div class="rt-admin-subtitle">Visão geral do retiro com foco em ocupação, financeiro e operação.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
    <main class="p-4 lg:p-6 space-y-6">
      <section class="rt-hero rounded-[2rem] p-6 lg:p-8 shadow-2xl">
        <div class="relative z-10 flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
          <div>
            <div class="rt-chip bg-white/10 text-teal-50 border border-white/15 mb-4">Dashboard principal</div>
            <h1 class="text-3xl lg:text-4xl font-bold">Resumo</h1>
            <p class="text-teal-50/90 mt-3 max-w-3xl">Acompanhe inscrições, participantes, acomodações e arrecadação do <?= h(setting($pdo, 'event_name', 'Retiro 2027 - ICNV Catedral')) ?> em um só painel.</p>
          </div>
          <div class="rounded-[1.35rem] bg-white/12 border border-white/15 px-5 py-4 text-sm text-teal-50/95 min-w-[260px]">
            <div class="text-teal-100/80">Período do retiro</div>
            <div class="font-semibold text-lg mt-1">01/01/2026 - 31/01/2027</div>
            <div class="mt-3 text-teal-100/80">Atualizado em <?= date('d/m/Y H:i') ?></div>
          </div>
        </div>
      </section>

      <?php if (!empty($prefs['summary_cards'])): ?>
      <section class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rt-card rt-stat-card rounded-[1.6rem] p-5"><div class="text-sm text-slate-500">Inscrições</div><div class="text-3xl font-bold mt-2"><?= h($summary['groups']) ?></div><div class="text-sm text-slate-500 mt-2">Ver todas →</div></div>
        <div class="rt-card rt-stat-card rounded-[1.6rem] p-5"><div class="text-sm text-slate-500">Participantes</div><div class="text-3xl font-bold mt-2"><?= h($summary['participants']) ?></div><div class="text-sm text-slate-500 mt-2">Gestão completa</div></div>
        <div class="rt-card rt-stat-card rounded-[1.6rem] p-5"><div class="text-sm text-slate-500">Acomodações</div><div class="text-3xl font-bold mt-2"><?= h($capacity['chale_occupied'] + $capacity['alojamento_occupied']) ?>/<?= h($capacity['chale_total'] + $capacity['alojamento_total']) ?></div><div class="text-sm text-slate-500 mt-2">Capacidade ocupada</div></div>
        <div class="rt-card rt-stat-card rounded-[1.6rem] p-5"><div class="text-sm text-slate-500">Arrecadado</div><div class="text-3xl font-bold mt-2"><?= money_br($summary['paid_total']) ?></div><div class="text-sm text-slate-500 mt-2">Saldo pendente <?= money_br($summary['pending_finance']) ?></div></div>
      </section>
      <?php endif; ?>

      <section class="grid xl:grid-cols-[1.6fr_1fr] gap-6">
        <div class="space-y-6">
          <div class="grid xl:grid-cols-2 gap-6">
            <?php if (!empty($prefs['lodging_gender_chart'])): ?><div class="rt-card rounded-[1.75rem] p-5"><h2 class="font-semibold text-xl mb-4">Alojamentos por sexo</h2><canvas id="lodgingGenderChart" height="180"></canvas></div><?php endif; ?>
            <?php if (!empty($prefs['finance_chart'])): ?><div class="rt-card rounded-[1.75rem] p-5"><h2 class="font-semibold text-xl mb-4">Status financeiro</h2><canvas id="financeChart" height="180"></canvas></div><?php endif; ?>
          </div>
          <div class="grid xl:grid-cols-2 gap-6">
            <?php if (!empty($prefs['recent_groups'])): ?>
            <div class="rt-card rounded-[1.75rem] p-5 overflow-x-auto">
              <div class="flex items-center justify-between mb-4"><h2 class="font-semibold text-xl">Inscrições recentes</h2><a class="text-sm underline" href="index.php?route=admin/groups">Ver todas as inscrições</a></div>
              <table class="min-w-full text-sm">
                <thead><tr class="text-left text-slate-500 border-b border-slate-200"><th class="py-2">Código</th><th class="py-2">Responsável</th><th class="py-2">Data</th></tr></thead>
                <tbody>
                <?php foreach ($recentGroups as $row): ?>
                  <tr class="border-b border-slate-100">
                    <td class="py-2"><a class="underline" href="index.php?route=admin/group-edit&id=<?= h($row['id']) ?>"><?= h($row['access_code']) ?></a></td>
                    <td class="py-2"><?= h($row['responsible_name']) ?></td>
                    <td class="py-2"><?= date('d/m', strtotime($row['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
            <?php if (!empty($prefs['recent_activity'])): ?>
            <div class="rt-card rounded-[1.75rem] p-5">
              <div class="flex items-center justify-between mb-4"><h2 class="font-semibold text-xl">Atividade recente</h2><a class="text-sm underline" href="index.php?route=admin/settings#logs">Ver tudo</a></div>
              <div class="space-y-3">
                <?php foreach ($recentActivity as $log): ?>
                  <div class="rounded-2xl bg-slate-50 px-4 py-3">
                    <div class="font-medium"><?= h($log['action']) ?></div>
                    <div class="text-sm text-slate-500 mt-1"><?= h($log['details']) ?></div>
                    <div class="text-xs text-slate-400 mt-2"><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="space-y-6">
          <?php if (!empty($prefs['occupancy_cards'])): ?>
          <div class="rt-card rounded-[1.75rem] p-5">
            <h2 class="font-semibold text-xl mb-4">Ocupação</h2>
            <div class="space-y-4 text-sm">
              <div>
                <div class="flex justify-between mb-2"><span>Chalés</span><strong><?= h($capacity['chale_occupied']) ?>/<?= h($capacity['chale_total']) ?></strong></div>
                <div class="h-3 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full" style="width: <?= $capacity['chale_total']>0? max(0,min(100,($capacity['chale_occupied']/$capacity['chale_total']*100))):0 ?>%; background: var(--rt-primary)"></div></div>
              </div>
              <div>
                <div class="flex justify-between mb-2"><span>Alojamento masculino</span><strong><?= h($lodgingGender['male_occupied']) ?>/<?= h($lodgingGender['male_total']) ?></strong></div>
                <div class="h-3 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full" style="width: <?= $lodgingGender['male_total']>0? max(0,min(100,($lodgingGender['male_occupied']/$lodgingGender['male_total']*100))):0 ?>%; background: #3b82f6"></div></div>
              </div>
              <div>
                <div class="flex justify-between mb-2"><span>Alojamento feminino</span><strong><?= h($lodgingGender['female_occupied']) ?>/<?= h($lodgingGender['female_total']) ?></strong></div>
                <div class="h-3 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full" style="width: <?= $lodgingGender['female_total']>0? max(0,min(100,($lodgingGender['female_occupied']/$lodgingGender['female_total']*100))):0 ?>%; background: #d946ef"></div></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($prefs['accommodation_chart'])): ?><div class="rt-card rounded-[1.75rem] p-5"><h2 class="font-semibold text-xl mb-4">Distribuição por acomodação</h2><canvas id="accommodationChart" height="180"></canvas></div><?php endif; ?>
          <?php if (!empty($prefs['status_chart'])): ?><div class="rt-card rounded-[1.75rem] p-5"><h2 class="font-semibold text-xl mb-4">Status das inscrições</h2><canvas id="statusChart" height="180"></canvas></div><?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</div>

<script>
const accommodationData = <?= json_encode($accommodationData, JSON_UNESCAPED_UNICODE) ?>;
const financeData = <?= json_encode($financeData, JSON_UNESCAPED_UNICODE) ?>;
const statusData = <?= json_encode($statusData, JSON_UNESCAPED_UNICODE) ?>;
const lodgingGenderData = <?= json_encode($lodgingGenderData, JSON_UNESCAPED_UNICODE) ?>;
if (document.getElementById('accommodationChart')) new Chart(document.getElementById('accommodationChart'), {type:'doughnut',data:{labels:accommodationData.map(x=>x.label),datasets:[{data:accommodationData.map(x=>x.total),backgroundColor:['#0f766e','#0ea5a4','#94a3b8','#8b5cf6']}]}, options:{plugins:{legend:{position:'bottom'}}}});
if (document.getElementById('financeChart')) new Chart(document.getElementById('financeChart'), {type:'doughnut',data:{labels:financeData.map(x=>x.label),datasets:[{data:financeData.map(x=>x.total),backgroundColor:['#0f766e','#eab308','#ef4444']}]}, options:{plugins:{legend:{position:'bottom'}}}});
if (document.getElementById('statusChart')) new Chart(document.getElementById('statusChart'), {type:'line',data:{labels:statusData.map(x=>x.label),datasets:[{data:statusData.map(x=>x.total),borderColor:'#0f766e',backgroundColor:'rgba(15,118,110,.12)',fill:true,tension:.35}]}, options:{plugins:{legend:{display:false}}}});
if (document.getElementById('lodgingGenderChart')) new Chart(document.getElementById('lodgingGenderChart'), {type:'doughnut',data:{labels:lodgingGenderData.map(x=>x.label),datasets:[{data:lodgingGenderData.map(x=>x.total),backgroundColor:['#3b82f6','#d946ef']}]}, options:{plugins:{legend:{position:'right'}}}});
</script>
