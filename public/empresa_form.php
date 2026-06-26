<?php
// /home/sistema/contas-pagar/public/empresa_form.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/EmpresaController.php';

Auth::require();
Permissao::require('gerenciar_cadastros');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$empresa = $id ? EmpresaController::obter($id) : null;

$titulo = $id ? 'Editar Empresa' : 'Nova Empresa';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>🏭 <?= $titulo ?></h1>
<p class="empresa-atual"><a href="<?= BASE_URL ?>/empresas.php">← Voltar</a></p>

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

<form method="POST" action="<?= BASE_URL ?>/empresa_salvar.php" class="form-padrao">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-linha">
        <label>Razão Social *
            <input type="text" name="razao_social" required value="<?= htmlspecialchars($empresa['razao_social'] ?? '') ?>">
        </label>
        <label>Nome Fantasia
            <input type="text" name="nome_fantasia" value="<?= htmlspecialchars($empresa['nome_fantasia'] ?? '') ?>">
        </label>
    </div>

    <div class="form-linha">
        <label>CNPJ
            <input type="text" name="cnpj" maxlength="18" placeholder="00.000.000/0000-00"
                   value="<?= htmlspecialchars($empresa['cnpj'] ?? '') ?>"
                   oninput="maskCNPJ(this)">
        </label>
        <label>Inscrição Estadual
            <input type="text" name="inscricao_estadual" value="<?= htmlspecialchars($empresa['inscricao_estadual'] ?? '') ?>">
        </label>
    </div>

    <label>Endereço
        <input type="text" name="endereco" value="<?= htmlspecialchars($empresa['endereco'] ?? '') ?>"
               placeholder="Rua, número, bairro">
    </label>

    <div class="form-linha">
        <label>Cidade
            <input type="text" name="cidade" value="<?= htmlspecialchars($empresa['cidade'] ?? '') ?>">
        </label>
        <label>UF
            <input type="text" name="uf" maxlength="2" value="<?= htmlspecialchars($empresa['uf'] ?? '') ?>"
                   oninput="this.value = this.value.toUpperCase()">
        </label>
        <label>CEP
            <input type="text" name="cep" maxlength="9" value="<?= htmlspecialchars($empresa['cep'] ?? '') ?>"
                   oninput="maskCEP(this)">
        </label>
    </div>

    <div class="form-linha">
        <label>Telefone
            <input type="text" name="telefone" value="<?= htmlspecialchars($empresa['telefone'] ?? '') ?>"
                   oninput="maskPhone(this)">
        </label>
        <label>E-mail
            <input type="email" name="email" value="<?= htmlspecialchars($empresa['email'] ?? '') ?>">
        </label>
    </div>

    <div class="form-acoes">
        <a href="<?= BASE_URL ?>/empresas.php" class="btn">Cancelar</a>
        <button type="submit" class="btn btn-primario">Salvar</button>
    </div>
</form>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>