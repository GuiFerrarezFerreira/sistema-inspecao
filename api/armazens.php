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
                     e.nome as empresa_nome, e.id as empresa_id
              FROM armazens a
              LEFT JOIN empresas e ON a.empresa_id = e.id
              WHERE a.ativo = TRUE 
              ORDER BY a.nome";
    
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
        
        // Verificar se código já existe
        $query_check = "SELECT id FROM armazens WHERE codigo = :codigo";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":codigo", $data->codigo);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            throw new Exception("Código de armazém já cadastrado");
        }
        
        // Criar armazém
        $query = "INSERT INTO armazens (nome, codigo, localizacao, descricao) 
                  VALUES (:nome, :codigo, :localizacao, :descricao)";
        
        $stmt = $db->prepare($query);
        
        $descricao = isset($data->descricao) ? $data->descricao : null;
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":codigo", $data->codigo);
        $stmt->bindParam(":localizacao", $data->localizacao);
        $stmt->bindParam(":descricao", $descricao);
        
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
                  SET nome = :nome, codigo = :codigo, localizacao = :localizacao, descricao = :descricao 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $descricao = isset($data->descricao) ? $data->descricao : null;
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":codigo", $data->codigo);
        $stmt->bindParam(":localizacao", $data->localizacao);
        $stmt->bindParam(":descricao", $descricao);
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