<?php
// /home/sistema/contas-pagar/public/usuario_form.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/UsuarioController.php';

Auth::require();
Permissao::require('gerenciar_usuarios');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$usuario = $id ? UsuarioController::obter($id) : null;
$todasEmpresas = UsuarioController::todasEmpresas();

// Agrupa vinculos por empresa_id pra facil lookup
$vinculos = [];
if ($usuario) {
    foreach ($usuario['vinculos'] as $v) {
        $vinculos[$v['empresa_id']] = $v['perfil_na_empresa'];
    }
}

$titulo = $id ? 'Editar Usuário' : 'Novo Usuário';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>👥 <?= $titulo ?></h1>
<p class="empresa-atual"><a href="<?= BASE_URL ?>/usuarios.php">← Voltar</a></p>

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

<form method="POST" action="<?= BASE_URL ?>/usuario_salvar.php" class="form-padrao">
    <input type="hidden" name="id" value="<?= $id ?>">

    <div class="form-linha">
        <label>Nome *
            <input type="text" name="nome" required value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>">
        </label>
        <label>E-mail *
            <input type="email" name="email" required value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
        </label>
    </div>

    <?php if (!$id): ?>
        <label>Senha * <small>(mínimo 6 caracteres)</small>
            <input type="password" name="senha" required minlength="6">
        </label>
    <?php else: ?>
        <label>Nova Senha <small>(deixe vazio para não alterar)</small>
            <input type="password" name="senha" minlength="6">
        </label>
    <?php endif; ?>

    <label>Perfil Padrão
        <select name="perfil_padrao">
            <?php foreach (PERFIS as $p): ?>
                <option value="<?= $p ?>" <?= ($usuario['perfil_padrao'] ?? 'operador') === $p ? 'selected' : '' ?>>
                    <?= ucfirst($p) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="muted">Perfil aplicado quando o usuário acessa uma empresa pela primeira vez.</small>
    </label>

    <h2 style="margin-top:24px;font-size:16px">🏭 Vínculos com Empresas</h2>
    <p class="muted" style="font-size:13px">Selecione em quais empresas o usuário pode acessar e qual perfil terá em cada uma.</p>

    <?php if (empty($todasEmpresas)): ?>
        <p class="vazio">Nenhuma empresa ativa cadastrada. <a href="<?= BASE_URL ?>/empresa_form.php">Criar empresa</a></p>
    <?php else: ?>
        <table class="tabela tabela-vinculos">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Tem acesso?</th>
                    <th>Perfil</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($todasEmpresas as $emp): ?>
                <?php $temVinculo = isset($vinculos[$emp['id']]); ?>
                <tr>
                    <td><?= htmlspecialchars($emp['razao_social']) ?><br>
                        <small class="muted"><?= htmlspecialchars($emp['nome_fantasia'] ?? '') ?></small>
                    </td>
                    <td>
                        <input type="checkbox" name="vinculos[<?= $emp['id'] ?>][ativo]" value="1"
                               <?= $temVinculo ? 'checked' : '' ?> onchange="toggleVinculo(this, <?= $emp['id'] ?>)">
                    </td>
                    <td>
                        <select name="vinculos[<?= $emp['id'] ?>][perfil_na_empresa]" id="vinculo_<?= $emp['id'] ?>" <?= !$temVinculo ? 'disabled' : '' ?>>
                            <?php foreach (PERFIS as $p): ?>
                                <option value="<?= $p ?>" <?= ($vinculos[$emp['id']] ?? 'operador') === $p ? 'selected' : '' ?>>
                                    <?= ucfirst($p) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="form-acoes">
        <a href="<?= BASE_URL ?>/usuarios.php" class="btn">Cancelar</a>
        <button type="submit" class="btn btn-primario">Salvar</button>
    </div>
</form>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>