<?php
// Migration: adiciona conta_pai_id em contas_pagar (para parcelamento)
require '/home/sistema/contas-pagar/src/config/database.php';

$pdo = getConnection();

// Verifica se coluna já existe
$check = $pdo->query("SHOW COLUMNS FROM contas_pagar LIKE 'conta_pai_id'")->fetch();

if (!$check) {
    $pdo->exec("ALTER TABLE contas_pagar ADD COLUMN conta_pai_id INT(11) NULL AFTER eh_parcelada, ADD INDEX idx_conta_pai (conta_pai_id), ADD FOREIGN KEY (conta_pai_id) REFERENCES contas_pagar(id) ON DELETE CASCADE");
    echo "Coluna conta_pai_id adicionada com sucesso.\n";
} else {
    echo "Coluna conta_pai_id ja existe.\n";
}

// Verifica indices de parcela
$check = $pdo->query("SHOW COLUMNS FROM contas_pagar LIKE 'parcela_numero'")->fetch();
if ($check) {
    echo "Coluna parcela_numero ja existe.\n";
} else {
    $pdo->exec("ALTER TABLE contas_pagar ADD COLUMN parcela_numero INT(11) NULL AFTER conta_pai_id, ADD COLUMN parcela_total INT(11) NULL AFTER parcela_numero");
    echo "Colunas parcela_numero/parcela_total adicionadas.\n";
}
?>