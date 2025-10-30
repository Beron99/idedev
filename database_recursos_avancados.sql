-- =====================================================
-- RECURSOS AVANÇADOS - ATUALIZAÇÃO DO BANCO
-- Upload, Aprovações, Metas, Relatórios
-- =====================================================

-- 1. ADICIONAR CAMPOS PARA WORKFLOW DE APROVAÇÃO
ALTER TABLE contas_pagar
ADD COLUMN IF NOT EXISTS aprovacao_status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente' AFTER prioridade,
ADD COLUMN IF NOT EXISTS aprovado_por INT AFTER aprovacao_status,
ADD COLUMN IF NOT EXISTS data_aprovacao TIMESTAMP NULL AFTER aprovado_por,
ADD COLUMN IF NOT EXISTS motivo_rejeicao TEXT AFTER data_aprovacao,
ADD INDEX IF NOT EXISTS idx_aprovacao (aprovacao_status),
ADD INDEX IF NOT EXISTS idx_aprovado_por (aprovado_por);

-- 2. ADICIONAR FOREIGN KEY PARA APROVADOR
ALTER TABLE contas_pagar
ADD CONSTRAINT IF NOT EXISTS fk_aprovado_por
FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL;

-- 3. CRIAR TABELA DE METAS E ORÇAMENTOS
CREATE TABLE IF NOT EXISTS metas_orcamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    categoria_id INT,
    mes_ano DATE NOT NULL,
    valor_orcado DECIMAL(10, 2) NOT NULL,
    valor_gasto DECIMAL(10, 2) DEFAULT 0,
    percentual_utilizado DECIMAL(5, 2) DEFAULT 0,
    status ENUM('ok', 'atencao', 'estourado') DEFAULT 'ok',
    observacoes TEXT,
    criado_por INT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uk_meta (departamento_id, categoria_id, mes_ano),
    INDEX idx_mes_ano (mes_ano),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CRIAR TABELA DE NOTIFICAÇÕES
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('info', 'aviso', 'sucesso', 'erro') DEFAULT 'info',
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    link VARCHAR(255),
    lida BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_lida (usuario_id, lida),
    INDEX idx_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. CRIAR TABELA DE RELATÓRIOS SALVOS
CREATE TABLE IF NOT EXISTS relatorios_salvos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('excel', 'pdf') NOT NULL,
    filtros JSON,
    arquivo VARCHAR(255) NOT NULL,
    criado_por INT,
    departamento_id INT,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE CASCADE,
    INDEX idx_criado_por (criado_por),
    INDEX idx_departamento (departamento_id),
    INDEX idx_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. CRIAR TABELA DE CONFIGURAÇÕES DO SISTEMA
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao TEXT,
    tipo ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. INSERIR CONFIGURAÇÕES PADRÃO
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES
('dias_aviso_vencimento', '7', 'Dias antes do vencimento para enviar aviso', 'number'),
('valor_minimo_aprovacao', '1000.00', 'Valor mínimo que requer aprovação do gestor', 'number'),
('permitir_anexos', 'true', 'Permitir upload de anexos nas contas', 'boolean'),
('tamanho_max_anexo', '5', 'Tamanho máximo do anexo em MB', 'number'),
('tipos_anexo_permitidos', '["pdf","jpg","jpeg","png","doc","docx","xlsx"]', 'Tipos de arquivo permitidos', 'json'),
('email_notificacoes', 'true', 'Enviar notificações por email', 'boolean')
ON DUPLICATE KEY UPDATE chave=chave;

-- 8. CRIAR DIRETÓRIO VIRTUAL PARA ANEXOS (necessita permissões no sistema)
-- mkdir -p uploads/anexos
-- chmod 755 uploads/anexos

-- 9. ATUALIZAR TRIGGER PARA CALCULAR PERCENTUAL DE METAS
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS atualizar_meta_orcamento
AFTER INSERT ON contas_pagar
FOR EACH ROW
BEGIN
    DECLARE v_orcado DECIMAL(10, 2);
    DECLARE v_gasto DECIMAL(10, 2);

    -- Atualizar meta do departamento/categoria
    UPDATE metas_orcamentos mo
    SET
        valor_gasto = (
            SELECT COALESCE(SUM(valor), 0)
            FROM contas_pagar
            WHERE departamento_id = mo.departamento_id
            AND (categoria_id = mo.categoria_id OR mo.categoria_id IS NULL)
            AND DATE_FORMAT(data_vencimento, '%Y-%m-01') = mo.mes_ano
            AND status = 'pago'
        ),
        percentual_utilizado = (valor_gasto / valor_orcado * 100),
        status = CASE
            WHEN (valor_gasto / valor_orcado * 100) > 100 THEN 'estourado'
            WHEN (valor_gasto / valor_orcado * 100) > 80 THEN 'atencao'
            ELSE 'ok'
        END
    WHERE departamento_id = NEW.departamento_id
    AND mes_ano = DATE_FORMAT(NEW.data_vencimento, '%Y-%m-01');
END$$

DELIMITER ;

-- 10. VIEW PARA DASHBOARD
CREATE OR REPLACE VIEW v_dashboard_stats AS
SELECT
    d.id as departamento_id,
    d.nome as departamento,
    COUNT(DISTINCT u.id) as total_usuarios,
    COUNT(DISTINCT c.id) as total_contas,
    SUM(CASE WHEN c.status = 'pendente' THEN c.valor ELSE 0 END) as valor_pendente,
    SUM(CASE WHEN c.status = 'pago' THEN c.valor ELSE 0 END) as valor_pago,
    SUM(CASE WHEN c.status = 'vencido' THEN c.valor ELSE 0 END) as valor_vencido,
    d.orcamento_mensal,
    (SUM(CASE WHEN c.status = 'pago' AND MONTH(c.data_pagamento) = MONTH(CURRENT_DATE) THEN c.valor ELSE 0 END) / d.orcamento_mensal * 100) as percentual_gasto
FROM departamentos d
LEFT JOIN usuarios u ON d.id = u.departamento_id AND u.ativo = TRUE
LEFT JOIN contas_pagar c ON d.id = c.departamento_id
WHERE d.ativo = TRUE
GROUP BY d.id, d.nome, d.orcamento_mensal;

-- =====================================================
-- DADOS DE TESTE (OPCIONAL)
-- =====================================================

-- Inserir meta de exemplo para departamento Financeiro
INSERT INTO metas_orcamentos (departamento_id, mes_ano, valor_orcado, criado_por)
SELECT
    id,
    DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'),
    orcamento_mensal,
    (SELECT id FROM usuarios WHERE role = 'admin' LIMIT 1)
FROM departamentos
WHERE nome = 'Financeiro'
ON DUPLICATE KEY UPDATE valor_orcado=valor_orcado;

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================

SELECT 'Recursos avançados instalados com sucesso!' as STATUS;
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('metas_orcamentos', 'notificacoes', 'relatorios_salvos', 'configuracoes');

-- Verificar novas colunas
SELECT COLUMN_NAME, DATA_TYPE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'contas_pagar'
AND COLUMN_NAME IN ('aprovacao_status', 'aprovado_por', 'data_aprovacao');
