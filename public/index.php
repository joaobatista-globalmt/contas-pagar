<?php
// /home/sistema/contas-pagar/public/index.php (dashboard)

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';
require_once __DIR__ . '/../src/lib/Permissao.php';
require_once __DIR__ . '/../src/controllers/DashboardController.php';

Auth::require();

$titulo = 'Dashboard';
require_once __DIR__ . '/../src/views/dashboard/index.php';