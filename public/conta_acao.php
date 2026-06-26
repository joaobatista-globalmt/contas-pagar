<?php
// /home/sistema/contas-pagar/public/conta_acao.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/ContaController.php';

Auth::require();

$acao = $_POST['acao'] ?? '';
$id = (int)($_POST['id'] ?? 0);

$resultado = null;
switch ($acao) {
    case 'aprovar':
        $resultado = ContaController::aprovar($id);
        break;
    case 'pagar':
        $resultado = ContaController::pagar($id, $_POST);
        break;
    case 'cancelar':
        $resultado = ContaController::cancelar($id);
        break;
    case 'excluir':
        $resultado = ContaController::excluir($id);
        break;
    default:
        $resultado = ['ok' => false, 'erros' => ['Ação inválida']];
}

if ($resultado['ok']) {
    $msgs = [
        'aprovar' => 'Conta aprovada',
        'pagar' => 'Pagamento registrado',
        'cancelar' => 'Conta cancelada',
        'excluir' => 'Conta excluída',
    ];
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => $msgs[$acao] ?? 'OK'];
} else {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => implode(', ', $resultado['erros'])];
}

header('Location: ' . BASE_URL . '/conta_detalhe.php?id=' . $id);
exit;