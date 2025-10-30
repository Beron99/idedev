<?php
// Página de Auditoria (Log de Ações)
if (!defined('ADMIN_PAGE')) {
    die('Acesso negado');
}

// Filtros
$filtro_usuario = $_GET['filtro_usuario'] ?? '';
$filtro_acao = $_GET['filtro_acao'] ?? '';
$filtro_tabela = $_GET['filtro_tabela'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';
$limite = (int)($_GET['limite'] ?? 50);

$where = ["1=1"];
$params = [];

if ($filtro_usuario) {
    $where[] = "a.usuario_id = :usuario_id";
    $params[':usuario_id'] = $filtro_usuario;
}

if ($filtro_acao) {
    $where[] = "a.acao = :acao";
    $params[':acao'] = $filtro_acao;
}

if ($filtro_tabela) {
    $where[] = "a.tabela_afetada = :tabela";
    $params[':tabela'] = $filtro_tabela;
}

if ($data_inicio) {
    $where[] = "DATE(a.data_hora) >= :data_inicio";
    $params[':data_inicio'] = $data_inicio;
}

if ($data_fim) {
    $where[] = "DATE(a.data_hora) <= :data_fim";
    $params[':data_fim'] = $data_fim;
}

// Buscar registros de auditoria
$sql = "SELECT a.*, u.nome as usuario_nome, u.email as usuario_email
        FROM auditoria a
        LEFT JOIN usuarios u ON a.usuario_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY a.data_hora DESC
        LIMIT :limite";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
$stmt->execute();
$auditorias = $stmt->fetchAll();

// Buscar usuários para filtro
$stmt = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome");
$usuarios = $stmt->fetchAll();

// Ações disponíveis
$acoes_disponiveis = [
    'criar_usuario', 'editar_usuario', 'excluir_usuario', 'ativar_usuario', 'desativar_usuario', 'resetar_senha',
    'criar_departamento', 'editar_departamento', 'excluir_departamento',
    'criar_conta', 'editar_conta', 'excluir_conta', 'pagar_conta',
    'aprovar_conta', 'rejeitar_conta',
    'criar_categoria', 'editar_categoria', 'excluir_categoria',
    'login', 'logout', 'falha_login'
];
?>

<div class="admin-auditoria">
    <div class="page-header">
        <h2>📋 Log de Auditoria</h2>
        <button class="btn-secondary" onclick="exportarAuditoria()">
            📥 Exportar CSV
        </button>
    </div>

    <!-- Estatísticas Rápidas -->
    <div class="stats-grid-audit">
        <?php
        // Total de ações hoje
        $stmt = $pdo->query("SELECT COUNT(*) FROM auditoria WHERE DATE(data_hora) = CURDATE()");
        $total_hoje = $stmt->fetchColumn();

        // Total esta semana
        $stmt = $pdo->query("SELECT COUNT(*) FROM auditoria WHERE YEARWEEK(data_hora) = YEARWEEK(NOW())");
        $total_semana = $stmt->fetchColumn();

        // Ação mais comum
        $stmt = $pdo->query("SELECT acao, COUNT(*) as total FROM auditoria GROUP BY acao ORDER BY total DESC LIMIT 1");
        $acao_comum = $stmt->fetch();
        ?>

        <div class="stat-card-audit">
            <div class="stat-label">Ações Hoje</div>
            <div class="stat-value"><?php echo $total_hoje; ?></div>
        </div>

        <div class="stat-card-audit">
            <div class="stat-label">Ações esta Semana</div>
            <div class="stat-value"><?php echo $total_semana; ?></div>
        </div>

        <div class="stat-card-audit">
            <div class="stat-label">Ação Mais Comum</div>
            <div class="stat-value-small"><?php echo htmlspecialchars($acao_comum['acao'] ?? '-'); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-box">
        <form method="GET" class="filtros-form-audit">
            <input type="hidden" name="acao" value="auditoria">

            <select name="filtro_usuario">
                <option value="">Todos os usuários</option>
                <?php foreach ($usuarios as $user): ?>
                    <option value="<?php echo $user['id']; ?>"
                            <?php echo $filtro_usuario == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filtro_acao">
                <option value="">Todas as ações</option>
                <?php foreach ($acoes_disponiveis as $acao): ?>
                    <option value="<?php echo $acao; ?>"
                            <?php echo $filtro_acao === $acao ? 'selected' : ''; ?>>
                        <?php echo str_replace('_', ' ', ucfirst($acao)); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="filtro_tabela">
                <option value="">Todas as tabelas</option>
                <option value="usuarios" <?php echo $filtro_tabela === 'usuarios' ? 'selected' : ''; ?>>Usuários</option>
                <option value="departamentos" <?php echo $filtro_tabela === 'departamentos' ? 'selected' : ''; ?>>Departamentos</option>
                <option value="contas_pagar" <?php echo $filtro_tabela === 'contas_pagar' ? 'selected' : ''; ?>>Contas a Pagar</option>
                <option value="categorias" <?php echo $filtro_tabela === 'categorias' ? 'selected' : ''; ?>>Categorias</option>
            </select>

            <input type="date" name="data_inicio" value="<?php echo htmlspecialchars($data_inicio); ?>" placeholder="Data início">
            <input type="date" name="data_fim" value="<?php echo htmlspecialchars($data_fim); ?>" placeholder="Data fim">

            <select name="limite">
                <option value="50" <?php echo $limite == 50 ? 'selected' : ''; ?>>50 registros</option>
                <option value="100" <?php echo $limite == 100 ? 'selected' : ''; ?>>100 registros</option>
                <option value="200" <?php echo $limite == 200 ? 'selected' : ''; ?>>200 registros</option>
                <option value="500" <?php echo $limite == 500 ? 'selected' : ''; ?>>500 registros</option>
            </select>

            <button type="submit" class="btn-secondary">🔍 Filtrar</button>
            <a href="?acao=auditoria" class="btn-secondary">🔄 Limpar</a>
        </form>
    </div>

    <!-- Timeline de Auditoria -->
    <div class="auditoria-timeline">
        <?php if (empty($auditorias)): ?>
            <p class="texto-vazio">Nenhum registro encontrado</p>
        <?php else: ?>
            <?php
            $data_anterior = '';
            foreach ($auditorias as $audit):
                $data_atual = date('Y-m-d', strtotime($audit['data_hora']));
                if ($data_atual !== $data_anterior):
                    $data_anterior = $data_atual;
            ?>
                <div class="timeline-date-separator">
                    <?php echo date('d/m/Y', strtotime($audit['data_hora'])); ?>
                </div>
            <?php endif; ?>

            <div class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content">
                    <div class="timeline-header">
                        <div class="timeline-usuario">
                            <strong><?php echo htmlspecialchars($audit['usuario_nome'] ?? 'Sistema'); ?></strong>
                            <span class="timeline-email"><?php echo htmlspecialchars($audit['usuario_email'] ?? ''); ?></span>
                        </div>
                        <div class="timeline-hora">
                            <?php echo date('H:i:s', strtotime($audit['data_hora'])); ?>
                        </div>
                    </div>

                    <div class="timeline-acao">
                        <span class="badge-acao acao-<?php echo getAcaoTipo($audit['acao']); ?>">
                            <?php echo getAcaoIcone($audit['acao']); ?>
                            <?php echo str_replace('_', ' ', ucfirst($audit['acao'])); ?>
                        </span>

                        <?php if ($audit['tabela_afetada']): ?>
                            <span class="timeline-tabela">
                                em <strong><?php echo htmlspecialchars($audit['tabela_afetada']); ?></strong>
                                <?php if ($audit['registro_id']): ?>
                                    (ID: <?php echo $audit['registro_id']; ?>)
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="timeline-ip">
                        <small>IP: <?php echo htmlspecialchars($audit['ip_address']); ?></small>
                        <?php if ($audit['user_agent']): ?>
                            <small class="timeline-ua">
                                <?php echo htmlspecialchars(substr($audit['user_agent'], 0, 50)); ?>...
                            </small>
                        <?php endif; ?>
                    </div>

                    <?php if ($audit['dados_antigos'] || $audit['dados_novos']): ?>
                        <div class="timeline-dados">
                            <button class="btn-toggle-dados" onclick="toggleDados(<?php echo $audit['id']; ?>)">
                                🔍 Ver detalhes
                            </button>
                            <div id="dados-<?php echo $audit['id']; ?>" class="dados-detalhes" style="display: none;">
                                <?php if ($audit['dados_antigos']): ?>
                                    <div class="dados-box">
                                        <strong>Dados Anteriores:</strong>
                                        <pre><?php echo htmlspecialchars(json_encode(json_decode($audit['dados_antigos']), JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                <?php endif; ?>
                                <?php if ($audit['dados_novos']): ?>
                                    <div class="dados-box">
                                        <strong>Dados Novos:</strong>
                                        <pre><?php echo htmlspecialchars(json_encode(json_decode($audit['dados_novos']), JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleDados(id) {
    const elemento = document.getElementById('dados-' + id);
    if (elemento.style.display === 'none') {
        elemento.style.display = 'block';
    } else {
        elemento.style.display = 'none';
    }
}

function exportarAuditoria() {
    const params = new URLSearchParams(window.location.search);
    params.set('exportar', 'csv');
    window.location.href = 'admin_auditoria_export.php?' + params.toString();
}
</script>

<?php
// Funções auxiliares
function getAcaoTipo($acao) {
    if (strpos($acao, 'criar') !== false) return 'criar';
    if (strpos($acao, 'editar') !== false || strpos($acao, 'ativar') !== false) return 'editar';
    if (strpos($acao, 'excluir') !== false || strpos($acao, 'desativar') !== false) return 'excluir';
    if (strpos($acao, 'aprovar') !== false) return 'aprovar';
    if (strpos($acao, 'rejeitar') !== false) return 'rejeitar';
    if (strpos($acao, 'login') !== false) return 'login';
    if (strpos($acao, 'pagar') !== false) return 'pagar';
    return 'outro';
}

function getAcaoIcone($acao) {
    $icones = [
        'criar' => '➕',
        'editar' => '✏️',
        'excluir' => '🗑️',
        'ativar' => '✅',
        'desativar' => '🚫',
        'aprovar' => '✓',
        'rejeitar' => '✕',
        'login' => '🔐',
        'logout' => '🚪',
        'pagar' => '💰',
        'resetar' => '🔑'
    ];

    foreach ($icones as $palavra => $icone) {
        if (strpos($acao, $palavra) !== false) {
            return $icone;
        }
    }

    return '📝';
}
?>
