<?php
// /home/sistema/contas-pagar/src/lib/CsvExporter.php

class CsvExporter {

    /**
     * Gera CSV a partir de array de dados
     */
    public static function gerar(array $dados, array $colunas, string $nomeArquivo): void {
        $nomeArquivo = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nomeArquivo);

        // Headers HTTP
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomeArquivo . '.csv"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');

        // BOM pra Excel reconhecer UTF-8
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

        // Cabecalho
        $cabecalho = array_map(fn($c) => $c['label'], $colunas);
        fputcsv($out, $cabecalho, ';');

        // Dados
        foreach ($dados as $linha) {
            $row = [];
            foreach ($colunas as $col) {
                $key = $col['key'];
                $valor = $linha[$key] ?? '';

                // Formatacao custom
                if (isset($col['format']) && is_callable($col['format'])) {
                    $valor = $col['format']($valor, $linha);
                }

                $row[] = $valor;
            }
            fputcsv($out, $row, ';');
        }

        fclose($out);
        exit;
    }
}