<?php
// /home/sistema/contas-pagar/public/recorrencia_acao.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/RecorrenciaController.php';

Auth::require();

$acao = $_POST['acao'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($acao === 'ativar') {
    RecorrenciaController::ativarDesativar($id, true);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Recorrência ativada'];
} elseif ($acao === 'desativar') {
    RecorrenciaController::ativarDesativar($id, false);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Recorrência desativada'];
}

header('Location: ' . BASE_URL . '/recorrencia.php');
exit;