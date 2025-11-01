# 💰 Sistema de Gestão Financeira

Sistema completo de gestão de contas a pagar com dashboard, gráficos, sistema administrativo, departamentos, Kanban e muito mais.

---

## 🚀 Instalação Rápida

### 1. Execute o Banco de Dados

Abra o phpMyAdmin e execute o arquivo:

```
database_completo.sql
```

**Guia completo:** [COMO_INSTALAR.md](COMO_INSTALAR.md)

### 2. Faça Upload dos Arquivos

Envie todos os arquivos `.php` e `.css` para seu servidor via FTP.

### 3. Crie a Pasta de Anexos

```
uploads/anexos/ (permissão 755)
```

### 4. Acesse o Sistema

```
http://seusite.com/login.php
```

**Credenciais:**
- Email: `admin@sistema.com`
- Senha: `Admin@123`

---

## 📁 Estrutura de Arquivos

### Arquivos Principais

```
├── login.php              # Página de login
├── cadastro.php           # Cadastro de usuários
├── dashboard.php          # Dashboard principal
├── contas.php             # Gerenciamento de contas a pagar
├── categorias.php         # Gerenciamento de categorias
├── kanban.php             # Visualização Kanban
├── admin.php              # Painel administrativo principal
├── style.css              # Estilos do sistema
├── config.php             # Configurações do banco
├── security.php           # Funções de segurança
└── permissions.php        # Sistema de permissões
```

### Arquivos Administrativos

```
├── admin_dashboard.php         # Dashboard do admin
├── admin_usuarios.php          # Gerenciar usuários
├── admin_usuarios_ajax.php     # Backend de usuários
├── admin_departamentos.php     # Gerenciar departamentos
├── admin_departamentos_ajax.php # Backend de departamentos
├── admin_auditoria.php         # Log de auditoria
└── kanban_ajax.php             # Backend do Kanban
```

### Arquivos de Instalação

```
├── database_completo.sql       # Banco de dados completo (USE ESTE!)
├── COMO_INSTALAR.md           # Guia de instalação
├── README_COMPLETO.md         # Este arquivo
└── SEGURANCA.md               # Documentação de segurança
```

---

## ✨ Funcionalidades

### 🎯 Recursos Principais

- ✅ **Dashboard com Gráficos** - Visualização de gastos com Chart.js
- ✅ **Gestão de Contas a Pagar** - Cadastro, edição e controle de contas
- ✅ **Categorias Personalizadas** - Organize suas despesas
- ✅ **Visualização Kanban** - Arraste e solte para gerenciar status
- ✅ **Filtros Avançados** - Filtre por período, categoria, status, etc.
- ✅ **Relatórios** - Relatórios detalhados de gastos

### 👥 Sistema Administrativo

- ✅ **3 Níveis de Acesso** - Admin, Gestor, Usuário
- ✅ **Gerenciamento de Usuários** - CRUD completo de usuários
- ✅ **Departamentos** - Organize por departamentos
- ✅ **Metas e Orçamentos** - Controle de orçamento mensal
- ✅ **Log de Auditoria** - Rastreie todas as ações do sistema
- ✅ **Sistema de Aprovação** - Workflow de aprovação de despesas

### 🔒 Segurança

- ✅ **Proteção CSRF** - Tokens de segurança em todos os formulários
- ✅ **Sessões Seguras** - Cookies httponly, secure e samesite
- ✅ **Rate Limiting** - Proteção contra força bruta (5 tentativas/5min)
- ✅ **Senhas Fortes** - Validação de senha forte obrigatória
- ✅ **SQL Injection Protection** - Prepared statements em todas as queries
- ✅ **XSS Protection** - Sanitização de entradas
- ✅ **Auditoria Completa** - Log de todas as ações com IP e User Agent

---

## 👥 Níveis de Acesso

### 🔴 Administrador
- Acesso total ao sistema
- Gerenciar usuários e departamentos
- Ver todas as contas de todos os departamentos
- Acessar log de auditoria
- Configurar sistema

### 🔵 Gestor
- Gerenciar contas do seu departamento
- Ver usuários do departamento
- Aprovar/rejeitar despesas
- Ver relatórios do departamento

### ⚪ Usuário
- Cadastrar suas próprias contas
- Ver suas contas
- Solicitar aprovações
- Gerar relatórios pessoais

---

## 🗃️ Banco de Dados

### Tabelas (9)

1. **usuarios** - Usuários do sistema
2. **departamentos** - Departamentos da empresa
3. **categorias** - Categorias de despesas
4. **contas_pagar** - Contas a pagar
5. **auditoria** - Log de ações
6. **metas_orcamentos** - Metas e orçamentos
7. **notificacoes** - Notificações do sistema
8. **relatorios_salvos** - Relatórios gerados
9. **configuracoes** - Configurações do sistema

### Dados Pré-Cadastrados

**7 Departamentos:**
- Financeiro (R$ 50.000/mês)
- Recursos Humanos (R$ 30.000/mês)
- TI / Tecnologia (R$ 40.000/mês)
- Vendas (R$ 25.000/mês)
- Marketing (R$ 20.000/mês)
- Operações (R$ 35.000/mês)
- Administrativo (R$ 15.000/mês)

**1 Usuário Admin:**
- Email: admin@sistema.com
- Senha: Admin@123

---

## 🎨 Páginas do Sistema

### Usuário Comum

| Página | URL | Descrição |
|--------|-----|-----------|
| Login | `login.php` | Autenticação |
| Cadastro | `cadastro.php` | Novo usuário |
| Dashboard | `dashboard.php` | Visão geral com gráficos |
| Contas | `contas.php` | Lista de contas a pagar |
| Categorias | `categorias.php` | Gerenciar categorias |
| Kanban | `kanban.php` | Board Kanban |

