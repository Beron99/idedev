<?php
// Página de Gerenciamento de Usuários
if (!defined('ADMIN_PAGE')) {
    die('Acesso negado');
}

// Buscar usuários
$filtro_role = $_GET['filtro_role'] ?? '';
$filtro_depto = $_GET['filtro_depto'] ?? '';
$busca = $_GET['busca'] ?? '';

$where = ["u.id > 0"];
$params = [];

if ($filtro_role) {
    $where[] = "u.role = :role";
    $params[':role'] = $filtro_role;
}

if ($filtro_depto) {
    $where[] = "u.departamento_id = :depto";
    $params[':depto'] = $filtro_depto;
}

if ($busca) {
    $where[] = "(u.nome LIKE :busca OR u.email LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

$sql = "SELECT u.*, d.nome as departamento_nome, d.cor as departamento_cor
        FROM usuarios u
        LEFT JOIN departamentos d ON u.departamento_id = d.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY u.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Buscar departamentos para filtros e formulários
$stmt = $pdo->query("SELECT * FROM departamentos ORDER BY nome");
$departamentos = $stmt->fetchAll();
?>

<div class="admin-usuarios">
    <div class="page-header">
        <h2>👥 Gerenciamento de Usuários</h2>
        <button class="btn-primary" onclick="abrirModalNovoUsuario()">
            ➕ Novo Usuário
        </button>
    </div>

    <!-- Filtros -->
    <div class="filtros-box">
        <form method="GET" class="filtros-form">
            <input type="hidden" name="acao" value="usuarios">

            <input type="text"
                   name="busca"
                   placeholder="Buscar por nome ou email..."
                   value="<?php echo htmlspecialchars($busca); ?>">

            <select name="filtro_role">
                <option value="">Todas as funções</option>
                <option value="admin" <?php echo $filtro_role === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                <option value="gestor" <?php echo $filtro_role === 'gestor' ? 'selected' : ''; ?>>Gestor</option>
                <option value="usuario" <?php echo $filtro_role === 'usuario' ? 'selected' : ''; ?>>Usuário</option>
            </select>

            <select name="filtro_depto">
                <option value="">Todos os departamentos</option>
                <?php foreach ($departamentos as $dept): ?>
                    <option value="<?php echo $dept['id']; ?>"
                            <?php echo $filtro_depto == $dept['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dept['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-secondary">🔍 Filtrar</button>
            <a href="?acao=usuarios" class="btn-secondary">🔄 Limpar</a>
        </form>
    </div>

    <!-- Tabela de Usuários -->
    <div class="table-container">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Função</th>
                    <th>Departamento</th>
                    <th>Status</th>
                    <th>Último Acesso</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8" class="texto-vazio">Nenhum usuário encontrado</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usuarios as $user): ?>
                        <tr>
                            <td>
                                <?php if ($user['foto_perfil']): ?>
                                    <img src="<?php echo htmlspecialchars($user['foto_perfil']); ?>"
                                         class="user-avatar"
                                         alt="Foto">
                                <?php else: ?>
                                    <div class="user-avatar-placeholder">
                                        <?php echo strtoupper(substr($user['nome'], 0, 2)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($user['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge-role role-<?php echo $user['role']; ?>">
                                    <?php echo getNomeRole($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($user['departamento_nome']): ?>
                                    <span class="badge-depto" style="background-color: <?php echo $user['departamento_cor']; ?>;">
                                        <?php echo htmlspecialchars($user['departamento_nome']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="texto-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['ativo']): ?>
                                    <span class="badge-status ativo">✓ Ativo</span>
                                <?php else: ?>
                                    <span class="badge-status inativo">✕ Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['ultimo_acesso']): ?>
                                    <?php echo date('d/m/Y H:i', strtotime($user['ultimo_acesso'])); ?>
                                <?php else: ?>
                                    <span class="texto-muted">Nunca</span>
                                <?php endif; ?>
                            </td>
                            <td class="acoes-td">
                                <button class="btn-icon"
                                        onclick="editarUsuario(<?php echo $user['id']; ?>)"
                                        title="Editar">
                                    ✏️
                                </button>
                                <button class="btn-icon"
                                        onclick="resetarSenha(<?php echo $user['id']; ?>)"
                                        title="Resetar Senha">
                                    🔑
                                </button>
                                <button class="btn-icon"
                                        onclick="toggleAtivo(<?php echo $user['id']; ?>, <?php echo $user['ativo'] ? 'false' : 'true'; ?>)"
                                        title="<?php echo $user['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                    <?php echo $user['ativo'] ? '🚫' : '✅'; ?>
                                </button>
                                <?php if ($user['id'] != $_SESSION['usuario_id']): ?>
                                    <button class="btn-icon btn-danger"
                                            onclick="excluirUsuario(<?php echo $user['id']; ?>)"
                                            title="Excluir">
                                        🗑️
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Novo/Editar Usuário -->
<div id="modalUsuario" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fecharModalUsuario()">&times;</span>
        <h3 id="modalUsuarioTitulo">Novo Usuário</h3>

        <form id="formUsuario" method="POST" action="admin_usuarios_ajax.php">
            <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
            <input type="hidden" name="acao" value="criar">
            <input type="hidden" name="usuario_id" id="edit_usuario_id">

            <div class="form-group">
                <label>Nome Completo *</label>
                <input type="text" name="nome" id="edit_nome" required>
            </div>

            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" id="edit_email" required>
            </div>

            <div class="form-group" id="senha_group">
                <label>Senha *</label>
                <input type="password" name="senha" id="edit_senha">
                <small>Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número</small>
            </div>

            <div class="form-group">
                <label>Função *</label>
                <select name="role" id="edit_role" required>
                    <option value="usuario">Usuário</option>
                    <option value="gestor">Gestor</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>

            <div class="form-group">
                <label>Departamento *</label>
                <select name="departamento_id" id="edit_departamento_id" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($departamentos as $dept): ?>
                        <option value="<?php echo $dept['id']; ?>">
                            <?php echo htmlspecialchars($dept['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Telefone</label>
                <input type="text" name="telefone" id="edit_telefone" placeholder="(00) 00000-0000">
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="ativo" id="edit_ativo" value="1" checked>
                    Usuário ativo
                </label>
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-primary">💾 Salvar</button>
                <button type="button" class="btn-secondary" onclick="fecharModalUsuario()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Abrir modal para novo usuário
function abrirModalNovoUsuario() {
    document.getElementById('modalUsuarioTitulo').textContent = 'Novo Usuário';
    document.getElementById('formUsuario').reset();
    document.querySelector('input[name="acao"]').value = 'criar';
    document.getElementById('edit_usuario_id').value = '';
    document.getElementById('edit_senha').required = true;
    document.getElementById('senha_group').style.display = 'block';
    document.getElementById('modalUsuario').style.display = 'block';
}

// Editar usuário
function editarUsuario(id) {
    fetch(`admin_usuarios_ajax.php?acao=buscar&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuário';
                document.querySelector('input[name="acao"]').value = 'editar';
                document.getElementById('edit_usuario_id').value = data.usuario.id;
                document.getElementById('edit_nome').value = data.usuario.nome;
                document.getElementById('edit_email').value = data.usuario.email;
                document.getElementById('edit_role').value = data.usuario.role;
                document.getElementById('edit_departamento_id').value = data.usuario.departamento_id || '';
                document.getElementById('edit_telefone').value = data.usuario.telefone || '';
                document.getElementById('edit_ativo').checked = data.usuario.ativo == 1;
                document.getElementById('edit_senha').required = false;
                document.getElementById('senha_group').style.display = 'none';
                document.getElementById('modalUsuario').style.display = 'block';
            } else {
                alert('Erro ao buscar usuário: ' + data.message);
            }
        })
        .catch(err => {
            alert('Erro ao buscar usuário');
            console.error(err);
        });
}

// Fechar modal
function fecharModalUsuario() {
    document.getElementById('modalUsuario').style.display = 'none';
}

// Submit do formulário
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('admin_usuarios_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(err => {
        alert('Erro ao salvar usuário');
        console.error(err);
    });
});

// Resetar senha
function resetarSenha(id) {
    if (!confirm('Deseja gerar uma nova senha temporária para este usuário?')) {
        return;
    }

    fetch('admin_usuarios_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `acao=resetar_senha&usuario_id=${id}&csrf_token=<?php echo gerarTokenCSRF(); ?>`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Nova senha: ' + data.nova_senha + '\n\nEnvie esta senha ao usuário.');
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(err => {
        alert('Erro ao resetar senha');
        console.error(err);
    });
}

// Toggle ativo/inativo
function toggleAtivo(id, ativo) {
    const acao_texto = ativo ? 'ativar' : 'desativar';
    if (!confirm(`Deseja ${acao_texto} este usuário?`)) {
        return;
    }

    fetch('admin_usuarios_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `acao=toggle_ativo&usuario_id=${id}&ativo=${ativo}&csrf_token=<?php echo gerarTokenCSRF(); ?>`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(err => {
        alert('Erro ao atualizar status');
        console.error(err);
    });
}

// Excluir usuário
function excluirUsuario(id) {
    if (!confirm('ATENÇÃO: Deseja realmente excluir este usuário?\n\nEsta ação não pode ser desfeita!')) {
        return;
    }

    fetch('admin_usuarios_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `acao=excluir&usuario_id=${id}&csrf_token=<?php echo gerarTokenCSRF(); ?>`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erro: ' + data.message);
        }
    })
    .catch(err => {
        alert('Erro ao excluir usuário');
        console.error(err);
    });
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalUsuario');
    if (event.target == modal) {
        fecharModalUsuario();
    }
}
</script>
