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

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validar token CSRF
    if (!validarTokenCSRF($_POST['csrf_token'] ?? '')) {
        logSeguranca('warning', 'Tentativa de ação em categorias com token CSRF inválido', $usuario_id);
        $mensagem = 'Token de segurança inválido!';
        $tipo_mensagem = 'erro';
    } else {
        $acao = $_POST['acao'] ?? '';

        try {
            if ($acao == 'adicionar') {
                $nome = limparEntrada($_POST['nome']);
                $cor = $_POST['cor'];

            $stmt = $pdo->prepare("INSERT INTO categorias (nome, cor, usuario_id) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $cor, $usuario_id]);

                logSeguranca('info', "Categoria adicionada: $nome", $usuario_id);

                $mensagem = 'Categoria adicionada com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'editar') {
                $id = intval($_POST['id']);
                $nome = limparEntrada($_POST['nome']);
                $cor = $_POST['cor'];

            $stmt = $pdo->prepare("UPDATE categorias SET nome = ?, cor = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$nome, $cor, $id, $usuario_id]);

                logSeguranca('info', "Categoria editada ID: $id", $usuario_id);

                $mensagem = 'Categoria atualizada com sucesso!';
                $tipo_mensagem = 'sucesso';

            } elseif ($acao == 'excluir') {
                $id = intval($_POST['id']);

                // Verificar se há contas usando esta categoria
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM contas_pagar WHERE categoria_id = ? AND usuario_id = ?");
                $stmt->execute([$id, $usuario_id]);

                if ($stmt->fetchColumn() > 0) {
                    $mensagem = 'Não é possível excluir esta categoria pois existem contas vinculadas a ela.';
                    $tipo_mensagem = 'erro';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ? AND usuario_id = ?");
                    $stmt->execute([$id, $usuario_id]);

                    logSeguranca('info', "Categoria excluída ID: $id", $usuario_id);

                    $mensagem = 'Categoria excluída com sucesso!';
                    $tipo_mensagem = 'sucesso';
                }
            }
        } catch(PDOException $e) {
            logSeguranca('error', 'Erro em categorias: ' . $e->getMessage(), $usuario_id);
            $mensagem = 'Erro ao processar ação. Tente novamente.';
            $tipo_mensagem = 'erro';
        }
    }
}

// Gerar token CSRF
$csrf_token = gerarTokenCSRF();

// Buscar categorias com estatísticas
$stmt = $pdo->prepare("
    SELECT
        c.*,
        COUNT(cp.id) as total_contas,
        COALESCE(SUM(CASE WHEN cp.status = 'pago' THEN cp.valor ELSE 0 END), 0) as total_gasto
    FROM categorias c
    LEFT JOIN contas_pagar cp ON c.id = cp.categoria_id AND cp.usuario_id = ?
    WHERE c.usuario_id = ?
    GROUP BY c.id
    ORDER BY c.nome
");
$stmt->execute([$usuario_id, $usuario_id]);
$categorias = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - Gestão Financeira</title>
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
                <a href="categorias.php" class="nav-item active">Categorias</a>
            </div>

            <?php if ($mensagem): ?>
                <div class="mensagem <?php echo $tipo_mensagem; ?>">
                    <?php echo $mensagem; ?>
                </div>
            <?php endif; ?>

            <div class="header-section">
                <h2>Categorias de Despesas</h2>
                <button class="btn btn-primary" onclick="abrirModal()">+ Nova Categoria</button>
            </div>

            <!-- Grid de categorias -->
            <div class="categorias-grid">
                <?php foreach ($categorias as $cat): ?>
                    <div class="categoria-card" style="border-left: 4px solid <?php echo $cat['cor']; ?>">
                        <div class="categoria-header">
                            <div class="categoria-icone" style="background-color: <?php echo $cat['cor']; ?>20;">
                                <div class="categoria-cor" style="background-color: <?php echo $cat['cor']; ?>"></div>
                            </div>
                            <div class="categoria-info">
                                <h3><?php echo htmlspecialchars($cat['nome']); ?></h3>
                            </div>
                        </div>
                        <div class="categoria-stats">
                            <div class="stat-item">
                                <span class="stat-label">Contas</span>
                                <span class="stat-valor"><?php echo $cat['total_contas']; ?></span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">Total Gasto</span>
                                <span class="stat-valor">R$ <?php echo number_format($cat['total_gasto'], 2, ',', '.'); ?></span>
                            </div>
                        </div>
                        <div class="categoria-acoes">
                            <button onclick="editarCategoria(<?php echo htmlspecialchars(json_encode($cat)); ?>)" class="btn-acao btn-editar">Editar</button>
                            <button onclick="excluirCategoria(<?php echo $cat['id']; ?>)" class="btn-acao btn-excluir">Excluir</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar -->
    <div id="modalCategoria" class="modal">
        <div class="modal-content modal-small">
            <span class="modal-close" onclick="fecharModal()">&times;</span>
            <h2 id="modalTitulo">Nova Categoria</h2>
            <form method="POST" action="" id="formCategoria">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="acao" id="formAcao" value="adicionar">
                <input type="hidden" name="id" id="formId">

                <div class="form-group">
                    <label for="nome">Nome da Categoria *</label>
                    <input type="text" id="nome" name="nome" required placeholder="Ex: Alimentação, Transporte...">
                </div>

                <div class="form-group">
                    <label for="cor">Cor *</label>
                    <div class="cor-picker">
                        <input type="color" id="cor" name="cor" value="#667eea" required>
                        <span id="corValor">#667eea</span>
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
        function abrirModal() {
            document.getElementById('modalTitulo').textContent = 'Nova Categoria';
            document.getElementById('formAcao').value = 'adicionar';
            document.getElementById('formCategoria').reset();
            document.getElementById('cor').value = '#667eea';
            document.getElementById('corValor').textContent = '#667eea';
            document.getElementById('modalCategoria').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalCategoria').style.display = 'none';
        }

        function editarCategoria(categoria) {
            document.getElementById('modalTitulo').textContent = 'Editar Categoria';
            document.getElementById('formAcao').value = 'editar';
            document.getElementById('formId').value = categoria.id;
            document.getElementById('nome').value = categoria.nome;
            document.getElementById('cor').value = categoria.cor;
            document.getElementById('corValor').textContent = categoria.cor;
            document.getElementById('modalCategoria').style.display = 'flex';
        }

        function excluirCategoria(id) {
            if (confirm('Tem certeza que deseja excluir esta categoria?')) {
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

        // Atualizar valor da cor
        document.getElementById('cor').addEventListener('input', function(e) {
            document.getElementById('corValor').textContent = e.target.value;
        });

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalCategoria');
            if (event.target == modal) {
                fecharModal();
            }
        }
    </script>
</body>
</html>
