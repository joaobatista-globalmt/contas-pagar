<?php
// /home/sistema/contas-pagar/public/conta_detalhe.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/ContaController.php';

Auth::require();

$id = (int)($_GET['id'] ?? 0);
$conta = ContaController::obter($id);

if (!$conta) {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Conta não encontrada'];
    header('Location: ' . BASE_URL . '/contas.php');
    exit;
}

$titulo = 'Conta #' . $conta['id'];
require_once __DIR__ . '/../src/views/layout/header.php';

function brl($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
function brl_input($v) { return 'R$ ' . number_format((float)$v, 2, ',', '.'); }
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

<h1>📋 <?= htmlspecialchars($conta['descricao']) ?></h1>
<p class="empresa-atual">
    <a href="<?= BASE_URL ?>/contas.php">← Voltar à lista</a>
</p>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (!empty($conta['parcelas'])): ?>
<section class="conta-parcelas">
    <h2>📊 Parcelas (<?= count($conta['parcelas']) ?>)</h2>
    <table class="tabela">
        <thead>
            <tr>
                <th>#</th>
                <th>Vencimento</th>
                <th>Valor</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($conta['parcelas'] as $p): ?>
                <tr>
                    <td><strong><?= $p['parcela_numero'] ?>/<?= $p['parcela_total'] ?></strong></td>
                    <td>
                        <?= date('d/m/Y', strtotime($p['data_vencimento'])) ?>
                        <?php if ($p['status'] !== 'PAGA' && $p['status'] !== 'CANCELADA' && $p['dias_vencidos'] > 0): ?>
                            <br><span class="badge badge-vermelho">+<?= $p['dias_vencidos'] ?> dia(s)</span>
                        <?php endif; ?>
                    </td>
                    <td class="td-valor"><?= brl($p['valor']) ?>
                        <?php if ($p['valor_pago']): ?>
                            <br><small class="muted">Pago: <?= brl($p['valor_pago']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= statusBadge($p['status']) ?>
                        <?php if ($p['data_pagamento']): ?>
                            <br><small class="muted"><?= date('d/m/Y', strtotime($p['data_pagamento'])) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?= BASE_URL ?>/conta_detalhe.php?id=<?= $p['id'] ?>" class="btn btn-pequeno">Ver</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;font-weight:bold">Total:</td>
                <td class="td-valor" style="font-weight:bold"><?= brl($conta['valor']) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</section>
<?php endif; ?>

<div class="conta-grid">
    <section class="conta-info">
        <h2>Informações</h2>
        <table class="tabela-info">
            <tr><th>Status</th><td><?= statusBadge($conta['status']) ?>
                <?php if ($conta['status'] !== 'PAGA' && $conta['status'] !== 'CANCELADA' && $conta['dias_vencidos'] > 0): ?>
                    <span class="badge badge-vermelho">Atrasada <?= $conta['dias_vencidos'] ?> dia(s)</span>
                <?php endif; ?>
            </td></tr>
            <tr><th>Valor</th><td><strong style="font-size:18px"><?= brl($conta['valor']) ?></strong>
                <?php if ($conta['valor_pago'] && $conta['valor_pago'] != $conta['valor']): ?>
                    <br><small class="muted">Pago: <?= brl($conta['valor_pago']) ?></small>
                <?php endif; ?>
            </td></tr>
            <tr><th>Descrição</th><td><?= htmlspecialchars($conta['descricao']) ?></td></tr>
            <tr><th>Nº Documento</th><td><?= htmlspecialchars($conta['numero_documento'] ?? '-') ?></td></tr>
            <tr><th>Fornecedor</th><td>
                <?php if ($conta['fornecedor_nome']): ?>
                    <?= htmlspecialchars($conta['fornecedor_nome']) ?>
                    <?php if ($conta['fornecedor_doc']): ?>
                        <br><small class="muted"><?= htmlspecialchars($conta['fornecedor_doc']) ?></small>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="muted">-</span>
                <?php endif; ?>
            </td></tr>
            <tr><th>Categoria</th><td>
                <?php if ($conta['categoria_nome']): ?>
                    <span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:<?= htmlspecialchars($conta['categoria_cor']) ?>;vertical-align:middle;margin-right:6px"></span>
                    <?= htmlspecialchars($conta['categoria_nome']) ?>
                <?php else: ?>
                    <span class="muted">-</span>
                <?php endif; ?>
            </td></tr>
            <tr><th>Data de Emissão</th><td><?= $conta['data_emissao'] ? date('d/m/Y', strtotime($conta['data_emissao'])) : '<span class="muted">-</span>' ?></td></tr>
            <tr><th>Data de Vencimento</th><td><?= date('d/m/Y', strtotime($conta['data_vencimento'])) ?></td></tr>
            <tr><th>Forma de Pagamento</th><td><?= $conta['forma_pagamento'] ? ucwords(strtolower(str_replace('_', ' ', $conta['forma_pagamento']))) : '<span class="muted">-</span>' ?></td></tr>

            <?php if ($conta['status'] === 'PAGA'): ?>
                <tr><th>Data do Pagamento</th><td><?= date('d/m/Y', strtotime($conta['data_pagamento'])) ?></td></tr>
                <tr><th>Pago por</th><td><?= htmlspecialchars($conta['pago_por_nome'] ?? '-') ?></td></tr>
            <?php endif; ?>

            <?php if ($conta['aprovada_por']): ?>
                <tr><th>Aprovada por</th><td><?= htmlspecialchars($conta['aprovado_por_nome']) ?>
                    <br><small class="muted"><?= date('d/m/Y H:i', strtotime($conta['aprovada_em'])) ?></small>
                </td></tr>
            <?php endif; ?>

            <tr><th>Criada por</th><td><?= htmlspecialchars($conta['criado_por_nome'] ?? '-') ?>
                <br><small class="muted"><?= date('d/m/Y H:i', strtotime($conta['created_at'])) ?></small>
            </td></tr>

            <?php if ($conta['observacoes']): ?>
                <tr><th>Observações</th><td><?= nl2br(htmlspecialchars($conta['observacoes'])) ?></td></tr>
            <?php endif; ?>
        </table>

        <div class="conta-acoes">
            <?php if ($conta['status'] === 'PENDENTE' || $conta['status'] === 'APROVADA'): ?>
                <?php if (Permissao::pode('editar_conta')): ?>
                    <a href="<?= BASE_URL ?>/conta_form.php?id=<?= $conta['id'] ?>" class="btn">✏️ Editar</a>
                <?php endif; ?>
                <?php if ($conta['status'] === 'PENDENTE' && Permissao::pode('aprovar_conta')): ?>
                    <form method="POST" action="<?= BASE_URL ?>/conta_acao.php" style="display:inline">
                        <input type="hidden" name="acao" value="aprovar">
                        <input type="hidden" name="id" value="<?= $conta['id'] ?>">
                        <button type="submit" class="btn btn-azul">✓ Aprovar</button>
                    </form>
                <?php endif; ?>
                <?php if (Permissao::pode('pagar_conta')): ?>
                    <button type="button" class="btn btn-verde" onclick="document.getElementById('modal-pagar').style.display='block'">💰 Pagar</button>
                <?php endif; ?>
                <?php if (Permissao::pode('cancelar_conta')): ?>
                    <form method="POST" action="<?= BASE_URL ?>/conta_acao.php" style="display:inline"
                          onsubmit="return confirm('Cancelar esta conta?')">
                        <input type="hidden" name="acao" value="cancelar">
                        <input type="hidden" name="id" value="<?= $conta['id'] ?>">
                        <button type="submit" class="btn btn-vermelho">✗ Cancelar</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($conta['status'] === 'PENDENTE' && Permissao::pode('excluir_conta')): ?>
                <form method="POST" action="<?= BASE_URL ?>/conta_acao.php" style="display:inline"
                      onsubmit="return confirm('Excluir definitivamente esta conta?')">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= $conta['id'] ?>">
                    <button type="submit" class="btn btn-vermelho">🗑️ Excluir</button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <section class="conta-anexos">
        <h2>📎 Anexos</h2>
        <p class="muted">Em breve: upload de PDFs da NF.</p>
        <p class="muted">(Fase 10 do projeto)</p>
    </section>
</div>

<?php if (Permissao::pode('pagar_conta') && ($conta['status'] === 'PENDENTE' || $conta['status'] === 'APROVADA')): ?>
<!-- Modal de Pagamento -->
<div id="modal-pagar" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000">
    <div style="background:white;max-width:500px;margin:80px auto;padding:24px;border-radius:8px;position:relative">
        <button onclick="document.getElementById('modal-pagar').style.display='none'"
                style="position:absolute;top:8px;right:12px;border:none;background:none;font-size:20px;cursor:pointer">×</button>
        <h2>💰 Registrar Pagamento</h2>
        <form method="POST" action="<?= BASE_URL ?>/conta_acao.php">
            <input type="hidden" name="acao" value="pagar">
            <input type="hidden" name="id" value="<?= $conta['id'] ?>">

            <label>Data do Pagamento *
                <input type="date" name="data_pagamento" required value="<?= date('Y-m-d') ?>">
            </label>
            <label>Valor Pago *
                <input type="text" name="valor_pago" required value="<?= brl_input($conta['valor']) ?>" oninput="maskMoney(this)">
            </label>
            <label>Forma de Pagamento *
                <select name="forma_pagamento" required>
                    <?php foreach (FORMAS_PAGAMENTO as $fp): ?>
                        <option value="<?= $fp ?>" <?= ($conta['forma_pagamento'] ?? '') === $fp ? 'selected' : '' ?>>
                            <?= ucwords(strtolower(str_replace('_', ' ', $fp))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px">
                <button type="button" class="btn" onclick="document.getElementById('modal-pagar').style.display='none'">Cancelar</button>
                <button type="submit" class="btn btn-verde">Confirmar Pagamento</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>