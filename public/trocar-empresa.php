<?php
// /home/sistema/contas-pagar/public/trocar-empresa.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/lib/Auth.php';

if (!Auth::check()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['empresa_id'])) {
    Auth::setEmpresaAtual((int)$_POST['empresa_id']);
}

header('Location: ' . BASE_URL . '/');
exit;