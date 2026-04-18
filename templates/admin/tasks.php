<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Quadro de tarefas</div>
        <div class="rt-admin-subtitle">Escala por dias e turnos, com critérios automáticos de idade e sexo.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="index.php?route=admin/tasks-report" class="rounded-2xl rt-btn-secondary px-4 py-2 text-sm font-semibold">Relatório HTML</a>
      </div>
    </div>
    <main class="rt-admin-content p-6 lg:p-8">
      <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
      <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>

      <div class="grid xl:grid-cols-4 gap-4 mb-6">
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Turnos cadastrados</div><div class="text-3xl font-bold mt-2"><?= h($taskSummary['slots'] ?? 0) ?></div></div>
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Pessoas alocadas</div><div class="text-3xl font-bold mt-2"><?= h($taskSummary['assignments'] ?? 0) ?></div></div>
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Capacidade total</div><div class="text-3xl font-bold mt-2"><?= h($taskSummary['capacity'] ?? 0) ?></div></div>
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Dias cobertos</div><div class="text-3xl font-bold mt-2"><?= h($taskSummary['days'] ?? 0) ?></div></div>
      </div>

      <div class="grid xl:grid-cols-2 gap-6 mb-6">
        <section class="rt-card rounded-3xl p-6">
          <h2 class="text-xl font-semibold mb-4"><?= $taskEdit ? 'Editar tarefa' : 'Nova tarefa' ?></h2>
          <form method="post" action="index.php?route=admin/task-save" class="grid md:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= h($taskEdit['id'] ?? '') ?>">
            <div class="rt-input-wrap md:col-span-2"><label>Nome da tarefa</label><input type="text" name="name" value="<?= h($taskEdit['name'] ?? '') ?>" required></div>
            <div class="rt-input-wrap md:col-span-2"><label>Descrição</label><textarea name="description" rows="3"><?= h($taskEdit['description'] ?? '') ?></textarea></div>
            <div class="rt-input-wrap"><label>Idade mínima</label><input type="number" name="min_age" value="<?= h($taskEdit['min_age'] ?? '') ?>"></div>
            <div class="rt-input-wrap"><label>Idade máxima</label><input type="number" name="max_age" value="<?= h($taskEdit['max_age'] ?? '') ?>"></div>
            <div class="rt-input-wrap"><label>Sexo permitido</label><select name="sex_rule"><option value="any" <?= (($taskEdit['sex_rule'] ?? 'any') === 'any') ? 'selected' : '' ?>>Qualquer</option><option value="M" <?= (($taskEdit['sex_rule'] ?? '') === 'M') ? 'selected' : '' ?>>Masculino</option><option value="F" <?= (($taskEdit['sex_rule'] ?? '') === 'F') ? 'selected' : '' ?>>Feminino</option></select></div>
            <div class="rt-input-wrap"><label>Capacidade por turno</label><input type="number" min="1" name="capacity_per_slot" value="<?= h($taskEdit['capacity_per_slot'] ?? 1) ?>"></div>
            <div class="rt-input-wrap"><label>Ordem</label><input type="number" name="sort_order" value="<?= h($taskEdit['sort_order'] ?? 0) ?>"></div>
            <div class="rt-input-wrap"><label>Ativa</label><select name="is_active"><option value="1" <?= !isset($taskEdit['is_active']) || (int)$taskEdit['is_active'] === 1 ? 'selected' : '' ?>>Sim</option><option value="0" <?= isset($taskEdit['is_active']) && (int)$taskEdit['is_active'] === 0 ? 'selected' : '' ?>>Não</option></select></div>
            <div class="md:col-span-2 flex gap-3 pt-2"><button class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold">Salvar tarefa</button><?php if ($taskEdit): ?><a href="index.php?route=admin/tasks" class="rounded-2xl rt-btn-secondary px-5 py-3 font-semibold">Cancelar edição</a><?php endif; ?></div>
          </form>
        </section>

        <section class="rt-card rounded-3xl p-6">
          <h2 class="text-xl font-semibold mb-4"><?= $taskSlotEdit ? 'Editar turno' : 'Novo turno' ?></h2>
          <form method="post" action="index.php?route=admin/task-slot-save" class="grid md:grid-cols-2 gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= h($taskSlotEdit['id'] ?? '') ?>">
            <div class="rt-input-wrap md:col-span-2"><label>Tarefa</label><select name="task_id" required><option value="">Selecione</option><?php foreach ($taskDefinitions as $task): ?><option value="<?= h($task['id']) ?>" <?= ((string)($taskSlotEdit['task_id'] ?? '') === (string)$task['id']) ? 'selected' : '' ?>><?= h($task['name']) ?></option><?php endforeach; ?></select></div>
            <div class="rt-input-wrap"><label>Dia</label><input type="date" name="slot_date" value="<?= h($taskSlotEdit['slot_date'] ?? '') ?>" required></div>
            <div class="rt-input-wrap"><label>Turno</label><select name="shift_key" required><option value="">Selecione</option><?php foreach ($taskShiftOptions as $shiftKey => $shiftLabel): ?><option value="<?= h($shiftKey) ?>" <?= ((string)($taskSlotEdit['shift_key'] ?? '') === (string)$shiftKey) ? 'selected' : '' ?>><?= h($shiftLabel) ?></option><?php endforeach; ?></select></div>
            <div class="rt-input-wrap"><label>Capacidade neste turno</label><input type="number" min="1" name="capacity_override" value="<?= h($taskSlotEdit['capacity_override'] ?? '') ?>" placeholder="Usar padrão da tarefa"></div>
            <div class="rt-input-wrap"><label>Observações</label><input type="text" name="notes" value="<?= h($taskSlotEdit['notes'] ?? '') ?>"></div>
            <div class="md:col-span-2 flex gap-3 pt-2"><button class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold">Salvar turno</button><?php if ($taskSlotEdit): ?><a href="index.php?route=admin/tasks" class="rounded-2xl rt-btn-secondary px-5 py-3 font-semibold">Cancelar edição</a><?php endif; ?></div>
          </form>
        </section>
      </div>

      <section class="rt-card rounded-3xl p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
          <div>
            <h2 class="text-xl font-semibold">Distribuição automática</h2>
            <p class="text-sm text-slate-500">A mesma pessoa não será repetida em tarefas diferentes no mesmo dia/turno.</p>
          </div>
          <div class="flex gap-3">
            <a href="index.php?route=admin/tasks-report" class="rounded-2xl rt-btn-secondary px-4 py-3 font-semibold">Visualizar relatório</a>
            <a href="index.php?route=admin/tasks-report&mode=download_html" class="rounded-2xl rt-btn-secondary px-4 py-3 font-semibold">Baixar HTML</a>
          </div>
        </div>
        <form method="post" action="index.php?route=admin/task-generate" class="grid md:grid-cols-4 gap-4 items-end">
          <?= csrf_field() ?>
          <div class="rt-input-wrap"><label>Gerar apenas para o dia</label><input type="date" name="slot_date"></div>
          <div class="rt-input-wrap"><label>Gerar apenas para o turno</label><select name="shift_key"><option value="">Todos</option><?php foreach ($taskShiftOptions as $shiftKey => $shiftLabel): ?><option value="<?= h($shiftKey) ?>"><?= h($shiftLabel) ?></option><?php endforeach; ?></select></div>
          <div class="md:col-span-2"><button class="rounded-2xl rt-btn-primary px-5 py-3 font-semibold">Gerar distribuição automática</button></div>
        </form>
      </section>

      <div class="space-y-6">
        <?php foreach ($taskSlots as $slot): ?>
          <?php $capacity = (int)($slot['slot_capacity'] ?? 0); $filled = count($slot['assignments'] ?? []); ?>
          <section class="rt-card rounded-3xl p-6">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
              <div>
                <h3 class="text-xl font-semibold"><?= h($slot['task_name']) ?></h3>
                <p class="text-sm text-slate-500"><?= h(date('d/m/Y', strtotime((string)$slot['slot_date']))) ?> · <?= h($taskShiftOptions[$slot['shift_key']] ?? $slot['shift_label']) ?> · Critérios: <?= h(rt2027_task_sex_label((string)$slot['sex_rule'])) ?><?= $slot['min_age'] !== null ? ' · mín. ' . h($slot['min_age']) . ' anos' : '' ?><?= $slot['max_age'] !== null ? ' · máx. ' . h($slot['max_age']) . ' anos' : '' ?></p>
              </div>
              <div class="flex flex-wrap gap-2 items-center">
                <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold"><?= h($filled) ?>/<?= h($capacity) ?> alocados</span>
                <a class="underline text-indigo-600" href="index.php?route=admin/tasks&slot_id=<?= h($slot['id']) ?>">Editar turno</a>
                <form method="post" action="index.php?route=admin/task-slot-delete" onsubmit="return confirm('Remover este turno da escala?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= h($slot['id']) ?>">
                  <button class="text-red-600 underline">Excluir turno</button>
                </form>
              </div>
            </div>

            <div class="grid xl:grid-cols-2 gap-6">
              <div>
                <h4 class="font-semibold mb-3">Equipe alocada</h4>
                <div class="space-y-3">
                  <?php if (!empty($slot['assignments'])): foreach ($slot['assignments'] as $assignment): ?>
                    <div class="rounded-2xl border border-slate-200 px-4 py-3 flex items-center justify-between gap-3">
                      <div>
                        <div class="font-semibold"><?= h($assignment['full_name']) ?></div>
                        <div class="text-sm text-slate-500"><?= h($assignment['access_code']) ?> · <?= h($assignment['age_band']) ?><?= $assignment['sex'] ? ' · ' . h(rt2027_task_sex_label((string)$assignment['sex'])) : '' ?></div>
                      </div>
                      <form method="post" action="index.php?route=admin/task-assignment-delete" onsubmit="return confirm('Remover participante desta tarefa?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= h($assignment['id']) ?>">
                        <button class="text-red-600 underline">Remover</button>
                      </form>
                    </div>
                  <?php endforeach; else: ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-slate-500">Nenhum participante alocado neste turno.</div>
                  <?php endif; ?>
                </div>
              </div>
              <div>
                <h4 class="font-semibold mb-3">Adicionar manualmente</h4>
                <form method="post" action="index.php?route=admin/task-assignment-add" class="rounded-2xl border border-slate-200 p-4 mb-4">
                  <?= csrf_field() ?>
                  <input type="hidden" name="task_slot_id" value="<?= h($slot['id']) ?>">
                  <div class="rt-input-wrap"><label>Participante elegível</label><select name="participant_id" required><option value="">Selecione</option><?php foreach ($slot['available_participants'] as $participant): ?><option value="<?= h($participant['id']) ?>"><?= h($participant['full_name']) ?> — <?= h($participant['access_code']) ?><?= $participant['age'] !== null ? ' · ' . h($participant['age']) . ' anos' : '' ?></option><?php endforeach; ?></select></div>
                  <button class="rounded-2xl rt-btn-secondary px-4 py-3 font-semibold">Adicionar ao turno</button>
                </form>
                <div class="text-sm text-slate-500">Participantes indisponíveis para este dia/turno não aparecem na lista.</div>
              </div>
            </div>
          </section>
        <?php endforeach; ?>
        <?php if (empty($taskSlots)): ?>
          <section class="rt-card rounded-3xl p-8 text-center text-slate-500">Nenhum turno cadastrado ainda. Cadastre uma tarefa e depois crie os dias/turnos da escala.</section>
        <?php endif; ?>
      </div>

      <section class="rt-card rounded-3xl p-6 mt-6">
        <h2 class="text-xl font-semibold mb-4">Tarefas cadastradas</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead><tr class="text-left text-slate-500"><th class="pb-3">Tarefa</th><th class="pb-3">Critérios</th><th class="pb-3">Capacidade</th><th class="pb-3">Status</th><th class="pb-3">Ações</th></tr></thead>
            <tbody>
              <?php foreach ($taskDefinitions as $task): ?>
                <tr class="border-t border-slate-200">
                  <td class="py-3 font-semibold"><?= h($task['name']) ?></td>
                  <td class="py-3"><?= h(rt2027_task_sex_label((string)$task['sex_rule'])) ?><?= $task['min_age'] !== null ? ' · mín. ' . h($task['min_age']) : '' ?><?= $task['max_age'] !== null ? ' · máx. ' . h($task['max_age']) : '' ?></td>
                  <td class="py-3"><?= h($task['capacity_per_slot']) ?></td>
                  <td class="py-3"><?= (int)$task['is_active'] === 1 ? 'Ativa' : 'Inativa' ?></td>
                  <td class="py-3 flex gap-3"><a class="underline text-indigo-600" href="index.php?route=admin/tasks&task_id=<?= h($task['id']) ?>">Editar</a><form method="post" action="index.php?route=admin/task-delete" onsubmit="return confirm('Excluir esta tarefa e seus turnos?');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= h($task['id']) ?>"><button class="underline text-red-600">Excluir</button></form></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
