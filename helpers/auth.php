<?php
// helpers/auth.php
function verificarAutenticacao() {
    //session_start();

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }    
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(array("message" => "Não autorizado"));
        exit();
    }
    return $_SESSION['usuario_id'];
}

function verificarAdmin() {
    //session_start();

    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }    
    if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo'] !== 'admin') {
        http_response_code(403);
        echo json_encode(array("message" => "Acesso negado"));
        exit();
    }
    return $_SESSION['usuario_id'];
}