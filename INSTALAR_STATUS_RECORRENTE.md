# Instalação do Status "Recorrente"

## Como Adicionar o Novo Status ao Sistema

### 1. Execute o SQL

No phpMyAdmin:

1. Selecione o banco: `u411458227_studupss`
2. Vá na aba **SQL**
3. Copie TODO o conteúdo do arquivo: **`adicionar_status_recorrente.sql`**
4. Cole e clique em **Executar**

### 2. Arquivos Criados

✅ **adicionar_status_recorrente.sql** - SQL para adicionar o novo status
✅ **INSTALAR_STATUS_RECORRENTE.md** - Este arquivo de instruções

### 3. Arquivos Atualizados

✅ **contas.php** - Atualizado para usar status 'recorrente' ao criar/editar contas
✅ **dashboard.php** - Adicionado card para exibir contas recorrentes separadamente
✅ **style.css** - Adicionados estilos CSS para o status recorrente

---

## O que foi Alterado

### Status Disponíveis Agora:

1. **`pendente`** - Contas normais a pagar (não pagas ainda)
2. **`pago`** - Contas que já foram pagas
3. **`vencido`** - Contas vencidas que não foram pagas
4. **`recorrente`** - Contas recorrentes (templates/modelos)

### Lógica do Sistema:

**Contas Recorrentes ORIGINAIS:**
- Status: `recorrente`
- São os "templates" que definem as regras (dia do vencimento, valor, tipo de recorrência)
- NÃO aparecem nos cards de "Pendentes", "Pagas" ou "Vencidas"
- Aparecem em um card específico "Recorrentes" e na seção "Contas Recorrentes Ativas"

**Contas GERADAS Automaticamente:**
- Status inicial: `pendente`
- São criadas a partir das contas recorrentes
- Podem mudar para `pago` (quando marcadas como pagas) ou `vencido` (quando passam da data)
- Aparecem nos cards normais de contas a pagar

### Alteração na Tabela: contas_pagar

**Campo Modificado:**
```sql
status ENUM('pendente', 'pago', 'vencido', 'recorrente') DEFAULT 'pendente'
```

**Antes:** 3 status (pendente, pago, vencido)
**Depois:** 4 status (+ recorrente)

---

## Recursos Adicionados

### 1. Dashboard - Novo Card "Recorrentes"

**Localização:** Seção "Contas a Pagar" do dashboard

**Informações exibidas:**
- Total de contas recorrentes ativas
- Valor mensal total (soma de todas as contas recorrentes)

**Exemplo:**
```
┌─────────────────────┐
│ 🔄                  │
│ Recorrentes         │
│ 5                   │
│ R$ 2.500,00/mês    │
└─────────────────────┘
```

### 2. Criação de Contas

Ao criar uma nova conta em **contas.php**:

- **Conta Normal** → Status = `pendente`
- **Conta Recorrente** → Status = `recorrente`

### 3. Edição de Contas

Ao editar uma conta em **contas.php**:

- **Conta Gerada Automaticamente** → Mantém o status atual (pendente/pago/vencido)
- **Conta Recorrente Original** → Ajusta baseado no checkbox de recorrência

### 4. Estilos Visuais

