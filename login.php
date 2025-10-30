<?php
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        logSeguranca('warning', 'Tentativa de login com token CSRF inválido');
        $mensagem = 'Token de segurança inválido. Recarregue a página e tente novamente.';
        $tipo_mensagem = 'erro';
    } else {
        $email = limparEntrada($_POST['email'], 'email');
        $senha = $_POST['senha'];

        if (empty($email) || empty($senha)) {
            $mensagem = 'Preencha todos os campos!';
            $tipo_mensagem = 'erro';
        } else {
            // Verificar rate limiting
            $chave_rate_limit = 'login_' . $_SERVER['REMOTE_ADDR'];

            if (estaRateLimitBloqueado($chave_rate_limit)) {
                $tempo_restante = tempoRestanteBloqueio($chave_rate_limit);
                logSeguranca('warning', "Tentativa de login bloqueada por rate limit - Email: $email");
                $mensagem = "Muitas tentativas de login. Tente novamente em " . ceil($tempo_restante / 60) . " minutos.";
                $tipo_mensagem = 'erro';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
                    $stmt->execute([$email]);
                    $usuario = $stmt->fetch();

                    if ($usuario && password_verify($senha, $usuario['senha'])) {
                        // Login bem-sucedido - regenerar sessão
                        session_regenerate_id(true);

                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nome'] = $usuario['nome'];
                        $_SESSION['usuario_email'] = $usuario['email'];
                        $_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
                        $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

                        // Limpar rate limit em caso de sucesso
                        unset($_SESSION['rate_limit'][$chave_rate_limit]);

                        logSeguranca('info', 'Login bem-sucedido', $usuario['id']);

                        header('Location: dashboard.php');
                        exit;
                    } else {
                        // Registrar tentativa falha
                        verificarRateLimit($chave_rate_limit);

                        logSeguranca('warning', "Tentativa de login falha - Email: $email");

                        $mensagem = 'Email ou senha incorretos!';
                        $tipo_mensagem = 'erro';
                    }
                } catch(PDOException $e) {
                    logSeguranca('error', 'Erro no login: ' . $e->getMessage());
                    $mensagem = 'Erro ao fazer login. Tente novamente.';
                    $tipo_mensagem = 'erro';
                }
            }
        }
    }
}

// Gerar token CSRF
$csrf_token = gerarTokenCSRF();
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
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

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
