# 🚀 Guia de Instalação - Recursos Avançados

## ⚠️ IMPORTANTE: Execute no phpMyAdmin

Este guia explica como executar o SQL dos recursos avançados no phpMyAdmin.

---

## 📋 Passo a Passo

### 1️⃣ **Acesse o phpMyAdmin**
- Entre no seu phpMyAdmin
- Selecione o banco de dados: `u411458227_studupss`

### 2️⃣ **Vá para a aba SQL**
- Clique na aba "SQL" no topo da página

### 3️⃣ **Execute os comandos em ETAPAS**

**IMPORTANTE:** NÃO execute o arquivo inteiro de uma vez. Execute em blocos separados conforme abaixo:

---

## 🔹 BLOCO 1: Adicionar Colunas na Tabela contas_pagar

Cole e execute um por vez:

```sql
ALTER TABLE contas_pagar
ADD COLUMN aprovacao_status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente' AFTER prioridade;
```

Se der erro dizendo que a coluna já existe, ignore e passe para a próxima.

```sql
ALTER TABLE contas_pagar
ADD COLUMN aprovado_por INT AFTER aprovacao_status;
```

```sql
ALTER TABLE contas_pagar
ADD COLUMN data_aprovacao TIMESTAMP NULL AFTER aprovado_por;
```

```sql
ALTER TABLE contas_pagar
ADD COLUMN motivo_rejeicao TEXT AFTER data_aprovacao;
```

```sql
ALTER TABLE contas_pagar
ADD INDEX idx_aprovacao (aprovacao_status);
```

```sql
ALTER TABLE contas_pagar
ADD INDEX idx_aprovado_por (aprovado_por);
```

```sql
ALTER TABLE contas_pagar
ADD CONSTRAINT fk_aprovado_por
FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL;
```

---

## 🔹 BLOCO 2: Criar Tabela de Metas e Orçamentos

```sql
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
```

---

## 🔹 BLOCO 3: Criar Tabela de Notificações

```sql
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
```

---

## 🔹 BLOCO 4: Criar Tabela de Relatórios Salvos

```sql
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
```

---

## 🔹 BLOCO 5: Criar Tabela de Configurações

```sql
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT,
    descricao TEXT,
    tipo ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔹 BLOCO 6: Inserir Configurações Padrão

```sql
INSERT INTO configuracoes (chave, valor, descricao, tipo) VALUES
('dias_aviso_vencimento', '7', 'Dias antes do vencimento para enviar aviso', 'number'),
('valor_minimo_aprovacao', '1000.00', 'Valor mínimo que requer aprovação do gestor', 'number'),
('permitir_anexos', 'true', 'Permitir upload de anexos nas contas', 'boolean'),
('tamanho_max_anexo', '5', 'Tamanho máximo do anexo em MB', 'number'),
('tipos_anexo_permitidos', '["pdf","jpg","jpeg","png","doc","docx","xlsx"]', 'Tipos de arquivo permitidos', 'json'),
('email_notificacoes', 'true', 'Enviar notificações por email', 'boolean')
ON DUPLICATE KEY UPDATE chave=chave;
```

---

## 🔹 BLOCO 7: Criar Trigger (⚠️ ATENÇÃO: Mudar DELIMITER)

**No phpMyAdmin, você precisa mudar o Delimiter:**

1. No campo SQL, procure por "Delimiter" (normalmente está embaixo da caixa de texto)
2. Mude de `;` para `$$`
3. Cole e execute:

```sql
DROP TRIGGER IF EXISTS atualizar_meta_orcamento$$

CREATE TRIGGER atualizar_meta_orcamento
AFTER INSERT ON contas_pagar
FOR EACH ROW
BEGIN
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
```

4. **IMPORTANTE:** Depois de executar, volte o Delimiter para `;`

---

## 🔹 BLOCO 8: Criar View para Dashboard

```sql
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
GROUP BY d.id, d.nome, d.orcamento_mensal;
```

---

## 🔹 BLOCO 9: Inserir Dados de Teste (OPCIONAL)

```sql
INSERT INTO metas_orcamentos (departamento_id, mes_ano, valor_orcado, criado_por)
SELECT
    id,
    DATE_FORMAT(CURRENT_DATE, '%Y-%m-01'),
    orcamento_mensal,
    (SELECT id FROM usuarios WHERE role = 'admin' LIMIT 1)
FROM departamentos
ON DUPLICATE KEY UPDATE valor_orcado=valor_orcado;
```

---

## ✅ Verificação

Execute para verificar se tudo foi criado:

```sql
SHOW TABLES;
```

Você deve ver as novas tabelas:
- metas_orcamentos
- notificacoes
- relatorios_salvos
- configuracoes

Para verificar as novas colunas em contas_pagar:

```sql
SHOW COLUMNS FROM contas_pagar;
```

Você deve ver:
- aprovacao_status
- aprovado_por
- data_aprovacao
- motivo_rejeicao

---

## 📁 Criar Pasta para Anexos

**IMPORTANTE:** Você precisa criar manualmente a pasta para upload de anexos:

1. Via FTP ou Gerenciador de Arquivos do seu hosting
2. Crie a pasta: `uploads/anexos`
3. Dê permissão **755** para a pasta

---

## ❌ Possíveis Erros e Soluções

### Erro: "Duplicate column name"
**Solução:** A coluna já existe. Pule para o próximo comando.

### Erro: "Can't DROP 'idx_aprovacao'; check that column/key exists"
**Solução:** O índice não existe. Ignore e continue.

### Erro: "Duplicate key name"
**Solução:** O índice já foi criado. Continue para o próximo comando.

### Erro com TRIGGER
**Solução:** Certifique-se de que mudou o Delimiter para `$$` antes de executar e voltou para `;` depois.

---

## 🎉 Conclusão

Após executar todos os blocos com sucesso, seu banco de dados estará pronto para usar todos os recursos avançados:

✅ Sistema de aprovação de contas
✅ Metas e orçamentos por departamento
✅ Sistema de notificações
✅ Relatórios salvos
✅ Configurações do sistema
✅ Views otimizadas para dashboard

Agora você pode acessar as novas páginas:
- `admin.php?acao=dashboard` - Dashboard administrativo
- `admin.php?acao=usuarios` - Gerenciar usuários
- `admin.php?acao=departamentos` - Gerenciar departamentos
- `admin.php?acao=auditoria` - Log de auditoria
- `kanban.php` - Visualização Kanban
