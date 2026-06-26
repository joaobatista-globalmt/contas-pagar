<?php
// /home/sistema/contas-pagar/public/categoria_salvar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/CategoriaController.php';

Auth::require();

$id = (int)($_POST['id'] ?? 0);
$dados = $_POST;

if ($id > 0) {
    $resultado = CategoriaController::atualizar($id, $dados);
} else {
    $resultado = CategoriaController::criar($dados);
}

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Categoria salva com sucesso'];
    header('Location: ' . BASE_URL . '/categorias.php');
    exit;
} else {
    $_SESSION['erros_form'] = $resultado['erros'];
    $voltar = $id ? "categoria_form.php?id=$id" : "categoria_form.php";
    header('Location: ' . BASE_URL . '/' . $voltar);
    exit;
}