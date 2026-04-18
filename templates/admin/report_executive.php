<?php
/** @var array $reportRows */
/** @var array $reportTotals */
/** @var string $generatedAt */
$rawJson = json_encode($reportRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$eventName = setting($pdo, 'event_name', 'Retiro 2027 - ICNV Catedral');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h($eventName) ?> • Relatório executivo</title>
  <style>

    :root{
      --bg:#f4f8f7;
      --surface:#ffffff;
      --surface-2:#eef7f5;
      --text:#1f2937;
      --muted:#6b7280;
      --line:#dbe5e3;
      --brand:#0f766e;
      --brand-2:#115e59;
      --brand-soft:#d1fae5;
      --blue-soft:#e0f2fe;
      --blue:#0369a1;
      --orange-soft:#ffedd5;
      --orange:#c2410c;
      --purple-soft:#f3e8ff;
      --purple:#7e22ce;
      --red-soft:#fee2e2;
      --red:#b91c1c;
      --shadow:0 10px 30px rgba(15, 23, 42, .08);
      --radius:18px;
    }
    *{box-sizing:border-box}
    body{
      margin:0;
      font-family:Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      background:
        radial-gradient(circle at top right, rgba(15,118,110,.10), transparent 28%),
        radial-gradient(circle at top left, rgba(3,105,161,.06), transparent 22%),
        var(--bg);
      color:var(--text);
    }
    .wrap{max-width:1100px;margin:0 auto;padding:20px 14px 40px;}
    .hero{
      background:linear-gradient(135deg, #0b3d3a 0%, #0f766e 100%);
      color:#fff;border-radius:26px;padding:24px 18px;box-shadow:var(--shadow);
    }
    .hero h1{margin:0 0 8px;font-size:clamp(28px, 5vw, 42px);line-height:1.05;letter-spacing:-.03em;}
    .hero p{margin:0;color:rgba(255,255,255,.85);max-width:760px;}
    .pill-row{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;}
    .pill{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.12);border-radius:999px;padding:10px 14px;font-size:14px;backdrop-filter:blur(8px);}
    .note{margin-top:14px;background:rgba(255,255,255,.10);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:14px 16px;font-size:14px;line-height:1.5;color:rgba(255,255,255,.94);}
    .toolbar{position:sticky;top:0;z-index:5;margin:18px 0 14px;background:rgba(244,248,247,.9);backdrop-filter:blur(10px);padding:12px 0;}
    .toolbar-inner{display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
    .search{flex:1 1 260px;min-width:0;border:1px solid var(--line);border-radius:16px;background:var(--surface);padding:14px 16px;font-size:15px;box-shadow:0 4px 16px rgba(15, 23, 42, .04);color:var(--text);}
    .segmented, .filters{display:flex;flex-wrap:wrap;gap:8px;}
    button{font:inherit;border:0;cursor:pointer;}
    .segmented button, .filters button{padding:11px 14px;border-radius:999px;background:var(--surface);border:1px solid var(--line);color:var(--muted);box-shadow:0 4px 16px rgba(15, 23, 42, .04);}
    .segmented button.active, .filters button.active{background:var(--brand);border-color:var(--brand);color:#fff;}
    .counter{margin:8px 0 0;color:var(--muted);font-size:14px;}
    .section.hidden{display:none}
    .grid{display:grid;gap:14px;}
    .family-grid{grid-template-columns:repeat(auto-fit, minmax(290px, 1fr));}
    .summary-grid{grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));}
    .card{background:var(--surface);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:18px;}
    .card h3{margin:0 0 8px;font-size:22px;line-height:1.15;letter-spacing:-.02em;}
    .muted{color:var(--muted);font-size:14px;}
    .meta-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;}
    .badge{display:inline-flex;align-items:center;gap:6px;font-size:13px;padding:8px 10px;border-radius:999px;background:#f5f7f8;color:var(--muted);border:1px solid var(--line);line-height:1;}
    .badge.primary{background:var(--brand-soft);border-color:transparent;color:var(--brand);font-weight:700;}
    .badge.blue{background:var(--blue-soft);border-color:transparent;color:var(--blue);font-weight:700;}
    .badge.orange{background:var(--orange-soft);border-color:transparent;color:var(--orange);font-weight:700;}
    .badge.purple{background:var(--purple-soft);border-color:transparent;color:var(--purple);font-weight:700;}
    .badge.red{background:var(--red-soft);border-color:transparent;color:var(--red);font-weight:700;}
    .member-list, .summary-list{list-style:none;padding:0;margin:16px 0 0;display:grid;gap:10px;}
    .member-list li, .summary-list li{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;border-top:1px solid var(--line);padding-top:10px;}
    .member-left{min-width:0;}
    .member-name{font-weight:700;font-size:14px;color:var(--text);}
    .member-age{color:var(--muted);font-size:13px;margin-top:2px;}
    .member-price{font-weight:700;font-size:14px;color:var(--brand);white-space:nowrap;}
    .summary-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:8px;}
    .summary-tag{padding:10px 12px;border-radius:999px;font-size:13px;font-weight:700;white-space:nowrap;background:var(--brand-soft);color:var(--brand);}
    .empty{color:var(--muted);font-style:italic;}
    .footer{margin-top:18px;text-align:center;color:var(--muted);font-size:13px;}
    @media (max-width:720px){
      .wrap{padding:16px 12px 28px}
      .hero{padding:20px 16px; border-radius:22px}
      .card{padding:16px; border-radius:16px}
      .card h3{font-size:20px}
      .member-list li, .summary-list li{flex-direction:column;align-items:flex-start;}
    }
  
  </style>
