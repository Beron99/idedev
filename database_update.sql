-- =====================================================
-- ATUALIZAÇÃO DO BANCO DE DADOS
-- Sistema de Administração + Departamentos
-- =====================================================

-- 1. CRIAR TABELA DE DEPARTAMENTOS
CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    cor VARCHAR(7) DEFAULT '#667eea',
    orcamento_mensal DECIMAL(10, 2) DEFAULT 0,
    responsavel_id INT,
    ativo BOOLEAN DEFAULT TRUE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ADICIONAR COLUNAS NA TABELA USUARIOS
ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS role ENUM('admin', 'gestor', 'usuario') DEFAULT 'usuario' AFTER senha,
ADD COLUMN IF NOT EXISTS departamento_id INT AFTER role,
ADD COLUMN IF NOT EXISTS ativo BOOLEAN DEFAULT TRUE AFTER departamento_id,
ADD COLUMN IF NOT EXISTS foto_perfil VARCHAR(255) AFTER ativo,
ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) AFTER foto_perfil,
ADD COLUMN IF NOT EXISTS ultimo_acesso TIMESTAMP NULL AFTER telefone;

-- 3. ADICIONAR ÍNDICES NA TABELA USUARIOS
ALTER TABLE usuarios
ADD INDEX IF NOT EXISTS idx_role (role),
ADD INDEX IF NOT EXISTS idx_departamento (departamento_id),
ADD INDEX IF NOT EXISTS idx_ativo (ativo);

-- 4. ADICIONAR FOREIGN KEY (departamento)
ALTER TABLE usuarios
ADD CONSTRAINT fk_usuario_departamento
FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
ON DELETE SET NULL;

-- 5. ADICIONAR COLUNA DEPARTAMENTO NA TABELA CONTAS_PAGAR
ALTER TABLE contas_pagar
ADD COLUMN IF NOT EXISTS departamento_id INT AFTER usuario_id,
ADD INDEX IF NOT EXISTS idx_departamento (departamento_id);

-- 6. ADICIONAR FOREIGN KEY (departamento em contas_pagar)
ALTER TABLE contas_pagar
ADD CONSTRAINT fk_conta_departamento
FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
ON DELETE SET NULL;

-- 7. ADICIONAR COLUNA PRIORIDADE EM CONTAS_PAGAR (para Kanban)
ALTER TABLE contas_pagar
ADD COLUMN IF NOT EXISTS prioridade ENUM('baixa', 'media', 'alta', 'urgente') DEFAULT 'media' AFTER status,
ADD COLUMN IF NOT EXISTS posicao_kanban INT DEFAULT 0 AFTER prioridade,
ADD INDEX IF NOT EXISTS idx_prioridade (prioridade);

-- =====================================================
-- DADOS INICIAIS
-- =====================================================

-- 8. INSERIR DEPARTAMENTOS PADRÃO
INSERT INTO departamentos (nome, descricao, cor, orcamento_mensal) VALUES
('Financeiro', 'Departamento Financeiro', '#FF6384', 50000.00),
('Recursos Humanos', 'Departamento de RH', '#36A2EB', 30000.00),
('TI / Tecnologia', 'Departamento de Tecnologia', '#FFCE56', 40000.00),
('Vendas', 'Departamento Comercial', '#4BC0C0', 25000.00),
('Marketing', 'Departamento de Marketing', '#9966FF', 20000.00),
('Operações', 'Departamento Operacional', '#FF9F40', 35000.00),
('Administrativo', 'Departamento Administrativo', '#C9CBCF', 15000.00)
ON DUPLICATE KEY UPDATE nome=nome;

-- 9. CRIAR PRIMEIRO ADMIN
-- Senha padrão: Admin@123
-- IMPORTANTE: Altere esta senha após primeiro login!
INSERT INTO usuarios (nome, email, senha, role, ativo)
VALUES (
    'Administrador',
    'admin@sistema.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    TRUE
)
ON DUPLICATE KEY UPDATE email=email;

-- =====================================================
-- TABELA DE LOG DE AUDITORIA (OPCIONAL)
-- =====================================================

CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    acao VARCHAR(100) NOT NULL,
    tabela VARCHAR(50),
    registro_id INT,
    dados_antigos TEXT,
    dados_novos TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_data (data_hora),
    INDEX idx_tabela (tabela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- FIM DO SCRIPT
-- =====================================================

-- VERIFICAÇÃO
SELECT 'Atualização concluída com sucesso!' as STATUS;
SELECT COUNT(*) as total_departamentos FROM departamentos;
SELECT COUNT(*) as total_usuarios FROM usuarios;
SELECT COUNT(*) as total_admins FROM usuarios WHERE role = 'admin';
