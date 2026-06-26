<?php
// /home/sistema/contas-pagar/public/login.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/lib/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $resultado = Auth::login($email, $senha);

    if ($resultado['ok']) {
        header('Location: ' . BASE_URL . '/');
        exit;
    } else {
        $_SESSION['erro_login'] = $resultado['msg'];
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

// GET: mostra formulário
require_once __DIR__ . '/../src/views/auth/login.php';