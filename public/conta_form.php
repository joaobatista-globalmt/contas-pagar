<?php
// /home/sistema/contas-pagar/public/conta_form.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/ContaController.php';
require_once __DIR__ . '/../src/controllers/FornecedorController.php';
require_once __DIR__ . '/../src/controllers/CategoriaController.php';

Auth::require();
Permissao::require('criar_conta'); // cria e edita usam mesma página

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$conta = null;
if ($id) {
    $conta = ContaController::obter($id);
    if (!$conta) {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Conta não encontrada'];
        header('Location: ' . BASE_URL . '/contas.php');
        exit;
    }
    if ($conta['status'] !== 'PENDENTE' && $conta['status'] !== 'APROVADA') {
        $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Conta não pode ser editada (status: ' . $conta['status'] . ')'];
        header('Location: ' . BASE_URL . '/conta_detalhe.php?id=' . $id);
        exit;
    }
    Permissao::require('editar_conta');
}

$fornecedores = FornecedorController::listarAtivos();
$categorias = CategoriaController::listarAtivas();

$titulo = $id ? 'Editar Conta' : 'Nova Conta a Pagar';
require_once __DIR__ . '/../src/views/layout/header.php';

function brl_input($v) {
    if (!$v) return '';
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}
?>

<h1>📋 <?= $titulo ?></h1>
<p class="empresa-atual"><a href="<?= BASE_URL ?>/contas.php">← Voltar à lista</a></p>

<?php if (isset($_SESSION['erros_form'])): ?>
    <div class="alert alert-error">
        <ul style="margin:0;padding-left:20px">
            <?php foreach ($_SESSION['erros_form'] as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['erros_form']); ?>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>/conta_salvar.php" class="form-padrao">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-linha">
        <label>Descrição *
            <input type="text" name="descricao" required maxlength="255" value="<?= htmlspecialchars($conta['descricao'] ?? '') ?>">
        </label>
        <label>Nº Documento / NF
            <input type="text" name="numero_documento" maxlength="50" value="<?= htmlspecialchars($conta['numero_documento'] ?? '') ?>" placeholder="Ex: NF-12345">
        </label>
    </div>

    <div class="form-linha">
        <label>Fornecedor
            <select name="fornecedor_id">
                <option value="">— sem fornecedor —</option>
                <?php foreach ($fornecedores as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= ($conta['fornecedor_id'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Categoria
            <select name="categoria_id">
                <option value="">— sem categoria —</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($conta['categoria_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="form-linha">
        <label>Valor *
            <input type="text" name="valor" required value="<?= brl_input($conta['valor'] ?? '') ?>"
                   placeholder="R$ 0,00" oninput="maskMoney(this)">
        </label>
        <label>Data de Emissão
            <input type="date" name="data_emissao" value="<?= htmlspecialchars($conta['data_emissao'] ?? '') ?>">
        </label>
        <label>Data de Vencimento *
            <input type="date" name="data_vencimento" required value="<?= htmlspecialchars($conta['data_vencimento'] ?? '') ?>">
        </label>
    </div>

    <label>Forma de Pagamento (prevista)
        <select name="forma_pagamento">
            <option value="">— definir no pagamento —</option>
            <?php foreach (FORMAS_PAGAMENTO as $fp): ?>
                <option value="<?= $fp ?>" <?= ($conta['forma_pagamento'] ?? '') === $fp ? 'selected' : '' ?>>
                    <?= ucwords(strtolower(str_replace('_', ' ', $fp))) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Observações
        <textarea name="observacoes" rows="3"><?= htmlspecialchars($conta['observacoes'] ?? '') ?></textarea>
    </label>

    <div class="form-acoes">
        <a href="<?= BASE_URL ?>/contas.php" class="btn">Cancelar</a>
        <button type="submit" class="btn btn-primario">Salvar (status: PENDENTE)</button>
    </div>
</form>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>