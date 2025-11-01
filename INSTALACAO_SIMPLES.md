# Instalação Simplificada - Sistema de Contas a Pagar

Sistema essencial de gestão de contas a pagar com apenas os recursos fundamentais.

---

## 1. Execute o Banco de Dados

### Passo 1: Abra o phpMyAdmin
Acesse: `http://localhost/phpmyadmin` ou o painel do seu servidor

### Passo 2: Selecione o Banco de Dados
Clique no banco: `u411458227_studupss`

### Passo 3: Importe o Arquivo SQL
1. Clique na aba **SQL**
2. Copie e cole o conteúdo do arquivo **`database_simples.sql`**
3. Clique em **Executar**

---

## 2. Credenciais de Acesso

Acesse: `http://seusite.com/login.php`

```
Email: admin@sistema.com
Senha: admin123
```

**IMPORTANTE:** Altere a senha após o primeiro login!

---

## 3. Estrutura do Sistema

### Arquivos Essenciais (13 arquivos)

```
📁 Sistema de Contas a Pagar
├── 🔐 Autenticação
│   ├── login.php          - Login no sistema
│   ├── cadastro.php       - Cadastro de novos usuários
│   └── logout.php         - Sair do sistema
│
├── 📊 Páginas Principais
│   ├── dashboard.php      - Painel com estatísticas e gráficos
│   ├── contas.php         - Gerenciar contas a pagar
│   └── categorias.php     - Gerenciar categorias
│
├── ⚙️ Configurações
│   ├── config.php         - Conexão com banco de dados
│   ├── security.php       - Funções de segurança (CSRF, sessões)
│   └── style.css          - Estilos do sistema
│
└── 🗄️ Banco de Dados
    └── database_simples.sql - SQL simplificado (3 tabelas)
```

---

## 4. Recursos do Sistema

### O que o sistema FAZ:

- ✅ **Login e Cadastro** - Autenticação de usuários
- ✅ **Dashboard** - Visão geral com estatísticas e gráficos
- ✅ **Contas a Pagar** - Adicionar, editar, excluir e marcar como pago
- ✅ **Categorias** - Organizar despesas por categoria
- ✅ **Filtros** - Filtrar por status, categoria e mês
- ✅ **Gráficos** - Chart.js para visualização de dados
- ✅ **Segurança Básica** - CSRF, sessões seguras, rate limiting

### O que o sistema NÃO FAZ:

- ❌ Sistema de departamentos
- ❌ Níveis de acesso (admin/gestor/usuário)
- ❌ Kanban
- ❌ Aprovação de despesas
- ❌ Auditoria detalhada
- ❌ Metas e orçamentos
- ❌ Upload de anexos

---

## 5. Tabelas do Banco de Dados

### 3 Tabelas:

1. **usuarios** - Usuários do sistema
   - id, nome, email, senha, ativo

2. **categorias** - Categorias de despesas
   - id, usuario_id, nome, cor, ativo

3. **contas_pagar** - Contas a pagar
   - id, usuario_id, categoria_id, descricao, valor, data_vencimento, data_pagamento, status, observacoes

---

## 6. Configurar Conexão com Banco

Edite o arquivo **`config.php`**:

```php
$host = 'localhost';
$dbname = 'u411458227_studupss';
$username = 'SEU_USUARIO';  // <- ALTERE AQUI
$password = 'SUA_SENHA';     // <- ALTERE AQUI
```

---

## 7. Estrutura de Pastas

Não precisa criar pastas adicionais. Todos os arquivos ficam na raiz.

---

## 8. Como Usar

### Primeiro Acesso

1. Acesse `login.php`
2. Entre com: `admin@sistema.com` / `admin123`
3. Você será redirecionado para o dashboard

### Adicionar uma Conta

1. Vá em **Contas a Pagar**
2. Clique em **+ Nova Conta**
3. Preencha:
   - Descrição (ex: "Conta de Luz")
   - Valor (ex: "150,00")
   - Vencimento (ex: "15/02/2025")
   - Categoria (opcional)
   - Observações (opcional)
4. Clique em **Salvar**

### Marcar como Pago

1. Na lista de contas, clique no botão **✓** (check)
2. Informe a data do pagamento (ou deixe em branco para hoje)
3. Confirme

### Criar Categorias

1. Vá em **Categorias**
2. Clique em **+ Nova Categoria**
3. Escolha o nome e a cor
4. Salve

---

## 9. Solução de Problemas

### Não consigo fazer login

**Solução:**
1. Limpe o cache do navegador (Ctrl+Shift+Delete)
2. Verifique se executou o `database_simples.sql`
3. Use: `admin@sistema.com` / `admin123`

### Erro de conexão com banco

**Solução:**
1. Verifique o arquivo `config.php`
2. Confirme que o banco `u411458227_studupss` existe
3. Teste as credenciais de acesso ao MySQL

### Erro "Token de segurança inválido"

**Solução:**
1. Limpe os cookies do navegador
2. Recarregue a página (F5)
3. Tente novamente

---

## 10. Requisitos

- PHP 7.4+
- MySQL 5.7+
- Navegador moderno (Chrome, Firefox, Edge)

---

## 11. Segurança

O sistema possui:

- ✅ Proteção CSRF em todos os formulários
- ✅ Sessões seguras (httponly, secure, samesite)
- ✅ Rate limiting (5 tentativas de login por 5 minutos)
- ✅ Senhas criptografadas com bcrypt
- ✅ Prepared statements (proteção SQL injection)
- ✅ Sanitização de inputs (proteção XSS)

---

## 12. Arquivos Removidos

Os seguintes arquivos **NÃO são necessários** na versão simplificada:

```
❌ admin.php
❌ admin_dashboard.php
❌ admin_usuarios.php
❌ admin_usuarios_ajax.php
❌ admin_departamentos.php
❌ admin_departamentos_ajax.php
❌ admin_auditoria.php
❌ kanban.php
❌ kanban_ajax.php
❌ permissions.php
❌ painel.php
❌ database_completo.sql (use database_simples.sql)
❌ gerar_senha.php
❌ resetar_senha_admin.php
```

Você pode deletar esses arquivos se quiser manter apenas o essencial.

---

## 13. Resumo da Instalação

```
1. Importe database_simples.sql no phpMyAdmin
2. Configure config.php com suas credenciais
3. Acesse login.php
4. Entre com: admin@sistema.com / admin123
5. Comece a usar!
```

---

**Tempo de instalação:** 5 minutos

**Sistema criado em:** 2025
**Versão:** 1.0 Simplificada
