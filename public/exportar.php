<?php
// /home/sistema/contas-pagar/public/exportar.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/lib/CsvExporter.php';
require_once __DIR__ . '/../src/controllers/RelatorioController.php';

Auth::require();
Permissao::require('ver_relatorios');

$tipo = $_GET['tipo'] ?? 'periodo';
$formato = $_GET['formato'] ?? 'csv';

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-t');
$status = $_GET['status'] ?? '';
$diasFluxo = (int)($_GET['dias_fluxo'] ?? 90);

// Helpers
function brlForCsv($v) { return number_format((float)$v, 2, ',', '.'); }
function dataForCsv($v) { return $v ? date('d/m/Y', strtotime($v)) : ''; }

if ($formato === 'csv') {
    switch ($tipo) {
        case 'periodo':
            $dados = RelatorioController::porPeriodo($dataInicio, $dataFim, $status ?: null);
            CsvExporter::gerar(
                $dados['contas'],
                [
                    ['key' => 'data_vencimento', 'label' => 'Vencimento', 'format' => fn($v) => dataForCsv($v)],
                    ['key' => 'descricao', 'label' => 'Descrição'],
                    ['key' => 'numero_documento', 'label' => 'Documento'],
                    ['key' => 'fornecedor_nome', 'label' => 'Fornecedor'],
                    ['key' => 'categoria_nome', 'label' => 'Categoria'],
                    ['key' => 'valor', 'label' => 'Valor', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'valor_pago', 'label' => 'Valor Pago', 'format' => fn($v) => $v ? brlForCsv($v) : ''],
                    ['key' => 'data_pagamento', 'label' => 'Data Pagamento', 'format' => fn($v) => dataForCsv($v)],
                    ['key' => 'forma_pagamento', 'label' => 'Forma Pagamento'],
                    ['key' => 'status', 'label' => 'Status'],
                ],
                "contas_periodo_{$dataInicio}_a_{$dataFim}"
            );
            break;

        case 'categoria':
            $dados = RelatorioController::porCategoria($dataInicio, $dataFim);
            CsvExporter::gerar(
                $dados,
                [
                    ['key' => 'categoria_nome', 'label' => 'Categoria'],
                    ['key' => 'qtd_contas', 'label' => 'Qtd Contas'],
                    ['key' => 'valor_pago', 'label' => 'Pago', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'valor_pendente', 'label' => 'Pendente', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'valor_atrasado', 'label' => 'Atrasado', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'valor_total', 'label' => 'Total', 'format' => fn($v) => brlForCsv($v)],
                ],
                "relatorio_categoria_{$dataInicio}_a_{$dataFim}"
            );
            break;

        case 'fornecedor':
            $dados = RelatorioController::porFornecedor($dataInicio, $dataFim);
            CsvExporter::gerar(
                $dados,
                [
                    ['key' => 'fornecedor_nome', 'label' => 'Fornecedor'],
                    ['key' => 'qtd_contas', 'label' => 'Qtd Contas'],
                    ['key' => 'valor_pago', 'label' => 'Pago', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'valor_pendente', 'label' => 'Pendente', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'valor_atrasado', 'label' => 'Atrasado', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'valor_total', 'label' => 'Total', 'format' => fn($v) => brlForCsv($v)],
                ],
                "relatorio_fornecedor_{$dataInicio}_a_{$dataFim}"
            );
            break;

        case 'fluxo':
            $dados = RelatorioController::fluxoCaixa($diasFluxo);
            CsvExporter::gerar(
                $dados['porDia'],
                [
                    ['key' => 'data_vencimento', 'label' => 'Data', 'format' => fn($v) => dataForCsv($v)],
                    ['key' => 'qtd', 'label' => 'Qtd Contas'],
                    ['key' => 'valor', 'label' => 'Valor Previsto', 'format' => fn($v) => brlForCsv($v)],
                ],
                "fluxo_caixa_{$diasFluxo}dias"
            );
            break;

        case 'atrasadas':
            $dados = RelatorioController::atrasadas();
            CsvExporter::gerar(
                $dados['contas'],
                [
                    ['key' => 'dias_atraso', 'label' => 'Dias Atraso'],
                    ['key' => 'data_vencimento', 'label' => 'Vencimento', 'format' => fn($v) => dataForCsv($v)],
                    ['key' => 'descricao', 'label' => 'Descrição'],
                    ['key' => 'fornecedor_nome', 'label' => 'Fornecedor'],
                    ['key' => 'fornecedor_email', 'label' => 'Email'],
                    ['key' => 'fornecedor_telefone', 'label' => 'Telefone'],
                    ['key' => 'categoria_nome', 'label' => 'Categoria'],
                    ['key' => 'valor', 'label' => 'Valor', 'format' => fn($v) => brlForCsv($v)],
                    ['key' => 'status', 'label' => 'Status'],
                ],
                "contas_atrasadas_" . date('Y-m-d')
            );
            break;
    }
} elseif ($formato === 'pdf') {
    // PDF via wkhtmltopdf: gera HTML intermediario
    $html = gerarHtmlRelatorio($tipo, $dataInicio, $dataFim, $status, $diasFluxo);

    $tmpHtml = tempnam(sys_get_temp_dir(), 'rel_') . '.html';
    $tmpPdf = tempnam(sys_get_temp_dir(), 'rel_') . '.pdf';
    file_put_contents($tmpHtml, $html);

    $cmd = sprintf(
        'wkhtmltopdf --quiet --encoding UTF-8 --margin-top 10mm --margin-bottom 10mm --margin-left 10mm --margin-right 10mm %s %s 2>&1',
        escapeshellarg($tmpHtml),
        escapeshellarg($tmpPdf)
    );
    shell_exec($cmd);

    if (file_exists($tmpPdf)) {
        $nomeArquivo = "relatorio_{$tipo}_" . date('Y-m-d') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '"');
        header('Content-Length: ' . filesize($tmpPdf));
        readfile($tmpPdf);
        unlink($tmpHtml);
        unlink($tmpPdf);
        exit;
    } else {
        die('Erro ao gerar PDF. Comando: ' . $cmd);
    }
}

