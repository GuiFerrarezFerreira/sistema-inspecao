// assets/js/main.js

// Variáveis globais
let scanner = null;
let currentChecklistId = null;
let currentItemId = null;
let criteriosContador = 0;
let fotosParaUpload = {};
let localizacaoAtual = null;
let watchId = null;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {

    // Verificar suporte a geolocalização
    if ("geolocation" in navigator) {
        console.log("Geolocalização disponível");
        
        // Verificar permissões (API moderna)
        if ('permissions' in navigator) {
            navigator.permissions.query({ name: 'geolocation' }).then(result => {
                if (result.state === 'granted') {
                    console.log('Permissão de localização já concedida');
                } else if (result.state === 'prompt') {
                    console.log('Permissão de localização será solicitada');
                } else if (result.state === 'denied') {
                    console.warn('Permissão de localização negada');
                }
            });
        }
    } else {
        console.warn("Geolocalização NÃO disponível neste navegador");
    }

    if (currentUser.tipo === 'admin') {
        carregarDados();
    } else {
        carregarMeusChecklists();
    }
});

// Funções de navegação
function showTab(tabName) {
    const tabs = document.querySelectorAll('.tab');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => tab.classList.remove('active'));
    contents.forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');
}

function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
    
    // Se for o modal de item, resetar critérios
    if (modalId === 'modalItem') {
        criteriosContador = 0;
        document.getElementById('criteriosContainer').innerHTML = '';
        adicionarCriterio();
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Funções para critérios
function adicionarCriterio() {
    const container = document.getElementById('criteriosContainer');
    const div = document.createElement('div');
    div.className = 'criterio-item';
    div.innerHTML = `
        <input type="text" name="criterios[]" placeholder="Ex: Verificar integridade física" style="width: calc(100% - 40px);">
        <button type="button" onclick="removerCriterio(this)" class="btn-danger" style="width: 30px; padding: 5px;">×</button>
    `;
    container.appendChild(div);
    criteriosContador++;
}

function removerCriterio(button) {
    if (document.querySelectorAll('.criterio-item').length > 1) {
        button.parentElement.remove();
    }
}

// Funções de formulários - EMPRESAS
document.getElementById('formEmpresa')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('api/empresas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Empresa cadastrada com sucesso!');
            closeModal('modalEmpresa');
            carregarEmpresas();
            e.target.reset();
        } else {
            alert(result.message || 'Erro ao cadastrar empresa');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao cadastrar empresa');
    }
});

// Funções de formulários - UNIDADES
document.getElementById('formUnidade')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('api/unidades.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Unidade cadastrada com sucesso!');
            closeModal('modalUnidade');
            carregarUnidades();
            e.target.reset();
        } else {
            alert(result.message || 'Erro ao cadastrar unidade');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao cadastrar unidade');
    }
});

// Funções de formulários - FUNCIONÁRIOS
document.getElementById('formFuncionario')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('api/funcionarios.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Funcionário cadastrado com sucesso!');
            closeModal('modalFuncionario');
            carregarFuncionarios();
            e.target.reset();
        } else {
            alert(result.message || 'Erro ao cadastrar funcionário');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao cadastrar funcionário');
    }
});

// Funções de formulários - ARMAZÉNS
document.getElementById('formArmazem')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('api/armazens.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Armazém cadastrado com sucesso!');
            closeModal('modalArmazem');
            carregarArmazens();
            e.target.reset();
        } else {
            alert(result.message || 'Erro ao cadastrar armazém');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao cadastrar armazém');
    }
});

// Funções de formulários - ITENS
document.getElementById('formItem')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Coletar critérios
    const criterios = [];
    document.querySelectorAll('input[name="criterios[]"]').forEach(input => {
        if (input.value.trim()) {
            criterios.push(input.value.trim());
        }
    });
    data.criterios = criterios;

    try {
        const response = await fetch('api/itens.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Item cadastrado com sucesso!');
            closeModal('modalItem');
            carregarItens();
            e.target.reset();
        } else {
            alert(result.message || 'Erro ao cadastrar item');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao cadastrar item');
    }
});

// Funções de formulários - CHECKLISTS
document.getElementById('formChecklist')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Coletar funcionários selecionados
    const checkboxesFuncionarios = document.querySelectorAll('#funcionariosCheckboxes input[type="checkbox"]:checked');
    data.funcionarios_ids = Array.from(checkboxesFuncionarios).map(cb => cb.value);

    if (data.funcionarios_ids.length === 0) {
        alert('Selecione pelo menos um funcionário responsável!');
        return;
    }
    
    // Coletar itens selecionados
    const checkboxes = document.querySelectorAll('#itensCheckboxes input[type="checkbox"]:checked');
    data.itens = Array.from(checkboxes).map(cb => cb.value);

    if (data.itens.length === 0) {
        alert('Selecione pelo menos um item!');
        return;
    }

    try {
        const response = await fetch('api/checklists.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            alert('Checklist criado com sucesso!');
            closeModal('modalChecklist');
            carregarChecklists();
            if (result.data && result.data.checklist_id) {
                verQRCodes(result.data.checklist_id);
            }
            e.target.reset();
        } else {
            alert(result.message || 'Erro ao criar checklist');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao criar checklist');
    }
});

// Função principal de carregamento de dados
function carregarDados() {
    carregarEmpresas();
    carregarUnidades();
    carregarFuncionarios();
    carregarArmazens();
    carregarItens();
    carregarChecklists();
    carregarInspecoes();
    carregarDocumentos();
}

