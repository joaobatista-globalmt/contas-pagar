<?php
// /home/sistema/contas-pagar/src/controllers/EmpresaController.php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Permissao.php';
require_once __DIR__ . '/../lib/Validator.php';

class EmpresaController {

    public static function listar(): array {
        Permissao::require('gerenciar_cadastros');
        $pdo = db();
        $stmt = $pdo->query('SELECT * FROM empresas ORDER BY razao_social');
        return $stmt->fetchAll();
    }

    public static function obter(int $id): ?array {
        Permissao::require('gerenciar_cadastros');
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM empresas WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function criar(array $dados): array {
        Permissao::require('gerenciar_cadastros');

        // Validacoes
        $erros = [];
        $razao = trim($dados['razao_social'] ?? '');
        if ($razao === '') $erros[] = 'Razão social é obrigatória';

        $cnpj = preg_replace('/[^0-9]/', '', $dados['cnpj'] ?? '');
        if ($cnpj && !Validator::cnpj($cnpj)) $erros[] = 'CNPJ inválido';

        $uf = strtoupper(trim($dados['uf'] ?? ''));
        if ($uf && !Validator::uf($uf)) $erros[] = 'UF deve ter 2 letras maiúsculas';

        $email = trim($dados['email'] ?? '');
        if ($email && !Validator::email($email)) $erros[] = 'E-mail inválido';

        if ($cnpj) {
            $pdo = db();
            $stmt = $pdo->prepare('SELECT id FROM empresas WHERE cnpj = ?');
            $stmt->execute([$cnpj]);
            if ($stmt->fetch()) $erros[] = 'Já existe empresa com este CNPJ';
        }

        if ($erros) return ['ok' => false, 'erros' => $erros];

        $pdo = db();
        $stmt = $pdo->prepare('
            INSERT INTO empresas (razao_social, nome_fantasia, cnpj, inscricao_estadual, endereco, cidade, uf, cep, telefone, email)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $razao,
            trim($dados['nome_fantasia'] ?? '') ?: null,
            $cnpj ?: null,
            trim($dados['inscricao_estadual'] ?? '') ?: null,
            trim($dados['endereco'] ?? '') ?: null,
            trim($dados['cidade'] ?? '') ?: null,
            $uf ?: null,
            trim($dados['cep'] ?? '') ?: null,
            trim($dados['telefone'] ?? '') ?: null,
            $email ?: null,
        ]);

        $id = (int)$pdo->lastInsertId();
        self::log('CRIAR_EMPRESA', "Empresa #$id - $razao");
        return ['ok' => true, 'id' => $id];
    }

    public static function atualizar(int $id, array $dados): array {
        Permissao::require('gerenciar_cadastros');

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM empresas WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) return ['ok' => false, 'erros' => ['Empresa não encontrada']];

        $erros = [];
        $razao = trim($dados['razao_social'] ?? '');
        if ($razao === '') $erros[] = 'Razão social é obrigatória';

        $cnpj = preg_replace('/[^0-9]/', '', $dados['cnpj'] ?? '');
        if ($cnpj && !Validator::cnpj($cnpj)) $erros[] = 'CNPJ inválido';

        $uf = strtoupper(trim($dados['uf'] ?? ''));
        if ($uf && !Validator::uf($uf)) $erros[] = 'UF deve ter 2 letras maiúsculas';

        if ($cnpj) {
            $stmt = $pdo->prepare('SELECT id FROM empresas WHERE cnpj = ? AND id != ?');
            $stmt->execute([$cnpj, $id]);
            if ($stmt->fetch()) $erros[] = 'Já existe outra empresa com este CNPJ';
        }

        if ($erros) return ['ok' => false, 'erros' => $erros];

        $stmt = $pdo->prepare('
            UPDATE empresas SET
                razao_social = ?, nome_fantasia = ?, cnpj = ?, inscricao_estadual = ?,
                endereco = ?, cidade = ?, uf = ?, cep = ?, telefone = ?, email = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $razao,
            trim($dados['nome_fantasia'] ?? '') ?: null,
            $cnpj ?: null,
            trim($dados['inscricao_estadual'] ?? '') ?: null,
            trim($dados['endereco'] ?? '') ?: null,
            trim($dados['cidade'] ?? '') ?: null,
            $uf ?: null,
            trim($dados['cep'] ?? '') ?: null,
            trim($dados['telefone'] ?? '') ?: null,
            trim($dados['email'] ?? '') ?: null,
            $id,
        ]);

        self::log('ATUALIZAR_EMPRESA', "Empresa #$id - $razao");
        return ['ok' => true];
    }

    public static function ativarDesativar(int $id, bool $ativo): array {
        Permissao::require('gerenciar_cadastros');
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE empresas SET ativo = ? WHERE id = ?');
        $stmt->execute([$ativo ? 1 : 0, $id]);
        self::log($ativo ? 'ATIVAR_EMPRESA' : 'DESATIVAR_EMPRESA', "Empresa #$id");
        return ['ok' => true];
    }

    private static function log(string $op, string $desc): void {
        $pdo = db();
        $pdo->prepare('INSERT INTO log_operacoes (usuario_id, operacao, descricao) VALUES (?, ?, ?)')
            ->execute([Auth::user()['id'], $op, $desc]);
    }
}