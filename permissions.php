<?php
/**
 * Sistema de Permissões e Controle de Acesso
 */

// Verificar se usuário está logado
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit;
    }
}

// Verificar se usuário é admin
function verificarAdmin() {
    verificarLogin();

    if (!isset($_SESSION['usuario_role']) || $_SESSION['usuario_role'] !== 'admin') {
        header('Location: dashboard.php?erro=acesso_negado');
        exit;
    }
}

// Verificar se usuário é admin ou gestor
function verificarGestor() {
    verificarLogin();

    if (!isset($_SESSION['usuario_role']) ||
        !in_array($_SESSION['usuario_role'], ['admin', 'gestor'])) {
        header('Location: dashboard.php?erro=acesso_negado');
        exit;
    }
}

// Verificar se usuário tem permissão
function temPermissao($permissao) {
    if (!isset($_SESSION['usuario_role'])) {
        return false;
    }

    $role = $_SESSION['usuario_role'];

    $permissoes = [
        'admin' => [
            'criar_usuario',
            'editar_usuario',
            'excluir_usuario',
            'gerenciar_departamentos',
            'ver_todos_departamentos',
            'criar_conta',
            'editar_conta',
            'excluir_conta',
            'aprovar_conta',
            'ver_relatorios',
            'ver_auditoria',
            'alterar_configuracoes'
        ],
        'gestor' => [
            'criar_conta',
            'editar_conta',
            'editar_conta_departamento',
            'excluir_conta_departamento',
            'aprovar_conta',
            'ver_relatorios_departamento',
            'ver_usuarios_departamento'
        ],
        'usuario' => [
            'criar_conta',
            'editar_conta_propria',
            'ver_conta_propria'
        ]
    ];

    return in_array($permissao, $permissoes[$role] ?? []);
}

// Verificar se pode editar/excluir conta
function podeEditarConta($conta, $usuario_id, $role, $departamento_id = null) {
    // Admin pode tudo
    if ($role === 'admin') {
        return true;
    }

    // Gestor pode editar contas do seu departamento
    if ($role === 'gestor' && $departamento_id) {
        return $conta['departamento_id'] == $departamento_id;
    }

    // Usuário pode editar apenas suas próprias contas
    return $conta['usuario_id'] == $usuario_id;
}

// Obter nome da role em português
function getNomeRole($role) {
    $roles = [
        'admin' => 'Administrador',
        'gestor' => 'Gestor',
        'usuario' => 'Usuário'
    ];

    return $roles[$role] ?? 'Desconhecido';
}

// Obter cor da role
function getCorRole($role) {
    $cores = [
        'admin' => '#FF6384',
        'gestor' => '#36A2EB',
        'usuario' => '#4BC0C0'
    ];

    return $cores[$role] ?? '#999';
}

// Carregar informações completas do usuário na sessão
function carregarInfoUsuario($pdo, $usuario_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT u.*, d.nome as departamento_nome, d.cor as departamento_cor
            FROM usuarios u
            LEFT JOIN departamentos d ON u.departamento_id = d.id
            WHERE u.id = ? AND u.ativo = TRUE
        ");
        $stmt->execute([$usuario_id]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nome'] = $user['nome'];
            $_SESSION['usuario_email'] = $user['email'];
            $_SESSION['usuario_role'] = $user['role'];
            $_SESSION['usuario_departamento_id'] = $user['departamento_id'];
            $_SESSION['usuario_departamento_nome'] = $user['departamento_nome'];
            $_SESSION['usuario_foto'] = $user['foto_perfil'];

            // Atualizar último acesso
            $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
            $stmt->execute([$usuario_id]);

            return true;
        }

        return false;
    } catch(PDOException $e) {
        logSeguranca('error', 'Erro ao carregar info do usuário: ' . $e->getMessage());
        return false;
    }
}

// Filtrar query por permissões
function aplicarFiltroPermissao($role, $departamento_id = null) {
    if ($role === 'admin') {
        return ''; // Admin vê tudo
    }

    if ($role === 'gestor' && $departamento_id) {
        return " AND departamento_id = " . intval($departamento_id);
    }

    return " AND usuario_id = " . intval($_SESSION['usuario_id']);
}

// Registrar ação na auditoria
function registrarAuditoria($pdo, $acao, $tabela = null, $registro_id = null, $dados_antigos = null, $dados_novos = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO auditoria (usuario_id, acao, tabela, registro_id, dados_antigos, dados_novos, ip, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['usuario_id'] ?? null,
            $acao,
            $tabela,
            $registro_id,
            $dados_antigos ? json_encode($dados_antigos) : null,
            $dados_novos ? json_encode($dados_novos) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        return true;
    } catch(PDOException $e) {
        logSeguranca('error', 'Erro ao registrar auditoria: ' . $e->getMessage());
        return false;
    }
}
?>
