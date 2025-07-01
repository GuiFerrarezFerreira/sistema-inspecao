<?php
// api/avarias.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';
require_once '../helpers/log.php';
require_once '../helpers/validation.php';
require_once '../helpers/response.php';

$usuario_id = verificarAutenticacao();
$database = new Database();
$db = $database->getConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['inspecao_id']) && isset($_GET['item_id'])) {
            obterAvaria($_GET['inspecao_id'], $_GET['item_id']);
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Parâmetros inválidos"));
        }
        break;
    case 'POST':
        if (isset($_GET['upload_foto'])) {
            uploadFotoAvaria();
        } else {
            salvarAvaria();
        }
        break;
    case 'DELETE':
        if (isset($_GET['foto_id'])) {
            excluirFoto($_GET['foto_id']);
        }
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function obterAvaria($inspecao_id, $item_id) {
    global $db;
    
    // Buscar avaria existente
    $query = "SELECT a.*, 
              (SELECT COUNT(*) FROM inspecao_avaria_fotos WHERE avaria_id = a.id) as total_fotos
              FROM inspecao_avarias a
              WHERE a.inspecao_id = :inspecao_id AND a.item_id = :item_id
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":inspecao_id", $inspecao_id);
    $stmt->bindParam(":item_id", $item_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $avaria = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Buscar fotos da avaria
        $query_fotos = "SELECT * FROM inspecao_avaria_fotos 
                        WHERE avaria_id = :avaria_id 
                        ORDER BY criado_em";
        
        $stmt_fotos = $db->prepare($query_fotos);
        $stmt_fotos->bindParam(":avaria_id", $avaria['id']);
        $stmt_fotos->execute();
        
        $fotos = array();
        while ($foto = $stmt_fotos->fetch(PDO::FETCH_ASSOC)) {
            $fotos[] = array(
                "id" => $foto['id'],
                "nome_arquivo" => $foto['nome_arquivo'],
                "url" => "/uploads/avarias/" . $foto['nome_arquivo'],
                "tipo_mime" => $foto['tipo_mime'],
                "tamanho" => $foto['tamanho'],
                "criado_em" => $foto['criado_em']
            );
        }
        
        $avaria['fotos'] = $fotos;
        
        enviarResposta(true, "Avaria encontrada", $avaria);
    } else {
        enviarResposta(true, "Nenhuma avaria cadastrada", null);
    }
}

