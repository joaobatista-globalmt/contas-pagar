<?php
// /home/sistema/contas-pagar/src/controllers/UsuarioController.php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Permissao.php';
require_once __DIR__ . '/../lib/Validator.php';

class UsuarioController {

    public static function listar(): array {
        Permissao::require('gerenciar_usuarios');
        $pdo = db();
        $sql = '
            SELECT u.*,
                   GROUP_CONCAT(DISTINCT e.razao_social ORDER BY e.razao_social SEPARATOR ", ") AS empresas_vinculadas
            FROM usuarios u
            LEFT JOIN usuarios_empresas ue ON ue.usuario_id = u.id AND ue.ativo = 1
            LEFT JOIN empresas e ON e.id = ue.empresa_id AND e.ativo = 1
            GROUP BY u.id
            ORDER BY u.nome
        ';
        return $pdo->query($sql)->fetchAll();
    }

    public static function obter(int $id): ?array {
        Permissao::require('gerenciar_usuarios');
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, nome, email, perfil_padrao, ativo, data_criacao, ultimo_acesso FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) return null;

        // Vinculos
        $stmt = $pdo->prepare('
            SELECT ue.empresa_id, e.razao_social, e.nome_fantasia, ue.perfil_na_empresa, ue.ativo
            FROM usuarios_empresas ue
            INNER JOIN empresas e ON e.id = ue.empresa_id
            WHERE ue.usuario_id = ?
            ORDER BY e.razao_social
        ');
        $stmt->execute([$id]);
        $user['vinculos'] = $stmt->fetchAll();
        return $user;
    }

    public static function criar(array $dados): array {
        Permissao::require('gerenciar_usuarios');

        $erros = [];
        $nome = trim($dados['nome'] ?? '');
        $email = trim($dados['email'] ?? '');
        $senha = $dados['senha'] ?? '';
        $perfil = $dados['perfil_padrao'] ?? 'operador';

        if ($nome === '') $erros[] = 'Nome é obrigatório';
        if ($email === '' || !Validator::email($email)) $erros[] = 'E-mail inválido';
        if (strlen($senha) < 6) $erros[] = 'Senha deve ter no mínimo 6 caracteres';
        if (!in_array($perfil, PERFIS)) $erros[] = 'Perfil inválido';

        if ($email) {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) $erros[] = 'Já existe usuário com este e-mail';
        }

        if ($erros) return ['ok' => false, 'erros' => $erros];

        $pdo = db();
        $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil_padrao, ativo) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$nome, $email, $hash, $perfil]);
        $id = (int)$pdo->lastInsertId();

        // Vincular a empresas (opcional)
        if (!empty($dados['vinculos']) && is_array($dados['vinculos'])) {
            self::salvarVinculos($id, $dados['vinculos']);
        }

        self::log('CRIAR_USUARIO', "Usuario #$id - $nome ($email)");
        return ['ok' => true, 'id' => $id];
    }

    public static function atualizar(int $id, array $dados): array {
        Permissao::require('gerenciar_usuarios');

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) return ['ok' => false, 'erros' => ['Usuário não encontrado']];

        $erros = [];
        $nome = trim($dados['nome'] ?? '');
        $email = trim($dados['email'] ?? '');
        $perfil = $dados['perfil_padrao'] ?? 'operador';

        if ($nome === '') $erros[] = 'Nome é obrigatório';
        if ($email === '' || !Validator::email($email)) $erros[] = 'E-mail inválido';
        if (!in_array($perfil, PERFIS)) $erros[] = 'Perfil inválido';

        if ($email) {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ?');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) $erros[] = 'Outro usuário já usa este e-mail';
        }

        if ($erros) return ['ok' => false, 'erros' => $erros];

        $stmt = $pdo->prepare('UPDATE usuarios SET nome = ?, email = ?, perfil_padrao = ? WHERE id = ?');
        $stmt->execute([$nome, $email, $perfil, $id]);

        if (isset($dados['vinculos']) && is_array($dados['vinculos'])) {
            self::salvarVinculos($id, $dados['vinculos']);
        }

        self::log('ATUALIZAR_USUARIO', "Usuario #$id - $nome");
        return ['ok' => true];
    }

    public static function resetarSenha(int $id, string $novaSenha): array {
        Permissao::require('gerenciar_usuarios');
        if (strlen($novaSenha) < 6) return ['ok' => false, 'erros' => ['Senha deve ter no mínimo 6 caracteres']];

        $pdo = db();
        $stmt = $pdo->prepare('SELECT nome FROM usuarios WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) return ['ok' => false, 'erros' => ['Usuário não encontrado']];

        $hash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 10]);
        $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')->execute([$hash, $id]);
        self::log('RESETAR_SENHA', "Usuario #$id - {$user['nome']}");
        return ['ok' => true];
    }

    public static function ativarDesativar(int $id, bool $ativo): array {
        Permissao::require('gerenciar_usuarios');
        if ($id === Auth::user()['id']) return ['ok' => false, 'erros' => ['Você não pode desativar seu próprio usuário']];

        $pdo = db();
        $pdo->prepare('UPDATE usuarios SET ativo = ? WHERE id = ?')->execute([$ativo ? 1 : 0, $id]);
        self::log($ativo ? 'ATIVAR_USUARIO' : 'DESATIVAR_USUARIO', "Usuario #$id");
        return ['ok' => true];
    }

    private static function salvarVinculos(int $usuarioId, array $vinculos): void {
        $pdo = db();
        // Remove todos os vinculos atuais (logico, mantendo historico seria melhor mas eh v1)
        $pdo->prepare('DELETE FROM usuarios_empresas WHERE usuario_id = ?')->execute([$usuarioId]);

        $stmt = $pdo->prepare('INSERT INTO usuarios_empresas (usuario_id, empresa_id, perfil_na_empresa) VALUES (?, ?, ?)');
        foreach ($vinculos as $v) {
            if (!isset($v['empresa_id'], $v['perfil_na_empresa'])) continue;
            $empresaId = (int)$v['empresa_id'];
            $perfil = $v['perfil_na_empresa'];
            if (!in_array($perfil, PERFIS)) continue;

            // Confirma que empresa existe e esta ativa
            $check = $pdo->prepare('SELECT id FROM empresas WHERE id = ? AND ativo = 1');
            $check->execute([$empresaId]);
            if (!$check->fetch()) continue;

            $stmt->execute([$usuarioId, $empresaId, $perfil]);
        }
    }

    public static function todasEmpresas(): array {
        Permissao::require('gerenciar_usuarios');
        $pdo = db();
        return $pdo->query('SELECT id, razao_social, nome_fantasia FROM empresas WHERE ativo = 1 ORDER BY razao_social')->fetchAll();
    }

    private static function log(string $op, string $desc): void {
        $pdo = db();
        $pdo->prepare('INSERT INTO log_operacoes (usuario_id, operacao, descricao) VALUES (?, ?, ?)')
            ->execute([Auth::user()['id'], $op, $desc]);
    }
}