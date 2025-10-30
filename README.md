# Sistema de Gestão Financeira

Sistema completo de gestão de contas a pagar com dashboard, gráficos e relatórios.

## Funcionalidades

### Dashboard
- Estatísticas em tempo real (contas pendentes, pagas e vencidas)
- Gráfico de pizza com gastos por categoria (últimos 30 dias)
- Gráfico de linha com evolução mensal (últimos 6 meses)
- Lista de próximas contas a vencer (7 dias)

### Contas a Pagar
- Cadastro completo de contas (descrição, valor, vencimento, categoria, observações)
- Listagem com filtros por status, categoria e mês
- Edição e exclusão de contas
- Marcar contas como pagas
- Status automático (pendente, pago, vencido)

### Categorias
- Sistema de categorias personalizadas com cores
- 7 categorias padrão pré-cadastradas
- Estatísticas por categoria (total de contas e gastos)
- Gerenciamento completo (adicionar, editar, excluir)

### Autenticação
- Sistema de login e cadastro
- Senhas criptografadas
- Sessões seguras
- Campo de senha com visualização

## Instalação

1. Execute o script SQL no seu banco de dados:
```bash
mysql -u seu_usuario -p seu_banco < database.sql
```

2. Configure as credenciais em `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'seu_banco');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

3. Acesse o sistema:
- Cadastro: `cadastro.php`
- Login: `login.php`
- Dashboard: `dashboard.php` (após login)

## Estrutura de Arquivos

```
├── cadastro.php       # Página de cadastro de usuários
├── login.php          # Página de login
├── logout.php         # Script de logout
├── dashboard.php      # Dashboard principal com gráficos
├── contas.php         # Gerenciamento de contas a pagar
├── categorias.php     # Gerenciamento de categorias
├── config.php         # Configurações do banco de dados
├── database.sql       # Script de criação das tabelas
├── style.css          # Estilos do sistema
└── README.md          # Este arquivo
```

## Tecnologias Utilizadas

- **Backend**: PHP 7.4+
- **Banco de Dados**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Gráficos**: Chart.js 4.4.0
- **Design**: Responsivo com CSS Grid e Flexbox

## Banco de Dados

### Tabelas
- `usuarios`: Cadastro de usuários
- `categorias`: Categorias de despesas
- `contas_pagar`: Contas a pagar

### Relacionamentos
- Cada conta pertence a um usuário
- Cada conta pode ter uma categoria
- Cada categoria pertence a um usuário

## Segurança

- Senhas criptografadas com `password_hash()`
- Proteção contra SQL Injection com Prepared Statements
- Validação de sessão em todas as páginas
- Proteção XSS com `htmlspecialchars()`

## Autor

Sistema desenvolvido para gestão financeira pessoal.
