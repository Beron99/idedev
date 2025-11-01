# Instalação do Módulo de Clientes/Pagadores

## Como Adicionar Gestão de Clientes ao Sistema

### 1. Execute o SQL

No phpMyAdmin:

1. Selecione o banco: `u411458227_studupss`
2. Vá na aba **SQL**
3. Copie TODO o conteúdo do arquivo: **`adicionar_clientes.sql`**
4. Cole e clique em **Executar**

### 2. Arquivos Criados

✅ **adicionar_clientes.sql** - SQL de instalação
✅ **clientes.php** - Página de gerenciamento de clientes
✅ **buscar_historico_cliente.php** - API para buscar histórico via AJAX

### 3. Arquivos Atualizados

✅ **contas_receber.php** - Adicionado select de clientes cadastrados
✅ **dashboard.php** - Menu atualizado com link "Clientes"
✅ **contas.php** - Menu atualizado com link "Clientes"
✅ **categorias.php** - Menu atualizado com link "Clientes"

---

## O que foi adicionado

### Nova Tabela: clientes

```sql
Campos Principais:
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- usuario_id (INT, FK → usuarios.id)

Dados da Empresa:
- razao_social (VARCHAR 255, NOT NULL) - Nome ou Razão Social
- nome_fantasia (VARCHAR 255) - Nome Fantasia
- cnpj (VARCHAR 18) - CNPJ para Pessoa Jurídica
- cpf (VARCHAR 14) - CPF para Pessoa Física
- tipo_pessoa (ENUM: juridica, fisica)

Contato:
- email (VARCHAR 255)
- telefone (VARCHAR 20)
- celular (VARCHAR 20)

Endereço:
- cep (VARCHAR 10)
- endereco (VARCHAR 255)
- numero (VARCHAR 20)
- complemento (VARCHAR 100)
- bairro (VARCHAR 100)
- cidade (VARCHAR 100)
- estado (VARCHAR 2)

Outros:
- observacoes (TEXT)
- ativo (BOOLEAN) - Status ativo/inativo
- data_criacao (TIMESTAMP)
- data_atualizacao (TIMESTAMP)
```

### Alteração na Tabela: contas_receber

**Nova coluna adicionada:**
- `cliente_id` (INT, FK → clientes.id)

**Campo mantido para compatibilidade:**
- `cliente` (VARCHAR) - Para registros antigos ou clientes não cadastrados

### Nova View: v_clientes_stats

Estatísticas completas de cada cliente:
- Total de contas a receber
- Contas pendentes, recebidas, vencidas
- Valores totais por status
- Data da última movimentação

---

## Recursos do Módulo

### ✅ Página de Clientes (clientes.php)

**CRUD Completo:**
- Adicionar cliente
- Editar cliente
- Ativar/Inativar cliente
- Excluir cliente (se não houver contas vinculadas)

**Dados de Pessoa Jurídica:**
- Razão Social *
- Nome Fantasia
- CNPJ

**Dados de Pessoa Física:**
- Nome Completo *
- Apelido
- CPF

**Contatos:**
- E-mail
- Telefone fixo
- Celular

**Endereço Completo:**
- CEP
- Endereço
- Número
- Complemento
- Bairro
- Cidade
- Estado (select com todos os estados)

**Observações:**
- Campo de texto livre para informações adicionais

**Estatísticas no Card:**
- Total de contas a receber
- Quantidade de contas pendentes
- Valor total a receber (pendente)

**Filtros:**
- Por status (Todos, Ativos, Inativos)
- Busca por nome, CNPJ, CPF, email

**Botão "Histórico":**
- Abre modal com histórico completo do cliente
- Mostra todas as contas a receber
- Estatísticas detalhadas (pendente, recebido, vencido)

### ✅ Integração com Contas a Receber

**Select de Cliente Cadastrado:**
- Lista todos os clientes ativos
- Mostra Razão Social e Nome Fantasia
- Ao selecionar, preenche automaticamente o campo "Nome do Cliente"

**Campo Nome do Cliente:**
- Preenchido automaticamente ao selecionar um cliente cadastrado
- Pode ser digitado manualmente para clientes não cadastrados
- Mantém compatibilidade com registros antigos

**Exibição na Listagem:**
- Se houver cliente cadastrado: mostra Razão Social + Nome Fantasia
- Se não houver cliente cadastrado: mostra o campo texto "cliente"
- Fallback para registros antigos

---

## Como Usar

### Cadastrar um Novo Cliente

1. Acesse **Clientes** no menu
2. Clique em **+ Novo Cliente**
3. Selecione o tipo de pessoa (Jurídica ou Física)
4. Preencha os dados obrigatórios:
   - **Pessoa Jurídica:** Razão Social
   - **Pessoa Física:** Nome Completo
5. Preencha dados opcionais (CNPJ/CPF, contatos, endereço, observações)
6. Clique em **Salvar**

### Criar Conta a Receber com Cliente Cadastrado

1. Acesse **Contas a Receber**
2. Clique em **+ Nova Conta a Receber**
3. Preencha Descrição, Valor e Vencimento
4. No campo **"Cliente Cadastrado"**: selecione o cliente da lista
   - O campo "Nome do Cliente" será preenchido automaticamente
5. Preencha categoria e observações (opcional)
6. Clique em **Salvar**

### Criar Conta a Receber SEM Cliente Cadastrado

1. Acesse **Contas a Receber**
2. Clique em **+ Nova Conta a Receber**
3. Preencha Descrição, Valor e Vencimento
4. **Deixe o select "Cliente Cadastrado" vazio**
5. Digite manualmente no campo **"Nome do Cliente"**
6. Clique em **Salvar**