function salvarAvaria() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->inspecao_id, "inspecao_id");
        validarCampoObrigatorio($data->item_id, "item_id");
        
        // Verificar se a inspeção pertence ao usuário
        $query_check = "SELECT id FROM inspecoes 
                        WHERE id = :inspecao_id 
                        AND funcionario_id = :funcionario_id 
                        AND status = 'em_andamento'";
        
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt_check->bindParam(":funcionario_id", $usuario_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Inspeção não encontrada ou não pertence ao usuário");
        }
        
        $db->beginTransaction();
        
        // Verificar se já existe avaria para este item
        $query_exists = "SELECT id FROM inspecao_avarias 
                         WHERE inspecao_id = :inspecao_id AND item_id = :item_id";
        
        $stmt_exists = $db->prepare($query_exists);
        $stmt_exists->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt_exists->bindParam(":item_id", $data->item_id);
        $stmt_exists->execute();
        
        $observacoes = isset($data->observacoes) ? $data->observacoes : null;
        
        if ($stmt_exists->rowCount() > 0) {
            // Atualizar avaria existente
            $row = $stmt_exists->fetch(PDO::FETCH_ASSOC);
            $avaria_id = $row['id'];
            
            $query = "UPDATE inspecao_avarias 
                      SET observacoes = :observacoes 
                      WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":observacoes", $observacoes);
            $stmt->bindParam(":id", $avaria_id);
            $stmt->execute();
        } else {
            // Criar nova avaria
            $query = "INSERT INTO inspecao_avarias (inspecao_id, item_id, observacoes) 
                      VALUES (:inspecao_id, :item_id, :observacoes)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":inspecao_id", $data->inspecao_id);
            $stmt->bindParam(":item_id", $data->item_id);
            $stmt->bindParam(":observacoes", $observacoes);
            $stmt->execute();
            
            $avaria_id = $db->lastInsertId();
        }
        
        // Atualizar flag de avaria no resultado da inspeção
        $tem_avaria = !empty($observacoes) || (isset($data->tem_fotos) && $data->tem_fotos);
        
        $query_update = "UPDATE inspecao_resultados 
                         SET tem_avaria = :tem_avaria 
                         WHERE inspecao_id = :inspecao_id AND item_id = :item_id";
        
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bindParam(":tem_avaria", $tem_avaria, PDO::PARAM_BOOL);
        $stmt_update->bindParam(":inspecao_id", $data->inspecao_id);
        $stmt_update->bindParam(":item_id", $data->item_id);
        $stmt_update->execute();
        
        $db->commit();
        
        registrarLog($db, $usuario_id, 'REGISTRAR_AVARIA', 'Avaria registrada para item', 'inspecao_avarias', $avaria_id);
        
        enviarResposta(true, "Avaria salva com sucesso", array("avaria_id" => $avaria_id));
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function uploadFotoAvaria() {
    global $db, $usuario_id;
    
    try {
        // Validar dados
        if (!isset($_POST['inspecao_id']) || !isset($_POST['item_id'])) {
            throw new Exception("Dados incompletos");
        }
        
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erro no upload da foto");
        }
        
        $inspecao_id = $_POST['inspecao_id'];
        $item_id = $_POST['item_id'];
        $arquivo = $_FILES['foto'];
        
        // Validar tipo de arquivo
        $tipos_permitidos = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($arquivo['type'], $tipos_permitidos)) {
            throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG ou WebP");
        }
        
        // Validar tamanho (máximo 5MB)
        $tamanho_maximo = 5 * 1024 * 1024; // 5MB
        if ($arquivo['size'] > $tamanho_maximo) {
            throw new Exception("Arquivo muito grande. Tamanho máximo: 5MB");
        }
        
        // Verificar se a inspeção pertence ao usuário
        $query_check = "SELECT id FROM inspecoes 
                        WHERE id = :inspecao_id 
                        AND funcionario_id = :funcionario_id 
                        AND status = 'em_andamento'";
        
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":inspecao_id", $inspecao_id);
        $stmt_check->bindParam(":funcionario_id", $usuario_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Inspeção não encontrada ou não pertence ao usuário");
        }
        
        $db->beginTransaction();
        
        // Obter ou criar avaria
        $query_avaria = "SELECT id FROM inspecao_avarias 
                         WHERE inspecao_id = :inspecao_id AND item_id = :item_id";
        
        $stmt_avaria = $db->prepare($query_avaria);
        $stmt_avaria->bindParam(":inspecao_id", $inspecao_id);
        $stmt_avaria->bindParam(":item_id", $item_id);
        $stmt_avaria->execute();
        
        if ($stmt_avaria->rowCount() > 0) {
            $row = $stmt_avaria->fetch(PDO::FETCH_ASSOC);
            $avaria_id = $row['id'];
        } else {
            // Criar avaria
            $query_criar = "INSERT INTO inspecao_avarias (inspecao_id, item_id) 
                            VALUES (:inspecao_id, :item_id)";
            
            $stmt_criar = $db->prepare($query_criar);
            $stmt_criar->bindParam(":inspecao_id", $inspecao_id);
            $stmt_criar->bindParam(":item_id", $item_id);
            $stmt_criar->execute();
            
            $avaria_id = $db->lastInsertId();
        }
        
        // Criar diretório se não existir
        $upload_dir = '../uploads/avarias/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Gerar nome único para o arquivo
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid('avaria_') . '_' . time() . '.' . $extensao;
        $caminho_completo = $upload_dir . $nome_arquivo;
        
        // Mover arquivo
        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
            throw new Exception("Erro ao salvar arquivo");
        }
        
        // Salvar informações no banco
        $query_foto = "INSERT INTO inspecao_avaria_fotos 
                       (avaria_id, nome_arquivo, caminho_arquivo, tipo_mime, tamanho) 
                       VALUES (:avaria_id, :nome_arquivo, :caminho_arquivo, :tipo_mime, :tamanho)";
        
        $stmt_foto = $db->prepare($query_foto);
        $stmt_foto->bindParam(":avaria_id", $avaria_id);
        $stmt_foto->bindParam(":nome_arquivo", $nome_arquivo);
        $stmt_foto->bindParam(":caminho_arquivo", $caminho_completo);
        $stmt_foto->bindParam(":tipo_mime", $arquivo['type']);
        $stmt_foto->bindParam(":tamanho", $arquivo['size']);
        $stmt_foto->execute();
        
        $foto_id = $db->lastInsertId();
        
        // Atualizar flag de avaria
        $query_update = "UPDATE inspecao_resultados 
                         SET tem_avaria = TRUE 
                         WHERE inspecao_id = :inspecao_id AND item_id = :item_id";
        
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bindParam(":inspecao_id", $inspecao_id);
        $stmt_update->bindParam(":item_id", $item_id);
        $stmt_update->execute();
        
        $db->commit();
        
        registrarLog($db, $usuario_id, 'UPLOAD_FOTO_AVARIA', 'Foto de avaria enviada', 'inspecao_avaria_fotos', $foto_id);
        
        enviarResposta(true, "Foto enviada com sucesso", array(
            "foto_id" => $foto_id,
            "nome_arquivo" => $nome_arquivo,
            "url" => "/uploads/avarias/" . $nome_arquivo
        ));
        
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirFoto($foto_id) {
    global $db, $usuario_id;
    
    try {
        // Buscar informações da foto
        $query = "SELECT f.*, a.inspecao_id 
                  FROM inspecao_avaria_fotos f
                  INNER JOIN inspecao_avarias a ON f.avaria_id = a.id
                  WHERE f.id = :foto_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":foto_id", $foto_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Foto não encontrada");
        }
        
        $foto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar se a inspeção pertence ao usuário
        $query_check = "SELECT id FROM inspecoes 
                        WHERE id = :inspecao_id 
                        AND funcionario_id = :funcionario_id 
                        AND status = 'em_andamento'";
        
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":inspecao_id", $foto['inspecao_id']);
        $stmt_check->bindParam(":funcionario_id", $usuario_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() == 0) {
            throw new Exception("Sem permissão para excluir esta foto");
        }
        
        // Excluir arquivo físico
        if (file_exists($foto['caminho_arquivo'])) {
            unlink($foto['caminho_arquivo']);
        }
        
        // Excluir do banco
        $query_delete = "DELETE FROM inspecao_avaria_fotos WHERE id = :foto_id";
        $stmt_delete = $db->prepare($query_delete);
        $stmt_delete->bindParam(":foto_id", $foto_id);
        $stmt_delete->execute();
        
        registrarLog($db, $usuario_id, 'EXCLUIR_FOTO_AVARIA', 'Foto de avaria excluída', 'inspecao_avaria_fotos', $foto_id);
        
        enviarResposta(true, "Foto excluída com sucesso");
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}