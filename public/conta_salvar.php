<?php
// /home/sistema/contas-pagar/public/conta_salvar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/ContaController.php';

Auth::require();

$id = (int)($_POST['id'] ?? 0);
$dados = $_POST;

if ($id > 0) {
    $resultado = ContaController::atualizar($id, $dados);
} else {
    $resultado = ContaController::criar($dados);
}

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Conta salva com sucesso'];
    header('Location: ' . BASE_URL . '/conta_detalhe.php?id=' . ($resultado['id'] ?? $id));
    exit;
} else {
    $_SESSION['erros_form'] = $resultado['erros'];
    $voltar = $id ? "conta_form.php?id=$id" : "conta_form.php";
    header('Location: ' . BASE_URL . '/' . $voltar);
    exit;
}