<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'u411458227_studupss');
define('DB_USER', 'u411458227_studupss');
define('DB_PASS', '#Ide@2k25');

// Conexão com o banco de dados
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Iniciar sessão
session_start();
?>
