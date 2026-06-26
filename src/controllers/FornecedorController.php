<?php
// /home/sistema/contas-pagar/src/controllers/FornecedorController.php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Permissao.php';
require_once __DIR__ . '/../lib/Validator.php';

class FornecedorController {

    public static function listar(?string $busca = null): array {
        // Nao precisa de permissao especial - qualquer user da empresa pode ver
        $empresaId = Auth::empresaAtualId();
        if (!$empresaId) return [];

        $pdo = db();
        $sql = 'SELECT f.*,
                       (SELECT COUNT(*) FROM contas_pagar WHERE fornecedor_id = f.id) AS qtd_contas
                FROM fornecedores f
                WHERE f.empresa_id = ?';
        $params = [$empresaId];

        if ($busca) {
            $sql .= ' AND (f.nome LIKE ? OR f.cnpj_cpf LIKE ?)';
            $params[] = "%$busca%";
            $params[] = "%$busca%";
        }
        $sql .= ' ORDER BY f.nome';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function obter(int $id): ?array {
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM fornecedores WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresaId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function criar(array $dados): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();

        $erros = self::validar($dados);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $pdo = db();
        $stmt = $pdo->prepare('
            INSERT INTO fornecedores (empresa_id, nome, tipo_pessoa, cnpj_cpf, email, telefone,
                                     banco, agencia, conta, pix, observacoes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $empresaId,
            trim($dados['nome']),
            $dados['tipo_pessoa'] ?? 'J',
            self::limparDoc($dados['cnpj_cpf'] ?? ''),
            trim($dados['email'] ?? '') ?: null,
            trim($dados['telefone'] ?? '') ?: null,
            trim($dados['banco'] ?? '') ?: null,
            trim($dados['agencia'] ?? '') ?: null,
            trim($dados['conta'] ?? '') ?: null,
            trim($dados['pix'] ?? '') ?: null,
            trim($dados['observacoes'] ?? '') ?: null,
        ]);

        $id = (int)$pdo->lastInsertId();
        self::log('CRIAR_FORNECEDOR', "Fornecedor #$id - " . trim($dados['nome']));
        return ['ok' => true, 'id' => $id];
    }

    public static function atualizar(int $id, array $dados): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();

        // Verifica que fornecedor pertence a empresa
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM fornecedores WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresaId]);
        if (!$stmt->fetch()) return ['ok' => false, 'erros' => ['Fornecedor não encontrado']];

        $erros = self::validar($dados);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $stmt = $pdo->prepare('
            UPDATE fornecedores SET
                nome = ?, tipo_pessoa = ?, cnpj_cpf = ?, email = ?, telefone = ?,
                banco = ?, agencia = ?, conta = ?, pix = ?, observacoes = ?
            WHERE id = ? AND empresa_id = ?
        ');
        $stmt->execute([
            trim($dados['nome']),
            $dados['tipo_pessoa'] ?? 'J',
            self::limparDoc($dados['cnpj_cpf'] ?? ''),
            trim($dados['email'] ?? '') ?: null,
            trim($dados['telefone'] ?? '') ?: null,
            trim($dados['banco'] ?? '') ?: null,
            trim($dados['agencia'] ?? '') ?: null,
            trim($dados['conta'] ?? '') ?: null,
            trim($dados['pix'] ?? '') ?: null,
            trim($dados['observacoes'] ?? '') ?: null,
            $id,
            $empresaId,
        ]);

        self::log('ATUALIZAR_FORNECEDOR', "Fornecedor #$id - " . trim($dados['nome']));
        return ['ok' => true];
    }

    public static function ativarDesativar(int $id, bool $ativo): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE fornecedores SET ativo = ? WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$ativo ? 1 : 0, $id, $empresaId]);
        self::log($ativo ? 'ATIVAR_FORNECEDOR' : 'DESATIVAR_FORNECEDOR', "Fornecedor #$id");
        return ['ok' => true];
    }

    public static function listarAtivos(): array {
        // Retorna so fornecedores ativos (pra usar em dropdowns de contas)
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, nome, cnpj_cpf FROM fornecedores WHERE empresa_id = ? AND ativo = 1 ORDER BY nome');
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    private static function validar(array $dados): array {
        $erros = [];
        $nome = trim($dados['nome'] ?? '');
        if ($nome === '') $erros[] = 'Nome é obrigatório';

        $tipo = $dados['tipo_pessoa'] ?? 'J';
        if (!in_array($tipo, ['F', 'J'])) $erros[] = 'Tipo de pessoa inválido';

        $doc = self::limparDoc($dados['cnpj_cpf'] ?? '');
        if ($doc !== '') {
            if ($tipo === 'F' && !Validator::cpf($doc)) $erros[] = 'CPF inválido';
            if ($tipo === 'J' && !Validator::cnpj($doc)) $erros[] = 'CNPJ inválido';
        }

        $email = trim($dados['email'] ?? '');
        if ($email && !Validator::email($email)) $erros[] = 'E-mail inválido';

        return $erros;
    }

    private static function limparDoc(string $doc): string {
        return preg_replace('/[^0-9]/', '', $doc);
    }

    private static function log(string $op, string $desc): void {
        $pdo = db();
        $pdo->prepare('INSERT INTO log_operacoes (empresa_id, usuario_id, operacao, descricao) VALUES (?, ?, ?, ?)')
            ->execute([Auth::empresaAtualId(), Auth::user()['id'], $op, $desc]);
    }
}