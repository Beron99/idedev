# 🚀 Instalação Simples - 3 Passos

## ⚠️ ANTES DE COMEÇAR

### No phpMyAdmin:
1. **Clique no nome do seu banco de dados** na lateral esquerda: `u411458227_studupss`
2. O banco ficará **destacado/selecionado**
3. Agora pode executar os SQLs

---

## 📝 PASSO 1: Estrutura Básica

### Arquivo: `database_fixo.sql`

1. Abra o arquivo **database_fixo.sql**
2. Copie **TODO** o conteúdo
3. No phpMyAdmin, vá na aba **SQL**
4. Cole o conteúdo
5. Clique em **Executar**

✅ **Resultado:** Tabelas `usuarios`, `categorias` e `contas_pagar` criadas

---

## 📝 PASSO 2: Sistema Administrativo

### Arquivo: `database_update_fixo.sql`

1. Abra o arquivo **database_update_fixo.sql**
2. Copie **TODO** o conteúdo
3. No phpMyAdmin, aba **SQL**
4. Cole o conteúdo
5. Clique em **Executar**

⚠️ **Se aparecer erro "Duplicate column name", ignore** - significa que a coluna já existe.

✅ **Resultado:**
- Tabelas `departamentos` e `auditoria` criadas
- 7 departamentos inseridos
- Usuário admin criado
- Novas colunas adicionadas

---

## 📝 PASSO 3: Recursos Avançados

### Arquivo: Execute BLOCO POR BLOCO

⚠️ **IMPORTANTE:** Não copie tudo de uma vez! Execute cada bloco separadamente.

### BLOCO 1 - Colunas de Aprovação

Cole e execute **cada linha separadamente**:

```sql
USE u411458227_studupss;

ALTER TABLE contas_pagar
ADD COLUMN aprovacao_status ENUM('pendente', 'aprovado', 'rejeitado') DEFAULT 'pendente' AFTER prioridade;
```

Se der erro "Duplicate column", ignore e passe para a próxima.

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

### BLOCO 2 - Tabela de Metas

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

### BLOCO 3 - Tabela de Notificações

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

### BLOCO 4 - Tabela de Relatórios

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

### BLOCO 5 - Tabela de Configurações

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

### BLOCO 6 - Configurações Padrão

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

### BLOCO 7 - View para Dashboard

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

### BLOCO 8 - Dados de Teste (OPCIONAL)

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

## ✅ VERIFICAÇÃO FINAL

Execute para verificar se tudo foi criado:

```sql
SHOW TABLES;
```

Você deve ter **9 tabelas**:
- auditoria
- categorias
- configuracoes
- contas_pagar
- departamentos
- metas_orcamentos
- notificacoes
- relatorios_salvos
- usuarios

---

## 🔑 FAZER LOGIN

Após instalar tudo:

**URL:** `http://seusite.com/login.php`

**Email:** `admin@sistema.com`
**Senha:** `Admin@123`

⚠️ **IMPORTANTE:** Troque a senha após o primeiro login!

---

## 📁 CRIAR PASTA PARA ANEXOS

Via FTP ou Gerenciador de Arquivos:
1. Crie a pasta: `uploads/anexos`
2. Permissão: **755**

---

## 🎯 PÁGINAS DO SISTEMA

Após login como admin, acesse:

- **Dashboard:** `dashboard.php`
- **Contas a Pagar:** `contas.php`
- **Categorias:** `categorias.php`
- **Admin Dashboard:** `admin.php?acao=dashboard`
- **Gerenciar Usuários:** `admin.php?acao=usuarios`
- **Gerenciar Departamentos:** `admin.php?acao=departamentos`
- **Log de Auditoria:** `admin.php?acao=auditoria`
- **Kanban:** `kanban.php`

---

## ❌ POSSÍVEIS ERROS

### Erro: "Nenhum banco de dados foi selecionado"
**Solução:** Clique no nome do banco `u411458227_studupss` na lateral esquerda do phpMyAdmin ANTES de executar o SQL.

### Erro: "Duplicate column name"
**Solução:** A coluna já existe. Ignore e continue.

### Erro: "Table already exists"
**Solução:** A tabela já foi criada. Continue para o próximo bloco.

---

## 🎉 PRONTO!

Seu sistema está completo e funcional! 🚀
