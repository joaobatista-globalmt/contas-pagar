<?php
// /home/sistema/contas-pagar/src/lib/Uploader.php

class Uploader {

    const TIPOS_PERMITIDOS = ['application/pdf'];
    const TAMANHO_MAXIMO = 10 * 1024 * 1024; // 10MB

    /**
     * Faz upload de um arquivo PDF
     */
    public static function uploadPdf(array $file, int $empresaId, int $contaId, int $usuarioId): array {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'msg' => 'Erro no upload: código ' . ($file['error'] ?? 'desconhecido')];
        }

        if ($file['size'] > self::TAMANHO_MAXIMO) {
            $mb = round($file['size'] / 1024 / 1024, 2);
            return ['ok' => false, 'msg' => "Arquivo muito grande ($mb MB). Máximo: 10 MB"];
        }

        // Valida MIME real (não só extensão)
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::TIPOS_PERMITIDOS)) {
            return ['ok' => false, 'msg' => "Tipo de arquivo não permitido: $mime. Apenas PDF"];
        }

        // Valida magic bytes (primeiros 4 bytes de um PDF: %PDF)
        $handle = fopen($file['tmp_name'], 'rb');
        $magic = fread($handle, 4);
        fclose($handle);
        if ($magic !== '%PDF') {
            return ['ok' => false, 'msg' => 'Arquivo não é um PDF válido (magic bytes incorretos)'];
        }

        // Cria estrutura de diretorios
        $dir = UPLOAD_PATH . "/anexos/$empresaId/$contaId";
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Nome do arquivo: timestamp + sanitized original
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nomeOriginal = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $nomeArquivo = date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 6) . '_' . $nomeOriginal . '.pdf';
        $caminhoCompleto = "$dir/$nomeArquivo";
        $caminhoRelativo = "anexos/$empresaId/$contaId/$nomeArquivo";

        if (!move_uploaded_file($file['tmp_name'], $caminhoCompleto)) {
            return ['ok' => false, 'msg' => 'Erro ao salvar arquivo'];
        }

        chmod($caminhoCompleto, 0644);

        // Salva no banco
        $pdo = db();
        $stmt = $pdo->prepare('
            INSERT INTO anexos (conta_id, nome_arquivo, caminho_arquivo, tamanho_bytes, tipo_mime, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $contaId,
            $file['name'],
            $caminhoRelativo,
            $file['size'],
            $mime,
            $usuarioId,
        ]);

        return [
            'ok' => true,
            'id' => (int)$pdo->lastInsertId(),
            'nome' => $file['name'],
            'caminho' => $caminhoRelativo,
            'tamanho' => $file['size'],
        ];
    }

    /**
     * Lista anexos de uma conta
     */
    public static function listarPorConta(int $contaId): array {
        $pdo = db();
        $stmt = $pdo->prepare('
            SELECT a.*, u.nome AS uploaded_by_nome
            FROM anexos a
            LEFT JOIN usuarios u ON u.id = a.uploaded_by
            WHERE a.conta_id = ?
            ORDER BY a.uploaded_at DESC
        ');
        $stmt->execute([$contaId]);
        return $stmt->fetchAll();
    }

    /**
     * Exclui anexo (arquivo + registro)
     * Apenas admin ou o proprio uploaded_by podem excluir
     */
    public static function excluir(int $anexoId, int $empresaId, int $userId, string $perfil): array {
        $pdo = db();
        // Valida que anexo pertence a uma conta da empresa
        $stmt = $pdo->prepare('
            SELECT a.* FROM anexos a
            INNER JOIN contas_pagar cp ON cp.id = a.conta_id
            WHERE a.id = ? AND cp.empresa_id = ?
        ');
        $stmt->execute([$anexoId, $empresaId]);
        $anexo = $stmt->fetch();

        if (!$anexo) {
            return ['ok' => false, 'msg' => 'Anexo não encontrado'];
        }

        // Permissao: admin pode tudo; outros soh o proprio
        if ($perfil !== 'admin' && $anexo['uploaded_by'] != $userId) {
            return ['ok' => false, 'msg' => 'Você só pode excluir anexos que você mesmo enviou'];
        }

        // Remove arquivo fisico
        $caminhoCompleto = UPLOAD_PATH . '/' . $anexo['caminho_arquivo'];
        if (file_exists($caminhoCompleto)) {
            unlink($caminhoCompleto);
        }

        // Remove registro
        $pdo->prepare('DELETE FROM anexos WHERE id = ?')->execute([$anexoId]);

        return ['ok' => true];
    }

    /**
     * Formata tamanho do arquivo
     */
    public static function formatTamanho(int $bytes): string {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}