</head>
<body>
  <div class="wrap">
    <header class="hero">
      <h1><?= h($eventName) ?></h1>
      <p>Relatório interativo de inscrições, acomodações e estimativa financeira.</p>
      <div class="pill-row" id="heroPills"></div>
      <div class="note">
        <strong>Regras de valor:</strong> Chalé → 0–2 anos = cortesia · 3–9 anos = R$ 350,00 · 10+ anos = R$ 700,00 · Demais acomodações → 0–2 anos = cortesia · 3–9 anos = R$ 300,00 · 10+ anos = R$ 600,00.<br>
        <strong>Parcelamento:</strong> até 10x, preferencialmente até o dia <?= h(setting($pdo, 'payment_deadline_day', '10')) ?> de cada mês.<br>
        <strong>Confirmação:</strong> a inscrição só será confirmada mediante aceno financeiro.
      </div>
    </header>

    <div class="toolbar">
      <div class="toolbar-inner">
        <div class="filters" aria-label="Ordenação">
          <button class="active" type="button">Ordenação: <?= h($reportSortBy) ?> / <?= h($reportSortDir) ?></button>
        </div>
        <input id="searchInput" class="search" type="search" placeholder="Buscar por responsável, membro, acomodação, telefone ou observação" />
        <div class="segmented" aria-label="Modo de visualização">
          <button class="active" data-view-btn="families">Por família</button>
          <button data-view-btn="summary">Por acomodação</button>
        </div>
        <div class="filters" aria-label="Filtro de acomodação">
          <button class="active" data-filter="all">Todos</button>
          <button data-filter="chale">Chalé</button>
          <button data-filter="alojamento">Alojamento</button>
          <button data-filter="casa">Dorme em casa</button>
        </div>
      </div>
      <p id="counterText" class="counter"></p>
    </div>

    <section id="familiesSection" class="section">
      <div id="familiesGrid" class="grid family-grid"></div>
    </section>

    <section id="summarySection" class="section hidden">
      <div id="summaryGrid" class="grid summary-grid"></div>
    </section>

    <p class="footer">Arquivo gerado automaticamente em <?= h($generatedAt) ?> com base nos dados atuais do sistema.</p>
  </div>

  <script>
    const RAW_DATA = <?= $rawJson ?: '[]' ?>;

    function valorPorIdade(idade){
      if (idade === null || idade === undefined || idade === "") return null;
      if (idade <= 2) return 0;
      if (idade <= 9) return 300;
      return 600;
    }

    function faixaEtaria(idade){
      if (idade === null || idade === undefined || idade === "") return "Idade pendente";
      if (idade <= 2) return "0–2 anos";
      if (idade <= 9) return "3–9 anos";
      if (idade <= 17) return "10–17 anos";
      return "18+";
    }

    function normalizarAcomodacao(txt){
      const t = (txt || "").toLowerCase().normalize("NFD").replace(/[̀-ͯ]/g, "");
      if (t.includes("chale")) return "chale";
      if (t.includes("aloj")) return "alojamento";
      if (t.includes("casa")) return "casa";
      return "outro";
    }

    function moeda(v){
      return new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(v);
    }

    const families = RAW_DATA.map((item, idx) => {
      const membros = (item.membros || []).map(m => {
        const idade = (m.idade === null || m.idade === undefined || m.idade === "") ? null : Number(m.idade);
        const valor = m.valor === null || m.valor === undefined || m.valor === '' ? valorPorIdade(idade) : Number(m.valor);
        return {
          nome: m.nome || "Sem nome",
          idade,
          valor,
          faixa: faixaEtaria(idade)
        };
      });

      const valorTotal = membros.reduce((s, m) => s + (m.valor || 0), 0);
      const totalPessoas = membros.length;
      const tipo = normalizarAcomodacao(item.acomodacao);

      const contagemFaixas = {
        "0–2 anos": 0,
        "3–9 anos": 0,
        "10–17 anos": 0,
        "18+": 0,
        "Idade pendente": 0
      };

      membros.forEach(m => contagemFaixas[m.faixa]++);

      const searchBlob = [
        item.responsavel,
        item.telefone,
        item.acomodacao,
        item.observacoes,
        item.preferenciaCompartilhar,
        item.divideChale ? "divide chale sim" : "divide chale nao",
        ...membros.map(m => `${m.nome} ${m.idade ?? ""} ${m.faixa}`)
      ].join(" ").toLowerCase();

      return {
        id: idx + 1,
        codigo: item.codigo || '',
        responsavel: item.responsavel || "Sem responsável",
        telefone: item.telefone || "",
        acomodacao: item.acomodacao || "Não informado",
        tipo,
        divideChale: !!item.divideChale,
        observacoes: item.observacoes || "",
        preferenciaCompartilhar: item.preferenciaCompartilhar || "",
        membros,
        valorTotal,
        totalPessoas,
        contagemFaixas,
        searchBlob
      };
    });

    const totals = {
      familias: families.length,
      pessoas: families.reduce((s, f) => s + f.totalPessoas, 0),
      chale: families.filter(f => f.tipo === "chale").length,
      alojamento: families.filter(f => f.tipo === "alojamento").length,
      casa: families.filter(f => f.tipo === "casa").length,
      divideChale: families.filter(f => f.tipo === "chale" && f.divideChale).length,
      valorPrevisto: families.reduce((s, f) => s + f.valorTotal, 0),
      idadesPendentes: families.reduce((s, f) => s + f.contagemFaixas["Idade pendente"], 0)
    };

    const byAccommodation = [
      { key: "chale", titulo: "Chalés", subtitulo: "Famílias que pediram chalé", tag: `${totals.chale} grupo(s)` },
      { key: "alojamento", titulo: "Alojamento", subtitulo: "Inscrições para alojamento", tag: `${totals.alojamento} grupo(s)` },
      { key: "casa", titulo: "Dorme em casa", subtitulo: "Participantes sem hospedagem", tag: `${totals.casa} grupo(s)` }
    ];

    const heroPills = document.getElementById("heroPills");
    const familiesGrid = document.getElementById("familiesGrid");
    const summaryGrid = document.getElementById("summaryGrid");
    const counterText = document.getElementById("counterText");
    const searchInput = document.getElementById("searchInput");
    const familiesSection = document.getElementById("familiesSection");
    const summarySection = document.getElementById("summarySection");
    const viewButtons = document.querySelectorAll("[data-view-btn]");
    const filterButtons = document.querySelectorAll("[data-filter]");

    let currentView = "families";
    let currentFilter = "all";
    let currentSearch = "";

    function renderHero(){
      heroPills.innerHTML = `
        <span class="pill">👥 ${totals.pessoas} pessoas mapeadas</span>
        <span class="pill">🏠 ${totals.familias} famílias/grupos</span>
        <span class="pill">🏡 ${totals.chale} chalés</span>
        <span class="pill">🏢 ${totals.alojamento} alojamentos</span>
        <span class="pill">🏠 ${totals.casa} dormem em casa</span>
        <span class="pill">💰 ${moeda(totals.valorPrevisto)} estimado</span>
        <span class="pill">⚠️ ${totals.idadesPendentes} idade(s) pendente(s)</span>
      `;
    }

    function getFilteredFamilies(){
      return families.filter(f => {
        const byType = currentFilter === "all" ? true : f.tipo === currentFilter;
        const bySearch = currentSearch.trim() === "" ? true : f.searchBlob.includes(currentSearch);
        return byType && bySearch;
      });
    }

    function renderFamilies(){
      const visible = getFilteredFamilies();

      familiesGrid.innerHTML = visible.map(f => {
        const badgesFaixa = `
          ${f.contagemFaixas["0–2 anos"] ? `<span class="badge blue">👶 ${f.contagemFaixas["0–2 anos"]} cortesia</span>` : ""}
          ${f.contagemFaixas["3–9 anos"] ? `<span class="badge orange">🧒 ${f.contagemFaixas["3–9 anos"]} infantil</span>` : ""}
          ${f.contagemFaixas["10–17 anos"] ? `<span class="badge purple">🧑 ${f.contagemFaixas["10–17 anos"]} juvenil</span>` : ""}
          ${f.contagemFaixas["18+"] ? `<span class="badge primary">🧑‍🤝‍🧑 ${f.contagemFaixas["18+"]} adulto(s)</span>` : ""}
          ${f.contagemFaixas["Idade pendente"] ? `<span class="badge red">⚠️ ${f.contagemFaixas["Idade pendente"]} sem idade</span>` : ""}
        `;

        const membrosHtml = f.membros.length
          ? f.membros.map(m => `
              <li>
                <div class="member-left">
                  <div class="member-name">${m.nome}</div>
                  <div class="member-age">${m.idade === null ? "idade não informada" : `${m.idade} anos`} • ${m.faixa}</div>
                </div>
                <div class="member-price">${m.valor === null ? "pendente" : moeda(m.valor)}</div>
              </li>
            `).join("")
          : `<li class="empty">Sem membros informados.</li>`;

        const prefCompart = f.preferenciaCompartilhar
          ? `<div class="muted" style="margin-top:8px"><strong>Preferência para dividir:</strong> ${f.preferenciaCompartilhar}</div>`
          : "";

        return `
          <article class="card">
            <h3>${f.responsavel}</h3><div class="muted">Código ${f.codigo || ('#'+f.id)}</div>
            <div class="muted">${f.telefone || "Telefone não informado"}</div>

            <div class="meta-row">
              <span class="badge primary">${f.totalPessoas} pessoa(s)</span>
              <span class="badge">${f.acomodacao}</span>
              ${f.tipo === "chale"
                ? `<span class="badge ${f.divideChale ? "blue" : ""}">${f.divideChale ? "Aceita dividir chalé" : "Não divide chalé"}</span>`
                : ""}
              <span class="badge primary">💰 ${moeda(f.valorTotal)}</span>
            </div>

            <div class="meta-row">
              ${badgesFaixa}
            </div>

            ${f.observacoes ? `<div class="muted" style="margin-top:12px"><strong>Obs.:</strong> ${f.observacoes}</div>` : ""}
            ${prefCompart}

            <ul class="member-list">
              ${membrosHtml}
            </ul>
          </article>
        `;
      }).join("");

      counterText.textContent = `${visible.length} família(s)/grupo(s) exibido(s).`;
    }

    function renderSummary(){
      const visibleFamilies = getFilteredFamilies();
      summaryGrid.innerHTML = byAccommodation.map(group => {
        const itens = visibleFamilies.filter(f => f.tipo === group.key);
        const totalPessoas = itens.reduce((s, f) => s + f.totalPessoas, 0);
        const totalValor = itens.reduce((s, f) => s + f.valorTotal, 0);
        const pendentes = itens.reduce((s, f) => s + f.contagemFaixas["Idade pendente"], 0);

        const content = itens.length
          ? itens.map(f => `
              <li>
                <div class="member-left">
                  <div class="member-name">${f.responsavel}</div>
                  <div class="member-age">${f.totalPessoas} pessoa(s)${f.tipo === "chale" ? ` • ${f.divideChale ? "aceita dividir" : "não divide"}` : ""}</div>
                </div>
                <div class="member-price">${moeda(f.valorTotal)}</div>
              </li>
            `).join("")
          : `<li class="empty">Nenhum registro nesta categoria.</li>`;

        return `
          <article class="card">
            <div class="summary-head">
              <div>
                <h3>${group.titulo}</h3>
                <p class="muted">${group.subtitulo}</p>
              </div>
              <span class="summary-tag">${group.tag}</span>
            </div>

            <div class="meta-row">
              <span class="badge primary">${totalPessoas} pessoa(s)</span>
              <span class="badge">💰 ${moeda(totalValor)}</span>
              ${pendentes ? `<span class="badge red">⚠️ ${pendentes} idade(s) pendente(s)</span>` : ""}
            </div>

            <ul class="summary-list">
              ${content}
            </ul>
          </article>
        `;
      }).join("");

      counterText.textContent = "Resumo por acomodação exibido.";
    }

    function updateView(){
      if(currentView === "families"){
        familiesSection.classList.remove("hidden");
        summarySection.classList.add("hidden");
        renderFamilies();
      } else {
        familiesSection.classList.add("hidden");
        summarySection.classList.remove("hidden");
        renderSummary();
      }

      viewButtons.forEach(btn => {
        btn.classList.toggle("active", btn.dataset.viewBtn === currentView);
      });

      filterButtons.forEach(btn => {
        btn.classList.toggle("active", btn.dataset.filter === currentFilter);
      });
    }

    viewButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        currentView = btn.dataset.viewBtn;
        updateView();
      });
    });

    filterButtons.forEach(btn => {
      btn.addEventListener("click", () => {
        currentFilter = btn.dataset.filter;
        updateView();
      });
    });

    searchInput.addEventListener("input", e => {
      currentSearch = e.target.value.toLowerCase();
      if(currentView !== "families") currentView = "families";
      updateView();
    });

    renderHero();
    updateView();
  </script>
</body>
</html>
