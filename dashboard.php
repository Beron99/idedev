<?php
require_once 'config.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Criar categorias padrão se não existirem
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);

    if ($stmt->fetchColumn() == 0) {
        $categorias_padrao = [
            ['Alimentação', '#FF6384'],
            ['Transporte', '#36A2EB'],
            ['Moradia', '#FFCE56'],
            ['Saúde', '#4BC0C0'],
            ['Educação', '#9966FF'],
            ['Lazer', '#FF9F40'],
            ['Outros', '#C9CBCF']
        ];

        $stmt = $pdo->prepare("INSERT INTO categorias (nome, cor, usuario_id) VALUES (?, ?, ?)");
        foreach ($categorias_padrao as $cat) {
            $stmt->execute([$cat[0], $cat[1], $usuario_id]);
        }
    }
} catch(PDOException $e) {
    // Silenciar erro se já existirem
}

// Buscar estatísticas gerais
try {
    // Total de contas pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_pagar WHERE usuario_id = ? AND status = 'pendente'");
    $stmt->execute([$usuario_id]);
    $pendentes = $stmt->fetch();

    // Total de contas pagas no mês atual
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_pagar WHERE usuario_id = ? AND status = 'pago' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())");
    $stmt->execute([$usuario_id]);
    $pagas_mes = $stmt->fetch();

    // Total de contas vencidas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_pagar WHERE usuario_id = ? AND status = 'pendente' AND data_vencimento < CURRENT_DATE()");
    $stmt->execute([$usuario_id]);
    $vencidas = $stmt->fetch();

    // Atualizar status de contas vencidas
    $pdo->prepare("UPDATE contas_pagar SET status = 'vencido' WHERE status = 'pendente' AND data_vencimento < CURRENT_DATE()")->execute();

    // Próximas contas a vencer (próximos 7 dias)
    $stmt = $pdo->prepare("
        SELECT c.*, cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM contas_pagar c
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        WHERE c.usuario_id = ? AND c.status = 'pendente'
        AND c.data_vencimento BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
        ORDER BY c.data_vencimento ASC
        LIMIT 5
    ");
    $stmt->execute([$usuario_id]);
    $proximas = $stmt->fetchAll();

    // Gastos por categoria (últimos 30 dias)
    $stmt = $pdo->prepare("
        SELECT cat.nome, cat.cor, COALESCE(SUM(c.valor), 0) as total
        FROM categorias cat
        LEFT JOIN contas_pagar c ON cat.id = c.categoria_id
            AND c.usuario_id = ?
            AND c.status = 'pago'
            AND c.data_pagamento >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        WHERE cat.usuario_id = ?
        GROUP BY cat.id, cat.nome, cat.cor
        HAVING total > 0
        ORDER BY total DESC
    ");
    $stmt->execute([$usuario_id, $usuario_id]);
    $gastos_categoria = $stmt->fetchAll();

    // Evolução mensal (últimos 6 meses)
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(data_pagamento, '%Y-%m') as mes,
            SUM(valor) as total
        FROM contas_pagar
        WHERE usuario_id = ? AND status = 'pago'
        AND data_pagamento >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(data_pagamento, '%Y-%m')
        ORDER BY mes ASC
    ");
    $stmt->execute([$usuario_id]);
    $evolucao = $stmt->fetchAll();

} catch(PDOException $e) {
    $erro = 'Erro ao buscar dados: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Gestão Financeira</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <div class="container-painel">
        <div class="header-painel">
            <h1>Gestão Financeira</h1>
            <div class="user-info">
                <span>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>!</span>
                <a href="logout.php" class="btn-logout">Sair</a>
            </div>
        </div>

        <div class="content-painel">
            <!-- Menu de navegação -->
            <div class="menu-nav">
                <a href="dashboard.php" class="nav-item active">Dashboard</a>
                <a href="contas.php" class="nav-item">Contas a Pagar</a>
                <a href="categorias.php" class="nav-item">Categorias</a>
            </div>

            <?php if (isset($erro)): ?>
                <div class="mensagem erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <!-- Cards de estatísticas -->
            <div class="stats-grid">
                <div class="stat-card pendente">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3>Contas Pendentes</h3>
                        <p class="stat-number"><?php echo $pendentes['total']; ?></p>
                        <p class="stat-value">R$ <?php echo number_format($pendentes['soma'], 2, ',', '.'); ?></p>
                    </div>
                </div>

                <div class="stat-card pago">
                    <div class="stat-icon">✓</div>
                    <div class="stat-info">
                        <h3>Pagas este Mês</h3>
                        <p class="stat-number"><?php echo $pagas_mes['total']; ?></p>
                        <p class="stat-value">R$ <?php echo number_format($pagas_mes['soma'], 2, ',', '.'); ?></p>
                    </div>
                </div>

                <div class="stat-card vencido">
                    <div class="stat-icon">⚠</div>
                    <div class="stat-info">
                        <h3>Contas Vencidas</h3>
                        <p class="stat-number"><?php echo $vencidas['total']; ?></p>
                        <p class="stat-value">R$ <?php echo number_format($vencidas['soma'], 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="charts-grid">
                <div class="chart-box">
                    <h3>Gastos por Categoria (30 dias)</h3>
                    <canvas id="chartCategorias"></canvas>
                </div>

                <div class="chart-box">
                    <h3>Evolução Mensal</h3>
                    <canvas id="chartEvolucao"></canvas>
                </div>
            </div>

            <!-- Próximas contas a vencer -->
            <div class="proximas-contas">
                <h3>Próximas Contas (7 dias)</h3>
                <?php if (count($proximas) > 0): ?>
                    <div class="contas-list">
                        <?php foreach ($proximas as $conta): ?>
                            <div class="conta-item">
                                <div class="conta-info">
                                    <span class="conta-categoria" style="background-color: <?php echo $conta['categoria_cor'] ?? '#ccc'; ?>"></span>
                                    <div>
                                        <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                                        <small><?php echo $conta['categoria_nome'] ?? 'Sem categoria'; ?></small>
                                    </div>
                                </div>
                                <div class="conta-detalhes">
                                    <span class="conta-valor">R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></span>
                                    <span class="conta-vencimento"><?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="texto-vazio">Nenhuma conta a vencer nos próximos 7 dias!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de Pizza - Gastos por Categoria
        const ctxCategorias = document.getElementById('chartCategorias');
        new Chart(ctxCategorias, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($gastos_categoria, 'nome')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($gastos_categoria, 'total')); ?>,
                    backgroundColor: <?php echo json_encode(array_column($gastos_categoria, 'cor')); ?>,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': R$ ' + context.parsed.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            }
        });

        // Gráfico de Linha - Evolução Mensal
        const ctxEvolucao = document.getElementById('chartEvolucao');
        new Chart(ctxEvolucao, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($item) {
                    $date = DateTime::createFromFormat('Y-m', $item['mes']);
                    return $date->format('M/y');
                }, $evolucao)); ?>,
                datasets: [{
                    label: 'Gastos (R$)',
                    data: <?php echo json_encode(array_column($evolucao, 'total')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(0);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
