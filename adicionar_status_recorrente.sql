-- =====================================================
-- ADICIONAR STATUS "RECORRENTE" AO SISTEMA
-- Execute este SQL para adicionar o novo status
-- =====================================================

USE u411458227_studupss;

-- =====================================================
-- Alterar coluna status para incluir 'recorrente'
-- =====================================================

ALTER TABLE contas_pagar
MODIFY COLUMN status ENUM('pendente', 'pago', 'vencido', 'recorrente') DEFAULT 'pendente';

-- =====================================================
-- Atualizar contas recorrentes existentes para o novo status
-- =====================================================

UPDATE contas_pagar
SET status = 'recorrente'
WHERE recorrente = TRUE
  AND gerada_automaticamente = FALSE
  AND (data_fim_recorrencia IS NULL OR data_fim_recorrencia >= CURRENT_DATE());

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================

-- Ver contas com status recorrente
SELECT id, descricao, valor, status, recorrente, tipo_recorrencia
FROM contas_pagar
WHERE status = 'recorrente'
LIMIT 10;

-- =====================================================
-- INFORMAÇÕES
-- =====================================================
-- Status disponíveis agora:
--   - pendente: Contas normais a pagar
--   - pago: Contas já pagas
--   - vencido: Contas vencidas não pagas
--   - recorrente: Contas recorrentes (templates)
--
-- Lógica:
--   - Contas recorrentes ORIGINAIS = status 'recorrente'
--   - Contas GERADAS automaticamente = status 'pendente' (podem virar 'pago' ou 'vencido')
-- =====================================================
