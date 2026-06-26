<?php
// /home/sistema/contas-pagar/public/usuarios.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/UsuarioController.php';

Auth::require();
Permissao::require('gerenciar_usuarios');

$usuarios = UsuarioController::listar();
$titulo = 'Usuários';
require_once __DIR__ . '/../src/views/layout/header.php';
?>

<h1>👥 Usuários</h1>
<p class="empresa-atual">Gerenciamento de usuários e seus vínculos com empresas</p>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="alert alert-<?= $_SESSION['flash']['tipo'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($_SESSION['flash']['msg']) ?>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<div class="acoes-topo">
    <a href="<?= BASE_URL ?>/usuario_form.php" class="btn btn-primario">+ Novo Usuário</a>
</div>

<table class="tabela">
    <thead>
        <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Perfil padrão</th>
            <th>Empresas vinculadas</th>
            <th>Último acesso</th>
            <th>Status</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['nome']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge badge-azul"><?= htmlspecialchars($u['perfil_padrao']) ?></span></td>
            <td><small><?= htmlspecialchars($u['empresas_vinculadas'] ?? '(nenhuma)') ?></small></td>
            <td><?= $u['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acesso'])) : '<span class="muted">nunca</span>' ?></td>
            <td>
                <?php if ($u['ativo']): ?>
                    <span class="badge badge-verde">Ativo</span>
                <?php else: ?>
                    <span class="badge badge-cinza">Inativo</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= BASE_URL ?>/usuario_form.php?id=<?= $u['id'] ?>" class="btn btn-pequeno">Editar</a>
                <?php if ($u['id'] != Auth::user()['id']): ?>
                    <form method="POST" action="<?= BASE_URL ?>/usuario_acao.php" style="display:inline">
                        <input type="hidden" name="acao" value="<?= $u['ativo'] ? 'desativar' : 'ativar' ?>">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-pequeno <?= $u['ativo'] ? 'btn-vermelho' : 'btn-verde' ?>"
                                onclick="return confirm('<?= $u['ativo'] ? 'Desativar' : 'Ativar' ?> este usuário?')">
                            <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
                        </button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../src/views/layout/footer.php'; ?>