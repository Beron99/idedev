<?php
require_once 'config.php';
require_once 'permissions.php';

// Somente admins
verificarAdmin();

$mensagem = '';
$tipo_mensagem = '';
$acao_atual = $_GET['acao'] ?? 'dashboard';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $mensagem = 'Token de segurança inválido!';
        $tipo_mensagem = 'erro';
    } else {
        $acao = $_POST['acao'] ?? '';

        try {
            // GERENCIAR USUÁRIOS
            if ($acao == 'criar_usuario') {
                $nome = limparEntrada($_POST['nome']);
                $email = limparEntrada($_POST['email'], 'email');
                $senha = $_POST['senha'];
                $role = $_POST['role'];
                $departamento_id = intval($_POST['departamento_id']) ?: null;
                $telefone = limparEntrada($_POST['telefone']);

                // Validar senha forte
                $validacao = validarSenhaForte($senha);
                if (!$validacao['valida']) {
                    throw new Exception(implode('<br>', $validacao['erros']));
                }

                // Verificar se email já existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    throw new Exception('Email já cadastrado!');
                }

                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO usuarios (nome, email, senha, role, departamento_id, telefone, ativo)
                    VALUES (?, ?, ?, ?, ?, ?, TRUE)
                ");
                $stmt->execute([$nome, $email, $senha_hash, $role, $departamento_id, $telefone]);

                registrarAuditoria($pdo, 'Criou usuário: ' . $nome, 'usuarios', $pdo->lastInsertId());
                logSeguranca('info', "Admin criou usuário: $email", $_SESSION['usuario_id']);

                $mensagem = 'Usuário criado com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'editar_usuario') {
                $id = intval($_POST['id']);
                $nome = limparEntrada($_POST['nome']);
                $email = limparEntrada($_POST['email'], 'email');
                $role = $_POST['role'];
                $departamento_id = intval($_POST['departamento_id']) ?: null;
                $telefone = limparEntrada($_POST['telefone']);
                $ativo = isset($_POST['ativo']) ? 1 : 0;

                $stmt = $pdo->prepare("
                    UPDATE usuarios
                    SET nome = ?, email = ?, role = ?, departamento_id = ?, telefone = ?, ativo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $email, $role, $departamento_id, $telefone, $ativo, $id]);

                registrarAuditoria($pdo, 'Editou usuário: ' . $nome, 'usuarios', $id);

                $mensagem = 'Usuário atualizado com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'resetar_senha') {
                $id = intval($_POST['id']);
                $nova_senha = 'Senha@123'; // Senha padrão
                $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$senha_hash, $id]);

                registrarAuditoria($pdo, 'Resetou senha do usuário ID: ' . $id, 'usuarios', $id);

                $mensagem = "Senha resetada para: <strong>$nova_senha</strong>";
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'excluir_usuario') {
                $id = intval($_POST['id']);

                // Não pode excluir a si mesmo
                if ($id == $_SESSION['usuario_id']) {
                    throw new Exception('Você não pode excluir sua própria conta!');
                }

                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);

                registrarAuditoria($pdo, 'Excluiu usuário ID: ' . $id, 'usuarios', $id);

                $mensagem = 'Usuário excluído com sucesso!';
                $tipo_mensagem = 'sucesso';

            }
            // GERENCIAR DEPARTAMENTOS
            elseif ($acao == 'criar_departamento') {
                $nome = limparEntrada($_POST['nome']);
                $descricao = limparEntrada($_POST['descricao']);
                $cor = $_POST['cor'];
                $orcamento = floatval(str_replace(',', '.', str_replace('.', '', $_POST['orcamento'])));

                $stmt = $pdo->prepare("
                    INSERT INTO departamentos (nome, descricao, cor, orcamento_mensal)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$nome, $descricao, $cor, $orcamento]);

                registrarAuditoria($pdo, 'Criou departamento: ' . $nome, 'departamentos', $pdo->lastInsertId());

                $mensagem = 'Departamento criado com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'editar_departamento') {
                $id = intval($_POST['id']);
                $nome = limparEntrada($_POST['nome']);
                $descricao = limparEntrada($_POST['descricao']);
                $cor = $_POST['cor'];
                $orcamento = floatval(str_replace(',', '.', str_replace('.', '', $_POST['orcamento'])));
                $ativo = isset($_POST['ativo']) ? 1 : 0;

                $stmt = $pdo->prepare("
                    UPDATE departamentos
                    SET nome = ?, descricao = ?, cor = ?, orcamento_mensal = ?, ativo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nome, $descricao, $cor, $orcamento, $ativo, $id]);

                registrarAuditoria($pdo, 'Editou departamento: ' . $nome, 'departamentos', $id);

                $mensagem = 'Departamento atualizado com sucesso!';
                $tipo_mensagem = 'sucesso';
            }

        } catch(PDOException $e) {
            logSeguranca('error', 'Erro no admin: ' . $e->getMessage(), $_SESSION['usuario_id']);
            $mensagem = 'Erro ao processar ação.';
            $tipo_mensagem = 'erro';
        } catch(Exception $e) {
            $mensagem = $e->getMessage();
            $tipo_mensagem = 'erro';
        }
    }
}

