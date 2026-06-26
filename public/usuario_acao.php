<?php
// /home/sistema/contas-pagar/public/usuario_acao.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/UsuarioController.php';

Auth::require();

$acao = $_POST['acao'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($acao === 'ativar') {
    UsuarioController::ativarDesativar($id, true);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Usuário ativado'];
} elseif ($acao === 'desativar') {
    $result = UsuarioController::ativarDesativar($id, false);
    if (!empty($result['erros'])) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => implode(', ', $result['erros'])];
    } else {
        $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Usuário desativado'];
    }
}

header('Location: ' . BASE_URL . '/usuarios.php');
exit;