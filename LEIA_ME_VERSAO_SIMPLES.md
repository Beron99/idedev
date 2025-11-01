# Sistema de Contas a Pagar - VERSÃO SIMPLIFICADA

**Versão:** 1.0 Essencial
**Data:** 2025

---

## O QUE MUDOU?

Simplifiquei o sistema para conter **apenas os recursos essenciais** para um sistema de contas a pagar funcional.

---

## ARQUIVOS NECESSÁRIOS (10 arquivos)

### Use APENAS estes arquivos:

```
✅ login.php              - Página de login
✅ cadastro.php           - Cadastro de usuários
✅ logout.php             - Sair do sistema
✅ dashboard.php          - Dashboard com gráficos
✅ contas.php             - Gerenciar contas a pagar
✅ categorias.php         - Gerenciar categorias
✅ config.php             - Configuração do banco
✅ security.php           - Segurança (CSRF, sessões)
✅ style.css              - Estilos do sistema
✅ database_simples.sql   - Banco de dados (3 tabelas)
```

---

## ARQUIVOS QUE VOCÊ PODE DELETAR (12 arquivos)

Estes arquivos fazem parte da versão avançada e **NÃO são necessários**:

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
```

---

## INSTALAÇÃO RÁPIDA (3 passos)

### 1. Importe o Banco de Dados

No phpMyAdmin, execute o arquivo:
```
database_simples.sql
```

### 2. Configure a Conexão

Edite o arquivo [config.php](config.php):
```php
$username = 'seu_usuario';  // <- Altere aqui
$password = 'sua_senha';     // <- Altere aqui
```

### 3. Acesse o Sistema

```
http://seusite.com/login.php

Email: admin@sistema.com
Senha: admin123
```

**Pronto! Sistema funcionando em 5 minutos.**

---

## RECURSOS DISPONÍVEIS

### ✅ O que o sistema FAZ:

1. **Autenticação**
   - Login e cadastro de usuários
   - Sessões seguras
   - Proteção CSRF

2. **Dashboard**
   - Estatísticas de contas (pendentes, pagas, vencidas)
   - Gráfico de gastos por categoria
   - Gráfico de evolução mensal
   - Próximas contas a vencer

3. **Contas a Pagar**
   - Adicionar nova conta
   - Editar conta existente
   - Excluir conta
   - Marcar como pago
   - Filtrar por status, categoria e mês

4. **Categorias**
   - Criar categorias personalizadas
   - Escolher cor da categoria
   - Editar e excluir categorias

5. **Segurança**
   - CSRF protection
   - Rate limiting (5 tentativas/5min)
   - Senhas criptografadas (bcrypt)
   - SQL injection protection
   - XSS protection

### ❌ O que o sistema NÃO FAZ:

- Departamentos
- Níveis de acesso (admin/gestor/usuário)
- Kanban
- Aprovação de despesas
- Auditoria detalhada
- Metas e orçamentos
- Upload de anexos
- Relatórios PDF/Excel
- Notificações

---

## BANCO DE DADOS

### Apenas 3 Tabelas:

1. **usuarios**
   - Armazena dados dos usuários
   - Campos: id, nome, email, senha, ativo

2. **categorias**
   - Categorias de despesas
   - Campos: id, usuario_id, nome, cor, ativo

3. **contas_pagar**
   - Contas a pagar
   - Campos: id, usuario_id, categoria_id, descricao, valor, data_vencimento, data_pagamento, status, observacoes

---

## FLUXO DE USO

```
1. Login → Dashboard
2. Ver estatísticas e gráficos
3. Ir em "Contas a Pagar"
4. Clicar em "+ Nova Conta"
5. Preencher dados e salvar
6. Filtrar por status/categoria/mês
7. Marcar como pago quando pagou
```

---

## COMPARAÇÃO: VERSÃO COMPLETA vs. SIMPLIFICADA

| Recurso | Versão Completa | Versão Simples |
|---------|----------------|----------------|
| Tabelas no Banco | 9 tabelas | 3 tabelas |
| Arquivos PHP | 21 arquivos | 10 arquivos |
| Linhas de CSS | 1500+ | 800 |
| Níveis de Acesso | 3 (Admin/Gestor/Usuário) | 1 (Usuário) |
| Departamentos | ✅ Sim | ❌ Não |
| Kanban | ✅ Sim | ❌ Não |
| Aprovação | ✅ Sim | ❌ Não |
| Auditoria | ✅ Sim | ❌ Não |
| Dashboard | ✅ Avançado | ✅ Básico |
| Gráficos | ✅ Chart.js | ✅ Chart.js |
| Segurança | ✅ Completa | ✅ Básica |

---

## VANTAGENS DA VERSÃO SIMPLIFICADA

✅ **Mais rápido** - Menos queries ao banco
✅ **Mais leve** - Menos arquivos para carregar
✅ **Mais simples** - Fácil de entender e manter
✅ **Menos bugs** - Menos código = menos pontos de falha
✅ **Foco** - Apenas o essencial para contas a pagar

---

## QUANDO USAR CADA VERSÃO?

### Use a VERSÃO SIMPLIFICADA se:
- Você quer apenas gerenciar contas a pagar
- Não precisa de múltiplos departamentos
- Não precisa de aprovação de despesas
- Quer algo rápido e funcional
- É para uso pessoal ou pequena empresa

### Use a VERSÃO COMPLETA se:
- Precisa de múltiplos departamentos
- Precisa de níveis de acesso (admin/gestor/usuário)
- Precisa de aprovação de despesas
- Precisa de auditoria detalhada
- Precisa de Kanban
- É para empresa média/grande

---

## GUIA COMPLETO

Leia o arquivo [INSTALACAO_SIMPLES.md](INSTALACAO_SIMPLES.md) para instruções detalhadas.

---

## ESTRUTURA DE NAVEGAÇÃO

```
Login
  ↓
