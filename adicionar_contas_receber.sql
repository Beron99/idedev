-- =====================================================
-- ADICIONAR CONTAS A RECEBER
-- Execute este SQL para adicionar o módulo de contas a receber
-- =====================================================

USE u411458227_studupss;

-- =====================================================
-- TABELA: contas_receber
-- =====================================================
CREATE TABLE IF NOT EXISTS contas_receber (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    categoria_id INT DEFAULT NULL,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data_vencimento DATE NOT NULL,
    data_recebimento DATE DEFAULT NULL,
    status ENUM('pendente', 'recebido', 'vencido') DEFAULT 'pendente',
    cliente VARCHAR(255) DEFAULT NULL,
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
-- ATUALIZAR VIEW: Estatísticas do Dashboard
-- =====================================================
DROP VIEW IF EXISTS v_dashboard_stats;

CREATE VIEW v_dashboard_stats AS
SELECT
    u.id as usuario_id,
    -- Contas a Pagar
    COUNT(CASE WHEN cp.status = 'pendente' THEN 1 END) as total_pagar_pendentes,
    COUNT(CASE WHEN cp.status = 'pago' THEN 1 END) as total_pagar_pagas,
    COUNT(CASE WHEN cp.status = 'vencido' THEN 1 END) as total_pagar_vencidas,
    COALESCE(SUM(CASE WHEN cp.status = 'pendente' THEN cp.valor END), 0) as valor_pagar_pendente,
    COALESCE(SUM(CASE WHEN cp.status = 'pago' THEN cp.valor END), 0) as valor_pagar_pago,
    COALESCE(SUM(CASE WHEN cp.status = 'vencido' THEN cp.valor END), 0) as valor_pagar_vencido,
    -- Contas a Receber
    COUNT(CASE WHEN cr.status = 'pendente' THEN 1 END) as total_receber_pendentes,
    COUNT(CASE WHEN cr.status = 'recebido' THEN 1 END) as total_receber_recebidas,
    COUNT(CASE WHEN cr.status = 'vencido' THEN 1 END) as total_receber_vencidas,
    COALESCE(SUM(CASE WHEN cr.status = 'pendente' THEN cr.valor END), 0) as valor_receber_pendente,
    COALESCE(SUM(CASE WHEN cr.status = 'recebido' THEN cr.valor END), 0) as valor_receber_recebido,
    COALESCE(SUM(CASE WHEN cr.status = 'vencido' THEN cr.valor END), 0) as valor_receber_vencido
FROM usuarios u
LEFT JOIN contas_pagar cp ON u.id = cp.usuario_id
LEFT JOIN contas_receber cr ON u.id = cr.usuario_id
GROUP BY u.id;

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================
SELECT 'Tabela contas_receber criada com sucesso!' as status;
SELECT COUNT(*) as total_registros FROM contas_receber;

-- =====================================================
-- INFORMAÇÕES
-- =====================================================
-- Nova tabela: contas_receber
-- Campo adicional: cliente (nome do cliente/pagador)
-- Status: pendente, recebido, vencido
-- View atualizada com estatísticas de recebimentos
-- =====================================================