### Ver Histórico de um Cliente

1. Acesse **Clientes**
2. Encontre o cliente desejado
3. Clique no botão **"Histórico"** (azul)
4. Verá:
   - Dados do cliente
   - Estatísticas (pendente, recebido, vencido)
   - Lista completa de contas a receber ordenadas por vencimento

### Inativar um Cliente

1. Acesse **Clientes**
2. Encontre o cliente desejado
3. Clique em **"Inativar"** (amarelo)
4. Cliente ficará inativo mas mantém o histórico
5. Não aparecerá no select de "Contas a Receber"

### Excluir um Cliente

1. Acesse **Clientes**
2. Encontre o cliente desejado
3. Clique em **"Excluir"** (vermelho)
4. **Atenção:** Só pode excluir se NÃO houver contas vinculadas

---

## Estrutura Final do Sistema

### Arquivos PHP (13 arquivos)
```
✅ login.php
✅ cadastro.php
✅ logout.php
✅ dashboard.php
✅ contas.php              (Contas a Pagar)
✅ contas_receber.php      (Contas a Receber) - ATUALIZADO
✅ clientes.php            (Clientes) - NOVO
✅ buscar_historico_cliente.php - NOVO
✅ categorias.php
✅ config.php
✅ security.php
```

### Tabelas no Banco (5 tabelas)
```
✅ usuarios
✅ categorias
✅ contas_pagar
✅ contas_receber          - ATUALIZADA (nova coluna cliente_id)
✅ clientes                - NOVA
```

### Views (1 view)
```
✅ v_clientes_stats        - NOVA
```

---

## Verificar Instalação

Execute no phpMyAdmin:

```sql
-- Verificar se a tabela foi criada
SELECT COUNT(*) as total_clientes FROM clientes;

-- Ver estrutura da tabela
DESCRIBE clientes;

-- Ver estrutura atualizada de contas_receber
DESCRIBE contas_receber;

-- Ver estatísticas de um cliente (substitua 1 pelo ID do cliente)
SELECT * FROM v_clientes_stats WHERE id = 1;
```

---

## Recursos Técnicos

### Máscaras de Input

✅ **CNPJ:** 00.000.000/0000-00
✅ **CPF:** 000.000.000-00
✅ **CEP:** 00000-000
✅ **Telefone:** (00) 0000-0000
✅ **Celular:** (00) 00000-0000

### Validações

✅ **Exclusão de Cliente:** Verifica se há contas vinculadas
✅ **Tipo de Pessoa:** Alterna campos CNPJ/CPF automaticamente
✅ **Select Dinâmico:** Preenche campo texto ao selecionar cliente
✅ **CSRF Protection:** Token de segurança em todos os formulários
✅ **Logs de Auditoria:** Registra todas as operações

### Performance

✅ **Queries otimizadas** com LEFT JOIN
✅ **Índices** em campos chave (usuario_id, cliente_id, cnpj, cpf)
✅ **AJAX** para carregar histórico sem recarregar página
✅ **View materializada** para estatísticas rápidas

---

## Compatibilidade

### ✅ Registros Antigos

O sistema mantém **100% de compatibilidade** com contas a receber criadas antes da instalação do módulo de clientes:

- Campo `cliente` (VARCHAR) continua funcionando
- Registros antigos aparecem normalmente na listagem
- Não é necessário migrar dados antigos
- Novos registros podem usar tanto o select quanto o campo texto

### ✅ Instalação Gradual

Você pode:
- Instalar o módulo sem migrar dados antigos
- Continuar usando o campo texto para alguns clientes
- Cadastrar apenas os clientes principais
- Migrar gradualmente conforme necessidade

---

## Menu de Navegação Atualizado

Todos os arquivos foram atualizados com o novo menu:

```
Dashboard | Contas a Pagar | Contas a Receber | Clientes | Categorias
```

---

## Problemas Comuns

### Erro ao executar SQL

**Problema:** Erro ao criar a tabela clientes

**Solução:** Verifique se executou primeiro o [instalar.sql](instalar.sql) ou [adicionar_contas_receber.sql](adicionar_contas_receber.sql)

### Erro: coluna cliente_id não existe

**Problema:** Contas a receber não carrega

**Solução:** Execute novamente o SQL `adicionar_clientes.sql` - a alteração na tabela pode não ter sido aplicada

### Select de clientes vazio

**Problema:** Select "Cliente Cadastrado" não mostra opções

**Solução:** Cadastre pelo menos um cliente ativo em **Clientes**

### Erro ao carregar histórico

**Problema:** Botão "Histórico" não funciona

**Solução:** Verifique se o arquivo `buscar_historico_cliente.php` foi criado no mesmo diretório

---

## Próximas Melhorias (Opcional)

Sugestões para expandir o módulo:

- ❌ Importação de clientes via CSV/Excel
- ❌ Relatório de inadimplência por cliente
- ❌ Envio de lembretes por email para clientes
- ❌ Múltiplos contatos por cliente
- ❌ Upload de documentos (contratos, notas fiscais)
- ❌ Histórico de comunicações com o cliente

---

**Instalação concluída!** 🎉

Agora você tem um sistema completo de gestão financeira com:
- ✅ Contas a Pagar
- ✅ Contas a Receber
- ✅ Clientes/Pagadores com histórico
- ✅ Categorias
- ✅ Dashboard completo
- ✅ Filtros e Relatórios

---

**Versão:** 3.0 - Com Gestão de Clientes
**Data:** 2025-11-01
**Autor:** Claude + Hesron
