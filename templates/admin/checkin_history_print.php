<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Histórico de Check-in</title>
  <style>
    body{font-family:Arial,sans-serif;color:#0f172a;margin:24px}
    h1{margin:0 0 8px} p{margin:0 0 18px;color:#475569}
    table{width:100%;border-collapse:collapse;font-size:12px}
    th,td{border:1px solid #cbd5e1;padding:8px;vertical-align:top}
    th{background:#e2e8f0;text-align:left}
    @media print { .no-print{display:none} body{margin:0} }
  </style>
</head>
<body>
  <div class="no-print" style="margin-bottom:16px;"><button onclick="window.print()">Imprimir / Salvar em PDF</button></div>
  <h1>Histórico de Check-in</h1>
  <p>Gerado em <?= h(date('d/m/Y H:i')) ?>.</p>
  <table>
    <thead>
      <tr>
        <th>Data/Hora</th>
        <th>Código</th>
        <th>Responsável</th>
        <th>Participante</th>
        <th>Anterior</th>
        <th>Novo</th>
        <th>Origem</th>
        <th>Contexto</th>
        <th>Administrador</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($historyRows as $row): ?>
        <tr>
          <td><?= h((string)($row['created_at'] ?? '')) ?></td>
          <td><?= h((string)($row['access_code'] ?? '')) ?></td>
          <td><?= h((string)($row['responsible_name'] ?? '')) ?></td>
          <td><?= h((string)($row['full_name'] ?? '')) ?></td>
          <td><?= h((string)($row['previous_status'] ?? '')) ?></td>
          <td><?= h((string)($row['new_status'] ?? '')) ?></td>
          <td><?= h((string)($row['change_source'] ?? '')) ?></td>
          <td><?= h((string)($row['change_context'] ?? '')) ?></td>
          <td><?= h((string)($row['admin_name'] ?? '')) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <script>window.addEventListener('load',()=>setTimeout(()=>window.print(),300));</script>
</body>
</html>
