<?php
// api/inspecoes.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';
require_once '../helpers/log.php';
require_once '../helpers/validation.php';
require_once '../helpers/response.php';

$usuario_id = verificarAutenticacao();
$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['id'])) {
            obterInspecao($_GET['id']);
        } elseif (isset($_GET['checklist_id'])) {
            obterItensChecklist($_GET['checklist_id']);
        } else {
            listarInspecoes();
        }
        break;
    case 'POST':
        if (isset($_GET['iniciar'])) {
            iniciarInspecao();
        } elseif (isset($_GET['verificar_qr'])) {
            verificarQRCode();
        } else {
            finalizarInspecao();
        }
        break;
    case 'PUT':
        atualizarItemInspecao();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function obterItensChecklist($checklist_id) {
    global $db, $usuario_id;
    
    // Verificar se existe inspeção em andamento
    $query_inspecao = "SELECT id FROM inspecoes 
                       WHERE checklist_id = :checklist_id 
                       AND funcionario_id = :funcionario_id 
                       AND status = 'em_andamento'
                       ORDER BY data_inicio DESC
                       LIMIT 1";
    
    $stmt_inspecao = $db->prepare($query_inspecao);
    $stmt_inspecao->bindParam(":checklist_id", $checklist_id);
    $stmt_inspecao->bindParam(":funcionario_id", $usuario_id);
    $stmt_inspecao->execute();
    
    $inspecao_id = null;
    if ($stmt_inspecao->rowCount() > 0) {
        $row = $stmt_inspecao->fetch(PDO::FETCH_ASSOC);
        $inspecao_id = $row['id'];
    }
    
    // Buscar itens do checklist
    $query = "SELECT ci.id as checklist_item_id, ci.qr_code_data,
                     i.id as item_id, i.nome, i.descricao,
                     ir.status, ir.observacoes, ir.data_verificacao, ir.qr_code_lido
              FROM checklist_itens ci
              INNER JOIN itens_inspecao i ON ci.item_id = i.id
              LEFT JOIN inspecao_resultados ir ON ir.item_id = i.id AND ir.inspecao_id = :inspecao_id
              WHERE ci.checklist_id = :checklist_id
              ORDER BY i.nome";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":checklist_id", $checklist_id);
    $stmt->bindParam(":inspecao_id", $inspecao_id);
    $stmt->execute();
    
    $itens = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Buscar critérios do item
        $criterios = buscarCriteriosItem($row['item_id'], $inspecao_id);
        
        $itens[] = array(
            "checklist_item_id" => $row['checklist_item_id'],
            "item_id" => $row['item_id'],
            "nome" => $row['nome'],
            "descricao" => $row['descricao'],
            "criterios" => $criterios,
            "qr_code_data" => $row['qr_code_data'],
            "status" => $row['status'],
            "observacoes" => $row['observacoes'],
            "data_verificacao" => $row['data_verificacao'],
            "qr_code_lido" => $row['qr_code_lido']
        );
    }
    
    enviarResposta(true, "Itens do checklist obtidos com sucesso", array(
        "inspecao_id" => $inspecao_id,
        "itens" => $itens
    ));
}

function buscarCriteriosItem($item_id, $inspecao_id = null) {
    global $db;
    
    $query = "SELECT c.id, c.descricao, c.ordem,
                     COALESCE(icr.checado, FALSE) as checado
              FROM criterios_inspecao c
              LEFT JOIN inspecao_criterios_resultados icr ON c.id = icr.criterio_id 
                    AND icr.item_id = :item_id 
                    AND icr.inspecao_id = :inspecao_id
              WHERE c.item_id = :item_id AND c.ativo = TRUE
              ORDER BY c.ordem, c.id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":item_id", $item_id);
    $stmt->bindParam(":inspecao_id", $inspecao_id);
    $stmt->execute();
    
    $criterios = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $criterios[] = array(
            "id" => $row['id'],
            "descricao" => $row['descricao'],
            "ordem" => $row['ordem'],
            "checado" => (bool)$row['checado']
        );
    }
    
    return $criterios;
}

