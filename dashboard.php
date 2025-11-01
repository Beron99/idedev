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
    // Atualizar status de contas vencidas (pagar e receber)
    $pdo->prepare("UPDATE contas_pagar SET status = 'vencido' WHERE status = 'pendente' AND data_vencimento < CURRENT_DATE()")->execute();

    // Verificar se a tabela contas_receber existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'contas_receber'");
    $tem_contas_receber = $stmt->rowCount() > 0;

    if ($tem_contas_receber) {
        $pdo->prepare("UPDATE contas_receber SET status = 'vencido' WHERE status = 'pendente' AND data_vencimento < CURRENT_DATE()")->execute();
    }

    // === CONTAS A PAGAR ===
    // Pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_pagar WHERE usuario_id = ? AND status = 'pendente'");
    $stmt->execute([$usuario_id]);
    $pagar_pendentes = $stmt->fetch();

    // Pagas este mês
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_pagar WHERE usuario_id = ? AND status = 'pago' AND MONTH(data_pagamento) = MONTH(CURRENT_DATE()) AND YEAR(data_pagamento) = YEAR(CURRENT_DATE())");
    $stmt->execute([$usuario_id]);
    $pagar_pagas_mes = $stmt->fetch();

    // Vencidas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_pagar WHERE usuario_id = ? AND status = 'vencido'");
    $stmt->execute([$usuario_id]);
    $pagar_vencidas = $stmt->fetch();

    // === CONTAS A RECEBER (se existir a tabela) ===
    if ($tem_contas_receber) {
        // Pendentes
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_receber WHERE usuario_id = ? AND status = 'pendente'");
        $stmt->execute([$usuario_id]);
        $receber_pendentes = $stmt->fetch();

        // Recebidas este mês
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_receber WHERE usuario_id = ? AND status = 'recebido' AND MONTH(data_recebimento) = MONTH(CURRENT_DATE()) AND YEAR(data_recebimento) = YEAR(CURRENT_DATE())");
        $stmt->execute([$usuario_id]);
        $receber_recebidas_mes = $stmt->fetch();

        // Vencidas
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(valor), 0) as soma FROM contas_receber WHERE usuario_id = ? AND status = 'vencido'");
        $stmt->execute([$usuario_id]);
        $receber_vencidas = $stmt->fetch();
    } else {
        // Valores padrão se não existir a tabela
        $receber_pendentes = ['total' => 0, 'soma' => 0];
        $receber_recebidas_mes = ['total' => 0, 'soma' => 0];
        $receber_vencidas = ['total' => 0, 'soma' => 0];
    }

    // === FLUXO DE CAIXA (Resumo) ===
    $saldo_previsto = $receber_pendentes['soma'] - $pagar_pendentes['soma'];
    $saldo_mes = $receber_recebidas_mes['soma'] - $pagar_pagas_mes['soma'];

    // === PRÓXIMAS CONTAS A PAGAR (próximos 7 dias) ===
    $stmt = $pdo->prepare("
        SELECT 'pagar' as tipo, c.*, cat.nome as categoria_nome, cat.cor as categoria_cor
        FROM contas_pagar c
        LEFT JOIN categorias cat ON c.categoria_id = cat.id
        WHERE c.usuario_id = ? AND c.status = 'pendente'
        AND c.data_vencimento BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
        ORDER BY c.data_vencimento ASC
        LIMIT 5
    ");
    $stmt->execute([$usuario_id]);
    $proximas_pagar = $stmt->fetchAll();

    // === PRÓXIMAS CONTAS A RECEBER (próximos 7 dias) ===
    if ($tem_contas_receber) {
        $stmt = $pdo->prepare("
            SELECT 'receber' as tipo, c.*, cat.nome as categoria_nome, cat.cor as categoria_cor
            FROM contas_receber c
            LEFT JOIN categorias cat ON c.categoria_id = cat.id
            WHERE c.usuario_id = ? AND c.status = 'pendente'
            AND c.data_vencimento BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY)
            ORDER BY c.data_vencimento ASC
            LIMIT 5
        ");
        $stmt->execute([$usuario_id]);
        $proximas_receber = $stmt->fetchAll();
    } else {
        $proximas_receber = [];
    }

    // === GASTOS vs RECEITAS (últimos 30 dias) ===
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(valor), 0) as total
        FROM contas_pagar
        WHERE usuario_id = ? AND status = 'pago'
        AND data_pagamento >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$usuario_id]);
    $gastos_30dias = $stmt->fetchColumn();

    if ($tem_contas_receber) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(valor), 0) as total
            FROM contas_receber
            WHERE usuario_id = ? AND status = 'recebido'
            AND data_recebimento >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$usuario_id]);
        $receitas_30dias = $stmt->fetchColumn();
    } else {
        $receitas_30dias = 0;
    }

    // === GASTOS POR CATEGORIA (últimos 30 dias) ===
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
        LIMIT 8
    ");
    $stmt->execute([$usuario_id, $usuario_id]);
    $gastos_categoria = $stmt->fetchAll();

    // === EVOLUÇÃO MENSAL - Receitas vs Despesas (últimos 6 meses) ===
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
    $evolucao_despesas = $stmt->fetchAll();

    if ($tem_contas_receber) {
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(data_recebimento, '%Y-%m') as mes,
                SUM(valor) as total
            FROM contas_receber
            WHERE usuario_id = ? AND status = 'recebido'
            AND data_recebimento >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(data_recebimento, '%Y-%m')
            ORDER BY mes ASC
        ");
        $stmt->execute([$usuario_id]);
        $evolucao_receitas = $stmt->fetchAll();
    } else {
        $evolucao_receitas = [];
    }

    // Criar array combinado de meses
    $meses = [];
    foreach ($evolucao_despesas as $item) {
        if (!in_array($item['mes'], $meses)) $meses[] = $item['mes'];
    }
    foreach ($evolucao_receitas as $item) {
        if (!in_array($item['mes'], $meses)) $meses[] = $item['mes'];
    }
    sort($meses);

    // Organizar dados por mês
    $dados_despesas = [];
    $dados_receitas = [];
    foreach ($meses as $mes) {
        $desp = array_filter($evolucao_despesas, function($item) use ($mes) { return $item['mes'] == $mes; });
        $rec = array_filter($evolucao_receitas, function($item) use ($mes) { return $item['mes'] == $mes; });

        $dados_despesas[] = $desp ? array_values($desp)[0]['total'] : 0;
        $dados_receitas[] = $rec ? array_values($rec)[0]['total'] : 0;
    }

    // === SUGESTÕES E ALERTAS ===
    $sugestoes = [];
    $alertas = [];

    // Alerta: Contas vencidas
    if ($pagar_vencidas['total'] > 0) {
        $alertas[] = [
            'tipo' => 'erro',
            'icone' => '⚠️',
            'titulo' => 'Contas Vencidas!',
            'mensagem' => "Você tem {$pagar_vencidas['total']} conta(s) a pagar vencida(s) no valor de R$ " . number_format($pagar_vencidas['soma'], 2, ',', '.')
        ];
    }

    if ($tem_contas_receber && $receber_vencidas['total'] > 0) {
        $alertas[] = [
            'tipo' => 'aviso',
            'icone' => '💰',
            'titulo' => 'Recebimentos Atrasados',
            'mensagem' => "Você tem {$receber_vencidas['total']} conta(s) a receber vencida(s) no valor de R$ " . number_format($receber_vencidas['soma'], 2, ',', '.')
        ];
    }

    // Sugestão: Gastos acima da média
    if ($gastos_30dias > $receitas_30dias && $receitas_30dias > 0) {
        $diferenca = $gastos_30dias - $receitas_30dias;
        $sugestoes[] = [
            'icone' => '📊',
            'titulo' => 'Atenção aos Gastos',
            'mensagem' => "Seus gastos estão R$ " . number_format($diferenca, 2, ',', '.') . " acima das receitas nos últimos 30 dias. Considere revisar suas despesas."
        ];
    }

    // Sugestão: Saldo positivo
    if ($saldo_mes > 0) {
        $sugestoes[] = [
            'icone' => '✅',
            'titulo' => 'Parabéns!',
            'mensagem' => "Você teve um saldo positivo de R$ " . number_format($saldo_mes, 2, ',', '.') . " neste mês. Continue assim!"
        ];
    }

    // Sugestão: Muitas contas pendentes
    if ($pagar_pendentes['total'] > 10) {
        $sugestoes[] = [
            'icone' => '📝',
            'titulo' => 'Organize suas Contas',
            'mensagem' => "Você tem {$pagar_pendentes['total']} contas pendentes. Considere organizá-las por prioridade."
        ];
    }

    // Sugestão: Sem categorias
    if (count($gastos_categoria) == 0) {
        $sugestoes[] = [
            'icone' => '🏷️',
            'titulo' => 'Use Categorias',
            'mensagem' => "Categorize suas despesas para ter um melhor controle financeiro e visualização nos gráficos."
        ];
    }

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
    <link rel="stylesheet" href="ESTILOS_DASHBOARD.css">
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
                <a href="contas_receber.php" class="nav-item">Contas a Receber</a>
                <a href="clientes.php" class="nav-item">Clientes</a>
                <a href="categorias.php" class="nav-item">Categorias</a>
            </div>

            <?php if (isset($erro)): ?>
                <div class="mensagem erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <!-- Alertas -->
            <?php if (count($alertas) > 0): ?>
                <div class="alertas-container">
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="alerta alerta-<?php echo $alerta['tipo']; ?>">
                            <span class="alerta-icone"><?php echo $alerta['icone']; ?></span>
                            <div class="alerta-conteudo">
                                <strong><?php echo $alerta['titulo']; ?></strong>
                                <p><?php echo $alerta['mensagem']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Resumo Financeiro -->
            <div class="resumo-financeiro">
                <div class="resumo-card saldo-mes <?php echo $saldo_mes >= 0 ? 'positivo' : 'negativo'; ?>">
                    <h4>Saldo do Mês</h4>
                    <p class="resumo-valor">R$ <?php echo number_format(abs($saldo_mes), 2, ',', '.'); ?></p>
                    <small><?php echo $saldo_mes >= 0 ? 'Positivo' : 'Negativo'; ?></small>
                </div>
                <div class="resumo-card saldo-previsto <?php echo $saldo_previsto >= 0 ? 'positivo' : 'negativo'; ?>">
                    <h4>Saldo Previsto</h4>
                    <p class="resumo-valor">R$ <?php echo number_format(abs($saldo_previsto), 2, ',', '.'); ?></p>
                    <small>Pendentes</small>
                </div>
            </div>

            <!-- Cards de estatísticas - CONTAS A PAGAR -->
            <h3 class="secao-titulo">Contas a Pagar</h3>
            <div class="stats-grid">
                <div class="stat-card pendente">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3>Pendentes</h3>
                        <p class="stat-number"><?php echo $pagar_pendentes['total']; ?></p>
                        <p class="stat-value">R$ <?php echo number_format($pagar_pendentes['soma'], 2, ',', '.'); ?></p>
                    </div>
                </div>

                <div class="stat-card pago">
                    <div class="stat-icon">✓</div>
                    <div class="stat-info">
                        <h3>Pagas este Mês</h3>
                        <p class="stat-number"><?php echo $pagar_pagas_mes['total']; ?></p>
                        <p class="stat-value">R$ <?php echo number_format($pagar_pagas_mes['soma'], 2, ',', '.'); ?></p>
                    </div>
                </div>

                <div class="stat-card vencido">
                    <div class="stat-icon">⚠</div>
                    <div class="stat-info">
                        <h3>Vencidas</h3>
                        <p class="stat-number"><?php echo $pagar_vencidas['total']; ?></p>
                        <p class="stat-value">R$ <?php echo number_format($pagar_vencidas['soma'], 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Cards de estatísticas - CONTAS A RECEBER -->
            <?php if ($tem_contas_receber): ?>
                <h3 class="secao-titulo">Contas a Receber</h3>
                <div class="stats-grid">
                    <div class="stat-card receber-pendente">
                        <div class="stat-icon">💰</div>
                        <div class="stat-info">
                            <h3>Pendentes</h3>
                            <p class="stat-number"><?php echo $receber_pendentes['total']; ?></p>
                            <p class="stat-value">R$ <?php echo number_format($receber_pendentes['soma'], 2, ',', '.'); ?></p>
                        </div>
                    </div>

                    <div class="stat-card receber-recebido">
                        <div class="stat-icon">✅</div>
                        <div class="stat-info">
                            <h3>Recebidas este Mês</h3>
                            <p class="stat-number"><?php echo $receber_recebidas_mes['total']; ?></p>
                            <p class="stat-value">R$ <?php echo number_format($receber_recebidas_mes['soma'], 2, ',', '.'); ?></p>
                        </div>
                    </div>

                    <div class="stat-card receber-vencido">
                        <div class="stat-icon">⏰</div>
                        <div class="stat-info">
                            <h3>Vencidas</h3>
                            <p class="stat-number"><?php echo $receber_vencidas['total']; ?></p>
                            <p class="stat-value">R$ <?php echo number_format($receber_vencidas['soma'], 2, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Gráficos -->
            <div class="charts-grid">
                <div class="chart-box">
                    <h3>Receitas vs Despesas (6 meses)</h3>
                    <canvas id="chartEvolucao"></canvas>
                </div>

                <div class="chart-box">
                    <h3>Gastos por Categoria (30 dias)</h3>
                    <canvas id="chartCategorias"></canvas>
                </div>
            </div>

            <!-- Próximas Contas -->
            <div class="proximas-grid">
                <!-- Contas a PAGAR -->
                <div class="proximas-contas">
                    <h3>📤 Próximas a Pagar (7 dias)</h3>
                    <?php if (count($proximas_pagar) > 0): ?>
                        <div class="contas-list">
                            <?php foreach ($proximas_pagar as $conta): ?>
                                <div class="conta-item pagar">
                                    <div class="conta-info">
                                        <span class="conta-categoria" style="background-color: <?php echo $conta['categoria_cor'] ?? '#ccc'; ?>"></span>
                                        <div>
                                            <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                                            <small><?php echo $conta['categoria_nome'] ?? 'Sem categoria'; ?></small>
                                        </div>
                                    </div>
                                    <div class="conta-detalhes">
                                        <span class="conta-valor pagar">R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></span>
                                        <span class="conta-vencimento"><?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="texto-vazio">Nenhuma conta a pagar nos próximos 7 dias</p>
                    <?php endif; ?>
                </div>

                <!-- Contas a RECEBER -->
                <?php if ($tem_contas_receber): ?>
                    <div class="proximas-contas">
                        <h3>📥 Próximas a Receber (7 dias)</h3>
                        <?php if (count($proximas_receber) > 0): ?>
                            <div class="contas-list">
                                <?php foreach ($proximas_receber as $conta): ?>
                                    <div class="conta-item receber">
                                        <div class="conta-info">
                                            <span class="conta-categoria" style="background-color: <?php echo $conta['categoria_cor'] ?? '#ccc'; ?>"></span>
                                            <div>
                                                <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                                                <small><?php echo $conta['cliente'] ?? 'Cliente não informado'; ?></small>
                                            </div>
                                        </div>
                                        <div class="conta-detalhes">
                                            <span class="conta-valor receber">R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></span>
                                            <span class="conta-vencimento"><?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="texto-vazio">Nenhuma conta a receber nos próximos 7 dias</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sugestões -->
            <?php if (count($sugestoes) > 0): ?>
                <div class="sugestoes-container">
                    <h3>💡 Sugestões</h3>
                    <div class="sugestoes-grid">
                        <?php foreach ($sugestoes as $sugestao): ?>
                            <div class="sugestao-card">
                                <span class="sugestao-icone"><?php echo $sugestao['icone']; ?></span>
                                <div class="sugestao-conteudo">
                                    <h4><?php echo $sugestao['titulo']; ?></h4>
                                    <p><?php echo $sugestao['mensagem']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Gráfico de Linha - Receitas vs Despesas
        const ctxEvolucao = document.getElementById('chartEvolucao');
        new Chart(ctxEvolucao, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($mes) {
                    $date = DateTime::createFromFormat('Y-m', $mes);
                    return $date->format('M/y');
                }, $meses)); ?>,
                datasets: [
                    {
                        label: 'Receitas',
                        data: <?php echo json_encode($dados_receitas); ?>,
                        borderColor: '#2ecc71',
                        backgroundColor: 'rgba(46, 204, 113, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Despesas',
                        data: <?php echo json_encode($dados_despesas); ?>,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toFixed(2).replace('.', ',');
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
                                let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                let percent = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': R$ ' + context.parsed.toFixed(2).replace('.', ',') + ' (' + percent + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
