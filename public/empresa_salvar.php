<?php
// /home/sistema/contas-pagar/public/empresa_salvar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/EmpresaController.php';

Auth::require();

$id = (int)($_POST['id'] ?? 0);
$dados = $_POST;

if ($id > 0) {
    $resultado = EmpresaController::atualizar($id, $dados);
} else {
    $resultado = EmpresaController::criar($dados);
}

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Empresa salva com sucesso'];
    header('Location: ' . BASE_URL . '/empresas.php');
    exit;
} else {
    $_SESSION['erros_form'] = $resultado['erros'];
    $voltar = $id ? "empresa_form.php?id=$id" : "empresa_form.php";
    header('Location: ' . BASE_URL . '/' . $voltar);
    exit;
}