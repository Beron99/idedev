-- =====================================================
-- INSTALAÇÃO DO ZERO - Sistema de Contas a Pagar
-- =====================================================
-- Execute este arquivo em um banco de dados VAZIO
-- ou use o phpMyAdmin para criar um novo banco
-- =====================================================

-- Criar e usar o banco de dados
CREATE DATABASE IF NOT EXISTS u411458227_studupss
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE u411458227_studupss;

-- =====================================================
-- TABELA: usuarios
-- =====================================================
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: categorias
-- =====================================================
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cor VARCHAR(7) DEFAULT '#3498db',
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABELA: contas_pagar
-- =====================================================
CREATE TABLE contas_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT DEFAULT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE DEFAULT NULL,
    status ENUM('pendente', 'pago', 'vencido') DEFAULT 'pendente',
    observacoes TEXT DEFAULT NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_status (status),
    INDEX idx_data_vencimento (data_vencimento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- Usuário administrador
INSERT INTO usuarios (nome, email, senha, ativo) VALUES
('Administrador', 'admin@sistema.com', '$2y$10$eUITICgfgN5.ZZ6wI1N.HOqYKQcuV0WfGZJslGbELRdSCKwzRZfBi', TRUE);

-- Categorias padrão
INSERT INTO categorias (usuario_id, nome, cor, ativo) VALUES
(1, 'Alimentação', '#e74c3c', TRUE),
(1, 'Transporte', '#3498db', TRUE),
(1, 'Saúde', '#2ecc71', TRUE),
(1, 'Educação', '#9b59b6', TRUE),
(1, 'Lazer', '#f39c12', TRUE),
(1, 'Moradia', '#1abc9c', TRUE),
(1, 'Utilidades', '#34495e', TRUE),
(1, 'Outros', '#95a5a6', TRUE);

-- =====================================================
-- VIEW: Estatísticas do Dashboard
-- =====================================================
CREATE VIEW v_dashboard_stats AS
SELECT
    u.id as usuario_id,
    COUNT(CASE WHEN c.status = 'pendente' THEN 1 END) as total_pendentes,
    COUNT(CASE WHEN c.status = 'pago' THEN 1 END) as total_pagas,
    COUNT(CASE WHEN c.status = 'vencido' THEN 1 END) as total_vencidas,
    COALESCE(SUM(CASE WHEN c.status = 'pendente' THEN c.valor END), 0) as valor_pendente,
    COALESCE(SUM(CASE WHEN c.status = 'pago' THEN c.valor END), 0) as valor_pago,
    COALESCE(SUM(CASE WHEN c.status = 'vencido' THEN c.valor END), 0) as valor_vencido,
    COALESCE(SUM(c.valor), 0) as valor_total
FROM usuarios u
LEFT JOIN contas_pagar c ON u.id = c.usuario_id
GROUP BY u.id;

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================
SELECT '✅ Instalação concluída com sucesso!' as status;
SELECT CONCAT('Usuários: ', COUNT(*)) as total FROM usuarios;
SELECT CONCAT('Categorias: ', COUNT(*)) as total FROM categorias;

-- =====================================================
-- CREDENCIAIS DE ACESSO
-- =====================================================
-- Email: admin@sistema.com
-- Senha: admin123
--
-- ⚠️ ALTERE A SENHA APÓS O PRIMEIRO LOGIN!
-- =====================================================
