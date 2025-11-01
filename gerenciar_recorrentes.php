<?php
require_once 'config.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = '';
$tipo_mensagem = '';

// Processar geração de contas
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $mensagem = 'Token de segurança inválido!';
        $tipo_mensagem = 'erro';
    } else {
        $acao = $_POST['acao'] ?? '';

        if ($acao == 'gerar_mes') {
            $mes_referencia = $_POST['mes_referencia'];

            try {
                // Buscar contas recorrentes do usuário
                $stmt = $pdo->prepare("
                    SELECT *
                    FROM contas_pagar
                    WHERE usuario_id = ?
                      AND recorrente = TRUE
                      AND (data_fim_recorrencia IS NULL OR data_fim_recorrencia >= CURRENT_DATE())
                      AND id NOT IN (
                          SELECT COALESCE(conta_recorrente_origem_id, 0)
                          FROM contas_pagar
                          WHERE usuario_id = ? AND gerada_automaticamente = TRUE
                            AND DATE_FORMAT(data_vencimento, '%Y-%m') = ?
                      )
                ");
                $stmt->execute([$usuario_id, $usuario_id, $mes_referencia]);
                $contas_recorrentes = $stmt->fetchAll();

                $contas_geradas = 0;

                foreach ($contas_recorrentes as $conta) {
                    $dia = $conta['dia_vencimento_recorrente'];
                    $ano_mes = $mes_referencia;

                    // Validar dia do mês
                    $ultimo_dia_mes = date('t', strtotime($ano_mes . '-01'));
                    if ($dia > $ultimo_dia_mes) {
                        $dia = $ultimo_dia_mes;
                    }

                    $data_vencimento = $ano_mes . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);
                    $descricao_nova = $conta['descricao'] . ' (' . date('m/Y', strtotime($data_vencimento)) . ')';

                    // Inserir nova conta
                    $stmt = $pdo->prepare("
                        INSERT INTO contas_pagar (
                            usuario_id, categoria_id, descricao, valor, data_vencimento,
                            observacoes, status, gerada_automaticamente, conta_recorrente_origem_id
                        ) VALUES (?, ?, ?, ?, ?, ?, 'pendente', TRUE, ?)
                    ");
                    $stmt->execute([
                        $usuario_id,
                        $conta['categoria_id'],
                        $descricao_nova,
                        $conta['valor'],
                        $data_vencimento,
                        $conta['observacoes'],
                        $conta['id']
                    ]);

                    $contas_geradas++;
                }

                if ($contas_geradas > 0) {
                    $mensagem = "✓ $contas_geradas conta(s) gerada(s) com sucesso para " . date('m/Y', strtotime($mes_referencia . '-01'));
                    $tipo_mensagem = 'sucesso';
                } else {
                    $mensagem = "Nenhuma conta para gerar neste mês. Todas as contas recorrentes já foram geradas.";
                    $tipo_mensagem = 'sucesso';
                }

                logSeguranca('info', "Contas recorrentes geradas para $mes_referencia: $contas_geradas", $usuario_id);

            } catch (PDOException $e) {
                $mensagem = 'Erro ao gerar contas: ' . $e->getMessage();
                $tipo_mensagem = 'erro';
            }
        }
    }
}

// Gerar token CSRF
$csrf_token = gerarTokenCSRF();

