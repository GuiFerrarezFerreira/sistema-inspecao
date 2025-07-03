<?php
// api/checklists.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';
require_once '../helpers/log.php';
require_once '../helpers/validation.php';
require_once '../helpers/response.php';

$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['funcionario'])) {
            $usuario_id = verificarAutenticacao();
            listarChecklistsFuncionario($usuario_id);
        } else {
            $usuario_id = verificarAdmin();
            listarChecklists();
        }
        break;
    case 'POST':
        $usuario_id = verificarAdmin();
        criarChecklist();
        break;
    case 'PUT':
        $usuario_id = verificarAdmin();
        atualizarChecklist();
        break;
    case 'DELETE':
        $usuario_id = verificarAdmin();
        excluirChecklist();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function listarChecklists() {
    global $db;
    
    $query = "SELECT 
                c.id, 
                c.nome, 
                c.periodicidade, 
                c.ativo, 
                c.criado_em,
                a.nome as armazem_nome, 
                a.id as armazem_id,
                COUNT(DISTINCT ci.id) as total_itens
              FROM checklists c
              INNER JOIN armazens a ON c.armazem_id = a.id
              LEFT JOIN checklist_itens ci ON c.id = ci.checklist_id
              WHERE c.ativo = TRUE
              GROUP BY c.id
              ORDER BY c.nome";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $checklists = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Buscar responsáveis
        $query_resp = "SELECT u.id, u.nome 
                       FROM checklist_responsaveis cr
                       INNER JOIN usuarios u ON cr.funcionario_id = u.id
                       WHERE cr.checklist_id = :checklist_id
                       ORDER BY u.nome";
        
        $stmt_resp = $db->prepare($query_resp);
        $stmt_resp->bindParam(":checklist_id", $row['id']);
        $stmt_resp->execute();
        
        $responsaveis = array();
        $responsaveis_nomes = array();
        while ($resp = $stmt_resp->fetch(PDO::FETCH_ASSOC)) {
            $responsaveis[] = array(
                "id" => $resp['id'],
                "nome" => $resp['nome']
            );
            $responsaveis_nomes[] = $resp['nome'];
        }
        
        $checklists[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "periodicidade" => $row['periodicidade'],
            "armazem_id" => $row['armazem_id'],
            "armazem_nome" => $row['armazem_nome'],
            "responsaveis" => $responsaveis,
            "responsaveis_nomes" => implode(", ", $responsaveis_nomes),
            "total_itens" => $row['total_itens'],
            "ativo" => $row['ativo'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Checklists listados com sucesso", $checklists);
}

function listarChecklistsFuncionario($funcionario_id) {
    global $db;
    
    $query = "SELECT DISTINCT
                c.id, 
                c.nome, 
                c.periodicidade,
                a.nome as armazem_nome,
                COUNT(DISTINCT ci.id) as total_itens,
                COUNT(DISTINCT i.id) as inspecoes_pendentes,
                MAX(i.data_inicio) as ultima_inspecao
              FROM checklists c
              INNER JOIN armazens a ON c.armazem_id = a.id
              INNER JOIN checklist_responsaveis cr ON c.id = cr.checklist_id
              LEFT JOIN checklist_itens ci ON c.id = ci.checklist_id
              LEFT JOIN inspecoes i ON c.id = i.checklist_id 
                    AND i.funcionario_id = :funcionario_id 
                    AND i.status = 'em_andamento'
              WHERE cr.funcionario_id = :funcionario_id AND c.ativo = TRUE
              GROUP BY c.id
              ORDER BY c.nome";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":funcionario_id", $funcionario_id);
    $stmt->execute();
    
    $checklists = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Verificar se está no prazo
        $prazo = calcularProximaInspecao($row['periodicidade'], $row['ultima_inspecao']);
        
        $checklists[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "periodicidade" => $row['periodicidade'],
            "armazem_nome" => $row['armazem_nome'],
            "total_itens" => $row['total_itens'],
            "tem_pendente" => $row['inspecoes_pendentes'] > 0,
            "ultima_inspecao" => $row['ultima_inspecao'],
            "proxima_inspecao" => $prazo,
            "status" => determinarStatusChecklist($prazo)
        );
    }
    
    enviarResposta(true, "Checklists do funcionário listados com sucesso", $checklists);
}

function criarChecklist() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->armazem_id, "armazém");
        validarCampoObrigatorio($data->periodicidade, "periodicidade");
        
        if (!isset($data->funcionarios_ids) || count($data->funcionarios_ids) == 0) {
            throw new Exception("Selecione pelo menos um funcionário responsável");
        }
        
        if (!isset($data->itens) || count($data->itens) == 0) {
            throw new Exception("Selecione pelo menos um item para o checklist");
        }
        
        $db->beginTransaction();
        
        // Criar checklist (sem funcionario_id)
        $query = "INSERT INTO checklists (nome, armazem_id, periodicidade) 
                  VALUES (:nome, :armazem_id, :periodicidade)";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":armazem_id", $data->armazem_id);
        $stmt->bindParam(":periodicidade", $data->periodicidade);
        
        $stmt->execute();
        $checklist_id = $db->lastInsertId();
        
        // Adicionar responsáveis
        $query_resp = "INSERT INTO checklist_responsaveis (checklist_id, funcionario_id) 
                       VALUES (:checklist_id, :funcionario_id)";
        
        $stmt_resp = $db->prepare($query_resp);
        
        foreach ($data->funcionarios_ids as $funcionario_id) {
            $stmt_resp->bindParam(":checklist_id", $checklist_id);
            $stmt_resp->bindParam(":funcionario_id", $funcionario_id);
            $stmt_resp->execute();
        }
        
        // Adicionar itens ao checklist
        $query_item = "INSERT INTO checklist_itens (checklist_id, item_id, qr_code_data) 
                       VALUES (:checklist_id, :item_id, :qr_code_data)";
        
        $stmt_item = $db->prepare($query_item);
        
        foreach ($data->itens as $item_id) {
            $qr_data = json_encode(array(
                "checklist_id" => $checklist_id,
                "item_id" => $item_id,
                "timestamp" => time()
            ));
            
            $stmt_item->bindParam(":checklist_id", $checklist_id);
            $stmt_item->bindParam(":item_id", $item_id);
            $stmt_item->bindParam(":qr_code_data", $qr_data);
            $stmt_item->execute();
        }
        
        $db->commit();
        
        registrarLog($db, $usuario_id, 'CRIAR_CHECKLIST', 'Checklist criado: ' . $data->nome, 'checklists', $checklist_id);
        
        enviarResposta(true, "Checklist criado com sucesso", array("checklist_id" => $checklist_id));
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function atualizarChecklist() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->periodicidade, "periodicidade");
        
        if (!isset($data->funcionarios_ids) || count($data->funcionarios_ids) == 0) {
            throw new Exception("Selecione pelo menos um funcionário responsável");
        }
        
        $db->beginTransaction();
        
        // Atualizar checklist
        $query = "UPDATE checklists 
                  SET nome = :nome, periodicidade = :periodicidade 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":periodicidade", $data->periodicidade);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            // Remover responsáveis antigos
            $query_delete = "DELETE FROM checklist_responsaveis WHERE checklist_id = :checklist_id";
            $stmt_delete = $db->prepare($query_delete);
            $stmt_delete->bindParam(":checklist_id", $data->id);
            $stmt_delete->execute();
            
            // Adicionar novos responsáveis
            $query_resp = "INSERT INTO checklist_responsaveis (checklist_id, funcionario_id) 
                           VALUES (:checklist_id, :funcionario_id)";
            
            $stmt_resp = $db->prepare($query_resp);
            
            foreach ($data->funcionarios_ids as $funcionario_id) {
                $stmt_resp->bindParam(":checklist_id", $data->id);
                $stmt_resp->bindParam(":funcionario_id", $funcionario_id);
                $stmt_resp->execute();
            }
            
            $db->commit();
            
            registrarLog($db, $usuario_id, 'ATUALIZAR_CHECKLIST', 'Checklist atualizado: ' . $data->nome, 'checklists', $data->id);
            
            enviarResposta(true, "Checklist atualizado com sucesso");
        } else {
            throw new Exception("Erro ao atualizar checklist");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirChecklist() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        
        // Verificar se existem inspeções realizadas
        $query_check = "SELECT COUNT(*) as total FROM inspecoes WHERE checklist_id = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":id", $data->id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            throw new Exception("Não é possível excluir o checklist pois existem inspeções realizadas");
        }
        
        // Não excluir fisicamente, apenas desativar
        $query = "UPDATE checklists SET ativo = FALSE WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'EXCLUIR_CHECKLIST', 'Checklist desativado', 'checklists', $data->id);
            
            enviarResposta(true, "Checklist excluído com sucesso");
        } else {
            throw new Exception("Erro ao excluir checklist");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

// Funções auxiliares
function calcularProximaInspecao($periodicidade, $ultima_inspecao) {
    if ($ultima_inspecao == null) {
        return date('Y-m-d'); // Se nunca foi inspecionado, deve ser feito hoje
    }
    
    $ultima = new DateTime($ultima_inspecao);
    $proxima = clone $ultima;
    
    switch ($periodicidade) {
        case 'diario':
            $proxima->add(new DateInterval('P1D'));
            break;
        case 'semanal':
            $proxima->add(new DateInterval('P1W'));
            break;
        case 'mensal':
            $proxima->add(new DateInterval('P1M'));
            break;
        case 'anual':
            $proxima->add(new DateInterval('P1Y'));
            break;
    }
    
    return $proxima->format('Y-m-d');
}

function determinarStatusChecklist($prazo) {
    $hoje = new DateTime();
    $data_prazo = new DateTime($prazo);
    
    if ($data_prazo < $hoje) {
        return 'atrasado';
    } elseif ($data_prazo == $hoje) {
        return 'hoje';
    } else {
        return 'no_prazo';
    }
}