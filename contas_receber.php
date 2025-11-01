<?php
require_once 'config.php';

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$mensagem = '';
$tipo_mensagem = '';

// Processar ações (adicionar, editar, excluir, marcar como recebido)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        logSeguranca('warning', 'Tentativa de ação em contas a receber com token CSRF inválido', $usuario_id);
        $mensagem = 'Token de segurança inválido!';
        $tipo_mensagem = 'erro';
    } else {
        $acao = $_POST['acao'] ?? '';

        try {
            if ($acao == 'adicionar') {
                $descricao = limparEntrada($_POST['descricao']);
                $valor = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor'])));
                $data_vencimento = $_POST['data_vencimento'];
                $categoria_id = intval($_POST['categoria_id']) ?: null;
                $cliente_id = intval($_POST['cliente_id']) ?: null;
                $cliente = limparEntrada($_POST['cliente']);
                $observacoes = limparEntrada($_POST['observacoes']);

                // Validar valor positivo
                if ($valor <= 0) {
                    throw new Exception('O valor deve ser maior que zero');
                }

            $stmt = $pdo->prepare("INSERT INTO contas_receber (usuario_id, categoria_id, cliente_id, descricao, valor, data_vencimento, cliente, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $categoria_id, $cliente_id, $descricao, $valor, $data_vencimento, $cliente, $observacoes]);

                logSeguranca('info', "Conta a receber adicionada: $descricao (R$ $valor)", $usuario_id);

                $mensagem = 'Conta a receber adicionada com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'editar') {
                $id = intval($_POST['id']);
                $descricao = limparEntrada($_POST['descricao']);
                $valor = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor'])));
                $data_vencimento = $_POST['data_vencimento'];
                $categoria_id = intval($_POST['categoria_id']) ?: null;
                $cliente_id = intval($_POST['cliente_id']) ?: null;
                $cliente = limparEntrada($_POST['cliente']);
                $observacoes = limparEntrada($_POST['observacoes']);

                // Validar valor positivo
                if ($valor <= 0) {
                    throw new Exception('O valor deve ser maior que zero');
                }

            $stmt = $pdo->prepare("UPDATE contas_receber SET descricao = ?, valor = ?, data_vencimento = ?, categoria_id = ?, cliente_id = ?, cliente = ?, observacoes = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$descricao, $valor, $data_vencimento, $categoria_id, $cliente_id, $cliente, $observacoes, $id, $usuario_id]);

                logSeguranca('info', "Conta a receber editada ID: $id", $usuario_id);

                $mensagem = 'Conta a receber atualizada com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'excluir') {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM contas_receber WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuario_id]);

                logSeguranca('info', "Conta a receber excluída ID: $id", $usuario_id);

                $mensagem = 'Conta a receber excluída com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'receber') {
                $id = intval($_POST['id']);
                $data_recebimento = $_POST['data_recebimento'] ?? date('Y-m-d');

                $stmt = $pdo->prepare("UPDATE contas_receber SET status = 'recebido', data_recebimento = ? WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$data_recebimento, $id, $usuario_id]);

                logSeguranca('info', "Conta recebida ID: $id", $usuario_id);

                $mensagem = 'Conta marcada como recebida!';
                $tipo_mensagem = 'sucesso';
            }
        } catch(PDOException $e) {
            logSeguranca('error', 'Erro em contas a receber: ' . $e->getMessage(), $usuario_id);
            $mensagem = 'Erro ao processar ação. Tente novamente.';
            $tipo_mensagem = 'erro';
        } catch(Exception $e) {
            $mensagem = $e->getMessage();
            $tipo_mensagem = 'erro';
        }
    }
}

// Gerar token CSRF
$csrf_token = gerarTokenCSRF();

// Filtros
$filtro_status = $_GET['status'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_mes = $_GET['mes'] ?? '';

// Buscar categorias
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuario_id = ? ORDER BY nome");
$stmt->execute([$usuario_id]);
$categorias = $stmt->fetchAll();

// Buscar clientes ativos
try {
    $stmt = $pdo->prepare("SELECT id, razao_social, nome_fantasia FROM clientes WHERE usuario_id = ? AND ativo = 1 ORDER BY razao_social");
    $stmt->execute([$usuario_id]);
    $clientes = $stmt->fetchAll();
} catch(PDOException $e) {
    $clientes = [];
}

// Buscar contas com filtros
$where = ["c.usuario_id = ?"];
$params = [$usuario_id];

if ($filtro_status) {
    $where[] = "c.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_categoria) {
    $where[] = "c.categoria_id = ?";
    $params[] = $filtro_categoria;
}

if ($filtro_mes) {
    $where[] = "DATE_FORMAT(c.data_vencimento, '%Y-%m') = ?";
    $params[] = $filtro_mes;
}

$sql = "
    SELECT c.*, cat.nome as categoria_nome, cat.cor as categoria_cor,
           cli.razao_social as cliente_nome, cli.nome_fantasia as cliente_fantasia
    FROM contas_receber c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    LEFT JOIN clientes cli ON c.cliente_id = cli.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.data_vencimento DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contas = $stmt->fetchAll();

// Atualizar status de contas vencidas
$pdo->prepare("UPDATE contas_receber SET status = 'vencido' WHERE status = 'pendente' AND data_vencimento < CURRENT_DATE()")->execute();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas a Receber - Gestão Financeira</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-painel">
        <div class="header-painel">
            <h1>Gestão Financeira</h1>
            <div class="user-info">
                <span>Olá, <strong><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></strong>!</span>
                <a href="logout.php" class="btn-logout">Sair</a>
            </div>
        </div>

        <div class="content-painel">
            <!-- Menu de navegação -->
            <div class="menu-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="contas.php" class="nav-item">Contas a Pagar</a>
                <a href="contas_receber.php" class="nav-item active">Contas a Receber</a>
                <a href="clientes.php" class="nav-item">Clientes</a>
                <a href="categorias.php" class="nav-item">Categorias</a>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h2>Contas a Receber</h2>
                <button class="btn btn-primary" onclick="abrirModal()">+ Nova Conta a Receber</button>
            </div>

            <!-- Filtros -->
            <div class="filtros">
                <form method="GET" action="" class="filtros-form">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">Todos os Status</option>
                        <option value="pendente" <?php echo $filtro_status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="recebido" <?php echo $filtro_status == 'recebido' ? 'selected' : ''; ?>>Recebido</option>
                        <option value="vencido" <?php echo $filtro_status == 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                    </select>

                    <select name="categoria" onchange="this.form.submit()">
                        <option value="">Todas as Categorias</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filtro_categoria == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="month" name="mes" value="<?php echo $filtro_mes; ?>" onchange="this.form.submit()">

                    <?php if ($filtro_status || $filtro_categoria || $filtro_mes): ?>
                        <a href="contas_receber.php" class="btn-limpar">Limpar Filtros</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Lista de contas -->
            <div class="table-container">
                <table class="table-contas">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Cliente</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($contas) > 0): ?>
                            <?php foreach ($contas as $conta): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($conta['descricao']); ?></strong>
                                        <?php if ($conta['observacoes']): ?>
                                            <br><small class="observacao"><?php echo htmlspecialchars($conta['observacoes']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($conta['cliente_nome']) {
                                            echo htmlspecialchars($conta['cliente_nome']);
                                            if ($conta['cliente_fantasia']) {
                                                echo '<br><small style="color: #999;">' . htmlspecialchars($conta['cliente_fantasia']) . '</small>';
                                            }
                                        } elseif ($conta['cliente']) {
                                            echo htmlspecialchars($conta['cliente']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($conta['categoria_nome']): ?>
                                            <span class="badge-categoria" style="background-color: <?php echo $conta['categoria_cor']; ?>">
                                                <?php echo htmlspecialchars($conta['categoria_nome']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-categoria" style="background-color: #ccc;">Sem categoria</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="valor">R$ <?php echo number_format($conta['valor'], 2, ',', '.'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($conta['data_vencimento'])); ?></td>
                                    <td>
                                        <span class="badge-status status-<?php echo $conta['status']; ?>">
                                            <?php echo ucfirst($conta['status']); ?>
                                        </span>
                                    </td>
                                    <td class="acoes">
                                        <?php if ($conta['status'] != 'recebido'): ?>
                                            <button onclick="marcarRecebido(<?php echo $conta['id']; ?>)" class="btn-acao btn-pagar" title="Marcar como recebido">✓</button>
                                        <?php endif; ?>
                                        <button onclick="editarConta(<?php echo htmlspecialchars(json_encode($conta)); ?>)" class="btn-acao btn-editar" title="Editar">✎</button>
                                        <button onclick="excluirConta(<?php echo $conta['id']; ?>)" class="btn-acao btn-excluir" title="Excluir">×</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="texto-vazio">Nenhuma conta a receber encontrada</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar -->
    <div id="modalConta" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="fecharModal()">&times;</span>
            <h2 id="modalTitulo">Nova Conta a Receber</h2>
            <form method="POST" action="" id="formConta">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="acao" id="formAcao" value="adicionar">
                <input type="hidden" name="id" id="formId">

                <div class="form-group">
                    <label for="descricao">Descrição *</label>
                    <input type="text" id="descricao" name="descricao" required placeholder="Ex: Venda de produto, Prestação de serviço">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="valor">Valor (R$) *</label>
                        <input type="text" id="valor" name="valor" placeholder="0,00" required>
                    </div>

                    <div class="form-group">
                        <label for="data_vencimento">Vencimento *</label>
                        <input type="date" id="data_vencimento" name="data_vencimento" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cliente_id">Cliente Cadastrado</label>
                        <select id="cliente_id" name="cliente_id" onchange="atualizarCampoCliente()">
                            <option value="">Selecione um cliente...</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?php echo $cli['id']; ?>" data-nome="<?php echo htmlspecialchars($cli['razao_social']); ?>">
                                    <?php echo htmlspecialchars($cli['razao_social']); ?>
                                    <?php if ($cli['nome_fantasia']): ?>
                                        (<?php echo htmlspecialchars($cli['nome_fantasia']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #999;">Ou digite o nome abaixo</small>
                    </div>

                    <div class="form-group">
                        <label for="cliente">Nome do Cliente</label>
                        <input type="text" id="cliente" name="cliente" placeholder="Digite manualmente se não estiver cadastrado">
                    </div>
                </div>

                <div class="form-group">
                    <label for="categoria_id">Categoria</label>
                    <select id="categoria_id" name="categoria_id">
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3" placeholder="Informações adicionais sobre o recebimento"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Marcar como Recebido -->
    <div id="modalRecebido" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <span class="modal-close" onclick="fecharModalRecebido()">&times;</span>
            <h2>Marcar como Recebido</h2>
            <form method="POST" action="" id="formRecebido">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="acao" value="receber">
                <input type="hidden" name="id" id="formRecebidoId">

                <div class="form-group">
                    <label for="data_recebimento">Data do Recebimento *</label>
                    <input type="date" id="data_recebimento" name="data_recebimento" value="<?php echo date('Y-m-d'); ?>" required>
                    <small style="color: #999;">Por padrão é hoje (<?php echo date('d/m/Y'); ?>)</small>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="fecharModalRecebido()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">Confirmar Recebimento</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalTitulo').textContent = 'Nova Conta a Receber';
            document.getElementById('formAcao').value = 'adicionar';
            document.getElementById('formConta').reset();
            document.getElementById('modalConta').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalConta').style.display = 'none';
        }

        function atualizarCampoCliente() {
            const select = document.getElementById('cliente_id');
            const clienteInput = document.getElementById('cliente');
            const selectedOption = select.options[select.selectedIndex];

            if (select.value) {
                clienteInput.value = selectedOption.getAttribute('data-nome');
            }
        }

        function editarConta(conta) {
            document.getElementById('modalTitulo').textContent = 'Editar Conta a Receber';
            document.getElementById('formAcao').value = 'editar';
            document.getElementById('formId').value = conta.id;
            document.getElementById('descricao').value = conta.descricao;
            document.getElementById('valor').value = parseFloat(conta.valor).toFixed(2).replace('.', ',');
            document.getElementById('data_vencimento').value = conta.data_vencimento;
            document.getElementById('cliente_id').value = conta.cliente_id || '';
            document.getElementById('cliente').value = conta.cliente || '';
            document.getElementById('categoria_id').value = conta.categoria_id || '';
            document.getElementById('observacoes').value = conta.observacoes || '';
            document.getElementById('modalConta').style.display = 'flex';
        }

        function excluirConta(id) {
            if (confirm('Tem certeza que deseja excluir esta conta a receber?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function marcarRecebido(id) {
            document.getElementById('formRecebidoId').value = id;
            document.getElementById('data_recebimento').value = '<?php echo date('Y-m-d'); ?>';
            document.getElementById('modalRecebido').style.display = 'flex';
        }

        function fecharModalRecebido() {
            document.getElementById('modalRecebido').style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modalConta = document.getElementById('modalConta');
            const modalRecebido = document.getElementById('modalRecebido');

            if (event.target == modalConta) {
                fecharModal();
            }
            if (event.target == modalRecebido) {
                fecharModalRecebido();
            }
        }

        // Formatar valor monetário
        document.getElementById('valor').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = (value / 100).toFixed(2);
            e.target.value = value.replace('.', ',');
        });
    </script>
</body>
</html>
