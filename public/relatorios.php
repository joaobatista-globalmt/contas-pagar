<?php
// /home/sistema/contas-pagar/public/relatorios.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/RelatorioController.php';

Auth::require();
Permissao::require('ver_relatorios');

$tipo = $_GET['tipo'] ?? 'periodo';

// Defaults de periodo
$hoje = date('Y-m-d');
$inicioMes = date('Y-m-01');
$fimMes = date('Y-m-t');

$dataInicio = $_GET['data_inicio'] ?? $inicioMes;
$dataFim = $_GET['data_fim'] ?? $fimMes;
$status = $_GET['status'] ?? '';
$diasFluxo = (int)($_GET['dias_fluxo'] ?? 90);

$dados = null;
$titulo = '';
switch ($tipo) {
    case 'categoria':
        $titulo = 'Por Categoria';
        $dados = RelatorioController::porCategoria($dataInicio, $dataFim);
        break;
    case 'fornecedor':
        $titulo = 'Por Fornecedor';
        $dados = RelatorioController::porFornecedor($dataInicio, $dataFim);
        break;
    case 'fluxo':
        $titulo = 'Fluxo de Caixa';
        $dados = RelatorioController::fluxoCaixa($diasFluxo);
        break;
    case 'atrasadas':
        $titulo = 'Contas Atrasadas';
        $dados = RelatorioController::atrasadas();
        break;
    case 'periodo':
    default:
        $titulo = 'Por Período';
        $dados = RelatorioController::porPeriodo($dataInicio, $dataFim, $status ?: null);
        break;
}

function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function pct($parte, $total) {
    if ($total == 0) return 0;
    return round(($parte / $total) * 100, 1);
}

require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>📈 Relatórios</h1>
<p class="empresa-atual">Análise financeira por período, categoria, fornecedor e projeção</p>

<!-- Seletor de tipo + filtros -->
<form method="GET" class="filtros-card">
    <div class="filtros-linha">
        <label>Tipo de Relatório
            <select name="tipo" onchange="this.form.submit()">
                <option value="periodo" <?= $tipo === 'periodo' ? 'selected' : '' ?>>Por Período</option>
                <option value="categoria" <?= $tipo === 'categoria' ? 'selected' : '' ?>>Por Categoria</option>
                <option value="fornecedor" <?= $tipo === 'fornecedor' ? 'selected' : '' ?>>Por Fornecedor</option>
                <option value="fluxo" <?= $tipo === 'fluxo' ? 'selected' : '' ?>>Fluxo de Caixa</option>
                <option value="atrasadas" <?= $tipo === 'atrasadas' ? 'selected' : '' ?>>Atrasadas</option>
            </select>
        </label>
        <?php if (in_array($tipo, ['periodo', 'categoria', 'fornecedor'])): ?>
            <label>De
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>">
            </label>
            <label>Até
                <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>">
            </label>
        <?php endif; ?>
        <?php if ($tipo === 'periodo'): ?>
            <label>Status
                <select name="status">
                    <option value="">Todos</option>
                    <?php foreach (STATUS_CONTA as $s): ?>
                        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <?php if ($tipo === 'fluxo'): ?>
            <label>Período
                <select name="dias_fluxo">
                    <option value="30" <?= $diasFluxo === 30 ? 'selected' : '' ?>>30 dias</option>
                    <option value="60" <?= $diasFluxo === 60 ? 'selected' : '' ?>>60 dias</option>
                    <option value="90" <?= $diasFluxo === 90 ? 'selected' : '' ?>>90 dias</option>
                    <option value="180" <?= $diasFluxo === 180 ? 'selected' : '' ?>>180 dias</option>
                </select>
            </label>
        <?php endif; ?>
        <button type="submit" class="btn btn-primario">🔍 Atualizar</button>
        <a href="<?= BASE_URL ?>/relatorios.php?tipo=<?= $tipo ?>" class="btn">Limpar</a>
    </div>
</form>

<?php
// Botoes de exportacao
$exportParams = $_GET;
unset($exportParams['PHPSESSID']);
$csvUrl = BASE_URL . '/exportar.php?' . http_build_query(array_merge($exportParams, ['formato' => 'csv']));
$pdfUrl = BASE_URL . '/exportar.php?' . http_build_query(array_merge($exportParams, ['formato' => 'pdf']));
?>
<div class="botoes-exportar">
    <a href="<?= htmlspecialchars($csvUrl) ?>" class="btn btn-verde" target="_blank">📥 Baixar CSV</a>
    <a href="<?= htmlspecialchars($pdfUrl) ?>" class="btn btn-vermelho" target="_blank">📄 Baixar PDF</a>
</div>

