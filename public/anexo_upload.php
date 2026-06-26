<?php
// /home/sistema/contas-pagar/public/anexo_upload.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/lib/Uploader.php';
require_once __DIR__ . '/../src/controllers/ContaController.php';

Auth::require();
Permissao::require('anexar_arquivo');

$contaId = (int)($_POST['conta_id'] ?? 0);
$conta = ContaController::obter($contaId);
if (!$conta) {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Conta não encontrada'];
    header('Location: ' . BASE_URL . '/contas.php');
    exit;
}

if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Nenhum arquivo enviado'];
    header('Location: ' . BASE_URL . '/conta_detalhe.php?id=' . $contaId);
    exit;
}

$resultado = Uploader::uploadPdf(
    $_FILES['arquivo'],
    Auth::empresaAtualId(),
    $contaId,
    Auth::user()['id']
);

if ($resultado['ok']) {
    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Anexo enviado: ' . $resultado['nome']];
} else {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => $resultado['msg']];
}

header('Location: ' . BASE_URL . '/conta_detalhe.php?id=' . $contaId);
exit;