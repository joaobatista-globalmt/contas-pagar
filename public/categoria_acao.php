<?php
// /home/sistema/contas-pagar/public/categoria_acao.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/CategoriaController.php';

Auth::require();

$acao = $_POST['acao'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($acao === 'ativar') {
    CategoriaController::ativarDesativar($id, true);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Categoria ativada'];
} elseif ($acao === 'desativar') {
    CategoriaController::ativarDesativar($id, false);
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Categoria desativada'];
}

header('Location: ' . BASE_URL . '/categorias.php');
exit;