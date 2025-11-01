# Instalação do Módulo de Contas a Receber

## Como Adicionar Contas a Receber ao Sistema

### 1. Execute o SQL

No phpMyAdmin:

1. Selecione o banco: `u411458227_studupss`
2. Vá na aba **SQL**
3. Copie TODO o conteúdo do arquivo: **`adicionar_contas_receber.sql`**
4. Cole e clique em **Executar**

### 2. Arquivos Criados

✅ **adicionar_contas_receber.sql** - SQL de instalação
✅ **contas_receber.php** - Página de contas a receber

### 3. O que foi adicionado

#### Nova Tabela: contas_receber

```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- usuario_id (INT, FK → usuarios.id)
- categoria_id (INT, FK → categorias.id, NULL)
- descricao (VARCHAR 255)
- valor (DECIMAL 10,2)
- data_vencimento (DATE)
- data_recebimento (DATE, NULL)
- status (ENUM: pendente, recebido, vencido)
- cliente (VARCHAR 255, NULL) ← NOVO CAMPO
- observacoes (TEXT, NULL)
- data_criacao (TIMESTAMP)
- data_atualizacao (TIMESTAMP)
```

#### Campo Exclusivo

**cliente** - Nome do cliente/pagador que deve fazer o pagamento

#### View Atualizada

A view `v_dashboard_stats` foi atualizada para incluir estatísticas de contas a receber.

### 4. Menus Atualizados

Todos os menus de navegação foram atualizados com o novo item:

```
Dashboard | Contas a Pagar | Contas a Receber | Categorias
```

Arquivos atualizados:
- ✅ dashboard.php
- ✅ contas.php
- ✅ categorias.php

### 5. Recursos do Módulo

✅ **CRUD Completo**
- Adicionar conta a receber
- Editar conta a receber
- Excluir conta a receber
- Marcar como recebido

✅ **Filtros**
- Por status (pendente, recebido, vencido)
- Por categoria
- Por mês

✅ **Informações**
- Descrição do recebimento
- Valor
- Data de vencimento
- Nome do cliente/pagador
- Categoria
- Observações

✅ **Segurança**
- Proteção CSRF
- Validação de valores
- Log de auditoria
- Isolamento por usuário

### 6. Diferenças: Contas a Pagar vs Contas a Receber

| Recurso | Contas a Pagar | Contas a Receber |
|---------|----------------|------------------|
| Descrição | Gastos/Despesas | Vendas/Receitas |
| Status Pago | "pago" | "recebido" |
| Campo Especial | - | "cliente" |
| Cor no Dashboard | Vermelho/Laranja | Verde/Azul |
| Objetivo | Controlar saídas | Controlar entradas |

### 7. Como Usar

#### Adicionar Conta a Receber

1. Acesse **Contas a Receber**
2. Clique em **+ Nova Conta a Receber**
3. Preencha:
   - Descrição (Ex: "Venda de produto X")
   - Valor (Ex: "1.500,00")
   - Vencimento
   - Cliente (Ex: "João Silva")
   - Categoria (opcional)
   - Observações (opcional)
4. Clique em **Salvar**

#### Marcar como Recebido

1. Na lista, clique no botão **✓** (check verde)
2. Informe a data do recebimento (ou deixe em branco para hoje)
3. Confirme

#### Filtrar Contas

Use os filtros no topo da página:
- **Status:** Pendente, Recebido, Vencido
- **Categoria:** Escolha uma categoria
- **Mês:** Selecione um mês específico

### 8. Estrutura Final do Sistema

#### Arquivos PHP (11 arquivos)
```
✅ login.php
✅ cadastro.php
✅ logout.php
✅ dashboard.php
✅ contas.php              (Contas a Pagar)
✅ contas_receber.php      (Contas a Receber) ← NOVO
✅ categorias.php
✅ config.php
✅ security.php
```

#### Tabelas no Banco (4 tabelas)
```
✅ usuarios
✅ categorias
✅ contas_pagar
✅ contas_receber          ← NOVA
```

### 9. Verificar Instalação

Execute no phpMyAdmin:

```sql
-- Verificar se a tabela foi criada
SELECT COUNT(*) as total FROM contas_receber;

-- Ver estrutura da tabela
DESCRIBE contas_receber;

-- Ver estatísticas na view
SELECT * FROM v_dashboard_stats;
```

### 10. Teste Completo

1. ✅ Faça login
2. ✅ Vá em "Contas a Receber"
3. ✅ Adicione uma conta a receber
4. ✅ Edite a conta
5. ✅ Marque como recebida
6. ✅ Teste os filtros
7. ✅ Exclua a conta de teste

---

## Problemas Comuns

### Erro ao executar SQL

**Problema:** Erro ao criar a tabela contas_receber

**Solução:** Verifique se executou primeiro o [instalar.sql](instalar.sql)

### Menu não aparece

**Problema:** Link "Contas a Receber" não aparece no menu

**Solução:** Limpe o cache do navegador (Ctrl+Shift+Delete)

### Erro ao adicionar conta

**Problema:** Erro ao salvar conta a receber

**Solução:** Verifique se a tabela foi criada corretamente

---

## Próximas Melhorias (Opcional)

Sugestões para expandir o módulo:

- ❌ Dashboard com estatísticas de recebimentos
- ❌ Gráficos de receitas vs despesas
- ❌ Fluxo de caixa mensal
- ❌ Relatórios de inadimplência
- ❌ Envio de lembretes por email
- ❌ Integração com boletos

---

**Instalação concluída!** 🎉

Agora você tem um sistema completo de gestão financeira com:
- ✅ Contas a Pagar
- ✅ Contas a Receber
- ✅ Categorias
- ✅ Dashboard
- ✅ Filtros e Relatórios

**Versão:** 1.1 - Com Contas a Receber
**Data:** 2025-11-01
