<?php
// logout.php
session_start();
require_once 'config/database.php';
require_once 'helpers/log.php';

if (isset($_SESSION['usuario_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar se o usuário ainda existe no banco antes de registrar o log
    $query = "SELECT id FROM usuarios WHERE id = :usuario_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario_id", $_SESSION['usuario_id']);
    $stmt->execute();
    
    // Só registra o log se o usuário existir
    if ($stmt->rowCount() > 0) {
        registrarLog($db, $_SESSION['usuario_id'], 'LOGOUT', 'Usuário realizou logout do sistema');
    }
}

// Destruir todas as variáveis de sessão
$_SESSION = array();

// Destruir o cookie de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir a sessão
session_destroy();

// Redirecionar para login
header('Location: login.php?logout=1');
exit();