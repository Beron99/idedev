<?php
session_start();
require_once 'config.php';
require_once 'permissions.php';

// Apenas admins podem gerenciar departamentos
verificarAdmin();

header('Content-Type: application/json');

$acao = $_REQUEST['acao'] ?? '';

try {
    switch ($acao) {
        case 'criar':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            // Validar campos
            $nome = limparEntrada($_POST['nome'] ?? '');
            $descricao = limparEntrada($_POST['descricao'] ?? '');
            $cor = $_POST['cor'] ?? '#3498db';
            $orcamento_mensal = (float)($_POST['orcamento_mensal'] ?? 0);
            $gestor_nome = limparEntrada($_POST['gestor_nome'] ?? '');

            if (!$nome) {
                throw new Exception('Nome é obrigatório');
            }

            if ($orcamento_mensal < 0) {
                throw new Exception('Orçamento deve ser um valor positivo');
            }

            // Validar formato de cor
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
                throw new Exception('Cor inválida');
            }

            // Verificar se nome já existe
            $stmt = $pdo->prepare("SELECT id FROM departamentos WHERE nome = ?");
            $stmt->execute([$nome]);
            if ($stmt->fetch()) {
                throw new Exception('Já existe um departamento com este nome');
            }

            // Criar departamento
            $stmt = $pdo->prepare("
                INSERT INTO departamentos (nome, descricao, cor, orcamento_mensal, gestor_nome, data_criacao)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $nome,
                $descricao ?: null,
                $cor,
                $orcamento_mensal,
                $gestor_nome ?: null
            ]);

            $novo_id = $pdo->lastInsertId();

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                'criar_departamento',
                'departamentos',
                $novo_id,
                null,
                json_encode(['nome' => $nome, 'orcamento' => $orcamento_mensal])
            );

            echo json_encode([
                'success' => true,
                'message' => 'Departamento criado com sucesso!'
            ]);
            break;

        case 'editar':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            $departamento_id = (int)($_POST['departamento_id'] ?? 0);
            $nome = limparEntrada($_POST['nome'] ?? '');
            $descricao = limparEntrada($_POST['descricao'] ?? '');
            $cor = $_POST['cor'] ?? '#3498db';
            $orcamento_mensal = (float)($_POST['orcamento_mensal'] ?? 0);
            $gestor_nome = limparEntrada($_POST['gestor_nome'] ?? '');

            if (!$departamento_id || !$nome) {
                throw new Exception('Dados inválidos');
            }

            if ($orcamento_mensal < 0) {
                throw new Exception('Orçamento deve ser um valor positivo');
            }

            // Validar formato de cor
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $cor)) {
                throw new Exception('Cor inválida');
            }

            // Verificar se nome já existe em outro departamento
            $stmt = $pdo->prepare("SELECT id FROM departamentos WHERE nome = ? AND id != ?");
            $stmt->execute([$nome, $departamento_id]);
            if ($stmt->fetch()) {
                throw new Exception('Já existe outro departamento com este nome');
            }

            // Buscar dados antigos para auditoria
            $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE id = ?");
            $stmt->execute([$departamento_id]);
            $dados_antigos = $stmt->fetch();

            if (!$dados_antigos) {
                throw new Exception('Departamento não encontrado');
            }

            // Atualizar departamento
            $stmt = $pdo->prepare("
                UPDATE departamentos
                SET nome = ?,
                    descricao = ?,
                    cor = ?,
                    orcamento_mensal = ?,
                    gestor_nome = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nome,
                $descricao ?: null,
                $cor,
                $orcamento_mensal,
                $gestor_nome ?: null,
                $departamento_id
            ]);

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                'editar_departamento',
                'departamentos',
                $departamento_id,
                json_encode($dados_antigos),
                json_encode(['nome' => $nome, 'orcamento' => $orcamento_mensal])
            );

            echo json_encode([
                'success' => true,
                'message' => 'Departamento atualizado com sucesso!'
            ]);
            break;

        case 'buscar':
            $departamento_id = (int)($_GET['id'] ?? 0);

            $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE id = ?");
            $stmt->execute([$departamento_id]);
            $departamento = $stmt->fetch();

            if (!$departamento) {
                throw new Exception('Departamento não encontrado');
            }

            echo json_encode([
                'success' => true,
                'departamento' => $departamento
            ]);
            break;

        case 'excluir':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            $departamento_id = (int)($_POST['departamento_id'] ?? 0);

            if (!$departamento_id) {
                throw new Exception('Departamento inválido');
            }

            // Buscar dados do departamento para auditoria
            $stmt = $pdo->prepare("SELECT * FROM departamentos WHERE id = ?");
            $stmt->execute([$departamento_id]);
            $departamento = $stmt->fetch();

            if (!$departamento) {
                throw new Exception('Departamento não encontrado');
            }

            // Contar usuários e contas associadas
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE departamento_id = ?");
            $stmt->execute([$departamento_id]);
            $total_usuarios = $stmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contas_pagar WHERE departamento_id = ?");
            $stmt->execute([$departamento_id]);
            $total_contas = $stmt->fetchColumn();

            // Remover associação de usuários e contas (não exclui)
            if ($total_usuarios > 0) {
                $stmt = $pdo->prepare("UPDATE usuarios SET departamento_id = NULL WHERE departamento_id = ?");
                $stmt->execute([$departamento_id]);
            }

            if ($total_contas > 0) {
                $stmt = $pdo->prepare("UPDATE contas_pagar SET departamento_id = NULL WHERE departamento_id = ?");
                $stmt->execute([$departamento_id]);
            }

            // Excluir departamento
            $stmt = $pdo->prepare("DELETE FROM departamentos WHERE id = ?");
            $stmt->execute([$departamento_id]);

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                'excluir_departamento',
                'departamentos',
                $departamento_id,
                json_encode($departamento),
                json_encode(['usuarios_afetados' => $total_usuarios, 'contas_afetadas' => $total_contas])
            );

            echo json_encode([
                'success' => true,
                'message' => 'Departamento excluído com sucesso'
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
