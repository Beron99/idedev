<?php
require_once 'config.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

// Buscar todos os usuários cadastrados
try {
    $stmt = $pdo->query("SELECT id, nome, email, data_cadastro FROM usuarios ORDER BY data_cadastro DESC");
    $usuarios = $stmt->fetchAll();
} catch(PDOException $e) {
    $erro = 'Erro ao buscar usuários: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Sistema</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-painel">
        <div class="header-painel">
            <h1>Painel Administrativo</h1>
            <div class="user-info">
                <span>Bem-vindo, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>!</span>
                <a href="logout.php" class="btn-logout">Sair</a>
            </div>
        </div>

        <div class="content-painel">
            <h2>Usuários Cadastrados</h2>

            <?php if (isset($erro)): ?>
                <div class="mensagem erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table class="table-usuarios">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Data de Cadastro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($usuarios) > 0): ?>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?php echo $usuario['id']; ?></td>
                                    <td><?php echo htmlspecialchars($usuario['nome']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Nenhum usuário cadastrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="stats">
                <div class="stat-box">
                    <h3>Total de Usuários</h3>
                    <p class="stat-number"><?php echo count($usuarios); ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
