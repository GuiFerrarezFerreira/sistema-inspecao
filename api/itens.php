<?php
// api/itens.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';
require_once '../helpers/log.php';
require_once '../helpers/validation.php';
require_once '../helpers/response.php';

$usuario_id = verificarAdmin();
$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['armazem_id'])) {
            listarItensPorArmazem($_GET['armazem_id']);
        } else {
            listarItens();
        }
        break;
    case 'POST':
        criarItem();
        break;
    case 'PUT':
        atualizarItem();
        break;
    case 'DELETE':
        excluirItem();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function listarItens() {
    global $db;
    
    $query = "SELECT i.id, i.nome, i.descricao, i.ativo, i.criado_em,
                     a.nome as armazem_nome, a.id as armazem_id
              FROM itens_inspecao i
              INNER JOIN armazens a ON i.armazem_id = a.id
              WHERE i.ativo = TRUE
              ORDER BY a.nome, i.nome";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $itens = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Buscar critérios para cada item
        $criterios = buscarCriteriosItem($row['id']);
        
        $itens[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "descricao" => $row['descricao'],
            "armazem_id" => $row['armazem_id'],
            "armazem_nome" => $row['armazem_nome'],
            "criterios" => $criterios,
            "ativo" => $row['ativo'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Itens listados com sucesso", $itens);
}

function listarItensPorArmazem($armazem_id) {
    global $db;
    
    $query = "SELECT id, nome, descricao, ativo, criado_em
              FROM itens_inspecao
              WHERE armazem_id = :armazem_id AND ativo = TRUE
              ORDER BY nome";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":armazem_id", $armazem_id);
    $stmt->execute();
    
    $itens = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Buscar critérios para cada item
        $criterios = buscarCriteriosItem($row['id']);
        
        $itens[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "descricao" => $row['descricao'],
            "criterios" => $criterios,
            "ativo" => $row['ativo'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Itens do armazém listados com sucesso", $itens);
}

function buscarCriteriosItem($item_id) {
    global $db;
    
    $query = "SELECT id, descricao, ordem 
              FROM criterios_inspecao 
              WHERE item_id = :item_id AND ativo = TRUE 
              ORDER BY ordem, id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":item_id", $item_id);
    $stmt->execute();
    
    $criterios = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $criterios[] = array(
            "id" => $row['id'],
            "descricao" => $row['descricao'],
            "ordem" => $row['ordem']
        );
    }
    
    return $criterios;
}

function criarItem() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->armazem_id, "armazém");
        
        // Verificar se armazém existe
        $query_check = "SELECT id FROM armazens WHERE id = :armazem_id AND ativo = TRUE";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":armazem_id", $data->armazem_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Armazém não encontrado");
        }
        
        $db->beginTransaction();
        
        // Criar item
        $query = "INSERT INTO itens_inspecao (armazem_id, nome, descricao) 
                  VALUES (:armazem_id, :nome, :descricao)";
        
        $stmt = $db->prepare($query);
        
        $descricao = isset($data->descricao) ? $data->descricao : null;
        
        $stmt->bindParam(":armazem_id", $data->armazem_id);
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":descricao", $descricao);
        
        if ($stmt->execute()) {
            $item_id = $db->lastInsertId();
            
            // Adicionar critérios
            if (isset($data->criterios) && is_array($data->criterios)) {
                $query_criterio = "INSERT INTO criterios_inspecao (item_id, descricao, ordem) 
                                   VALUES (:item_id, :descricao, :ordem)";
                $stmt_criterio = $db->prepare($query_criterio);
                
                foreach ($data->criterios as $index => $criterio) {
                    if (!empty(trim($criterio))) {
                        $stmt_criterio->bindParam(":item_id", $item_id);
                        $stmt_criterio->bindParam(":descricao", $criterio);
                        $stmt_criterio->bindValue(":ordem", $index);
                        $stmt_criterio->execute();
                    }
                }
            }
            
            $db->commit();
            
            registrarLog($db, $usuario_id, 'CRIAR_ITEM', 'Item de inspeção criado: ' . $data->nome, 'itens_inspecao', $item_id);
            
            enviarResposta(true, "Item criado com sucesso", array("id" => $item_id));
        } else {
            throw new Exception("Erro ao criar item");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function atualizarItem() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->armazem_id, "armazém");
        
        // Verificar se armazém existe
        $query_check = "SELECT id FROM armazens WHERE id = :armazem_id AND ativo = TRUE";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":armazem_id", $data->armazem_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Armazém não encontrado");
        }
        
        $db->beginTransaction();
        
        // Atualizar item
        $query = "UPDATE itens_inspecao 
                  SET armazem_id = :armazem_id, nome = :nome, descricao = :descricao 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $descricao = isset($data->descricao) ? $data->descricao : null;
        
        $stmt->bindParam(":armazem_id", $data->armazem_id);
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            // Desativar critérios antigos
            $query_desativar = "UPDATE criterios_inspecao SET ativo = FALSE WHERE item_id = :item_id";
            $stmt_desativar = $db->prepare($query_desativar);
            $stmt_desativar->bindParam(":item_id", $data->id);
            $stmt_desativar->execute();
            
            // Adicionar novos critérios
            if (isset($data->criterios) && is_array($data->criterios)) {
                $query_criterio = "INSERT INTO criterios_inspecao (item_id, descricao, ordem) 
                                   VALUES (:item_id, :descricao, :ordem)";
                $stmt_criterio = $db->prepare($query_criterio);
                
                foreach ($data->criterios as $index => $criterio) {
                    if (!empty(trim($criterio))) {
                        $stmt_criterio->bindParam(":item_id", $data->id);
                        $stmt_criterio->bindParam(":descricao", $criterio);
                        $stmt_criterio->bindValue(":ordem", $index);
                        $stmt_criterio->execute();
                    }
                }
            }
            
            $db->commit();
            
            registrarLog($db, $usuario_id, 'ATUALIZAR_ITEM', 'Item de inspeção atualizado: ' . $data->nome, 'itens_inspecao', $data->id);
            
            enviarResposta(true, "Item atualizado com sucesso");
        } else {
            throw new Exception("Erro ao atualizar item");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirItem() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        
        // Verificar se item está em uso em algum checklist
        $query_check = "SELECT COUNT(*) as total FROM checklist_itens WHERE item_id = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":id", $data->id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            throw new Exception("Não é possível excluir o item pois está sendo usado em checklists");
        }
        
        $db->beginTransaction();
        
        // Desativar critérios
        $query_criterios = "UPDATE criterios_inspecao SET ativo = FALSE WHERE item_id = :id";
        $stmt_criterios = $db->prepare($query_criterios);
        $stmt_criterios->bindParam(":id", $data->id);
        $stmt_criterios->execute();
        
        // Não excluir fisicamente, apenas desativar
        $query = "UPDATE itens_inspecao SET ativo = FALSE WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            $db->commit();
            
            registrarLog($db, $usuario_id, 'EXCLUIR_ITEM', 'Item de inspeção desativado', 'itens_inspecao', $data->id);
            
            enviarResposta(true, "Item excluído com sucesso");
        } else {
            throw new Exception("Erro ao excluir item");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}