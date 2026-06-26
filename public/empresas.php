<?php
// /home/sistema/contas-pagar/public/empresas.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/EmpresaController.php';

Auth::require();
Permissao::require('gerenciar_cadastros');

$empresas = EmpresaController::listar();
$titulo = 'Empresas';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>🏭 Empresas</h1>
<p class="empresa-atual">Cadastro de empresas do grupo</p>

<div class="acoes-topo">
    <a href="<?= BASE_URL ?>/empresa_form.php" class="btn btn-primario">+ Nova Empresa</a>
</div>

<table class="tabela">
    <thead>
        <tr>
            <th>Razão Social</th>
            <th>Nome Fantasia</th>
            <th>CNPJ</th>
            <th>Cidade/UF</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($empresas as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['razao_social']) ?></td>
            <td><?= htmlspecialchars($e['nome_fantasia'] ?? '-') ?></td>
            <td><?= htmlspecialchars($e['cnpj'] ?? '-') ?></td>
            <td><?= htmlspecialchars(($e['cidade'] ?? '-') . '/' . ($e['uf'] ?? '-')) ?></td>
            <td>
                <?php if ($e['ativo']): ?>
                    <span class="badge badge-verde">Ativa</span>
                <?php else: ?>
                    <span class="badge badge-cinza">Inativa</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= BASE_URL ?>/empresa_form.php?id=<?= $e['id'] ?>" class="btn btn-pequeno">Editar</a>
                <?php if ($e['ativo'] && $e['id'] != Auth::empresaAtualId()): ?>
                    <form method="POST" action="<?= BASE_URL ?>/empresa_acao.php" style="display:inline">
                        <input type="hidden" name="acao" value="desativar">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button type="submit" class="btn btn-pequeno btn-vermelho" onclick="return confirm('Desativar esta empresa?')">Desativar</button>
                    </form>
                <?php elseif (!$e['ativo']): ?>
                    <form method="POST" action="<?= BASE_URL ?>/empresa_acao.php" style="display:inline">
                        <input type="hidden" name="acao" value="ativar">
                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                        <button type="submit" class="btn btn-pequeno btn-verde">Ativar</button>
                    </form>
                <?php else: ?>
                    <span class="muted">(empresa atual)</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>