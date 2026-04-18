<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Financeiro</div>
        <div class="rt-admin-subtitle">Painel administrativo do retiro com navegação responsiva e tema ajustável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/logout" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>
  <main class="rt-admin-content p-6 lg:p-8">
    <div class="mb-6"><h1 class="text-3xl font-bold">Financeiro</h1><p class="text-slate-600">Lance pagamentos, acompanhe o saldo pendente e controle o parcelamento em até <?= h(setting($pdo, 'max_installments', '10')) ?>x.</p><div class="mt-3 rounded-2xl bg-indigo-50 border border-indigo-100 px-4 py-3 text-sm text-slate-700"><p><strong><?= h(setting($pdo, 'financial_confirmation_note', 'A inscrição só será confirmada mediante aceno financeiro.')) ?></strong></p><p class="mt-1">Transferências: <strong><?= h(setting($pdo, 'pix_beneficiary', 'Igreja Cristã Nova Vida Catedral')) ?></strong> · PIX (CNPJ): <strong><?= h(setting($pdo, 'pix_key', '03.102.014/0001-04')) ?></strong> · Comprovantes: <strong><?= h(setting($pdo, 'payment_receipt_contact', 'Diácono Cláudio')) ?></strong>.</p></div></div>
    <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
    <div class="grid xl:grid-cols-3 gap-6">
      <div class="xl:col-span-1 rt-card rounded-3xl shadow p-6">
        <h2 class="font-semibold text-xl mb-4">Registrar pagamento</h2>
        <form method="post" enctype="multipart/form-data" class="space-y-4">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <label class="block"><span class="text-sm font-medium">Inscrição</span>
            <select name="group_id" class="mt-1 w-full rounded-xl border-slate-300">
              <?php foreach ($groupsFinance as $group): ?>
              <option value="<?= h($group['id']) ?>"><?= h($group['access_code'] . ' — ' . $group['responsible_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="block"><span class="text-sm font-medium">Valor pago</span><input name="amount_paid" class="mt-1 w-full rounded-xl border-slate-300" required></label>
          <label class="block"><span class="text-sm font-medium">Forma de pagamento</span>
            <select name="payment_method" class="mt-1 w-full rounded-xl border-slate-300">
              <option value="pix">Pix</option><option value="dinheiro">Dinheiro</option><option value="cartao">Cartão</option><option value="transferencia">Transferência</option>
            </select>
          </label>
          <label class="block"><span class="text-sm font-medium">Parcela</span><input type="number" name="installment_number" min="1" class="mt-1 w-full rounded-xl border-slate-300" value="1"></label>
          <label class="block"><span class="text-sm font-medium">Data</span><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="block"><span class="text-sm font-medium">Comprovante do pagamento</span><input type="file" name="payment_receipt" class="mt-1 w-full rounded-xl border-slate-300"></label>
          <label class="block"><span class="text-sm font-medium">Observações</span><textarea name="notes" rows="3" class="mt-1 w-full rounded-xl border-slate-300"></textarea></label>
          <button class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold">Salvar pagamento</button>
        </form>
      </div>
      <div class="xl:col-span-2 space-y-6">
        <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
          <h2 class="font-semibold text-xl mb-4">Resumo por inscrição</h2>
          <table class="min-w-full text-sm">
            <thead><tr class="text-left text-slate-500"><th class="pb-3">Código</th><th class="pb-3">Responsável</th><th class="pb-3">Sugerido</th><th class="pb-3">Pago</th><th class="pb-3">Pendente</th><th class="pb-3">Status</th></tr></thead>
            <tbody>
              <?php foreach ($groupsFinance as $row): ?>
              <tr class="border-t border-slate-200"><td class="py-3"><?= h($row['access_code']) ?></td><td class="py-3"><?= h($row['responsible_name']) ?></td><td class="py-3"><?= h(money_br($row['suggested_value'])) ?></td><td class="py-3"><?= h(money_br($row['amount_paid'])) ?></td><td class="py-3"><?= h(money_br($row['amount_pending'])) ?></td><td class="py-3"><?= h(ucfirst($row['financial_status'])) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
          <h2 class="font-semibold text-xl mb-4">Últimos pagamentos</h2>
          <table class="min-w-full text-sm">
            <thead><tr class="text-left text-slate-500"><th class="pb-3">Data</th><th class="pb-3">Código</th><th class="pb-3">Responsável</th><th class="pb-3">Valor</th><th class="pb-3">Forma</th><th class="pb-3">Parcela</th><th class="pb-3">Comprovante</th></tr></thead>
            <tbody>
              <?php foreach ($paymentsRows as $row): ?>
              <tr class="border-t border-slate-200"><td class="py-3"><?= h($row['payment_date']) ?></td><td class="py-3"><?= h($row['access_code']) ?></td><td class="py-3"><?= h($row['responsible_name']) ?></td><td class="py-3"><?= h(money_br($row['amount_paid'])) ?></td><td class="py-3"><?= h(payment_label($row['payment_method'])) ?></td><td class="py-3"><?= h($row['installment_number']) ?>x</td><td class="py-3"><?php if (!empty($row['receipt_file'])): ?><a class="underline text-indigo-600" target="_blank" href="<?= h($row['receipt_file']) ?>">Abrir</a><?php else: ?><span class="text-slate-400">—</span><?php endif; ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>
