<?php
// /home/sistema/contas-pagar/public/fornecedor_salvar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/FornecedorController.php';

Auth::require();

$id = (int)($_POST['id'] ?? 0);
$dados = $_POST;

if ($id > 0) {
    $resultado = FornecedorController::atualizar($id, $dados);
} else {
    $resultado = FornecedorController::criar($dados);
}

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Fornecedor salvo com sucesso'];
    header('Location: ' . BASE_URL . '/fornecedores.php');
    exit;
} else {
    $_SESSION['erros_form'] = $resultado['erros'];
    $voltar = $id ? "fornecedor_form.php?id=$id" : "fornecedor_form.php";
    header('Location: ' . BASE_URL . '/' . $voltar);
    exit;
}