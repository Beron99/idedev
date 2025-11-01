<?php
/**
 * RESETAR SENHA DO ADMINISTRADOR
 *
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo para seu servidor
 * 2. Acesse via navegador: http://seusite.com/resetar_senha_admin.php
 * 3. Digite a nova senha
 * 4. Clique em "Resetar Senha"
 * 5. DELETE este arquivo após usar!
 */

// Incluir configuração do banco
require_once 'config.php';

$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    if (empty($nova_senha)) {
        $mensagem = 'Digite uma senha!';
        $tipo_mensagem = 'erro';
    } elseif ($nova_senha !== $confirma_senha) {
        $mensagem = 'As senhas não coincidem!';
        $tipo_mensagem = 'erro';
    } elseif (strlen($nova_senha) < 6) {
        $mensagem = 'A senha deve ter no mínimo 6 caracteres!';
        $tipo_mensagem = 'erro';
    } else {
        try {
            $hash = password_hash($nova_senha, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE email = 'admin@sistema.com'");
            $stmt->execute([$hash]);

            if ($stmt->rowCount() > 0) {
                $mensagem = "✅ Senha alterada com sucesso!<br><br>
                            <strong>Email:</strong> admin@sistema.com<br>
                            <strong>Nova senha:</strong> " . htmlspecialchars($nova_senha) . "<br><br>
                            <strong>⚠️ IMPORTANTE: DELETE este arquivo agora!</strong>";
                $tipo_mensagem = 'sucesso';
            } else {
                $mensagem = 'Usuário admin não encontrado! Execute o database_completo.sql primeiro.';
                $tipo_mensagem = 'erro';
            }
        } catch (Exception $e) {
            $mensagem = 'Erro ao atualizar senha: ' . $e->getMessage();
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
    <title>Resetar Senha Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }

        .aviso {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .aviso strong {
            color: #856404;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .mensagem {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: left;
        }

        .mensagem.sucesso {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .mensagem.erro {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 15px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }

        small {
            color: #999;
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Resetar Senha do Admin</h1>

        <div class="aviso">
            <strong>⚠️ ATENÇÃO:</strong> Este arquivo permite resetar a senha do administrador.
            DELETE este arquivo após usar!
        </div>

        <?php if ($mensagem): ?>
            <div class="mensagem <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <?php if ($tipo_mensagem !== 'sucesso'): ?>
            <form method="POST">
                <div class="form-group">
                    <label>Email do Admin</label>
                    <input type="text" value="admin@sistema.com" readonly style="background: #f5f5f5;">
                </div>

                <div class="form-group">
                    <label>Nova Senha *</label>
                    <input type="password" name="nova_senha" id="nova_senha" required>
                    <small>Mínimo 6 caracteres</small>
                </div>

                <div class="form-group">
                    <label>Confirmar Senha *</label>
                    <input type="password" name="confirma_senha" required>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="mostrar" onclick="toggleSenha()">
                    <label for="mostrar" style="margin: 0;">Mostrar senha</label>
                </div>

                <button type="submit" class="btn">🔓 Resetar Senha</button>
            </form>
        <?php else: ?>
            <a href="login.php" class="btn" style="display: block; text-align: center; text-decoration: none;">
                ➡️ Ir para Login
            </a>
        <?php endif; ?>
    </div>

    <script>
        function toggleSenha() {
            const senha = document.getElementById('nova_senha');
            const confirma = document.querySelector('input[name="confirma_senha"]');
            const tipo = senha.type === 'password' ? 'text' : 'password';
            senha.type = tipo;
            confirma.type = tipo;
        }
    </script>
</body>
</html>
