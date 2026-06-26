<?php
// /home/sistema/contas-pagar/src/controllers/RelatorioController.php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Permissao.php';

class RelatorioController {

    /**
     * Relatorio por periodo: totais + detalhes das contas
     */
    public static function porPeriodo(string $dataInicio, string $dataFim, ?string $status = null): array {
        Permissao::require('ver_relatorios');
        $empresaId = Auth::empresaAtualId();

        $pdo = db();
        $sql = "
            SELECT cp.*,
                   f.nome AS fornecedor_nome,
                   c.nome AS categoria_nome, c.cor AS categoria_cor
            FROM contas_pagar cp
            LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
            LEFT JOIN categorias c ON c.id = cp.categoria_id
            WHERE cp.empresa_id = ?
              AND cp.data_vencimento BETWEEN ? AND ?
        ";
        $params = [$empresaId, $dataInicio, $dataFim];

        if ($status) {
            $sql .= ' AND cp.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY cp.data_vencimento, cp.id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $contas = $stmt->fetchAll();

        $totais = [
            'qtd' => count($contas),
            'valor_total' => array_sum(array_column($contas, 'valor')),
            'valor_pago' => array_sum(array_column($contas, 'valor_pago')),
            'valor_pendente' => 0,
            'valor_atrasado' => 0,
            'qtd_pagas' => 0,
            'qtd_pendentes' => 0,
            'qtd_atrasadas' => 0,
        ];
        foreach ($contas as $c) {
            if ($c['status'] === 'PAGA') {
                $totais['qtd_pagas']++;
            } elseif ($c['status'] === 'ATRASADA') {
                $totais['qtd_atrasadas']++;
                $totais['valor_atrasado'] += $c['valor'];
            } else {
                $totais['qtd_pendentes']++;
                $totais['valor_pendente'] += $c['valor'];
            }
        }

        return [
            'contas' => $contas,
            'totais' => $totais,
            'periodo' => ['inicio' => $dataInicio, 'fim' => $dataFim],
        ];
    }

    /**
     * Relatorio por categoria: total gasto por categoria no periodo
     */
    public static function porCategoria(string $dataInicio, string $dataFim): array {
        Permissao::require('ver_relatorios');
        $empresaId = Auth::empresaAtualId();

        $pdo = db();
        $sql = "
            SELECT
                COALESCE(c.id, 0) AS categoria_id,
                COALESCE(c.nome, '(Sem categoria)') AS categoria_nome,
                COALESCE(c.cor, '#95a5a6') AS categoria_cor,
                COUNT(cp.id) AS qtd_contas,
                SUM(CASE WHEN cp.status = 'PAGA' THEN cp.valor_pago ELSE 0 END) AS valor_pago,
                SUM(CASE WHEN cp.status IN ('PENDENTE','APROVADA') THEN cp.valor ELSE 0 END) AS valor_pendente,
                SUM(CASE WHEN cp.status = 'ATRASADA' THEN cp.valor ELSE 0 END) AS valor_atrasado,
                SUM(CASE WHEN cp.status = 'CANCELADA' THEN 0 ELSE cp.valor END) AS valor_total
            FROM contas_pagar cp
            LEFT JOIN categorias c ON c.id = cp.categoria_id
            WHERE cp.empresa_id = ?
              AND cp.data_vencimento BETWEEN ? AND ?
              AND cp.status != 'CANCELADA'
            GROUP BY c.id, c.nome, c.cor
            ORDER BY valor_total DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresaId, $dataInicio, $dataFim]);
        return $stmt->fetchAll();
    }

    /**
     * Relatorio por fornecedor
     */
    public static function porFornecedor(string $dataInicio, string $dataFim): array {
        Permissao::require('ver_relatorios');
        $empresaId = Auth::empresaAtualId();

        $pdo = db();
        $sql = "
            SELECT
                COALESCE(f.id, 0) AS fornecedor_id,
                COALESCE(f.nome, '(Sem fornecedor)') AS fornecedor_nome,
                COUNT(cp.id) AS qtd_contas,
                SUM(CASE WHEN cp.status = 'PAGA' THEN cp.valor_pago ELSE 0 END) AS valor_pago,
                SUM(CASE WHEN cp.status IN ('PENDENTE','APROVADA') THEN cp.valor ELSE 0 END) AS valor_pendente,
                SUM(CASE WHEN cp.status = 'ATRASADA' THEN cp.valor ELSE 0 END) AS valor_atrasado,
                SUM(CASE WHEN cp.status = 'CANCELADA' THEN 0 ELSE cp.valor END) AS valor_total
            FROM contas_pagar cp
            LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
            WHERE cp.empresa_id = ?
              AND cp.data_vencimento BETWEEN ? AND ?
              AND cp.status != 'CANCELADA'
            GROUP BY f.id, f.nome
            ORDER BY valor_total DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresaId, $dataInicio, $dataFim]);
        return $stmt->fetchAll();
    }

    /**
     * Fluxo de caixa: projecao dos proximos N dias
     */
    public static function fluxoCaixa(int $dias = 90): array {
        Permissao::require('ver_relatorios');
        $empresaId = Auth::empresaAtualId();
        $hoje = date('Y-m-d');
        $futuro = date('Y-m-d', strtotime("+$dias days"));

        $pdo = db();

        // Agrupa por dia de vencimento
        $sql = "
            SELECT
                data_vencimento,
                COUNT(*) AS qtd,
                SUM(valor) AS valor
            FROM contas_pagar
            WHERE empresa_id = ?
              AND status IN ('PENDENTE','APROVADA','ATRASADA')
              AND data_vencimento BETWEEN ? AND ?
            GROUP BY data_vencimento
            ORDER BY data_vencimento
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresaId, $hoje, $futuro]);
        $porDia = $stmt->fetchAll();

        // Totais por semana pra visualizacao macro
        $porSemana = [];
        $sql = "
            SELECT
                YEARWEEK(data_vencimento, 1) AS ano_semana,
                MIN(data_vencimento) AS inicio_semana,
                MAX(data_vencimento) AS fim_semana,
                COUNT(*) AS qtd,
                SUM(valor) AS valor
            FROM contas_pagar
            WHERE empresa_id = ?
              AND status IN ('PENDENTE','APROVADA','ATRASADA')
              AND data_vencimento BETWEEN ? AND ?
            GROUP BY YEARWEEK(data_vencimento, 1)
            ORDER BY ano_semana
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresaId, $hoje, $futuro]);
        $porSemana = $stmt->fetchAll();

        // Totais por mes pra visualizacao macro
        $porMes = [];
        $sql = "
            SELECT
                DATE_FORMAT(data_vencimento, '%Y-%m') AS ano_mes,
                COUNT(*) AS qtd,
                SUM(valor) AS valor
            FROM contas_pagar
            WHERE empresa_id = ?
              AND status IN ('PENDENTE','APROVADA','ATRASADA')
              AND data_vencimento BETWEEN ? AND ?
            GROUP BY DATE_FORMAT(data_vencimento, '%Y-%m')
            ORDER BY ano_mes
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresaId, $hoje, $futuro]);
        $porMes = $stmt->fetchAll();

        return [
            'porDia' => $porDia,
            'porSemana' => $porSemana,
            'porMes' => $porMes,
            'total_periodo' => array_sum(array_column($porDia, 'valor')),
            'dias' => $dias,
        ];
    }

    /**
     * Lista contas atrasadas com detalhes
     */
    public static function atrasadas(): array {
        Permissao::require('ver_relatorios');
        $empresaId = Auth::empresaAtualId();
        $pdo = db();

        $sql = "
            SELECT cp.*,
                   f.nome AS fornecedor_nome, f.email AS fornecedor_email, f.telefone AS fornecedor_telefone,
                   c.nome AS categoria_nome,
                   DATEDIFF(CURDATE(), cp.data_vencimento) AS dias_atraso
            FROM contas_pagar cp
            LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
            LEFT JOIN categorias c ON c.id = cp.categoria_id
            WHERE cp.empresa_id = ?
              AND cp.status IN ('ATRASADA', 'PENDENTE', 'APROVADA')
              AND cp.data_vencimento < CURDATE()
              AND cp.status != 'CANCELADA'
            ORDER BY cp.data_vencimento ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$empresaId]);
        $contas = $stmt->fetchAll();

        return [
            'contas' => $contas,
            'total_atrasado' => array_sum(array_column($contas, 'valor')),
            'qtd' => count($contas),
        ];
    }
}