<?php
require_once 'config.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    echo '<p style="text-align: center; color: #e74c3c;">Acesso negado.</p>';
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$cliente_id = intval($_GET['cliente_id'] ?? 0);

if ($cliente_id == 0) {
    echo '<p style="text-align: center; color: #e74c3c;">Cliente inválido.</p>';
    exit;
}

try {
    // Buscar dados do cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$cliente_id, $usuario_id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        echo '<p style="text-align: center; color: #e74c3c;">Cliente não encontrado.</p>';
        exit;
    }

    // Buscar contas a receber do cliente
    $stmt = $pdo->prepare("
        SELECT
            cr.*,
            cat.nome as categoria_nome,
            cat.cor as categoria_cor
        FROM contas_receber cr
        LEFT JOIN categorias cat ON cr.categoria_id = cat.id
        WHERE cr.cliente_id = ? AND cr.usuario_id = ?
        ORDER BY cr.data_vencimento DESC
    ");
    $stmt->execute([$cliente_id, $usuario_id]);
    $contas = $stmt->fetchAll();

    // Estatísticas
    $total_contas = count($contas);
    $total_pendente = 0;
    $total_recebido = 0;
    $total_vencido = 0;

    foreach ($contas as $conta) {
        if ($conta['status'] == 'pendente') {
            $total_pendente += $conta['valor'];
        } elseif ($conta['status'] == 'recebido') {
            $total_recebido += $conta['valor'];
        } elseif ($conta['status'] == 'vencido') {
            $total_vencido += $conta['valor'];
        }
    }

    ?>

    <!-- Informações do Cliente -->
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 10px 0; color: #333;"><?php echo htmlspecialchars($cliente['razao_social']); ?></h3>
        <?php if ($cliente['nome_fantasia']): ?>
            <p style="margin: 5px 0; color: #666;"><?php echo htmlspecialchars($cliente['nome_fantasia']); ?></p>
        <?php endif; ?>
        <p style="margin: 5px 0; color: #999; font-size: 13px;">
            <?php
            if ($cliente['tipo_pessoa'] == 'juridica' && $cliente['cnpj']) {
                echo 'CNPJ: ' . htmlspecialchars($cliente['cnpj']);
            } elseif ($cliente['tipo_pessoa'] == 'fisica' && $cliente['cpf']) {
                echo 'CPF: ' . htmlspecialchars($cliente['cpf']);
            }
            ?>
        </p>
    </div>

    <!-- Estatísticas -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px;">
        <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; text-align: center;">
            <p style="margin: 0; font-size: 12px; color: #666;">Pendente</p>
            <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold; color: #667eea;">R$ <?php echo number_format($total_pendente, 2, ',', '.'); ?></p>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #2ecc71; text-align: center;">
            <p style="margin: 0; font-size: 12px; color: #666;">Recebido</p>
            <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold; color: #2ecc71;">R$ <?php echo number_format($total_recebido, 2, ',', '.'); ?></p>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #e74c3c; text-align: center;">
            <p style="margin: 0; font-size: 12px; color: #666;">Vencido</p>
            <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold; color: #e74c3c;">R$ <?php echo number_format($total_vencido, 2, ',', '.'); ?></p>
        </div>
    </div>

    <!-- Lista de Contas -->
    <h4 style="margin: 20px 0 15px 0; color: #333;">Histórico de Contas a Receber (<?php echo $total_contas; ?>)</h4>

    <?php if ($total_contas > 0): ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($contas as $conta): ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid <?php
                    if ($conta['status'] == 'pendente') echo '#f39c12';
                    elseif ($conta['status'] == 'recebido') echo '#2ecc71';
                    elseif ($conta['status'] == 'vencido') echo '#e74c3c';
                ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <div style="flex: 1;">
                            <strong style="color: #333; font-size: 14px;"><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                            <?php if ($conta['categoria_nome']): ?>
                                <span style="display: inline-block; margin-left: 10px; padding: 3px 10px; background: <?php echo $conta['categoria_cor']; ?>; color: white; border-radius: 12px; font-size: 11px;">
                                    <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <span style="font-size: 16px; font-weight: bold; color: <?php
                            if ($conta['status'] == 'pendente') echo '#f39c12';
                            elseif ($conta['status'] == 'recebido') echo '#2ecc71';
                            elseif ($conta['status'] == 'vencido') echo '#e74c3c';
                        ?>;">
                            R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?>
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; font-size: 12px; color: #666;">
                        <div>
                            <strong>Vencimento:</strong><br>
                            <?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?>
                        </div>
                        <?php if ($conta['data_recebimento']): ?>
                            <div>
                                <strong>Recebimento:</strong><br>
                                <?php echo date('d/m/Y', strtotime($conta['data_recebimento'])); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong>Status:</strong><br>
                            <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; <?php
                                if ($conta['status'] == 'pendente') echo 'background: #fff3cd; color: #856404;';
                                elseif ($conta['status'] == 'recebido') echo 'background: #d4edda; color: #155724;';
                                elseif ($conta['status'] == 'vencido') echo 'background: #f8d7da; color: #721c24;';
                            ?>">
                                <?php echo $conta['status']; ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($conta['observacoes']): ?>
                        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #dee2e6;">
                            <small style="color: #999;">
                                <strong>Obs:</strong> <?php echo htmlspecialchars($conta['observacoes']); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; padding: 40px; color: #999;">Nenhuma conta a receber cadastrada para este cliente.</p>
    <?php endif; ?>

    <?php

} catch(PDOException $e) {
    echo '<p style="text-align: center; color: #e74c3c;">Erro ao buscar histórico: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>
