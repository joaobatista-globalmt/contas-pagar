<?php
// /home/sistema/contas-pagar/src/lib/Auth.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

class Auth {
    /**
     * Faz login com email e senha
     */
    public static function login(string $email, string $senha): array {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['ok' => false, 'msg' => 'Usuário não encontrado ou inativo'];
        }

        if (!password_verify($senha, $user['senha_hash'])) {
            return ['ok' => false, 'msg' => 'Senha incorreta'];
        }

        // Pega empresas vinculadas
        $stmt = $pdo->prepare('
            SELECT ue.empresa_id, e.razao_social, e.nome_fantasia, e.cnpj, ue.perfil_na_empresa
            FROM usuarios_empresas ue
            INNER JOIN empresas e ON e.id = ue.empresa_id
            WHERE ue.usuario_id = ? AND ue.ativo = 1 AND e.ativo = 1
            ORDER BY e.razao_social
        ');
        $stmt->execute([$user['id']]);
        $empresas = $stmt->fetchAll();

        if (empty($empresas)) {
            return ['ok' => false, 'msg' => 'Usuário sem empresa vinculada'];
        }

        // Salva na sessao
        $_SESSION['user'] = [
            'id' => $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
            'perfil_padrao' => $user['perfil_padrao'],
        ];
        $_SESSION['empresas'] = $empresas;

        // Define empresa atual (primeira da lista, ou a última usada)
        if (isset($_SESSION['empresa_atual_id'])) {
            // valida que ainda tem acesso
            $found = false;
            foreach ($empresas as $e) {
                if ($e['empresa_id'] == $_SESSION['empresa_atual_id']) { $found = true; break; }
            }
            if (!$found) {
                $_SESSION['empresa_atual_id'] = $empresas[0]['empresa_id'];
            }
        } else {
            $_SESSION['empresa_atual_id'] = $empresas[0]['empresa_id'];
        }

        // Atualiza ultimo_acesso
        $pdo->prepare('UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?')->execute([$user['id']]);

        return ['ok' => true];
    }

    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool {
        return isset($_SESSION['user']);
    }

    public static function user(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public static function empresas(): array {
        return $_SESSION['empresas'] ?? [];
    }

    public static function empresaAtualId(): ?int {
        return $_SESSION['empresa_atual_id'] ?? null;
    }

    public static function setEmpresaAtual(int $empresaId): bool {
        foreach (self::empresas() as $e) {
            if ($e['empresa_id'] === $empresaId) {
                $_SESSION['empresa_atual_id'] = $empresaId;
                return true;
            }
        }
        return false;
    }

    public static function perfilNaEmpresaAtual(): ?string {
        $id = self::empresaAtualId();
        if (!$id) return null;
        foreach (self::empresas() as $e) {
            if ($e['empresa_id'] === $id) return $e['perfil_na_empresa'];
        }
        return null;
    }

    public static function require(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        if (!self::empresaAtualId()) {
            header('Location: ' . BASE_URL . '/selecionar-empresa.php');
            exit;
        }
    }
}