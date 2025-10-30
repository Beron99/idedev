<?php
/**
 * Arquivo de Segurança
 * Funções e configurações de segurança do sistema
 */

// Configurar sessão segura
function iniciarSessaoSegura() {
    // Configurações de cookie seguro
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => isset($_SERVER['HTTPS']), // true se HTTPS
        'httponly' => true, // Previne XSS
        'samesite' => 'Strict' // Previne CSRF
    ];

    session_set_cookie_params($cookieParams);

    // Regenerar ID de sessão periodicamente
    if (!isset($_SESSION)) {
        session_start();
    }

    // Verificar se precisa regenerar
    if (!isset($_SESSION['CRIADO'])) {
        $_SESSION['CRIADO'] = time();
    } else if (time() - $_SESSION['CRIADO'] > 1800) {
        // Regenerar sessão a cada 30 minutos
        session_regenerate_id(true);
        $_SESSION['CRIADO'] = time();
    }

    // Validar IP e User Agent para prevenir session hijacking
    if (isset($_SESSION['usuario_id'])) {
        $sessaoValida = true;

        // Verificar IP
        if (isset($_SESSION['IP'])) {
            if ($_SESSION['IP'] !== $_SERVER['REMOTE_ADDR']) {
                $sessaoValida = false;
            }
        } else {
            $_SESSION['IP'] = $_SERVER['REMOTE_ADDR'];
        }

        // Verificar User Agent
        if (isset($_SESSION['USER_AGENT'])) {
            if ($_SESSION['USER_AGENT'] !== $_SERVER['HTTP_USER_AGENT']) {
                $sessaoValida = false;
            }
        } else {
            $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];
        }

        if (!$sessaoValida) {
            session_unset();
            session_destroy();
            header('Location: login.php?erro=sessao_invalida');
            exit;
        }
    }
}

// Gerar token CSRF
function gerarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function validarTokenCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Limpar e validar entrada
function limparEntrada($dado, $tipo = 'string') {
    $dado = trim($dado);

    switch ($tipo) {
        case 'email':
            return filter_var($dado, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($dado, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($dado, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'string':
        default:
            return htmlspecialchars($dado, ENT_QUOTES, 'UTF-8');
    }
}

// Validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validar senha forte
function validarSenhaForte($senha) {
    // Mínimo 8 caracteres, pelo menos 1 letra maiúscula, 1 minúscula, 1 número
    $erros = [];

    if (strlen($senha) < 8) {
        $erros[] = 'A senha deve ter no mínimo 8 caracteres';
    }

    if (!preg_match('/[A-Z]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos uma letra maiúscula';
    }

    if (!preg_match('/[a-z]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos uma letra minúscula';
    }

    if (!preg_match('/[0-9]/', $senha)) {
        $erros[] = 'A senha deve conter pelo menos um número';
    }

    return [
        'valida' => empty($erros),
        'erros' => $erros
    ];
}

// Definir headers de segurança
function definirHeadersSeguranca() {
    // Prevenir clickjacking
    header('X-Frame-Options: DENY');

    // Prevenir MIME sniffing
    header('X-Content-Type-Options: nosniff');

    // Ativar proteção XSS do navegador
    header('X-XSS-Protection: 1; mode=block');

    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");

    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // HTTPS Strict Transport Security (se estiver em HTTPS)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Rate limiting simples (proteção contra brute force)
function verificarRateLimit($chave, $max_tentativas = 5, $janela = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $agora = time();
    $chave_completa = 'rl_' . $chave;

    // Limpar tentativas antigas
    if (isset($_SESSION['rate_limit'][$chave_completa])) {
        $_SESSION['rate_limit'][$chave_completa] = array_filter(
            $_SESSION['rate_limit'][$chave_completa],
            function($timestamp) use ($agora, $janela) {
                return ($agora - $timestamp) < $janela;
            }
        );
    } else {
        $_SESSION['rate_limit'][$chave_completa] = [];
    }

    // Verificar se excedeu o limite
    if (count($_SESSION['rate_limit'][$chave_completa]) >= $max_tentativas) {
        return false;
    }

    // Registrar tentativa
    $_SESSION['rate_limit'][$chave_completa][] = $agora;
    return true;
}

// Verificar se está bloqueado
function estaRateLimitBloqueado($chave, $max_tentativas = 5) {
    if (!isset($_SESSION['rate_limit'])) {
        return false;
    }

    $chave_completa = 'rl_' . $chave;

    if (!isset($_SESSION['rate_limit'][$chave_completa])) {
        return false;
    }

    return count($_SESSION['rate_limit'][$chave_completa]) >= $max_tentativas;
}

// Obter tempo restante de bloqueio
function tempoRestanteBloqueio($chave, $janela = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        return 0;
    }

    $chave_completa = 'rl_' . $chave;

    if (!isset($_SESSION['rate_limit'][$chave_completa]) || empty($_SESSION['rate_limit'][$chave_completa])) {
        return 0;
    }

    $primeira_tentativa = min($_SESSION['rate_limit'][$chave_completa]);
    $tempo_decorrido = time() - $primeira_tentativa;
    $tempo_restante = $janela - $tempo_decorrido;

    return max(0, $tempo_restante);
}

// Log de segurança
function logSeguranca($tipo, $mensagem, $usuario_id = null) {
    $log_file = __DIR__ . '/logs/security.log';
    $log_dir = dirname($log_file);

    // Criar diretório de logs se não existir
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    $usuario = $usuario_id ?? 'GUEST';

    $log_entry = sprintf(
        "[%s] [%s] [IP: %s] [User: %s] %s (UA: %s)\n",
        $timestamp,
        strtoupper($tipo),
        $ip,
        $usuario,
        $mensagem,
        $user_agent
    );

    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
?>
