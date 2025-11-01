<?php
// Script para gerar hash de senha
// Execute este arquivo no navegador para gerar o hash correto

$senha = 'Admin@123';
$hash = password_hash($senha, PASSWORD_BCRYPT);

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Gerador de Hash de Senha</title>";
echo "<style>";
echo "body { font-family: Arial; padding: 20px; background: #f5f5f5; }";
echo ".box { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }";
echo "h1 { color: #333; }";
echo ".info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo ".hash { background: #f5f5f5; padding: 15px; border-radius: 5px; word-break: break-all; font-family: monospace; margin: 10px 0; }";
echo ".success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='box'>";
echo "<h1>🔑 Hash de Senha Gerado</h1>";
echo "<div class='info'>";
echo "<strong>Senha:</strong> " . htmlspecialchars($senha);
echo "</div>";
echo "<div class='hash'>";
echo "<strong>Hash bcrypt:</strong><br>";
echo htmlspecialchars($hash);
echo "</div>";
echo "<div class='success'>";
echo "<strong>✅ Use este hash no SQL:</strong><br><br>";
echo "<code>INSERT INTO usuarios (nome, email, senha, role, ativo) VALUES<br>";
echo "('Administrador do Sistema', 'admin@sistema.com', '" . htmlspecialchars($hash) . "', 'admin', TRUE);</code>";
echo "</div>";
echo "<hr>";
echo "<h2>Teste de Verificação</h2>";
echo "<div class='info'>";
$verifica = password_verify($senha, $hash);
if ($verifica) {
    echo "✅ <strong>Senha verificada com sucesso!</strong><br>";
    echo "A senha 'Admin@123' corresponde ao hash gerado.";
} else {
    echo "❌ <strong>Erro na verificação!</strong>";
}
echo "</div>";
echo "</div>";
echo "</body>";
echo "</html>";
?>
