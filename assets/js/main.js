// assets/js/main.js

// Variáveis globais
let scanner = null;
let currentChecklistId = null;
let currentItemId = null;

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
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Funções de formulários
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

document.getElementById('formItem')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

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

// Funções de carregamento de dados
function carregarDados() {
    carregarFuncionarios();
    carregarArmazens();
    carregarItens();
    carregarChecklists();
}

async function carregarFuncionarios() {
    try {
        const response = await fetch('api/funcionarios.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(f => `
                <div class="card">
                    <h3>${f.nome}</h3>
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

async function carregarArmazens() {
    try {
        const response = await fetch('api/armazens.php');
        const result = await response.json();
        
        if (result.success && result.data) {
            const html = result.data.map(a => `
                <div class="card">
                    <h3>${a.nome}</h3>
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

            // Atualizar selects
            const selectArmazem = document.querySelector('select[name="armazem_id"]');
            if (selectArmazem) {
                selectArmazem.innerHTML = '<option value="">Selecione...</option>' +
                    result.data.map(a => `<option value="${a.id}">${a.nome}</option>`).join('');
            }
        }
    } catch (error) {
        console.error('Erro ao carregar armazéns:', error);
    }
}

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
                    ${i.criterios_inspecao ? `<p>Critérios: ${i.criterios_inspecao}</p>` : ''}
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
                    <div class="checklist-item" id="item-${item.item_id}" data-inspecao="${inspecaoId}">
                        <h4>${item.nome}</h4>
                        ${item.descricao ? `<p>${item.descricao}</p>` : ''}
                        ${item.criterios ? `<p><strong>Critérios:</strong> ${item.criterios}</p>` : ''}
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
                        <div id="observacao-${item.item_id}" style="${item.status === 'problema' ? 'display: block;' : 'display: none;'} margin-top: 10px;">
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
            
            // Marcar itens já inspecionados
            itens.forEach(item => {
                if (item.status) {
                    const itemEl = document.getElementById(`item-${item.item_id}`);
                    itemEl.classList.add(`status-${item.status}`);
                }
            });
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
    
    if (status === 'problema') {
        document.getElementById(`observacao-${itemId}`).style.display = 'block';
    } else {
        document.getElementById(`observacao-${itemId}`).style.display = 'none';
    }
    
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
                qr_code_lido: true
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

        if (status) {
            resultados.push({
                item_id: itemId,
                status: status,
                observacao: observacao,
                qr_code_lido: true
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

// Funções de edição (a serem implementadas)
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

// Fechar modais ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};