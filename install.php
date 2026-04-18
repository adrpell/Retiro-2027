<?php
$lockFile = __DIR__ . '/storage/install.lock';

if (file_exists($lockFile)) {
    http_response_code(403);
    exit('Instalador bloqueado por segurança.');
}

session_start();
require_once __DIR__ . '/app/helpers.php';
require_once __DIR__ . '/app/Database.php';
require_once __DIR__ . '/app/MigrationRunner.php';

$installed = file_exists(__DIR__ . '/config/config.php');
if ($installed) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appName = trim($_POST['app_name'] ?? 'Retiro 2027 - ICNV Catedral');
    $baseUrl = trim($_POST['base_url'] ?? '');
    $dbConfig = [
        'host' => trim($_POST['db_host'] ?? 'localhost'),
        'port' => trim($_POST['db_port'] ?? '3306'),
        'database' => trim($_POST['db_database'] ?? ''),
        'username' => trim($_POST['db_username'] ?? ''),
        'password' => (string)($_POST['db_password'] ?? ''),
        'charset' => 'utf8mb4',
    ];
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = (string)($_POST['admin_password'] ?? '');
    $loadDemo = !empty($_POST['load_demo']);

    try {
        if ($dbConfig['database'] === '' || $dbConfig['username'] === '' || $adminName === '' || $adminEmail === '' || $adminPassword === '') {
            throw new RuntimeException('Preencha os campos obrigatórios.');
        }

        $config = [
            'app_name' => $appName,
            'base_url' => $baseUrl,
            'db' => $dbConfig,
            'session_name' => 'rt2027_session',
        ];

        $pdo = Database::connect($config);
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schema);
        MigrationRunner::runPending($pdo, __DIR__ . '/database/migrations');

        $stmt = $pdo->prepare('INSERT INTO admins (name, email, password_hash, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash)');
        $stmt->execute([$adminName, $adminEmail, password_hash($adminPassword, PASSWORD_DEFAULT), 'admin']);

        $defaults = file_get_contents(__DIR__ . '/database/default_settings.sql');
        $pdo->exec($defaults);

        if ($loadDemo) {
            $demo = file_get_contents(__DIR__ . '/database/sample_data.sql');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
            $pdo->exec('TRUNCATE TABLE participants;');
            $pdo->exec('TRUNCATE TABLE payments;');
            $pdo->exec('TRUNCATE TABLE groups;');
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
            $pdo->exec($demo);
        }

        $configCode = "<?php\nreturn " . var_export($config, true) . ";\n";
        file_put_contents(__DIR__ . '/config/config.php', $configCode);

        $success = 'Instalação concluída com sucesso. Entre no sistema.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
file_put_contents(__DIR__ . '/storage/install.lock', 'locked');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Instalação | Retiro 2027</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-10 px-4">
  <div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-3xl shadow-xl p-8">
      <h1 class="text-3xl font-bold text-slate-900 mb-2">Instalação do Sistema Retiro 2027</h1>
      <p class="text-slate-600 mb-6">Preencha os dados abaixo para gerar a configuração e criar o primeiro administrador.</p>
      <?php if ($error): ?><div class="mb-4 rounded-xl bg-red-50 text-red-700 px-4 py-3"><?= h($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="mb-4 rounded-xl bg-green-50 text-green-700 px-4 py-3"><?= h($success) ?> <a class="underline font-semibold" href="index.php?route=admin/login">Entrar</a></div><?php endif; ?>

      <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2"><h2 class="font-semibold text-xl">Aplicação</h2></div>
        <label class="block">
          <span class="text-sm font-medium text-slate-700">Nome da aplicação</span>
          <input name="app_name" value="Retiro 2027 - ICNV Catedral" class="mt-1 w-full rounded-xl border-slate-300">
        </label>
        <label class="block">
          <span class="text-sm font-medium text-slate-700">URL base (opcional)</span>
          <input name="base_url" placeholder="https://seu-dominio.com.br/retiro2027" class="mt-1 w-full rounded-xl border-slate-300">
        </label>

        <div class="md:col-span-2"><h2 class="font-semibold text-xl">Banco de dados</h2></div>
        <label class="block"><span class="text-sm font-medium text-slate-700">Host</span><input name="db_host" value="localhost" class="mt-1 w-full rounded-xl border-slate-300"></label>
        <label class="block"><span class="text-sm font-medium text-slate-700">Porta</span><input name="db_port" value="3306" class="mt-1 w-full rounded-xl border-slate-300"></label>
        <label class="block"><span class="text-sm font-medium text-slate-700">Banco</span><input name="db_database" class="mt-1 w-full rounded-xl border-slate-300" required></label>
        <label class="block"><span class="text-sm font-medium text-slate-700">Usuário</span><input name="db_username" class="mt-1 w-full rounded-xl border-slate-300" required></label>
        <label class="block md:col-span-2"><span class="text-sm font-medium text-slate-700">Senha do banco</span><input type="password" name="db_password" class="mt-1 w-full rounded-xl border-slate-300"></label>

        <div class="md:col-span-2"><h2 class="font-semibold text-xl">Administrador</h2></div>
        <label class="block"><span class="text-sm font-medium text-slate-700">Nome</span><input name="admin_name" class="mt-1 w-full rounded-xl border-slate-300" required></label>
        <label class="block"><span class="text-sm font-medium text-slate-700">E-mail</span><input type="email" name="admin_email" class="mt-1 w-full rounded-xl border-slate-300" required></label>
        <label class="block md:col-span-2"><span class="text-sm font-medium text-slate-700">Senha</span><input type="password" name="admin_password" class="mt-1 w-full rounded-xl border-slate-300" required></label>

        <label class="md:col-span-2 flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
          <input type="checkbox" name="load_demo" value="1" checked>
          <span>Carregar dados de demonstração baseados na planilha anexada</span>
        </label>

        <div class="md:col-span-2 flex gap-3">
          <button class="rounded-2xl bg-indigo-600 px-5 py-3 text-white font-semibold">Instalar sistema</button>
          <a href="README.md" class="rounded-2xl border border-slate-300 px-5 py-3 font-semibold text-slate-700">Ler README</a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
