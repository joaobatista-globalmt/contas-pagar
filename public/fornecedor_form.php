<?php
// /home/sistema/contas-pagar/public/fornecedor_form.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/FornecedorController.php';

Auth::require();
Permissao::require('gerenciar_cadastros');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$fornecedor = $id ? FornecedorController::obter($id) : null;

$titulo = $id ? 'Editar Fornecedor' : 'Novo Fornecedor';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>🏢 <?= $titulo ?></h1>
<p class="empresa-atual"><a href="<?= BASE_URL ?>/fornecedores.php">← Voltar</a></p>

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

<form method="POST" action="<?= BASE_URL ?>/fornecedor_salvar.php" class="form-padrao">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-linha">
        <label>Nome *
            <input type="text" name="nome" required value="<?= htmlspecialchars($fornecedor['nome'] ?? '') ?>">
        </label>
        <label>Tipo de Pessoa
            <select name="tipo_pessoa">
                <option value="J" <?= ($fornecedor['tipo_pessoa'] ?? 'J') === 'J' ? 'selected' : '' ?>>Pessoa Jurídica (CNPJ)</option>
                <option value="F" <?= ($fornecedor['tipo_pessoa'] ?? '') === 'F' ? 'selected' : '' ?>>Pessoa Física (CPF)</option>
            </select>
        </label>
    </div>

    <label id="label-doc">CNPJ / CPF
        <input type="text" name="cnpj_cpf" value="<?= htmlspecialchars($fornecedor['cnpj_cpf'] ?? '') ?>"
               placeholder="00.000.000/0000-00" oninput="aplicarMascaraDoc(this)">
    </label>

    <div class="form-linha">
        <label>E-mail
            <input type="email" name="email" value="<?= htmlspecialchars($fornecedor['email'] ?? '') ?>">
        </label>
        <label>Telefone
            <input type="text" name="telefone" value="<?= htmlspecialchars($fornecedor['telefone'] ?? '') ?>"
                   oninput="maskPhone(this)">
        </label>
    </div>

    <h2 style="margin-top:24px;font-size:16px">💰 Dados Bancários</h2>
    <div class="form-linha">
        <label>Banco
            <input type="text" name="banco" value="<?= htmlspecialchars($fornecedor['banco'] ?? '') ?>" placeholder="Banco do Brasil">
        </label>
        <label>Agência
            <input type="text" name="agencia" value="<?= htmlspecialchars($fornecedor['agencia'] ?? '') ?>" placeholder="1234">
        </label>
        <label>Conta
            <input type="text" name="conta" value="<?= htmlspecialchars($fornecedor['conta'] ?? '') ?>" placeholder="12345-6">
        </label>
    </div>

    <label>Chave PIX
        <input type="text" name="pix" value="<?= htmlspecialchars($fornecedor['pix'] ?? '') ?>"
               placeholder="CPF, CNPJ, e-mail, telefone ou chave aleatória">
    </label>

    <label>Observações
        <textarea name="observacoes" rows="3"><?= htmlspecialchars($fornecedor['observacoes'] ?? '') ?></textarea>
    </label>

    <div class="form-acoes">
        <a href="<?= BASE_URL ?>/fornecedores.php" class="btn">Cancelar</a>
        <button type="submit" class="btn btn-primario">Salvar</button>
    </div>
</form>

<script>
// Aplica mascara conforme tipo de pessoa selecionado
document.addEventListener('DOMContentLoaded', function() {
    const select = document.querySelector('select[name="tipo_pessoa"]');
    const inputDoc = document.querySelector('input[name="cnpj_cpf"]');
    const labelDoc = document.getElementById('label-doc');

    function aplicarMascara() {
        if (select.value === 'F') {
            labelDoc.firstChild.textContent = 'CPF';
            inputDoc.placeholder = '000.000.000-00';
        } else {
            labelDoc.firstChild.textContent = 'CNPJ';
            inputDoc.placeholder = '00.000.000/0000-00';
        }
    }
    select.addEventListener('change', function() {
        inputDoc.value = '';
        aplicarMascara();
    });
    aplicarMascara();
});

function aplicarMascaraDoc(input) {
    const tipo = document.querySelector('select[name="tipo_pessoa"]').value;
    if (tipo === 'F') maskCPF(input);
    else maskCNPJ(input);
}
</script>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>