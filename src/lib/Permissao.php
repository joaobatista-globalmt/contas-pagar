<?php
// /home/sistema/contas-pagar/src/lib/Permissao.php

require_once __DIR__ . '/Auth.php';

class Permissao {
    /**
     * Checagens por perfil:
     * admin       → tudo
     * operador    → criar/editar contas, ver relatorios
     * aprovador   → tudo do operador + aprovar contas
     * pagador     → tudo do operador + pagar contas
     * visualizador → so leitura
     */
    public static function pode(string $acao): bool {
        $perfil = Auth::perfilNaEmpresaAtual();

        switch ($acao) {
            case 'criar_conta':
            case 'editar_conta':
            case 'excluir_conta':
                return in_array($perfil, ['admin', 'operador']);

            case 'aprovar_conta':
                return in_array($perfil, ['admin', 'aprovador']);

            case 'pagar_conta':
                return in_array($perfil, ['admin', 'pagador']);

            case 'cancelar_conta':
                return in_array($perfil, ['admin', 'aprovador', 'pagador']);

            case 'gerenciar_cadastros': // fornecedores/categorias/empresas
                return $perfil === 'admin';

            case 'gerenciar_usuarios':
                return $perfil === 'admin';

            case 'ver_relatorios':
                return in_array($perfil, ['admin', 'operador', 'aprovador', 'pagador', 'visualizador']);

            case 'gerar_recorrencia':
                return in_array($perfil, ['admin', 'operador']);

            case 'anexar_arquivo':
                return in_array($perfil, ['admin', 'operador', 'aprovador', 'pagador']);

            default:
                return false;
        }
    }

    public static function require(string $acao): void {
        if (!self::pode($acao)) {
            http_response_code(403);
            echo json_encode(['erro' => 'Sem permissão para esta ação']);
            exit;
        }
    }
}