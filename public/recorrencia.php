<?php
// /home/sistema/contas-pagar/public/recorrencia.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/RecorrenciaController.php';

Auth::require();

$recorrencias = RecorrenciaController::listar();
$titulo = 'Recorrências';
require_once __DIR__ . '/../src/views/layout/header.php';

function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function brl_input($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
?>

<h1>🔁 Recorrências</h1>
<p class="empresa-atual">Templates de contas que se repetem automaticamente</p>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'success' ? 'success' : 'error' ?>">
        <?= nl2br(htmlspecialchars($_SESSION['flash']['msg'])) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (Permissao::pode('gerar_recorrencia')): ?>
<div class="card-gerar-mes">
    <h2>📅 Gerar Contas do Mês</h2>
    <p>Clique abaixo para gerar automaticamente as contas deste mês baseadas nas recorrências ativas.</p>
    <form method="POST" action="<?= BASE_URL ?>/recorrencia_gerar.php" style="display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap">
        <label>Mês
            <input type="month" name="mes" value="<?= date('Y-m') ?>" required>
        </label>
        <button type="submit" class="btn btn-verde btn-grande"
                onclick="return confirm('Gerar contas do mês selecionado? Contas já geradas serão puladas.')">
            ⚡ Gerar Contas do Mês
        </button>
    </form>
    <small class="muted">Última geração registrada em cada recorrência evita duplicação.</small>
</div>
<?php endif; ?>

<div class="barra-acoes">
    <div></div>
    <?php if (Permissao::pode('gerenciar_cadastros')): ?>
        <a href="<?= BASE_URL ?>/recorrencia_form.php" class="btn btn-primario">+ Nova Recorrência</a>
    <?php endif; ?>
</div>

<?php if (empty($recorrencias)): ?>
    <p class="vazio">Nenhuma recorrência cadastrada. <a href="<?= BASE_URL ?>/recorrencia_form.php">Criar a primeira</a></p>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Fornecedor</th>
                <th>Categoria</th>
                <th>Valor</th>
                <th>Dia</th>
                <th>Periodicidade</th>
                <th>Última geração</th>
                <th>Geradas</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($recorrencias as $r): ?>
            <tr>
                <td><strong><?= htmlspecialchars($r['descricao']) ?></strong></td>
                <td><?= htmlspecialchars($r['fornecedor_nome'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['categoria_nome'] ?? '-') ?></td>
                <td class="td-valor"><?= brl($r['valor']) ?></td>
                <td>Dia <?= $r['dia_vencimento'] ?></td>
                <td><span class="badge badge-azul"><?= $r['periodicidade'] ?></span></td>
                <td><?= $r['ultima_geracao'] ? substr($r['ultima_geracao'], 5, 2) . '/' . substr($r['ultima_geracao'], 0, 4) : '<span class="muted">nunca</span>' ?></td>
                <td><?= $r['qtd_geradas'] ?></td>
                <td>
                    <?php if ($r['ativa']): ?>
                        <span class="badge badge-verde">Ativa</span>
                    <?php else: ?>
                        <span class="badge badge-cinza">Inativa</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (Permissao::pode('gerenciar_cadastros')): ?>
                        <a href="<?= BASE_URL ?>/recorrencia_form.php?id=<?= $r['id'] ?>" class="btn btn-pequeno">Editar</a>
                        <form method="POST" action="<?= BASE_URL ?>/recorrencia_acao.php" style="display:inline">
                            <input type="hidden" name="acao" value="<?= $r['ativa'] ? 'desativar' : 'ativar' ?>">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-pequeno <?= $r['ativa'] ? 'btn-vermelho' : 'btn-verde' ?>"
                                    onclick="return confirm('<?= $r['ativa'] ? 'Desativar' : 'Ativar' ?> esta recorrência?')">
                                <?= $r['ativa'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>