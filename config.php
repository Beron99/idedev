<?php
/**
 * Configurações do Sistema
 */

// Incluir funções de segurança
require_once __DIR__ . '/security.php';

// Definir headers de segurança
definirHeadersSeguranca();

// Iniciar sessão segura
iniciarSessaoSegura();

// Modo de desenvolvimento (alterar para false em produção)
define('DEV_MODE', false);

// Configurações do banco de dados
// IMPORTANTE: Em produção, use variáveis de ambiente
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'u411458227_studupss');
define('DB_USER', getenv('DB_USER') ?: 'u411458227_studupss');
define('DB_PASS', getenv('DB_PASS') ?: '#Ide@2k25');

// Conexão com o banco de dados
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false, // Previne SQL Injection
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch(PDOException $e) {
    // Não expor detalhes do erro em produção
    if (DEV_MODE) {
        die("Erro na conexão: " . $e->getMessage());
    } else {
        logSeguranca('error', 'Erro de conexão ao banco: ' . $e->getMessage());
        die("Erro ao conectar ao sistema. Tente novamente mais tarde.");
    }
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');
?>
