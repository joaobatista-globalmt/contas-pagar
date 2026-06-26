<?php
// /home/sistema/contas-pagar/public/usuario_salvar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/UsuarioController.php';

Auth::require();
Permissao::require('gerenciar_usuarios');

$id = (int)($_POST['id'] ?? 0);
$dados = $_POST;

// Filtra vinculos: so envia os que tiverem ativo=1
$vinculosFiltrados = [];
if (!empty($dados['vinculos']) && is_array($dados['vinculos'])) {
    foreach ($dados['vinculos'] as $empId => $v) {
        if (!empty($v['ativo'])) {
            $vinculosFiltrados[] = [
                'empresa_id' => (int)$empId,
                'perfil_na_empresa' => $v['perfil_na_empresa'] ?? 'operador',
            ];
        }
    }
}
$dados['vinculos'] = $vinculosFiltrados;

if ($id > 0) {
    $resultado = UsuarioController::atualizar($id, $dados);
    // Resetar senha se informada
    if ($resultado['ok'] && !empty($_POST['senha'])) {
        $resultado = UsuarioController::resetarSenha($id, $_POST['senha']);
    }
} else {
    $resultado = UsuarioController::criar($dados);
}

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Usuário salvo com sucesso'];
    header('Location: ' . BASE_URL . '/usuarios.php');
    exit;
} else {
    $_SESSION['erros_form'] = $resultado['erros'];
    $voltar = $id ? "usuario_form.php?id=$id" : "usuario_form.php";
    header('Location: ' . BASE_URL . '/' . $voltar);
    exit;
}