**Cor do Badge:** Roxo (#9b59b6)

```css
.status-recorrente {
    background: #9b59b6;
    color: white;
}

.stat-card.recorrente {
    border-left-color: #9b59b6;
}
```

---

## Como Usar

### Criar uma Conta Recorrente

1. Acesse **Contas a Pagar**
2. Clique em **+ Nova Conta**
3. Preencha os dados:
   - Descrição: "Aluguel"
   - Valor: "1.500,00"
4. Marque **"Conta Recorrente"** (radio button)
5. Configure:
   - Tipo: Mensal
   - Dia do vencimento: 10
   - Primeira conta: 10/12/2025
6. Clique em **Salvar**

**Resultado:** Conta criada com `status = 'recorrente'`

### Gerar Contas do Mês

Use a página **gerenciar_recorrentes.php** ou o script CLI **gerar_contas_recorrentes.php**

As contas geradas terão `status = 'pendente'` e `gerada_automaticamente = TRUE`

### Visualizar no Dashboard

**Card "Recorrentes"** mostra apenas contas com `status = 'recorrente'`

**Card "Pendentes"** mostra apenas contas com `status = 'pendente'` (incluindo as geradas automaticamente)

**Seção "Contas Recorrentes Ativas"** mostra detalhes de todas as contas recorrentes

---

## Diferenças Antes vs Depois

### ANTES (Status Antigo)

Contas recorrentes tinham `status = 'pendente'`

**Problema:**
- Misturavam com contas normais pendentes
- Difícil distinguir templates de contas reais
- Cards mostravam valores incorretos

### DEPOIS (Status Novo)

Contas recorrentes têm `status = 'recorrente'`

**Benefícios:**
- ✅ Separação clara entre templates e contas reais
- ✅ Card específico mostra total mensal de contas recorrentes
- ✅ Contas geradas aparecem corretamente como pendentes
- ✅ Dashbo ard mais organizado e preciso

---

## Consultas SQL Úteis

### Ver todas as contas recorrentes

```sql
SELECT id, descricao, valor, status, tipo_recorrencia, dia_vencimento_recorrente
FROM contas_pagar
WHERE status = 'recorrente'
ORDER BY valor DESC;
```

### Ver contas geradas automaticamente

```sql
SELECT id, descricao, valor, status, data_vencimento, conta_recorrente_origem_id
FROM contas_pagar
WHERE gerada_automaticamente = TRUE
ORDER BY data_vencimento DESC
LIMIT 20;
```

### Atualizar contas existentes para o novo status

```sql
-- Contas recorrentes originais → status 'recorrente'
UPDATE contas_pagar
SET status = 'recorrente'
WHERE recorrente = TRUE
  AND gerada_automaticamente = FALSE;

-- Contas geradas → manter como 'pendente' (já devem estar assim)
UPDATE contas_pagar
SET status = 'pendente'
WHERE gerada_automaticamente = TRUE
  AND status = 'recorrente';
```

---

## Problemas Comuns

### Contas recorrentes aparecem como pendentes

**Causa:** SQL não foi executado ou executado parcialmente

**Solução:**
```sql
UPDATE contas_pagar
SET status = 'recorrente'
WHERE recorrente = TRUE AND gerada_automaticamente = FALSE;
```

### Card de recorrentes mostra 0

**Causa:** Nenhuma conta com status 'recorrente' no banco

**Solução:** Verifique se as contas recorrentes têm `status = 'recorrente'`

### Contas geradas não aparecem como pendentes

**Causa:** Contas geradas foram marcadas como 'recorrente' por engano

**Solução:**
```sql
UPDATE contas_pagar
SET status = 'pendente'
WHERE gerada_automaticamente = TRUE;
```

---

## Resumo da Estrutura

```
CONTA RECORRENTE ORIGINAL
┌─────────────────────────────┐
│ ID: 1                       │
│ Descrição: Aluguel          │
│ Valor: R$ 1.500,00          │
│ Status: recorrente          │
│ Recorrente: TRUE            │
│ Gerada Auto: FALSE          │
└─────────────────────────────┘
           │
           │ Gera automaticamente
           ▼
CONTA GERADA (Dezembro/2025)
┌─────────────────────────────┐
│ ID: 15                      │
│ Descrição: Aluguel (12/2025)│
│ Valor: R$ 1.500,00          │
│ Status: pendente            │
│ Recorrente: FALSE           │
│ Gerada Auto: TRUE           │
│ Origem ID: 1                │
└─────────────────────────────┘
```

---

**Instalação concluída!** ✅

Agora o sistema diferencia claramente:
- 🔄 **Contas Recorrentes** (templates)
- ⏳ **Contas Pendentes** (a pagar)
- ✓ **Contas Pagas**
- ⚠ **Contas Vencidas**

---

**Versão:** 4.1 - Com Status Recorrente
**Data:** 2025-11-01
**Autor:** Claude + Hesron
