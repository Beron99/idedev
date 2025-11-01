-- =====================================================
-- SISTEMA DE GESTÃO FINANCEIRA - BANCO DE DADOS COMPLETO
-- Versão: 1.0
-- Compatível com phpMyAdmin
-- =====================================================

-- Selecionar o banco de dados
USE u411458227_studupss;

-- Desabilitar verificação de chaves estrangeiras temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. TABELA: usuarios
-- =====================================================
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    role ENUM('admin', 'gestor', 'usuario') DEFAULT 'usuario',
    departamento_id INT DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    foto_perfil VARCHAR(255) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    ultimo_acesso TIMESTAMP NULL DEFAULT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_departamento (departamento_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. TABELA: departamentos
-- =====================================================
DROP TABLE IF EXISTS departamentos;

CREATE TABLE departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT DEFAULT NULL,
    cor VARCHAR(7) DEFAULT '#667eea',
    orcamento_mensal DECIMAL(10, 2) DEFAULT 0,
    responsavel_id INT DEFAULT NULL,
    gestor_nome VARCHAR(100) DEFAULT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TABELA: categorias
-- =====================================================
DROP TABLE IF EXISTS categorias;

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    cor VARCHAR(7) DEFAULT '#667eea',
    usuario_id INT NOT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. TABELA: contas_pagar
-- =====================================================
DROP TABLE IF EXISTS contas_pagar;

CREATE TABLE contas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT DEFAULT NULL,
    departamento_id INT DEFAULT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    status ENUM('pendente', 'pago', 'vencido') DEFAULT 'pendente',
    prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media',
    posicao_kanban INT DEFAULT 0,
    aprovacao_status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    aprovado_por INT DEFAULT NULL,
    data_aprovacao TIMESTAMP NULL DEFAULT NULL,
    motivo_rejeicao TEXT DEFAULT NULL,
    observacoes TEXT DEFAULT NULL,
    anexo VARCHAR(255) DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_departamento (departamento_id),
    INDEX idx_status (status),
    INDEX idx_vencimento (data_vencimento),
    INDEX idx_prioridade (prioridade),
    INDEX idx_aprovacao (aprovacao_status),
    INDEX idx_aprovado_por (aprovado_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. TABELA: auditoria
-- =====================================================
DROP TABLE IF EXISTS auditoria;

CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT NULL,
    acao VARCHAR(100) NOT NULL,
    tabela VARCHAR(50) DEFAULT NULL,
    registro_id INT DEFAULT NULL,
    dados_antigos TEXT DEFAULT NULL,
    dados_novos TEXT DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (data_hora),
    INDEX idx_tabela (tabela),
    INDEX idx_acao (acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. TABELA: metas_orcamentos
-- =====================================================
DROP TABLE IF EXISTS metas_orcamentos;

CREATE TABLE metas_orcamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    categoria_id INT DEFAULT NULL,
    mes_ano DATE NOT NULL,
    valor_orcado DECIMAL(10, 2) NOT NULL,
    valor_gasto DECIMAL(10, 2) DEFAULT 0,
    percentual_utilizado DECIMAL(5, 2) DEFAULT 0,
    status ENUM('ok', 'atencao', 'estourado') DEFAULT 'ok',
    observacoes TEXT DEFAULT NULL,
    criado_por INT DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_meta (departamento_id, categoria_id, mes_ano),
    INDEX idx_departamento (departamento_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_mes_ano (mes_ano),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. TABELA: notificacoes
-- =====================================================
DROP TABLE IF EXISTS notificacoes;

CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('info', 'aviso', 'sucesso', 'erro') DEFAULT 'info',
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    link VARCHAR(255) DEFAULT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_id),
    INDEX idx_usuario_lida (usuario_id, lida),
    INDEX idx_data (data_criacao),
    INDEX idx_lida (lida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. TABELA: relatorios_salvos
-- =====================================================
DROP TABLE IF EXISTS relatorios_salvos;

CREATE TABLE relatorios_salvos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('excel', 'pdf') NOT NULL,
    filtros JSON DEFAULT NULL,
    arquivo VARCHAR(255) NOT NULL,
    criado_por INT DEFAULT NULL,
    departamento_id INT DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_criado_por (criado_por),
    INDEX idx_departamento (departamento_id),
    INDEX idx_data (data_criacao),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. TABELA: configuracoes
-- =====================================================
DROP TABLE IF EXISTS configuracoes;

CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT DEFAULT NULL,
    descricao TEXT DEFAULT NULL,
    tipo ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADICIONAR FOREIGN KEYS
-- =====================================================

-- Foreign keys para usuarios
ALTER TABLE usuarios
ADD CONSTRAINT fk_usuario_departamento
FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;

-- Foreign keys para categorias
ALTER TABLE categorias
ADD CONSTRAINT fk_categoria_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Foreign keys para contas_pagar
ALTER TABLE contas_pagar
ADD CONSTRAINT fk_conta_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

ALTER TABLE contas_pagar
ADD CONSTRAINT fk_conta_categoria
FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL;

ALTER TABLE contas_pagar
ADD CONSTRAINT fk_conta_departamento
FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;

ALTER TABLE contas_pagar
ADD CONSTRAINT fk_conta_aprovado_por
FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Foreign keys para auditoria
ALTER TABLE auditoria
ADD CONSTRAINT fk_auditoria_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Foreign keys para metas_orcamentos
ALTER TABLE metas_orcamentos
ADD CONSTRAINT fk_meta_departamento
FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE;

ALTER TABLE metas_orcamentos
ADD CONSTRAINT fk_meta_categoria
FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL;

ALTER TABLE metas_orcamentos
ADD CONSTRAINT fk_meta_criado_por
FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- Foreign keys para notificacoes
ALTER TABLE notificacoes
ADD CONSTRAINT fk_notificacao_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

-- Foreign keys para relatorios_salvos
ALTER TABLE relatorios_salvos
ADD CONSTRAINT fk_relatorio_criado_por
FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

ALTER TABLE relatorios_salvos
ADD CONSTRAINT fk_relatorio_departamento
FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE;

-- Reabilitar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- INSERIR DADOS INICIAIS
-- =====================================================

-- Inserir departamentos padrão
INSERT INTO departamentos (nome, descricao, cor, orcamento_mensal) VALUES
('Financeiro', 'Departamento Financeiro', '#FF6384', 50000.00),
('Recursos Humanos', 'Departamento de RH', '#36A2EB', 30000.00),
('TI / Tecnologia', 'Departamento de Tecnologia', '#FFCE56', 40000.00),
('Vendas', 'Departamento Comercial', '#4BC0C0', 25000.00),
('Marketing', 'Departamento de Marketing', '#9966FF', 20000.00),
('Operações', 'Departamento Operacional', '#FF9F40', 35000.00),
('Administrativo', 'Departamento Administrativo', '#C9CBCF', 15000.00);

-- Inserir usuário administrador
-- Email: admin@sistema.com
-- Senha: Admin@123
INSERT INTO usuarios (nome, email, senha, role, ativo) VALUES
('Administrador do Sistema', 'admin@sistema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);

-- Inserir configurações padrão do sistema
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES
('dias_aviso_vencimento', '7', 'Dias antes do vencimento para enviar aviso', 'number'),
('valor_minimo_aprovacao', '1000.00', 'Valor mínimo que requer aprovação do gestor', 'number'),
('permitir_anexos', 'true', 'Permitir upload de anexos nas contas', 'boolean'),
('tamanho_max_anexo', '5', 'Tamanho máximo do anexo em MB', 'number'),
('tipos_anexo_permitidos', '["pdf","jpg","jpeg","png","doc","docx","xlsx"]', 'Tipos de arquivo permitidos', 'json'),
('email_notificacoes', 'true', 'Enviar notificações por email', 'boolean'),
('sistema_nome', 'Sistema de Gestão Financeira', 'Nome do sistema', 'text'),
('sistema_versao', '1.0', 'Versão do sistema', 'text');

-- Inserir metas de exemplo para cada departamento
INSERT INTO metas_orcamentos (departamento_id, mes_ano, valor_orcado, criado_por)
SELECT
    id,
    DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'),
    orcamento_mensal,
    (SELECT id FROM usuarios WHERE role = 'admin' LIMIT 1)
FROM departamentos;

-- =====================================================
-- CRIAR VIEW PARA DASHBOARD
-- =====================================================

DROP VIEW IF EXISTS v_dashboard_stats;

CREATE VIEW v_dashboard_stats AS
SELECT
    d.id as departamento_id,
    d.nome as departamento,
    d.cor as departamento_cor,
    COUNT(DISTINCT u.id) as total_usuarios,
    COUNT(DISTINCT c.id) as total_contas,
    SUM(CASE WHEN c.status = 'pendente' THEN c.valor ELSE 0 END) as valor_pendente,
    SUM(CASE WHEN c.status = 'pago' THEN c.valor ELSE 0 END) as valor_pago,
    SUM(CASE WHEN c.status = 'vencido' THEN c.valor ELSE 0 END) as valor_vencido,
    d.orcamento_mensal,
    ROUND((SUM(CASE WHEN c.status = 'pago' AND MONTH(c.data_pagamento) = MONTH(CURRENT_DATE) AND YEAR(c.data_pagamento) = YEAR(CURRENT_DATE) THEN c.valor ELSE 0 END) / NULLIF(d.orcamento_mensal, 0) * 100), 2) as percentual_gasto
FROM departamentos d
LEFT JOIN usuarios u ON d.id = u.departamento_id AND u.ativo = TRUE
LEFT JOIN contas_pagar c ON d.id = c.departamento_id
WHERE d.ativo = TRUE
GROUP BY d.id, d.nome, d.cor, d.orcamento_mensal;

-- =====================================================
-- VERIFICAÇÃO FINAL
-- =====================================================

SELECT '=====================================' as '';
SELECT 'BANCO DE DADOS CRIADO COM SUCESSO!' as STATUS;
SELECT '=====================================' as '';
SELECT '' as '';

SELECT 'TABELAS CRIADAS:' as '';
SELECT TABLE_NAME as Tabela, TABLE_ROWS as Registros
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;

SELECT '' as '';
SELECT 'CREDENCIAIS DE ACESSO:' as '';
SELECT 'Email: admin@sistema.com' as '';
SELECT 'Senha: Admin@123' as '';
SELECT '' as '';
SELECT 'IMPORTANTE: Altere a senha após o primeiro login!' as '';
SELECT '=====================================' as '';

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================
