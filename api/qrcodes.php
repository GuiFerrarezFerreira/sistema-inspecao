<?php
// api/qrcodes.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';
require_once '../helpers/validation.php';
require_once '../helpers/response.php';

$usuario_id = verificarAdmin();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(array("message" => "Método não permitido"));
    exit();
}

if (!isset($_GET['checklist_id'])) {
    http_response_code(400);
    echo json_encode(array("message" => "ID do checklist é obrigatório"));
    exit();
}

$checklist_id = $_GET['checklist_id'];

// Buscar dados do checklist e seus itens
$query = "SELECT 
          c.id as checklist_id, c.nome as checklist_nome,
          a.nome as armazem_nome, a.codigo as armazem_codigo,
          ci.id as checklist_item_id, ci.qr_code_data,
          i.id as item_id, i.nome as item_nome, i.descricao as item_descricao
          FROM checklists c
          INNER JOIN armazens a ON c.armazem_id = a.id
          INNER JOIN checklist_itens ci ON c.id = ci.checklist_id
          INNER JOIN itens_inspecao i ON ci.item_id = i.id
          WHERE c.id = :checklist_id AND c.ativo = TRUE
          ORDER BY i.nome";

$stmt = $db->prepare($query);
$stmt->bindParam(":checklist_id", $checklist_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    http_response_code(404);
    echo json_encode(array("message" => "Checklist não encontrado"));
    exit();
}

$qrcodes = array();
$checklist_info = null;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($checklist_info === null) {
        $checklist_info = array(
            "checklist_id" => $row['checklist_id'],
            "checklist_nome" => $row['checklist_nome'],
            "armazem_nome" => $row['armazem_nome'],
            "armazem_codigo" => $row['armazem_codigo']
        );
    }
    
    $qrcodes[] = array(
        "checklist_item_id" => $row['checklist_item_id'],
        "item_id" => $row['item_id'],
        "item_nome" => $row['item_nome'],
        "item_descricao" => $row['item_descricao'],
        "qr_code_data" => $row['qr_code_data']
    );
}

$response = array(
    "checklist" => $checklist_info,
    "qrcodes" => $qrcodes
);

enviarResposta(true, "QR Codes obtidos com sucesso", $response);
