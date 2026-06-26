<?php
// /home/sistema/contas-pagar/src/controllers/CategoriaController.php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Permissao.php';

class CategoriaController {

    public static function listar(): array {
        $empresaId = Auth::empresaAtualId();
        if (!$empresaId) return [];

        $pdo = db();
        $stmt = $pdo->prepare('
            SELECT c.*,
                   (SELECT COUNT(*) FROM contas_pagar WHERE categoria_id = c.id) AS qtd_contas
            FROM categorias c
            WHERE c.empresa_id = ?
            ORDER BY c.nome
        ');
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    public static function obter(int $id): ?array {
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM categorias WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresaId]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(array $dados): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();

        $erros = self::validar($dados);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        // Verifica nome duplicado na empresa
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE empresa_id = ? AND LOWER(nome) = LOWER(?)');
        $stmt->execute([$empresaId, trim($dados['nome'])]);
        if ($stmt->fetch()) $erros[] = 'Já existe categoria com este nome';

        if ($erros) return ['ok' => false, 'erros' => $erros];

        $stmt = $pdo->prepare('INSERT INTO categorias (empresa_id, nome, tipo, cor) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $empresaId,
            trim($dados['nome']),
            $dados['tipo'] ?? 'DESPESA',
            self::normalizarCor($dados['cor'] ?? '#3498db'),
        ]);

        $id = (int)$pdo->lastInsertId();
        self::log('CRIAR_CATEGORIA', "Categoria #$id - " . trim($dados['nome']));
        return ['ok' => true, 'id' => $id];
    }

    public static function atualizar(int $id, array $dados): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresaId]);
        if (!$stmt->fetch()) return ['ok' => false, 'erros' => ['Categoria não encontrada']];

        $erros = self::validar($dados);
        // Verifica duplicado (excluindo o proprio)
        $stmt = $pdo->prepare('SELECT id FROM categorias WHERE empresa_id = ? AND LOWER(nome) = LOWER(?) AND id != ?');
        $stmt->execute([$empresaId, trim($dados['nome']), $id]);
        if ($stmt->fetch()) $erros[] = 'Já existe outra categoria com este nome';

        if ($erros) return ['ok' => false, 'erros' => $erros];

        $stmt = $pdo->prepare('UPDATE categorias SET nome = ?, tipo = ?, cor = ? WHERE id = ? AND empresa_id = ?');
        $stmt->execute([
            trim($dados['nome']),
            $dados['tipo'] ?? 'DESPESA',
            self::normalizarCor($dados['cor'] ?? '#3498db'),
            $id,
            $empresaId,
        ]);

        self::log('ATUALIZAR_CATEGORIA', "Categoria #$id - " . trim($dados['nome']));
        return ['ok' => true];
    }

    public static function ativarDesativar(int $id, bool $ativo): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE categorias SET ativo = ? WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$ativo ? 1 : 0, $id, $empresaId]);
        self::log($ativo ? 'ATIVAR_CATEGORIA' : 'DESATIVAR_CATEGORIA', "Categoria #$id");
        return ['ok' => true];
    }

    public static function listarAtivas(): array {
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('SELECT id, nome, tipo, cor FROM categorias WHERE empresa_id = ? AND ativo = 1 ORDER BY nome');
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    private static function validar(array $dados): array {
        $erros = [];
        $nome = trim($dados['nome'] ?? '');
        if ($nome === '') $erros[] = 'Nome é obrigatório';
        if (strlen($nome) > 100) $erros[] = 'Nome deve ter no máximo 100 caracteres';

        $tipo = $dados['tipo'] ?? 'DESPESA';
        $tiposValidos = ['DESPESA', 'IMPOSTO', 'SERVICO', 'PRODUTO', 'OUTROS'];
        if (!in_array($tipo, $tiposValidos)) $erros[] = 'Tipo inválido';

        $cor = $dados['cor'] ?? '#3498db';
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) $erros[] = 'Cor inválida (use formato #RRGGBB)';

        return $erros;
    }

    private static function normalizarCor(string $cor): string {
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $cor)) return '#3498db';
        return $cor;
    }

    private static function log(string $op, string $desc): void {
        $pdo = db();
        $pdo->prepare('INSERT INTO log_operacoes (empresa_id, usuario_id, operacao, descricao) VALUES (?, ?, ?, ?)')
            ->execute([Auth::empresaAtualId(), Auth::user()['id'], $op, $desc]);
    }
}