<?php
// api/empresas.php
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
        if (isset($_GET['id'])) {
            obterEmpresa($_GET['id']);
        } else {
            listarEmpresas();
        }
        break;
    case 'POST':
        criarEmpresa();
        break;
    case 'PUT':
        atualizarEmpresa();
        break;
    case 'DELETE':
        excluirEmpresa();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function listarEmpresas() {
    global $db;
    
    $query = "SELECT e.id, e.nome, e.cnpj, e.telefone, e.email, e.ativo, e.criado_em,
                     COUNT(DISTINCT u.id) as total_unidades,
                     COUNT(DISTINCT f.id) as total_funcionarios,
                     COUNT(DISTINCT a.id) as total_armazens
              FROM empresas e
              LEFT JOIN unidades u ON e.id = u.empresa_id AND u.ativo = TRUE
              LEFT JOIN usuarios f ON e.id = f.empresa_id AND f.ativo = TRUE
              LEFT JOIN armazens a ON e.id = a.empresa_id AND a.ativo = TRUE
              WHERE e.ativo = TRUE
              GROUP BY e.id
              ORDER BY e.nome";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $empresas = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $empresas[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "cnpj" => $row['cnpj'],
            "telefone" => $row['telefone'],
            "email" => $row['email'],
            "total_unidades" => intval($row['total_unidades']),
            "total_funcionarios" => intval($row['total_funcionarios']),
            "total_armazens" => intval($row['total_armazens']),
            "ativo" => $row['ativo'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Empresas listadas com sucesso", $empresas);
}

function obterEmpresa($id) {
    global $db;
    
    $query = "SELECT * FROM empresas WHERE id = :id AND ativo = TRUE";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $empresa = $stmt->fetch(PDO::FETCH_ASSOC);
        enviarResposta(true, "Empresa encontrada", $empresa);
    } else {
        http_response_code(404);
        enviarResposta(false, "Empresa não encontrada");
    }
}

function criarEmpresa() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->nome, "nome");
        
        // Verificar se CNPJ já existe
        if (!empty($data->cnpj)) {
            $query_check = "SELECT id FROM empresas WHERE cnpj = :cnpj";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(":cnpj", $data->cnpj);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                throw new Exception("CNPJ já cadastrado");
            }
        }
        
        // Criar empresa
        $query = "INSERT INTO empresas (nome, cnpj, telefone, email) 
                  VALUES (:nome, :cnpj, :telefone, :email)";
        
        $stmt = $db->prepare($query);
        
        $cnpj = isset($data->cnpj) ? $data->cnpj : null;
        $telefone = isset($data->telefone) ? $data->telefone : null;
        $email = isset($data->email) ? $data->email : null;
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":cnpj", $cnpj);
        $stmt->bindParam(":telefone", $telefone);
        $stmt->bindParam(":email", $email);
        
        if ($stmt->execute()) {
            $empresa_id = $db->lastInsertId();
            registrarLog($db, $usuario_id, 'CRIAR_EMPRESA', 'Empresa criada: ' . $data->nome, 'empresas', $empresa_id);
            
            enviarResposta(true, "Empresa criada com sucesso", array("id" => $empresa_id));
        } else {
            throw new Exception("Erro ao criar empresa");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function atualizarEmpresa() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        validarCampoObrigatorio($data->nome, "nome");
        
        // Verificar se CNPJ já existe para outra empresa
        if (!empty($data->cnpj)) {
            $query_check = "SELECT id FROM empresas WHERE cnpj = :cnpj AND id != :id";
            $stmt_check = $db->prepare($query_check);
            $stmt_check->bindParam(":cnpj", $data->cnpj);
            $stmt_check->bindParam(":id", $data->id);
            $stmt_check->execute();
            
            if ($stmt_check->rowCount() > 0) {
                throw new Exception("CNPJ já cadastrado para outra empresa");
            }
        }
        
        // Atualizar empresa
        $query = "UPDATE empresas 
                  SET nome = :nome, cnpj = :cnpj, telefone = :telefone, email = :email 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $cnpj = isset($data->cnpj) ? $data->cnpj : null;
        $telefone = isset($data->telefone) ? $data->telefone : null;
        $email = isset($data->email) ? $data->email : null;
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":cnpj", $cnpj);
        $stmt->bindParam(":telefone", $telefone);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'ATUALIZAR_EMPRESA', 'Empresa atualizada: ' . $data->nome, 'empresas', $data->id);
            
            enviarResposta(true, "Empresa atualizada com sucesso");
        } else {
            throw new Exception("Erro ao atualizar empresa");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirEmpresa() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        
        // Verificar se existem funcionários vinculados
        $query_check = "SELECT COUNT(*) as total FROM usuarios WHERE empresa_id = :id AND ativo = TRUE";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":id", $data->id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            throw new Exception("Não é possível excluir a empresa pois existem funcionários vinculados");
        }
        
        // Verificar se existem armazéns vinculados
        $query_check = "SELECT COUNT(*) as total FROM armazens WHERE empresa_id = :id AND ativo = TRUE";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":id", $data->id);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            throw new Exception("Não é possível excluir a empresa pois existem armazéns vinculados");
        }
        
        // Não excluir fisicamente, apenas desativar
        $query = "UPDATE empresas SET ativo = FALSE WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'EXCLUIR_EMPRESA', 'Empresa desativada', 'empresas', $data->id);
            
            enviarResposta(true, "Empresa excluída com sucesso");
        } else {
            throw new Exception("Erro ao excluir empresa");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}