function iniciarInspecao() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->checklist_id, "checklist_id");
        
        // Verificar se já existe inspeção em andamento
        $query_check = "SELECT id FROM inspecoes 
                        WHERE checklist_id = :checklist_id 
                        AND funcionario_id = :funcionario_id 
                        AND status = 'em_andamento'";
        
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":checklist_id", $data->checklist_id);
        $stmt_check->bindParam(":funcionario_id", $usuario_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            $row = $stmt_check->fetch(PDO::FETCH_ASSOC);
            enviarResposta(true, "Inspeção já em andamento", array("inspecao_id" => $row['id']));
            return;
        }
        
        // Criar nova inspeção
        $query = "INSERT INTO inspecoes (checklist_id, funcionario_id, data_inicio, status) 
                  VALUES (:checklist_id, :funcionario_id, NOW(), 'em_andamento')";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":checklist_id", $data->checklist_id);
        $stmt->bindParam(":funcionario_id", $usuario_id);
        
        if ($stmt->execute()) {
            $inspecao_id = $db->lastInsertId();
            
            registrarLog($db, $usuario_id, 'INICIAR_INSPECAO', 'Inspeção iniciada', 'inspecoes', $inspecao_id);
            
            enviarResposta(true, "Inspeção iniciada com sucesso", array("inspecao_id" => $inspecao_id));
        } else {
            throw new Exception("Erro ao iniciar inspeção");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function verificarQRCode() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->qr_data, "qr_data");
        validarCampoObrigatorio($data->inspecao_id, "inspecao_id");
        
        $qr_decoded = json_decode($data->qr_data, true);
        
        if (!isset($qr_decoded['checklist_id']) || !isset($qr_decoded['item_id'])) {
            throw new Exception("QR Code inválido");
        }
        
        // Verificar se o item pertence à inspeção
        $query = "SELECT c.id 
                  FROM inspecoes i
                  INNER JOIN checklists c ON i.checklist_id = c.id
                  INNER JOIN checklist_itens ci ON c.id = ci.checklist_id
                  WHERE i.id = :inspecao_id 
                  AND ci.item_id = :item_id
                  AND i.funcionario_id = :funcionario_id
                  AND i.status = 'em_andamento'";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt->bindParam(":item_id", $qr_decoded['item_id']);
        $stmt->bindParam(":funcionario_id", $usuario_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Item não pertence a esta inspeção");
        }
        
        enviarResposta(true, "QR Code válido", array(
            "item_id" => $qr_decoded['item_id']
        ));
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function atualizarItemInspecao() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->inspecao_id, "inspecao_id");
        validarCampoObrigatorio($data->item_id, "item_id");
        validarCampoObrigatorio($data->status, "status");
        
        // Verificar se a inspeção pertence ao usuário
        $query_check = "SELECT id FROM inspecoes 
                        WHERE id = :inspecao_id 
                        AND funcionario_id = :funcionario_id 
                        AND status = 'em_andamento'";
        
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt_check->bindParam(":funcionario_id", $usuario_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Inspeção não encontrada ou não pertence ao usuário");
        }
        
        $db->beginTransaction();
        
        // Verificar se já existe resultado para este item
        $query_exists = "SELECT id FROM inspecao_resultados 
                         WHERE inspecao_id = :inspecao_id AND item_id = :item_id";
        
        $stmt_exists = $db->prepare($query_exists);
        $stmt_exists->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt_exists->bindParam(":item_id", $data->item_id);
        $stmt_exists->execute();
        
        $observacoes = isset($data->observacoes) ? $data->observacoes : null;
        $qr_code_lido = isset($data->qr_code_lido) ? $data->qr_code_lido : false;
        
        if ($stmt_exists->rowCount() > 0) {
            // Atualizar resultado existente
            $query = "UPDATE inspecao_resultados 
                      SET status = :status, observacoes = :observacoes, 
                          data_verificacao = NOW(), qr_code_lido = :qr_code_lido 
                      WHERE inspecao_id = :inspecao_id AND item_id = :item_id";
        } else {
            // Criar novo resultado
            $query = "INSERT INTO inspecao_resultados 
                      (inspecao_id, item_id, status, observacoes, data_verificacao, qr_code_lido) 
                      VALUES 
                      (:inspecao_id, :item_id, :status, :observacoes, NOW(), :qr_code_lido)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt->bindParam(":item_id", $data->item_id);
        $stmt->bindParam(":status", $data->status);
        $stmt->bindParam(":observacoes", $observacoes);
        $stmt->bindParam(":qr_code_lido", $qr_code_lido, PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            // Salvar critérios marcados
            if (isset($data->criterios_checados) && is_array($data->criterios_checados)) {
                $query_criterio = "INSERT INTO inspecao_criterios_resultados 
                                   (inspecao_id, item_id, criterio_id, checado) 
                                   VALUES (:inspecao_id, :item_id, :criterio_id, :checado)
                                   ON DUPLICATE KEY UPDATE checado = VALUES(checado)";
                
                $stmt_criterio = $db->prepare($query_criterio);
                
                foreach ($data->criterios_checados as $criterio_id => $checado) {
                    $stmt_criterio->bindParam(":inspecao_id", $data->inspecao_id);
                    $stmt_criterio->bindParam(":item_id", $data->item_id);
                    $stmt_criterio->bindParam(":criterio_id", $criterio_id);
                    $stmt_criterio->bindValue(":checado", $checado ? 1 : 0, PDO::PARAM_INT);
                    $stmt_criterio->execute();
                }
            }
            
            $db->commit();
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

function finalizarInspecao() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->inspecao_id, "inspecao_id");
        
        if (!isset($data->resultados) || count($data->resultados) == 0) {
            throw new Exception("Nenhum resultado foi registrado");
        }
        
        $db->beginTransaction();
        
        // Verificar se a inspeção pertence ao usuário
        $query_check = "SELECT checklist_id FROM inspecoes 
                        WHERE id = :inspecao_id 
                        AND funcionario_id = :funcionario_id 
                        AND status = 'em_andamento'";
        
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt_check->bindParam(":funcionario_id", $usuario_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Inspeção não encontrada ou não pertence ao usuário");
        }
        
        // Inserir/atualizar resultados
        $query_resultado = "INSERT INTO inspecao_resultados 
                           (inspecao_id, item_id, status, observacoes, data_verificacao, qr_code_lido) 
                           VALUES 
                           (:inspecao_id, :item_id, :status, :observacoes, NOW(), :qr_code_lido)
                           ON DUPLICATE KEY UPDATE
                           status = VALUES(status),
                           observacoes = VALUES(observacoes),
                           data_verificacao = NOW(),
                           qr_code_lido = VALUES(qr_code_lido)";
        
        $stmt_resultado = $db->prepare($query_resultado);
        
        foreach ($data->resultados as $resultado) {
            $observacoes = isset($resultado->observacao) ? $resultado->observacao : 
                          (isset($resultado->observacoes) ? $resultado->observacoes : null);
            $qr_code_lido = isset($resultado->qr_code_lido) ? $resultado->qr_code_lido : true;
            
            $stmt_resultado->bindParam(":inspecao_id", $data->inspecao_id);
            $stmt_resultado->bindParam(":item_id", $resultado->item_id);
            $stmt_resultado->bindParam(":status", $resultado->status);
            $stmt_resultado->bindParam(":observacoes", $observacoes);
            $stmt_resultado->bindParam(":qr_code_lido", $qr_code_lido, PDO::PARAM_BOOL);
            $stmt_resultado->execute();
            
            // Salvar critérios marcados
            if (isset($resultado->criterios_checados) && is_array($resultado->criterios_checados)) {
                $query_criterio = "INSERT INTO inspecao_criterios_resultados 
                                   (inspecao_id, item_id, criterio_id, checado) 
                                   VALUES (:inspecao_id, :item_id, :criterio_id, :checado)
                                   ON DUPLICATE KEY UPDATE checado = VALUES(checado)";
                
                $stmt_criterio = $db->prepare($query_criterio);
                
                foreach ($resultado->criterios_checados as $criterio_id => $checado) {
                    $stmt_criterio->bindParam(":inspecao_id", $data->inspecao_id);
                    $stmt_criterio->bindParam(":item_id", $resultado->item_id);
                    $stmt_criterio->bindParam(":criterio_id", $criterio_id);
                    $stmt_criterio->bindValue(":checado", $checado ? 1 : 0, PDO::PARAM_INT);
                    $stmt_criterio->execute();
                }
            }
        }
        
        // Finalizar inspeção
        $observacoes_gerais = isset($data->observacoes_gerais) ? $data->observacoes_gerais : null;
        
        $query_finalizar = "UPDATE inspecoes 
                           SET status = 'concluida', data_fim = NOW(), observacoes_gerais = :observacoes 
                           WHERE id = :inspecao_id";
        
        $stmt_finalizar = $db->prepare($query_finalizar);
        $stmt_finalizar->bindParam(":observacoes", $observacoes_gerais);
        $stmt_finalizar->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt_finalizar->execute();
        
        $db->commit();
        
        registrarLog($db, $usuario_id, 'FINALIZAR_INSPECAO', 'Inspeção finalizada', 'inspecoes', $data->inspecao_id);
        
        enviarResposta(true, "Inspeção finalizada com sucesso");
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function listarInspecoes() {
    global $db, $usuario_id;
    
    // Verificar se é admin
    $query_tipo = "SELECT tipo FROM usuarios WHERE id = :usuario_id";
    $stmt_tipo = $db->prepare($query_tipo);
    $stmt_tipo->bindParam(":usuario_id", $usuario_id);
    $stmt_tipo->execute();
    $row_tipo = $stmt_tipo->fetch(PDO::FETCH_ASSOC);
    
    $is_admin = ($row_tipo['tipo'] == 'admin');
    
    $query = "SELECT i.id, i.data_inicio, i.data_fim, i.status, i.observacoes_gerais,
                     c.nome as checklist_nome, c.periodicidade,
                     a.nome as armazem_nome,
                     u.nome as funcionario_nome,
                     COUNT(DISTINCT ir.id) as total_itens_verificados,
                     COUNT(DISTINCT CASE WHEN ir.status = 'problema' THEN ir.id END) as total_problemas
              FROM inspecoes i
              INNER JOIN checklists c ON i.checklist_id = c.id
              INNER JOIN armazens a ON c.armazem_id = a.id
              INNER JOIN usuarios u ON i.funcionario_id = u.id
              LEFT JOIN inspecao_resultados ir ON i.id = ir.inspecao_id
              WHERE 1=1 ";
    
    if (!$is_admin) {
        $query .= " AND i.funcionario_id = :funcionario_id ";
    }
    
    $query .= " GROUP BY i.id ORDER BY i.data_inicio DESC LIMIT 100";
    
    $stmt = $db->prepare($query);
    
    if (!$is_admin) {
        $stmt->bindParam(":funcionario_id", $usuario_id);
    }
    
    $stmt->execute();
    
    $inspecoes = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $inspecoes[] = array(
            "id" => $row['id'],
            "checklist_nome" => $row['checklist_nome'],
            "armazem_nome" => $row['armazem_nome'],
            "funcionario_nome" => $row['funcionario_nome'],
            "periodicidade" => $row['periodicidade'],
            "data_inicio" => $row['data_inicio'],
            "data_fim" => $row['data_fim'],
            "status" => $row['status'],
            "total_itens_verificados" => $row['total_itens_verificados'],
            "total_problemas" => $row['total_problemas'],
            "observacoes_gerais" => $row['observacoes_gerais']
        );
    }
    
    enviarResposta(true, "Inspeções listadas com sucesso", $inspecoes);
}