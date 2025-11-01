-- =====================================================
-- ADICIONAR MÓDULO DE CLIENTES/PAGADORES
-- Execute este SQL para adicionar a gestão de clientes
-- =====================================================

USE u411458227_studupss;

-- =====================================================
-- TABELA: clientes
-- =====================================================
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,

    -- Dados da Empresa
    razao_social VARCHAR(255) NOT NULL,
    nome_fantasia VARCHAR(255) DEFAULT NULL,
    cnpj VARCHAR(18) DEFAULT NULL,
    cpf VARCHAR(14) DEFAULT NULL,
    tipo_pessoa ENUM('juridica', 'fisica') DEFAULT 'juridica',

    -- Contato
    email VARCHAR(255) DEFAULT NULL,
    telefone VARCHAR(20) DEFAULT NULL,
    celular VARCHAR(20) DEFAULT NULL,

    -- Endereço
    cep VARCHAR(10) DEFAULT NULL,
    endereco VARCHAR(255) DEFAULT NULL,
    numero VARCHAR(20) DEFAULT NULL,
    complemento VARCHAR(100) DEFAULT NULL,
    bairro VARCHAR(100) DEFAULT NULL,
    cidade VARCHAR(100) DEFAULT NULL,
    estado VARCHAR(2) DEFAULT NULL,

    -- Observações
    observacoes TEXT DEFAULT NULL,

    -- Status
    ativo BOOLEAN DEFAULT TRUE,

    -- Timestamps
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_razao_social (razao_social),
    INDEX idx_cnpj (cnpj),
    INDEX idx_cpf (cpf),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ALTERAR TABELA: contas_receber
-- Adicionar FK para clientes
-- =====================================================

-- Adicionar nova coluna cliente_id
ALTER TABLE contas_receber
ADD COLUMN cliente_id INT DEFAULT NULL AFTER usuario_id;

-- Adicionar índice
ALTER TABLE contas_receber
ADD INDEX idx_cliente_id (cliente_id);

-- Adicionar FK (sem ON DELETE CASCADE para preservar histórico)
ALTER TABLE contas_receber
ADD CONSTRAINT fk_contas_receber_cliente
FOREIGN KEY (cliente_id) REFERENCES clientes(id)
ON DELETE SET NULL;

-- =====================================================
-- ATUALIZAR DADOS EXISTENTES
-- Migrar campo 'cliente' (texto) para clientes cadastrados
-- =====================================================

-- Nota: Os registros antigos com campo 'cliente' (texto) continuam funcionando
-- O campo 'cliente' (VARCHAR) será mantido como fallback para registros antigos
-- Novos registros devem usar cliente_id

-- =====================================================
-- VIEW: Estatísticas de Clientes
-- =====================================================
CREATE OR REPLACE VIEW v_clientes_stats AS
SELECT
    c.id,
    c.usuario_id,
    c.razao_social,
    c.nome_fantasia,
    c.cnpj,
    c.cpf,
    c.tipo_pessoa,
    c.email,
    c.telefone,
    c.celular,
    c.ativo,
    -- Estatísticas de Contas a Receber
    COUNT(cr.id) as total_contas,
    COUNT(CASE WHEN cr.status = 'pendente' THEN 1 END) as contas_pendentes,
    COUNT(CASE WHEN cr.status = 'recebido' THEN 1 END) as contas_recebidas,
    COUNT(CASE WHEN cr.status = 'vencido' THEN 1 END) as contas_vencidas,
    COALESCE(SUM(CASE WHEN cr.status = 'pendente' THEN cr.valor ELSE 0 END), 0) as valor_pendente,
    COALESCE(SUM(CASE WHEN cr.status = 'recebido' THEN cr.valor ELSE 0 END), 0) as valor_recebido,
    COALESCE(SUM(CASE WHEN cr.status = 'vencido' THEN cr.valor ELSE 0 END), 0) as valor_vencido,
    COALESCE(SUM(cr.valor), 0) as valor_total,
    MAX(cr.data_vencimento) as ultima_conta_vencimento,
    MAX(cr.data_recebimento) as ultimo_recebimento
FROM clientes c
LEFT JOIN contas_receber cr ON c.id = cr.cliente_id
GROUP BY c.id, c.usuario_id, c.razao_social, c.nome_fantasia, c.cnpj, c.cpf,
         c.tipo_pessoa, c.email, c.telefone, c.celular, c.ativo;

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================
SELECT 'Tabela clientes criada com sucesso!' as status;
SELECT COUNT(*) as total_clientes FROM clientes;
DESCRIBE clientes;

-- =====================================================
-- INFORMAÇÕES
-- =====================================================
-- Nova tabela: clientes
-- Campos principais:
--   - razao_social (obrigatório)
--   - nome_fantasia, cnpj, cpf
--   - tipo_pessoa (juridica/fisica)
--   - email, telefone, celular
--   - endereço completo
--   - observacoes, ativo
--
-- Alteração: contas_receber
--   - Nova coluna: cliente_id (INT, FK para clientes)
--   - Campo 'cliente' (VARCHAR) mantido para compatibilidade
--
-- Nova view: v_clientes_stats
--   - Estatísticas completas de cada cliente
--   - Total de contas, valores, última movimentação
-- =====================================================
