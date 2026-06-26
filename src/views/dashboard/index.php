<?php
// /home/sistema/contas-pagar/src/views/dashboard/index.php

require_once __DIR__ . '/../layout/header.php';
$dados = DashboardController::dados(Auth::empresaAtualId());
$cards = $dados['cards'];

function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function statusBadge($s) {
    $cores = [
        'PENDENTE' => '#f39c12',
        'APROVADA' => '#3498db',
        'PAGA'     => '#27ae60',
        'ATRASADA' => '#e74c3c',
        'CANCELADA'=> '#95a5a6',
    ];
    $cor = $cores[$s] ?? '#7f8c8d';
    return "<span class='badge' style='background:$cor'>$s</span>";
}
?>

<h1>📊 Dashboard</h1>
<p class="empresa-atual"><?= htmlspecialchars($empresaAtualNome) ?></p>

<div class="cards-grid">
    <div class="card card-azul">
        <h3>Vencem hoje</h3>
        <p class="card-valor"><?= brl($cards['total_hoje'] ?? 0) ?></p>
        <p class="card-info"><?= $cards['qtd_pendentes'] ?? 0 ?> pendentes no total</p>
    </div>
    <div class="card card-amarelo">
        <h3>Próximos 7 dias</h3>
        <p class="card-valor"><?= brl($cards['total_semana'] ?? 0) ?></p>
        <p class="card-info"><?= count($dados['proximas']) ?> contas a vencer</p>
    </div>
    <div class="card card-verde">
        <h3>Total no mês</h3>
        <p class="card-valor"><?= brl($cards['total_mes'] ?? 0) ?></p>
        <p class="card-info"><?= $cards['qtd_aprovadas'] ?? 0 ?> aguardando pagamento</p>
    </div>
    <div class="card card-vermelho">
        <h3>Atrasadas</h3>
        <p class="card-valor"><?= brl($cards['total_atrasadas'] ?? 0) ?></p>
        <p class="card-info"><?= $cards['qtd_atrasadas'] ?? 0 ?> contas vencidas</p>
    </div>
</div>

<div class="grid-2col">
    <section>
        <h2>⚠️ Contas Atrasadas</h2>
        <?php if (empty($dados['atrasadas'])): ?>
            <p class="vazio">Nenhuma conta atrasada. 🎉</p>
        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr><th>Descrição</th><th>Fornecedor</th><th>Vencimento</th><th>Atraso</th><th>Valor</th></tr>
                </thead>
                <tbody>
                <?php foreach ($dados['atrasadas'] as $c): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/conta_detalhe.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['descricao']) ?></a></td>
                        <td><?= htmlspecialchars($c['fornecedor'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                        <td><span class="badge badge-vermelho"><?= $c['dias_atraso'] ?> dia(s)</span></td>
                        <td class="td-valor"><?= brl($c['valor']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section>
        <h2>📅 Próximos Vencimentos</h2>
        <?php if (empty($dados['proximas'])): ?>
            <p class="vazio">Nenhuma conta a vencer nos próximos 7 dias.</p>
        <?php else: ?>
            <table class="tabela">
                <thead>
                    <tr><th>Descrição</th><th>Fornecedor</th><th>Vencimento</th><th>Status</th><th>Valor</th></tr>
                </thead>
                <tbody>
                <?php foreach ($dados['proximas'] as $c): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/conta_detalhe.php?id=<?= $c['id'] ?>"><?= htmlspecialchars($c['descricao']) ?></a></td>
                        <td><?= htmlspecialchars($c['fornecedor'] ?? '-') ?></td>
                        <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                        <td><?= statusBadge($c['status']) ?></td>
                        <td class="td-valor"><?= brl($c['valor']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>