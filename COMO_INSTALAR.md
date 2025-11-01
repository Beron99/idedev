# 🚀 Como Instalar o Banco de Dados

## UM ÚNICO ARQUIVO - UMA ÚNICA EXECUÇÃO

---

## 📝 PASSO A PASSO

### 1️⃣ Abrir o phpMyAdmin

Acesse seu phpMyAdmin em:
```
https://seusite.com/phpmyadmin
```

---

### 2️⃣ Selecionar o Banco de Dados

Na **lateral esquerda**, clique em:
```
u411458227_studupss
```

O banco ficará **destacado/selecionado**.

---

### 3️⃣ Ir para a aba SQL

No topo da página, clique na aba **SQL**.

---

### 4️⃣ Importar o arquivo

**OPÇÃO A - Importar Arquivo (Recomendado):**

1. Clique na aba **"Importar"** (ao lado da aba SQL)
2. Clique em **"Escolher arquivo"**
3. Selecione o arquivo **`database_completo.sql`**
4. Role até o final da página
5. Clique em **"Executar"**

**OPÇÃO B - Copiar e Colar:**

1. Abra o arquivo **`database_completo.sql`** no Bloco de Notas
2. Selecione **TODO** o conteúdo (Ctrl+A)
3. Copie (Ctrl+C)
4. No phpMyAdmin, aba **SQL**, cole na caixa de texto
5. Clique em **"Executar"**

---

### 5️⃣ Aguardar a Execução

O phpMyAdmin irá:
- ✅ Criar 9 tabelas
- ✅ Inserir 7 departamentos
- ✅ Criar usuário admin
- ✅ Inserir configurações padrão
- ✅ Criar view para dashboard
- ✅ Inserir metas de exemplo

**Tempo estimado:** 5-10 segundos

---

### 6️⃣ Verificar Sucesso

No final da execução, você verá uma mensagem:

```
BANCO DE DADOS CRIADO COM SUCESSO!
```

E uma lista das tabelas criadas:
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

## 🔑 FAZER LOGIN NO SISTEMA

Após a instalação bem-sucedida:

**URL:** `http://seusite.com/login.php`

**Credenciais:**
```
Email: admin@sistema.com
Senha: Admin@123
```

⚠️ **IMPORTANTE:** Altere a senha imediatamente após o primeiro login!

---

## 📁 CRIAR PASTA PARA ANEXOS

Via FTP ou Gerenciador de Arquivos do seu hosting:

1. Crie a pasta: **`uploads/anexos`**
2. Defina permissão: **755**

---

## 🎯 PÁGINAS DISPONÍVEIS

Após fazer login como admin:

| Página | URL | Descrição |
|--------|-----|-----------|
| **Dashboard** | `dashboard.php` | Dashboard principal com gráficos |
| **Contas a Pagar** | `contas.php` | Gerenciar contas a pagar |
| **Categorias** | `categorias.php` | Gerenciar categorias |
| **Kanban** | `kanban.php` | Visualização Kanban das contas |
| **Admin Dashboard** | `admin.php?acao=dashboard` | Dashboard administrativo |
| **Usuários** | `admin.php?acao=usuarios` | Gerenciar usuários |
| **Departamentos** | `admin.php?acao=departamentos` | Gerenciar departamentos |
| **Auditoria** | `admin.php?acao=auditoria` | Log de auditoria |

---

## ✅ O QUE FOI INSTALADO

### Tabelas Criadas (9):
1. **usuarios** - Usuários do sistema
2. **departamentos** - Departamentos da empresa
3. **categorias** - Categorias de despesas
4. **contas_pagar** - Contas a pagar
5. **auditoria** - Log de ações do sistema
6. **metas_orcamentos** - Metas e orçamentos por departamento
7. **notificacoes** - Sistema de notificações
8. **relatorios_salvos** - Relatórios gerados
9. **configuracoes** - Configurações do sistema

### Dados Inseridos:
- ✅ 7 Departamentos padrão (Financeiro, RH, TI, Vendas, Marketing, Operações, Administrativo)
- ✅ 1 Usuário Admin
- ✅ 8 Configurações do sistema
- ✅ 7 Metas de orçamento (uma para cada departamento)

### Recursos Instalados:
- ✅ Sistema de permissões (Admin, Gestor, Usuário)
- ✅ Sistema de aprovação de despesas
- ✅ Sistema de departamentos
- ✅ Log de auditoria
- ✅ Metas e orçamentos
- ✅ Sistema de notificações
- ✅ Relatórios salvos
- ✅ Visualização Kanban
- ✅ Dashboard com gráficos
- ✅ Segurança avançada (CSRF, rate limiting)

---

## ❌ POSSÍVEIS ERROS

### Erro: "Nenhum banco de dados foi selecionado"
**Causa:** Você não selecionou o banco de dados.
**Solução:** Clique em `u411458227_studupss` na lateral esquerda do phpMyAdmin.

### Erro: "Table already exists"
**Causa:** As tabelas já foram criadas anteriormente.
**Solução:** O script usa `DROP TABLE IF EXISTS`, então isso não deve acontecer. Se acontecer, o banco já está instalado.

### Erro: "Access denied"
**Causa:** Seu usuário não tem permissão para criar tabelas.
**Solução:** Entre em contato com seu provedor de hospedagem.

### Erro de timeout
**Causa:** Script muito grande para executar de uma vez.
**Solução:** Use a opção **Importar** em vez de copiar/colar.

---

## 🔄 REINSTALAR (Limpar Tudo)

Se precisar reinstalar do zero:

1. No phpMyAdmin, selecione seu banco
2. Clique na aba **"Estrutura"**
3. Marque **"Selecionar tudo"** (checkbox no topo)
4. No dropdown "Com selecionados", escolha **"Esvaziar"** ou **"Eliminar"**
5. Confirme
6. Execute o arquivo `database_completo.sql` novamente

---

## 📞 SUPORTE

Se encontrar qualquer erro durante a instalação:

1. Anote a mensagem de erro completa
2. Anote em qual linha do SQL ocorreu o erro
3. Verifique se seu banco de dados suporta MySQL 5.7 ou superior

---

## 🎉 PRONTO!

Seu sistema está **100% funcional** e pronto para uso!

Acesse `login.php` e comece a usar o sistema! 🚀

---

## 📋 CHECKLIST FINAL

Marque conforme for completando:

- [ ] Arquivo `database_completo.sql` executado com sucesso
- [ ] Mensagem "BANCO DE DADOS CRIADO COM SUCESSO!" apareceu
- [ ] 9 tabelas criadas (verificar em "Estrutura")
- [ ] Pasta `uploads/anexos` criada com permissão 755
- [ ] Login realizado com admin@sistema.com
- [ ] Senha alterada no primeiro acesso
- [ ] Dashboard carregando corretamente
- [ ] Sistema funcionando!

---

**Desenvolvido com ❤️**
**Versão do Sistema: 1.0**
