-- =====================================================
-- CORRIGIR SENHA DO ADMINISTRADOR
-- =====================================================

-- SOLUÇÃO 1: Atualizar com senha simples temporária
-- Senha temporária: admin123 (SEM maiúscula e caracteres especiais)
-- Você pode alterar depois no sistema

USE u411458227_studupss;

-- Atualizar o usuário admin com nova senha
UPDATE usuarios
SET senha = '$2y$10$eUITICgfgN5.ZZ6wI1N.HOqYKQcuV0WfGZJslGbELRdSCKwzRZfBi'
WHERE email = 'admin@sistema.com';

-- Verificar se foi atualizado
SELECT id, nome, email, role, ativo
FROM usuarios
WHERE email = 'admin@sistema.com';

-- =====================================================
-- NOVAS CREDENCIAIS TEMPORÁRIAS
-- =====================================================
-- Email: admin@sistema.com
-- Senha: admin123
--
-- IMPORTANTE: Entre no sistema e altere a senha imediatamente!
-- =====================================================

-- Se preferir, pode criar um novo admin com essas credenciais:
-- (Descomente as linhas abaixo se quiser criar um novo usuário)

/*
DELETE FROM usuarios WHERE email = 'admin@sistema.com';

INSERT INTO usuarios (nome, email, senha, role, ativo) VALUES
('Administrador', 'admin@sistema.com', '$2y$10$eUITICgfgN5.ZZ6wI1N.HOqYKQcuV0WfGZJslGbELRdSCKwzRZfBi', 'admin', TRUE);
*/
