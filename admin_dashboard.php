<?php
// Dashboard Administrativo
if (!defined('ADMIN_PAGE')) {
    die('Acesso negado');
}

// Buscar estatísticas
try {
    // Stats gerais
    $stmt = $pdo->query("SELECT * FROM v_dashboard_stats ORDER BY departamento");
    $stats_departamentos = $stmt->fetchAll();

    // Usuários por role
    $stmt = $pdo->query("
        SELECT role, COUNT(*) as total
        FROM usuarios
        WHERE ativo = TRUE
        GROUP BY role
    ");
    $usuarios_por_role = $stmt->fetchAll();

    // Contas por status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total, SUM(valor) as valor_total
        FROM contas_pagar
        GROUP BY status
    ");
    $contas_por_status = $stmt->fetchAll();

    // Aprovações pendentes
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM contas_pagar
        WHERE aprovacao_status = 'pendente'
    ");
    $aprovacoes_pendentes = $stmt->fetchColumn();

    // Metas estouradas
    $stmt = $pdo->query("
        SELECT COUNT(*) as total
        FROM metas_orcamentos
        WHERE status = 'estourado'
        AND mes_ano >= DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH), '%Y-%m-01')
    ");
    $metas_estouradas = $stmt->fetchColumn();

    // Top 5 maiores gastos por departamento (mês atual)
    $stmt = $pdo->query("
        SELECT
            d.nome as departamento,
            d.cor,
            SUM(c.valor) as total_gasto
        FROM contas_pagar c
        INNER JOIN departamentos d ON c.departamento_id = d.id
        WHERE c.status = 'pago'
        AND MONTH(c.data_pagamento) = MONTH(CURRENT_DATE())
        AND YEAR(c.data_pagamento) = YEAR(CURRENT_DATE())
        GROUP BY d.id, d.nome, d.cor
        ORDER BY total_gasto DESC
        LIMIT 5
    ");
    $top_gastos = $stmt->fetchAll();

} catch(PDOException $e) {
    logSeguranca('error', 'Erro no dashboard admin: ' . $e->getMessage());
}
?>

<div class="admin-dashboard">
    <!-- Cards de Resumo -->
    <div class="stats-grid-admin">
        <div class="stat-card-admin warning">
            <div class="stat-icon-admin">⏳</div>
            <div class="stat-info-admin">
                <h4>Aprovações Pendentes</h4>
                <p class="stat-number-admin"><?php echo $aprovacoes_pendentes ?? 0; ?></p>
                <a href="?acao=aprovacoes" class="stat-link">Ver todas →</a>
            </div>
        </div>

        <div class="stat-card-admin success">
            <div class="stat-icon-admin">👥</div>
            <div class="stat-info-admin">
                <h4>Usuários Ativos</h4>
                <p class="stat-number-admin"><?php echo $stats['usuarios'] ?? 0; ?></p>
                <a href="?acao=usuarios" class="stat-link">Gerenciar →</a>
            </div>
        </div>

        <div class="stat-card-admin info">
            <div class="stat-icon-admin">🏢</div>
            <div class="stat-info-admin">
                <h4>Departamentos</h4>
                <p class="stat-number-admin"><?php echo $stats['departamentos'] ?? 0; ?></p>
                <a href="?acao=departamentos" class="stat-link">Gerenciar →</a>
            </div>
        </div>

        <div class="stat-card-admin danger">
            <div class="stat-icon-admin">⚠️</div>
            <div class="stat-info-admin">
                <h4>Metas Estouradas</h4>
                <p class="stat-number-admin"><?php echo $metas_estouradas ?? 0; ?></p>
                <a href="?acao=metas" class="stat-link">Ver detalhes →</a>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="charts-grid-admin">
        <!-- Gráfico: Usuários por Role -->
        <div class="chart-box-admin">
            <h3>Usuários por Função</h3>
            <canvas id="chartRoles"></canvas>
        </div>

        <!-- Gráfico: Contas por Status -->
        <div class="chart-box-admin">
            <h3>Contas por Status</h3>
            <canvas id="chartStatus"></canvas>
        </div>

        <!-- Gráfico: Top 5 Gastos por Departamento -->
        <div class="chart-box-admin">
            <h3>Top 5 Gastos por Departamento (Mês Atual)</h3>
            <canvas id="chartTopGastos"></canvas>
        </div>
    </div>

    <!-- Tabela de Departamentos -->
    <div class="table-section">
        <h3>Visão Geral dos Departamentos</h3>
        <div class="table-container">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Departamento</th>
                        <th>Usuários</th>
                        <th>Orçamento Mensal</th>
                        <th>Gasto Atual</th>
                        <th>% Utilizado</th>
                        <th>Pendente</th>
                        <th>Vencido</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats_departamentos as $dept): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($dept['departamento']); ?></strong>
                            </td>
                            <td><?php echo $dept['total_usuarios']; ?></td>
                            <td>R$ <?php echo number_format($dept['orcamento_mensal'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($dept['valor_pago'], 2, ',', '.'); ?></td>
                            <td>
                                <?php
                                $percentual = $dept['percentual_gasto'] ?? 0;
                                $cor = $percentual > 100 ? 'red' : ($percentual > 80 ? 'orange' : 'green');
                                ?>
                                <span style="color: <?php echo $cor; ?>; font-weight: bold;">
                                    <?php echo number_format($percentual, 1); ?>%
                                </span>
                            </td>
                            <td>R$ <?php echo number_format($dept['valor_pendente'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($dept['valor_vencido'], 2, ',', '.'); ?></td>
                            <td>
                                <?php if ($percentual > 100): ?>
                                    <span class="badge-status-meta estourado">Estourado</span>
                                <?php elseif ($percentual > 80): ?>
                                    <span class="badge-status-meta atencao">Atenção</span>
                                <?php else: ?>
                                    <span class="badge-status-meta ok">OK</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ações Recentes -->
    <div class="acoes-recentes-section">
        <h3>📋 Últimas Ações no Sistema</h3>
        <?php if (!empty($acoes_recentes)): ?>
            <div class="acoes-list">
                <?php foreach ($acoes_recentes as $acao): ?>
                    <div class="acao-item">
                        <div class="acao-icon">📌</div>
                        <div class="acao-content">
                            <strong><?php echo htmlspecialchars($acao['usuario_nome'] ?? 'Sistema'); ?></strong>
                            <span><?php echo htmlspecialchars($acao['acao']); ?></span>
                            <small><?php echo date('d/m/Y H:i', strtotime($acao['data_hora'])); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="texto-vazio">Nenhuma ação registrada ainda.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Gráfico de Usuários por Role
const ctxRoles = document.getElementById('chartRoles');
if (ctxRoles) {
    new Chart(ctxRoles, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(function($r) {
                return getNomeRole($r['role']);
            }, $usuarios_por_role)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($usuarios_por_role, 'total')); ?>,
                backgroundColor: ['#FF6384', '#36A2EB', '#4BC0C0'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

// Gráfico de Contas por Status
const ctxStatus = document.getElementById('chartStatus');
if (ctxStatus) {
    new Chart(ctxStatus, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($contas_por_status, 'status')); ?>,
            datasets: [{
                label: 'Quantidade',
                data: <?php echo json_encode(array_column($contas_por_status, 'total')); ?>,
                backgroundColor: ['#FF9F40', '#4BC0C0', '#FF6384'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });
}

// Gráfico Top 5 Gastos
const ctxTop = document.getElementById('chartTopGastos');
if (ctxTop) {
    new Chart(ctxTop, {
        type: 'horizontalBar',
        data: {
            labels: <?php echo json_encode(array_column($top_gastos, 'departamento')); ?>,
            datasets: [{
                label: 'Gasto (R$)',
                data: <?php echo json_encode(array_column($top_gastos, 'total_gasto')); ?>,
                backgroundColor: <?php echo json_encode(array_column($top_gastos, 'cor')); ?>,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                x: { beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toFixed(0);
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php
// Definir constante para indicar que esta é uma página admin
define('ADMIN_PAGE', true);
?>
