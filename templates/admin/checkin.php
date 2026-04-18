<div class="rt-admin-shell">
  <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>
  <div class="rt-admin-content">
    <div class="rt-admin-topbar rt-card rounded-3xl">
      <div>
        <div class="rt-admin-title">Check-in</div>
        <div class="rt-admin-subtitle">Recepção com QR local, scanner por câmera, fila offline e histórico auditável.</div>
      </div>
      <div class="hidden md:flex items-center gap-3">
        <button type="button" class="rt-theme-toggle" id="rtThemeToggleDesktopInline" aria-label="Alternar tema">🌙</button>
        <a href="<?= h(route_url('admin/logout')) ?>" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600">Sair</a>
      </div>
    </div>

    <main class="rt-admin-content p-6 lg:p-8">
      <?php if ($msg = flash('success')): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>
      <?php if ($msg = flash('error')): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($msg) ?></div><?php endif; ?>

      <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <h1 class="text-3xl font-bold">Check-in operacional</h1>
          <p class="text-slate-600">Marque presença individualmente, em lote por família ou por leitura direta do QR no celular ou notebook.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <a href="<?= h(route_url('admin/checkin/history-csv', array_filter(['search' => $filters['search'] ?? '', 'arrival_from' => $filters['arrival_from'] ?? '', 'arrival_to' => $filters['arrival_to'] ?? '']))) ?>" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Histórico CSV</a>
          <a href="<?= h(route_url('admin/checkin/history-print', array_filter(['search' => $filters['search'] ?? '', 'arrival_from' => $filters['arrival_from'] ?? '', 'arrival_to' => $filters['arrival_to'] ?? '']))) ?>" target="_blank" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Histórico PDF</a>
        </div>
      </div>

      <div class="grid xl:grid-cols-[1.7fr,1fr] gap-6 mb-6">
        <div class="rt-card rounded-3xl shadow p-6">
          <form class="grid md:grid-cols-2 xl:grid-cols-4 gap-4">
            <input type="hidden" name="route" value="admin/checkin">
            <input name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Nome, código ou responsável" class="rounded-xl border-slate-300">
            <select name="sex" class="rounded-xl border-slate-300"><option value="">Sexo</option><option value="M" <?= ($filters['sex'] ?? '')==='M'?'selected':'' ?>>Masculino</option><option value="F" <?= ($filters['sex'] ?? '')==='F'?'selected':'' ?>>Feminino</option></select>
            <select name="accommodation_choice" class="rounded-xl border-slate-300"><option value="">Acomodação</option><option value="chale" <?= ($filters['accommodation_choice'] ?? '')==='chale'?'selected':'' ?>>Chalé</option><option value="alojamento" <?= ($filters['accommodation_choice'] ?? '')==='alojamento'?'selected':'' ?>>Alojamento</option><option value="casa" <?= ($filters['accommodation_choice'] ?? '')==='casa'?'selected':'' ?>>Dormir em casa</option></select>
            <select name="checkin_status" class="rounded-xl border-slate-300"><option value="">Status</option><option value="sim" <?= ($filters['checkin_status'] ?? '')==='sim'?'selected':'' ?>>Presente</option><option value="nao" <?= ($filters['checkin_status'] ?? '')==='nao'?'selected':'' ?>>Pendente</option></select>
            <label class="text-sm text-slate-600">Chegada a partir
              <input type="datetime-local" name="arrival_from" value="<?= h($filters['arrival_from'] ?? '') ?>" class="mt-1 w-full rounded-xl border-slate-300">
            </label>
            <label class="text-sm text-slate-600">Chegada até
              <input type="datetime-local" name="arrival_to" value="<?= h($filters['arrival_to'] ?? '') ?>" class="mt-1 w-full rounded-xl border-slate-300">
            </label>
            <button class="rounded-2xl border border-slate-300 px-4 py-2 font-semibold">Filtrar</button>
            <button type="button" id="rtCheckinSyncBtn" class="rounded-2xl rt-btn-primary px-4 py-2 font-semibold">Sincronizar fila offline</button>
          </form>
        </div>

        <div class="rt-card rounded-3xl shadow p-6">
          <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="text-lg font-bold">Scanner QR local</h2>
            <div class="text-xs text-slate-500">Sem serviço externo</div>
          </div>
          <div id="rtScannerStatus" class="text-sm text-slate-600 mb-3">Use a câmera para ler o QR de uma inscrição.</div>
          <video id="rtQrVideo" class="hidden w-full rounded-2xl border border-slate-200 bg-black" autoplay playsinline muted></video>
          <canvas id="rtQrCanvas" class="hidden"></canvas>
          <div class="flex flex-wrap gap-3 mb-3">
            <button type="button" id="rtStartScan" class="rounded-2xl rt-btn-primary px-4 py-2 font-semibold">Abrir câmera</button>
            <button type="button" id="rtStopScan" class="rounded-2xl border border-slate-300 px-4 py-2 font-semibold text-slate-700">Parar</button>
          </div>
          <form id="rtManualScanForm" class="flex flex-col gap-2">
            <label class="text-sm text-slate-600">Código da inscrição ou URL do QR
              <input type="text" id="rtManualScanValue" class="mt-1 w-full rounded-xl border-slate-300" placeholder="Ex.: RET034 ou URL copiada do QR">
            </label>
            <button type="submit" class="rounded-2xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Processar leitura manual</button>
          </form>
        </div>
      </div>

      <div class="grid md:grid-cols-5 gap-4 mb-6">
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Total listado</div><div class="text-3xl font-bold mt-2"><?= (int)($checkinStats['total_listed'] ?? count($checkinRows)) ?></div></div>
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Já presentes</div><div class="text-3xl font-bold mt-2"><?= h((string)($checkinStats['checked_in'] ?? 0)) ?></div></div>
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Pendentes</div><div class="text-3xl font-bold mt-2"><?= h((string)($checkinStats['pending'] ?? 0)) ?></div></div>
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Famílias</div><div class="text-3xl font-bold mt-2"><?= h((string)($checkinStats['families'] ?? 0)) ?></div></div>
        <div class="rt-card rounded-3xl p-5"><div class="text-sm text-slate-500">Fila offline</div><div class="text-3xl font-bold mt-2" id="rtOfflineQueueCount">0</div><div class="text-xs text-slate-500 mt-2">Última sincronização: <span id="rtOfflineLastSync">—</span></div></div>
      </div>

      <div class="grid xl:grid-cols-[2fr,1fr] gap-6">
        <div class="rt-card rounded-3xl shadow p-6 overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left text-slate-500">
                <th class="pb-3">Participante</th>
                <th class="pb-3">Código</th>
                <th class="pb-3">Acomodação</th>
                <th class="pb-3">Status</th>
                <th class="pb-3">Chegada</th>
                <th class="pb-3">Operador</th>
                <th class="pb-3">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($checkinRows as $row): ?>
                <tr class="border-t border-slate-200 align-top">
                  <td class="py-3">
                    <div class="font-semibold"><?= h($row['full_name']) ?></div>
                    <div class="text-xs text-slate-500"><?= h($row['responsible_name']) ?><?= !empty($row['is_responsible']) ? ' • Responsável' : '' ?></div>
                    <div class="text-xs text-slate-500">Grupo: <?= (int)($row['group_checked'] ?? 0) ?>/<?= (int)($row['group_total'] ?? 1) ?> presentes</div>
                  </td>
                  <td class="py-3 font-semibold"><?= h($row['access_code']) ?></td>
                  <td class="py-3"><?= h(accommodation_label($row['accommodation_choice'])) ?></td>
                  <td class="py-3"><span data-checkin-badge="<?= (int)$row['id'] ?>" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold <?= ($row['checkin_status'] ?? 'nao')==='sim' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' ?>"><?= ($row['checkin_status'] ?? 'nao')==='sim' ? 'Presente' : 'Pendente' ?></span></td>
                  <td class="py-3 text-xs text-slate-600" data-checkin-time="<?= (int)$row['id'] ?>"><?= !empty($row['checked_in_at']) ? h(date('d/m/Y H:i', strtotime((string)$row['checked_in_at']))) : '—' ?></td>
                  <td class="py-3 text-xs text-slate-600" data-checkin-admin="<?= (int)$row['id'] ?>"><?= h((string)($row['checked_in_by_name'] ?? '—')) ?></td>
                  <td class="py-3">
                    <div class="flex flex-wrap gap-2">
                      <form method="post" action="<?= h(route_url('admin/checkin/toggle')) ?>" class="rt-checkin-form">
                        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                        <input type="hidden" name="participant_id" value="<?= (int)$row['id'] ?>">
                        <input type="hidden" name="return_query" value="<?= h(http_build_query(array_merge($_GET, ['route' => 'admin/checkin']))) ?>">
                        <input type="hidden" name="checkin_status" value="<?= ($row['checkin_status'] ?? 'nao')==='sim' ? 'nao' : 'sim' ?>">
                        <button type="submit" class="rounded-2xl px-4 py-2 text-xs font-semibold <?= ($row['checkin_status'] ?? 'nao')==='sim' ? 'border border-slate-300 text-slate-700' : 'rt-btn-primary text-white' ?>">
                          <?= ($row['checkin_status'] ?? 'nao')==='sim' ? 'Desfazer' : 'Marcar presença' ?>
                        </button>
                      </form>

                      <?php if (!empty($row['is_responsible']) && (int)($row['group_total'] ?? 1) > 1): ?>
                        <form method="post" action="<?= h(route_url('admin/checkin/group-toggle')) ?>">
                          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                          <input type="hidden" name="group_id" value="<?= (int)$row['group_id'] ?>">
                          <input type="hidden" name="return_query" value="<?= h(http_build_query(array_merge($_GET, ['route' => 'admin/checkin']))) ?>">
                          <input type="hidden" name="checkin_status" value="<?= !empty($row['group_all_checked']) ? 'nao' : 'sim' ?>">
                          <button type="submit" class="rounded-2xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700"><?= !empty($row['group_all_checked']) ? 'Desfazer família' : 'Marcar família' ?></button>
                        </form>
                      <?php endif; ?>

                      <button type="button" class="rounded-2xl border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 rt-checkin-qr-btn" data-qr-url="<?= h($row['scan_url']) ?>" data-qr-label="<?= h($row['access_code'] . ' • ' . $row['responsible_name']) ?>">QR</button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <aside class="space-y-6">
          <div class="rt-card rounded-3xl shadow p-6">
            <h2 class="text-lg font-bold mb-4">Histórico recente</h2>
            <div class="space-y-4 text-sm max-h-[420px] overflow-auto pr-2">
              <?php if (!$checkinHistory): ?>
                <div class="text-slate-500">Nenhuma alteração de check-in registrada ainda.</div>
              <?php endif; ?>
              <?php foreach ($checkinHistory as $item): ?>
                <div class="border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                  <div class="font-semibold"><?= h($item['full_name'] ?: ('Participante #' . $item['participant_id'])) ?></div>
                  <div class="text-slate-600"><?= h($item['access_code'] ?: '—') ?> • <?= h($item['admin_name'] ?: 'Sistema') ?></div>
                  <div class="text-slate-500 text-xs"><?= h($item['previous_status']) ?> → <?= h($item['new_status']) ?> • <?= h($item['change_source']) ?> • <?= h(date('d/m H:i', strtotime((string)$item['created_at']))) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="rt-card rounded-3xl shadow p-6 hidden" id="rtCheckinQrPanel">
            <h2 class="text-lg font-bold mb-4">QR local da inscrição</h2>
            <div class="flex flex-col items-center gap-4 text-center">
              <div id="rtCheckinQrImage" class="rounded-2xl border border-slate-200 p-2 bg-white"></div>
              <div class="text-sm font-semibold" id="rtCheckinQrLabel"></div>
              <input id="rtCheckinQrUrl" type="text" readonly class="w-full rounded-xl border-slate-300 text-xs">
              <p class="text-xs text-slate-500">O QR aponta para o check-in direto da inscrição em um dispositivo já autenticado.</p>
            </div>
          </div>
        </aside>
      </div>

      <form id="rtQrProcessForm" method="post" action="<?= h(route_url('admin/checkin/qr')) ?>" class="hidden">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="access_code" id="rtQrProcessCode" value="">
      </form>

      <script src="<?= h(rt2027_build_app_url('assets/js/qrcode.min.js')) ?>"></script>
      <script>
      (function(){
        const key='rt2027_checkin_queue';
        const syncKey='rt2027_checkin_last_sync';
        const countEl=document.getElementById('rtOfflineQueueCount');
        const syncBtn=document.getElementById('rtCheckinSyncBtn');
        const lastSyncEl=document.getElementById('rtOfflineLastSync');
        const qrPanel=document.getElementById('rtCheckinQrPanel');
        const qrImage=document.getElementById('rtCheckinQrImage');
        const qrLabel=document.getElementById('rtCheckinQrLabel');
        const qrUrl=document.getElementById('rtCheckinQrUrl');
        const scannerStatus=document.getElementById('rtScannerStatus');
        const video=document.getElementById('rtQrVideo');
        const canvas=document.getElementById('rtQrCanvas');
        const startBtn=document.getElementById('rtStartScan');
        const stopBtn=document.getElementById('rtStopScan');
        const manualForm=document.getElementById('rtManualScanForm');
        const manualInput=document.getElementById('rtManualScanValue');
        let stream=null;
        let timer=null;
        function getQueue(){ try{return JSON.parse(localStorage.getItem(key)||'[]');}catch(e){return [];} }
        function setQueue(v){ localStorage.setItem(key, JSON.stringify(v)); renderCount(); }
        function renderCount(){ if(countEl) countEl.textContent = getQueue().length; }
        function setLastSync(value){ localStorage.setItem(syncKey, value); renderLastSync(); }
        function renderLastSync(){ if(!lastSyncEl) return; const value=localStorage.getItem(syncKey)||''; lastSyncEl.textContent = value ? new Date(value).toLocaleString('pt-BR') : '—'; }
        async function syncQueue(){
          const q=getQueue(); if(!q.length){ renderCount(); return; }
          const failures=[];
          for(const item of q){
            const fd=new FormData();
            fd.append('_csrf', <?= json_encode(csrf_token()) ?>);
            fd.append('participant_id', item.participant_id);
            fd.append('checkin_status', item.checkin_status);
            const res=await fetch(<?= json_encode(route_url('admin/checkin/sync')) ?>, {method:'POST', body:fd, credentials:'same-origin'});
            if(!res.ok){ failures.push(item); continue; }
            const payload=await res.json();
            if(!payload.ok){ failures.push(item); continue; }
            const badge=document.querySelector('[data-checkin-badge="'+item.participant_id+'"]');
            if(badge){ badge.textContent = payload.checkin_status==='sim' ? 'Presente' : 'Pendente'; }
            const timeCell=document.querySelector('[data-checkin-time="'+item.participant_id+'"]');
            if(timeCell){ timeCell.textContent = payload.checked_in_at ? new Date(payload.checked_in_at.replace(' ','T')).toLocaleString('pt-BR') : '—'; }
            const adminCell=document.querySelector('[data-checkin-admin="'+item.participant_id+'"]');
            if(adminCell){ adminCell.textContent = payload.checked_in_by_name || '—'; }
          }
          setQueue(failures);
          setLastSync(new Date().toISOString());
          if(!failures.length){ window.location.reload(); }
        }
        document.querySelectorAll('.rt-checkin-form').forEach(form => {
          form.addEventListener('submit', function(e){
            if(navigator.onLine) return;
            e.preventDefault();
            const pid=form.querySelector('[name="participant_id"]').value;
            const status=form.querySelector('[name="checkin_status"]').value;
            const q=getQueue(); q.push({participant_id:pid, checkin_status:status}); setQueue(q);
            const badge=document.querySelector('[data-checkin-badge="'+pid+'"]');
            if(badge){ badge.textContent = status==='sim' ? 'Presente*' : 'Pendente*'; }
          });
        });
        document.querySelectorAll('.rt-checkin-qr-btn').forEach(btn => {
          btn.addEventListener('click', function(){
            if(!qrPanel || !qrImage || typeof QRCode === 'undefined') return;
            qrPanel.classList.remove('hidden');
            qrImage.innerHTML = '';
            new QRCode(qrImage, { text: btn.dataset.qrUrl || '', width: 180, height: 180, colorDark: '#0f172a', colorLight: '#ffffff' });
            qrLabel.textContent = btn.dataset.qrLabel || '';
            qrUrl.value = btn.dataset.qrUrl || '';
            qrPanel.scrollIntoView({behavior:'smooth', block:'center'});
          });
        });
        function resolveScanValue(value){
          const raw=(value||'').trim();
          if(!raw) return '';
          if(/^https?:\/\//i.test(raw)) return raw;
          return <?= json_encode(rt2027_build_app_url(route_url('admin/checkin/qr'))) ?> + '&access_code=' + encodeURIComponent(raw.replace(/^.*access_code=/i,''));
        }
        const qrProcessForm=document.getElementById('rtQrProcessForm');
        const qrProcessCode=document.getElementById('rtQrProcessCode');
        function extractAccessCode(value){
          const raw=(value||'').trim();
          if(!raw) return '';
          const match = raw.match(/[?&]access_code=([^&#]+)/i);
          if(match){
            try { return decodeURIComponent(match[1].replace(/\+/g,' ')).trim(); } catch(err){ return match[1].trim(); }
          }
          return raw.replace(/^.*access_code=/i,'').trim();
        }
        function processScanValue(value){
          const accessCode=extractAccessCode(value);
          if(!accessCode || !qrProcessForm || !qrProcessCode){ return; }
          qrProcessCode.value = accessCode;
          qrProcessForm.submit();
        }
        async function startScanner(){
          if(!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
            scannerStatus.textContent='Câmera não suportada neste dispositivo. Use a leitura manual.';
            return;
          }
          try{
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = stream;
            video.classList.remove('hidden');
            scannerStatus.textContent='Aponte a câmera para o QR da inscrição.';
            const detector = ('BarcodeDetector' in window) ? new BarcodeDetector({formats:['qr_code']}) : null;
            if(!detector){
              scannerStatus.textContent='Seu navegador não oferece leitura QR por câmera. Use a leitura manual ou um navegador Chromium atualizado.';
              return;
            }
            const ctx=canvas.getContext('2d');
            timer = setInterval(async () => {
              if(!video.videoWidth || !video.videoHeight) return;
              canvas.width = video.videoWidth; canvas.height = video.videoHeight;
              ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
              try {
                const codes = await detector.detect(canvas);
                if(codes && codes.length){
                  stopScanner();
                  processScanValue(codes[0].rawValue || '');
                }
              } catch(err) {}
            }, 500);
          }catch(err){
            scannerStatus.textContent='Não foi possível acessar a câmera. Verifique a permissão do navegador.';
          }
        }
        function stopScanner(){
          if(timer){ clearInterval(timer); timer=null; }
          if(stream){ stream.getTracks().forEach(t=>t.stop()); stream=null; }
          if(video){ video.srcObject=null; video.classList.add('hidden'); }
        }
        if(startBtn){ startBtn.addEventListener('click', startScanner); }
        if(stopBtn){ stopBtn.addEventListener('click', function(){ stopScanner(); scannerStatus.textContent='Scanner parado.'; }); }
        if(manualForm){ manualForm.addEventListener('submit', function(e){ e.preventDefault(); processScanValue(manualInput.value); }); }
        if(syncBtn){ syncBtn.addEventListener('click', function(){ syncQueue().catch(()=>alert('Não foi possível sincronizar agora.')); }); }
        window.addEventListener('online', ()=>{ syncQueue().catch(()=>{}); });
        renderCount();
        renderLastSync();
      })();
      </script>
    </main>
  </div>
</div>
