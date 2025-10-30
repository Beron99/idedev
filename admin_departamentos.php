<?php
// Página de Gerenciamento de Departamentos
if (!defined('ADMIN_PAGE')) {
    die('Acesso negado');
}

// Buscar departamentos com estatísticas
$stmt = $pdo->query("
    SELECT
        d.*,
        COUNT(DISTINCT u.id) as total_usuarios,
        COUNT(DISTINCT c.id) as total_contas,
        COALESCE(SUM(CASE WHEN c.status = 'pago' THEN c.valor ELSE 0 END), 0) as total_pago,
        COALESCE(SUM(CASE WHEN c.status = 'pendente' THEN c.valor ELSE 0 END), 0) as total_pendente
    FROM departamentos d
    LEFT JOIN usuarios u ON u.departamento_id = d.id AND u.ativo = TRUE
    LEFT JOIN contas_pagar c ON c.departamento_id = d.id
    GROUP BY d.id
    ORDER BY d.nome
");
$departamentos = $stmt->fetchAll();
?>

<div class="admin-departamentos">
    <div class="page-header">
        <h2>🏢 Gerenciamento de Departamentos</h2>
        <button class="btn-primary" onclick="abrirModalNovoDepartamento()">
            ➕ Novo Departamento
        </button>
    </div>

    <!-- Cards de Departamentos -->
    <div class="departamentos-grid">
        <?php foreach ($departamentos as $dept): ?>
            <div class="departamento-card" style="border-left: 5px solid <?php echo $dept['cor']; ?>;">
                <div class="dept-header">
                    <h3 style="color: <?php echo $dept['cor']; ?>;">
                        <?php echo htmlspecialchars($dept['nome']); ?>
                    </h3>
                    <div class="dept-actions">
                        <button class="btn-icon" onclick="editarDepartamento(<?php echo $dept['id']; ?>)" title="Editar">✏️</button>
                        <button class="btn-icon btn-danger" onclick="excluirDepartamento(<?php echo $dept['id']; ?>)" title="Excluir">🗑️</button>
                    </div>
                </div>

                <?php if ($dept['descricao']): ?>
                    <p class="dept-descricao"><?php echo htmlspecialchars($dept['descricao']); ?></p>
                <?php endif; ?>

                <div class="dept-stats">
                    <div class="dept-stat">
                        <span class="stat-label">👥 Usuários:</span>
                        <span class="stat-value"><?php echo $dept['total_usuarios']; ?></span>
                    </div>
                    <div class="dept-stat">
                        <span class="stat-label">📝 Contas:</span>
                        <span class="stat-value"><?php echo $dept['total_contas']; ?></span>
                    </div>
                </div>

                <div class="dept-orcamento">
                    <div class="orcamento-info">
                        <span class="label">Orçamento Mensal:</span>
                        <span class="valor">R$ <?php echo number_format($dept['orcamento_mensal'], 2, ',', '.'); ?></span>
                    </div>
                    <div class="orcamento-info">
                        <span class="label">Gasto no Mês:</span>
                        <span class="valor <?php echo $dept['total_pago'] > $dept['orcamento_mensal'] ? 'valor-negativo' : ''; ?>">
                            R$ <?php echo number_format($dept['total_pago'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    <div class="orcamento-info">
                        <span class="label">Pendente:</span>
                        <span class="valor">R$ <?php echo number_format($dept['total_pendente'], 2, ',', '.'); ?></span>
                    </div>

                    <?php
                    $percentual = $dept['orcamento_mensal'] > 0 ? ($dept['total_pago'] / $dept['orcamento_mensal']) * 100 : 0;
                    $cor_barra = $percentual > 100 ? '#e74c3c' : ($percentual > 80 ? '#f39c12' : '#27ae60');
                    ?>

                    <div class="orcamento-barra">
                        <div class="barra-progresso">
                            <div class="barra-fill" style="width: <?php echo min($percentual, 100); ?>%; background-color: <?php echo $cor_barra; ?>;"></div>
                        </div>
                        <span class="barra-label" style="color: <?php echo $cor_barra; ?>;">
                            <?php echo number_format($percentual, 1); ?>%
                        </span>
                    </div>
                </div>

                <?php if ($dept['gestor_nome']): ?>
                    <div class="dept-gestor">
                        <strong>👤 Gestor:</strong> <?php echo htmlspecialchars($dept['gestor_nome']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabela Detalhada -->
    <div class="table-section">
        <h3>Visão Detalhada</h3>
        <div class="table-container">
            <table class="table-admin">
                <thead>
                    <tr>
                        <th>Departamento</th>
                        <th>Gestor</th>
                        <th>Usuários</th>
                        <th>Orçamento</th>
                        <th>Gasto</th>
                        <th>Pendente</th>
                        <th>% Utilizado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departamentos as $dept): ?>
                        <?php
                        $percentual = $dept['orcamento_mensal'] > 0 ? ($dept['total_pago'] / $dept['orcamento_mensal']) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <span class="badge-depto" style="background-color: <?php echo $dept['cor']; ?>;">
                                    <?php echo htmlspecialchars($dept['nome']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($dept['gestor_nome'] ?? '-'); ?></td>
                            <td><?php echo $dept['total_usuarios']; ?></td>
                            <td>R$ <?php echo number_format($dept['orcamento_mensal'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($dept['total_pago'], 2, ',', '.'); ?></td>
                            <td>R$ <?php echo number_format($dept['total_pendente'], 2, ',', '.'); ?></td>
                            <td>
                                <span style="color: <?php echo $percentual > 100 ? '#e74c3c' : ($percentual > 80 ? '#f39c12' : '#27ae60'); ?>; font-weight: bold;">
                                    <?php echo number_format($percentual, 1); ?>%
                                </span>
                            </td>
                            <td class="acoes-td">
                                <button class="btn-icon" onclick="editarDepartamento(<?php echo $dept['id']; ?>)">✏️</button>
                                <button class="btn-icon btn-danger" onclick="excluirDepartamento(<?php echo $dept['id']; ?>)">🗑️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Novo/Editar Departamento -->
<div id="modalDepartamento" class="modal">
    <div class="modal-content">
        <span class="close" onclick="fecharModalDepartamento()">&times;</span>
        <h3 id="modalDepartamentoTitulo">Novo Departamento</h3>

        <form id="formDepartamento" method="POST" action="admin_departamentos_ajax.php">
            <input type="hidden" name="csrf_token" value="<?php echo gerarTokenCSRF(); ?>">
            <input type="hidden" name="acao" value="criar">
            <input type="hidden" name="departamento_id" id="edit_departamento_id">

            <div class="form-group">
                <label>Nome do Departamento *</label>
                <input type="text" name="nome" id="edit_nome" required placeholder="Ex: Financeiro, TI, Marketing...">
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <textarea name="descricao" id="edit_descricao" rows="3" placeholder="Descrição do departamento..."></textarea>
            </div>

            <div class="form-group">
                <label>Cor de Identificação *</label>
                <div class="color-picker-group">
                    <input type="color" name="cor" id="edit_cor" value="#3498db" required>
                    <span class="color-preview" id="colorPreview" style="background-color: #3498db;"></span>
                </div>
                <small>Escolha uma cor para identificar o departamento</small>
            </div>

            <div class="form-group">
                <label>Orçamento Mensal *</label>
                <input type="number" name="orcamento_mensal" id="edit_orcamento_mensal" step="0.01" min="0" required placeholder="0.00">
            </div>

            <div class="form-group">
                <label>Nome do Gestor</label>
                <input type="text" name="gestor_nome" id="edit_gestor_nome" placeholder="Nome do responsável pelo departamento">
            </div>

            <div class="modal-actions">
                <button type="submit" class="btn-primary">💾 Salvar</button>
                <button type="button" class="btn-secondary" onclick="fecharModalDepartamento()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
// Color picker preview
document.getElementById('edit_cor')?.addEventListener('input', function(e) {
    document.getElementById('colorPreview').style.backgroundColor = e.target.value;
});

// Abrir modal para novo departamento
function abrirModalNovoDepartamento() {
    document.getElementById('modalDepartamentoTitulo').textContent = 'Novo Departamento';
    document.getElementById('formDepartamento').reset();
    document.querySelector('input[name="acao"]').value = 'criar';
    document.getElementById('edit_departamento_id').value = '';
    document.getElementById('edit_cor').value = '#3498db';
    document.getElementById('colorPreview').style.backgroundColor = '#3498db';
    document.getElementById('modalDepartamento').style.display = 'block';
}

// Editar departamento
function editarDepartamento(id) {
    fetch(`admin_departamentos_ajax.php?acao=buscar&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalDepartamentoTitulo').textContent = 'Editar Departamento';
                document.querySelector('input[name="acao"]').value = 'editar';
                document.getElementById('edit_departamento_id').value = data.departamento.id;
                document.getElementById('edit_nome').value = data.departamento.nome;
                document.getElementById('edit_descricao').value = data.departamento.descricao || '';
                document.getElementById('edit_cor').value = data.departamento.cor;
                document.getElementById('colorPreview').style.backgroundColor = data.departamento.cor;
                document.getElementById('edit_orcamento_mensal').value = data.departamento.orcamento_mensal;
                document.getElementById('edit_gestor_nome').value = data.departamento.gestor_nome || '';
                document.getElementById('modalDepartamento').style.display = 'block';
            } else {
                alert('Erro ao buscar departamento: ' + data.message);
            }
        })
        .catch(err => {
            alert('Erro ao buscar departamento');
            console.error(err);
        });
}

// Fechar modal
function fecharModalDepartamento() {
    document.getElementById('modalDepartamento').style.display = 'none';
}

// Submit do formulário
document.getElementById('formDepartamento').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('admin_departamentos_ajax.php', {
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
        alert('Erro ao salvar departamento');
        console.error(err);
    });
});

// Excluir departamento
function excluirDepartamento(id) {
    if (!confirm('ATENÇÃO: Deseja realmente excluir este departamento?\n\nUsuários e contas associadas não serão excluídos, mas ficarão sem departamento.')) {
        return;
    }

    fetch('admin_departamentos_ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `acao=excluir&departamento_id=${id}&csrf_token=<?php echo gerarTokenCSRF(); ?>`
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
        alert('Erro ao excluir departamento');
        console.error(err);
    });
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('modalDepartamento');
    if (event.target == modal) {
        fecharModalDepartamento();
    }
}
</script>
