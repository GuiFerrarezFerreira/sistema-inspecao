<?php
// api/login.php
session_start();
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido"));
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->username) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Usuário e senha são obrigatórios"));
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT id, nome, email, usuario, senha, tipo, cargo 
          FROM usuarios 
          WHERE usuario = :usuario AND ativo = TRUE 
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(":usuario", $data->username);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data->password == $row['senha']) {
        $_SESSION['usuario_id'] = $row['id'];
        $_SESSION['nome'] = $row['nome'];
        $_SESSION['tipo'] = $row['tipo'];
        
        registrarLog($db, $row['id'], 'LOGIN', 'Usuário realizou login no sistema');
        
        $user = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "email" => $row['email'],
            "tipo" => $row['tipo'],
            "cargo" => $row['cargo']
        );
        
        echo json_encode(array(
            "success" => true,
            "message" => "Login realizado com sucesso",
            "user" => $user
        ));
    } else {
        registrarLog($db, null, 'LOGIN_FALHOU', 'Tentativa de login com senha incorreta para usuário: ' . $data->username);
        
        http_response_code(401);
        echo json_encode(array(
            "success" => false,
            "message" => "Usuário ou senha inválidos"
        ));
    }
} else {
    registrarLog($db, null, 'LOGIN_FALHOU', 'Tentativa de login com usuário inexistente: ' . $data->username);
    
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Usuário ou senha inválidos"
    ));
}
