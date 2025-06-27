<?php
// api/funcionarios.php
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
        listarFuncionarios();
        break;
    case 'POST':
        criarFuncionario();
        break;
    case 'PUT':
        atualizarFuncionario();
        break;
    case 'DELETE':
        excluirFuncionario();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function listarFuncionarios() {
    global $db;
    
    $query = "SELECT u.id, u.nome, u.email, u.usuario, u.tipo, u.cargo, u.ativo, u.criado_em,
                     e.nome as empresa_nome, e.id as empresa_id
              FROM usuarios u
              LEFT JOIN empresas e ON u.empresa_id = e.id
              WHERE u.ativo = 1
              ORDER BY u.nome";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $funcionarios = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $funcionarios[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "email" => $row['email'],
            "usuario" => $row['usuario'],
            "tipo" => $row['tipo'],
            "cargo" => $row['cargo'],
            "empresa_id" => $row['empresa_id'],
            "empresa_nome" => $row['empresa_nome'],
            "ativo" => $row['ativo'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Funcionários listados com sucesso", $funcionarios);
}

function criarFuncionario() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->email, "email");
        validarCampoObrigatorio($data->usuario, "usuário");
        validarCampoObrigatorio($data->senha, "senha");
        validarCampoObrigatorio($data->cargo, "cargo");
        
        if (!validarEmail($data->email)) {
            throw new Exception("Email inválido");
        }
        
        // Verificar se usuário já existe
        $query_check = "SELECT id FROM usuarios WHERE usuario = :usuario OR email = :email";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":usuario", $data->usuario);
        $stmt_check->bindParam(":email", $data->email);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            throw new Exception("Usuário ou email já cadastrado");
        }
        
        // Criar funcionário
        $query = "INSERT INTO usuarios (nome, email, usuario, senha, tipo, cargo) 
                  VALUES (:nome, :email, :usuario, :senha, 'funcionario', :cargo)";
        
        $stmt = $db->prepare($query);
        
        $senha_hash = $data->senha;
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":email", $data->email);
        $stmt->bindParam(":usuario", $data->usuario);
        $stmt->bindParam(":senha", $senha_hash);
        $stmt->bindParam(":cargo", $data->cargo);
        
        if ($stmt->execute()) {
            $funcionario_id = $db->lastInsertId();
            registrarLog($db, $usuario_id, 'CRIAR_FUNCIONARIO', 'Funcionário criado: ' . $data->nome, 'usuarios', $funcionario_id);
            
            enviarResposta(true, "Funcionário criado com sucesso", array("id" => $funcionario_id));
        } else {
            throw new Exception("Erro ao criar funcionário");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function atualizarFuncionario() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->email, "email");
        validarCampoObrigatorio($data->cargo, "cargo");
        
        if (!validarEmail($data->email)) {
            throw new Exception("Email inválido");
        }
        
        // Verificar se email já existe para outro usuário
        $query_check = "SELECT id FROM usuarios WHERE email = :email AND id != :id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":email", $data->email);
        $stmt_check->bindParam(":id", $data->id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            throw new Exception("Email já cadastrado para outro usuário");
        }
        
        // Atualizar funcionário
        $query = "UPDATE usuarios 
                  SET nome = :nome, email = :email, cargo = :cargo 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":email", $data->email);
        $stmt->bindParam(":cargo", $data->cargo);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'ATUALIZAR_FUNCIONARIO', 'Funcionário atualizado: ' . $data->nome, 'usuarios', $data->id);
            
            enviarResposta(true, "Funcionário atualizado com sucesso");
        } else {
            throw new Exception("Erro ao atualizar funcionário");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirFuncionario() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        
        // Não excluir fisicamente, apenas desativar
        $query = "UPDATE usuarios SET ativo = FALSE WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'EXCLUIR_FUNCIONARIO', 'Funcionário desativado', 'usuarios', $data->id);
            
            enviarResposta(true, "Funcionário excluído com sucesso");
        } else {
            throw new Exception("Erro ao excluir funcionário");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}