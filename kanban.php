<?php
session_start();
require_once 'config.php';
require_once 'permissions.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_role = $_SESSION['usuario_role'];
$usuario_departamento = $_SESSION['usuario_departamento_id'] ?? null;

// Filtros
$filtro_departamento = $_GET['departamento'] ?? '';
$filtro_prioridade = $_GET['prioridade'] ?? '';
$filtro_valor_min = $_GET['valor_min'] ?? '';
$filtro_valor_max = $_GET['valor_max'] ?? '';

// Construir WHERE baseado em permissões
$where = ["c.status IN ('pendente', 'pago')"];
$params = [];

// Usuários comuns só veem suas contas
if ($usuario_role === 'usuario') {
    $where[] = "c.usuario_id = :usuario_id";
    $params[':usuario_id'] = $usuario_id;
}
// Gestores veem apenas seu departamento
elseif ($usuario_role === 'gestor' && $usuario_departamento) {
    $where[] = "c.departamento_id = :departamento_id";
    $params[':departamento_id'] = $usuario_departamento;
}
// Admins veem tudo

// Aplicar filtros adicionais
if ($filtro_departamento && $usuario_role === 'admin') {
    $where[] = "c.departamento_id = :filtro_dept";
    $params[':filtro_dept'] = $filtro_departamento;
}

if ($filtro_prioridade) {
    $where[] = "c.prioridade = :prioridade";
    $params[':prioridade'] = $filtro_prioridade;
}

if ($filtro_valor_min) {
    $where[] = "c.valor >= :valor_min";
    $params[':valor_min'] = $filtro_valor_min;
}

if ($filtro_valor_max) {
    $where[] = "c.valor <= :valor_max";
    $params[':valor_max'] = $filtro_valor_max;
}

// Buscar contas para o Kanban
$sql = "SELECT c.*,
               cat.nome as categoria_nome,
               cat.cor as categoria_cor,
               d.nome as departamento_nome,
               d.cor as departamento_cor,
               u.nome as usuario_nome
        FROM contas_pagar c
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        LEFT JOIN departamentos d ON c.departamento_id = d.id
        LEFT JOIN usuarios u ON c.usuario_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.posicao_kanban ASC, c.data_vencimento ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$todas_contas = $stmt->fetchAll();

// Organizar por coluna
$colunas = [
    'pendente' => ['titulo' => 'A Fazer', 'cor' => '#e74c3c', 'contas' => []],
    'em_analise' => ['titulo' => 'Em Análise', 'cor' => '#f39c12', 'contas' => []],
    'aprovado' => ['titulo' => 'Aprovado', 'cor' => '#3498db', 'contas' => []],
    'pago' => ['titulo' => 'Pago', 'cor' => '#27ae60', 'contas' => []]
];

foreach ($todas_contas as $conta) {
    $status_kanban = $conta['status'];

    // Mapear status de aprovação para colunas do Kanban
    if ($conta['status'] === 'pendente') {
        if ($conta['aprovacao_status'] === 'pendente') {
            $status_kanban = 'em_analise';
        } elseif ($conta['aprovacao_status'] === 'aprovado') {
            $status_kanban = 'aprovado';
        } elseif ($conta['aprovacao_status'] === 'rejeitado') {
            $status_kanban = 'pendente';
        }
    }

    if (isset($colunas[$status_kanban])) {
        $colunas[$status_kanban]['contas'][] = $conta;
    }
}

// Buscar departamentos para filtro (apenas admins)
if ($usuario_role === 'admin') {
    $stmt = $pdo->query("SELECT id, nome FROM departamentos ORDER BY nome");
    $departamentos = $stmt->fetchAll();
}

