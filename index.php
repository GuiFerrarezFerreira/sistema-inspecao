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
    <style>
        .criterio-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            gap: 10px;
        }
        
        .criterio-item input[type="text"] {
            flex: 1;
        }
        
        .criterio-item button {
            padding: 5px 10px;
            font-size: 18px;
            line-height: 1;
        }
        
        .criterios-container {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .checklist-item.status-ok {
            border-left: 4px solid #2ecc71;
        }
        
        .checklist-item.status-problem {
            border-left: 4px solid #e74c3c;
        }
    </style>
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
                    <button class="tab active" onclick="showTab('empresas')">Empresas</button>
                    <button class="tab" onclick="showTab('unidades')">Unidades</button>
                    <button class="tab" onclick="showTab('funcionarios')">Funcionários</button>
                    <button class="tab" onclick="showTab('armazens')">Armazéns</button>
                    <button class="tab" onclick="showTab('itens')">Itens</button>
                    <button class="tab" onclick="showTab('checklists')">Checklists</button>
                    <button class="tab" onclick="showTab('relatorios')">Relatórios</button>
                    <button class="tab" onclick="showTab('inspecoes')">Inspeções</button>                    
                </div>

                <!-- Tab Empresas -->
                <div id="tab-empresas" class="tab-content active">
                    <h2>Gerenciar Empresas</h2>
                    <button onclick="showModal('modalEmpresa')" class="btn-success">+ Nova Empresa</button>
                    <div id="empresasList" class="grid"></div>
                </div>

                <!-- Tab Unidades -->
                <div id="tab-unidades" class="tab-content">
                    <h2>Gerenciar Unidades</h2>
                    <button onclick="showModal('modalUnidade')" class="btn-success">+ Nova Unidade</button>
                    <div id="unidadesList" class="grid"></div>
                </div>

                <!-- Tab Funcionários -->
                <div id="tab-funcionarios" class="tab-content">
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

                <!-- Tab Inspeções -->
                <div id="tab-inspecoes" class="tab-content">
                    <h2>Inspeções Realizadas</h2>
                    
                    <div class="filtros-inspecao" style="margin-bottom: 20px;">
                        <div class="form-group" style="display: inline-block; margin-right: 15px;">
                            <label>Funcionário:</label>
                            <select id="filtroFuncionario" onchange="carregarInspecoes()">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: inline-block; margin-right: 15px;">
                            <label>Armazém:</label>
                            <select id="filtroArmazem" onchange="carregarInspecoes()">
                                <option value="">Todos</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: inline-block; margin-right: 15px;">
                            <label>Status:</label>
                            <select id="filtroStatus" onchange="carregarInspecoes()">
                                <option value="">Todos</option>
                                <option value="concluida">Concluída</option>
                                <option value="em_andamento">Em Andamento</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: inline-block;">
                            <label>Período:</label>
                            <input type="date" id="filtroDataInicio" onchange="carregarInspecoes()">
                            <input type="date" id="filtroDataFim" onchange="carregarInspecoes()">
                        </div>
                    </div>
                    
                    <div id="inspecoesList" class="grid"></div>
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
    <!-- Modal Empresa -->
    <div id="modalEmpresa" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalEmpresa')">&times;</span>
            <h2>Cadastrar Empresa</h2>
            <form id="formEmpresa">
                <div class="form-group">
                    <label>Nome da Empresa:</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>CNPJ:</label>
                    <input type="text" name="cnpj" placeholder="00.000.000/0001-00">
                </div>
                <div class="form-group">
                    <label>Telefone:</label>
                    <input type="text" name="telefone" placeholder="(11) 1234-5678">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email">
                </div>
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </div>

    <!-- Modal Unidade -->
    <div id="modalUnidade" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalUnidade')">&times;</span>
            <h2>Cadastrar Unidade</h2>
            <form id="formUnidade">
                <div class="form-group">
                    <label>Empresa:</label>
                    <select name="empresa_id" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nome da Unidade:</label>
                    <input type="text" name="nome" required>
                </div>
                <div class="form-group">
                    <label>Endereço:</label>
                    <input type="text" name="endereco">
                </div>
                <div class="form-group">
                    <label>Cidade:</label>
                    <input type="text" name="cidade" required>
                </div>
                <div class="form-group">
                    <label>Estado (UF):</label>
                    <select name="estado" required>
                        <option value="">Selecione...</option>
                        <option value="AC">AC</option>
                        <option value="AL">AL</option>
                        <option value="AP">AP</option>
                        <option value="AM">AM</option>
                        <option value="BA">BA</option>
                        <option value="CE">CE</option>
                        <option value="DF">DF</option>
                        <option value="ES">ES</option>
                        <option value="GO">GO</option>
                        <option value="MA">MA</option>
                        <option value="MT">MT</option>
                        <option value="MS">MS</option>
                        <option value="MG">MG</option>
                        <option value="PA">PA</option>
                        <option value="PB">PB</option>
                        <option value="PR">PR</option>
                        <option value="PE">PE</option>
                        <option value="PI">PI</option>
                        <option value="RJ">RJ</option>
                        <option value="RN">RN</option>
                        <option value="RS">RS</option>
                        <option value="RO">RO</option>
                        <option value="RR">RR</option>
                        <option value="SC">SC</option>
                        <option value="SP">SP</option>
                        <option value="SE">SE</option>
                        <option value="TO">TO</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>CEP:</label>
                    <input type="text" name="cep" placeholder="00000-000">
                </div>
                <div class="form-group">
                    <label>Telefone:</label>
                    <input type="text" name="telefone" placeholder="(11) 1234-5678">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email">
                </div>
                <div class="form-group">
                    <label>Responsável:</label>
                    <input type="text" name="responsavel">
                </div>
                <button type="submit">Cadastrar</button>
            </form>
        </div>
    </div>

    <!-- Modal Funcionário -->
    <div id="modalFuncionario" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalFuncionario')">&times;</span>
            <h2>Cadastrar Funcionário</h2>
            <form id="formFuncionario">
                <div class="form-group">
                    <label>Empresa:</label>
                    <select id="funcionario_empresa_id" onchange="carregarUnidadesPorEmpresa('funcionario')" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unidade:</label>
                    <select name="unidade_id" id="funcionario_unidade_id" required disabled>
                        <option value="">Selecione primeiro a empresa...</option>
                    </select>
                </div>
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
                    <label>Empresa:</label>
                    <select id="armazem_empresa_id" onchange="carregarUnidadesPorEmpresa('armazem')" required>
                        <option value="">Selecione...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unidade:</label>
                    <select name="unidade_id" id="armazem_unidade_id" required disabled>
                        <option value="">Selecione primeiro a empresa...</option>
                    </select>
                </div>
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
                    <div id="criteriosContainer" class="criterios-container">
                        <!-- Critérios serão adicionados dinamicamente aqui -->
                    </div>
                    <button type="button" onclick="adicionarCriterio()" class="btn-secondary">+ Adicionar Critério</button>
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
                    <select id="armazem_id" name="armazem_id" required onchange="carregarItensArmazem(this.value)">
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

    <!-- Modal Visualizar Inspeção -->
    <div id="modalVisualizarInspecao" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('modalVisualizarInspecao')">&times;</span>
            <h2>Detalhes da Inspeção</h2>
            <div id="detalhesInspecao">
                <!-- Conteúdo será carregado dinamicamente -->
            </div>
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