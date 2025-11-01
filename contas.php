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

// Processar ações (adicionar, editar, excluir, marcar como pago)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        logSeguranca('warning', 'Tentativa de ação em contas com token CSRF inválido', $usuario_id);
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
                $observacoes = limparEntrada($_POST['observacoes']);

                // Campos de recorrência
                $recorrente = isset($_POST['recorrente']) ? 1 : 0;
                $tipo_recorrencia = $_POST['tipo_recorrencia'] ?? 'mensal';
                $dia_vencimento_recorrente = $recorrente ? intval($_POST['dia_vencimento_recorrente']) : null;
                $data_fim_recorrencia = !empty($_POST['data_fim_recorrencia']) ? $_POST['data_fim_recorrencia'] : null;

                // Validar valor positivo
                if ($valor <= 0) {
                    throw new Exception('O valor deve ser maior que zero');
                }

            $stmt = $pdo->prepare("INSERT INTO contas_pagar (usuario_id, categoria_id, descricao, valor, data_vencimento, observacoes, recorrente, tipo_recorrencia, dia_vencimento_recorrente, data_fim_recorrencia) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$usuario_id, $categoria_id, $descricao, $valor, $data_vencimento, $observacoes, $recorrente, $tipo_recorrencia, $dia_vencimento_recorrente, $data_fim_recorrencia]);

                logSeguranca('info', "Conta adicionada: $descricao (R$ $valor)", $usuario_id);

                $mensagem = 'Conta adicionada com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'editar') {
                $id = intval($_POST['id']);
                $descricao = limparEntrada($_POST['descricao']);
                $valor = floatval(str_replace(',', '.', str_replace('.', '', $_POST['valor'])));
                $data_vencimento = $_POST['data_vencimento'];
                $categoria_id = intval($_POST['categoria_id']) ?: null;
                $observacoes = limparEntrada($_POST['observacoes']);

                // Campos de recorrência
                $recorrente = isset($_POST['recorrente']) ? 1 : 0;
                $tipo_recorrencia = $_POST['tipo_recorrencia'] ?? 'mensal';
                $dia_vencimento_recorrente = $recorrente ? intval($_POST['dia_vencimento_recorrente']) : null;
                $data_fim_recorrencia = !empty($_POST['data_fim_recorrencia']) ? $_POST['data_fim_recorrencia'] : null;

                // Validar valor positivo
                if ($valor <= 0) {
                    throw new Exception('O valor deve ser maior que zero');
                }

            $stmt = $pdo->prepare("UPDATE contas_pagar SET descricao = ?, valor = ?, data_vencimento = ?, categoria_id = ?, observacoes = ?, recorrente = ?, tipo_recorrencia = ?, dia_vencimento_recorrente = ?, data_fim_recorrencia = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$descricao, $valor, $data_vencimento, $categoria_id, $observacoes, $recorrente, $tipo_recorrencia, $dia_vencimento_recorrente, $data_fim_recorrencia, $id, $usuario_id]);

                logSeguranca('info', "Conta editada ID: $id", $usuario_id);

                $mensagem = 'Conta atualizada com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'excluir') {
                $id = intval($_POST['id']);
                $stmt = $pdo->prepare("DELETE FROM contas_pagar WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuario_id]);

                logSeguranca('info', "Conta excluída ID: $id", $usuario_id);

                $mensagem = 'Conta excluída com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'pagar') {
                $id = intval($_POST['id']);
                $data_pagamento = $_POST['data_pagamento'] ?? date('Y-m-d');

                $stmt = $pdo->prepare("UPDATE contas_pagar SET status = 'pago', data_pagamento = ? WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$data_pagamento, $id, $usuario_id]);

                logSeguranca('info', "Conta paga ID: $id", $usuario_id);

                $mensagem = 'Conta marcada como paga!';
                $tipo_mensagem = 'sucesso';
            }
        } catch(PDOException $e) {
            logSeguranca('error', 'Erro em contas: ' . $e->getMessage(), $usuario_id);
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
    SELECT c.*, cat.nome as categoria_nome, cat.cor as categoria_cor
    FROM contas_pagar c
    LEFT JOIN categorias cat ON c.categoria_id = cat.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY c.data_vencimento DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas a Pagar - Gestão Financeira</title>
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
                <a href="contas.php" class="nav-item active">Contas a Pagar</a>
                <a href="contas_receber.php" class="nav-item">Contas a Receber</a>
                <a href="clientes.php" class="nav-item">Clientes</a>
                <a href="categorias.php" class="nav-item">Categorias</a>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h2>Contas a Pagar</h2>
                <button class="btn btn-primary" onclick="abrirModal()">+ Nova Conta</button>
            </div>

            <!-- Filtros -->
            <div class="filtros">
                <form method="GET" action="" class="filtros-form">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">Todos os Status</option>
                        <option value="pendente" <?php echo $filtro_status == 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="pago" <?php echo $filtro_status == 'pago' ? 'selected' : ''; ?>>Pago</option>
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
                        <a href="contas.php" class="btn-limpar">Limpar Filtros</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Lista de contas -->
            <div class="table-container">
                <table class="table-contas">
                    <thead>
                        <tr>
                            <th>Descrição</th>
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
                                        <?php if ($conta['recorrente']): ?>
                                            <span style="display: inline-block; margin-left: 5px; padding: 2px 8px; background: #667eea; color: white; border-radius: 10px; font-size: 11px; font-weight: 600;">
                                                🔄 RECORRENTE
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($conta['observacoes']): ?>
                                            <br><small class="observacao"><?php echo htmlspecialchars($conta['observacoes']); ?></small>
                                        <?php endif; ?>
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
                                        <?php if ($conta['status'] != 'pago'): ?>
                                            <button onclick="marcarPago(<?php echo $conta['id']; ?>)" class="btn-acao btn-pagar" title="Marcar como pago">✓</button>
                                        <?php endif; ?>
                                        <button onclick="editarConta(<?php echo htmlspecialchars(json_encode($conta)); ?>)" class="btn-acao btn-editar" title="Editar">✎</button>
                                        <button onclick="excluirConta(<?php echo $conta['id']; ?>)" class="btn-acao btn-excluir" title="Excluir">×</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="texto-vazio">Nenhuma conta encontrada</td>
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
            <h2 id="modalTitulo">Nova Conta</h2>
            <form method="POST" action="" id="formConta">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="acao" id="formAcao" value="adicionar">
                <input type="hidden" name="id" id="formId">

                <div class="form-group">
                    <label for="descricao">Descrição *</label>
                    <input type="text" id="descricao" name="descricao" required>
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
                    <textarea id="observacoes" name="observacoes" rows="3"></textarea>
                </div>

                <!-- Seção de Recorrência -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e0e0e0;">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                            <input type="checkbox" id="recorrente" name="recorrente" value="1" onchange="toggleRecorrencia()">
                            <strong>Conta Recorrente (Fixa Mensal)</strong>
                        </label>
                        <small style="color: #999; display: block; margin-top: 5px;">Marque se esta conta se repete todos os meses (ex: aluguel, internet, etc.)</small>
                    </div>

                    <div id="campos_recorrencia" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="tipo_recorrencia">Tipo de Recorrência *</label>
                                <select id="tipo_recorrencia" name="tipo_recorrencia">
                                    <option value="mensal">Mensal</option>
                                    <option value="bimestral">Bimestral (a cada 2 meses)</option>
                                    <option value="trimestral">Trimestral (a cada 3 meses)</option>
                                    <option value="semestral">Semestral (a cada 6 meses)</option>
                                    <option value="anual">Anual (todo ano)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="dia_vencimento_recorrente">Dia do Vencimento *</label>
                                <input type="number" id="dia_vencimento_recorrente" name="dia_vencimento_recorrente" min="1" max="31" placeholder="Ex: 10">
                                <small style="color: #999; display: block; margin-top: 3px;">Dia do mês para vencer (1 a 31)</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="data_fim_recorrencia">Data de Fim (Opcional)</label>
                            <input type="date" id="data_fim_recorrencia" name="data_fim_recorrencia">
                            <small style="color: #999; display: block; margin-top: 3px;">Deixe em branco se a conta é indefinida</small>
                        </div>

                        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 10px;">
                            <p style="margin: 0; font-size: 13px; color: #856404;">
                                <strong>ℹ️ Como funciona:</strong><br>
                                Esta conta será salva como modelo. Todo mês, o sistema pode gerar automaticamente uma nova conta com base neste modelo.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleRecorrencia() {
            const checkbox = document.getElementById('recorrente');
            const campos = document.getElementById('campos_recorrencia');
            campos.style.display = checkbox.checked ? 'block' : 'none';

            // Se marcar recorrente, pegar o dia do vencimento automaticamente
            if (checkbox.checked) {
                const dataVencimento = document.getElementById('data_vencimento').value;
                if (dataVencimento) {
                    const dia = new Date(dataVencimento + 'T00:00:00').getDate();
                    document.getElementById('dia_vencimento_recorrente').value = dia;
                }
            }
        }

        function abrirModal() {
            document.getElementById('modalTitulo').textContent = 'Nova Conta';
            document.getElementById('formAcao').value = 'adicionar';
            document.getElementById('formConta').reset();
            document.getElementById('recorrente').checked = false;
            document.getElementById('campos_recorrencia').style.display = 'none';
            document.getElementById('modalConta').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalConta').style.display = 'none';
        }

        function editarConta(conta) {
            document.getElementById('modalTitulo').textContent = 'Editar Conta';
            document.getElementById('formAcao').value = 'editar';
            document.getElementById('formId').value = conta.id;
            document.getElementById('descricao').value = conta.descricao;
            document.getElementById('valor').value = parseFloat(conta.valor).toFixed(2).replace('.', ',');
            document.getElementById('data_vencimento').value = conta.data_vencimento;
            document.getElementById('categoria_id').value = conta.categoria_id || '';
            document.getElementById('observacoes').value = conta.observacoes || '';

            // Campos de recorrência
            document.getElementById('recorrente').checked = conta.recorrente == 1;
            document.getElementById('tipo_recorrencia').value = conta.tipo_recorrencia || 'mensal';
            document.getElementById('dia_vencimento_recorrente').value = conta.dia_vencimento_recorrente || '';
            document.getElementById('data_fim_recorrencia').value = conta.data_fim_recorrencia || '';
            document.getElementById('campos_recorrencia').style.display = conta.recorrente == 1 ? 'block' : 'none';

            document.getElementById('modalConta').style.display = 'flex';
        }

        function excluirConta(id) {
            if (confirm('Tem certeza que deseja excluir esta conta?')) {
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

        function marcarPago(id) {
            const dataPagamento = prompt('Data do pagamento (deixe em branco para hoje):');
            if (dataPagamento !== null) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="acao" value="pagar">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="data_pagamento" value="${dataPagamento || '<?php echo date('Y-m-d'); ?>'}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalConta');
            if (event.target == modal) {
                fecharModal();
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
