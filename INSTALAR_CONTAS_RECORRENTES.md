# Instalação do Módulo de Contas Recorrentes

## Como Adicionar Contas Fixas Mensais ao Sistema

### 1. Execute o SQL

No phpMyAdmin:

1. Selecione o banco: `u411458227_studupss`
2. Vá na aba **SQL**
3. Copie TODO o conteúdo do arquivo: **`adicionar_contas_recorrentes.sql`**
4. Cole e clique em **Executar**

### 2. Arquivos Criados

✅ **adicionar_contas_recorrentes.sql** - SQL de instalação
✅ **gerar_contas_recorrentes.php** - Script CLI para gerar contas automaticamente
✅ **gerenciar_recorrentes.php** - Página web para gerenciar contas recorrentes

### 3. Arquivos Atualizados

✅ **contas.php** - Adicionado campos de recorrência no formulário

---

## O que foi adicionado

### Alteração na Tabela: contas_pagar

**Novos campos adicionados:**

```sql
- recorrente (BOOLEAN) - Indica se é conta recorrente
- tipo_recorrencia (ENUM) - mensal, bimestral, trimestral, semestral, anual
- dia_vencimento_recorrente (INT) - Dia do mês para vencimento (1-31)
- data_fim_recorrencia (DATE) - Data final da recorrência (opcional)
- gerada_automaticamente (BOOLEAN) - Se foi gerada pelo sistema
- conta_recorrente_origem_id (INT) - Referência à conta original
```

### Nova Tabela: log_contas_geradas

```sql
Rastreia todas as contas geradas automaticamente:
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- conta_recorrente_id (INT) - FK para conta original
- conta_gerada_id (INT) - FK para conta gerada
- data_geracao (TIMESTAMP) - Quando foi gerada
- mes_referencia (VARCHAR 7) - Formato YYYY-MM
```

### Nova View: v_contas_recorrentes_ativas

Lista contas recorrentes que ainda estão ativas com estatísticas:
- Dados completos da conta
- Total de contas geradas
- Último mês gerado

### Nova Stored Procedure: gerar_contas_recorrentes_mes

Procedimento armazenado que gera automaticamente contas para um mês específico:
- Evita duplicação verificando o log
- Adiciona mês/ano na descrição
- Cria contas com status "pendente"

---

## Recursos do Módulo

### ✅ Formulário em Contas a Pagar

**Nova Seção: "Conta Recorrente"**

- ☑️ Checkbox para marcar como recorrente
- 📋 Select de tipo de recorrência:
  - Mensal
  - Bimestral (a cada 2 meses)
  - Trimestral (a cada 3 meses)
  - Semestral (a cada 6 meses)
  - Anual (todo ano)
- 📅 Dia do vencimento (1-31)
- 📆 Data de fim (opcional)
- ℹ️ Explicação de como funciona

**Recursos Automáticos:**
- Preenche automaticamente o dia do vencimento com base na data selecionada
- Mostra/oculta campos conforme checkbox marcado
- Badge "🔄 RECORRENTE" na listagem de contas

### ✅ Página de Gerenciamento

**gerenciar_recorrentes.php** - Painel completo para gerenciar:

**Geração Manual de Contas:**
- Select de mês/ano
- Botão para gerar todas as contas do mês selecionado
- Mensagens de sucesso/erro

**Listagem de Contas Recorrentes:**
- Cards com informações de cada conta
- Tipo de recorrência
- Dia do vencimento
- Valor fixo
- Total de contas já geradas
- Data de término (se houver)
- Categoria e observações

**Informações e Dicas:**
- Como funciona o sistema
- Orientações de uso

### ✅ Script de Linha de Comando

**gerar_contas_recorrentes.php** - Script CLI:

**Uso:**
```bash
# Gerar contas do próximo mês
php gerar_contas_recorrentes.php

# Gerar contas de um mês específico
php gerar_contas_recorrentes.php 2025-12
```

**Funcionalidades:**
- Processa todas as contas recorrentes ativas
- Exibe progresso no terminal
- Mostra resumo ao final
- Registra no log de auditoria
- Evita duplicação de contas

**Configurar CRON (Linux):**
```bash
# Executar todo dia 1º do mês às 00:00
0 0 1 * * /usr/bin/php /caminho/para/gerar_contas_recorrentes.php

# Executar todo dia 25 do mês às 08:00 (para gerar do próximo mês)
0 8 25 * * /usr/bin/php /caminho/para/gerar_contas_recorrentes.php
```

---

