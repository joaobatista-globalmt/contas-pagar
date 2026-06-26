<?php
// /home/sistema/contas-pagar/public/recorrencia_gerar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/RecorrenciaController.php';

Auth::require();

$mes = $_POST['mes'] ?? date('Y-m');
$resultado = RecorrenciaController::gerarMes($mes);

if ($resultado['ok']) {
    $msg = "✅ Geração do mês {$resultado['mes']} concluída:\n";
    $msg .= "• {$resultado['geradas']} conta(s) gerada(s)\n";
    $msg .= "• {$resultado['puladas']} pulada(s)\n";

    if (!empty($resultado['detalhes'])) {
        $msg .= "\nDetalhes:\n";
        foreach (array_slice($resultado['detalhes'], 0, 20) as $d) {
            $status = $d['acao'] === 'gerada' ? "✓ gerada (R$ {$d['valor']}, vence {$d['vencimento']})" : "→ pulada ({$d['motivo']})";
            $msg .= "  • {$d['descricao']}: {$status}\n";
        }
        if (count($resultado['detalhes']) > 20) {
            $msg .= "  ... e mais " . (count($resultado['detalhes']) - 20) . "\n";
        }
    }

    $_SESSION['flash'] = ['tipo' => 'success', 'msg' => $msg];
} else {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => $resultado['msg'] ?? 'Erro'];
}

header('Location: ' . BASE_URL . '/recorrencia.php');
exit;