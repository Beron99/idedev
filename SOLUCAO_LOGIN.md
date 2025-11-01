# 🔐 Solução: Não Consigo Logar como Admin

## 3 Soluções Rápidas

---

## ✅ SOLUÇÃO 1: Usar Senha Simples Temporária (MAIS RÁPIDA)

### Passo 1: Execute este SQL no phpMyAdmin

1. Abra o phpMyAdmin
2. Selecione o banco `u411458227_studupss`
3. Vá na aba **SQL**
4. Cole e execute:

```sql
USE u411458227_studupss;

UPDATE usuarios
SET senha = '$2y$10$eUITICgfgN5.ZZ6wI1N.HOqYKQcuV0WfGZJslGbELRdSCKwzRZfBi'
WHERE email = 'admin@sistema.com';
```

### Passo 2: Fazer Login

```
Email: admin@sistema.com
Senha: admin123
```

### Passo 3: Alterar a Senha

Depois de entrar, vá em configurações e altere para uma senha forte!

---

## ✅ SOLUÇÃO 2: Usar Script PHP (MAIS SEGURA)

### Passo 1: Fazer Upload

Faça upload do arquivo **`resetar_senha_admin.php`** para seu servidor.

### Passo 2: Acessar no Navegador

```
http://seusite.com/resetar_senha_admin.php
```

### Passo 3: Definir Nova Senha

1. Digite a nova senha
2. Confirme a senha
3. Clique em "Resetar Senha"

### Passo 4: Fazer Login

Use o email `admin@sistema.com` e a senha que você acabou de criar.

### Passo 5: DELETE o Arquivo

⚠️ **IMPORTANTE:** Delete o arquivo `resetar_senha_admin.php` do servidor após usar!

---

## ✅ SOLUÇÃO 3: Recriar o Usuário Admin

### Execute este SQL no phpMyAdmin:

```sql
USE u411458227_studupss;

-- Deletar admin antigo
DELETE FROM usuarios WHERE email = 'admin@sistema.com';

-- Criar novo admin
INSERT INTO usuarios (nome, email, senha, role, ativo) VALUES
('Administrador', 'admin@sistema.com', '$2y$10$eUITICgfgN5.ZZ6wI1N.HOqYKQcuV0WfGZJslGbELRdSCKwzRZfBi', 'admin', TRUE);
```

### Login:
```
Email: admin@sistema.com
Senha: admin123
```

---

## 🔍 Verificar se o Usuário Existe

Execute no phpMyAdmin:

```sql
SELECT id, nome, email, role, ativo
FROM usuarios
WHERE email = 'admin@sistema.com';
```

**Se não retornar nada:** O usuário não existe. Execute o `database_completo.sql` primeiro.

---

## 🐛 Outros Problemas Possíveis

### Erro: "Token de segurança inválido"

**Solução:**
1. Limpe o cache do navegador (Ctrl+Shift+Delete)
2. Limpe os cookies
3. Tente novamente

### Erro: "Email ou senha incorretos"

**Possíveis causas:**
1. ❌ Senha está errada
2. ❌ Usuário não existe no banco
3. ❌ Banco de dados não foi criado corretamente

**Solução:**
- Use uma das 3 soluções acima
- Verifique se executou o `database_completo.sql`

### Login funciona mas não entra

**Verifique:**
1. Se as sessões PHP estão funcionando
2. Se o arquivo `config.php` tem as credenciais corretas do banco
3. Se o PHP tem permissão para escrever sessões

---

## 📋 Checklist de Instalação

Marque o que já fez:

- [ ] Executei o `database_completo.sql` no phpMyAdmin
- [ ] Banco de dados `u411458227_studupss` está selecionado
- [ ] Tabela `usuarios` foi criada
- [ ] Usuário admin existe (verificar com SELECT)
- [ ] Arquivo `config.php` tem as credenciais corretas
- [ ] Limpei cache e cookies do navegador
- [ ] Tentei as 3 soluções acima

---

## 🎯 Senha Padrão Simples

Depois de usar qualquer solução acima:

```
Email: admin@sistema.com
Senha: admin123
```

⚠️ **Altere esta senha imediatamente após fazer login!**

---

## 💡 Dica: Verificar Hash da Senha

Se quiser verificar se o hash da senha está correto, crie um arquivo `teste_senha.php`:

```php
<?php
$senha = 'admin123';
$hash = '$2y$10$eUITICgfgN5.ZZ6wI1N.HOqYKQcuV0WfGZJslGbELRdSCKwzRZfBi';

if (password_verify($senha, $hash)) {
    echo "✅ Senha correta!";
} else {
    echo "❌ Senha incorreta!";
}
?>
```

Acesse: `http://seusite.com/teste_senha.php`

---

## 📞 Ainda Não Funciona?

Se nenhuma solução funcionou:

1. **Verifique o config.php:**
   - As credenciais do banco estão corretas?
   - Consegue conectar ao banco?

2. **Teste a conexão:**
   ```php
   <?php
   require_once 'config.php';
   echo "Conectado ao banco com sucesso!";
   $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
   echo "<br>Total de usuários: " . $stmt->fetchColumn();
   ?>
   ```

3. **Verifique os erros do PHP:**
   - Habilite `display_errors` no php.ini
   - Veja o log de erros

---

## ✅ Solução Recomendada

**Use a SOLUÇÃO 1** (mais rápida):

1. Execute o UPDATE no phpMyAdmin
2. Entre com: admin@sistema.com / admin123
3. Altere a senha dentro do sistema

**Tempo total: 1 minuto** ⚡

---

**Arquivo criado em:** 2025
**Versão do Sistema:** 1.0
