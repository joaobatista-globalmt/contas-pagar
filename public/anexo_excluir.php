<?php
// /home/sistema/contas-pagar/public/anexo_excluir.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/lib/Uploader.php';
require_once __DIR__ . '/../src/controllers/ContaController.php';

Auth::require();
Permissao::require('anexar_arquivo');

$anexoId = (int)($_POST['id'] ?? 0);
$contaId = (int)($_POST['conta_id'] ?? 0);

// Confirma que conta existe na empresa
$conta = ContaController::obter($contaId);
if (!$conta) {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Conta não encontrada'];
    header('Location: ' . BASE_URL . '/contas.php');
    exit;
}

$resultado = Uploader::excluir($anexoId, Auth::empresaAtualId(), Auth::user()['id'], Auth::perfilNaEmpresaAtual());

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Anexo excluído'];
} else {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => $resultado['msg'] ?? 'Erro ao excluir'];
}

header('Location: ' . BASE_URL . '/conta_detalhe.php?id=' . $contaId);
exit;