// FUNÇÃO PARA CARREGAR UNIDADES POR EMPRESA
async function carregarUnidadesPorEmpresa(contexto) {
    const empresaId = document.getElementById(`${contexto}_empresa_id`).value;
    const selectUnidade = document.getElementById(`${contexto}_unidade_id`);
    
    if (!empresaId) {
        selectUnidade.innerHTML = '<option value="">Selecione primeiro a empresa...</option>';
        selectUnidade.disabled = true;
        return;
    }
    
    try {
        const response = await fetch(`api/unidades.php?empresa_id=${empresaId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            selectUnidade.innerHTML = '<option value="">Selecione...</option>' +
                result.data.map(u => `<option value="${u.id}">${u.nome} - ${u.cidade}/${u.estado}</option>`).join('');
            selectUnidade.disabled = false;
        }
    } catch (error) {
        console.error('Erro ao carregar unidades:', error);
        selectUnidade.innerHTML = '<option value="">Erro ao carregar unidades</option>';
    }
}

// Função para carregar EMPRESAS
async function carregarEmpresas() {
    try {
        const response = await fetch('api/empresas.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(e => `
                <div class="card">
                    <h3>${e.nome}</h3>
                    ${e.cnpj ? `<p>CNPJ: ${e.cnpj}</p>` : ''}
                    ${e.telefone ? `<p>Telefone: ${e.telefone}</p>` : ''}
                    ${e.email ? `<p>Email: ${e.email}</p>` : ''}
                    <p>Unidades: ${e.total_unidades}</p>
                    <p>Funcionários: ${e.total_funcionarios}</p>
                    <p>Armazéns: ${e.total_armazens}</p>
                    <div class="actions">
                        <button onclick="editarEmpresa(${e.id})">Editar</button>
                        <button onclick="excluirEmpresa(${e.id})" class="btn-danger">Excluir</button>
                    </div>
                </div>
            `).join('');

            document.getElementById('empresasList').innerHTML = html;

            // Atualizar todos os selects de empresa
            const selectsEmpresa = document.querySelectorAll('select[name="empresa_id"]');
            selectsEmpresa.forEach(select => {
                select.innerHTML = '<option value="">Selecione...</option>' +
                    result.data.map(e => `<option value="${e.id}">${e.nome}</option>`).join('');
            });
            
            // Atualizar select específico para funcionários
            const selectFuncionarioEmpresa = document.getElementById('funcionario_empresa_id');
            if (selectFuncionarioEmpresa) {
                selectFuncionarioEmpresa.innerHTML = '<option value="">Selecione...</option>' +
                    result.data.map(e => `<option value="${e.id}">${e.nome}</option>`).join('');
            }
            
            // Atualizar select específico para armazéns
            const selectArmazemEmpresa = document.getElementById('armazem_empresa_id');
            if (selectArmazemEmpresa) {
                selectArmazemEmpresa.innerHTML = '<option value="">Selecione...</option>' +
                    result.data.map(e => `<option value="${e.id}">${e.nome}</option>`).join('');
            }
        }
    } catch (error) {
        console.error('Erro ao carregar empresas:', error);
    }
}

// Função para carregar UNIDADES
async function carregarUnidades() {
    try {
        const response = await fetch('api/unidades.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(u => `
                <div class="card">
                    <h3>${u.nome}</h3>
                    <p>Empresa: ${u.empresa_nome}</p>
                    ${u.endereco ? `<p>Endereço: ${u.endereco}</p>` : ''}
                    <p>Cidade/UF: ${u.cidade}/${u.estado}</p>
                    ${u.cep ? `<p>CEP: ${u.cep}</p>` : ''}
                    ${u.telefone ? `<p>Telefone: ${u.telefone}</p>` : ''}
                    ${u.email ? `<p>Email: ${u.email}</p>` : ''}
                    ${u.responsavel ? `<p>Responsável: ${u.responsavel}</p>` : ''}
                    <div class="actions">
                        <button onclick="editarUnidade(${u.id})">Editar</button>
                        <button onclick="excluirUnidade(${u.id})" class="btn-danger">Excluir</button>
                    </div>
                </div>
            `).join('');

            document.getElementById('unidadesList').innerHTML = html;
        }
    } catch (error) {
        console.error('Erro ao carregar unidades:', error);
    }
}

// Função para carregar FUNCIONÁRIOS
async function carregarFuncionarios() {
    try {
        const response = await fetch('api/funcionarios.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(f => `
                <div class="card">
                    <h3>${f.nome}</h3>
                    <p><strong>Empresa:</strong> ${f.empresa_nome || 'Não definida'}</p>
                    <p><strong>Unidade:</strong> ${f.unidade_nome || 'Não definida'} ${f.unidade_localizacao ? `(${f.unidade_localizacao})` : ''}</p>
                    <p><strong>Cargo:</strong> ${f.cargo}</p>
                    <p><strong>Email:</strong> ${f.email}</p>
                    <p><strong>Usuário:</strong> ${f.usuario}</p>
                    <p><strong>Status:</strong> ${f.ativo ? 'Ativo' : 'Inativo'}</p>
                    <div class="actions">
                        <button onclick="editarFuncionario(${f.id})">Editar</button>
                        <button onclick="excluirFuncionario(${f.id})" class="btn-danger">Excluir</button>
                    </div>
                </div>
            `).join('');

            document.getElementById('funcionariosList').innerHTML = html;
        }
    } catch (error) {
        console.error('Erro ao carregar funcionários:', error);
    }
}

// Função para carregar ARMAZÉNS
async function carregarArmazens() {
    try {
        const response = await fetch('api/armazens.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(a => `
                <div class="card">
                    <h3>${a.nome}</h3>
                    <p><strong>Empresa:</strong> ${a.empresa_nome || 'Não definida'}</p>
                    <p><strong>Unidade:</strong> ${a.unidade_nome || 'Não definida'} ${a.unidade_localizacao ? `(${a.unidade_localizacao})` : ''}</p>
                    <p><strong>Código:</strong> ${a.codigo}</p>
                    <p><strong>Localização:</strong> ${a.localizacao}</p>
                    ${a.descricao ? `<p><strong>Descrição:</strong> ${a.descricao}</p>` : ''}
                    <div class="actions">
                        <button onclick="editarArmazem(${a.id})">Editar</button>
                        <button onclick="excluirArmazem(${a.id})" class="btn-danger">Excluir</button>
                    </div>
                </div>
            `).join('');

            document.getElementById('armazensList').innerHTML = html;

            // Atualizar selects de armazém
            const selectArmazem = document.querySelector('select[name="armazem_id"]');
            if (selectArmazem) {
                selectArmazem.innerHTML = '<option value="">Selecione...</option>' +
                    result.data.map(a => `<option value="${a.id}">${a.nome}</option>`).join('');
            }

            const selectArmazem2 = document.querySelector('select[id="armazem_id"]');
            if (selectArmazem2) {
                selectArmazem2.innerHTML = '<option value="">Selecione...</option>' +
                    result.data.map(a => `<option value="${a.id}">${a.nome}</option>`).join('');
            }            
        }
    } catch (error) {
        console.error('Erro ao carregar armazéns:', error);
    }
}

// Função para carregar ITENS
async function carregarItens() {
    try {
        const response = await fetch('api/itens.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(i => `
                <div class="card">
                    <h3>${i.nome}</h3>
                    <p>Armazém: ${i.armazem_nome}</p>
                    ${i.descricao ? `<p>Descrição: ${i.descricao}</p>` : ''}
                    ${i.criterios && i.criterios.length > 0 ? `
                        <p><strong>Critérios de Inspeção:</strong></p>
                        <ul style="margin-left: 20px;">
                            ${i.criterios.map(c => `<li>${c.descricao}</li>`).join('')}
                        </ul>
                    ` : ''}
                    <div class="actions">
                        <button onclick="editarItem(${i.id})">Editar</button>
                        <button onclick="excluirItem(${i.id})" class="btn-danger">Excluir</button>
                    </div>
                </div>
            `).join('');

            document.getElementById('itensList').innerHTML = html;
        }
    } catch (error) {
        console.error('Erro ao carregar itens:', error);
    }
}

// Função para carregar CHECKLISTS
async function carregarChecklists() {
    try {
        const response = await fetch('api/checklists.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(c => `
                <div class="card">
                    <h3>${c.nome}</h3>
                    <p>Armazém: ${c.armazem_nome}</p>
                    <p>Responsáveis: ${c.responsaveis_nomes || 'Não definidos'}</p>
                    <p>Periodicidade: ${c.periodicidade}</p>
                    <p>Total de itens: ${c.total_itens}</p>
                    <div class="actions">
                        <button onclick="verQRCodes(${c.id})">Ver QR Codes</button>
                        <button onclick="editarChecklist(${c.id})">Editar</button>
                        <button onclick="excluirChecklist(${c.id})" class="btn-danger">Excluir</button>
                    </div>
                </div>
            `).join('');

            document.getElementById('checklistsList').innerHTML = html;

            // Atualizar checkboxes de funcionários
            await carregarFuncionariosCheckboxes();
        }
    } catch (error) {
        console.error('Erro ao carregar checklists:', error);
    }
}

// Nova função para carregar checkboxes de funcionários
async function carregarFuncionariosCheckboxes() {
    try {
        const response = await fetch('api/funcionarios.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            // Agrupar por empresa/unidade
            const funcionariosPorEmpresa = {};
            
            result.data.forEach(f => {
                const key = f.empresa_nome || 'Sem empresa';
                if (!funcionariosPorEmpresa[key]) {
                    funcionariosPorEmpresa[key] = [];
                }
                funcionariosPorEmpresa[key].push(f);
            });
            
            let html = '';
            for (const empresa in funcionariosPorEmpresa) {
                html += `<div style="margin-bottom: 15px;">
                    <strong>${empresa}</strong><br>`;
                
                funcionariosPorEmpresa[empresa].forEach(f => {
                    html += `
                        <label style="display: block; margin: 5px 0 5px 20px;">
                            <input type="checkbox" name="funcionarios_ids[]" value="${f.id}">
                            ${f.nome} - ${f.cargo}
                        </label>
                    `;
                });
                
                html += '</div>';
            }
            
            const container = document.getElementById('funcionariosCheckboxes');
            if (container) {
                container.innerHTML = html;
            }
        }
    } catch (error) {
        console.error('Erro ao carregar funcionários:', error);
    }
}

// Função para carregar itens de um armazém específico
async function carregarItensArmazem(armazemId) {
    if (!armazemId) {
        document.getElementById('itensCheckboxes').innerHTML = '';
        return;
    }

    try {
        const response = await fetch(`api/itens.php?armazem_id=${armazemId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(i => `
                <label style="display: block; margin: 5px 0;">
                    <input type="checkbox" value="${i.id}"> ${i.nome}
                </label>
            `).join('');

            document.getElementById('itensCheckboxes').innerHTML = html;
        }
    } catch (error) {
        console.error('Erro ao carregar itens do armazém:', error);
    }
}

// Função para carregar checklists do funcionário
async function carregarMeusChecklists() {
    try {
        const response = await fetch('api/checklists.php?funcionario=1');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(c => {
                let statusClass = '';
                let statusText = '';
                
                if (c.status === 'atrasado') {
                    statusClass = 'alert-error';
                    statusText = 'Atrasado';
                } else if (c.status === 'hoje') {
                    statusClass = 'alert-info';
                    statusText = 'Para hoje';
                } else {
                    statusClass = 'alert-success';
                    statusText = 'No prazo';
                }
                
                return `
                    <div class="card">
                        <h3>${c.nome}</h3>
                        <p>Armazém: ${c.armazem_nome}</p>
                        <p>Periodicidade: ${c.periodicidade}</p>
                        <p>Total de itens: ${c.total_itens}</p>
                        <div class="alert ${statusClass}">${statusText}</div>
                        <button onclick="iniciarInspecao(${c.id})" class="btn-success">
                            ${c.tem_pendente ? 'Continuar Inspeção' : 'Iniciar Inspeção'}
                        </button>
                    </div>
                `;
            }).join('');

            document.getElementById('meusChecklists').innerHTML = html;
        }
    } catch (error) {
        console.error('Erro ao carregar checklists:', error);
    }
}

