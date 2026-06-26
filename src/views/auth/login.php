<?php
// /home/sistema/contas-pagar/src/views/auth/login.php

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$erro = $_SESSION['erro_login'] ?? null;
unset($_SESSION['erro_login']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-body">
    <div class="login-card">
        <h1>💰 <?= APP_NAME ?></h1>
        <p class="subtitle">Sistema de Gestão de Contas a Pagar</p>

        <?php if ($erro): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/login.php">
            <label>E-mail
                <input type="email" name="email" required autofocus placeholder="seu@email.com">
            </label>
            <label>Senha
                <input type="password" name="senha" required placeholder="••••••••">
            </label>
            <button type="submit" class="btn btn-primario btn-bloco">Entrar</button>
        </form>

        <p class="login-hint">
            <small>Versão <?= APP_VERSION ?></small>
        </p>
    </div>
</body>
</html>