<?php
// /home/sistema/contas-pagar/public/recorrencia_form.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/RecorrenciaController.php';
require_once __DIR__ . '/../src/controllers/FornecedorController.php';
require_once __DIR__ . '/../src/controllers/CategoriaController.php';

Auth::require();
Permissao::require('gerenciar_cadastros');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$recorrencia = $id ? RecorrenciaController::obter($id) : null;

$fornecedores = FornecedorController::listarAtivos();
$categorias = CategoriaController::listarAtivas();

$titulo = $id ? 'Editar Recorrência' : 'Nova Recorrência';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>🔁 <?= $titulo ?></h1>
<p class="empresa-atual"><a href="<?= BASE_URL ?>/recorrencia.php">← Voltar</a></p>

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

<form method="POST" action="<?= BASE_URL ?>/recorrencia_salvar.php" class="form-padrao">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-linha">
        <label>Descrição *
            <input type="text" name="descricao" required maxlength="255"
                   value="<?= htmlspecialchars($recorrencia['descricao'] ?? '') ?>"
                   placeholder="Ex: Aluguel sala comercial">
        </label>
    </div>

    <div class="form-linha">
        <label>Fornecedor
            <select name="fornecedor_id">
                <option value="">— sem fornecedor —</option>
                <?php foreach ($fornecedores as $f): ?>
                    <option value="<?= $f['id'] ?>" <?= ($recorrencia['fornecedor_id'] ?? '') == $f['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($f['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Categoria
            <select name="categoria_id">
                <option value="">— sem categoria —</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($recorrencia['categoria_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="form-linha">
        <label>Valor *
            <input type="text" name="valor" required
                   value="<?= brl_input($recorrencia['valor'] ?? '') ?>"
                   placeholder="R$ 0,00" oninput="maskMoney(this)">
        </label>
        <label>Dia de Vencimento * <small>(1-31)</small>
            <input type="number" name="dia_vencimento" required min="1" max="31"
                   value="<?= htmlspecialchars($recorrencia['dia_vencimento'] ?? '5') ?>">
        </label>
        <label>Periodicidade *
            <select name="periodicidade" required>
                <?php foreach (PERIODICIDADE as $p): ?>
                    <option value="<?= $p ?>" <?= ($recorrencia['periodicidade'] ?? 'MENSAL') === $p ? 'selected' : '' ?>>
                        <?= ucfirst(strtolower($p)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="form-linha">
        <label>Data de Início *
            <input type="date" name="data_inicio" required
                   value="<?= htmlspecialchars($recorrencia['data_inicio'] ?? date('Y-m-d')) ?>">
        </label>
        <label>Data de Fim <small>(opcional)</small>
            <input type="date" name="data_fim"
                   value="<?= htmlspecialchars($recorrencia['data_fim'] ?? '') ?>">
        </label>
        <label>Status
            <select name="ativa">
                <option value="1" <?= ($recorrencia['ativa'] ?? 1) == 1 ? 'selected' : '' ?>>Ativa</option>
                <option value="0" <?= ($recorrencia['ativa'] ?? 1) == 0 ? 'selected' : '' ?>>Inativa</option>
            </select>
        </label>
    </div>

    <label>Observações
        <textarea name="observacoes" rows="3"><?= htmlspecialchars($recorrencia['observacoes'] ?? '') ?></textarea>
    </label>

    <div class="form-acoes">
        <a href="<?= BASE_URL ?>/recorrencia.php" class="btn">Cancelar</a>
        <button type="submit" class="btn btn-primario">Salvar</button>
    </div>
</form>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>