// Funções de QR Code
async function verQRCodes(checklistId) {
    window.open(`api/qrcode-print.php?checklist_id=${checklistId}`, '_blank');
}

function imprimirQRCodes() {
    window.print();
}

// Funções de inspeção
async function iniciarInspecao(checklistId) {
    currentChecklistId = checklistId;
    
    // Solicitar permissão de localização e obter posição inicial
    try {
        await obterLocalizacao();
        iniciarMonitoramentoLocalizacao();
        
        // Mostrar indicador de GPS ativo
        mostrarIndicadorGPS(true);
    } catch (error) {
        console.error('Erro ao obter localização:', error);
        if (!confirm('Não foi possível obter sua localização. Deseja continuar mesmo assim?')) {
            return;
        }
    }
    
    try {
        // Iniciar ou continuar inspeção com localização
        const iniciarResponse = await fetch('api/inspecoes.php?iniciar=1', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                checklist_id: checklistId,
                localizacao: localizacaoAtual 
            })
        });
        const iniciarResult = await iniciarResponse.json();
        
        if (!iniciarResult.success) {
            throw new Error(iniciarResult.message || 'Erro ao iniciar inspeção');
        }
        
        // Buscar itens do checklist
        const response = await fetch(`api/inspecoes.php?checklist_id=${checklistId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const inspecaoId = result.data.inspecao_id || iniciarResult.data.inspecao_id;
            const itens = result.data.itens;
            
            const html = `
                <div class="alert alert-info">
                    Escaneie o QR Code de cada item para realizar a inspeção
                </div>
                
                ${localizacaoAtual ? `
                    <div class="localizacao-info">
                        <strong>📍 Localização atual:</strong> 
                        Lat: ${localizacaoAtual.latitude}, 
                        Lng: ${localizacaoAtual.longitude}
                        (Precisão: ±${Math.round(localizacaoAtual.precisao)}m)
                    </div>
                ` : ''}
                
                ${itens.map(item => `
                    <div class="checklist-item ${item.status ? `status-${item.status}` : ''} ${item.tem_avaria ? 'tem-avaria' : ''}" 
                         id="item-${item.item_id}" 
                         data-inspecao="${inspecaoId}">
                        <h4>${item.nome} ${item.tem_avaria ? '<span class="avaria-indicator">⚠️ Avaria</span>' : ''}</h4>
                        ${item.descricao ? `<p>${item.descricao}</p>` : ''}
                        
                        ${item.criterios && item.criterios.length > 0 ? `
                            <div class="criterios-container" style="margin: 10px 0;">
                                <p><strong>Critérios de Inspeção:</strong></p>
                                ${item.criterios.map(c => `
                                    <label style="display: block; margin: 5px 0;">
                                        <input type="checkbox" 
                                               class="criterio-checkbox" 
                                               data-criterio-id="${c.id}"
                                               data-item-id="${item.item_id}"
                                               ${c.checado ? 'checked' : ''}
                                               ${!item.status ? 'disabled' : ''}>
                                        ${c.descricao}
                                    </label>
                                `).join('')}
                            </div>
                        ` : ''}
                        
                        <button onclick="abrirScanner(${item.item_id})" class="btn-primary" ${item.status ? 'disabled' : ''}>
                            ${item.qr_code_lido ? '✓ QR Code Escaneado' : 'Escanear QR Code'}
                        </button>
                        
                        <div class="status-buttons" style="${item.status ? 'display: flex;' : 'display: none;'}" id="status-${item.item_id}">
                            <button onclick="marcarStatus(${item.item_id}, 'ok')" 
                                    class="status-ok ${item.status === 'ok' ? 'active' : ''}">
                                ✓ OK
                            </button>
                            <button onclick="marcarStatus(${item.item_id}, 'problema')" 
                                    class="status-problem ${item.status === 'problema' ? 'active' : ''}">
                                ✗ Com Problema
                            </button>
                        </div>
                        
                        <div id="observacao-${item.item_id}" style="display: block; margin-top: 10px;">
                            <textarea placeholder="Observações..." rows="3" style="width: 100%">${item.observacoes || ''}</textarea>
                        </div>
                        
                        <div class="avaria-container" id="avaria-${item.item_id}" style="${item.status === 'problema' ? 'display: block;' : 'display: none;'}">
                            <h4>Registrar Avaria</h4>
                            <div class="form-group">
                                <label>Observações sobre a avaria:</label>
                                <textarea id="avaria-obs-${item.item_id}" 
                                          placeholder="Descreva detalhadamente a avaria encontrada..." 
                                          rows="3" 
                                          style="width: 100%;"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Fotos da avaria:</label>
                                <input type="file" 
                                       id="avaria-foto-${item.item_id}" 
                                       accept="image/*" 
                                       multiple 
                                       style="display: none;" 
                                       onchange="previewFotos(${item.item_id})">
                                <button type="button" 
                                        onclick="document.getElementById('avaria-foto-${item.item_id}').click()" 
                                        class="btn-upload-foto">
                                    📷 Adicionar Fotos
                                </button>
                                <div id="preview-fotos-${item.item_id}" class="foto-preview-container">
                                    <!-- Fotos serão mostradas aqui -->
                                </div>
                            </div>
                            <button type="button" 
                                    onclick="salvarAvaria(${item.item_id})" 
                                    class="btn-primary" 
                                    style="margin-top: 10px;">
                                Salvar Avaria
                            </button>
                        </div>
                    </div>
                `).join('')}
                
                <div style="margin-top: 30px; border-top: 2px solid #ddd; padding-top: 20px;">
                    <div class="form-group">
                        <label><strong>Observações Gerais da Inspeção:</strong></label>
                        <textarea id="observacoes-gerais" 
                                  placeholder="Adicione observações gerais sobre toda a inspeção..." 
                                  rows="4" 
                                  style="width: 100%;"></textarea>
                    </div>
                    
                    <button onclick="finalizarInspecao(${inspecaoId})" 
                            class="btn-success" 
                            style="width: 100%; padding: 15px; font-size: 18px;">
                        ✓ Finalizar Inspeção
                    </button>
                </div>
            `;

            document.getElementById('inspecaoContent').innerHTML = html;
            showModal('modalInspecao');
            
            // Carregar avarias existentes para itens já verificados
            for (const item of itens) {
                if (item.status) {
                    // Habilitar checkboxes para itens já verificados
                    document.querySelectorAll(`#item-${item.item_id} .criterio-checkbox`).forEach(cb => {
                        cb.disabled = false;
                    });
                    
                    // Carregar avarias se houver
                    if (item.tem_avaria) {
                        await carregarAvaria(inspecaoId, item.item_id);
                    }
                }
            }
            
            // Adicionar event listener para mostrar/ocultar container de avaria
            document.querySelectorAll('.status-buttons button').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.closest('.checklist-item').id.split('-')[1];
                    const avariaContainer = document.getElementById(`avaria-${itemId}`);
                    
                    if (this.classList.contains('status-problem')) {
                        avariaContainer.style.display = 'block';
                    } else {
                        avariaContainer.style.display = 'none';
                    }
                });
            });
            
            // Auto-save das observações
            let saveTimeout;
            document.querySelectorAll('textarea[id^="observacao-"]').forEach(textarea => {
                textarea.addEventListener('input', function() {
                    clearTimeout(saveTimeout);
                    const itemId = this.id.split('-')[1];
                    const itemEl = document.getElementById(`item-${itemId}`);
                    
                    // Só salva automaticamente se o item já foi verificado
                    if (itemEl.classList.contains('status-ok') || itemEl.classList.contains('status-problem')) {
                        saveTimeout = setTimeout(() => {
                            salvarObservacaoItem(itemId);
                        }, 2000); // Salva após 2 segundos de inatividade
                    }
                });
            });
            
        } else {
            throw new Error('Erro ao carregar itens do checklist');
        }
    } catch (error) {
        console.error('Erro ao iniciar inspeção:', error);
        alert('Erro ao carregar inspeção: ' + error.message);
        pararMonitoramentoLocalizacao();
        mostrarIndicadorGPS(false);
    }
}

