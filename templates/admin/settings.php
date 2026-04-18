<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Configurações</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
  <main class="rt-admin-content p-6 lg:p-8">
    <div class="mb-6"><h1 class="text-3xl font-bold">Configurações</h1><p class="text-slate-600">Financeiro, capacidade, Pix, parcelamento, widgets e estilo visual.</p></div>
    <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <form method="post" class="space-y-6">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="grid xl:grid-cols-2 gap-6">
        <div class="rt-card rounded-3xl shadow p-6 space-y-4">
          <h2 class="font-semibold text-xl">Evento e capacidade</h2>
          <label class="block"><span class="text-sm font-medium">Nome do evento</span><input name="event_name" value="<?= h(setting($pdo,'event_name','Retiro 2027 - ICNV Catedral')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="block"><span class="text-sm font-medium">Limite total de pessoas</span><input name="max_people" value="<?= h(setting($pdo,'max_people','160')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <div class="grid md:grid-cols-2 gap-4">
            <label class="block"><span class="text-sm font-medium">Quantidade de chalés</span><input name="chalet_units" value="<?= h(setting($pdo,'chalet_units','10')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Capacidade por chalé</span><input name="chalet_capacity_per_unit" value="<?= h(setting($pdo,'chalet_capacity_per_unit','6')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          </div>
          <div class="grid md:grid-cols-3 gap-4">
            <label class="block"><span class="text-sm font-medium">Capacidade total de alojamento</span><input name="lodging_capacity" value="<?= h(setting($pdo,'lodging_capacity','80')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Alojamento masculino</span><input name="lodging_male_capacity" value="<?= h(setting($pdo,'lodging_male_capacity','40')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Alojamento feminino</span><input name="lodging_female_capacity" value="<?= h(setting($pdo,'lodging_female_capacity','40')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          </div>
        </div>

        <div class="rt-card rounded-3xl shadow p-6 space-y-4">
          <h2 class="font-semibold text-xl">Valores por faixa etária</h2><p class="text-sm text-slate-500 md:col-span-3">0 a 2 anos: cortesia · 3 a 9 anos: R$ 300,00 · a partir de 10 anos: R$ 600,00. Mantidos nos campos abaixo para compatibilidade com a estrutura atual do sistema.</p>
          <div class="grid md:grid-cols-3 gap-4">
            <label class="block"><span class="text-sm font-medium">Adulto Chalé</span><input name="price_adult_chale" value="<?= h(setting($pdo,'price_adult_chale','750')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Adulto Alojamento</span><input name="price_adult_alojamento" value="<?= h(setting($pdo,'price_adult_alojamento','650')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Adulto Casa</span><input name="price_adult_casa" value="<?= h(setting($pdo,'price_adult_casa','650')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Criança Chalé</span><input name="price_child_chale" value="<?= h(setting($pdo,'price_child_chale','650')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Criança Alojamento</span><input name="price_child_alojamento" value="<?= h(setting($pdo,'price_child_alojamento','550')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Criança Casa</span><input name="price_child_casa" value="<?= h(setting($pdo,'price_child_casa','550')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          </div>
          <div class="grid md:grid-cols-3 gap-4">
            <label class="block"><span class="text-sm font-medium">Desconto família (%)</span><input name="family_discount_percent" value="<?= h(setting($pdo,'family_discount_percent','0')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Taxa de inscrição</span><input name="registration_fee" value="<?= h(setting($pdo,'registration_fee','0')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Máx. parcelas (até jan/27)</span><input name="max_installments" value="<?= h(setting($pdo,'max_installments','10')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          </div>
        </div>

        <div class="rt-card rounded-3xl shadow p-6 space-y-4">
          <h2 class="font-semibold text-xl">Pix e orientações financeiras</h2>
          <label class="block"><span class="text-sm font-medium">Favorecido</span><input name="pix_beneficiary" value="<?= h(setting($pdo,'pix_beneficiary','')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="block"><span class="text-sm font-medium">Chave Pix</span><input name="pix_key" value="<?= h(setting($pdo,'pix_key','')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="block"><span class="text-sm font-medium">Pix copia e cola</span><textarea name="pix_copy_paste" rows="4" class="mt-1 w-full rounded-xl border-slate-300"><?= h(setting($pdo,'pix_copy_paste','')) ?></textarea></label>
          <label class="block"><span class="text-sm font-medium">Contato para comprovantes</span><input name="payment_receipt_contact" value="<?= h(setting($pdo,'payment_receipt_contact','Diácono Cláudio')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="block"><span class="text-sm font-medium">Dia preferencial para pagamento</span><input name="payment_deadline_day" value="<?= h(setting($pdo,'payment_deadline_day','10')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="block md:col-span-3"><span class="text-sm font-medium">Nota de confirmação</span><input name="financial_confirmation_note" value="<?= h(setting($pdo,'financial_confirmation_note','A inscrição só será confirmada mediante aceno financeiro.')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
        </div>

        <div class="rt-card rounded-3xl shadow p-6 space-y-4">
          <h2 class="font-semibold text-xl">Estilo visual</h2>
          <div class="grid md:grid-cols-2 gap-4">
            <label class="block"><span class="text-sm font-medium">Cor principal</span><input type="color" name="primary_color" value="<?= h(setting($pdo,'primary_color','#4f46e5')) ?>" class="mt-1 h-12 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Cor de destaque</span><input type="color" name="accent_color" value="<?= h(setting($pdo,'accent_color','#14b8a6')) ?>" class="mt-1 h-12 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Cor da superfície</span><input type="color" name="surface_color" value="<?= h(setting($pdo,'surface_color','#ffffff')) ?>" class="mt-1 h-12 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Cor do menu lateral</span><input type="color" name="panel_bg_color" value="<?= h(setting($pdo,'panel_bg_color','#0f172a')) ?>" class="mt-1 h-12 w-full rounded-xl border-slate-300"></label>
            <label class="block md:col-span-2"><span class="text-sm font-medium">Sombra dos cards</span><input name="card_shadow" value="<?= h(setting($pdo,'card_shadow','0 18px 45px rgba(15,23,42,.08)')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Fonte</span><input name="font_family" value="<?= h(setting($pdo,'font_family','Inter, Arial, sans-serif')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Tamanho da fonte</span><input name="font_size" value="<?= h(setting($pdo,'font_size','16')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block"><span class="text-sm font-medium">Peso</span><input name="font_weight" value="<?= h(setting($pdo,'font_weight','400')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
            <label class="block md:col-span-2"><span class="text-sm font-medium">Estilo</span>
              <select name="font_style" class="mt-1 w-full rounded-xl border-slate-300">
                <option value="normal" <?= setting($pdo,'font_style','normal')==='normal'?'selected':'' ?>>Normal</option>
                <option value="italic" <?= setting($pdo,'font_style','normal')==='italic'?'selected':'' ?>>Itálico</option>
              </select>
            </label>
          </div>
        </div>

        <div class="rt-card rounded-3xl shadow p-6 space-y-4 xl:col-span-2">
          <h2 class="font-semibold text-xl">Widgets do dashboard</h2>
          <div class="grid md:grid-cols-5 gap-3 text-sm">
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_summary_cards" <?= !empty($prefs['summary_cards']) ? 'checked' : '' ?>>Cards resumo</label>
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_occupancy_cards" <?= !empty($prefs['occupancy_cards']) ? 'checked' : '' ?>>Cards de ocupação</label>
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_accommodation_chart" <?= !empty($prefs['accommodation_chart']) ? 'checked' : '' ?>>Gráfico acomodação</label>
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_finance_chart" <?= !empty($prefs['finance_chart']) ? 'checked' : '' ?>>Gráfico financeiro</label>
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_status_chart" <?= !empty($prefs['status_chart']) ? 'checked' : '' ?>>Gráfico status</label>
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_lodging_gender_chart" <?= !empty($prefs['lodging_gender_chart']) ? 'checked' : '' ?>>Alojamento M/F</label>
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_recent_groups" <?= !empty($prefs['recent_groups']) ? 'checked' : '' ?>>Inscrições recentes</label>
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="widget_recent_activity" <?= !empty($prefs['recent_activity']) ? 'checked' : '' ?>>Atividade recente</label>
          </div>
        </div>
      </div>

        <div class="rt-card rounded-3xl shadow p-6 space-y-4 xl:col-span-2">
          <h2 class="font-semibold text-xl">Backup automático do banco</h2>
          <div class="grid md:grid-cols-3 gap-4">
            <label class="rounded-2xl bg-slate-50 p-4 flex items-center gap-2"><input type="checkbox" name="backup_auto_enabled" value="1" <?= setting($pdo,'backup_auto_enabled','0')==='1'?'checked':'' ?>> Habilitar rotina automática</label>
            <label class="block"><span class="text-sm font-medium">Frequência</span><select name="backup_auto_frequency" class="mt-1 w-full rounded-xl border-slate-300"><option value="daily" <?= setting($pdo,'backup_auto_frequency','daily')==='daily'?'selected':'' ?>>Diário</option><option value="weekly" <?= setting($pdo,'backup_auto_frequency','daily')==='weekly'?'selected':'' ?>>Semanal</option></select></label>
            <label class="block"><span class="text-sm font-medium">Retenção (dias)</span><input name="backup_retention_days" value="<?= h(setting($pdo,'backup_retention_days','30')) ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          </div>
          <p class="text-sm text-slate-500">Depois de salvar, configure o cron do servidor apontando para <code>cron_backup.php</code> na raiz do sistema.</p>
        </div>
      </div>
      <button class="rounded-2xl rt-btn-primary px-6 py-3 font-semibold">Salvar configurações</button>
    </form>
  </main>
</div>
