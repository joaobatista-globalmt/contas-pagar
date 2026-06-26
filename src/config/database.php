<?php
// /home/sistema/contas-pagar/src/config/database.php

function getConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'contas_pagar';
        $user = getenv('DB_USER') ?: 'contas_app';
        $pass = getenv('DB_PASS') ?: 'contas_app_2026';

        try {
            $pdo = new PDO(
                "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('Erro de conexao DB: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode(['erro' => 'Erro ao conectar ao banco']));
        }
    }
    return $pdo;
}

function db(): PDO {
    return getConnection();
}