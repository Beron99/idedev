<?php
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = '';

// Gerar token CSRF antes de processar o formulário
$csrf_token = gerarTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar token CSRF (desabilitado temporariamente)
    // if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
    //     logSeguranca('warning', 'Tentativa de cadastro com token CSRF inválido');
    //     $mensagem = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    //     $tipo_mensagem = 'erro';
    // } else {
        $nome = limparEntrada($_POST['nome']);
        $email = limparEntrada($_POST['email'], 'email');
        $senha = $_POST['senha'];
        $confirma_senha = $_POST['confirma_senha'];

        // Validações
        if (empty($nome) || empty($email) || empty($senha) || empty($confirma_senha)) {
            $mensagem = 'Todos os campos são obrigatórios!';
            $tipo_mensagem = 'erro';
        } elseif (!validarEmail($email)) {
            $mensagem = 'Email inválido!';
            $tipo_mensagem = 'erro';
        } elseif ($senha !== $confirma_senha) {
            $mensagem = 'As senhas não coincidem!';
            $tipo_mensagem = 'erro';
        } else {
            // Validar senha forte
            $validacao_senha = validarSenhaForte($senha);
            if (!$validacao_senha['valida']) {
                $mensagem = implode('<br>', $validacao_senha['erros']);
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

                    logSeguranca('info', "Novo usuário cadastrado - Email: $email", $pdo->lastInsertId());

                    $mensagem = 'Cadastro realizado com sucesso! Redirecionando para o login...';
                    $tipo_mensagem = 'sucesso';
                    header("refresh:2;url=login.php");
                }
            } catch(PDOException $e) {
                logSeguranca('error', 'Erro no cadastro: ' . $e->getMessage());
                $mensagem = 'Erro ao cadastrar. Tente novamente.';
                $tipo_mensagem = 'erro';
            }
            }
        }
    // }  // Fim do else do CSRF (comentado)
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
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

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
                    <div class="password-wrapper">
                        <input type="password" id="senha" name="senha" required>
                        <span class="toggle-password" onclick="togglePassword('senha')">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirma_senha">Confirmar Senha:</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirma_senha" name="confirma_senha" required>
                        <span class="toggle-password" onclick="togglePassword('confirma_senha')">
                            <svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn">Cadastrar</button>
            </form>

            <p class="link-texto">Já tem uma conta? <a href="login.php">Fazer login</a></p>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('.eye-icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    </script>
</body>
</html>
