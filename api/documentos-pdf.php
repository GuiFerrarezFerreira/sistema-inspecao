<?php
// api/documentos-pdf.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';
require_once '../helpers/log.php';
require_once '../helpers/validation.php';
require_once '../helpers/response.php';

$usuario_id = verificarAdmin();
$database = new Database();
$db = $database->getConnection();

// Verificar se é download
if (isset($_GET['download'])) {
    baixarDocumento($_GET['download']);
    exit();
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        listarDocumentos();
        break;
    case 'POST':
        uploadDocumento();
        break;
    case 'DELETE':
        excluirDocumento();
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Método não permitido"));
        break;
}

function listarDocumentos() {
    global $db;
    
    $query = "SELECT d.*, e.nome as empresa_nome, u.nome as criado_por_nome
              FROM documentos_pdf d
              LEFT JOIN empresas e ON d.empresa_id = e.id
              INNER JOIN usuarios u ON d.criado_por = u.id
              WHERE d.ativo = TRUE
              ORDER BY d.criado_em DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $documentos = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $documentos[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "descricao" => $row['descricao'],
            "nome_arquivo" => $row['nome_arquivo'],
            "tamanho" => $row['tamanho'],
            "empresa_id" => $row['empresa_id'],
            "empresa_nome" => $row['empresa_nome'],
            "criado_por" => $row['criado_por'],
            "criado_por_nome" => $row['criado_por_nome'],
            "criado_em" => $row['criado_em']
        );
    }
    
    enviarResposta(true, "Documentos listados com sucesso", $documentos);
}

function uploadDocumento() {
    global $db, $usuario_id;
    
    try {
        // Validar dados
        validarCampoObrigatorio($_POST['nome'], "nome");
        
        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erro no upload do arquivo");
        }
        
        $arquivo = $_FILES['arquivo'];
        
        // Validar tipo de arquivo
        if ($arquivo['type'] !== 'application/pdf') {
            throw new Exception("Apenas arquivos PDF são permitidos");
        }
        
        // Validar tamanho (máximo 10MB)
        $tamanho_maximo = 10 * 1024 * 1024; // 10MB
        if ($arquivo['size'] > $tamanho_maximo) {
            throw new Exception("Arquivo muito grande. Tamanho máximo: 10MB");
        }
        
        // Criar diretório se não existir
        $upload_dir = '../uploads/documentos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Gerar nome único para o arquivo
        $nome_arquivo = uniqid('doc_') . '_' . time() . '.pdf';
        $caminho_completo = $upload_dir . $nome_arquivo;
        
        // Mover arquivo
        if (!move_uploaded_file($arquivo['tmp_name'], $caminho_completo)) {
            throw new Exception("Erro ao salvar arquivo");
        }
        
        $db->beginTransaction();
        
        // Salvar informações no banco
        $query = "INSERT INTO documentos_pdf 
                  (nome, descricao, nome_arquivo, caminho_arquivo, tamanho, empresa_id, criado_por, qr_code_data) 
                  VALUES 
                  (:nome, :descricao, :nome_arquivo, :caminho_arquivo, :tamanho, :empresa_id, :criado_por, :qr_code_data)";
        
        $stmt = $db->prepare($query);
        
        $descricao = isset($_POST['descricao']) ? $_POST['descricao'] : null;
        $empresa_id = !empty($_POST['empresa_id']) ? $_POST['empresa_id'] : null;
        
        // Gerar dados do QR Code
        $qr_data = json_encode(array(
            "tipo" => "documento_pdf",
            "id" => null, // Será atualizado depois
            "url" => $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/documentos-pdf.php?download=",
            "timestamp" => time()
        ));
        
        $stmt->bindParam(":nome", $_POST['nome']);
        $stmt->bindParam(":descricao", $descricao);
        $stmt->bindParam(":nome_arquivo", $nome_arquivo);
        $stmt->bindParam(":caminho_arquivo", $caminho_completo);
        $stmt->bindParam(":tamanho", $arquivo['size']);
        $stmt->bindParam(":empresa_id", $empresa_id);
        $stmt->bindParam(":criado_por", $usuario_id);
        $stmt->bindParam(":qr_code_data", $qr_data);
        
        if ($stmt->execute()) {
            $documento_id = $db->lastInsertId();
            
            // Atualizar QR Code data com o ID
            $qr_data_final = json_encode(array(
                "tipo" => "documento_pdf",
                "id" => $documento_id,
                "url" => $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/documentos-pdf.php?download=" . $documento_id,
                "timestamp" => time()
            ));
            
            $query_update = "UPDATE documentos_pdf SET qr_code_data = :qr_code_data WHERE id = :id";
            $stmt_update = $db->prepare($query_update);
            $stmt_update->bindParam(":qr_code_data", $qr_data_final);
            $stmt_update->bindParam(":id", $documento_id);
            $stmt_update->execute();
            
            $db->commit();
            
            registrarLog($db, $usuario_id, 'UPLOAD_PDF', 'Documento PDF enviado: ' . $_POST['nome'], 'documentos_pdf', $documento_id);
            
            enviarResposta(true, "Documento enviado com sucesso", array("id" => $documento_id));
        } else {
            throw new Exception("Erro ao salvar documento");
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        // Remover arquivo se houver erro
        if (isset($caminho_completo) && file_exists($caminho_completo)) {
            unlink($caminho_completo);
        }
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function excluirDocumento() {
    global $db, $usuario_id;
    
    $data = json_decode(file_get_contents("php://input"));
    
    try {
        validarCampoObrigatorio($data->id, "id");
        
        // Buscar informações do documento
        $query = "SELECT * FROM documentos_pdf WHERE id = :id AND ativo = TRUE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            throw new Exception("Documento não encontrado");
        }
        
        $documento = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Desativar documento
        $query_update = "UPDATE documentos_pdf SET ativo = FALSE WHERE id = :id";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bindParam(":id", $data->id);
        
        if ($stmt_update->execute()) {
            // Remover arquivo físico
            if (file_exists($documento['caminho_arquivo'])) {
                unlink($documento['caminho_arquivo']);
            }
            
            registrarLog($db, $usuario_id, 'EXCLUIR_PDF', 'Documento PDF excluído: ' . $documento['nome'], 'documentos_pdf', $data->id);
            
            enviarResposta(true, "Documento excluído com sucesso");
        } else {
            throw new Exception("Erro ao excluir documento");
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        enviarResposta(false, $e->getMessage());
    }
}

function baixarDocumento($documento_id) {
    global $db;
    
    // Buscar documento
    $query = "SELECT * FROM documentos_pdf WHERE id = :id AND ativo = TRUE";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $documento_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        die("Documento não encontrado");
    }
    
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!file_exists($documento['caminho_arquivo'])) {
        http_response_code(404);
        die("Arquivo não encontrado");
    }
    
    // Enviar arquivo para download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $documento['nome'] . '.pdf"');
    header('Content-Length: ' . filesize($documento['caminho_arquivo']));
    readfile($documento['caminho_arquivo']);
    exit();
}