/**
 * Gera HTML simples pra wkhtmltopdf converter em PDF
 */
function gerarHtmlRelatorio(string $tipo, string $dataInicio, string $dataFim, string $status, int $diasFluxo): string {
    $empresaId = Auth::empresaAtualId();
    $empresaNome = '';
    foreach (Auth::empresas() as $e) {
        if ($e['empresa_id'] == $empresaId) { $empresaNome = $e['nome_fantasia'] ?: $e['razao_social']; break; }
    }

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #333; }
        h1 { color: #2c3e50; border-bottom: 2px solid #2980b9; padding-bottom: 8px; }
        h2 { color: #34495e; margin-top: 20px; }
        .info { color: #7f8c8d; font-size: 10pt; margin-bottom: 20px; }
        .cards { display: flex; gap: 10px; margin-bottom: 20px; }
        .card { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 4px; }
        .card h3 { margin: 0 0 6px; font-size: 9pt; color: #7f8c8d; text-transform: uppercase; }
        .card .valor { font-size: 16pt; font-weight: bold; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
        th { background: #ecf0f1; padding: 8px; text-align: left; border-bottom: 2px solid #bdc3c7; }
        td { padding: 8px; border-bottom: 1px solid #ecf0f1; }
        .valor-col { text-align: right; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 9pt; color: #95a5a6; text-align: center; }
    </style></head><body>';

    $html .= '<h1>Contas a Pagar — Relatório</h1>';
    $html .= '<div class="info">';
    $html .= '<strong>Empresa:</strong> ' . htmlspecialchars($empresaNome) . '<br>';
    $html .= '<strong>Gerado em:</strong> ' . date('d/m/Y H:i:s') . '<br>';
    $html .= '<strong>Usuário:</strong> ' . htmlspecialchars(Auth::user()['nome']);
    $html .= '</div>';

    function brlP($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }

    switch ($tipo) {
        case 'periodo':
            $d = RelatorioController::porPeriodo($dataInicio, $dataFim, $status ?: null);
            $t = $d['totais'];
            $html .= '<h2>Período: ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . '</h2>';
            $html .= '<div class="cards">';
            $html .= '<div class="card"><h3>Total</h3><div class="valor">' . brlP($t['valor_total']) . '</div><small>' . $t['qtd'] . ' contas</small></div>';
            $html .= '<div class="card"><h3>Pago</h3><div class="valor" style="color:#27ae60">' . brlP($t['valor_pago']) . '</div></div>';
            $html .= '<div class="card"><h3>A Pagar</h3><div class="valor" style="color:#f39c12">' . brlP($t['valor_pendente']) . '</div></div>';
            $html .= '<div class="card"><h3>Atrasado</h3><div class="valor" style="color:#e74c3c">' . brlP($t['valor_atrasado']) . '</div></div>';
            $html .= '</div>';
            $html .= '<table><thead><tr><th>Vencimento</th><th>Descrição</th><th>Fornecedor</th><th>Categoria</th><th>Valor</th><th>Status</th></tr></thead><tbody>';
            foreach ($d['contas'] as $c) {
                $html .= '<tr>';
                $html .= '<td>' . date('d/m/Y', strtotime($c['data_vencimento'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($c['descricao']) . '</td>';
                $html .= '<td>' . htmlspecialchars($c['fornecedor_nome'] ?? '-') . '</td>';
                $html .= '<td>' . htmlspecialchars($c['categoria_nome'] ?? '-') . '</td>';
                $html .= '<td class="valor-col">' . brlP($c['valor']) . '</td>';
                $html .= '<td>' . $c['status'] . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
            break;

        case 'categoria':
            $d = RelatorioController::porCategoria($dataInicio, $dataFim);
            $total = array_sum(array_column($d, 'valor_total'));
            $html .= '<h2>Gastos por Categoria — ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . '</h2>';
            $html .= '<table><thead><tr><th>Categoria</th><th>Qtd</th><th>Pago</th><th>Pendente</th><th>Atrasado</th><th>Total</th><th>%</th></tr></thead><tbody>';
            foreach ($d as $r) {
                $pct = $total > 0 ? round(($r['valor_total'] / $total) * 100, 1) : 0;
                $html .= '<tr><td>' . htmlspecialchars($r['categoria_nome']) . '</td><td>' . $r['qtd_contas'] . '</td><td class="valor-col">' . brlP($r['valor_pago']) . '</td><td class="valor-col">' . brlP($r['valor_pendente']) . '</td><td class="valor-col">' . brlP($r['valor_atrasado']) . '</td><td class="valor-col">' . brlP($r['valor_total']) . '</td><td>' . $pct . '%</td></tr>';
            }
            $html .= '<tr><td colspan="5" style="text-align:right"><strong>Total Geral:</strong></td><td class="valor-col">' . brlP($total) . '</td><td></td></tr>';
            $html .= '</tbody></table>';
            break;

        case 'fornecedor':
            $d = RelatorioController::porFornecedor($dataInicio, $dataFim);
            $total = array_sum(array_column($d, 'valor_total'));
            $html .= '<h2>Gastos por Fornecedor — ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . '</h2>';
            $html .= '<table><thead><tr><th>Fornecedor</th><th>Qtd</th><th>Pago</th><th>Pendente</th><th>Atrasado</th><th>Total</th><th>%</th></tr></thead><tbody>';
            foreach ($d as $r) {
                $pct = $total > 0 ? round(($r['valor_total'] / $total) * 100, 1) : 0;
                $html .= '<tr><td>' . htmlspecialchars($r['fornecedor_nome']) . '</td><td>' . $r['qtd_contas'] . '</td><td class="valor-col">' . brlP($r['valor_pago']) . '</td><td class="valor-col">' . brlP($r['valor_pendente']) . '</td><td class="valor-col">' . brlP($r['valor_atrasado']) . '</td><td class="valor-col">' . brlP($r['valor_total']) . '</td><td>' . $pct . '%</td></tr>';
            }
            $html .= '<tr><td colspan="5" style="text-align:right"><strong>Total Geral:</strong></td><td class="valor-col">' . brlP($total) . '</td><td></td></tr>';
            $html .= '</tbody></table>';
            break;

        case 'fluxo':
            $d = RelatorioController::fluxoCaixa($diasFluxo);
            $html .= '<h2>Fluxo de Caixa — Próximos ' . $diasFluxo . ' dias</h2>';
            $html .= '<p><strong>Total previsto:</strong> ' . brlP($d['total_periodo']) . '</p>';
            $html .= '<h3>Visão Mensal</h3>';
            $html .= '<table><thead><tr><th>Mês</th><th>Qtd</th><th>Valor</th></tr></thead><tbody>';
            foreach ($d['porMes'] as $m) {
                $html .= '<tr><td>' . substr($m['ano_mes'], 5, 2) . '/' . substr($m['ano_mes'], 0, 4) . '</td><td>' . $m['qtd'] . '</td><td class="valor-col">' . brlP($m['valor']) . '</td></tr>';
            }
            $html .= '</tbody></table>';
            break;

        case 'atrasadas':
            $d = RelatorioController::atrasadas();
            $html .= '<h2>Contas Atrasadas</h2>';
            $html .= '<p><strong>Total:</strong> ' . brlP($d['total_atrasado']) . ' em ' . $d['qtd'] . ' contas</p>';
            $html .= '<table><thead><tr><th>Dias</th><th>Vencimento</th><th>Descrição</th><th>Fornecedor</th><th>Contato</th><th>Valor</th></tr></thead><tbody>';
            foreach ($d['contas'] as $c) {
                $contato = trim(($c['fornecedor_email'] ?? '') . ' ' . ($c['fornecedor_telefone'] ?? ''));
                $html .= '<tr><td>+' . $c['dias_atraso'] . '</td><td>' . date('d/m/Y', strtotime($c['data_vencimento'])) . '</td><td>' . htmlspecialchars($c['descricao']) . '</td><td>' . htmlspecialchars($c['fornecedor_nome'] ?? '-') . '</td><td>' . htmlspecialchars($contato) . '</td><td class="valor-col">' . brlP($c['valor']) . '</td></tr>';
            }
            $html .= '</tbody></table>';
            break;
    }

    $html .= '<div class="footer">Gerado por Contas a Pagar v' . APP_VERSION . ' em ' . date('d/m/Y H:i:s') . '</div>';
    $html .= '</body></html>';
    return $html;
}