## Como Usar

### Cadastrar uma Conta Recorrente

1. Acesse **Contas a Pagar**
2. Clique em **+ Nova Conta**
3. Preencha os dados normais:
   - Descrição (ex: "Aluguel")
   - Valor (ex: "1.500,00")
   - Vencimento (data da primeira conta)
   - Categoria (opcional)
4. **Marque** o checkbox "Conta Recorrente"
5. Preencha os dados de recorrência:
   - Tipo: Mensal
   - Dia do vencimento: 10
   - Data de fim: deixe vazio (conta indefinida)
6. Clique em **Salvar**

### Gerar Contas do Mês

**Opção 1: Manualmente pelo Painel**

1. Acesse **gerenciar_recorrentes.php** (ou adicione ao menu)
2. Selecione o mês desejado
3. Clique em **"Gerar Contas"**
4. As contas serão criadas automaticamente

**Opção 2: Via Linha de Comando**

```bash
php gerar_contas_recorrentes.php 2025-12
```

**Opção 3: Configurar CRON (Automático)**

```bash
crontab -e

# Adicionar linha:
0 0 1 * * /usr/bin/php /caminho/completo/gerar_contas_recorrentes.php
```

### Editar uma Conta Recorrente

1. Acesse **Contas a Pagar**
2. Encontre a conta com badge "🔄 RECORRENTE"
3. Clique em **Editar**
4. Modifique os dados necessários
5. As novas contas geradas usarão os dados atualizados

### Desativar uma Conta Recorrente

**Opção 1: Definir Data de Fim**
- Edite a conta recorrente
- Preencha "Data de Fim" com a data desejada
- Salvar

**Opção 2: Desmarcar Recorrente**
- Edite a conta
- Desmarque o checkbox "Conta Recorrente"
- Salvar

### Excluir Conta Recorrente

⚠️ **Atenção:** Excluir a conta recorrente NÃO exclui as contas já geradas.

1. Acesse **Contas a Pagar**
2. Encontre a conta recorrente
3. Clique em **Excluir**
4. As contas já geradas permanecerão

---

## Exemplos de Uso

### Exemplo 1: Aluguel Mensal

```
Descrição: Aluguel
Valor: R$ 1.500,00
Vencimento: 10/11/2025
Categoria: Moradia
✅ Conta Recorrente
Tipo: Mensal
Dia do Vencimento: 10
Data de Fim: (vazio)
```

**Resultado:**
- Todo mês será gerada uma conta "Aluguel (MM/YYYY)" no dia 10
- Valor sempre R$ 1.500,00
- Sem data de término

### Exemplo 2: Seguro do Carro (Anual)

```
Descrição: Seguro do Carro
Valor: R$ 2.400,00
Vencimento: 15/03/2026
Categoria: Transporte
✅ Conta Recorrente
Tipo: Anual
Dia do Vencimento: 15
Data de Fim: 15/03/2028
```

**Resultado:**
- Todo ano será gerada uma conta no dia 15/03
- Até 2028 (último ano)

### Exemplo 3: Internet + Telefone (Bimestral)

```
Descrição: Internet + Telefone
Valor: R$ 200,00
Vencimento: 05/11/2025
Categoria: Outros
✅ Conta Recorrente
Tipo: Bimestral
Dia do Vencimento: 5
Data de Fim: (vazio)
```

**Resultado:**
- A cada 2 meses será gerada uma conta no dia 5

---

## Verificar Instalação

Execute no phpMyAdmin:

```sql
-- Ver estrutura atualizada
DESCRIBE contas_pagar;

-- Ver log de contas geradas
SELECT COUNT(*) as total_logs FROM log_contas_geradas;

-- Ver contas recorrentes ativas
SELECT * FROM v_contas_recorrentes_ativas;

-- Ver últimas contas geradas
SELECT
    cp_orig.descricao as conta_original,
    cp_gerada.descricao as conta_gerada,
    cp_gerada.data_vencimento,
    cp_gerada.valor,
    lcg.mes_referencia
FROM log_contas_geradas lcg
INNER JOIN contas_pagar cp_orig ON lcg.conta_recorrente_id = cp_orig.id
INNER JOIN contas_pagar cp_gerada ON lcg.conta_gerada_id = cp_gerada.id
ORDER BY lcg.data_geracao DESC
LIMIT 10;
```

---

## Estrutura Final do Sistema

