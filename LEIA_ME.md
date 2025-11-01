# Sistema de Contas a Pagar - Instalação Rápida

## Instalação em 3 Passos

### 1️⃣ Limpar o Banco Atual

No phpMyAdmin:
1. Selecione o banco `u411458227_studupss`
2. Clique na aba **Estrutura**
3. **Marque todas as tabelas** (checkbox no topo)
4. Clique em **Apagar** (Drop)
5. Confirme

### 2️⃣ Executar o SQL

1. Ainda no banco `u411458227_studupss`
2. Vá na aba **SQL**
3. Copie TODO o conteúdo do arquivo **`instalar.sql`**
4. Cole na área SQL
5. Clique em **Executar**

### 3️⃣ Acessar o Sistema

```
URL: http://seusite.com/login.php
Email: admin@sistema.com
Senha: admin123
```

**Pronto! Sistema instalado.**

---

## Estrutura do Sistema

### Arquivos Essenciais (10 arquivos)

```
✅ login.php          - Login
✅ cadastro.php       - Cadastro de usuários
✅ logout.php         - Logout
✅ dashboard.php      - Dashboard com gráficos
✅ contas.php         - Gerenciar contas a pagar
✅ categorias.php     - Gerenciar categorias
✅ config.php         - Configuração do banco
✅ security.php       - Segurança
✅ style.css          - Estilos
✅ instalar.sql       - SQL de instalação
```

### Banco de Dados (3 tabelas)

```
✅ usuarios         - Usuários do sistema
✅ categorias       - Categorias de despesas
✅ contas_pagar     - Contas a pagar
```

---

## Recursos do Sistema

✅ Login e autenticação segura
✅ Dashboard com estatísticas
✅ Gráficos (Chart.js)
✅ CRUD de contas a pagar
✅ CRUD de categorias
✅ Filtros (status, categoria, mês)
✅ Proteção CSRF
✅ Rate limiting
✅ Sessões seguras

---

## Configuração

Edite o arquivo **`config.php`**:

```php
$host = 'localhost';
$dbname = 'u411458227_studupss';
$username = 'SEU_USUARIO';     // ← ALTERE
$password = 'SUA_SENHA';       // ← ALTERE
```

---

## Uso Rápido

### Adicionar uma conta

1. Acesse **Contas a Pagar**
2. Clique em **+ Nova Conta**
3. Preencha os dados
4. Salvar

### Marcar como paga

1. Na lista, clique no botão **✓**
2. Informe a data do pagamento
3. Confirme

---

## Solução de Problemas

### Erro ao executar SQL

**Solução:** Apague todas as tabelas manualmente antes de executar o instalar.sql

### Não consigo logar

**Solução:**
- Limpe o cache (Ctrl+Shift+Delete)
- Use: admin@sistema.com / admin123

### Erro de conexão

**Solução:** Verifique config.php

---

## Versão Simplificada

Este é o sistema **essencial**, sem recursos avançados como:
- ❌ Departamentos
- ❌ Níveis de acesso múltiplos
- ❌ Kanban
- ❌ Aprovação de despesas
- ❌ Auditoria completa
- ❌ Metas e orçamentos

Para a versão completa, use o arquivo `database_completo.sql`.

---

**Versão:** 1.0 Simplificada
**Tempo de instalação:** 5 minutos
