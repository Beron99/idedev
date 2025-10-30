<?php
session_start();
require_once 'config.php';
require_once 'permissions.php';

// Apenas admins podem gerenciar usuários
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
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $senha = $_POST['senha'] ?? '';
            $role = $_POST['role'] ?? 'usuario';
            $departamento_id = (int)($_POST['departamento_id'] ?? 0);
            $telefone = limparEntrada($_POST['telefone'] ?? '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if (!$nome || !$email) {
                throw new Exception('Nome e email são obrigatórios');
            }

            if (!in_array($role, ['admin', 'gestor', 'usuario'])) {
                throw new Exception('Função inválida');
            }

            // Validar senha forte
            $validacao_senha = validarSenhaForte($senha);
            if (!$validacao_senha['valida']) {
                throw new Exception('Senha inválida: ' . implode(', ', $validacao_senha['erros']));
            }

            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Email já cadastrado');
            }

            // Criar usuário
            $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, role, departamento_id, telefone, ativo, data_cadastro)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $nome,
                $email,
                $senha_hash,
                $role,
                $departamento_id ?: null,
                $telefone ?: null,
                $ativo
            ]);

            $novo_id = $pdo->lastInsertId();

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                'criar_usuario',
                'usuarios',
                $novo_id,
                null,
                json_encode(['nome' => $nome, 'email' => $email, 'role' => $role])
            );

            echo json_encode([
                'success' => true,
                'message' => 'Usuário criado com sucesso!'
            ]);
            break;

        case 'editar':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            $usuario_id = (int)($_POST['usuario_id'] ?? 0);
            $nome = limparEntrada($_POST['nome'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $role = $_POST['role'] ?? 'usuario';
            $departamento_id = (int)($_POST['departamento_id'] ?? 0);
            $telefone = limparEntrada($_POST['telefone'] ?? '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if (!$usuario_id || !$nome || !$email) {
                throw new Exception('Dados inválidos');
            }

            if (!in_array($role, ['admin', 'gestor', 'usuario'])) {
                throw new Exception('Função inválida');
            }

            // Verificar se email já existe em outro usuário
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $usuario_id]);
            if ($stmt->fetch()) {
                throw new Exception('Email já cadastrado para outro usuário');
            }

            // Buscar dados antigos para auditoria
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $dados_antigos = $stmt->fetch();

            // Atualizar usuário
            $stmt = $pdo->prepare("
                UPDATE usuarios
                SET nome = ?,
                    email = ?,
                    role = ?,
                    departamento_id = ?,
                    telefone = ?,
                    ativo = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nome,
                $email,
                $role,
                $departamento_id ?: null,
                $telefone ?: null,
                $ativo,
                $usuario_id
            ]);

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                'editar_usuario',
                'usuarios',
                $usuario_id,
                json_encode($dados_antigos),
                json_encode(['nome' => $nome, 'email' => $email, 'role' => $role, 'ativo' => $ativo])
            );

            echo json_encode([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso!'
            ]);
            break;

        case 'buscar':
            $usuario_id = (int)($_GET['id'] ?? 0);

            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }

            echo json_encode([
                'success' => true,
                'usuario' => $usuario
            ]);
            break;

        case 'resetar_senha':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            $usuario_id = (int)($_POST['usuario_id'] ?? 0);

            if (!$usuario_id) {
                throw new Exception('Usuário inválido');
            }

            // Gerar senha temporária
            $senha_temporaria = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 2) .
                               substr(str_shuffle('abcdefghjkmnpqrstuvwxyz'), 0, 4) .
                               substr(str_shuffle('123456789'), 0, 2) .
                               substr(str_shuffle('!@#$%'), 0, 1);

            $senha_hash = password_hash($senha_temporaria, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
            $stmt->execute([$senha_hash, $usuario_id]);

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                'resetar_senha',
                'usuarios',
                $usuario_id,
                null,
                null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Senha resetada com sucesso',
                'nova_senha' => $senha_temporaria
            ]);
            break;

        case 'toggle_ativo':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            $usuario_id = (int)($_POST['usuario_id'] ?? 0);
            $ativo = $_POST['ativo'] === 'true' ? 1 : 0;

            if (!$usuario_id) {
                throw new Exception('Usuário inválido');
            }

            // Não permitir desativar a si mesmo
            if ($usuario_id == $_SESSION['usuario_id'] && !$ativo) {
                throw new Exception('Você não pode desativar sua própria conta');
            }

            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
            $stmt->execute([$ativo, $usuario_id]);

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                $ativo ? 'ativar_usuario' : 'desativar_usuario',
                'usuarios',
                $usuario_id,
                null,
                json_encode(['ativo' => $ativo])
            );

            echo json_encode([
                'success' => true,
                'message' => $ativo ? 'Usuário ativado' : 'Usuário desativado'
            ]);
            break;

        case 'excluir':
            // Validar CSRF
            if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de segurança inválido');
            }

            $usuario_id = (int)($_POST['usuario_id'] ?? 0);

            if (!$usuario_id) {
                throw new Exception('Usuário inválido');
            }

            // Não permitir excluir a si mesmo
            if ($usuario_id == $_SESSION['usuario_id']) {
                throw new Exception('Você não pode excluir sua própria conta');
            }

            // Buscar dados do usuário para auditoria
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }

            // Verificar se tem contas associadas
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contas_pagar WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $tem_contas = $stmt->fetchColumn() > 0;

            if ($tem_contas) {
                throw new Exception('Não é possível excluir: usuário possui contas registradas');
            }

            // Excluir usuário
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);

            // Log de auditoria
            registrarAuditoria(
                $_SESSION['usuario_id'],
                'excluir_usuario',
                'usuarios',
                $usuario_id,
                json_encode($usuario),
                null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Usuário excluído com sucesso'
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
