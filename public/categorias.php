<?php
// /home/sistema/contas-pagar/public/categorias.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/CategoriaController.php';

Auth::require();

$categorias = CategoriaController::listar();
$titulo = 'Categorias';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>🏷️ Categorias</h1>
<p class="empresa-atual">Categorização de contas a pagar da empresa atual</p>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="barra-acoes">
    <div></div>
    <?php if (Permissao::pode('gerenciar_cadastros')): ?>
        <a href="<?= BASE_URL ?>/categoria_form.php" class="btn btn-primario">+ Nova Categoria</a>
    <?php endif; ?>
</div>

<?php if (empty($categorias)): ?>
    <p class="vazio">Nenhuma categoria cadastrada.</p>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th style="width:60px">Cor</th>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Contas</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categorias as $c): ?>
            <tr>
                <td><span style="display:inline-block;width:30px;height:30px;border-radius:4px;background:<?= htmlspecialchars($c['cor']) ?>;vertical-align:middle"></span></td>
                <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                <td><span class="badge badge-azul"><?= htmlspecialchars($c['tipo']) ?></span></td>
                <td><?= $c['qtd_contas'] ?></td>
                <td>
                    <?php if ($c['ativo']): ?>
                        <span class="badge badge-verde">Ativa</span>
                    <?php else: ?>
                        <span class="badge badge-cinza">Inativa</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (Permissao::pode('gerenciar_cadastros')): ?>
                        <a href="<?= BASE_URL ?>/categoria_form.php?id=<?= $c['id'] ?>" class="btn btn-pequeno">Editar</a>
                        <form method="POST" action="<?= BASE_URL ?>/categoria_acao.php" style="display:inline">
                            <input type="hidden" name="acao" value="<?= $c['ativo'] ? 'desativar' : 'ativar' ?>">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn btn-pequeno <?= $c['ativo'] ? 'btn-vermelho' : 'btn-verde' ?>"
                                    onclick="return confirm('<?= $c['ativo'] ? 'Desativar' : 'Ativar' ?> esta categoria?')">
                                <?= $c['ativo'] ? 'Desativar' : 'Ativar' ?>
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