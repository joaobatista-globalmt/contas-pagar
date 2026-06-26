<?php
// /home/sistema/contas-pagar/src/controllers/DashboardController.php

require_once __DIR__ . '/../lib/Auth.php';

class DashboardController {

    /**
     * Retorna os cards + listas do dashboard
     */
    public static function dados(int $empresaId): array {
        $pdo = db();
        $hoje = date('Y-m-d');
        $semana = date('Y-m-d', strtotime('+7 days'));
        $mes = date('Y-m-01');
        $mesFim = date('Y-m-t');

        // Cards
        $sql = "
            SELECT
                SUM(CASE WHEN status IN ('PENDENTE','APROVADA') AND data_vencimento = :hoje1 THEN valor ELSE 0 END) AS total_hoje,
                SUM(CASE WHEN status IN ('PENDENTE','APROVADA') AND data_vencimento BETWEEN :hoje2 AND :semana THEN valor ELSE 0 END) AS total_semana,
                SUM(CASE WHEN status IN ('PENDENTE','APROVADA') AND data_vencimento BETWEEN :mes AND :mesFim THEN valor ELSE 0 END) AS total_mes,
                SUM(CASE WHEN status = 'ATRASADA' THEN valor ELSE 0 END) AS total_atrasadas,
                COUNT(CASE WHEN status = 'PENDENTE' THEN 1 END) AS qtd_pendentes,
                COUNT(CASE WHEN status = 'APROVADA' THEN 1 END) AS qtd_aprovadas,
                COUNT(CASE WHEN status = 'PAGA' THEN 1 END) AS qtd_pagas,
                COUNT(CASE WHEN status = 'ATRASADA' THEN 1 END) AS qtd_atrasadas
            FROM contas_pagar
            WHERE empresa_id = :empresaId
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':hoje1' => $hoje, ':hoje2' => $hoje,
            ':semana' => $semana, ':mes' => $mes, ':mesFim' => $mesFim,
            ':empresaId' => $empresaId,
        ]);
        $cards = $stmt->fetch();

        // Proximas do vencimento (7 dias)
        $stmt = $pdo->prepare("
            SELECT cp.id, cp.descricao, cp.valor, cp.data_vencimento, cp.status,
                   f.nome AS fornecedor
            FROM contas_pagar cp
            LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
            WHERE cp.empresa_id = ?
              AND cp.status IN ('PENDENTE','APROVADA')
              AND cp.data_vencimento BETWEEN ? AND ?
            ORDER BY cp.data_vencimento ASC
            LIMIT 10
        ");
        $stmt->execute([$empresaId, $hoje, $semana]);
        $proximas = $stmt->fetchAll();

        // Atrasadas
        $stmt = $pdo->prepare("
            SELECT cp.id, cp.descricao, cp.valor, cp.data_vencimento,
                   DATEDIFF(CURDATE(), cp.data_vencimento) AS dias_atraso,
                   f.nome AS fornecedor
            FROM contas_pagar cp
            LEFT JOIN fornecedores f ON f.id = cp.fornecedor_id
            WHERE cp.empresa_id = ?
              AND cp.status = 'ATRASADA'
            ORDER BY cp.data_vencimento ASC
            LIMIT 10
        ");
        $stmt->execute([$empresaId]);
        $atrasadas = $stmt->fetchAll();

        return [
            'cards' => $cards,
            'proximas' => $proximas,
            'atrasadas' => $atrasadas,
        ];
    }
}