<?php
// /home/sistema/contas-pagar/src/views/layout/header.php
// Esperado: $titulo (string) opcional
$titulo = $titulo ?? APP_NAME;
$empresas = Auth::empresas();
$empresaId = Auth::empresaAtualId();
$user = Auth::user();
$empresaAtualNome = '';
foreach ($empresas as $e) {
    if ($e['empresa_id'] == $empresaId) {
        $empresaAtualNome = $e['nome_fantasia'] ?: $e['razao_social'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-left">
        <a href="<?= BASE_URL ?>/" class="logo">💰 <?= APP_NAME ?></a>
    </div>
    <div class="topbar-right">
        <form method="POST" action="<?= BASE_URL ?>/trocar-empresa.php" class="empresa-selector">
            <label>Empresa:</label>
            <select name="empresa_id" onchange="this.form.submit()">
                <?php foreach ($empresas as $e): ?>
                    <option value="<?= $e['empresa_id'] ?>" <?= $e['empresa_id'] == $empresaId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['nome_fantasia'] ?: $e['razao_social']) ?>
                        (<?= $e['perfil_na_empresa'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <span class="user-info">👤 <?= htmlspecialchars($user['nome']) ?></span>
        <a href="<?= BASE_URL ?>/logout.php" class="btn-sair">Sair</a>
    </div>
</header>

<aside class="sidebar">
    <nav>
        <a href="<?= BASE_URL ?>/" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="<?= BASE_URL ?>/contas.php" class="<?= basename($_SERVER['PHP_SELF']) === 'contas.php' ? 'active' : '' ?>">📋 Contas a Pagar</a>
        <a href="<?= BASE_URL ?>/fornecedores.php" class="<?= basename($_SERVER['PHP_SELF']) === 'fornecedores.php' ? 'active' : '' ?>">🏢 Fornecedores</a>
        <a href="<?= BASE_URL ?>/categorias.php" class="<?= basename($_SERVER['PHP_SELF']) === 'categorias.php' ? 'active' : '' ?>">🏷️ Categorias</a>
        <a href="<?= BASE_URL ?>/relatorios.php" class="<?= basename($_SERVER['PHP_SELF']) === 'relatorios.php' ? 'active' : '' ?>">📈 Relatórios</a>
        <a href="<?= BASE_URL ?>/recorrencia.php" class="<?= basename($_SERVER['PHP_SELF']) === 'recorrencia.php' ? 'active' : '' ?>">🔁 Recorrências</a>
        <?php if (Permissao::pode('gerenciar_usuarios')): ?>
            <hr>
            <a href="<?= BASE_URL ?>/usuarios.php" class="<?= basename($_SERVER['PHP_SELF']) === 'usuarios.php' ? 'active' : '' ?>">👥 Usuários</a>
            <a href="<?= BASE_URL ?>/empresas.php" class="<?= basename($_SERVER['PHP_SELF']) === 'empresas.php' ? 'active' : '' ?>">🏭 Empresas</a>
        <?php endif; ?>
    </nav>
</aside>

<main class="content">