// Buscar contas recorrentes ativas
try {
    $stmt = $pdo->prepare("
        SELECT
            cp.*,
            cat.nome as categoria_nome,
            cat.cor as categoria_cor,
            COUNT(cpg.id) as total_geradas
        FROM contas_pagar cp
        LEFT JOIN categorias cat ON cp.categoria_id = cat.id
        LEFT JOIN contas_pagar cpg ON cpg.conta_recorrente_origem_id = cp.id AND cpg.gerada_automaticamente = TRUE
        WHERE cp.usuario_id = ?
          AND cp.recorrente = TRUE
          AND (cp.data_fim_recorrencia IS NULL OR cp.data_fim_recorrencia >= CURRENT_DATE())
        GROUP BY cp.id
        ORDER BY cp.descricao
    ");
    $stmt->execute([$usuario_id]);
    $contas_recorrentes = $stmt->fetchAll();
} catch (PDOException $e) {
    $contas_recorrentes = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Contas Recorrentes - Gestão Financeira</title>
    <link rel="stylesheet" href="style.css">
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
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="contas.php" class="nav-item">Contas a Pagar</a>
                <a href="contas_receber.php" class="nav-item">Contas a Receber</a>
                <a href="clientes.php" class="nav-item">Clientes</a>
                <a href="categorias.php" class="nav-item">Categorias</a>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h2>🔄 Gerenciar Contas Recorrentes</h2>
            </div>

            <!-- Gerar Contas para Próximos Meses -->
            <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <h3 style="margin-top: 0;">Gerar Contas do Mês</h3>
                <p style="color: #666; margin-bottom: 20px;">Selecione o mês para gerar automaticamente todas as contas recorrentes:</p>

                <form method="POST" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="acao" value="gerar_mes">

                    <div class="form-group" style="margin: 0; min-width: 200px;">
                        <label for="mes_referencia">Mês/Ano:</label>
                        <input type="month" id="mes_referencia" name="mes_referencia" value="<?php echo date('Y-m', strtotime('+1 month')); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Gerar Contas</button>
                </form>
            </div>

            <!-- Lista de Contas Recorrentes -->
            <h3>Suas Contas Recorrentes Ativas (<?php echo count($contas_recorrentes); ?>)</h3>

            <?php if (count($contas_recorrentes) > 0): ?>
                <div class="categorias-grid">
                    <?php foreach ($contas_recorrentes as $conta): ?>
                        <div class="categoria-card" style="border-left: 4px solid <?php echo $conta['categoria_cor'] ?? '#667eea'; ?>">
                            <div class="categoria-header">
                                <div class="categoria-info">
                                    <h3><?php echo htmlspecialchars($conta['descricao']); ?></h3>
                                    <p style="font-size: 12px; color: #999; margin: 5px 0;">
                                        <?php
                                        $tipo_label = [
                                            'mensal' => 'Mensal',
                                            'bimestral' => 'Bimestral',
                                            'trimestral' => 'Trimestral',
                                            'semestral' => 'Semestral',
                                            'anual' => 'Anual'
                                        ];
                                        echo $tipo_label[$conta['tipo_recorrencia']] ?? 'Mensal';
                                        echo ' - Vence dia ' . $conta['dia_vencimento_recorrente'];
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <?php if ($conta['categoria_nome']): ?>
                                <div style="margin: 10px 0;">
                                    <span class="badge-categoria" style="background-color: <?php echo $conta['categoria_cor']; ?>">
                                        <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="categoria-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Valor</span>
                                    <span class="stat-valor" style="color: #e74c3c;">R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Contas Geradas</span>
                                    <span class="stat-valor"><?php echo $conta['total_geradas']; ?></span>
                                </div>
                            </div>

                            <?php if ($conta['data_fim_recorrencia']): ?>
                                <p style="font-size: 12px; color: #f39c12; margin-top: 10px;">
                                    ⚠️ Termina em: <?php echo date('d/m/Y', strtotime($conta['data_fim_recorrencia'])); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($conta['observacoes']): ?>
                                <p style="font-size: 12px; color: #666; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                                    <strong>Obs:</strong> <?php echo htmlspecialchars($conta['observacoes']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: white; padding: 40px; border-radius: 10px; text-align: center; color: #999;">
                    <p style="font-size: 16px; margin: 0;">Nenhuma conta recorrente cadastrada.</p>
                    <p style="margin: 10px 0 20px 0;">Vá em <strong>Contas a Pagar</strong> e marque "Conta Recorrente" ao criar uma nova conta.</p>
                    <a href="contas.php" class="btn btn-primary">Ir para Contas a Pagar</a>
                </div>
            <?php endif; ?>

            <!-- Informações -->
            <div style="background: #e8f4fd; padding: 20px; border-radius: 10px; margin-top: 30px; border-left: 4px solid #3498db;">
                <h4 style="margin: 0 0 10px 0; color: #2980b9;">ℹ️ Como funciona</h4>
                <ul style="margin: 0; padding-left: 20px; color: #555;">
                    <li style="margin: 5px 0;">Contas recorrentes são modelos que geram automaticamente novas contas todo mês</li>
                    <li style="margin: 5px 0;">Use este painel para gerar manualmente as contas do próximo mês</li>
                    <li style="margin: 5px 0;">Você pode editar uma conta recorrente em "Contas a Pagar"</li>
                    <li style="margin: 5px 0;">As contas geradas aparecem com a tag "RECORRENTE" em Contas a Pagar</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
