<?php
// /home/sistema/contas-pagar/src/config/app.php

// Constantes do sistema
define('APP_NAME', 'Contas a Pagar');
define('APP_VERSION', '1.0.0');

// Paths
define('BASE_PATH', '/home/sistema/contas-pagar');
define('SRC_PATH', BASE_PATH . '/src');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('LOG_PATH', BASE_PATH . '/logs');

// URL
define('BASE_URL', '/contas');  // sem dominio, funciona tanto direto quanto via Nginx

// Perfis permitidos
define('PERFIS', ['admin', 'operador', 'aprovador', 'pagador', 'visualizador']);

// Status de conta
define('STATUS_CONTA', ['PENDENTE', 'APROVADA', 'PAGA', 'ATRASADA', 'CANCELADA']);

// Formas de pagamento
define('FORMAS_PAGAMENTO', ['BOLETO', 'PIX', 'CARTAO_CREDITO', 'CARTAO_DEBITO', 'DINHEIRO', 'TRANSFERENCIA', 'DEBITO_AUTOMATICO', 'CHEQUE']);

// Periodicidade
define('PERIODICIDADE', ['MENSAL', 'BIMESTRAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL']);

// Timezone
date_default_timezone_set('America/Cuiaba');

// Erros: mostrar tudo em dev, none em prod
define('DEBUG', true);
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Inicia sessao
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}