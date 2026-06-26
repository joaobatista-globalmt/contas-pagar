-- ============================================================
-- Schema: contas_pagar (MariaDB 10.11)
-- Data: 2026-06-26
-- Versao: 1.0
-- ============================================================
-- Multi-tenant via empresa_id em todas as tabelas filhas
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS log_operacoes;
DROP TABLE IF EXISTS anexos;
DROP TABLE IF EXISTS contas_recorrencia;
DROP TABLE IF EXISTS contas_parcelas;
DROP TABLE IF EXISTS contas_pagar;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS fornecedores;
DROP TABLE IF EXISTS usuarios_empresas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS empresas;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. EMPRESAS (multi-tenant)
-- ============================================================
CREATE TABLE empresas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    razao_social VARCHAR(200) NOT NULL,
    nome_fantasia VARCHAR(100),
    cnpj VARCHAR(20),
    inscricao_estadual VARCHAR(20),
    endereco VARCHAR(255),
    cidade VARCHAR(100),
    uf CHAR(2),
    cep VARCHAR(10),
    telefone VARCHAR(20),
    email VARCHAR(150),
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_cnpj (cnpj),
    INDEX idx_razao_social (razao_social),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. USUARIOS (global - 1 user pode acessar N empresas)
-- ============================================================
CREATE TABLE usuarios (
    id INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil_padrao ENUM('admin','operador','aprovador','pagador','visualizador') DEFAULT 'operador',
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acesso DATETIME,
    PRIMARY KEY (id),
    UNIQUE KEY uk_email (email),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. USUARIOS_EMPRESAS (N:N, com perfil por empresa)
-- ============================================================
CREATE TABLE usuarios_empresas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    usuario_id INT(11) NOT NULL,
    empresa_id INT(11) NOT NULL,
    perfil_na_empresa ENUM('admin','operador','aprovador','pagador','visualizador') NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    data_vinculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuario_empresa (usuario_id, empresa_id),
    INDEX idx_empresa (empresa_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. FORNECEDORES (por empresa)
-- ============================================================
CREATE TABLE fornecedores (
    id INT(11) NOT NULL AUTO_INCREMENT,
    empresa_id INT(11) NOT NULL,
    nome VARCHAR(200) NOT NULL,
    tipo_pessoa ENUM('F','J') DEFAULT 'J',
    cnpj_cpf VARCHAR(20),
    email VARCHAR(150),
    telefone VARCHAR(20),
    banco VARCHAR(50),
    agencia VARCHAR(20),
    conta VARCHAR(30),
    pix VARCHAR(150),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_empresa (empresa_id),
    INDEX idx_nome (nome),
    INDEX idx_cnpj_cpf (cnpj_cpf),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. CATEGORIAS (por empresa)
-- ============================================================
CREATE TABLE categorias (
    id INT(11) NOT NULL AUTO_INCREMENT,
    empresa_id INT(11) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    tipo ENUM('DESPESA','IMPOSTO','SERVICO','PRODUTO','OUTROS') DEFAULT 'DESPESA',
    cor VARCHAR(7) DEFAULT '#3498db',
    ativo TINYINT(1) DEFAULT 1,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_empresa (empresa_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. CONTAS A PAGAR (tabela principal)
-- ============================================================
CREATE TABLE contas_pagar (
    id INT(11) NOT NULL AUTO_INCREMENT,
    empresa_id INT(11) NOT NULL,
    fornecedor_id INT(11),
    categoria_id INT(11),
    descricao VARCHAR(255) NOT NULL,
    numero_documento VARCHAR(50),
    valor DECIMAL(15,2) NOT NULL,
    valor_pago DECIMAL(15,2),
    data_emissao DATE,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status ENUM('PENDENTE','APROVADA','PAGA','ATRASADA','CANCELADA') DEFAULT 'PENDENTE',
    forma_pagamento ENUM('BOLETO','PIX','CARTAO_CREDITO','CARTAO_DEBITO','DINHEIRO','TRANSFERENCIA','DEBITO_AUTOMATICO','CHEQUE'),
    aprovada_por INT(11),
    aprovada_em DATETIME,
    paga_por INT(11),
    observacoes TEXT,
    eh_parcelada TINYINT(1) DEFAULT 0,
    eh_recorrente TINYINT(1) DEFAULT 0,
    recorrencia_id INT(11),
    parcela_numero INT(11),
    parcela_total INT(11),
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_empresa_status (empresa_id, status),
    INDEX idx_empresa_vencimento (empresa_id, data_vencimento),
    INDEX idx_fornecedor (fornecedor_id),
    INDEX idx_categoria (categoria_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (aprovada_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (paga_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. CONTAS_PARCELAS (filhas de conta parcelada)
-- ============================================================
CREATE TABLE contas_parcelas (
    id INT(11) NOT NULL AUTO_INCREMENT,
    conta_pai_id INT(11) NOT NULL,
    numero_parcela INT(11) NOT NULL,
    total_parcelas INT(11) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    data_vencimento DATE NOT NULL,
    status ENUM('PENDENTE','APROVADA','PAGA','ATRASADA','CANCELADA') DEFAULT 'PENDENTE',
    data_pagamento DATE,
    forma_pagamento VARCHAR(30),
    PRIMARY KEY (id),
    INDEX idx_conta_pai (conta_pai_id),
    FOREIGN KEY (conta_pai_id) REFERENCES contas_pagar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. CONTAS_RECORRENCIA (template)
-- ============================================================
CREATE TABLE contas_recorrencia (
    id INT(11) NOT NULL AUTO_INCREMENT,
    empresa_id INT(11) NOT NULL,
    fornecedor_id INT(11),
    categoria_id INT(11),
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(15,2) NOT NULL,
    dia_vencimento TINYINT(2) NOT NULL COMMENT '1-31',
    periodicidade ENUM('MENSAL','BIMESTRAL','TRIMESTRAL','SEMESTRAL','ANUAL') DEFAULT 'MENSAL',
    data_inicio DATE NOT NULL,
    data_fim DATE,
    ativa TINYINT(1) DEFAULT 1,
    ultima_geracao CHAR(7) COMMENT 'AAAA-MM da ultima geracao',
    observacoes TEXT,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_empresa (empresa_id),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (fornecedor_id) REFERENCES fornecedores(id) ON DELETE SET NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. ANEXOS (PDF da NF)
-- ============================================================
CREATE TABLE anexos (
    id INT(11) NOT NULL AUTO_INCREMENT,
    conta_id INT(11) NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    tamanho_bytes INT(11),
    tipo_mime VARCHAR(100) DEFAULT 'application/pdf',
    uploaded_by INT(11),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_conta (conta_id),
    FOREIGN KEY (conta_id) REFERENCES contas_pagar(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. LOG_OPERACOES (auditoria)
-- ============================================================
CREATE TABLE log_operacoes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    empresa_id INT(11),
    usuario_id INT(11),
    operacao VARCHAR(50) NOT NULL COMMENT 'CRIAR_CONTA, APROVAR_CONTA, PAGAR_CONTA, etc',
    descricao TEXT,
    ip_address VARCHAR(45),
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_empresa_data (empresa_id, data_hora),
    INDEX idx_operacao (operacao),
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FIM
-- ============================================================