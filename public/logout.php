<?php
// /home/sistema/contas-pagar/public/logout.php

require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/lib/Auth.php';

Auth::logout();
header('Location: ' . BASE_URL . '/login.php');
exit;