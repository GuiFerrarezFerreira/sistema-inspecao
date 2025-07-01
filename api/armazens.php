<?php
// api/armazens.php
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
        listarArmazens();
        break;
    case 'POST':
        criarArmazem();
        break;
    case 'PUT':
        atualizarArmazem();
        break;
    case 'DELETE':
        excluirArmazem();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function listarArmazens() {
    global $db;
    
    $query = "SELECT a.id, a.nome, a.codigo, a.localizacao, a.descricao, a.ativo, a.criado_em,
                     e.nome as empresa_nome, e.id as empresa_id,
                     un.nome as unidade_nome, un.id as unidade_id,
                     CONCAT(un.cidade, '/', un.estado) as unidade_localizacao
              FROM armazens a
              LEFT JOIN unidades un ON a.unidade_id = un.id
              LEFT JOIN empresas e ON un.empresa_id = e.id
              WHERE a.ativo = TRUE 
              ORDER BY e.nome, un.nome, a.nome";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $armazens = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $armazens[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "codigo" => $row['codigo'],
            "localizacao" => $row['localizacao'],
            "descricao" => $row['descricao'],
            "empresa_id" => $row['empresa_id'],
            "empresa_nome" => $row['empresa_nome'],
            "unidade_id" => $row['unidade_id'],
            "unidade_nome" => $row['unidade_nome'],
            "unidade_localizacao" => $row['unidade_localizacao'],
            "ativo" => $row['ativo'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Armazéns listados com sucesso", $armazens);
}

function criarArmazem() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->codigo, "código");
        validarCampoObrigatorio($data->localizacao, "localização");
        validarCampoObrigatorio($data->unidade_id, "unidade");
        
        // Verificar se unidade existe
        $query_unidade = "SELECT un.id, un.empresa_id 
                          FROM unidades un 
                          WHERE un.id = :unidade_id AND un.ativo = TRUE";
        $stmt_unidade = $db->prepare($query_unidade);
        $stmt_unidade->bindParam(":unidade_id", $data->unidade_id);
        $stmt_unidade->execute();
        
        if ($stmt_unidade->rowCount() == 0) {
            throw new Exception("Unidade não encontrada");
        }
        
        $unidade_info = $stmt_unidade->fetch(PDO::FETCH_ASSOC);
        $empresa_id = $unidade_info['empresa_id'];
        
        // Verificar se código já existe
        $query_check = "SELECT id FROM armazens WHERE codigo = :codigo";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":codigo", $data->codigo);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            throw new Exception("Código de armazém já cadastrado");
        }
        
        // Criar armazém
        $query = "INSERT INTO armazens (nome, codigo, localizacao, descricao, empresa_id, unidade_id) 
                  VALUES (:nome, :codigo, :localizacao, :descricao, :empresa_id, :unidade_id)";
        
        $stmt = $db->prepare($query);
        
        $descricao = isset($data->descricao) ? $data->descricao : null;
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":codigo", $data->codigo);
        $stmt->bindParam(":localizacao", $data->localizacao);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":empresa_id", $empresa_id);
        $stmt->bindParam(":unidade_id", $data->unidade_id);
        
        if ($stmt->execute()) {
            $armazem_id = $db->lastInsertId();
            registrarLog($db, $usuario_id, 'CRIAR_ARMAZEM', 'Armazém criado: ' . $data->nome, 'armazens', $armazem_id);
            
            enviarResposta(true, "Armazém criado com sucesso", array("id" => $armazem_id));
        } else {
            throw new Exception("Erro ao criar armazém");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function atualizarArmazem() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->codigo, "código");
        validarCampoObrigatorio($data->localizacao, "localização");
        validarCampoObrigatorio($data->unidade_id, "unidade");
        
        // Verificar se unidade existe
        $query_unidade = "SELECT un.id, un.empresa_id 
                          FROM unidades un 
                          WHERE un.id = :unidade_id AND un.ativo = TRUE";
        $stmt_unidade = $db->prepare($query_unidade);
        $stmt_unidade->bindParam(":unidade_id", $data->unidade_id);
        $stmt_unidade->execute();
        
        if ($stmt_unidade->rowCount() == 0) {
            throw new Exception("Unidade não encontrada");
        }
        
        $unidade_info = $stmt_unidade->fetch(PDO::FETCH_ASSOC);
        $empresa_id = $unidade_info['empresa_id'];
        
        // Verificar se código já existe para outro armazém
        $query_check = "SELECT id FROM armazens WHERE codigo = :codigo AND id != :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":codigo", $data->codigo);
        $stmt_check->bindParam(":id", $data->id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            throw new Exception("Código já cadastrado para outro armazém");
        }
        
        // Atualizar armazém
        $query = "UPDATE armazens 
                  SET nome = :nome, codigo = :codigo, localizacao = :localizacao, 
                      descricao = :descricao, empresa_id = :empresa_id, unidade_id = :unidade_id 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $descricao = isset($data->descricao) ? $data->descricao : null;
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":codigo", $data->codigo);
        $stmt->bindParam(":localizacao", $data->localizacao);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":empresa_id", $empresa_id);
        $stmt->bindParam(":unidade_id", $data->unidade_id);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'ATUALIZAR_ARMAZEM', 'Armazém atualizado: ' . $data->nome, 'armazens', $data->id);
            
            enviarResposta(true, "Armazém atualizado com sucesso");
        } else {
            throw new Exception("Erro ao atualizar armazém");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirArmazem() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        
        // Verificar se existem itens vinculados
        $query_check = "SELECT COUNT(*) as total FROM itens_inspecao WHERE armazem_id = :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":id", $data->id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            throw new Exception("Não é possível excluir o armazém pois existem itens vinculados");
        }
        
        // Não excluir fisicamente, apenas desativar
        $query = "UPDATE armazens SET ativo = FALSE WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'EXCLUIR_ARMAZEM', 'Armazém desativado', 'armazens', $data->id);
            
            enviarResposta(true, "Armazém excluído com sucesso");
        } else {
            throw new Exception("Erro ao excluir armazém");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}