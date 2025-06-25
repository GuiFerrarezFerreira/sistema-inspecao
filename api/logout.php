<?php 
// api/logout.php
session_start();
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/log.php';

if (isset($_SESSION['usuario_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    registrarLog($db, $_SESSION['usuario_id'], 'LOGOUT', 'Usuário realizou logout do sistema');
}

session_destroy();

echo json_encode(array(
    "success" => true,
    "message" => "Logout realizado com sucesso"
));