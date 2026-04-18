<?php $admin = current_admin($pdo); ?>
<?php
$menuSections = [
  [
    'id' => 'visao-geral',
    'label' => 'Visão geral',
    'icon' => '◫',
    'routes' => ['admin/dashboard'],
    'items' => [
      ['route' => 'admin/dashboard', 'label' => 'Dashboard', 'icon' => '⌂'],
    ],
  ],
  [
    'id' => 'cadastros',
    'label' => 'Cadastros',
    'icon' => '▤',
    'routes' => ['admin/groups','admin/participants'],
    'items' => [
      ['route' => 'admin/groups', 'label' => 'Inscrições', 'icon' => '☰'],
      ['route' => 'admin/participants', 'label' => 'Participantes', 'icon' => '◉'],
    ],
  ],
  [
    'id' => 'operacao',
    'label' => 'Operação',
    'icon' => '✦',
    'routes' => ['admin/accommodations','admin/financial','admin/receipts','admin/reports','admin/checkin','admin/tasks'],
    'items' => [
      ['route' => 'admin/accommodations', 'label' => 'Acomodações', 'icon' => '▥'],
      ['route' => 'admin/financial', 'label' => 'Financeiro', 'icon' => '◎'],
      ['route' => 'admin/receipts', 'label' => 'Comprovantes', 'icon' => '⎘'],
      ['route' => 'admin/checkin', 'label' => 'Check-in', 'icon' => '✓'],
      ['route' => 'admin/tasks', 'label' => 'Quadro de tarefas', 'icon' => '🧩'],
      ['route' => 'admin/reports', 'label' => 'Relatórios', 'icon' => '↗'],
    ],
  ],
  [
    'id' => 'alimentacao',
    'label' => 'Alimentação',
    'icon' => '🍽',
    'routes' => ['admin/food-dashboard','admin/food-menus','admin/food-pantry','admin/food-purchases','admin/food-restrictions','admin/food-reports','admin/food-stock-history','admin/food-categories'],
    'items' => [
      ['route' => 'admin/food-dashboard', 'label' => 'Visão geral', 'icon' => '◷'],
      ['route' => 'admin/food-menus', 'label' => 'Cardápio', 'icon' => '☰'],
      ['route' => 'admin/food-pantry', 'label' => 'Despensa', 'icon' => '◫'],
      ['route' => 'admin/food-purchases', 'label' => 'Compras', 'icon' => '🛒'],
      ['route' => 'admin/food-categories', 'label' => 'Categorias', 'icon' => '🏷'],
      ['route' => 'admin/food-restrictions', 'label' => 'Restrições', 'icon' => '🥗'],
      ['route' => 'admin/food-reports', 'label' => 'Relatórios', 'icon' => '📋'],
      ['route' => 'admin/food-stock-history', 'label' => 'Movimentações', 'icon' => '↺'],
    ],
  ],
  [
    'id' => 'sistema',
    'label' => 'Sistema',
    'icon' => '⚑',
    'routes' => ['admin/settings','admin/backups','admin/logout'],
    'items' => [
      ['route' => 'admin/settings', 'label' => 'Configurações', 'icon' => '⚙'],
      ['route' => 'admin/backups', 'label' => 'Backups', 'icon' => '🗄'],
      ['route' => 'admin/logout', 'label' => 'Sair', 'icon' => '⇠'],
    ],
  ],
];
?>
<div class="rt-admin-mobile-topbar lg:hidden">
  <div class="flex items-center gap-3">
    <button type="button" class="rt-admin-hamburger" id="rtAdminMenuToggle" aria-label="Abrir menu"><span></span></button>
    <div>
      <div class="font-bold text-slate-900 dark:text-slate-100"><?= h(setting($pdo, 'event_name', 'Retiro 2027 - ICNV Catedral')) ?></div>
      <div class="text-xs text-slate-500 dark:text-slate-400">Painel administrativo</div>
    </div>
  </div>
  <div class="flex items-center gap-2">
    <button type="button" class="rt-theme-toggle" id="rtThemeToggleMobile" aria-label="Alternar tema">🌙</button>
    <a class="text-sm font-semibold text-slate-600 dark:text-slate-300" href="index.php?route=admin/logout">Sair</a>
  </div>
</div>
<div class="rt-admin-sidebar-overlay" id="rtAdminSidebarOverlay"></div>
<aside id="rtAdminSidebar" class="rt-admin-sidebar w-full lg:w-72 text-slate-100 p-6 lg:min-h-screen" style="background: var(--rt-panel-bg);">
  <div class="mb-6 flex items-start justify-between gap-3">
    <div>
      <div class="text-2xl font-bold"><?= h(setting($pdo, 'event_name', 'Retiro 2027 - ICNV Catedral')) ?></div>
      <div class="text-sm text-slate-400 mt-1">Painel administrativo</div>
    </div>
    <div class="flex items-center gap-2">
      <button type="button" class="rt-theme-toggle hidden lg:inline-flex" id="rtThemeToggleDesktop" aria-label="Alternar tema">🌙</button>
      <button type="button" class="lg:hidden rounded-xl border border-slate-700 px-3 py-2 text-slate-300" id="rtAdminMenuClose" aria-label="Fechar menu">✕</button>
    </div>
  </div>
  <nav class="space-y-3 text-sm">
    <?php foreach ($menuSections as $section):
      $isOpen = in_array($route ?? '', $section['routes'], true);
    ?>
      <div class="rt-menu-section rounded-2xl border border-white/10 bg-white/5" data-menu-section>
        <button type="button" class="rt-menu-section-toggle w-full flex items-center justify-between gap-3 px-4 py-3 text-left" data-menu-toggle aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
          <span class="flex items-center gap-3 font-semibold">
            <span class="text-base"><?= $section['icon'] ?></span>
            <span><?= h($section['label']) ?></span>
          </span>
          <span class="rt-menu-chevron text-slate-300 <?= $isOpen ? 'is-open' : '' ?>">⌄</span>
        </button>
        <div class="rt-menu-section-items <?= $isOpen ? 'is-open' : '' ?>" data-menu-panel>
          <div class="px-3 pb-3 space-y-1">
            <?php foreach ($section['items'] as $item):
              $active = ($route ?? '') === $item['route'];
            ?>
              <a class="rt-menu-link flex items-center gap-3 rounded-2xl px-4 py-3 <?= $active ? 'bg-white text-slate-900 font-semibold shadow-sm' : 'text-slate-100 hover:bg-white/10' ?>" href="index.php?route=<?= urlencode($item['route']) ?>">
                <span class="text-base"><?= $item['icon'] ?></span>
                <span><?= h($item['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </nav>
  <div class="mt-6 rounded-2xl bg-slate-800/90 p-4 text-sm border border-white/10">
    <div class="font-semibold"><?= h($admin['name'] ?? 'Administrador') ?></div>
    <div class="text-slate-400 break-all mb-3"><?= h($admin['email'] ?? '') ?></div>
    <div class="text-xs text-slate-400">Tema, navegação e preferências ficam salvos neste navegador.</div>
  </div>
</aside>
