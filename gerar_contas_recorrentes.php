<?php
/**
 * Script para gerar contas recorrentes automaticamente
 *
 * Este script deve ser executado uma vez por mês (idealmente no dia 1º)
 * via CRON JOB ou manualmente pelo usuário admin
 *
 * Exemplo de CRON (rodar todo dia 1º às 00:00):
 * 0 0 1 * * /usr/bin/php /caminho/para/gerar_contas_recorrentes.php
 */

require_once 'config.php';

// Definir mês de referência (próximo mês por padrão)
$mes_referencia = isset($argv[1]) ? $argv[1] : date('Y-m', strtotime('+1 month'));

echo "====================================\n";
echo "GERADOR DE CONTAS RECORRENTES\n";
echo "====================================\n";
echo "Mês de referência: $mes_referencia\n";
echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
echo "====================================\n\n";

try {
    // Buscar todas as contas recorrentes ativas
    $stmt = $pdo->prepare("
        SELECT *
        FROM contas_pagar
        WHERE recorrente = TRUE
          AND (data_fim_recorrencia IS NULL OR data_fim_recorrencia >= CURRENT_DATE())
          AND id NOT IN (
              SELECT conta_recorrente_origem_id
              FROM log_contas_geradas
              WHERE mes_referencia = ?
          )
    ");
    $stmt->execute([$mes_referencia]);
    $contas_recorrentes = $stmt->fetchAll();

    $total_contas = count($contas_recorrentes);
    $contas_geradas = 0;
    $contas_erro = 0;

    echo "Total de contas recorrentes encontradas: $total_contas\n\n";

    if ($total_contas == 0) {
        echo "✓ Nenhuma conta para gerar neste mês.\n";
        exit(0);
    }

    // Processar cada conta recorrente
    foreach ($contas_recorrentes as $conta) {
        echo "Processando: " . $conta['descricao'] . " (ID: {$conta['id']})\n";

        try {
            // Calcular data de vencimento
            $dia = $conta['dia_vencimento_recorrente'];
            $ano_mes = $mes_referencia; // Formato: YYYY-MM

            // Validar se o dia é válido para o mês
            $ultimo_dia_mes = date('t', strtotime($ano_mes . '-01'));
            if ($dia > $ultimo_dia_mes) {
                $dia = $ultimo_dia_mes; // Ajustar para último dia do mês
            }

            $data_vencimento = $ano_mes . '-' . str_pad($dia, 2, '0', STR_PAD_LEFT);

            // Criar descrição com mês/ano
            $descricao_nova = $conta['descricao'] . ' (' . date('m/Y', strtotime($data_vencimento)) . ')';

            // Inserir nova conta
            $stmt = $pdo->prepare("
                INSERT INTO contas_pagar (
                    usuario_id, categoria_id, descricao, valor, data_vencimento,
                    observacoes, status, gerada_automaticamente, conta_recorrente_origem_id
                ) VALUES (?, ?, ?, ?, ?, ?, 'pendente', TRUE, ?)
            ");
            $stmt->execute([
                $conta['usuario_id'],
                $conta['categoria_id'],
                $descricao_nova,
                $conta['valor'],
                $data_vencimento,
                $conta['observacoes'],
                $conta['id']
            ]);

            $nova_conta_id = $pdo->lastInsertId();

            // Registrar no log
            $stmt = $pdo->prepare("
                INSERT INTO log_contas_geradas (conta_recorrente_id, conta_gerada_id, mes_referencia)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$conta['id'], $nova_conta_id, $mes_referencia]);

            echo "  ✓ Conta gerada com sucesso! Nova ID: $nova_conta_id\n";
            echo "    Vencimento: " . date('d/m/Y', strtotime($data_vencimento)) . "\n";
            echo "    Valor: R$ " . number_format($conta['valor'], 2, ',', '.') . "\n\n";

            $contas_geradas++;

        } catch (PDOException $e) {
            echo "  ✗ Erro ao gerar conta: " . $e->getMessage() . "\n\n";
            $contas_erro++;
        }
    }

    echo "====================================\n";
    echo "RESUMO\n";
    echo "====================================\n";
    echo "Total processadas: $total_contas\n";
    echo "Contas geradas: $contas_geradas\n";
    echo "Erros: $contas_erro\n";
    echo "====================================\n";

} catch (PDOException $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}
?>
