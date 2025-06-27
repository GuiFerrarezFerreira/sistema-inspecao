<?php
// api/unidades.php
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
        if (isset($_GET['empresa_id'])) {
            listarUnidadesPorEmpresa($_GET['empresa_id']);
        } else {
            listarUnidades();
        }
        break;
    case 'POST':
        criarUnidade();
        break;
    case 'PUT':
        atualizarUnidade();
        break;
    case 'DELETE':
        excluirUnidade();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function listarUnidades() {
    global $db;
    
    $query = "SELECT u.id, u.nome, u.endereco, u.cidade, u.estado, u.cep, 
                     u.telefone, u.email, u.responsavel, u.ativo, u.criado_em,
                     e.nome as empresa_nome, e.id as empresa_id
              FROM unidades u
              INNER JOIN empresas e ON u.empresa_id = e.id
              WHERE u.ativo = TRUE
              ORDER BY e.nome, u.nome";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $unidades = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unidades[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "endereco" => $row['endereco'],
            "cidade" => $row['cidade'],
            "estado" => $row['estado'],
            "cep" => $row['cep'],
            "telefone" => $row['telefone'],
            "email" => $row['email'],
            "responsavel" => $row['responsavel'],
            "empresa_id" => $row['empresa_id'],
            "empresa_nome" => $row['empresa_nome'],
            "ativo" => $row['ativo'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Unidades listadas com sucesso", $unidades);
}

function listarUnidadesPorEmpresa($empresa_id) {
    global $db;
    
    $query = "SELECT * FROM unidades 
              WHERE empresa_id = :empresa_id AND ativo = TRUE 
              ORDER BY nome";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":empresa_id", $empresa_id);
    $stmt->execute();
    
    $unidades = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unidades[] = $row;
    }
    
    enviarResposta(true, "Unidades da empresa listadas com sucesso", $unidades);
}

function criarUnidade() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->empresa_id, "empresa");
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->cidade, "cidade");
        validarCampoObrigatorio($data->estado, "estado");
        
        // Verificar se empresa existe
        $query_check = "SELECT id FROM empresas WHERE id = :empresa_id AND ativo = TRUE";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":empresa_id", $data->empresa_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Empresa não encontrada");
        }
        
        // Validar estado (UF)
        $estados_validos = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
        if (!in_array(strtoupper($data->estado), $estados_validos)) {
            throw new Exception("Estado inválido. Use a sigla do estado (ex: SP, RJ, MG)");
        }
        
        // Criar unidade
        $query = "INSERT INTO unidades (empresa_id, nome, endereco, cidade, estado, cep, telefone, email, responsavel) 
                  VALUES (:empresa_id, :nome, :endereco, :cidade, :estado, :cep, :telefone, :email, :responsavel)";
        
        $stmt = $db->prepare($query);
        
        $endereco = isset($data->endereco) ? $data->endereco : null;
        $cep = isset($data->cep) ? $data->cep : null;
        $telefone = isset($data->telefone) ? $data->telefone : null;
        $email = isset($data->email) ? $data->email : null;
        $responsavel = isset($data->responsavel) ? $data->responsavel : null;
        $estado = strtoupper($data->estado);
        
        $stmt->bindParam(":empresa_id", $data->empresa_id);
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":endereco", $endereco);
        $stmt->bindParam(":cidade", $data->cidade);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":cep", $cep);
        $stmt->bindParam(":telefone", $telefone);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":responsavel", $responsavel);
        
        if ($stmt->execute()) {
            $unidade_id = $db->lastInsertId();
            registrarLog($db, $usuario_id, 'CRIAR_UNIDADE', 'Unidade criada: ' . $data->nome, 'unidades', $unidade_id);
            
            enviarResposta(true, "Unidade criada com sucesso", array("id" => $unidade_id));
        } else {
            throw new Exception("Erro ao criar unidade");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function atualizarUnidade() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        validarCampoObrigatorio($data->nome, "nome");
        validarCampoObrigatorio($data->cidade, "cidade");
        validarCampoObrigatorio($data->estado, "estado");
        
        // Validar estado (UF)
        $estados_validos = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
        if (!in_array(strtoupper($data->estado), $estados_validos)) {
            throw new Exception("Estado inválido. Use a sigla do estado (ex: SP, RJ, MG)");
        }
        
        // Atualizar unidade
        $query = "UPDATE unidades 
                  SET nome = :nome, endereco = :endereco, cidade = :cidade, estado = :estado, 
                      cep = :cep, telefone = :telefone, email = :email, responsavel = :responsavel 
                  WHERE id = :id";
        
        $stmt = $db->prepare($query);
        
        $endereco = isset($data->endereco) ? $data->endereco : null;
        $cep = isset($data->cep) ? $data->cep : null;
        $telefone = isset($data->telefone) ? $data->telefone : null;
        $email = isset($data->email) ? $data->email : null;
        $responsavel = isset($data->responsavel) ? $data->responsavel : null;
        $estado = strtoupper($data->estado);
        
        $stmt->bindParam(":nome", $data->nome);
        $stmt->bindParam(":endereco", $endereco);
        $stmt->bindParam(":cidade", $data->cidade);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":cep", $cep);
        $stmt->bindParam(":telefone", $telefone);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":responsavel", $responsavel);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'ATUALIZAR_UNIDADE', 'Unidade atualizada: ' . $data->nome, 'unidades', $data->id);
            
            enviarResposta(true, "Unidade atualizada com sucesso");
        } else {
            throw new Exception("Erro ao atualizar unidade");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirUnidade() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        
        // Não excluir fisicamente, apenas desativar
        $query = "UPDATE unidades SET ativo = FALSE WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        
        if ($stmt->execute()) {
            registrarLog($db, $usuario_id, 'EXCLUIR_UNIDADE', 'Unidade desativada', 'unidades', $data->id);
            
            enviarResposta(true, "Unidade excluída com sucesso");
        } else {
            throw new Exception("Erro ao excluir unidade");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}