// Buscar dados para dashboard
$stats = [];
try {
    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = TRUE");
    $stats['usuarios'] = $stmt->fetchColumn();

    // Total de departamentos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM departamentos WHERE ativo = TRUE");
    $stats['departamentos'] = $stmt->fetchColumn();

    // Total de contas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM contas_pagar");
    $stats['contas'] = $stmt->fetchColumn();

    // Total de categorias
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
    $stats['categorias'] = $stmt->fetchColumn();

    // Ações recentes (auditoria)
    $stmt = $pdo->query("
        SELECT a.*, u.nome as usuario_nome
        FROM auditoria a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        ORDER BY a.data_hora DESC
        LIMIT 10
    ");
    $acoes_recentes = $stmt->fetchAll();

} catch(PDOException $e) {
    logSeguranca('error', 'Erro ao buscar stats: ' . $e->getMessage());
}

$csrf_token = gerarTokenCSRF();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração - Sistema</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-painel">
        <div class="header-painel">
            <h1>⚙️ Painel Administrativo</h1>
            <div class="user-info">
                <span class="badge-admin">ADMIN</span>
                <span><strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong></span>
                <a href="dashboard.php" class="btn-voltar">← Voltar</a>
                <a href="logout.php" class="btn-logout">Sair</a>
            </div>
        </div>

        <div class="content-painel">
            <!-- Menu Admin -->
            <div class="menu-admin">
                <a href="?acao=dashboard" class="menu-admin-item <?php echo $acao_atual == 'dashboard' ? 'active' : ''; ?>">
                    Dashboard
                </a>
                <a href="?acao=usuarios" class="menu-admin-item <?php echo $acao_atual == 'usuarios' ? 'active' : ''; ?>">
                    Usuários
                </a>
                <a href="?acao=departamentos" class="menu-admin-item <?php echo $acao_atual == 'departamentos' ? 'active' : ''; ?>">
                    Departamentos
                </a>
                <a href="?acao=auditoria" class="menu-admin-item <?php echo $acao_atual == 'auditoria' ? 'active' : ''; ?>">
                    Auditoria
                </a>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <?php
            // Incluir página específica baseada na ação
            $pagina_acao = match($acao_atual) {
                'usuarios' => 'admin_usuarios.php',
                'departamentos' => 'admin_departamentos.php',
                'auditoria' => 'admin_auditoria.php',
                default => 'admin_dashboard.php'
            };

            if (file_exists($pagina_acao)) {
                include $pagina_acao;
            } else {
                // Dashboard padrão
                include 'admin_dashboard.php';
            }
            ?>
        </div>
    </div>
</body>
</html>
