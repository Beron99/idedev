# 🚀 COMECE AQUI - Sistema de Contas a Pagar

## Versão Simplificada - Instalação em 5 Minutos

---

## 📋 PASSO A PASSO

### 1. Limpar o Banco de Dados

No **phpMyAdmin**:
1. Selecione o banco: `u411458227_studupss`
2. Aba **Estrutura**
3. Marque **todas as tabelas**
4. Clique em **Apagar** (Drop)
5. Confirme

### 2. Executar o SQL

1. Aba **SQL**
2. Abra o arquivo: **`instalar.sql`**
3. Copie **TODO** o conteúdo
4. Cole no phpMyAdmin
5. Clique em **Executar**

### 3. Configurar Conexão

Edite o arquivo **`config.php`**:
```php
$username = 'seu_usuario';  // ← Altere aqui
$password = 'sua_senha';     // ← Altere aqui
```

### 4. Acessar o Sistema

```
URL: http://seusite.com/login.php
Email: admin@sistema.com
Senha: admin123
```

### 5. Alterar a Senha

**IMPORTANTE:** Altere a senha do admin após o primeiro login!

---

## ✅ Pronto!

O sistema está instalado e funcionando!

---

## 📁 Estrutura Final

Após a instalação, você terá **13 arquivos essenciais**:

```
seu-projeto/
├── login.php              # Login
├── cadastro.php           # Cadastro
├── logout.php             # Logout
├── dashboard.php          # Dashboard
├── contas.php             # Contas a Pagar
├── categorias.php         # Categorias
├── config.php             # Configuração
├── security.php           # Segurança
├── style.css              # Estilos
├── instalar.sql           # SQL de instalação
├── LEIA_ME.md            # Documentação
├── ARQUIVOS_PARA_DELETAR.md  # Guia de limpeza
└── COMECE_AQUI.md        # Este arquivo
```

---

## 🗑️ Limpeza (Opcional)

Quer deixar o projeto mais organizado?

Consulte o arquivo: **`ARQUIVOS_PARA_DELETAR.md`**

Ele lista ~22 arquivos que podem ser removidos (versão completa do sistema).

---

## 🎯 Recursos Disponíveis

✅ **Autenticação**
- Login seguro
- Cadastro de usuários
- Proteção CSRF
- Rate limiting

✅ **Dashboard**
- Estatísticas em tempo real
- Gráficos com Chart.js
- Visão geral das contas

✅ **Contas a Pagar**
- Adicionar, editar, excluir
- Marcar como pago
- Filtros por status/categoria/mês

✅ **Categorias**
- Criar categorias personalizadas
- Escolher cores
- Organizar despesas

---

## 🔐 Segurança

O sistema possui:
- ✅ Proteção CSRF
- ✅ Sessões seguras
- ✅ Rate limiting (5 tentativas)
- ✅ Senhas criptografadas (bcrypt)
- ✅ SQL Injection protection
- ✅ XSS protection

---

## 📊 Banco de Dados

### 3 Tabelas:

1. **usuarios** - Usuários do sistema
2. **categorias** - Categorias de despesas
3. **contas_pagar** - Contas a pagar

### 1 View:

- **v_dashboard_stats** - Estatísticas do dashboard

---

## 🆘 Problemas?

### Erro ao executar SQL
**Solução:** Apague todas as tabelas manualmente antes

### Não consigo logar
**Solução:**
- Limpe o cache (Ctrl+Shift+Delete)
- Use: admin@sistema.com / admin123

### Erro de conexão
**Solução:** Verifique o arquivo `config.php`

---

## 📚 Documentação

- **LEIA_ME.md** - Guia completo do sistema
- **ARQUIVOS_PARA_DELETAR.md** - Lista de arquivos para limpar
- **COMECE_AQUI.md** - Este arquivo

---

## ⏱️ Tempo de Instalação

**Total:** 5 minutos

- Limpeza do banco: 1 min
- Executar SQL: 1 min
- Configurar config.php: 1 min
- Testar sistema: 2 min

---

## 🎉 Próximos Passos

1. ✅ Instale o sistema
2. ✅ Faça login
3. ✅ Altere a senha do admin
4. ✅ Crie suas categorias personalizadas
5. ✅ Comece a cadastrar suas contas a pagar
6. ✅ Use os filtros e gráficos
7. ✅ Marque as contas como pagas

---

## 💡 Dica

Salve este arquivo (**COMECE_AQUI.md**) para consultar sempre que precisar reinstalar o sistema!

---

**Sistema criado em:** 2025
**Versão:** 1.0 Simplificada
**Autor:** Claude + Hesron
