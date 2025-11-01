-- =====================================================
-- ADICIONAR CONTAS RECORRENTES
-- Execute este SQL para adicionar o recurso de contas fixas mensais
-- =====================================================

USE u411458227_studupss;

-- =====================================================
-- ALTERAR TABELA: contas_pagar
-- Adicionar campos de recorrência
-- =====================================================

-- Adicionar coluna de recorrência
ALTER TABLE contas_pagar
ADD COLUMN recorrente BOOLEAN DEFAULT FALSE AFTER observacoes;

-- Adicionar tipo de recorrência
ALTER TABLE contas_pagar
ADD COLUMN tipo_recorrencia ENUM('mensal', 'bimestral', 'trimestral', 'semestral', 'anual') DEFAULT 'mensal' AFTER recorrente;

-- Adicionar dia do vencimento para recorrência
ALTER TABLE contas_pagar
ADD COLUMN dia_vencimento_recorrente INT DEFAULT NULL AFTER tipo_recorrencia;

-- Adicionar data de fim da recorrência (opcional)
ALTER TABLE contas_pagar
ADD COLUMN data_fim_recorrencia DATE DEFAULT NULL AFTER dia_vencimento_recorrente;

-- Adicionar flag se é uma conta gerada automaticamente
ALTER TABLE contas_pagar
ADD COLUMN gerada_automaticamente BOOLEAN DEFAULT FALSE AFTER data_fim_recorrencia;

-- Adicionar referência à conta recorrente original
ALTER TABLE contas_pagar
ADD COLUMN conta_recorrente_origem_id INT DEFAULT NULL AFTER gerada_automaticamente;

-- Adicionar índices
ALTER TABLE contas_pagar
ADD INDEX idx_recorrente (recorrente);

ALTER TABLE contas_pagar
ADD INDEX idx_gerada_auto (gerada_automaticamente);

ALTER TABLE contas_pagar
ADD INDEX idx_conta_origem (conta_recorrente_origem_id);

-- Adicionar FK para conta origem
ALTER TABLE contas_pagar
ADD CONSTRAINT fk_conta_origem
FOREIGN KEY (conta_recorrente_origem_id) REFERENCES contas_pagar(id)
ON DELETE SET NULL;

