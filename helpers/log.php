<?php
// helpers/log.php
function registrarLog($conn, $usuario_id, $tipo_acao, $descricao, $tabela_afetada = null, $registro_id = null) {
    $query = "INSERT INTO logs_atividades 
              (usuario_id, tipo_acao, descricao, tabela_afetada, registro_id, ip_address, user_agent) 
              VALUES 
              (:usuario_id, :tipo_acao, :descricao, :tabela_afetada, :registro_id, :ip_address, :user_agent)";
    
    $stmt = $conn->prepare($query);
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt->bindParam(":usuario_id", $usuario_id);
    $stmt->bindParam(":tipo_acao", $tipo_acao);
    $stmt->bindParam(":descricao", $descricao);
    $stmt->bindParam(":tabela_afetada", $tabela_afetada);
    $stmt->bindParam(":registro_id", $registro_id);
    $stmt->bindParam(":ip_address", $ip_address);
    $stmt->bindParam(":user_agent", $user_agent);
    
    $stmt->execute();
}