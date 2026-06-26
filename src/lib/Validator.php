<?php
// /home/sistema/contas-pagar/src/lib/Validator.php

class Validator {
    /**
     * Valida CNPJ (com ou sem máscara)
     */
    public static function cnpj(string $cnpj): bool {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/^(\d)\1+$/', $cnpj)) return false; // todos iguais

        // Calcula DV
        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $soma = 0;
        for ($i = 0; $i < 12; $i++) $soma += $cnpj[$i] * $pesos1[$i];
        $resto = $soma % 11;
        $dv1 = $resto < 2 ? 0 : 11 - $resto;
        if ((int)$cnpj[12] !== $dv1) return false;

        $soma = 0;
        for ($i = 0; $i < 13; $i++) $soma += $cnpj[$i] * $pesos2[$i];
        $resto = $soma % 11;
        $dv2 = $resto < 2 ? 0 : 11 - $resto;
        return (int)$cnpj[13] === $dv2;
    }

    /**
     * Valida CPF (com ou sem máscara)
     */
    public static function cpf(string $cpf): bool {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\d)\1+$/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) $soma += $cpf[$i] * ($t + 1 - $i);
            $dv = $soma % 11 < 2 ? 0 : 11 - $soma % 11;
            if ((int)$cpf[$t] !== $dv) return false;
        }
        return true;
    }

    /**
     * Valida CNPJ OU CPF dependendo do tamanho
     */
    public static function cnpjOuCpf(string $doc): bool {
        $doc = preg_replace('/[^0-9]/', '', $doc);
        if (strlen($doc) === 11) return self::cpf($doc);
        if (strlen($doc) === 14) return self::cnpj($doc);
        return false;
    }

    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function uf(string $uf): bool {
        return preg_match('/^[A-Z]{2}$/', $uf) === 1;
    }
}