<?php
// /home/sistema/contas-pagar/public/recorrencia_salvar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/RecorrenciaController.php';

Auth::require();

$id = (int)($_POST['id'] ?? 0);
$dados = $_POST;

if ($id > 0) {
    $resultado = RecorrenciaController::atualizar($id, $dados);
} else {
    $resultado = RecorrenciaController::criar($dados);
}

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Recorrência salva com sucesso'];
} else {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => implode(', ', $resultado['erros'])];
}

header('Location: ' . BASE_URL . '/recorrencia.php');
exit;