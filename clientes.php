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

// Verificar se a tabela clientes existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'clientes'");
    if ($stmt->rowCount() == 0) {
        $mensagem = 'Módulo de Clientes não instalado. Execute o arquivo adicionar_clientes.sql';
        $tipo_mensagem = 'erro';
    }
} catch(PDOException $e) {
    $mensagem = 'Erro ao verificar módulo de clientes: ' . $e->getMessage();
    $tipo_mensagem = 'erro';
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($mensagem)) {
    // Validar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        logSeguranca('warning', 'Tentativa de ação em clientes com token CSRF inválido', $usuario_id);
        $mensagem = 'Token de segurança inválido!';
        $tipo_mensagem = 'erro';
    } else {
        $acao = $_POST['acao'] ?? '';

        try {
            if ($acao == 'adicionar') {
                $razao_social = limparEntrada($_POST['razao_social']);
                $nome_fantasia = limparEntrada($_POST['nome_fantasia']);
                $tipo_pessoa = $_POST['tipo_pessoa'];
                $cnpj = limparEntrada($_POST['cnpj']);
                $cpf = limparEntrada($_POST['cpf']);
                $email = limparEntrada($_POST['email']);
                $telefone = limparEntrada($_POST['telefone']);
                $celular = limparEntrada($_POST['celular']);
                $cep = limparEntrada($_POST['cep']);
                $endereco = limparEntrada($_POST['endereco']);
                $numero = limparEntrada($_POST['numero']);
                $complemento = limparEntrada($_POST['complemento']);
                $bairro = limparEntrada($_POST['bairro']);
                $cidade = limparEntrada($_POST['cidade']);
                $estado = limparEntrada($_POST['estado']);
                $observacoes = limparEntrada($_POST['observacoes']);

                $stmt = $pdo->prepare("
                    INSERT INTO clientes (
                        usuario_id, razao_social, nome_fantasia, tipo_pessoa,
                        cnpj, cpf, email, telefone, celular,
                        cep, endereco, numero, complemento, bairro, cidade, estado,
                        observacoes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $usuario_id, $razao_social, $nome_fantasia, $tipo_pessoa,
                    $cnpj, $cpf, $email, $telefone, $celular,
                    $cep, $endereco, $numero, $complemento, $bairro, $cidade, $estado,
                    $observacoes
                ]);

                logSeguranca('info', "Cliente adicionado: $razao_social", $usuario_id);

                $mensagem = 'Cliente adicionado com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'editar') {
                $id = intval($_POST['id']);
                $razao_social = limparEntrada($_POST['razao_social']);
                $nome_fantasia = limparEntrada($_POST['nome_fantasia']);
                $tipo_pessoa = $_POST['tipo_pessoa'];
                $cnpj = limparEntrada($_POST['cnpj']);
                $cpf = limparEntrada($_POST['cpf']);
                $email = limparEntrada($_POST['email']);
                $telefone = limparEntrada($_POST['telefone']);
                $celular = limparEntrada($_POST['celular']);
                $cep = limparEntrada($_POST['cep']);
                $endereco = limparEntrada($_POST['endereco']);
                $numero = limparEntrada($_POST['numero']);
                $complemento = limparEntrada($_POST['complemento']);
                $bairro = limparEntrada($_POST['bairro']);
                $cidade = limparEntrada($_POST['cidade']);
                $estado = limparEntrada($_POST['estado']);
                $observacoes = limparEntrada($_POST['observacoes']);

                $stmt = $pdo->prepare("
                    UPDATE clientes SET
                        razao_social = ?, nome_fantasia = ?, tipo_pessoa = ?,
                        cnpj = ?, cpf = ?, email = ?, telefone = ?, celular = ?,
                        cep = ?, endereco = ?, numero = ?, complemento = ?,
                        bairro = ?, cidade = ?, estado = ?, observacoes = ?
                    WHERE id = ? AND usuario_id = ?
                ");
                $stmt->execute([
                    $razao_social, $nome_fantasia, $tipo_pessoa,
                    $cnpj, $cpf, $email, $telefone, $celular,
                    $cep, $endereco, $numero, $complemento,
                    $bairro, $cidade, $estado, $observacoes,
                    $id, $usuario_id
                ]);

                logSeguranca('info', "Cliente editado ID: $id", $usuario_id);

                $mensagem = 'Cliente atualizado com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'alternar_status') {
                $id = intval($_POST['id']);

                $stmt = $pdo->prepare("UPDATE clientes SET ativo = NOT ativo WHERE id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuario_id]);

                logSeguranca('info', "Status do cliente alterado ID: $id", $usuario_id);

                $mensagem = 'Status do cliente alterado!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'excluir') {
                $id = intval($_POST['id']);

                // Verificar se há contas usando este cliente
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM contas_receber WHERE cliente_id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuario_id]);

                if ($stmt->fetchColumn() > 0) {
                    $mensagem = 'Não é possível excluir este cliente pois existem contas a receber vinculadas a ele.';
                    $tipo_mensagem = 'erro';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ? AND usuario_id = ?");
                    $stmt->execute([$id, $usuario_id]);

                    logSeguranca('info', "Cliente excluído ID: $id", $usuario_id);

                    $mensagem = 'Cliente excluído com sucesso!';
                    $tipo_mensagem = 'sucesso';
                }
            }
        } catch(PDOException $e) {
            logSeguranca('error', 'Erro em clientes: ' . $e->getMessage(), $usuario_id);
            $mensagem = 'Erro ao processar ação. Tente novamente.';
            $tipo_mensagem = 'erro';
        }
    }
}

