<?php
session_start();
require_once 'config.php';
require_once 'permissions.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? '';

try {
    switch ($acao) {
        case 'atualizar_status':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            $conta_id = (int)($_POST['conta_id'] ?? 0);
            $novo_status = $_POST['status'] ?? '';

            if (!$conta_id) {
                throw new Exception('Conta inválida');
            }

            // Validar status
            $status_validos = ['pendente', 'em_analise', 'aprovado', 'pago'];
            if (!in_array($novo_status, $status_validos)) {
                throw new Exception('Status inválido');
            }

            $usuario_id = $_SESSION['usuario_id'];
            $usuario_role = $_SESSION['usuario_role'];

            // Buscar conta
            $stmt = $pdo->prepare("SELECT * FROM contas_pagar WHERE id = ?");
            $stmt->execute([$conta_id]);
            $conta = $stmt->fetch();

            if (!$conta) {
                throw new Exception('Conta não encontrada');
            }

            // Verificar permissões
            if ($usuario_role === 'usuario' && $conta['usuario_id'] != $usuario_id) {
                throw new Exception('Você não tem permissão para editar esta conta');
            }

            if ($usuario_role === 'gestor') {
                $stmt = $pdo->prepare("
                    SELECT departamento_id FROM usuarios WHERE id = ?
                ");
                $stmt->execute([$usuario_id]);
                $user_dept = $stmt->fetchColumn();

                if ($conta['departamento_id'] != $user_dept) {
                    throw new Exception('Você não tem permissão para editar esta conta');
                }
            }

            $dados_antigos = json_encode($conta);

            // Lógica de atualização baseada no novo status
            switch ($novo_status) {
                case 'pendente':
                    // Volta para pendente
                    $stmt = $pdo->prepare("
                        UPDATE contas_pagar
                        SET status = 'pendente',
                            aprovacao_status = 'pendente'
                        WHERE id = ?
                    ");
                    $stmt->execute([$conta_id]);
                    break;

                case 'em_analise':
                    // Marca como em análise
                    $stmt = $pdo->prepare("
                        UPDATE contas_pagar
                        SET status = 'pendente',
                            aprovacao_status = 'pendente'
                        WHERE id = ?
                    ");
                    $stmt->execute([$conta_id]);
                    break;

                case 'aprovado':
                    // Apenas gestores e admins podem aprovar
                    if (!in_array($usuario_role, ['admin', 'gestor'])) {
                        throw new Exception('Apenas gestores e administradores podem aprovar contas');
                    }

                    $stmt = $pdo->prepare("
                        UPDATE contas_pagar
                        SET status = 'pendente',
                            aprovacao_status = 'aprovado',
                            aprovado_por = ?,
                            data_aprovacao = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$usuario_id, $conta_id]);
                    break;

                case 'pago':
                    // Marca como pago
                    $stmt = $pdo->prepare("
                        UPDATE contas_pagar
                        SET status = 'pago',
                            data_pagamento = CURDATE()
                        WHERE id = ?
                    ");
                    $stmt->execute([$conta_id]);
                    break;
            }

            // Buscar dados novos
            $stmt = $pdo->prepare("SELECT * FROM contas_pagar WHERE id = ?");
            $stmt->execute([$conta_id]);
            $dados_novos = json_encode($stmt->fetch());

            // Log de auditoria
            registrarAuditoria(
                $usuario_id,
                'atualizar_status_kanban',
                'contas_pagar',
                $conta_id,
                $dados_antigos,
                $dados_novos
            );

            echo json_encode([
                'success' => true,
                'message' => 'Status atualizado com sucesso'
            ]);
            break;

        default:
            throw new Exception('Ação inválida');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
