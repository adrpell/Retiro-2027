<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Quadro de tarefas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#f5f7fb;margin:0;color:#0f172a}
    .wrap{max-width:1180px;margin:0 auto;padding:24px}
    .hero{background:linear-gradient(135deg,#0f172a,#0f766e);color:#fff;border-radius:24px;padding:28px 32px;margin-bottom:22px}
    .toolbar{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:18px}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 18px;border-radius:14px;text-decoration:none;font-weight:700}
    .btn-primary{background:#0f766e;color:#fff}.btn-secondary{background:#fff;color:#0f172a;border:1px solid #cbd5e1}
    .stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}
    .card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 10px 24px rgba(15,23,42,.06)}
    .stat{padding:18px}.stat .label{font-size:13px;color:#64748b}.stat .value{font-size:30px;font-weight:800;margin-top:8px}
    .section{margin-bottom:20px;padding:20px}.section h2{margin:0 0 12px 0}
    .slot{border:1px solid #e2e8f0;border-radius:18px;padding:16px;margin-bottom:14px}
    .slot-head{display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:12px}
    .chip{display:inline-flex;padding:6px 10px;border-radius:999px;background:#ecfeff;color:#0f766e;font-weight:700;font-size:12px}
    table{width:100%;border-collapse:collapse}.th,th{font-size:12px;text-transform:uppercase;letter-spacing:.04em;color:#64748b;text-align:left}
    td,th{padding:10px 8px;border-top:1px solid #e2e8f0}
    @media (max-width:900px){.stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="toolbar">
      <a class="btn btn-secondary" href="index.php?route=admin/tasks">Voltar ao painel</a>
      <a class="btn btn-primary" href="index.php?route=admin/tasks-report&mode=download_html">Baixar HTML</a>
      <a class="btn btn-secondary" href="index.php?route=admin/tasks-report&mode=pdf">Imprimir / PDF</a>
    </div>
    <div class="hero">
      <h1 style="margin:0 0 8px 0;">Quadro de tarefas</h1>
      <div>Escala organizada por dias e turnos, com bloqueio de repetição da mesma pessoa no mesmo período.</div>
      <div style="margin-top:8px;font-size:14px;opacity:.9;">Gerado em <?= h($generatedAt) ?></div>
    </div>
    <div class="stats">
      <div class="card stat"><div class="label">Turnos</div><div class="value"><?= h($taskSummary['slots'] ?? 0) ?></div></div>
      <div class="card stat"><div class="label">Alocações</div><div class="value"><?= h($taskSummary['assignments'] ?? 0) ?></div></div>
      <div class="card stat"><div class="label">Capacidade</div><div class="value"><?= h($taskSummary['capacity'] ?? 0) ?></div></div>
      <div class="card stat"><div class="label">Dias cobertos</div><div class="value"><?= h($taskSummary['days'] ?? 0) ?></div></div>
    </div>
    <?php foreach ($taskReportGrouped as $slotDate => $shifts): ?>
      <div class="card section">
        <h2><?= h(date('d/m/Y', strtotime((string)$slotDate))) ?></h2>
        <?php foreach ($shifts as $shiftKey => $slots): ?>
          <div class="slot">
            <div class="slot-head">
              <div>
                <h3 style="margin:0 0 6px 0;"><?= h($taskShiftOptions[$shiftKey] ?? $shiftKey) ?></h3>
                <div style="font-size:13px;color:#64748b;">Turno com bloqueio automático de conflito por período.</div>
              </div>
              <div class="chip"><?= h(array_sum(array_map(fn($slot) => count($slot['assignments'] ?? []), $slots))) ?> pessoa(s) alocada(s)</div>
            </div>
            <?php foreach ($slots as $slot): ?>
              <div style="margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
                  <div>
                    <div style="font-weight:700;"><?= h($slot['task_name']) ?></div>
                    <div style="font-size:13px;color:#64748b;">Critérios: <?= h(rt2027_task_sex_label((string)$slot['sex_rule'])) ?><?= $slot['min_age'] !== null ? ' · mín. ' . h($slot['min_age']) : '' ?><?= $slot['max_age'] !== null ? ' · máx. ' . h($slot['max_age']) : '' ?></div>
                  </div>
                  <div class="chip"><?= h(count($slot['assignments'] ?? [])) ?>/<?= h($slot['slot_capacity'] ?? 0) ?></div>
                </div>
                <table>
                  <thead><tr><th>Participante</th><th>Código</th><th>Sexo</th><th>Idade</th><th>Modo</th></tr></thead>
                  <tbody>
                    <?php if (!empty($slot['assignments'])): foreach ($slot['assignments'] as $assignment): ?>
                      <tr>
                        <td><?= h($assignment['full_name']) ?></td>
                        <td><?= h($assignment['access_code']) ?></td>
                        <td><?= h(rt2027_task_sex_label((string)$assignment['sex'])) ?></td>
                        <td><?= h($assignment['age'] ?? '') ?></td>
                        <td><?= h(($assignment['assignment_mode'] ?? 'manual') === 'auto' ? 'Automático' : 'Manual') ?></td>
                      </tr>
                    <?php endforeach; else: ?>
                      <tr><td colspan="5">Nenhum participante alocado neste turno.</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