// Gerar token CSRF
$csrf_token = gerarTokenCSRF();

// Buscar filtros
$filtro_status = $_GET['status'] ?? 'todos';
$filtro_busca = $_GET['busca'] ?? '';

// Buscar clientes com estatísticas
try {
    $sql = "
        SELECT
            c.*,
            COUNT(cr.id) as total_contas,
            COUNT(CASE WHEN cr.status = 'pendente' THEN 1 END) as contas_pendentes,
            COALESCE(SUM(CASE WHEN cr.status = 'pendente' THEN cr.valor ELSE 0 END), 0) as valor_pendente,
            COALESCE(SUM(CASE WHEN cr.status = 'recebido' THEN cr.valor ELSE 0 END), 0) as valor_recebido
        FROM clientes c
        LEFT JOIN contas_receber cr ON c.id = cr.cliente_id AND cr.usuario_id = ?
        WHERE c.usuario_id = ?
    ";

    $params = [$usuario_id, $usuario_id];

    // Filtro de status
    if ($filtro_status == 'ativos') {
        $sql .= " AND c.ativo = 1";
    } elseif ($filtro_status == 'inativos') {
        $sql .= " AND c.ativo = 0";
    }

    // Filtro de busca
    if (!empty($filtro_busca)) {
        $sql .= " AND (c.razao_social LIKE ? OR c.nome_fantasia LIKE ? OR c.cnpj LIKE ? OR c.cpf LIKE ? OR c.email LIKE ?)";
        $busca_param = "%$filtro_busca%";
        $params = array_merge($params, [$busca_param, $busca_param, $busca_param, $busca_param, $busca_param]);
    }

    $sql .= " GROUP BY c.id ORDER BY c.razao_social";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll();
} catch(PDOException $e) {
    $clientes = [];
    $mensagem = 'Erro ao buscar clientes: ' . $e->getMessage();
    $tipo_mensagem = 'erro';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - Gestão Financeira</title>
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
                <a href="contas_receber.php" class="nav-item">Contas a Receber</a>
                <a href="clientes.php" class="nav-item active">Clientes</a>
                <a href="categorias.php" class="nav-item">Categorias</a>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h2>Clientes / Pagadores</h2>
                <button class="btn btn-primary" onclick="abrirModal()">+ Novo Cliente</button>
            </div>

            <!-- Filtros -->
            <div class="filtros">
                <form method="GET" class="filtros-form">
                    <select name="status" onchange="this.form.submit()">
                        <option value="todos" <?php echo $filtro_status == 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="ativos" <?php echo $filtro_status == 'ativos' ? 'selected' : ''; ?>>Ativos</option>
                        <option value="inativos" <?php echo $filtro_status == 'inativos' ? 'selected' : ''; ?>>Inativos</option>
                    </select>

                    <input type="text" name="busca" placeholder="Buscar por nome, CNPJ, CPF..." value="<?php echo htmlspecialchars($filtro_busca); ?>">

                    <button type="submit" class="btn btn-primary">Buscar</button>

                    <?php if ($filtro_status != 'todos' || !empty($filtro_busca)): ?>
                        <a href="clientes.php" class="btn-limpar">Limpar Filtros</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Grid de clientes -->
            <div class="categorias-grid">
                <?php if (count($clientes) > 0): ?>
                    <?php foreach ($clientes as $cliente): ?>
                        <div class="categoria-card" style="border-left: 4px solid <?php echo $cliente['ativo'] ? '#2ecc71' : '#e74c3c'; ?>">
                            <div class="categoria-header">
                                <div class="categoria-info">
                                    <h3><?php echo htmlspecialchars($cliente['razao_social']); ?></h3>
                                    <?php if ($cliente['nome_fantasia']): ?>
                                        <p style="font-size: 13px; color: #666; margin: 3px 0;"><?php echo htmlspecialchars($cliente['nome_fantasia']); ?></p>
                                    <?php endif; ?>
                                    <p style="font-size: 12px; color: #999; margin: 3px 0;">
                                        <?php
                                        if ($cliente['tipo_pessoa'] == 'juridica' && $cliente['cnpj']) {
                                            echo 'CNPJ: ' . htmlspecialchars($cliente['cnpj']);
                                        } elseif ($cliente['tipo_pessoa'] == 'fisica' && $cliente['cpf']) {
                                            echo 'CPF: ' . htmlspecialchars($cliente['cpf']);
                                        }
                                        ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="badge-status <?php echo $cliente['ativo'] ? 'ativo' : 'inativo'; ?>">
                                        <?php echo $cliente['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($cliente['email'] || $cliente['telefone'] || $cliente['celular']): ?>
                                <div style="margin: 15px 0; padding: 10px 0; border-top: 1px solid #e0e0e0; border-bottom: 1px solid #e0e0e0;">
                                    <?php if ($cliente['email']): ?>
                                        <p style="font-size: 12px; color: #666; margin: 3px 0;">📧 <?php echo htmlspecialchars($cliente['email']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($cliente['telefone']): ?>
                                        <p style="font-size: 12px; color: #666; margin: 3px 0;">📞 <?php echo htmlspecialchars($cliente['telefone']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($cliente['celular']): ?>
                                        <p style="font-size: 12px; color: #666; margin: 3px 0;">📱 <?php echo htmlspecialchars($cliente['celular']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="categoria-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Contas</span>
                                    <span class="stat-valor"><?php echo $cliente['total_contas']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Pendentes</span>
                                    <span class="stat-valor"><?php echo $cliente['contas_pendentes']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">A Receber</span>
                                    <span class="stat-valor" style="color: #f39c12;">R$ <?php echo number_format($cliente['valor_pendente'], 2, ',', '.'); ?></span>
                                </div>
                            </div>

                            <div class="categoria-acoes">
                                <button onclick="verHistorico(<?php echo $cliente['id']; ?>)" class="btn-acao btn-editar" style="background: #3498db;">Histórico</button>
                                <button onclick="editarCliente(<?php echo htmlspecialchars(json_encode($cliente)); ?>)" class="btn-acao btn-editar">Editar</button>
                                <button onclick="alternarStatus(<?php echo $cliente['id']; ?>)" class="btn-acao" style="background: <?php echo $cliente['ativo'] ? '#f39c12' : '#2ecc71'; ?>">
                                    <?php echo $cliente['ativo'] ? 'Inativar' : 'Ativar'; ?>
                                </button>
                                <button onclick="excluirCliente(<?php echo $cliente['id']; ?>)" class="btn-acao btn-excluir">Excluir</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="texto-vazio">Nenhum cliente encontrado. Clique em "+ Novo Cliente" para adicionar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar Cliente -->
    <div id="modalCliente" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="fecharModal()">&times;</span>
            <h2 id="modalTitulo">Novo Cliente</h2>
            <form method="POST" action="" id="formCliente">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="acao" id="formAcao" value="adicionar">
                <input type="hidden" name="id" id="formId">

                <!-- Tipo de Pessoa -->
                <div class="form-group">
                    <label>Tipo de Pessoa *</label>
                    <div style="display: flex; gap: 20px;">
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="tipo_pessoa" value="juridica" checked onchange="alternarTipoPessoa()"> Pessoa Jurídica
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px;">
                            <input type="radio" name="tipo_pessoa" value="fisica" onchange="alternarTipoPessoa()"> Pessoa Física
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="razao_social" id="labelRazaoSocial">Razão Social *</label>
                        <input type="text" id="razao_social" name="razao_social" required placeholder="Nome completo da empresa ou pessoa">
                    </div>

                    <div class="form-group">
                        <label for="nome_fantasia" id="labelNomeFantasia">Nome Fantasia</label>
                        <input type="text" id="nome_fantasia" name="nome_fantasia" placeholder="Nome comercial">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group" id="groupCnpj">
                        <label for="cnpj">CNPJ</label>
                        <input type="text" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" maxlength="18">
                    </div>

                    <div class="form-group" id="groupCpf" style="display: none;">
                        <label for="cpf">CPF</label>
                        <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" maxlength="14">
                    </div>

                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" placeholder="cliente@empresa.com">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" placeholder="(00) 0000-0000">
                    </div>

                    <div class="form-group">
                        <label for="celular">Celular</label>
                        <input type="text" id="celular" name="celular" placeholder="(00) 00000-0000">
                    </div>
                </div>

                <h3 style="margin-top: 20px; margin-bottom: 15px; color: #333; font-size: 16px;">Endereço</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" placeholder="00000-000" maxlength="9">
                    </div>

                    <div class="form-group">
                        <label for="endereco">Endereço</label>
                        <input type="text" id="endereco" name="endereco" placeholder="Rua, Avenida...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="numero">Número</label>
                        <input type="text" id="numero" name="numero" placeholder="123">
                    </div>

                    <div class="form-group">
                        <label for="complemento">Complemento</label>
                        <input type="text" id="complemento" name="complemento" placeholder="Sala, Apto...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="bairro">Bairro</label>
                        <input type="text" id="bairro" name="bairro" placeholder="Centro...">
                    </div>

                    <div class="form-group">
                        <label for="cidade">Cidade</label>
                        <input type="text" id="cidade" name="cidade" placeholder="São Paulo...">
                    </div>
                </div>

                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado">
                        <option value="">Selecione...</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3" placeholder="Informações adicionais sobre o cliente..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="fecharModal()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Histórico do Cliente -->
    <div id="modalHistorico" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="fecharModalHistorico()">&times;</span>
            <h2 id="historicoTitulo">Histórico do Cliente</h2>
            <div id="historicoConteudo" style="max-height: 500px; overflow-y: auto;">
                <p style="text-align: center; padding: 40px; color: #999;">Carregando...</p>
            </div>
        </div>
    </div>

    <script>
        function abrirModal() {
            document.getElementById('modalTitulo').textContent = 'Novo Cliente';
            document.getElementById('formAcao').value = 'adicionar';
            document.getElementById('formCliente').reset();
            document.querySelector('input[name="tipo_pessoa"][value="juridica"]').checked = true;
            alternarTipoPessoa();
            document.getElementById('modalCliente').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalCliente').style.display = 'none';
        }

        function fecharModalHistorico() {
            document.getElementById('modalHistorico').style.display = 'none';
        }

        function alternarTipoPessoa() {
            const tipoPessoa = document.querySelector('input[name="tipo_pessoa"]:checked').value;
            const groupCnpj = document.getElementById('groupCnpj');
            const groupCpf = document.getElementById('groupCpf');
            const labelRazaoSocial = document.getElementById('labelRazaoSocial');
            const labelNomeFantasia = document.getElementById('labelNomeFantasia');

            if (tipoPessoa === 'juridica') {
                groupCnpj.style.display = 'block';
                groupCpf.style.display = 'none';
                labelRazaoSocial.textContent = 'Razão Social *';
                labelNomeFantasia.textContent = 'Nome Fantasia';
            } else {
                groupCnpj.style.display = 'none';
                groupCpf.style.display = 'block';
                labelRazaoSocial.textContent = 'Nome Completo *';
                labelNomeFantasia.textContent = 'Apelido';
            }
        }

        function editarCliente(cliente) {
            document.getElementById('modalTitulo').textContent = 'Editar Cliente';
            document.getElementById('formAcao').value = 'editar';
            document.getElementById('formId').value = cliente.id;
            document.getElementById('razao_social').value = cliente.razao_social || '';
            document.getElementById('nome_fantasia').value = cliente.nome_fantasia || '';
            document.querySelector(`input[name="tipo_pessoa"][value="${cliente.tipo_pessoa}"]`).checked = true;
            document.getElementById('cnpj').value = cliente.cnpj || '';
            document.getElementById('cpf').value = cliente.cpf || '';
            document.getElementById('email').value = cliente.email || '';
            document.getElementById('telefone').value = cliente.telefone || '';
            document.getElementById('celular').value = cliente.celular || '';
            document.getElementById('cep').value = cliente.cep || '';
            document.getElementById('endereco').value = cliente.endereco || '';
            document.getElementById('numero').value = cliente.numero || '';
            document.getElementById('complemento').value = cliente.complemento || '';
            document.getElementById('bairro').value = cliente.bairro || '';
            document.getElementById('cidade').value = cliente.cidade || '';
            document.getElementById('estado').value = cliente.estado || '';
            document.getElementById('observacoes').value = cliente.observacoes || '';
            alternarTipoPessoa();
            document.getElementById('modalCliente').style.display = 'flex';
        }

        function alternarStatus(id) {
            if (confirm('Tem certeza que deseja alterar o status deste cliente?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="acao" value="alternar_status">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function excluirCliente(id) {
            if (confirm('Tem certeza que deseja excluir este cliente?')) {
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

        function verHistorico(clienteId) {
            document.getElementById('modalHistorico').style.display = 'flex';
            document.getElementById('historicoConteudo').innerHTML = '<p style="text-align: center; padding: 40px; color: #999;">Carregando...</p>';

            // Buscar histórico via AJAX
            fetch(`buscar_historico_cliente.php?cliente_id=${clienteId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('historicoConteudo').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('historicoConteudo').innerHTML = '<p style="text-align: center; padding: 40px; color: #e74c3c;">Erro ao carregar histórico.</p>';
                });
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modalCliente = document.getElementById('modalCliente');
            const modalHistorico = document.getElementById('modalHistorico');
            if (event.target == modalCliente) {
                fecharModal();
            }
            if (event.target == modalHistorico) {
                fecharModalHistorico();
            }
        }

        // Máscaras para inputs
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 14) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });

        document.getElementById('cep').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 8) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 10) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });

        document.getElementById('celular').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{2})(\d)/, '($1) $2');
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
