<?php
// /home/sistema/contas-pagar/public/fornecedores.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/FornecedorController.php';

Auth::require();

$busca = $_GET['busca'] ?? null;
$fornecedores = FornecedorController::listar($busca);
$titulo = 'Fornecedores';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>🏢 Fornecedores</h1>
<p class="empresa-atual">Gerenciamento de fornecedores da empresa atual</p>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="barra-acoes">
    <form method="GET" class="busca-form">
        <input type="search" name="busca" value="<?= htmlspecialchars($busca ?? '') ?>" placeholder="Buscar por nome ou CNPJ/CPF...">
        <button type="submit" class="btn">🔍 Buscar</button>
        <?php if ($busca): ?>
            <a href="<?= BASE_URL ?>/fornecedores.php" class="btn">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (Permissao::pode('gerenciar_cadastros')): ?>
        <a href="<?= BASE_URL ?>/fornecedor_form.php" class="btn btn-primario">+ Novo Fornecedor</a>
    <?php endif; ?>
</div>

<?php if (empty($fornecedores)): ?>
    <p class="vazio">Nenhum fornecedor <?= $busca ? 'encontrado' : 'cadastrado' ?>.</p>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>CPF/CNPJ</th>
                <th>Contato</th>
                <th>Contas</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($fornecedores as $f): ?>
            <tr>
                <td><strong><?= htmlspecialchars($f['nome']) ?></strong>
                    <?php if ($f['pix']): ?>
                        <br><small class="muted">PIX: <?= htmlspecialchars($f['pix']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= $f['tipo_pessoa'] === 'J' ? 'PJ' : 'PF' ?></td>
                <td><small><?= htmlspecialchars($f['cnpj_cpf'] ?? '-') ?></small></td>
                <td>
                    <?php if ($f['email']): ?>
                        📧 <?= htmlspecialchars($f['email']) ?><br>
                    <?php endif; ?>
                    <?php if ($f['telefone']): ?>
                        <small class="muted"><?= htmlspecialchars($f['telefone']) ?></small>
                    <?php endif; ?>
                </td>
                <td><?= $f['qtd_contas'] ?></td>
                <td>
                    <?php if ($f['ativo']): ?>
                        <span class="badge badge-verde">Ativo</span>
                    <?php else: ?>
                        <span class="badge badge-cinza">Inativo</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (Permissao::pode('gerenciar_cadastros')): ?>
                        <a href="<?= BASE_URL ?>/fornecedor_form.php?id=<?= $f['id'] ?>" class="btn btn-pequeno">Editar</a>
                        <form method="POST" action="<?= BASE_URL ?>/fornecedor_acao.php" style="display:inline">
                            <input type="hidden" name="acao" value="<?= $f['ativo'] ? 'desativar' : 'ativar' ?>">
                            <input type="hidden" name="id" value="<?= $f['id'] ?>">
                            <button type="submit" class="btn btn-pequeno <?= $f['ativo'] ? 'btn-vermelho' : 'btn-verde' ?>"
                                    onclick="return confirm('<?= $f['ativo'] ? 'Desativar' : 'Ativar' ?> este fornecedor?')">
                                <?= $f['ativo'] ? 'Desativar' : 'Ativar' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <span class="muted">visualização</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>