### Arquivos PHP (15 arquivos)
```
✅ login.php
✅ cadastro.php
✅ logout.php
✅ dashboard.php
✅ contas.php                       - ATUALIZADO (com recorrência)
✅ contas_receber.php
✅ clientes.php
✅ buscar_historico_cliente.php
✅ categorias.php
✅ config.php
✅ security.php
✅ gerar_contas_recorrentes.php    - NOVO (CLI)
✅ gerenciar_recorrentes.php       - NOVO (Web)
```

### Tabelas no Banco (6 tabelas)
```
✅ usuarios
✅ categorias
✅ contas_pagar                     - ATUALIZADA (novos campos)
✅ contas_receber
✅ clientes
✅ log_contas_geradas               - NOVA
```

### Views (2 views)
```
✅ v_clientes_stats
✅ v_contas_recorrentes_ativas      - NOVA
```

### Stored Procedures (1)
```
✅ gerar_contas_recorrentes_mes     - NOVA
```

---

## Recursos Técnicos

### Validações

✅ **Dia do Mês:** Se o dia não existe no mês (ex: dia 31 em fevereiro), ajusta para o último dia
✅ **Duplicação:** Verifica log para não gerar duas vezes no mesmo mês
✅ **Data de Fim:** Para automaticamente quando atinge a data
✅ **CSRF Protection:** Token de segurança em todos os formulários

### Performance

✅ **Índices** em campos chave (recorrente, gerada_automaticamente)
✅ **LOG separado** para rastreamento eficiente
✅ **View otimizada** para consultas rápidas
✅ **Stored Procedure** para processamento em batch

### Auditoria

✅ **Log de contas geradas** rastreia tudo
✅ **Campo origem** mantém referência à conta original
✅ **Flag de geração automática** diferencia contas manuais de automáticas
✅ **Registro em security_logs** de todas as operações

---

## Problemas Comuns

### Erro ao executar SQL

**Problema:** Erro ao criar stored procedure

**Solução:** Execute o SQL em partes:
1. Primeiro: CREATE TABLE e ALTER TABLE
2. Depois: CREATE VIEW
3. Por último: CREATE PROCEDURE

### Contas não estão sendo geradas

**Problema:** Script não gera nenhuma conta

**Possíveis causas:**
- Tabela log_contas_geradas não foi criada
- Contas já foram geradas para este mês
- Data de fim da recorrência já passou
- Checkbox "recorrente" não foi marcado

**Solução:**
```sql
-- Verificar se há contas recorrentes
SELECT * FROM contas_pagar WHERE recorrente = TRUE;

-- Verificar log
SELECT * FROM log_contas_geradas ORDER BY data_geracao DESC;
```

### Dia do vencimento errado

**Problema:** Conta gerada com dia diferente do configurado

**Solução:** O sistema ajusta automaticamente dias inválidos (ex: 31/02 vira 28/02)

### CRON não funciona

**Problema:** CRON job não executa o script

**Solução:**
```bash
# Testar manualmente primeiro
php /caminho/completo/gerar_contas_recorrentes.php

# Verificar logs do CRON
grep CRON /var/log/syslog

# Dar permissão de execução
chmod +x gerar_contas_recorrentes.php
```

---

## Menu de Navegação (Opcional)

Para adicionar link no menu, edite os arquivos PHP e adicione:

```html
<a href="gerenciar_recorrentes.php" class="nav-item">Contas Recorrentes</a>
```

---

## Próximas Melhorias (Opcional)

Sugestões para expandir o módulo:

- ❌ Notificação por email quando contas são geradas
- ❌ Relatório de contas recorrentes vs. pontuais
- ❌ Gerar várias parcelas de uma vez
- ❌ Suporte a recorrência customizada (a cada X dias)
- ❌ Histórico de alterações em contas recorrentes
- ❌ Pausar temporariamente uma recorrência

---

**Instalação concluída!** 🎉

Agora você tem um sistema completo com:
- ✅ Contas a Pagar com **recorrência**
- ✅ Contas a Receber
- ✅ Clientes/Pagadores
- ✅ Categorias
- ✅ Dashboard completo
- ✅ **Geração automática de contas fixas mensais**

---

## Fluxo Recomendado

1. **Cadastre suas contas fixas** (aluguel, internet, etc.) marcando como recorrente
2. **Configure um CRON** ou **gere manualmente** todo fim de mês
3. **Acompanhe em Contas a Pagar** as contas geradas
4. **Marque como pago** quando efetuar o pagamento

---

**Versão:** 4.0 - Com Contas Recorrentes
**Data:** 2025-11-01
**Autor:** Claude + Hesron