// Função auxiliar para salvar apenas observações de um item
async function salvarObservacaoItem(itemId) {
    const item = document.getElementById(`item-${itemId}`);
    const inspecaoId = item.dataset.inspecao;
    const status = item.classList.contains('status-ok') ? 'ok' : 
                   item.classList.contains('status-problem') ? 'problema' : null;
    
    if (!status) return;
    
    const observacoes = document.querySelector(`#observacao-${itemId} textarea`)?.value || '';
    
    try {
        // Obter localização atual se disponível
        let localizacao = null;
        if (localizacaoAtual) {
            localizacao = { ...localizacaoAtual };
        }
        
        // Coletar critérios marcados
        const criteriosChecados = {};
        document.querySelectorAll(`#item-${itemId} .criterio-checkbox`).forEach(cb => {
            criteriosChecados[cb.dataset.criterioId] = cb.checked;
        });
        
        await fetch('api/inspecoes.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                inspecao_id: inspecaoId,
                item_id: itemId,
                status: status,
                observacoes: observacoes,
                qr_code_lido: true,
                criterios_checados: criteriosChecados,
                localizacao: localizacao
            })
        });
        
        console.log(`Observações do item ${itemId} salvas automaticamente`);
    } catch (error) {
        console.error('Erro ao salvar observações:', error);
    }
}

function abrirScanner(itemId) {
    currentItemId = itemId;
    showModal('modalScanner');
    iniciarScanner();
}

async function iniciarScanner() {
    const video = document.getElementById('scanner-video');
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: 'environment' } 
        });
        video.srcObject = stream;
        video.play();

        scanner = new ZXing.BrowserQRCodeReader();
        scanner.decodeFromVideoDevice(undefined, video, (result, error) => {
            if (result) {
                processarQRCode(result.text);
                closeScanner();
            }
        });
    } catch (error) {
        console.error('Erro ao acessar câmera:', error);
        alert('Não foi possível acessar a câmera. Liberando item para inspeção manual...');
        closeScanner();
        // Liberar item para inspeção manual
        document.getElementById(`status-${currentItemId}`).style.display = 'flex';
        // Habilitar checkboxes dos critérios
        document.querySelectorAll(`#item-${currentItemId} .criterio-checkbox`).forEach(cb => {
            cb.disabled = false;
        });
    }
}

function closeScanner() {
    if (scanner) {
        scanner.reset();
        scanner = null;
    }
    
    const video = document.getElementById('scanner-video');
    if (video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }
    
    closeModal('modalScanner');
}

async function processarQRCode(data) {
    try {
        const qrData = JSON.parse(data);
        const itemEl = document.getElementById(`item-${currentItemId}`);
        const inspecaoId = itemEl.dataset.inspecao;
        
        // Verificar QR code
        const response = await fetch('api/inspecoes.php?verificar_qr=1', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                qr_data: data,
                inspecao_id: inspecaoId
            })
        });
        
        const result = await response.json();
        if (result.success && result.data.item_id == currentItemId) {
            document.getElementById(`status-${currentItemId}`).style.display = 'flex';
            // Habilitar checkboxes dos critérios
            document.querySelectorAll(`#item-${currentItemId} .criterio-checkbox`).forEach(cb => {
                cb.disabled = false;
            });
        } else {
            alert('QR Code não corresponde ao item selecionado!');
        }
    } catch (error) {
        alert('QR Code inválido!');
    }
}

async function marcarStatus(itemId, status) {
    const item = document.getElementById(`item-${itemId}`);
    const inspecaoId = item.dataset.inspecao;
    
    // Obter localização atual
    let localizacao = null;
    try {
        localizacao = await obterLocalizacao();
    } catch (error) {
        console.warn('Não foi possível obter localização para este item');
    }

    item.classList.remove('status-ok', 'status-problem');
    item.classList.add(`status-${status}`);
    
    // Coletar critérios marcados
    const criteriosChecados = {};
    document.querySelectorAll(`#item-${itemId} .criterio-checkbox`).forEach(cb => {
        criteriosChecados[cb.dataset.criterioId] = cb.checked;
    });
    
    // Salvar status com localização
    try {
        const observacoes = document.querySelector(`#observacao-${itemId} textarea`)?.value || '';
        
        await fetch('api/inspecoes.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                inspecao_id: inspecaoId,
                item_id: itemId,
                status: status,
                observacoes: observacoes,
                qr_code_lido: true,
                criterios_checados: criteriosChecados,
                localizacao: localizacao
            })
        });
    } catch (error) {
        console.error('Erro ao salvar status:', error);
    }
}