Dashboard (página inicial)
  ├── Ver estatísticas
  ├── Ver gráficos
  └── Ver próximas contas

Menu de Navegação:
  ├── Dashboard
  ├── Contas a Pagar
  │   ├── Adicionar
  │   ├── Editar
  │   ├── Excluir
  │   └── Marcar como Pago
  └── Categorias
      ├── Adicionar
      ├── Editar
      └── Excluir
```

---

## REQUISITOS MÍNIMOS

- PHP 7.4+
- MySQL 5.7+
- Apache ou Nginx
- Navegador moderno

---

## PROBLEMAS COMUNS

### 1. Não consigo logar
**Solução:** Use `admin@sistema.com` / `admin123` e limpe o cache

### 2. Erro de conexão com banco
**Solução:** Verifique [config.php](config.php) e as credenciais do MySQL

### 3. Token inválido
**Solução:** Limpe cookies e recarregue a página

---

## SEGURANÇA

Mesmo sendo simplificado, o sistema mantém:

- ✅ Proteção CSRF
- ✅ Sessões seguras
- ✅ Rate limiting
- ✅ Senhas bcrypt
- ✅ Prepared statements
- ✅ Input sanitization

---

## PRÓXIMOS PASSOS

1. Execute [database_simples.sql](database_simples.sql)
2. Configure [config.php](config.php)
3. Acesse [login.php](login.php)
4. Entre com admin@sistema.com / admin123
5. **Altere a senha!**
6. Comece a cadastrar suas contas

---

## SUPORTE

### Não funciona?

1. Verifique se executou o SQL corretamente
2. Verifique se o [config.php](config.php) está configurado
3. Limpe o cache do navegador
4. Consulte [INSTALACAO_SIMPLES.md](INSTALACAO_SIMPLES.md)

---

## RESUMO

**Sistema Simplificado de Contas a Pagar**

- 10 arquivos essenciais
- 3 tabelas no banco
- 5 minutos de instalação
- Funcional e seguro
- Fácil de manter

**Tudo que você precisa, nada que você não precisa.**

---

**Criado em:** 2025
**Versão:** 1.0 Essencial
**Tempo de instalação:** 5 minutos
