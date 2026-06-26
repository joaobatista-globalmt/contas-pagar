<?php
// /home/sistema/contas-pagar/src/controllers/RecorrenciaController.php

require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../lib/Permissao.php';

class RecorrenciaController {

    public static function listar(): array {
        $empresaId = Auth::empresaAtualId();
        if (!$empresaId) return [];

        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT r.*,
                   f.nome AS fornecedor_nome,
                   c.nome AS categoria_nome,
                   (SELECT COUNT(*) FROM contas_pagar WHERE recorrencia_id = r.id) AS qtd_geradas
            FROM contas_recorrencia r
            LEFT JOIN fornecedores f ON f.id = r.fornecedor_id
            LEFT JOIN categorias c ON c.id = r.categoria_id
            WHERE r.empresa_id = ?
            ORDER BY r.ativa DESC, r.descricao
        ");
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    public static function obter(int $id): ?array {
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('SELECT * FROM contas_recorrencia WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresaId]);
        return $stmt->fetch() ?: null;
    }

    public static function criar(array $dados): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();

        $erros = self::validar($dados);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $pdo = db();
        $stmt = $pdo->prepare("
            INSERT INTO contas_recorrencia (
                empresa_id, fornecedor_id, categoria_id, descricao, valor,
                dia_vencimento, periodicidade, data_inicio, data_fim, ativa, observacoes,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $empresaId,
            !empty($dados['fornecedor_id']) ? (int)$dados['fornecedor_id'] : null,
            !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null,
            trim($dados['descricao']),
            self::valorParaDecimal($dados['valor']),
            (int)$dados['dia_vencimento'],
            $dados['periodicidade'] ?? 'MENSAL',
            $dados['data_inicio'],
            !empty($dados['data_fim']) ? $dados['data_fim'] : null,
            !empty($dados['ativa']) ? 1 : 0,
            trim($dados['observacoes'] ?? '') ?: null,
            Auth::user()['id'],
        ]);

        $id = (int)$pdo->lastInsertId();
        self::log('CRIAR_RECORRENCIA', "Recorrencia #$id - " . trim($dados['descricao']));
        return ['ok' => true, 'id' => $id];
    }

    public static function atualizar(int $id, array $dados): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();

        $pdo = db();
        $stmt = $pdo->prepare('SELECT id FROM contas_recorrencia WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$id, $empresaId]);
        if (!$stmt->fetch()) return ['ok' => false, 'erros' => ['Recorrência não encontrada']];

        $erros = self::validar($dados);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $stmt = $pdo->prepare("
            UPDATE contas_recorrencia SET
                fornecedor_id = ?, categoria_id = ?, descricao = ?, valor = ?,
                dia_vencimento = ?, periodicidade = ?, data_inicio = ?, data_fim = ?,
                ativa = ?, observacoes = ?
            WHERE id = ? AND empresa_id = ?
        ");
        $stmt->execute([
            !empty($dados['fornecedor_id']) ? (int)$dados['fornecedor_id'] : null,
            !empty($dados['categoria_id']) ? (int)$dados['categoria_id'] : null,
            trim($dados['descricao']),
            self::valorParaDecimal($dados['valor']),
            (int)$dados['dia_vencimento'],
            $dados['periodicidade'] ?? 'MENSAL',
            $dados['data_inicio'],
            !empty($dados['data_fim']) ? $dados['data_fim'] : null,
            !empty($dados['ativa']) ? 1 : 0,
            trim($dados['observacoes'] ?? '') ?: null,
            $id,
            $empresaId,
        ]);

        self::log('ATUALIZAR_RECORRENCIA', "Recorrencia #$id - " . trim($dados['descricao']));
        return ['ok' => true];
    }

    public static function ativarDesativar(int $id, bool $ativo): array {
        Permissao::require('gerenciar_cadastros');
        $empresaId = Auth::empresaAtualId();
        $pdo = db();
        $stmt = $pdo->prepare('UPDATE contas_recorrencia SET ativa = ? WHERE id = ? AND empresa_id = ?');
        $stmt->execute([$ativo ? 1 : 0, $id, $empresaId]);
        self::log($ativo ? 'ATIVAR_RECORRENCIA' : 'DESATIVAR_RECORRENCIA', "Recorrencia #$id");
        return ['ok' => true];
    }

    /**
     * Gera contas do mes para todas as recorrencias ativas da empresa.
     * Retorna array com info do que foi gerado.
     */
    public static function gerarMes(?string $mes = null): array {
        Permissao::require('gerar_recorrencia');
        $empresaId = Auth::empresaAtualId();

        $mes = $mes ?: date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            return ['ok' => false, 'msg' => 'Mês inválido (use formato AAAA-MM)'];
        }

        $pdo = db();
        $stmt = $pdo->prepare("
            SELECT * FROM contas_recorrencia
            WHERE empresa_id = ? AND ativa = 1
            ORDER BY descricao
        ");
        $stmt->execute([$empresaId]);
        $recorrencias = $stmt->fetchAll();

        $geradas = 0;
        $puladas = 0;
        $detalhes = [];

        $pdo->beginTransaction();

        try {
            foreach ($recorrencias as $r) {
                // 1. Verifica periodicidade: só gera se a periodicidade permite este mes
                if (!self::deveGerar($r, $mes)) {
                    $puladas++;
                    $detalhes[] = [
                        'id' => $r['id'],
                        'descricao' => $r['descricao'],
                        'acao' => 'pulada',
                        'motivo' => 'Periodicidade não inclui este mês',
                    ];
                    continue;
                }

                // 2. Verifica se ja gerou neste mes
                if ($r['ultima_geracao'] === $mes) {
                    $puladas++;
                    $detalhes[] = [
                        'id' => $r['id'],
                        'descricao' => $r['descricao'],
                        'acao' => 'pulada',
                        'motivo' => 'Já gerada neste mês',
                    ];
                    continue;
                }

                // 3. Verifica data_fim
                if ($r['data_fim'] && $mes > substr($r['data_fim'], 0, 7)) {
                    $puladas++;
                    $detalhes[] = [
                        'id' => $r['id'],
                        'descricao' => $r['descricao'],
                        'acao' => 'pulada',
                        'motivo' => 'Recorrência encerrada',
                    ];
                    continue;
                }

                // 4. Calcula data de vencimento (dia_vencimento no mes/ano)
                $vencimento = self::calcularVencimento($r, $mes);

                // 5. Cria a conta
                $stmtC = $pdo->prepare("
                    INSERT INTO contas_pagar (
                        empresa_id, fornecedor_id, categoria_id, descricao, numero_documento,
                        valor, data_vencimento, forma_pagamento, observacoes,
                        status, created_by, eh_recorrente, recorrencia_id
                    ) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, 'PENDENTE', ?, 1, ?)
                ");
                $descMes = $r['descricao'] . ' (' . self::mesPtBr($mes) . ')';
                $stmtC->execute([
                    $empresaId,
                    $r['fornecedor_id'],
                    $r['categoria_id'],
                    $descMes,
                    $r['valor'],
                    $vencimento,
                    null, // forma_pagamento definida no pagamento
                    'Gerada automaticamente da recorrência #' . $r['id'],
                    Auth::user()['id'],
                    $r['id'],
                ]);

                // 6. Atualiza ultima_geracao
                $pdo->prepare('UPDATE contas_recorrencia SET ultima_geracao = ? WHERE id = ?')
                    ->execute([$mes, $r['id']]);

                $geradas++;
                $detalhes[] = [
                    'id' => $r['id'],
                    'descricao' => $r['descricao'],
                    'acao' => 'gerada',
                    'vencimento' => $vencimento,
                    'valor' => $r['valor'],
                    'conta_id' => (int)$pdo->lastInsertId(),
                ];
            }

            $pdo->commit();
            self::log('GERAR_RECORRENCIAS', "Geradas $geradas contas do mes $mes, $puladas puladas");
            return [
                'ok' => true,
                'geradas' => $geradas,
                'puladas' => $puladas,
                'mes' => $mes,
                'detalhes' => $detalhes,
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => 'Erro: ' . $e->getMessage()];
        }
    }

    /**
     * Verifica se a periodicidade permite gerar este mes
     */
    private static function deveGerar(array $r, string $mes): bool {
        $inicio = $r['data_inicio']; // YYYY-MM-DD
        $inicioMes = substr($inicio, 0, 7);
        if ($mes < $inicioMes) return false;

        $diffMeses = (date('Y', strtotime($mes . '-01')) - date('Y', strtotime($inicio))) * 12
                   + (date('m', strtotime($mes . '-01')) - date('m', strtotime($inicio)));

        switch ($r['periodicidade']) {
            case 'MENSAL': return true;
            case 'BIMESTRAL': return $diffMeses % 2 === 0;
            case 'TRIMESTRAL': return $diffMeses % 3 === 0;
            case 'SEMESTRAL': return $diffMeses % 6 === 0;
            case 'ANUAL': return $diffMeses % 12 === 0;
            default: return false;
        }
    }

    /**
     * Calcula data de vencimento (dia_vencimento do mes)
     * Ajusta para ultimo dia se mes nao tem o dia
     */
    private static function calcularVencimento(array $r, string $mes): string {
        $dia = (int)$r['dia_vencimento'];
        $ano = (int)substr($mes, 0, 4);
        $mesNum = (int)substr($mes, 5, 2);
        $ultimoDia = (int)date('t', strtotime(sprintf('%04d-%02d-01', $ano, $mesNum)));
        $diaFinal = min($dia, $ultimoDia);
        return sprintf('%04d-%02d-%02d', $ano, $mesNum, $diaFinal);
    }

    private static function mesPtBr(string $mes): string {
        $partes = explode('-', $mes);
        $meses = ['', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        return $meses[(int)$partes[1]] . '/' . substr($partes[0], -2);
    }

    private static function valorParaDecimal($valor): float {
        if (is_numeric($valor)) return (float)$valor;
        $v = preg_replace('/[^0-9,.]/', '', (string)$valor);
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
        return (float)$v;
    }

    private static function validar(array $dados): array {
        $erros = [];
        $desc = trim($dados['descricao'] ?? '');
        if ($desc === '') $erros[] = 'Descrição é obrigatória';

        $valor = self::valorParaDecimal($dados['valor'] ?? 0);
        if ($valor <= 0) $erros[] = 'Valor deve ser maior que zero';

        $dia = (int)($dados['dia_vencimento'] ?? 0);
        if ($dia < 1 || $dia > 31) $erros[] = 'Dia de vencimento deve ser entre 1 e 31';

        $per = $dados['periodicidade'] ?? '';
        if (!in_array($per, PERIODICIDADE)) $erros[] = 'Periodicidade inválida';

        if (empty($dados['data_inicio'])) $erros[] = 'Data de início é obrigatória';
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_inicio'])) $erros[] = 'Data de início inválida';

        if (!empty($dados['data_fim']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dados['data_fim'])) {
            $erros[] = 'Data de fim inválida';
        }
        if (!empty($dados['data_fim']) && $dados['data_fim'] <= $dados['data_inicio']) {
            $erros[] = 'Data de fim deve ser após data de início';
        }

        return $erros;
    }

    private static function log(string $op, string $desc): void {
        $pdo = db();
        $pdo->prepare('INSERT INTO log_operacoes (empresa_id, usuario_id, operacao, descricao) VALUES (?, ?, ?, ?)')
            ->execute([Auth::empresaAtualId(), Auth::user()['id'], $op, $desc]);
    }
}