-- =====================================================
-- TABELA: log_contas_geradas
-- Para rastrear contas geradas automaticamente
-- =====================================================
CREATE TABLE IF NOT EXISTS log_contas_geradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conta_recorrente_id INT NOT NULL,
    conta_gerada_id INT NOT NULL,
    data_geracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    mes_referencia VARCHAR(7) NOT NULL, -- Formato: YYYY-MM

    FOREIGN KEY (conta_recorrente_id) REFERENCES contas_pagar(id) ON DELETE CASCADE,
    FOREIGN KEY (conta_gerada_id) REFERENCES contas_pagar(id) ON DELETE CASCADE,
    INDEX idx_conta_recorrente (conta_recorrente_id),
    INDEX idx_mes_ref (mes_referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VIEW: Contas Recorrentes Ativas
-- =====================================================
CREATE OR REPLACE VIEW v_contas_recorrentes_ativas AS
SELECT
    cp.*,
    cat.nome as categoria_nome,
    cat.cor as categoria_cor,
    COUNT(DISTINCT lcg.id) as total_contas_geradas,
    MAX(lcg.mes_referencia) as ultimo_mes_gerado
FROM contas_pagar cp
LEFT JOIN categorias cat ON cp.categoria_id = cat.id
LEFT JOIN log_contas_geradas lcg ON cp.id = lcg.conta_recorrente_id
WHERE cp.recorrente = TRUE
  AND (cp.data_fim_recorrencia IS NULL OR cp.data_fim_recorrencia >= CURRENT_DATE())
GROUP BY cp.id, cat.nome, cat.cor;

-- =====================================================
-- STORED PROCEDURE: Gerar Contas Recorrentes
-- =====================================================
DELIMITER //

CREATE PROCEDURE gerar_contas_recorrentes_mes(IN mes_referencia VARCHAR(7))
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id INT;
    DECLARE v_usuario_id INT;
    DECLARE v_categoria_id INT;
    DECLARE v_descricao VARCHAR(255);
    DECLARE v_valor DECIMAL(10,2);
    DECLARE v_tipo_recorrencia VARCHAR(20);
    DECLARE v_dia_vencimento INT;
    DECLARE v_observacoes TEXT;
    DECLARE v_data_vencimento DATE;
    DECLARE v_mes_ano VARCHAR(7);
    DECLARE v_nova_conta_id INT;

    -- Cursor para contas recorrentes ativas
    DECLARE cur CURSOR FOR
        SELECT
            id, usuario_id, categoria_id, descricao, valor,
            tipo_recorrencia, dia_vencimento_recorrente, observacoes
        FROM contas_pagar
        WHERE recorrente = TRUE
          AND (data_fim_recorrencia IS NULL OR data_fim_recorrencia >= CURRENT_DATE())
          AND id NOT IN (
              SELECT conta_recorrente_id
              FROM log_contas_geradas
              WHERE mes_referencia = mes_referencia
          );

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO v_id, v_usuario_id, v_categoria_id, v_descricao, v_valor,
                       v_tipo_recorrencia, v_dia_vencimento, v_observacoes;

        IF done THEN
            LEAVE read_loop;
        END IF;

        -- Calcular data de vencimento
        SET v_data_vencimento = STR_TO_DATE(
            CONCAT(mes_referencia, '-', LPAD(v_dia_vencimento, 2, '0')),
            '%Y-%m-%d'
        );

        -- Inserir nova conta
        INSERT INTO contas_pagar (
            usuario_id, categoria_id, descricao, valor, data_vencimento,
            observacoes, status, gerada_automaticamente, conta_recorrente_origem_id
        ) VALUES (
            v_usuario_id, v_categoria_id,
            CONCAT(v_descricao, ' (', DATE_FORMAT(v_data_vencimento, '%m/%Y'), ')'),
            v_valor, v_data_vencimento,
            v_observacoes, 'pendente', TRUE, v_id
        );

        SET v_nova_conta_id = LAST_INSERT_ID();

        -- Registrar no log
        INSERT INTO log_contas_geradas (
            conta_recorrente_id, conta_gerada_id, mes_referencia
        ) VALUES (
            v_id, v_nova_conta_id, mes_referencia
        );

    END LOOP;

    CLOSE cur;
END //

DELIMITER ;

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================
SELECT 'Campos de recorrência adicionados com sucesso!' as status;

-- Ver estrutura atualizada
DESCRIBE contas_pagar;

-- Ver log de contas geradas
SELECT COUNT(*) as total_logs FROM log_contas_geradas;

-- =====================================================
-- INFORMAÇÕES
-- =====================================================
-- Alteração: contas_pagar
--   - recorrente (BOOLEAN) - Indica se é conta recorrente
--   - tipo_recorrencia (ENUM) - mensal, bimestral, trimestral, semestral, anual
--   - dia_vencimento_recorrente (INT) - Dia do mês para vencimento (1-31)
--   - data_fim_recorrencia (DATE) - Data final da recorrência (opcional)
--   - gerada_automaticamente (BOOLEAN) - Se foi gerada pelo sistema
--   - conta_recorrente_origem_id (INT) - Referência à conta original
--
-- Nova tabela: log_contas_geradas
--   - Rastreia todas as contas geradas automaticamente
--   - Evita duplicação de contas no mesmo mês
--
-- Nova view: v_contas_recorrentes_ativas
--   - Lista contas recorrentes que ainda estão ativas
--   - Mostra quantas contas foram geradas
--   - Mostra último mês gerado
--
-- Nova Stored Procedure: gerar_contas_recorrentes_mes
--   - Gera automaticamente contas para um mês específico
--   - Evita duplicação verificando o log
--   - Adiciona mês/ano na descrição
-- =====================================================

-- =====================================================
-- EXEMPLO DE USO
-- =====================================================
-- Para gerar contas do próximo mês manualmente:
-- CALL gerar_contas_recorrentes_mes('2025-12');
-- =====================================================
