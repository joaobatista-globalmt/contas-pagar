<?php
// /home/sistema/contas-pagar/public/categoria_form.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/CategoriaController.php';

Auth::require();
Permissao::require('gerenciar_cadastros');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$categoria = $id ? CategoriaController::obter($id) : null;

$titulo = $id ? 'Editar Categoria' : 'Nova Categoria';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>🏷️ <?= $titulo ?></h1>
<p class="empresa-atual"><a href="<?= BASE_URL ?>/categorias.php">← Voltar</a></p>

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

<form method="POST" action="<?= BASE_URL ?>/categoria_salvar.php" class="form-padrao">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-linha">
        <label>Nome *
            <input type="text" name="nome" required maxlength="100" value="<?= htmlspecialchars($categoria['nome'] ?? '') ?>">
        </label>
        <label>Tipo
            <select name="tipo">
                <?php
                $tipos = ['DESPESA', 'IMPOSTO', 'SERVICO', 'PRODUTO', 'OUTROS'];
                foreach ($tipos as $t): ?>
                    <option value="<?= $t ?>" <?= ($categoria['tipo'] ?? 'DESPESA') === $t ? 'selected' : '' ?>>
                        <?= ucfirst(strtolower($t)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Cor
            <input type="color" name="cor" value="<?= htmlspecialchars($categoria['cor'] ?? '#3498db') ?>">
        </label>
    </div>

    <div class="form-acoes">
        <a href="<?= BASE_URL ?>/categorias.php" class="btn">Cancelar</a>
        <button type="submit" class="btn btn-primario">Salvar</button>
    </div>
</form>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>