<h2><?= htmlspecialchars($titulo) ?></h2>

<?php if ($tipo === 'periodo'): ?>
    <?php $t = $dados['totais']; ?>
    <div class="cards-grid">
        <div class="card card-azul">
            <h3>Total do Período</h3>
            <p class="card-valor"><?= brl($t['valor_total']) ?></p>
            <p class="card-info"><?= $t['qtd'] ?> contas</p>
        </div>
        <div class="card card-verde">
            <h3>Pago</h3>
            <p class="card-valor"><?= brl($t['valor_pago']) ?></p>
            <p class="card-info"><?= $t['qtd_pagas'] ?> contas pagas</p>
        </div>
        <div class="card card-amarelo">
            <h3>A Pagar</h3>
            <p class="card-valor"><?= brl($t['valor_pendente']) ?></p>
            <p class="card-info"><?= $t['qtd_pendentes'] ?> pendentes/aprovadas</p>
        </div>
        <div class="card card-vermelho">
            <h3>Atrasado</h3>
            <p class="card-valor"><?= brl($t['valor_atrasado']) ?></p>
            <p class="card-info"><?= $t['qtd_atrasadas'] ?> contas atrasadas</p>
        </div>
    </div>

    <?php if (empty($dados['contas'])): ?>
        <p class="vazio">Nenhuma conta encontrada no período.</p>
    <?php else: ?>
        <table class="tabela">
            <thead><tr><th>Vencimento</th><th>Descrição</th><th>Fornecedor</th><th>Categoria</th><th>Valor</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($dados['contas'] as $c): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                    <td><?= htmlspecialchars($c['descricao']) ?></td>
                    <td><?= htmlspecialchars($c['fornecedor_nome'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($c['categoria_nome'] ?? '-') ?></td>
                    <td class="td-valor"><?= brl($c['valor']) ?>
                        <?php if ($c['valor_pago']): ?><br><small class="muted">Pago: <?= brl($c['valor_pago']) ?></small><?php endif; ?>
                    </td>
                    <td><span class="badge" style="background:<?= ['PENDENTE'=>'#f39c12','APROVADA'=>'#3498db','PAGA'=>'#27ae60','ATRASADA'=>'#e74c3c','CANCELADA'=>'#95a5a6'][$c['status']] ?? '#7f8c8d' ?>"><?= $c['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php elseif ($tipo === 'categoria'): ?>
    <?php if (empty($dados)): ?>
        <p class="vazio">Nenhuma conta no período.</p>
    <?php else: ?>
        <?php $totalGeral = array_sum(array_column($dados, 'valor_total')); ?>
        <table class="tabela">
            <thead><tr><th>Categoria</th><th>Qtd</th><th>Pago</th><th>Pendente</th><th>Atrasado</th><th>Total</th><th>%</th><th>Distribuição</th></tr></thead>
            <tbody>
            <?php foreach ($dados as $cat): ?>
                <tr>
                    <td><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= htmlspecialchars($cat['categoria_cor']) ?>;vertical-align:middle;margin-right:6px"></span><strong><?= htmlspecialchars($cat['categoria_nome']) ?></strong></td>
                    <td><?= $cat['qtd_contas'] ?></td>
                    <td class="td-valor"><?= brl($cat['valor_pago']) ?></td>
                    <td class="td-valor"><?= brl($cat['valor_pendente']) ?></td>
                    <td class="td-valor" style="color:#e74c3c"><?= brl($cat['valor_atrasado']) ?></td>
                    <td class="td-valor"><strong><?= brl($cat['valor_total']) ?></strong></td>
                    <td><?= pct($cat['valor_total'], $totalGeral) ?>%</td>
                    <td>
                        <div class="barra-progresso">
                            <div class="barra-fill" style="width:<?= pct($cat['valor_total'], $totalGeral) ?>%;background:<?= htmlspecialchars($cat['categoria_cor']) ?>"></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="5" style="text-align:right;font-weight:bold">Total Geral:</td><td class="td-valor" style="font-weight:bold"><?= brl($totalGeral) ?></td><td colspan="2"></td></tr>
            </tfoot>
        </table>
    <?php endif; ?>

<?php elseif ($tipo === 'fornecedor'): ?>
    <?php if (empty($dados)): ?>
        <p class="vazio">Nenhuma conta no período.</p>
    <?php else: ?>
        <?php $totalGeral = array_sum(array_column($dados, 'valor_total')); ?>
        <table class="tabela">
            <thead><tr><th>Fornecedor</th><th>Qtd</th><th>Pago</th><th>Pendente</th><th>Atrasado</th><th>Total</th><th>%</th></tr></thead>
            <tbody>
            <?php foreach ($dados as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['fornecedor_nome']) ?></strong></td>
                    <td><?= $f['qtd_contas'] ?></td>
                    <td class="td-valor"><?= brl($f['valor_pago']) ?></td>
                    <td class="td-valor"><?= brl($f['valor_pendente']) ?></td>
                    <td class="td-valor" style="color:#e74c3c"><?= brl($f['valor_atrasado']) ?></td>
                    <td class="td-valor"><strong><?= brl($f['valor_total']) ?></strong></td>
                    <td><?= pct($f['valor_total'], $totalGeral) ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><td colspan="5" style="text-align:right;font-weight:bold">Total Geral:</td><td class="td-valor" style="font-weight:bold"><?= brl($totalGeral) ?></td><td></td></tr></tfoot>
        </table>
    <?php endif; ?>

<?php elseif ($tipo === 'fluxo'): ?>
    <div class="cards-grid">
        <div class="card card-azul">
            <h3>Total a Pagar (próx. <?= $dados['dias'] ?> dias)</h3>
            <p class="card-valor"><?= brl($dados['total_periodo']) ?></p>
        </div>
    </div>

    <?php if (empty($dados['porMes'])): ?>
        <p class="vazio">Sem contas previstas para os próximos <?= $dados['dias'] ?> dias.</p>
    <?php else: ?>
        <h3>📅 Visão Mensal</h3>
        <table class="tabela">
            <thead><tr><th>Mês</th><th>Qtd contas</th><th>Valor previsto</th><th>Distribuição</th></tr></thead>
            <tbody>
            <?php $maxValor = max(array_column($dados['porMes'], 'valor')); ?>
            <?php foreach ($dados['porMes'] as $m): ?>
                <tr>
                    <td><strong><?= substr($m['ano_mes'], 5, 2) ?>/<?= substr($m['ano_mes'], 0, 4) ?></strong></td>
                    <td><?= $m['qtd'] ?></td>
                    <td class="td-valor"><strong><?= brl($m['valor']) ?></strong></td>
                    <td>
                        <div class="barra-progresso">
                            <div class="barra-fill" style="width:<?= pct($m['valor'], $maxValor) ?>%;background:#3498db"></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="margin-top:24px">📊 Visão Semanal</h3>
        <table class="tabela">
            <thead><tr><th>Semana</th><th>Período</th><th>Qtd</th><th>Valor</th></tr></thead>
            <tbody>
            <?php foreach ($dados['porSemana'] as $s): ?>
                <tr>
                    <td>Sem <?= substr($s['inicio_semana'], -5) ?></td>
                    <td><?= date('d/m', strtotime($s['inicio_semana'])) ?> - <?= date('d/m', strtotime($s['fim_semana'])) ?></td>
                    <td><?= $s['qtd'] ?></td>
                    <td class="td-valor"><strong><?= brl($s['valor']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php elseif ($tipo === 'atrasadas'): ?>
    <div class="cards-grid">
        <div class="card card-vermelho">
            <h3>Total Atrasado</h3>
            <p class="card-valor"><?= brl($dados['total_atrasado']) ?></p>
            <p class="card-info"><?= $dados['qtd'] ?> contas vencidas</p>
        </div>
    </div>

    <?php if (empty($dados['contas'])): ?>
        <p class="vazio">Nenhuma conta atrasada. 🎉</p>
    <?php else: ?>
        <table class="tabela">
            <thead><tr><th>Dias</th><th>Vencimento</th><th>Descrição</th><th>Fornecedor</th><th>Contato</th><th>Categoria</th><th>Valor</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($dados['contas'] as $c): ?>
                <tr>
                    <td><span class="badge badge-vermelho">+<?= $c['dias_atraso'] ?></span></td>
                    <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                    <td><a href="<?= BASE_URL ?>/conta_detalhe.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['descricao']) ?></a></td>
                    <td><?= htmlspecialchars($c['fornecedor_nome'] ?? '-') ?></td>
                    <td>
                        <?php if ($c['fornecedor_email']): ?>📧 <?= htmlspecialchars($c['fornecedor_email']) ?><br><?php endif; ?>
                        <?php if ($c['fornecedor_telefone']): ?><small class="muted"><?= htmlspecialchars($c['fornecedor_telefone']) ?></small><?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($c['categoria_nome'] ?? '-') ?></td>
                    <td class="td-valor"><strong><?= brl($c['valor']) ?></strong></td>
                    <td><span class="badge" style="background:#e74c3c"><?= $c['status'] ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr><td colspan="6" style="text-align:right;font-weight:bold">Total:</td><td class="td-valor" style="font-weight:bold;color:#e74c3c"><?= brl($dados['total_atrasado']) ?></td><td></td></tr></tfoot>
        </table>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>