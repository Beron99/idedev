<?php
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (empty($email) || empty($senha)) {
        $mensagem = 'Preencha todos os campos!';
        $tipo_mensagem = 'erro';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                header('Location: painel.php');
                exit;
            } else {
                $mensagem = 'Email ou senha incorretos!';
                $tipo_mensagem = 'erro';
            }
        } catch(PDOException $e) {
            $mensagem = 'Erro ao fazer login: ' . $e->getMessage();
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
    <title>Login - Sistema</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-box">
            <h1>Login</h1>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>

                <button type="submit" class="btn">Entrar</button>
            </form>

            <p class="link-texto">Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
        </div>
    </div>
</body>
</html>