### Administrador

| Página | URL | Descrição |
|--------|-----|-----------|
| Admin Dashboard | `admin.php?acao=dashboard` | Dashboard admin |
| Usuários | `admin.php?acao=usuarios` | Gerenciar usuários |
| Departamentos | `admin.php?acao=departamentos` | Gerenciar departamentos |
| Auditoria | `admin.php?acao=auditoria` | Log de auditoria |

---

## 🎯 Como Usar

### 1. Primeiro Acesso

1. Acesse `login.php`
2. Entre com: admin@sistema.com / Admin@123
3. **Altere a senha imediatamente!**

### 2. Criar Usuários

1. Vá em `admin.php?acao=usuarios`
2. Clique em "Novo Usuário"
3. Preencha os dados
4. Defina a função (Admin, Gestor ou Usuário)
5. Escolha o departamento
6. Clique em "Salvar"

### 3. Cadastrar Contas

1. Vá em `contas.php`
2. Clique em "Nova Conta"
3. Preencha:
   - Descrição
   - Valor
   - Data de vencimento
   - Categoria
   - Departamento
   - Prioridade
4. Clique em "Salvar"

### 4. Usar o Kanban

1. Acesse `kanban.php`
2. Arraste os cards entre as colunas:
   - **A Fazer** - Contas pendentes
   - **Em Análise** - Aguardando aprovação
   - **Aprovado** - Contas aprovadas
   - **Pago** - Contas pagas

---

## 🔧 Configuração

### Arquivo: config.php

Edite as credenciais do banco de dados:

```php
$host = 'localhost';
$dbname = 'u411458227_studupss';
$username = 'seu_usuario';
$password = 'sua_senha';
```

### Configurações do Sistema

Acesse via banco de dados na tabela `configuracoes`:

- `dias_aviso_vencimento` - Dias para avisar antes do vencimento (padrão: 7)
- `valor_minimo_aprovacao` - Valor que requer aprovação (padrão: R$ 1.000)
- `permitir_anexos` - Permitir upload de arquivos (padrão: true)
- `tamanho_max_anexo` - Tamanho máximo em MB (padrão: 5)

---

## 📊 Gráficos e Relatórios

### Dashboard Principal

- **Estatísticas:**
  - Total de contas pendentes
  - Total de contas pagas
  - Total de contas vencidas
  - Valor total por status

- **Gráficos (Chart.js):**
  - Contas por status (Doughnut)
  - Gastos por categoria (Bar)
  - Próximas contas a vencer (List)

### Dashboard Admin

- **Estatísticas:**
  - Aprovações pendentes
  - Usuários ativos
  - Total de departamentos
  - Metas estouradas

- **Gráficos:**
  - Usuários por função (Doughnut)
  - Contas por status (Bar)
  - Top 5 gastos por departamento (Horizontal Bar)

---

## 🔐 Segurança

### Recursos Implementados

1. **Proteção CSRF**
   - Token único por sessão
   - Validação em todos os formulários
   - Regeneração automática

2. **Autenticação Segura**
   - Senhas hasheadas com bcrypt
   - Validação de senha forte
   - Sessões com timeout

3. **Rate Limiting**
   - Máximo 5 tentativas de login
   - Bloqueio por 5 minutos
   - Log de tentativas falhadas

4. **SQL Injection Protection**
   - Prepared statements PDO
   - Parâmetros vinculados
   - Sem concatenação de SQL

5. **XSS Protection**
   - Sanitização de inputs
   - htmlspecialchars em outputs
   - Validação de tipos

6. **Auditoria**
   - Log de todas as ações
   - Rastreamento de IP
   - User Agent registrado

### Boas Práticas

- ✅ Sempre use HTTPS em produção
- ✅ Altere a senha do admin após instalação
- ✅ Faça backup regular do banco de dados
- ✅ Mantenha o PHP atualizado (7.4+)
- ✅ Configure permissões de arquivo adequadas

**Documentação completa:** [SEGURANCA.md](SEGURANCA.md)

---

## 🐛 Solução de Problemas

### Login não funciona

1. Limpe o cache do navegador (Ctrl+Shift+Delete)
2. Verifique as credenciais: admin@sistema.com / Admin@123
3. Verifique se o banco está conectado (teste em config.php)

### Erro "Token de segurança inválido"

1. Limpe os cookies do navegador
2. Recarregue a página (F5)
3. Tente novamente

### Erro ao importar banco de dados

1. Verifique se selecionou o banco correto
2. Use a opção "Importar" em vez de copiar/colar
3. Aumente o timeout do PHP no phpMyAdmin

### Kanban não atualiza

1. Verifique se JavaScript está habilitado
2. Abra o Console (F12) e veja se há erros
3. Limpe o cache e recarregue

---

## 📝 Requisitos

### Servidor

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache ou Nginx
- mod_rewrite habilitado

### Navegador

- Chrome 90+
- Firefox 88+
- Edge 90+
- Safari 14+

### Extensões PHP Necessárias

- PDO
- PDO_MySQL
- mbstring
- json

---

## 📚 Tecnologias Utilizadas

- **Backend:** PHP 7.4+
- **Banco de Dados:** MySQL 8.0
- **Frontend:** HTML5, CSS3, JavaScript
- **Gráficos:** Chart.js 4.4.0
- **Segurança:** CSRF, PDO, bcrypt
- **Design:** CSS Grid, Flexbox, Responsivo

---

## 👨‍💻 Autor

Sistema desenvolvido com ❤️ por **Claude + Hesron**

**Versão:** 1.0
**Data:** 2025

---

**🎉 Obrigado por usar nosso Sistema de Gestão Financeira!**