$csrf_token = gerarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban - Contas a Pagar</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .kanban-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .kanban-filtros {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .kanban-filtros select,
        .kanban-filtros input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .kanban-board {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 20px;
            min-height: 600px;
        }

        .kanban-column {
            flex: 1;
            min-width: 300px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
        }

        .kanban-column-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid;
        }

        .kanban-column-title {
            font-weight: bold;
            font-size: 16px;
        }

        .kanban-column-count {
            background: #fff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .kanban-cards {
            min-height: 400px;
        }

        .kanban-card {
            background: white;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: move;
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid;
        }

        .kanban-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .kanban-card.dragging {
            opacity: 0.5;
        }

        .kanban-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 8px;
        }

        .kanban-card-titulo {
            font-weight: bold;
            font-size: 14px;
            flex: 1;
        }

        .kanban-card-valor {
            font-size: 16px;
            font-weight: bold;
            color: #27ae60;
        }

        .kanban-card-info {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .kanban-card-badges {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .kanban-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-prioridade {
            background: #e74c3c;
            color: white;
        }

        .badge-prioridade.media {
            background: #f39c12;
        }

        .badge-prioridade.baixa {
            background: #95a5a6;
        }

        .badge-vencimento {
            background: #3498db;
            color: white;
        }

        .badge-vencimento.vencido {
            background: #e74c3c;
        }

        .badge-vencimento.proximo {
            background: #f39c12;
        }

        .kanban-card-footer {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #eee;
            font-size: 11px;
            color: #999;
        }

        .kanban-column.drag-over {
            background: #e8f4f8;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="kanban-header">
            <div>
                <h1>📋 Kanban - Contas a Pagar</h1>
                <p>Arraste os cards para mudar o status</p>
            </div>
            <div class="view-toggle">
                <a href="dashboard.php" class="btn-secondary">📊 Dashboard</a>
                <a href="contas.php" class="btn-secondary">📋 Lista</a>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" class="kanban-filtros">
            <?php if ($usuario_role === 'admin'): ?>
                <select name="departamento">
                    <option value="">Todos os departamentos</option>
                    <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>"
                                <?php echo $filtro_departamento == $dept['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dept['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <select name="prioridade">
                <option value="">Todas as prioridades</option>
                <option value="alta" <?php echo $filtro_prioridade === 'alta' ? 'selected' : ''; ?>>Alta</option>
                <option value="media" <?php echo $filtro_prioridade === 'media' ? 'selected' : ''; ?>>Média</option>
                <option value="baixa" <?php echo $filtro_prioridade === 'baixa' ? 'selected' : ''; ?>>Baixa</option>
            </select>

            <input type="number" name="valor_min" placeholder="Valor mín." step="0.01"
                   value="<?php echo htmlspecialchars($filtro_valor_min); ?>">
            <input type="number" name="valor_max" placeholder="Valor máx." step="0.01"
                   value="<?php echo htmlspecialchars($filtro_valor_max); ?>">

            <button type="submit" class="btn-secondary">🔍 Filtrar</button>
            <a href="kanban.php" class="btn-secondary">🔄 Limpar</a>
        </form>

        <!-- Board Kanban -->
        <div class="kanban-board">
            <?php foreach ($colunas as $status => $coluna): ?>
                <div class="kanban-column" data-status="<?php echo $status; ?>">
                    <div class="kanban-column-header" style="border-color: <?php echo $coluna['cor']; ?>;">
                        <span class="kanban-column-title" style="color: <?php echo $coluna['cor']; ?>;">
                            <?php echo $coluna['titulo']; ?>
                        </span>
                        <span class="kanban-column-count" style="color: <?php echo $coluna['cor']; ?>;">
                            <?php echo count($coluna['contas']); ?>
                        </span>
                    </div>

                    <div class="kanban-cards">
                        <?php foreach ($coluna['contas'] as $conta): ?>
                            <?php
                            $dias_vencimento = (strtotime($conta['data_vencimento']) - strtotime(date('Y-m-d'))) / 86400;
                            $classe_vencimento = $dias_vencimento < 0 ? 'vencido' : ($dias_vencimento <= 7 ? 'proximo' : '');
                            ?>
                            <div class="kanban-card"
                                 draggable="true"
                                 data-conta-id="<?php echo $conta['id']; ?>"
                                 style="border-left-color: <?php echo $conta['categoria_cor'] ?? '#999'; ?>;">

                                <div class="kanban-card-header">
                                    <div class="kanban-card-titulo">
                                        <?php echo htmlspecialchars($conta['descricao']); ?>
                                    </div>
                                    <div class="kanban-card-valor">
                                        R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?>
                                    </div>
                                </div>

                                <div class="kanban-card-info">
                                    📅 Vencimento: <?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?>
                                </div>

                                <?php if ($conta['categoria_nome']): ?>
                                    <div class="kanban-card-info">
                                        🏷️ <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($conta['departamento_nome']): ?>
                                    <div class="kanban-card-info">
                                        🏢 <?php echo htmlspecialchars($conta['departamento_nome']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="kanban-card-badges">
                                    <?php if ($conta['prioridade']): ?>
                                        <span class="kanban-badge badge-prioridade <?php echo $conta['prioridade']; ?>">
                                            <?php echo ucfirst($conta['prioridade']); ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="kanban-badge badge-vencimento <?php echo $classe_vencimento; ?>">
                                        <?php
                                        if ($dias_vencimento < 0) {
                                            echo 'Vencido há ' . abs((int)$dias_vencimento) . ' dias';
                                        } elseif ($dias_vencimento <= 7) {
                                            echo 'Vence em ' . (int)$dias_vencimento . ' dias';
                                        } else {
                                            echo 'No prazo';
                                        }
                                        ?>
                                    </span>
                                </div>

                                <div class="kanban-card-footer">
                                    Por: <?php echo htmlspecialchars($conta['usuario_nome'] ?? 'Sistema'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($coluna['contas'])): ?>
                            <p style="text-align: center; color: #999; margin-top: 20px;">
                                Nenhuma conta nesta coluna
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        let draggedElement = null;

        // Eventos de drag
        document.querySelectorAll('.kanban-card').forEach(card => {
            card.addEventListener('dragstart', function(e) {
                draggedElement = this;
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            card.addEventListener('dragend', function(e) {
                this.classList.remove('dragging');
            });

            // Click para abrir detalhes
            card.addEventListener('click', function(e) {
                if (!this.classList.contains('dragging')) {
                    const contaId = this.dataset.contaId;
                    window.location.href = 'contas.php?acao=ver&id=' + contaId;
                }
            });
        });

        // Eventos das colunas
        document.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });

            column.addEventListener('dragleave', function(e) {
                this.classList.remove('drag-over');
            });

            column.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (draggedElement) {
                    const contaId = draggedElement.dataset.contaId;
                    const novoStatus = this.dataset.status;

                    // Mover visualmente
                    this.querySelector('.kanban-cards').appendChild(draggedElement);

                    // Atualizar no servidor
                    atualizarStatusConta(contaId, novoStatus);
                }
            });
        });

        function atualizarStatusConta(contaId, novoStatus) {
            fetch('kanban_ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `acao=atualizar_status&conta_id=${contaId}&status=${novoStatus}&csrf_token=<?php echo $csrf_token; ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Erro ao atualizar status: ' + data.message);
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao atualizar status');
                location.reload();
            });
        }
    </script>
</body>
</html>