async function finalizarInspecao(inspecaoId) {
    // Parar monitoramento de localização
    pararMonitoramentoLocalizacao();
    mostrarIndicadorGPS(false);
    
    // Obter localização final
    let localizacaoFinal = null;
    try {
        localizacaoFinal = await obterLocalizacao();
    } catch (error) {
        console.warn('Não foi possível obter localização final');
    }

    const itens = document.querySelectorAll('.checklist-item');
    const resultados = [];

    itens.forEach(item => {
        const itemId = item.id.split('-')[1];
        const status = item.classList.contains('status-ok') ? 'ok' : 
                     item.classList.contains('status-problem') ? 'problema' : null;
        const observacao = document.querySelector(`#observacao-${itemId} textarea`)?.value || '';
        
        // Coletar critérios marcados
        const criteriosChecados = {};
        item.querySelectorAll('.criterio-checkbox').forEach(cb => {
            criteriosChecados[cb.dataset.criterioId] = cb.checked;
        });

        if (status) {
            resultados.push({
                item_id: itemId,
                status: status,
                observacoes: observacao,
                qr_code_lido: true,
                criterios_checados: criteriosChecados
            });
        }
    });

    try {
        const response = await fetch('api/inspecoes.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                inspecao_id: inspecaoId,
                resultados: resultados,
                localizacao_final: localizacaoFinal
            })
        });

        const result = await response.json();
        if (result.success) {
            alert('Inspeção finalizada com sucesso!');
            closeModal('modalInspecao');
            carregarMeusChecklists();
        } else {
            alert(result.message || 'Erro ao finalizar inspeção');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao finalizar inspeção');
    }
}


