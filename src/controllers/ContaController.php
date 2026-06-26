<?php
// /home/sistema/contas-pagar/src/controllers/ContaController.php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Permissao.php';
require_once __DIR__ . '/../lib/Validator.php';

class ContaController {

    /**
     * Lista contas com filtros
     */
    public static function listar(array $filtros = []): array {
        $empresaId = Auth::empresaAtualId();
        if (!$empresaId) return [];

        $pdo = db();
        $sql = "
            SELECT cp.*,
                   f.nome AS fornecedor_nome,
                   c.nome AS categoria_nome, c.cor AS categoria_cor,
                   DATEDIFF(CURDATE(), cp.data_vencimento) AS dias_vencidos
            FROM contas_pagar cp
            LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
            LEFT JOIN categorias c ON c.id = cp.categoria_id
            WHERE cp.empresa_id = ?
        ";
        $params = [$empresaId];

        // Filtro por status
        if (!empty($filtros['status'])) {
            $sql .= ' AND cp.status = ?';
            $params[] = $filtros['status'];
        }

        // Filtro por periodo
        if (!empty($filtros['data_inicio'])) {
            $sql .= ' AND cp.data_vencimento >= ?';
            $params[] = $filtros['data_inicio'];
        }
        if (!empty($filtros['data_fim'])) {
            $sql .= ' AND cp.data_vencimento <= ?';
            $params[] = $filtros['data_fim'];
        }

        // Filtro por fornecedor
        if (!empty($filtros['fornecedor_id'])) {
            $sql .= ' AND cp.fornecedor_id = ?';
            $params[] = (int)$filtros['fornecedor_id'];
        }

        // Filtro por categoria
        if (!empty($filtros['categoria_id'])) {
            $sql .= ' AND cp.categoria_id = ?';
            $params[] = (int)$filtros['categoria_id'];
        }

        // Busca textual
        if (!empty($filtros['busca'])) {
            $sql .= ' AND (cp.descricao LIKE ? OR cp.numero_documento LIKE ?)';
            $b = '%' . $filtros['busca'] . '%';
            $params[] = $b;
            $params[] = $b;
        }

        $sql .= ' ORDER BY cp.data_vencimento ASC, cp.id ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function obter(int $id): ?array {
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT cp.*,
                   f.nome AS fornecedor_nome, f.cnpj_cpf AS fornecedor_doc, f.email AS fornecedor_email,
                   c.nome AS categoria_nome, c.cor AS categoria_cor,
                   uc.nome AS criado_por_nome,
                   ua.nome AS aprovado_por_nome,
                   up.nome AS pago_por_nome,
                   DATEDIFF(CURDATE(), cp.data_vencimento) AS dias_vencidos
            FROM contas_pagar cp
            LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
            LEFT JOIN categorias c ON c.id = cp.categoria_id
            LEFT JOIN usuarios uc ON uc.id = cp.created_by
            LEFT JOIN usuarios ua ON ua.id = cp.aprovada_por
            LEFT JOIN usuarios up ON up.id = cp.paga_por
            WHERE cp.id = ? AND cp.empresa_id = ?
        ");
        $stmt->execute([$id, $empresaId]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(array $dados): array {
        Permissao::require('criar_conta');
        $empresaId = Auth::empresaAtualId();
        $erros = self::validar($dados);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $pdo = db();
        $stmt = $pdo->prepare("
            INSERT INTO contas_pagar (
                empresa_id, fornecedor_id, categoria_id, descricao, numero_documento,
                valor, data_emissao, data_vencimento, forma_pagamento, observacoes,
                status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDENTE', ?)
        ");
        $stmt->execute([
            $empresaId,
            !empty($dados['fornecedor_id']) ? (int)$dados['fornecedor_id'] : null,
            !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null,
            trim($dados['descricao']),
            trim($dados['numero_documento'] ?? '') ?: null,
            self::valorParaDecimal($dados['valor']),
            !empty($dados['data_emissao']) ? $dados['data_emissao'] : null,
            $dados['data_vencimento'],
            !empty($dados['forma_pagamento']) ? $dados['forma_pagamento'] : null,
            trim($dados['observacoes'] ?? '') ?: null,
            Auth::user()['id'],
        ]);

        $id = (int)$pdo->lastInsertId();
        self::log('CRIAR_CONTA', "Conta #$id - " . trim($dados['descricao']));
        return ['ok' => true, 'id' => $id];
    }

    public static function atualizar(int $id, array $dados): array {
        Permissao::require('editar_conta');
        $empresaId = Auth::empresaAtualId();

        $conta = self::obter($id);
        if (!$conta) return ['ok' => false, 'erros' => ['Conta não encontrada']];
        if ($conta['status'] === 'PAGA') return ['ok' => false, 'erros' => ['Conta já paga não pode ser editada']];
        if ($conta['status'] === 'CANCELADA') return ['ok' => false, 'erros' => ['Conta cancelada não pode ser editada']];

        $erros = self::validar($dados);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $pdo = db();
        $stmt = $pdo->prepare("
            UPDATE contas_pagar SET
                fornecedor_id = ?, categoria_id = ?, descricao = ?, numero_documento = ?,
                valor = ?, data_emissao = ?, data_vencimento = ?, forma_pagamento = ?,
                observacoes = ?
            WHERE id = ? AND empresa_id = ?
        ");
        $stmt->execute([
            !empty($dados['fornecedor_id']) ? (int)$dados['fornecedor_id'] : null,
            !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null,
            trim($dados['descricao']),
            trim($dados['numero_documento'] ?? '') ?: null,
            self::valorParaDecimal($dados['valor']),
            !empty($dados['data_emissao']) ? $dados['data_emissao'] : null,
            $dados['data_vencimento'],
            !empty($dados['forma_pagamento']) ? $dados['forma_pagamento'] : null,
            trim($dados['observacoes'] ?? '') ?: null,
            $id,
            $empresaId,
        ]);

        self::log('ATUALIZAR_CONTA', "Conta #$id - " . trim($dados['descricao']));
        return ['ok' => true];
    }

    public static function aprovar(int $id): array {
        Permissao::require('aprovar_conta');
        $conta = self::obter($id);
        if (!$conta) return ['ok' => false, 'erros' => ['Conta não encontrada']];
        if ($conta['status'] !== 'PENDENTE') return ['ok' => false, 'erros' => ['Apenas contas PENDENTES podem ser aprovadas']];

        $pdo = db();
        $stmt = $pdo->prepare("UPDATE contas_pagar SET status = 'APROVADA', aprovada_por = ?, aprovada_em = NOW() WHERE id = ? AND empresa_id = ?");
        $stmt->execute([Auth::user()['id'], $id, Auth::empresaAtualId()]);
        self::log('APROVAR_CONTA', "Conta #$id");
        return ['ok' => true];
    }

    public static function pagar(int $id, array $dados): array {
        Permissao::require('pagar_conta');
        $conta = self::obter($id);
        if (!$conta) return ['ok' => false, 'erros' => ['Conta não encontrada']];
        if (!in_array($conta['status'], ['PENDENTE', 'APROVADA'])) {
            return ['ok' => false, 'erros' => ['Apenas contas PENDENTES ou APROVADAS podem ser pagas']];
        }
        $erros = [];
        if (empty($dados['data_pagamento'])) $erros[] = 'Data do pagamento é obrigatória';
        if (empty($dados['forma_pagamento'])) $erros[] = 'Forma de pagamento é obrigatória';
        $valorPago = self::valorParaDecimal($dados['valor_pago'] ?? 0);
        if ($valorPago <= 0) $erros[] = 'Valor pago deve ser maior que zero';
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $pdo = db();
        $stmt = $pdo->prepare("UPDATE contas_pagar SET status = 'PAGA', data_pagamento = ?, forma_pagamento = ?, valor_pago = ?, paga_por = ? WHERE id = ? AND empresa_id = ?");
        $stmt->execute([
            $dados['data_pagamento'],
            $dados['forma_pagamento'],
            $valorPago,
            Auth::user()['id'],
            $id,
            Auth::empresaAtualId(),
        ]);
        self::log('PAGAR_CONTA', "Conta #$id - Pago R$ $valorPago via " . $dados['forma_pagamento']);
        return ['ok' => true];
    }

    public static function cancelar(int $id): array {
        Permissao::require('cancelar_conta');
        $conta = self::obter($id);
        if (!$conta) return ['ok' => false, 'erros' => ['Conta não encontrada']];
        if ($conta['status'] === 'PAGA') return ['ok' => false, 'erros' => ['Conta já paga não pode ser cancelada']];

        $pdo = db();
        $stmt = $pdo->prepare("UPDATE contas_pagar SET status = 'CANCELADA' WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, Auth::empresaAtualId()]);
        self::log('CANCELAR_CONTA', "Conta #$id");
        return ['ok' => true];
    }

    public static function excluir(int $id): array {
        Permissao::require('excluir_conta');
        $conta = self::obter($id);
        if (!$conta) return ['ok' => false, 'erros' => ['Conta não encontrada']];
        if ($conta['status'] !== 'PENDENTE') return ['ok' => false, 'erros' => ['Apenas contas PENDENTES podem ser excluídas']];

        $pdo = db();
        $stmt = $pdo->prepare('DELETE FROM contas_pagar WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, Auth::empresaAtualId()]);
        self::log('EXCLUIR_CONTA', "Conta #$id - " . $conta['descricao']);
        return ['ok' => true];
    }

    private static function validar(array $dados): array {
        $erros = [];
        $desc = trim($dados['descricao'] ?? '');
        if ($desc === '') $erros[] = 'Descrição é obrigatória';
        if (strlen($desc) > 255) $erros[] = 'Descrição deve ter no máximo 255 caracteres';

        $valor = self::valorParaDecimal($dados['valor'] ?? 0);
        if ($valor <= 0) $erros[] = 'Valor deve ser maior que zero';

        if (empty($dados['data_vencimento'])) $erros[] = 'Data de vencimento é obrigatória';
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_vencimento'])) $erros[] = 'Data de vencimento inválida';

        $forma = $dados['forma_pagamento'] ?? '';
        if ($forma && !in_array($forma, FORMAS_PAGAMENTO)) $erros[] = 'Forma de pagamento inválida';

        return $erros;
    }

    /**
     * Converte "R$ 1.234,56" ou "1234.56" para decimal "1234.56"
     */
    private static function valorParaDecimal($valor): float {
        if (is_numeric($valor)) return (float)$valor;
        $v = preg_replace('/[^0-9,.]/', '', (string)$valor);
        // Remove pontos de milhar, troca virgula por ponto
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
        return (float)$v;
    }

    private static function log(string $op, string $desc): void {
        $pdo = db();
        $pdo->prepare('INSERT INTO log_operacoes (empresa_id, usuario_id, operacao, descricao) VALUES (?, ?, ?, ?)')
            ->execute([Auth::empresaAtualId(), Auth::user()['id'], $op, $desc]);
    }
}