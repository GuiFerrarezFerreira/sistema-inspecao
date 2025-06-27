// assets/js/main.js

// Variáveis globais
let scanner = null;
let currentChecklistId = null;
let currentItemId = null;
let criteriosContador = 0;

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
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
                    <p>Empresa: ${f.empresa_nome || 'Não definida'}</p>
                    <p>Cargo: ${f.cargo}</p>
                    <p>Email: ${f.email}</p>
                    <p>Usuário: ${f.usuario}</p>
                    <p>Status: ${f.ativo ? 'Ativo' : 'Inativo'}</p>
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
                    <p>Empresa: ${a.empresa_nome || 'Não definida'}</p>
                    <p>Código: ${a.codigo}</p>
                    <p>Localização: ${a.localizacao}</p>
                    ${a.descricao ? `<p>Descrição: ${a.descricao}</p>` : ''}
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
                    <p>Responsável: ${c.funcionario_nome}</p>
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

            // Atualizar select de funcionários
            const selectFuncionario = document.querySelector('select[name="funcionario_id"]');
            if (selectFuncionario) {
                const funcionariosResponse = await fetch('api/funcionarios.php');
                const funcionariosResult = await funcionariosResponse.json();
                
                if (funcionariosResult.success && funcionariosResult.data) {
                    selectFuncionario.innerHTML = '<option value="">Selecione...</option>' +
                        funcionariosResult.data.map(f => `<option value="${f.id}">${f.nome}</option>`).join('');
                }
            }
        }
    } catch (error) {
        console.error('Erro ao carregar checklists:', error);
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
    
    try {
        // Iniciar ou continuar inspeção
        const iniciarResponse = await fetch('api/inspecoes.php?iniciar=1', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ checklist_id: checklistId })
        });
        const iniciarResult = await iniciarResponse.json();
        
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
                ${itens.map(item => `
                    <div class="checklist-item ${item.status ? `status-${item.status}` : ''}" id="item-${item.item_id}" data-inspecao="${inspecaoId}">
                        <h4>${item.nome}</h4>
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
                        
                        <button onclick="abrirScanner(${item.item_id})" class="btn-primary">
                            Escanear QR Code
                        </button>
                        <div class="status-buttons" style="${item.status ? 'display: flex;' : 'display: none;'}" id="status-${item.item_id}">
                            <button onclick="marcarStatus(${item.item_id}, 'ok')" class="status-ok">
                                ✓ OK
                            </button>
                            <button onclick="marcarStatus(${item.item_id}, 'problema')" class="status-problem">
                                ✗ Com Problema
                            </button>
                        </div>
                        <div id="observacao-${item.item_id}" style="display: block; margin-top: 10px;">
                            <textarea placeholder="Descreva o problema..." rows="3" style="width: 100%">${item.observacoes || ''}</textarea>
                        </div>
                    </div>
                `).join('')}
                <button onclick="finalizarInspecao(${inspecaoId})" class="btn-success" style="margin-top: 20px;">
                    Finalizar Inspeção
                </button>
            `;

            document.getElementById('inspecaoContent').innerHTML = html;
            showModal('modalInspecao');
        }
    } catch (error) {
        console.error('Erro ao iniciar inspeção:', error);
        alert('Erro ao carregar inspeção');
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
    
    item.classList.remove('status-ok', 'status-problem');
    item.classList.add(`status-${status}`);
    
    // Coletar critérios marcados
    const criteriosChecados = {};
    document.querySelectorAll(`#item-${itemId} .criterio-checkbox`).forEach(cb => {
        criteriosChecados[cb.dataset.criterioId] = cb.checked;
    });
    
    // Salvar status
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
                criterios_checados: criteriosChecados
            })
        });
    } catch (error) {
        console.error('Erro ao salvar status:', error);
    }
}

async function finalizarInspecao(inspecaoId) {
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

    if (resultados.length === 0) {
        alert('Por favor, inspecione pelo menos um item!');
        return;
    }

    try {
        const response = await fetch('api/inspecoes.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                inspecao_id: inspecaoId,
                resultados: resultados
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
            
            const duracaoText = inspecao.duracao 
                ? `${inspecao.duracao.horas}h ${inspecao.duracao.minutos}min`
                : 'Em andamento';
            
            const html = `
                <div class="inspecao-header">
                    <h3>${inspecao.checklist_nome}</h3>
                    <div class="inspecao-info">
                        <div class="info-item">
                            <span class="info-label">Armazém</span>
                            <span class="info-value">${inspecao.armazem.nome} (${inspecao.armazem.codigo})</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Localização</span>
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
                </div>
                
                <div class="itens-inspecionados">
                    <h4>Itens Inspecionados</h4>
                    ${itens.map(item => `
                        <div class="item-resultado ${item.status}">
                            <div class="item-info">
                                <h4>${item.item_nome}</h4>
                                ${item.item_descricao ? `<p>${item.item_descricao}</p>` : ''}
                                ${item.criterios_inspecao ? `<p><strong>Critérios:</strong> ${item.criterios_inspecao}</p>` : ''}
                                <p class="qr-indicator">
                                    ${item.qr_code_lido ? '✓ QR Code escaneado' : '⚠ QR Code não escaneado'}
                                </p>
                                ${item.observacoes ? `
                                    <div class="observacoes-box">
                                        <strong>Observações:</strong> ${item.observacoes}
                                    </div>
                                ` : ''}
                            </div>
                            <div class="item-status">
                                <span class="status-badge ${item.status}">
                                    ${item.status === 'ok' ? '✓ OK' : '✗ Problema'}
                                </span>
                            </div>
                        </div>
                    `).join('')}
                </div>
                
                ${inspecao.observacoes_gerais ? `
                    <div class="observacoes-gerais">
                        <h4>Observações Gerais</h4>
                        <p>${inspecao.observacoes_gerais}</p>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('detalhesInspecao').innerHTML = html;
            showModal('modalVisualizarInspecao');
        }
    } catch (error) {
        console.error('Erro ao carregar detalhes da inspeção:', error);
        alert('Erro ao carregar detalhes da inspeção');
    }
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
    window.print();
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

// Fechar modais ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};