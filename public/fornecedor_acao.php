<?php
// /home/sistema/contas-pagar/public/fornecedor_acao.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/FornecedorController.php';

Auth::require();

$acao = $_POST['acao'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($acao === 'ativar') {
    FornecedorController::ativarDesativar($id, true);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Fornecedor ativado'];
} elseif ($acao === 'desativar') {
    FornecedorController::ativarDesativar($id, false);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Fornecedor desativado'];
}

header('Location: ' . BASE_URL . '/fornecedores.php');
exit;