<?php
session_start();
require_once 'config/database.php';
require_once 'helpers/log.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: login.php?erro=1');
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT id, nome, email, usuario, senha, tipo, cargo 
              FROM usuarios 
              WHERE usuario = :usuario AND ativo = TRUE 
              LIMIT 1";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario", $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Temporariamente usando comparação direta. 
        // Em produção, usar password_verify($password, $row['senha'])
        if ($password == $row['senha']) {
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['nome'] = $row['nome'];
            $_SESSION['tipo'] = $row['tipo'];
            $_SESSION['cargo'] = $row['cargo'];
            
            registrarLog($db, $row['id'], 'LOGIN', 'Usuário realizou login no sistema');
            
            header('Location: index.php');
            exit();
        } else {
            registrarLog($db, null, 'LOGIN_FALHOU', 'Tentativa de login com senha incorreta para usuário: ' . $username);
            header('Location: login.php?erro=1');
            exit();
        }
    } else {
        registrarLog($db, null, 'LOGIN_FALHOU', 'Tentativa de login com usuário inexistente: ' . $username);
        header('Location: login.php?erro=1');
        exit();
    }
} catch (Exception $e) {
    // Para desenvolvimento - remover em produção
    error_log("Erro no login: " . $e->getMessage());
    header('Location: login.php?erro=1');
    exit();
}