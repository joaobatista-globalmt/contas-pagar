<?php
// /home/sistema/contas-pagar/public/empresa_acao.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/EmpresaController.php';

Auth::require();

$acao = $_POST['acao'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($acao === 'ativar') {
    EmpresaController::ativarDesativar($id, true);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Empresa ativada'];
} elseif ($acao === 'desativar') {
    $result = EmpresaController::ativarDesativar($id, false);
    if (!empty($result['erros'])) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => implode(', ', $result['erros'])];
    } else {
        $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Empresa desativada'];
    }
}

header('Location: ' . BASE_URL . '/empresas.php');
exit;