// Função para mostrar indicador de GPS
function mostrarIndicadorGPS(ativo) {
    let indicador = document.getElementById('gps-indicator');
    
    if (!indicador) {
        indicador = document.createElement('div');
        indicador.id = 'gps-indicator';
        indicador.style.cssText = `
            position: fixed;
            top: 70px;
            right: 20px;
            background: ${ativo ? '#2ecc71' : '#e74c3c'};
            color: white;
            padding: 10px 15px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            z-index: 1000;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        document.body.appendChild(indicador);
    }
    
    indicador.innerHTML = `
        <span style="display: inline-block; width: 10px; height: 10px; background: white; border-radius: 50%; ${ativo ? 'animation: pulse 1.5s infinite;' : ''}"></span>
        GPS ${ativo ? 'Ativo' : 'Inativo'}
    `;
    
    indicador.style.background = ativo ? '#2ecc71' : '#e74c3c';
    indicador.style.display = ativo ? 'flex' : 'none';
}

// Adicionar CSS para animação do indicador GPS
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% {
            opacity: 1;
            transform: scale(1);
        }
        50% {
            opacity: 0.5;
            transform: scale(1.2);
        }
        100% {
            opacity: 1;
            transform: scale(1);
        }
    }
    
    .localizacao-info {
        background: #e7f3ff;
        border: 1px solid #b8daff;
        padding: 10px;
        margin: 10px 0;
        border-radius: 4px;
        font-size: 12px;
    }
    
    .localizacao-info strong {
        color: #004085;
    }
`;
document.head.appendChild(style);

// Função para carregar lista de inspeções (admin)
async function carregarInspecoes() {
    try {
        const response = await fetch('api/inspecoes.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            // Aplicar filtros
            let inspecoes = result.data;
            
            const filtroFuncionario = document.getElementById('filtroFuncionario')?.value;
            const filtroArmazem = document.getElementById('filtroArmazem')?.value;
            const filtroStatus = document.getElementById('filtroStatus')?.value;
            const filtroDataInicio = document.getElementById('filtroDataInicio')?.value;
            const filtroDataFim = document.getElementById('filtroDataFim')?.value;
            
            if (filtroFuncionario) {
                inspecoes = inspecoes.filter(i => i.funcionario_nome === filtroFuncionario);
            }
            
            if (filtroArmazem) {
                inspecoes = inspecoes.filter(i => i.armazem_nome === filtroArmazem);
            }
            
            if (filtroStatus) {
                inspecoes = inspecoes.filter(i => i.status === filtroStatus);
            }
            
            if (filtroDataInicio) {
                inspecoes = inspecoes.filter(i => i.data_inicio >= filtroDataInicio);
            }
            
            if (filtroDataFim) {
                inspecoes = inspecoes.filter(i => i.data_inicio <= filtroDataFim + ' 23:59:59');
            }
            
            const html = inspecoes.map(i => {
                const statusClass = i.status === 'concluida' ? 'alert-success' : 'alert-info';
                const statusText = i.status === 'concluida' ? 'Concluída' : 'Em Andamento';
                const conformidade = i.total_itens_verificados > 0 
                    ? Math.round(((i.total_itens_verificados - i.total_problemas) / i.total_itens_verificados) * 100)
                    : 0;
                
                return `
                    <div class="card">
                        <h3>${i.checklist_nome}</h3>
                        <p><strong>Armazém:</strong> ${i.armazem_nome}</p>
                        <p><strong>Funcionário:</strong> ${i.funcionario_nome}</p>
                        <p><strong>Início:</strong> ${formatarDataHora(i.data_inicio)}</p>
                        ${i.data_fim ? `<p><strong>Fim:</strong> ${formatarDataHora(i.data_fim)}</p>` : ''}
                        <div class="alert ${statusClass}" style="margin: 10px 0;">${statusText}</div>
                        ${i.total_itens_verificados > 0 ? `
                            <div style="margin: 10px 0;">
                                <p><strong>Itens Verificados:</strong> ${i.total_itens_verificados}</p>
                                <p><strong>Problemas Encontrados:</strong> ${i.total_problemas}</p>
                                ${i.total_avarias > 0 ? `<p style="color: #f39c12;"><strong>⚠️ Avarias Registradas:</strong> ${i.total_avarias}</p>` : ''}
                                <p><strong>Taxa de Conformidade:</strong> ${conformidade}%</p>
                            </div>
                        ` : ''}
                        <button onclick="visualizarInspecao(${i.id})" class="btn-primary">
                            Visualizar Detalhes
                        </button>
                    </div>
                `;
            }).join('');

            document.getElementById('inspecoesList').innerHTML = html || '<p>Nenhuma inspeção encontrada.</p>';
            
            // Atualizar filtros se necessário
            atualizarFiltrosInspecao(result.data);
        }
    } catch (error) {
        console.error('Erro ao carregar inspeções:', error);
    }
}

// Função para visualizar detalhes de uma inspeção
async function visualizarInspecao(inspecaoId) {
    try {
        const response = await fetch(`api/inspecao-detalhes.php?id=${inspecaoId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            const inspecao = data.inspecao;
            const resumo = data.resumo;
            const itens = data.itens;
            
            // Calcular duração
            const duracaoText = inspecao.duracao 
                ? `${inspecao.duracao.horas}h ${inspecao.duracao.minutos}min`
                : 'Em andamento';
            
            // Criar mapa se houver coordenadas
            let mapaHtml = '';
            if (inspecao.latitude && inspecao.longitude) {
                const mapId = `map-${inspecaoId}`;
                mapaHtml = `
                    <div class="mapa-container" style="margin-top: 20px;">
                        <h4>Localização da Inspeção</h4>
                        <div id="${mapId}" style="height: 300px; border: 1px solid #ddd; border-radius: 8px;"></div>
                    </div>
                `;
            }
            
            const html = `
                <div class="inspecao-header">
                    <h3>${inspecao.checklist_nome}</h3>
                    <div class="inspecao-info">
                        <div class="info-item">
                            <span class="info-label">Armazém</span>
                            <span class="info-value">${inspecao.armazem.nome} (${inspecao.armazem.codigo})</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Localização do Armazém</span>
                            <span class="info-value">${inspecao.armazem.localizacao}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Funcionário</span>
                            <span class="info-value">${inspecao.funcionario.nome}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Cargo</span>
                            <span class="info-value">${inspecao.funcionario.cargo}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Data/Hora Início</span>
                            <span class="info-value">${formatarDataHora(inspecao.data_inicio)}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Data/Hora Fim</span>
                            <span class="info-value">${inspecao.data_fim ? formatarDataHora(inspecao.data_fim) : '-'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Duração</span>
                            <span class="info-value">${duracaoText}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Periodicidade</span>
                            <span class="info-value">${capitalize(inspecao.periodicidade)}</span>
                        </div>
                        ${inspecao.latitude && inspecao.longitude ? `
                            <div class="info-item">
                                <span class="info-label">Coordenadas GPS</span>
                                <span class="info-value">
                                    <a href="https://maps.google.com/?q=${inspecao.latitude},${inspecao.longitude}" 
                                       target="_blank" 
                                       style="color: #3498db; text-decoration: none;">
                                        📍 ${inspecao.latitude}, ${inspecao.longitude}
                                    </a>
                                </span>
                            </div>
                        ` : ''}
                        ${inspecao.endereco_aproximado ? `
                            <div class="info-item">
                                <span class="info-label">Endereço Aproximado</span>
                                <span class="info-value">${inspecao.endereco_aproximado}</span>
                            </div>
                        ` : ''}
                        ${inspecao.precisao_metros ? `
                            <div class="info-item">
                                <span class="info-label">Precisão do GPS</span>
                                <span class="info-value">±${Math.round(inspecao.precisao_metros)}m</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
                
                <div class="resumo-inspecao">
                    <div class="resumo-item">
                        <div class="resumo-numero">${resumo.total_itens}</div>
                        <div class="resumo-label">Total de Itens</div>
                    </div>
                    <div class="resumo-item">
                        <div class="resumo-numero" style="color: #2ecc71;">${resumo.itens_ok}</div>
                        <div class="resumo-label">Itens OK</div>
                    </div>
                    <div class="resumo-item">
                        <div class="resumo-numero" style="color: #e74c3c;">${resumo.itens_problema}</div>
                        <div class="resumo-label">Problemas</div>
                    </div>
                    <div class="resumo-item">
                        <div class="resumo-numero" style="color: #3498db;">${resumo.taxa_conformidade}%</div>
                        <div class="resumo-label">Conformidade</div>
                    </div>
                    ${resumo.total_avarias > 0 ? `
                        <div class="resumo-item">
                            <div class="resumo-numero" style="color: #f39c12;">${resumo.total_avarias}</div>
                            <div class="resumo-label">Avarias</div>
                        </div>
                    ` : ''}
                </div>
                
                ${mapaHtml}
                
                <div class="itens-inspecionados">
                    <h4>Itens Inspecionados</h4>
                    ${itens.map((item, index) => `
                        <div class="item-resultado ${item.status} ${item.tem_avaria ? 'tem-avaria' : ''}">
                            <div class="item-info" style="flex: 1;">
                                <h4>
                                    ${item.item_nome} 
                                    ${item.tem_avaria ? '<span class="avaria-indicator">⚠️ Avaria Registrada</span>' : ''}
                                </h4>
                                ${item.item_descricao ? `<p><em>${item.item_descricao}</em></p>` : ''}
                                
                                ${item.criterios_inspecao ? `
                                    <div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">
                                        <strong>Critérios de Inspeção:</strong>
                                        <p style="margin: 5px 0;">${item.criterios_inspecao}</p>
                                    </div>
                                ` : ''}
                                
                                <div style="display: flex; gap: 20px; margin: 10px 0; font-size: 14px;">
                                    <span class="qr-indicator" style="color: ${item.qr_code_lido ? '#2ecc71' : '#e74c3c'};">
                                        ${item.qr_code_lido ? '✓ QR Code escaneado' : '⚠ QR Code não escaneado'}
                                    </span>
                                    ${item.data_verificacao ? `
                                        <span style="color: #7f8c8d;">
                                            ⏰ ${formatarDataHora(item.data_verificacao)}
                                        </span>
                                    ` : ''}
                                </div>
                                
                                ${item.latitude && item.longitude ? `
                                    <div class="localizacao-info" style="margin: 10px 0;">
                                        <strong>📍 Local da verificação:</strong>
                                        <a href="https://maps.google.com/?q=${item.latitude},${item.longitude}" 
                                           target="_blank" 
                                           style="color: #3498db; text-decoration: none;">
                                            ${item.latitude}, ${item.longitude}
                                        </a>
                                        ${item.data_geolocalizacao ? `
                                            <span style="color: #7f8c8d; font-size: 12px;">
                                                (${formatarDataHora(item.data_geolocalizacao)})
                                            </span>
                                        ` : ''}
                                    </div>
                                ` : ''}
                                
                                ${item.observacoes ? `
                                    <div class="observacoes-box">
                                        <strong>Observações:</strong>
                                        <p style="margin: 5px 0;">${item.observacoes}</p>
                                    </div>
                                ` : ''}
                                
                                ${item.tem_avaria && item.avaria ? `
                                    <div class="avaria-info" style="margin-top: 15px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                                        <h5 style="margin: 0 0 10px 0; color: #856404; font-size: 16px;">
                                            🔧 Detalhes da Avaria
                                        </h5>
                                        
                                        ${item.avaria.observacoes ? `
                                            <div style="margin: 10px 0;">
                                                <strong>Descrição da avaria:</strong>
                                                <p style="margin: 5px 0; color: #856404;">${item.avaria.observacoes}</p>
                                            </div>
                                        ` : ''}
                                        
                                        ${item.avaria.fotos && item.avaria.fotos.length > 0 ? `
                                            <div style="margin-top: 15px;">
                                                <strong>Fotos da avaria (${item.avaria.fotos.length}):</strong>
                                                <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                                                    ${item.avaria.fotos.map((foto, fotoIndex) => `
                                                        <div style="position: relative;">
                                                            <img src="${foto.url}" 
                                                                 style="width: 120px; height: 120px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid #ffeaa7; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" 
                                                                 onclick="abrirModalFoto('${foto.url}', '${item.item_nome} - Foto ${fotoIndex + 1}')"
                                                                 title="Clique para ampliar"
                                                                 alt="Foto ${fotoIndex + 1} da avaria">
                                                        </div>
                                                    `).join('')}
                                                </div>
                                            </div>
                                        ` : ''}
                                        
                                        ${item.avaria.criado_em ? `
                                            <div style="margin-top: 10px; font-size: 12px; color: #856404;">
                                                <em>Registrado em: ${formatarDataHora(item.avaria.criado_em)}</em>
                                            </div>
                                        ` : ''}
                                    </div>
                                ` : ''}
                            </div>
                            
                            <div class="item-status" style="min-width: 120px; text-align: center;">
                                <span class="status-badge ${item.status}" style="font-size: 16px; padding: 8px 16px;">
                                    ${item.status === 'ok' ? '✓ OK' : '✗ Problema'}
                                </span>
                            </div>
                        </div>
                    `).join('')}
                    
                    ${itens.length === 0 ? `
                        <div class="alert alert-info">
                            Nenhum item foi inspecionado.
                        </div>
                    ` : ''}
                </div>
                
                ${inspecao.observacoes_gerais ? `
                    <div class="observacoes-gerais">
                        <h4>Observações Gerais da Inspeção</h4>
                        <p>${inspecao.observacoes_gerais}</p>
                    </div>
                ` : ''}
                
                <div style="margin-top: 30px; display: flex; gap: 10px; justify-content: center;">
                    <button onclick="imprimirInspecao()" class="btn-imprimir">
                        🖨️ Imprimir Relatório
                    </button>
                    <button onclick="exportarInspecaoPDF(${inspecaoId})" class="btn-secondary">
                        📄 Exportar PDF
                    </button>
                    <button onclick="compartilharInspecao(${inspecaoId})" class="btn-primary">
                        📤 Compartilhar
                    </button>
                </div>
            `;
            
            document.getElementById('detalhesInspecao').innerHTML = html;
            showModal('modalVisualizarInspecao');
            
            // Se houver coordenadas, criar mapa interativo
            if (inspecao.latitude && inspecao.longitude) {
                setTimeout(() => {
                    criarMapaInspecao(`map-${inspecaoId}`, inspecao, itens);
                }, 500);
            }
        } else {
            throw new Error(result.message || 'Erro ao carregar detalhes');
        }
    } catch (error) {
        console.error('Erro ao carregar detalhes da inspeção:', error);
        alert('Erro ao carregar detalhes da inspeção: ' + error.message);
    }
}


// Função para abrir modal de foto ampliada
function abrirModalFoto(url, titulo) {
    // Criar modal se não existir
    let modalFoto = document.getElementById('modalFotoAmpliada');
    
    if (!modalFoto) {
        modalFoto = document.createElement('div');
        modalFoto.id = 'modalFotoAmpliada';
        modalFoto.className = 'modal';
        modalFoto.innerHTML = `
            <div class="modal-content" style="max-width: 90%; max-height: 90vh; padding: 0; overflow: hidden;">
                <span class="close" onclick="document.getElementById('modalFotoAmpliada').style.display='none'">&times;</span>
                <h3 id="tituloFoto" style="padding: 20px; margin: 0; background: #f8f9fa; border-bottom: 1px solid #dee2e6;"></h3>
                <div style="padding: 20px; display: flex; justify-content: center; align-items: center; background: #000;">
                    <img id="fotoAmpliada" style="max-width: 100%; max-height: 70vh; object-fit: contain;">
                </div>
            </div>
        `;
        document.body.appendChild(modalFoto);
    }
    
    document.getElementById('tituloFoto').textContent = titulo || 'Foto da Avaria';
    document.getElementById('fotoAmpliada').src = url;
    modalFoto.style.display = 'block';
}

// Funções de relatório
async function gerarRelatorio() {
    const dataInicio = document.getElementById('dataInicio').value;
    const dataFim = document.getElementById('dataFim').value;

    if (!dataInicio || !dataFim) {
        alert('Por favor, selecione o período!');
        return;
    }

    try {
        const response = await fetch(`api/relatorios.php?tipo=geral&data_inicio=${dataInicio}&data_fim=${dataFim}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const data = result.data;
            const stats = data.estatisticas;
            
            const html = `
                <div class="alert alert-success">
                    <h3>Resumo do Período</h3>
                    <p>Total de Inspeções: ${stats.total_inspecoes}</p>
                    <p>Inspeções Concluídas: ${stats.inspecoes_concluidas}</p>
                    <p>Inspeções em Andamento: ${stats.inspecoes_andamento}</p>
                    <p>Itens Verificados: ${stats.total_itens_verificados}</p>
                    <p>Itens OK: ${stats.itens_ok}</p>
                    <p>Itens com Problema: ${stats.itens_problema}</p>
                    <p>Taxa de Conformidade: ${stats.taxa_conformidade}%</p>
                </div>
            `;

            document.getElementById('relatorioContent').innerHTML = html;
        }
    } catch (error) {
        console.error('Erro ao gerar relatório:', error);
        alert('Erro ao gerar relatório');
    }
}

// Funções auxiliares de CRUD
async function excluirEmpresa(id) {
    if (confirm('Deseja realmente excluir esta empresa?')) {
        try {
            const response = await fetch('api/empresas.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Empresa excluída com sucesso!');
                carregarEmpresas();
            } else {
                alert(result.message || 'Erro ao excluir empresa');
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }
}

async function excluirUnidade(id) {
    if (confirm('Deseja realmente excluir esta unidade?')) {
        try {
            const response = await fetch('api/unidades.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Unidade excluída com sucesso!');
                carregarUnidades();
            } else {
                alert(result.message || 'Erro ao excluir unidade');
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }
}

async function excluirFuncionario(id) {
    if (confirm('Deseja realmente excluir este funcionário?')) {
        try {
            const response = await fetch('api/funcionarios.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Funcionário excluído com sucesso!');
                carregarFuncionarios();
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }
}

async function excluirArmazem(id) {
    if (confirm('Deseja realmente excluir este armazém?')) {
        try {
            const response = await fetch('api/armazens.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Armazém excluído com sucesso!');
                carregarArmazens();
            } else {
                alert(result.message || 'Erro ao excluir armazém');
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }
}

async function excluirItem(id) {
    if (confirm('Deseja realmente excluir este item?')) {
        try {
            const response = await fetch('api/itens.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Item excluído com sucesso!');
                carregarItens();
            } else {
                alert(result.message || 'Erro ao excluir item');
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }
}

async function excluirChecklist(id) {
    if (confirm('Deseja realmente excluir este checklist?')) {
        try {
            const response = await fetch('api/checklists.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Checklist excluído com sucesso!');
                carregarChecklists();
            } else {
                alert(result.message || 'Erro ao excluir checklist');
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }
}

// Funções para avarias
function previewFotos(itemId) {
    const input = document.getElementById(`avaria-foto-${itemId}`);
    const preview = document.getElementById(`preview-fotos-${itemId}`);
    
    if (!fotosParaUpload[itemId]) {
        fotosParaUpload[itemId] = [];
    }
    
    Array.from(input.files).forEach(file => {
        if (file.type.startsWith('image/')) {
            fotosParaUpload[itemId].push(file);
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.style.position = 'relative';
                div.innerHTML = `
                    <img src="${e.target.result}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                    <button onclick="removerFotoPreview(${itemId}, ${fotosParaUpload[itemId].length - 1})" 
                            style="position: absolute; top: -5px; right: -5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">
                        ×
                    </button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        }
    });
}

function removerFotoPreview(itemId, index) {
    fotosParaUpload[itemId].splice(index, 1);
    atualizarPreviewFotos(itemId);
}

function atualizarPreviewFotos(itemId) {
    const preview = document.getElementById(`preview-fotos-${itemId}`);
    preview.innerHTML = '';
    
    fotosParaUpload[itemId].forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.style.position = 'relative';
            div.innerHTML = `
                <img src="${e.target.result}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                <button onclick="removerFotoPreview(${itemId}, ${index})" 
                        style="position: absolute; top: -5px; right: -5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">
                    ×
                </button>
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

async function salvarAvaria(itemId) {
    const itemEl = document.getElementById(`item-${itemId}`);
    const inspecaoId = itemEl.dataset.inspecao;
    const observacoes = document.getElementById(`avaria-obs-${itemId}`).value;
    const fotos = fotosParaUpload[itemId] || [];
    
    if (!observacoes.trim() && fotos.length === 0) {
        alert('Por favor, adicione observações ou fotos da avaria');
        return;
    }
    
    try {
        // Primeiro salvar as observações
        const response = await fetch('api/avarias.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                inspecao_id: inspecaoId,
                item_id: itemId,
                observacoes: observacoes,
                tem_fotos: fotos.length > 0
            })
        });
        
        const result = await response.json();
        if (!result.success) {
            throw new Error(result.message);
        }
        
        // Upload das fotos
        for (const foto of fotos) {
            const formData = new FormData();
            formData.append('inspecao_id', inspecaoId);
            formData.append('item_id', itemId);
            formData.append('foto', foto);
            
            await fetch('api/avarias.php?upload_foto=1', {
                method: 'POST',
                body: formData
            });
        }
        
        alert('Avaria registrada com sucesso!');
        
        // Limpar formulário
        document.getElementById(`avaria-obs-${itemId}`).value = '';
        document.getElementById(`preview-fotos-${itemId}`).innerHTML = '';
        fotosParaUpload[itemId] = [];
        
        // Marcar item como tendo avaria
        itemEl.classList.add('tem-avaria');
        
    } catch (error) {
        console.error('Erro ao salvar avaria:', error);
        alert('Erro ao salvar avaria: ' + error.message);
    }
}

async function carregarAvaria(inspecaoId, itemId) {
    try {
        const response = await fetch(`api/avarias.php?inspecao_id=${inspecaoId}&item_id=${itemId}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const avaria = result.data;
            
            // Preencher observações
            const obsTextarea = document.getElementById(`avaria-obs-${itemId}`);
            if (obsTextarea) {
                obsTextarea.value = avaria.observacoes || '';
            }
            
            // Mostrar fotos existentes
            if (avaria.fotos && avaria.fotos.length > 0) {
                const preview = document.getElementById(`preview-fotos-${itemId}`);
                if (preview) {
                    preview.innerHTML = avaria.fotos.map(foto => `
                        <div style="position: relative;">
                            <img src="${foto.url}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px; cursor: pointer;" 
                                 onclick="window.open('${foto.url}', '_blank')">
                            <button onclick="excluirFotoAvaria(${foto.id}, ${itemId})" 
                                    style="position: absolute; top: -5px; right: -5px; background: red; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">
                                ×
                            </button>
                        </div>
                    `).join('');
                }
            }
        }
    } catch (error) {
        console.error('Erro ao carregar avaria:', error);
    }
}

async function excluirFotoAvaria(fotoId, itemId) {
    if (!confirm('Deseja realmente excluir esta foto?')) {
        return;
    }
    
    try {
        const response = await fetch(`api/avarias.php?foto_id=${fotoId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        if (result.success) {
            // Recarregar avarias
            const itemEl = document.getElementById(`item-${itemId}`);
            const inspecaoId = itemEl.dataset.inspecao;
            carregarAvaria(inspecaoId, itemId);
        } else {
            alert('Erro ao excluir foto: ' + result.message);
        }
    } catch (error) {
        console.error('Erro ao excluir foto:', error);
        alert('Erro ao excluir foto');
    }
}

// Funções para Documentos PDF
document.getElementById('formDocumentoPDF')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('api/documentos-pdf.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Documento enviado com sucesso!');
            closeModal('modalDocumentoPDF');
            carregarDocumentos();
            if (result.data && result.data.id) {
                verQRCodePDF(result.data.id);
            }
            e.target.reset();
        } else {
            alert(result.message || 'Erro ao enviar documento');
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao enviar documento');
    }
});

async function carregarDocumentos() {
    try {
        const response = await fetch('api/documentos-pdf.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(d => `
                <div class="card">
                    <h3>${d.nome}</h3>
                    ${d.descricao ? `<p>${d.descricao}</p>` : ''}
                    <p><strong>Empresa:</strong> ${d.empresa_nome || 'Todas'}</p>
                    <p><strong>Enviado por:</strong> ${d.criado_por_nome}</p>
                    <p><strong>Data:</strong> ${formatarDataHora(d.criado_em)}</p>
                    <p><strong>Tamanho:</strong> ${formatarTamanhoArquivo(d.tamanho)}</p>
                    <div class="actions">
                        <button onclick="verQRCodePDF(${d.id})" class="btn-primary">Ver QR Code</button>
                        <button onclick="baixarPDF(${d.id})" class="btn-secondary">Baixar PDF</button>
                        <button onclick="excluirDocumento(${d.id})" class="btn-danger">Excluir</button>
                    </div>
                </div>
            `).join('');
            
            document.getElementById('documentosList').innerHTML = html || '<p>Nenhum documento cadastrado.</p>';
            
            // Atualizar select de empresas no modal
            const selectEmpresa = document.querySelector('#modalDocumentoPDF select[name="empresa_id"]');
            if (selectEmpresa) {
                const empresasResponse = await fetch('api/empresas.php');
                const empresasResult = await empresasResponse.json();
                
                if (empresasResult.success && empresasResult.data) {
                    selectEmpresa.innerHTML = '<option value="">Todas as empresas</option>' +
                        empresasResult.data.map(e => `<option value="${e.id}">${e.nome}</option>`).join('');
                }
            }
        }
    } catch (error) {
        console.error('Erro ao carregar documentos:', error);
    }
}

function verQRCodePDF(documentoId) {
    window.open(`api/qrcode-pdf.php?documento_id=${documentoId}`, '_blank');
}

function baixarPDF(documentoId) {
    window.open(`api/documentos-pdf.php?download=${documentoId}`, '_blank');
}

async function excluirDocumento(id) {
    if (confirm('Deseja realmente excluir este documento?')) {
        try {
            const response = await fetch('api/documentos-pdf.php', {
                method: 'DELETE',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id: id })
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Documento excluído com sucesso!');
                carregarDocumentos();
            } else {
                alert(result.message || 'Erro ao excluir documento');
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }
}

function formatarTamanhoArquivo(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Funções de edição (placeholder)
function editarEmpresa(id) {
    alert('Função de edição será implementada');
}

function editarUnidade(id) {
    alert('Função de edição será implementada');
}

function editarFuncionario(id) {
    alert('Função de edição será implementada');
}

function editarArmazem(id) {
    alert('Função de edição será implementada');
}

function editarItem(id) {
    alert('Função de edição será implementada');
}

function editarChecklist(id) {
    alert('Função de edição será implementada');
}

// Função auxiliar para formatar data e hora
function formatarDataHora(dataHora) {
    if (!dataHora) return '-';
    const data = new Date(dataHora);
    return data.toLocaleString('pt-BR');
}

// Função auxiliar para capitalizar texto
function capitalize(text) {
    return text.charAt(0).toUpperCase() + text.slice(1);
}

// Função para imprimir relatório de inspeção
function imprimirInspecao() {
    // Adicionar classe temporária para impressão
    const modal = document.getElementById('modalVisualizarInspecao');
    modal.classList.add('print-area');
    
    // Imprimir
    window.print();
    
    // Remover classe após impressão
    setTimeout(() => {
        modal.classList.remove('print-area');
    }, 1000);
}

// Função para atualizar filtros de inspeção
function atualizarFiltrosInspecao(inspecoes) {
    // Extrair valores únicos para os filtros
    const funcionarios = [...new Set(inspecoes.map(i => i.funcionario_nome))];
    const armazens = [...new Set(inspecoes.map(i => i.armazem_nome))];
    
    // Atualizar select de funcionários
    const selectFuncionario = document.getElementById('filtroFuncionario');
    if (selectFuncionario && selectFuncionario.options.length <= 1) {
        funcionarios.forEach(f => {
            const option = document.createElement('option');
            option.value = f;
            option.textContent = f;
            selectFuncionario.appendChild(option);
        });
    }
    
    // Atualizar select de armazéns
    const selectArmazem = document.getElementById('filtroArmazem');
    if (selectArmazem && selectArmazem.options.length <= 1) {
        armazens.forEach(a => {
            const option = document.createElement('option');
            option.value = a;
            option.textContent = a;
            selectArmazem.appendChild(option);
        });
    }
}

// Função para obter a localização atual
async function obterLocalizacao() {
    return new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Geolocalização não suportada pelo navegador'));
            return;
        }
        
        navigator.geolocation.getCurrentPosition(
            position => {
                const localizacao = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    precisao: position.coords.accuracy,
                    timestamp: new Date().toISOString()
                };
                localizacaoAtual = localizacao;
                resolve(localizacao);
            },
            error => {
                console.error('Erro ao obter localização:', error);
                reject(error);
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    });
}

// Função para iniciar monitoramento de localização
function iniciarMonitoramentoLocalizacao() {
    if (!navigator.geolocation) {
        console.warn('Geolocalização não suportada');
        return;
    }
    
    watchId = navigator.geolocation.watchPosition(
        position => {
            localizacaoAtual = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                precisao: position.coords.accuracy,
                timestamp: new Date().toISOString()
            };
            console.log('Localização atualizada:', localizacaoAtual);
        },
        error => {
            console.error('Erro no monitoramento de localização:', error);
        },
        {
            enableHighAccuracy: true,
            maximumAge: 30000,
            timeout: 27000
        }
    );
}

// Função para parar monitoramento
function pararMonitoramentoLocalizacao() {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
}



// Fechar modais ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};