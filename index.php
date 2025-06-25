<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$usuario_nome = $_SESSION['nome'];
$usuario_tipo = $_SESSION['tipo'];
$usuario_id = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Checklist de Inspeção</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Tela Principal -->
    <div id="mainScreen">
        <header>
            <h1>Sistema de Checklist de Inspeção</h1>
        </header>
        
        <div class="user-info">
            <span id="userDisplay">Olá, <?php echo htmlspecialchars($usuario_nome); ?></span>
            <a href="logout.php" class="btn-secondary">Sair</a>
        </div>

        <div class="container">
            <?php if ($usuario_tipo === 'admin'): ?>
            <!-- Área do Administrador -->
            <div id="adminArea">
                <div class="tabs">
                    <button class="tab active" onclick="showTab('funcionarios')">Funcionários</button>
                    <button class="tab" onclick="showTab('armazens')">Armazéns</button>
                    <button class="tab" onclick="showTab('itens')">Itens</button>
                    <button class="tab" onclick="showTab('checklists')">Checklists</button>
                    <button class="tab" onclick="showTab('relatorios')">Relatórios</button>
                </div>

                <!-- Tab Funcionários -->
                <div id="tab-funcionarios" class="tab-content active">
                    <h2>Gerenciar Funcionários</h2>
                    <button onclick="showModal('modalFuncionario')" class="btn-success">+ Novo Funcionário</button>
                    <div id="funcionariosList" class="grid"></div>
                </div>

                <!-- Tab Armazéns -->
                <div id="tab-armazens" class="tab-content">
                    <h2>Gerenciar Armazéns</h2>
                    <button onclick="showModal('modalArmazem')" class="btn-success">+ Novo Armazém</button>
                    <div id="armazensList" class="grid"></div>
                </div>

                <!-- Tab Itens -->
                <div id="tab-itens" class="tab-content">
                    <h2>Gerenciar Itens de Inspeção</h2>
                    <button onclick="showModal('modalItem')" class="btn-success">+ Novo Item</button>
                    <div id="itensList" class="grid"></div>
                </div>

                <!-- Tab Checklists -->
                <div id="tab-checklists" class="tab-content">
                    <h2>Gerenciar Checklists</h2>
                    <button onclick="showModal('modalChecklist')" class="btn-success">+ Novo Checklist</button>
                    <div id="checklistsList" class="grid"></div>
                </div>

                <!-- Tab Relatórios -->
                <div id="tab-relatorios" class="tab-content">
                    <h2>Relatórios de Inspeção</h2>
                    <div class="form-group">
                        <label>Filtrar por período:</label>
                        <input type="date" id="dataInicio">
                        <input type="date" id="dataFim">
                        <button onclick="gerarRelatorio()">Gerar Relatório</button>
                    </div>
                    <div id="relatorioContent"></div>
                </div>
            </div>
            <?php else: ?>
            <!-- Área do Funcionário -->
            <div id="funcionarioArea">
                <h2>Meus Checklists</h2>
                <div id="meusChecklists" class="grid"></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modais -->
    <?php if ($usuario_tipo === 'admin'): ?>
    <!-- Modal Funcionário -->
    <div id="modalFuncionario" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalFuncionario')">&times;</span>
            <h2>Cadastrar Funcionário</h2>
            <form id="formFuncionario">
                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Usuário:</label>
                    <input type="text" name="usuario" required>
                </div>
                <div class="form-group">
                    <label>Senha:</label>
                    <input type="password" name="senha" required>
                </div>
                <div class="form-group">
                    <label>Cargo:</label>
                    <input type="text" name="cargo" required>
                </div>
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </div>

    <!-- Modal Armazém -->
    <div id="modalArmazem" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalArmazem')">&times;</span>
            <h2>Cadastrar Armazém</h2>
            <form id="formArmazem">
                <div class="form-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Código:</label>
                    <input type="text" name="codigo" required>
                </div>
                <div class="form-group">
                    <label>Localização:</label>
                    <input type="text" name="localizacao" required>
                </div>
                <div class="form-group">
                    <label>Descrição:</label>
                    <textarea name="descricao" rows="3"></textarea>
                </div>
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </div>

    <!-- Modal Item -->
    <div id="modalItem" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalItem')">&times;</span>
            <h2>Cadastrar Item de Inspeção</h2>
            <form id="formItem">
                <div class="form-group">
                    <label>Nome do Item:</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Armazém:</label>
                    <select name="armazem_id" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descrição:</label>
                    <textarea name="descricao" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Critérios de Inspeção:</label>
                    <textarea name="criterios" rows="3" placeholder="Ex: Verificar integridade física, limpeza, funcionamento..."></textarea>
                </div>
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </div>

    <!-- Modal Checklist -->
    <div id="modalChecklist" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalChecklist')">&times;</span>
            <h2>Criar Checklist</h2>
            <form id="formChecklist">
                <div class="form-group">
                    <label>Nome do Checklist:</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Armazém:</label>
                    <select name="armazem_id" required onchange="carregarItensArmazem(this.value)">
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Funcionário Responsável:</label>
                    <select name="funcionario_id" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Periodicidade:</label>
                    <select name="periodicidade" required>
                        <option value="diario">Diário</option>
                        <option value="semanal">Semanal</option>
                        <option value="mensal">Mensal</option>
                        <option value="anual">Anual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Itens a Inspecionar:</label>
                    <div id="itensCheckboxes"></div>
                </div>
                <button type="submit">Criar Checklist</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal Realizar Inspeção -->
    <div id="modalInspecao" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalInspecao')">&times;</span>
            <h2>Realizar Inspeção</h2>
            <div id="inspecaoContent"></div>
        </div>
    </div>

    <!-- Modal Scanner QR Code -->
    <div id="modalScanner" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeScanner()">&times;</span>
            <h2>Escanear QR Code</h2>
            <div class="scanner-container">
                <video id="scanner-video"></video>
            </div>
            <button onclick="closeScanner()" class="btn-secondary">Cancelar</button>
        </div>
    </div>

    <!-- Modal QR Codes Gerados -->
    <div id="modalQRCodes" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalQRCodes')">&times;</span>
            <h2>QR Codes Gerados</h2>
            <div id="qrCodesContent"></div>
            <button onclick="imprimirQRCodes()" class="print-button">Imprimir Todos</button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/@zxing/library@latest"></script>
    <script>
        // Dados do usuário PHP para JavaScript
        const currentUser = {
            id: <?php echo $usuario_id; ?>,
            nome: '<?php echo addslashes($usuario_nome); ?>',
            tipo: '<?php echo $usuario_tipo; ?>'
        };
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>