<?php
if ($route === 'admin/logout') {
    session_destroy();
    redirect_to('admin/login');
}

if ($route === 'admin/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        redirect_to('admin/dashboard');
    }
    flash('error', 'Credenciais inválidas.');
    redirect_to('admin/login');
}


if ($route === 'admin/forgot-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $token = create_password_reset($pdo, $email);
    if ($token) {
        $link = rt2027_build_app_url('index.php?route=admin/reset-password&token=' . urlencode($token));
        flash('success', 'Link de recuperação gerado: <a class="underline" href="' . h($link) . '">abrir redefinição</a>');
    } else {
        flash('success', 'Se o e-mail existir, um link de recuperação foi gerado.');
    }
    redirect_to('admin/forgot-password');
}

if ($route === 'admin/reset-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $token = trim((string)($_POST['token'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmation = (string)($_POST['password_confirmation'] ?? '');
    $reset = validate_password_reset($pdo, $token);
    if (!$reset) {
        flash('error', 'Link inválido ou expirado.');
        header('Location: index.php?route=admin/reset-password&token=' . urlencode($token));
        exit;
    }
    if (strlen($password) < 6 || $password !== $confirmation) {
        flash('error', 'Confira a senha e a confirmação.');
        header('Location: index.php?route=admin/reset-password&token=' . urlencode($token));
        exit;
    }
    $pdo->prepare('UPDATE admins SET password_hash=? WHERE id=?')->execute([password_hash($password, PASSWORD_DEFAULT), $reset['admin_id']]);
    $pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=?')->execute([$reset['id']]);
    flash('success', 'Senha atualizada com sucesso.');
    redirect_to('admin/login');
}

if ($route === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();

    try {
        $result = registration_service($pdo)->registerPublic($_POST, $_FILES);
    } catch (InvalidArgumentException|RuntimeException $e) {
        flash('error', $e->getMessage());
        redirect_to('register');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível concluir a inscrição. Revise os dados e tente novamente.');
        redirect_to('register');
    }

    registration_service($pdo)->sendRegistrationConfirmation((int)$result['group_id'], (string)$result['responsible_email']);
    flash('success', 'Inscrição enviada com sucesso. Código de acesso: ' . $result['access_code']);
    $redirectLogin = ((string)$result['responsible_email'] !== '' && filter_var((string)$result['responsible_email'], FILTER_VALIDATE_EMAIL))
        ? (string)$result['responsible_email']
        : (string)$result['access_code'];
    header('Location: ' . route_url('lookup', ['login' => $redirectLogin, 'responsible_name' => (string)$result['responsible_name']]));
    exit;
}

if (($route === 'lookup' || $route === 'login') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $login = trim((string)($_POST['login'] ?? ''));
    $name = trim((string)($_POST['responsible_name'] ?? ''));
    header('Location: ' . route_url('lookup', ['login' => $login, 'responsible_name' => $name]));
    exit;
}

if ($route === 'lookup/save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $groupId = (int)($_POST['group_id'] ?? 0);
    $loginValue = trim((string)($_POST['lookup_login'] ?? ''));
    $searchName = trim((string)($_POST['lookup_name'] ?? ''));

    try {
        $group = registration_service($pdo)->updateViaLookup($groupId, $_POST);
        flash('success', 'Inscrição atualizada com sucesso.');
        $redirectLogin = !empty($group['responsible_email']) && filter_var($group['responsible_email'], FILTER_VALIDATE_EMAIL) ? $group['responsible_email'] : $group['access_code'];
        header('Location: ' . route_url('lookup', ['login' => $redirectLogin, 'responsible_name' => $group['responsible_name']]));
        exit;
    } catch (RuntimeException $e) {
        flash('error', $e->getMessage());
    } catch (Throwable $e) {
        flash('error', 'Não foi possível atualizar a inscrição neste momento.');
    }

    header('Location: ' . route_url('lookup', ['login' => $loginValue, 'responsible_name' => $searchName]));
    exit;
}

if ($route === 'lookup/payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    $groupId = (int)($_POST['group_id'] ?? 0);
    $loginValue = trim((string)($_POST['lookup_login'] ?? ''));
    $searchName = trim((string)($_POST['lookup_name'] ?? ''));

    try {
        $group = registration_service($pdo)->registerLookupPayment($groupId, $_POST, $_FILES);
        flash('success', 'Pagamento registrado com sucesso.');
        $redirectLogin = !empty($group['responsible_email']) && filter_var($group['responsible_email'], FILTER_VALIDATE_EMAIL) ? $group['responsible_email'] : $group['access_code'];
        header('Location: ' . route_url('lookup', ['login' => $redirectLogin, 'responsible_name' => $group['responsible_name']]));
        exit;
    } catch (InvalidArgumentException $e) {
        flash('error', $e->getMessage());
    } catch (RuntimeException $e) {
        flash('error', 'Não foi possível validar o acesso para registrar o pagamento.');
    } catch (Throwable $e) {
        flash('error', 'Não foi possível registrar o pagamento neste momento.');
    }

    header('Location: ' . route_url('lookup', ['login' => $loginValue, 'responsible_name' => $searchName]));
    exit;
}
