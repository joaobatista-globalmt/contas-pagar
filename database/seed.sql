-- ============================================================
-- Seed: dados de exemplo pra testar
-- 1 empresa, 3 usuarios (admin/operador/aprovador), 5 categorias,
-- 10 fornecedores, 30 contas em varios status
-- ============================================================
-- Senha padrao pra todos: senha123
-- Hash gerado via PHP: $2y$10$kbtEbEjnbqbScpgCxzzZhehYnYAZQg4Ym60ubTgsKJwxcduSbYS7O
-- ============================================================

-- Empresa exemplo
INSERT INTO empresas (id, razao_social, nome_fantasia, cnpj, inscricao_estadual, endereco, cidade, uf, cep, telefone, email, ativo) VALUES
(1, 'Globalmt Tecnologia Ltda', 'Globalmt', '11.222.333/0001-44', '12345678901', 'Av. das Empresas, 1000', 'Cuiaba', 'MT', '78000-000', '(65) 99999-0001', 'contato@globalmt.com.br', 1);

-- Usuarios
INSERT INTO usuarios (id, nome, email, senha_hash, perfil_padrao, ativo) VALUES
(1, 'João Batista', 'joao@globalmt.com.br', '$2y$10$kbtEbEjnbqbScpgCxzzZhehYnYAZQg4Ym60ubTgsKJwxcduSbYS7O', 'admin', 1),
(2, 'Maria Operadora', 'maria@globalmt.com.br', '$2y$10$kbtEbEjnbqbScpgCxzzZhehYnYAZQg4Ym60ubTgsKJwxcduSbYS7O', 'operador', 1),
(3, 'Carlos Aprovador', 'carlos@globalmt.com.br', '$2y$10$kbtEbEjnbqbScpgCxzzZhehYnYAZQg4Ym60ubTgsKJwxcduSbYS7O', 'aprovador', 1);

INSERT INTO usuarios_empresas (usuario_id, empresa_id, perfil_na_empresa) VALUES
(1, 1, 'admin'),
(2, 1, 'operador'),
(3, 1, 'aprovador');

INSERT INTO categorias (empresa_id, nome, tipo, cor) VALUES
(1, 'Aluguel', 'DESPESA', '#e74c3c'),
(1, 'Energia Elétrica', 'DESPESA', '#f39c12'),
(1, 'Internet/Telefonia', 'SERVICO', '#3498db'),
(1, 'Material de Escritório', 'PRODUTO', '#2ecc71'),
(1, 'Impostos', 'IMPOSTO', '#9b59b6');

INSERT INTO fornecedores (empresa_id, nome, tipo_pessoa, cnpj_cpf, email, telefone, banco, agencia, conta, pix) VALUES
(1, 'Imobiliária Centro', 'J', '11.111.111/0001-11', 'contato@imobcentro.com.br', '(65) 3333-1111', 'Banco do Brasil', '1234', '12345-6', 'contato@imobcentro.com.br'),
(1, 'Energisa MT', 'J', '22.222.222/0001-22', 'atendimento@energisa.com.br', '(65) 3333-2222', NULL, NULL, NULL, NULL),
(1, 'Vivo Empresas', 'J', '33.333.333/0001-33', 'empresas@vivo.com.br', '0800-3333', NULL, NULL, NULL, NULL),
(1, 'Kalunga', 'J', '44.444.444/0001-44', 'sac@kalunga.com.br', '(11) 4004-4444', NULL, NULL, NULL, NULL),
(1, 'Receita Federal', 'J', '00.000.000/0001-00', NULL, NULL, NULL, NULL, NULL, NULL),
(1, 'Papelaria Cuiabá', 'J', '55.555.555/0001-55', 'vendas@papelariacuiaba.com.br', '(65) 3333-5555', NULL, NULL, NULL, NULL),
(1, 'Contábil Global', 'J', '66.666.666/0001-66', 'contato@contabilglobal.com.br', '(65) 3333-6666', 'Caixa', '0501', '9876-5', 'pix@contabilglobal.com.br'),
(1, 'AWS Brasil', 'J', '77.777.777/0001-77', 'suporte@aws.amazon.com', '0800-7777', NULL, NULL, NULL, NULL),
(1, 'Google Cloud', 'J', '88.888.888/0001-88', 'suporte@googlecloud.com', '0800-8888', NULL, NULL, NULL, NULL),
(1, 'Microsoft Office 365', 'J', '99.999.999/0001-99', 'suporte@microsoft.com', '0800-9999', NULL, NULL, NULL, NULL);

