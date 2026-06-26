<?php
// /home/sistema/contas-pagar/public/contas.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/ContaController.php';
require_once __DIR__ . '/../src/controllers/FornecedorController.php';
require_once __DIR__ . '/../src/controllers/CategoriaController.php';

Auth::require();

$filtros = [
    'status' => $_GET['status'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'fornecedor_id' => $_GET['fornecedor_id'] ?? '',
    'categoria_id' => $_GET['categoria_id'] ?? '',
    'busca' => $_GET['busca'] ?? '',
];

$contas = ContaController::listar(array_filter($filtros));
$fornecedores = FornecedorController::listarAtivos();
$categorias = CategoriaController::listarAtivas();

$titulo = 'Contas a Pagar';
require_once __DIR__ . '/../src/views/layout/header.php';

function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function statusBadge($s) {
    $cores = [
        'PENDENTE' => '#f39c12',
        'APROVADA' => '#3498db',
        'PAGA' => '#27ae60',
        'ATRASADA' => '#e74c3c',
        'CANCELADA' => '#95a5a6',
    ];
    $cor = $cores[$s] ?? '#7f8c8d';
    return "<span class='badge' style='background:$cor'>$s</span>";
}
?>

<h1>📋 Contas a Pagar</h1>
<p class="empresa-atual"><?= count($contas) ?> conta(s) encontrada(s)</p>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Filtros -->
<form method="GET" class="filtros-card">
    <div class="filtros-linha">
        <label>Status
            <select name="status">
                <option value="">Todos</option>
                <?php foreach (STATUS_CONTA as $s): ?>
                    <option value="<?= $s ?>" <?= $filtros['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Vencimento de
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio']) ?>">
        </label>
        <label>até
            <input type="date" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim']) ?>">
        </label>
        <label>Fornecedor
            <select name="fornecedor_id">
                <option value="">Todos</option>
                <?php foreach ($fornecedores as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= $filtros['fornecedor_id'] == $f['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Categoria
            <select name="categoria_id">
                <option value="">Todas</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filtros['categoria_id'] == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="filtros-linha">
        <label style="flex:1">Buscar
            <input type="search" name="busca" value="<?= htmlspecialchars($filtros['busca']) ?>" placeholder="Descrição ou número do documento...">
        </label>
        <div style="display:flex; gap:8px; align-items:flex-end">
            <button type="submit" class="btn btn-primario">🔍 Filtrar</button>
            <a href="<?= BASE_URL ?>/contas.php" class="btn">Limpar</a>
        </div>
    </div>
</form>

<div class="barra-acoes">
    <div>
        <?php if (Permissao::pode('criar_conta')): ?>
            <a href="<?= BASE_URL ?>/conta_form.php" class="btn btn-primario">+ Nova Conta</a>
        <?php endif; ?>
    </div>
    <div>
        <small class="muted"><?= count($contas) ?> resultado(s)</small>
    </div>
</div>

<?php if (empty($contas)): ?>
    <p class="vazio">Nenhuma conta encontrada com os filtros aplicados.</p>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>Vencimento</th>
                <th>Descrição</th>
                <th>Fornecedor</th>
                <th>Categoria</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($contas as $c): ?>
            <tr>
                <td>
                    <?= date('d/m/Y', strtotime($c['data_vencimento'])) ?>
                    <?php if ($c['status'] !== 'PAGA' && $c['status'] !== 'CANCELADA' && $c['dias_vencidos'] > 0): ?>
                        <br><span class="badge badge-vermelho">+<?= $c['dias_vencidos'] ?> dia(s)</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= BASE_URL ?>/conta_detalhe.php?id=<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['descricao']) ?>
                    </a>
                    <?php if ($c['numero_documento']): ?>
                        <br><small class="muted">Doc: <?= htmlspecialchars($c['numero_documento']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['fornecedor_nome'] ?? '-') ?></td>
                <td>
                    <?php if ($c['categoria_nome']): ?>
                        <span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:<?= htmlspecialchars($c['categoria_cor']) ?>;vertical-align:middle;margin-right:4px"></span>
                        <?= htmlspecialchars($c['categoria_nome']) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="td-valor"><?= brl($c['valor']) ?></td>
                <td><?= statusBadge($c['status']) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/conta_detalhe.php?id=<?= $c['id'] ?>" class="btn btn-pequeno">Ver</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:bold">Total:</td>
                <td class="td-valor" style="font-weight:bold"><?= brl(array_sum(array_column($contas, 'valor'))) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>