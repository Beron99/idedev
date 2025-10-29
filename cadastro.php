<?php
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
        $mensagem = 'Todos os campos são obrigatórios!';
        $tipo_mensagem = 'erro';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Email inválido!';
        $tipo_mensagem = 'erro';
    } elseif ($senha !== $confirma_senha) {
        $mensagem = 'As senhas não coincidem!';
        $tipo_mensagem = 'erro';
    } elseif (strlen($senha) < 6) {
        $mensagem = 'A senha deve ter no mínimo 6 caracteres!';
        $tipo_mensagem = 'erro';
    } else {
        try {
            // Verificar se o email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $mensagem = 'Este email já está cadastrado!';
                $tipo_mensagem = 'erro';
            } else {
                // Inserir novo usuário
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash]);

                $mensagem = 'Cadastro realizado com sucesso! Redirecionando para o login...';
                $tipo_mensagem = 'sucesso';
                header("refresh:2;url=login.php");
            }
        } catch(PDOException $e) {
            $mensagem = 'Erro ao cadastrar: ' . $e->getMessage();
            $tipo_mensagem = 'erro';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Cadastro</h1>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>

                <div class="form-group">
                    <label for="confirma_senha">Confirmar Senha:</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" required>
                </div>

                <button type="submit" class="btn">Cadastrar</button>
            </form>

            <p class="link-texto">Já tem uma conta? <a href="login.php">Fazer login</a></p>
        </div>
    </div>
</body>
</html>