INSERT INTO contas_pagar (empresa_id, fornecedor_id, categoria_id, descricao, numero_documento, valor, data_vencimento, status, forma_pagamento, created_by) VALUES
(1, 1, 1, 'Aluguel sala comercial', 'NF-001', 2500.00, DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'PENDENTE', 'BOLETO', 2),
(1, 2, 2, 'Conta de luz - ref 06/2026', 'NF-002', 850.50, DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'PENDENTE', 'BOLETO', 2),
(1, 3, 3, 'Internet fibra 600MB', 'NF-003', 199.90, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'PENDENTE', 'DEBITO_AUTOMATICO', 2),
(1, 4, 4, 'Resma papel A4 - lote 10un', 'NF-004', 350.00, DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'PENDENTE', 'BOLETO', 2),
(1, 8, 5, 'AWS - mensal', 'NF-005', 480.00, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'PENDENTE', 'CARTAO_CREDITO', 2),
(1, 1, 1, 'Aluguel sala comercial', 'NF-006', 2500.00, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'APROVADA', 'BOLETO', 2),
(1, 6, 4, 'Canetas e grampeador', 'NF-007', 89.90, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'APROVADA', 'PIX', 2),
(1, 9, 5, 'Google Cloud - mensal', 'NF-008', 320.00, CURDATE(), 'APROVADA', 'CARTAO_CREDITO', 2),
(1, 1, 1, 'Aluguel sala comercial', 'NF-009', 2500.00, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'PAGA', 'TRANSFERENCIA', 2),
(1, 2, 2, 'Conta de luz - ref 05/2026', 'NF-010', 920.30, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'PAGA', 'BOLETO', 2),
(1, 3, 3, 'Internet fibra 600MB', 'NF-011', 199.90, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'PAGA', 'DEBITO_AUTOMATICO', 2),
(1, 7, 5, 'Honorários contábeis 05/2026', 'NF-012', 1500.00, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'PAGA', 'TRANSFERENCIA', 2),
(1, 10, 5, 'Microsoft 365 - mensal', 'NF-013', 280.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'PAGA', 'CARTAO_CREDITO', 2),
(1, 4, 4, 'Toner impressora', 'NF-014', 450.00, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'PAGA', 'PIX', 2),
(1, 2, 2, 'Conta de luz - ref 04/2026', 'NF-015', 880.00, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'ATRASADA', 'BOLETO', 2),
(1, 6, 4, 'Cadernos e canetas', 'NF-016', 120.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'ATRASADA', 'BOLETO', 2),
(1, 8, 5, 'AWS - mensal (cancelada)', 'NF-017', 480.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'CANCELADA', NULL, 2),
(1, 9, 5, 'Google Cloud - cancelada', 'NF-018', 320.00, DATE_SUB(CURDATE(), INTERVAL 28 DAY), 'CANCELADA', NULL, 2),
(1, 1, 1, 'Aluguel - próximo mês', 'NF-019', 2500.00, DATE_ADD(CURDATE(), INTERVAL 35 DAY), 'PENDENTE', 'BOLETO', 2),
(1, 1, 1, 'Aluguel - daqui 2 meses', 'NF-020', 2500.00, DATE_ADD(CURDATE(), INTERVAL 65 DAY), 'PENDENTE', 'BOLETO', 2),
(1, 2, 2, 'Conta de luz - próximo mês', 'NF-021', 850.00, DATE_ADD(CURDATE(), INTERVAL 40 DAY), 'PENDENTE', 'BOLETO', 2),
(1, 7, 5, 'Honorários contábeis - próximo mês', 'NF-022', 1500.00, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'PENDENTE', 'TRANSFERENCIA', 2),
(1, 8, 5, 'AWS - próximo mês', 'NF-023', 480.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'PENDENTE', 'CARTAO_CREDITO', 2),
(1, 9, 5, 'Google Cloud - próximo mês', 'NF-024', 320.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'PENDENTE', 'CARTAO_CREDITO', 2),
(1, 10, 5, 'Microsoft 365 - próximo mês', 'NF-025', 280.00, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'PENDENTE', 'CARTAO_CREDITO', 2),
(1, 3, 3, 'Internet - próximo mês', 'NF-026', 199.90, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'PENDENTE', 'DEBITO_AUTOMATICO', 2),
(1, 6, 4, 'Material escritório - mix', 'NF-027', 280.00, DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'PENDENTE', 'BOLETO', 2),
(1, 4, 4, 'Cartuchos impressora', 'NF-028', 380.00, DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'PENDENTE', 'PIX', 2),
(1, 1, 1, 'Condomínio', 'NF-029', 650.00, DATE_ADD(CURDATE(), INTERVAL 8 DAY), 'APROVADA', 'BOLETO', 2),
(1, 2, 2, 'Conta de luz - extra', 'NF-030', 120.00, DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'PENDENTE', 'BOLETO', 2);

UPDATE contas_pagar SET valor_pago = valor, data_pagamento = data_vencimento WHERE status = 'PAGA';

INSERT INTO log_operacoes (empresa_id, usuario_id, operacao, descricao) VALUES
(1, 1, 'SEED', 'Carga inicial de dados de exemplo - 30 contas');