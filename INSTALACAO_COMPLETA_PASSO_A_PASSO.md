# 🚀 Instalação Completa do Sistema - Passo a Passo

## 📋 Ordem de Execução dos Arquivos SQL

Você precisa executar os arquivos SQL na seguinte ordem:

1. ✅ `database.sql` - Estrutura básica (tabelas principais)
2. ✅ `database_update.sql` - Sistema administrativo e departamentos
3. ✅ `database_recursos_avancados_fixo.sql` - Recursos avançados

---

## 🔹 ETAPA 1: Estrutura Básica

### Arquivo: `database.sql`

Este arquivo cria:
- Tabela `usuarios`
- Tabela `categorias`
- Tabela `contas_pagar`

**Como executar:**
1. Acesse o phpMyAdmin
2. Selecione seu banco de dados: `u411458227_studupss`
3. Vá na aba **SQL**
4. Abra o arquivo `database.sql` no seu editor de texto
5. Copie **TODO** o conteúdo
6. Cole no phpMyAdmin e clique em **Executar**

**Verificação:**
Execute este comando para verificar se as tabelas foram criadas:

```sql
SHOW TABLES;
```

Você deve ver:
- `categorias`
- `contas_pagar`
- `usuarios`

---

## 🔹 ETAPA 2: Sistema Administrativo

### Arquivo: `database_update.sql`

Este arquivo cria:
- Tabela `departamentos`
- Tabela `auditoria`
- Adiciona colunas em `usuarios` (role, departamento_id, etc.)
- Adiciona colunas em `contas_pagar` (departamento_id, prioridade, posicao_kanban)
- Insere 7 departamentos padrão
- Cria usuário admin

**Como executar:**
1. No phpMyAdmin, aba **SQL**
2. Abra o arquivo `database_update.sql`
3. Copie **TODO** o conteúdo
4. Cole e execute

**IMPORTANTE:** Se aparecer erro sobre colunas duplicadas, ignore - significa que já foram criadas.

**Verificação:**
```sql
SHOW TABLES;
```

Você deve ver agora:
- `auditoria`
- `categorias`
- `contas_pagar`
- `departamentos`
- `usuarios`

Verificar departamentos:
```sql
SELECT * FROM departamentos;
```

Deve mostrar 7 departamentos (Financeiro, TI, RH, Marketing, Operações, Comercial, Administrativo).

Verificar usuário admin:
```sql
SELECT id, nome, email, role FROM usuarios WHERE role = 'admin';
```

Deve mostrar o admin criado.

---

## 🔹 ETAPA 3: Recursos Avançados

### Arquivo: `database_recursos_avancados_fixo.sql`

Este arquivo adiciona:
- Campos de aprovação em `contas_pagar`
- Tabela `metas_orcamentos`
- Tabela `notificacoes`
- Tabela `relatorios_salvos`
- Tabela `configuracoes`
- Trigger para calcular metas
- View para dashboard

**Como executar:**

⚠️ **ATENÇÃO:** Execute em BLOCOS separados conforme abaixo:

### Bloco 1: Colunas de Aprovação

Cole e execute **cada comando separadamente**:

```sql
ALTER TABLE contas_pagar
ADD COLUMN aprovacao_status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente' AFTER prioridade;
```

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

### Bloco 2: Tabela de Metas

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

### Bloco 3: Tabela de Notificações

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

### Bloco 4: Tabela de Relatórios

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

### Bloco 5: Tabela de Configurações

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

### Bloco 6: Configurações Padrão

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

### Bloco 7: Trigger (⚠️ REQUER MUDAR DELIMITER)

**IMPORTANTE:** No phpMyAdmin, procure o campo "Delimiter" (geralmente embaixo da caixa SQL) e mude de `;` para `$$`

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

**Depois de executar, volte o Delimiter para `;`**

### Bloco 8: View para Dashboard

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

### Bloco 9: Dados de Teste (OPCIONAL)

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

## ✅ Verificação Final

Execute este comando para ver todas as tabelas:

```sql
SHOW TABLES;
```

Você deve ter **9 tabelas**:
1. auditoria
2. categorias
3. configuracoes
4. contas_pagar
5. departamentos
6. metas_orcamentos
7. notificacoes
8. relatorios_salvos
9. usuarios

Verificar estrutura completa de contas_pagar:

```sql
DESCRIBE contas_pagar;
```

Deve ter as colunas:
- id
- descricao
- valor
- data_vencimento
- data_pagamento
- status
- observacao
- categoria_id
- usuario_id
- data_cadastro
- **departamento_id** (novo)
- **prioridade** (novo)
- **posicao_kanban** (novo)
- **aprovacao_status** (novo)
- **aprovado_por** (novo)
- **data_aprovacao** (novo)
- **motivo_rejeicao** (novo)

---

## 📁 Criar Pasta para Anexos

Via FTP ou Gerenciador de Arquivos:
1. Crie a pasta: `uploads/anexos`
2. Permissão: **755**

---

## 🔑 Login no Sistema

Após instalar tudo, você pode fazer login com:

**Email:** `admin@sistema.com`
**Senha:** `Admin@123`

**⚠️ IMPORTANTE:** Troque a senha após o primeiro login!

---

## 🎯 URLs do Sistema

- **Login:** `login.php`
- **Dashboard:** `dashboard.php`
- **Contas a Pagar:** `contas.php`
- **Categorias:** `categorias.php`
- **Admin Dashboard:** `admin.php?acao=dashboard`
- **Gerenciar Usuários:** `admin.php?acao=usuarios`
- **Gerenciar Departamentos:** `admin.php?acao=departamentos`
- **Log de Auditoria:** `admin.php?acao=auditoria`
- **Kanban:** `kanban.php`

---

## ❌ Possíveis Erros

### "Table already exists"
**Solução:** A tabela já foi criada. Continue para o próximo comando.

### "Duplicate column name"
**Solução:** A coluna já existe. Continue para o próximo comando.

### "Unknown column in information_schema"
**Solução:** Você está executando os comandos de verificação antes de criar as tabelas. Execute primeiro os blocos 1-9, depois faça a verificação.

### Erro no Trigger
**Solução:**
1. Certifique-se de mudar o Delimiter para `$$` ANTES de executar
2. Após executar, volte o Delimiter para `;`
3. No phpMyAdmin, o campo Delimiter fica embaixo da caixa de texto SQL

---

## 🎉 Pronto!

Seu sistema está completo com:
- ✅ Sistema de login e cadastro
- ✅ Gestão de contas a pagar
- ✅ Categorias
- ✅ Dashboard com gráficos
- ✅ Sistema administrativo completo
- ✅ Gerenciamento de usuários e departamentos
- ✅ Sistema de permissões (Admin, Gestor, Usuário)
- ✅ Log de auditoria
- ✅ Visualização Kanban
- ✅ Sistema de aprovação de despesas
- ✅ Metas e orçamentos
- ✅ Segurança avançada (CSRF, rate limiting, etc.)
