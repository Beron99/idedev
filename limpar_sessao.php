<?php
/**
 * Arquivo para limpar sessão em caso de problemas
 * Acesse este arquivo uma vez e depois delete-o
 */

session_start();
session_unset();
session_destroy();

// Limpar cookie de sessão
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

echo "Sessão limpa com sucesso!<br>";
echo "Agora você pode <a href='login.php'>fazer login novamente</a>.<br><br>";
echo "<strong>IMPORTANTE:</strong> Delete este arquivo após usar!";
?>
