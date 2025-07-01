<?php
// api/inspecao-detalhes.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';
require_once '../helpers/response.php';

$usuario_id = verificarAdmin();
$database = new Database();
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(array("message" => "ID da inspeção é obrigatório"));
    exit();
}

$inspecao_id = $_GET['id'];

try {
    // Buscar dados gerais da inspeção
    $query = "SELECT 
              i.id, i.data_inicio, i.data_fim, i.status, i.observacoes_gerais,
              c.nome as checklist_nome, c.periodicidade,
              a.nome as armazem_nome, a.codigo as armazem_codigo, a.localizacao as armazem_localizacao,
              u.nome as funcionario_nome, u.cargo as funcionario_cargo
              FROM inspecoes i
              INNER JOIN checklists c ON i.checklist_id = c.id
              INNER JOIN armazens a ON c.armazem_id = a.id
              INNER JOIN usuarios u ON i.funcionario_id = u.id
              WHERE i.id = :inspecao_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":inspecao_id", $inspecao_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(array("message" => "Inspeção não encontrada"));
        exit();
    }
    
    $inspecao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $query_itens = "SELECT 
                    ir.item_id, ir.status, ir.observacoes, ir.data_verificacao, ir.qr_code_lido, ir.tem_avaria,
                    ii.nome as item_nome, ii.descricao as item_descricao, ii.criterios_inspecao
                    FROM inspecao_resultados ir
                    INNER JOIN itens_inspecao ii ON ir.item_id = ii.id
                    WHERE ir.inspecao_id = :inspecao_id
                    ORDER BY ii.nome";
    
    $stmt_itens = $db->prepare($query_itens);
    $stmt_itens->bindParam(":inspecao_id", $inspecao_id);
    $stmt_itens->execute();
    
    $itens = array();
    $total_ok = 0;
    $total_problema = 0;
    $total_avarias = 0;
    
    while ($row = $stmt_itens->fetch(PDO::FETCH_ASSOC)) {
        // Buscar informações de avaria se houver
        $avaria = null;
        if ($row['tem_avaria']) {
            $query_avaria = "SELECT a.*, 
                            (SELECT COUNT(*) FROM inspecao_avaria_fotos WHERE avaria_id = a.id) as total_fotos
                            FROM inspecao_avarias a
                            WHERE a.inspecao_id = :inspecao_id AND a.item_id = :item_id
                            LIMIT 1";
            
            $stmt_avaria = $db->prepare($query_avaria);
            $stmt_avaria->bindParam(":inspecao_id", $inspecao_id);
            $stmt_avaria->bindParam(":item_id", $row['item_id']);
            $stmt_avaria->execute();
            
            if ($stmt_avaria->rowCount() > 0) {
                $avaria = $stmt_avaria->fetch(PDO::FETCH_ASSOC);
                
                // Buscar fotos da avaria
                $query_fotos = "SELECT id, nome_arquivo FROM inspecao_avaria_fotos 
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
                        "url" => "/uploads/avarias/" . $foto['nome_arquivo']
                    );
                }
                
                $avaria['fotos'] = $fotos;
                $total_avarias++;
            }
        }
        
        $itens[] = array(
            "item_id" => $row['item_id'],
            "item_nome" => $row['item_nome'],
            "item_descricao" => $row['item_descricao'],
            "criterios_inspecao" => $row['criterios_inspecao'],
            "status" => $row['status'],
            "observacoes" => $row['observacoes'],
            "data_verificacao" => $row['data_verificacao'],
            "qr_code_lido" => $row['qr_code_lido'],
            "tem_avaria" => $row['tem_avaria'],
            "avaria" => $avaria
        );
        
        if ($row['status'] == 'ok') {
            $total_ok++;
        } else {
            $total_problema++;
        }
    }
    
    // Calcular duração da inspeção
    $duracao = null;
    if ($inspecao['data_fim']) {
        $inicio = new DateTime($inspecao['data_inicio']);
        $fim = new DateTime($inspecao['data_fim']);
        $intervalo = $inicio->diff($fim);
        $duracao = array(
            "horas" => $intervalo->h,
            "minutos" => $intervalo->i
        );
    }
    
    $resposta = array(
        "inspecao" => array(
            "id" => $inspecao['id'],
            "checklist_nome" => $inspecao['checklist_nome'],
            "periodicidade" => $inspecao['periodicidade'],
            "armazem" => array(
                "nome" => $inspecao['armazem_nome'],
                "codigo" => $inspecao['armazem_codigo'],
                "localizacao" => $inspecao['armazem_localizacao']
            ),
            "funcionario" => array(
                "nome" => $inspecao['funcionario_nome'],
                "cargo" => $inspecao['funcionario_cargo']
            ),
            "data_inicio" => $inspecao['data_inicio'],
            "data_fim" => $inspecao['data_fim'],
            "status" => $inspecao['status'],
            "observacoes_gerais" => $inspecao['observacoes_gerais'],
            "duracao" => $duracao
        ),
        "resumo" => array(
            "total_itens" => count($itens),
            "itens_ok" => $total_ok,
            "itens_problema" => $total_problema,
            "total_avarias" => $total_avarias,
            "taxa_conformidade" => count($itens) > 0 ? round(($total_ok / count($itens)) * 100, 2) : 0
        ),
        "itens" => $itens
    );
    
    enviarResposta(true, "Detalhes da inspeção obtidos com sucesso", $resposta);
    
} catch (Exception $e) {
    http_response_code(500);
    enviarResposta(false, "Erro ao buscar detalhes da inspeção: " . $e->getMessage());
}