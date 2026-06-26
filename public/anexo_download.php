<?php
// /home/sistema/contas-pagar/public/anexo_download.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Uploader.php';

Auth::require();

$anexoId = (int)($_GET['id'] ?? 0);
if (!$anexoId) {
    http_response_code(400);
    die('ID inválido');
}

$pdo = db();
$stmt = $pdo->prepare('
    SELECT a.* FROM anexos a
    INNER JOIN contas_pagar cp ON cp.id = a.conta_id
    WHERE a.id = ? AND cp.empresa_id = ?
');
$stmt->execute([$anexoId, Auth::empresaAtualId()]);
$anexo = $stmt->fetch();

if (!$anexo) {
    http_response_code(404);
    die('Anexo não encontrado');
}

$caminhoCompleto = UPLOAD_PATH . '/' . $anexo['caminho_arquivo'];
if (!file_exists($caminhoCompleto)) {
    http_response_code(404);
    die('Arquivo físico não encontrado');
}

// Headers pra download
header('Content-Type: ' . ($anexo['tipo_mime'] ?? 'application/pdf'));
header('Content-Length: ' . filesize($caminhoCompleto));
header('Content-Disposition: inline; filename="' . basename($anexo['nome_arquivo']) . '"');
header('Cache-Control: private, max-age=3600');

readfile($caminhoCompleto);
exit;