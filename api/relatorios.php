<?php
// api/relatorios.php
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

$tipo_relatorio = isset($_GET['tipo']) ? $_GET['tipo'] : 'geral';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-01');
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-t');

switch ($tipo_relatorio) {
    case 'geral':
        relatorioGeral($data_inicio, $data_fim);
        break;
    case 'problemas':
        relatorioProblemas($data_inicio, $data_fim);
        break;
    case 'funcionarios':
        relatorioFuncionarios($data_inicio, $data_fim);
        break;
    case 'armazens':
        relatorioArmazens($data_inicio, $data_fim);
        break;
    default:
        http_response_code(400);
        echo json_encode(array("message" => "Tipo de relatório inválido"));
        break;
}

function relatorioGeral($data_inicio, $data_fim) {
    global $db;
    
    // Estatísticas gerais
    $query_stats = "SELECT 
                    COUNT(DISTINCT i.id) as total_inspecoes,
                    COUNT(DISTINCT CASE WHEN i.status = 'concluida' THEN i.id END) as inspecoes_concluidas,
                    COUNT(DISTINCT CASE WHEN i.status = 'em_andamento' THEN i.id END) as inspecoes_andamento,
                    COUNT(DISTINCT ir.id) as total_itens_verificados,
                    COUNT(DISTINCT CASE WHEN ir.status = 'ok' THEN ir.id END) as itens_ok,
                    COUNT(DISTINCT CASE WHEN ir.status = 'problema' THEN ir.id END) as itens_problema,
                    COUNT(DISTINCT c.id) as total_checklists_ativos,
                    COUNT(DISTINCT a.id) as total_armazens_ativos
                    FROM inspecoes i
                    LEFT JOIN inspecao_resultados ir ON i.id = ir.inspecao_id
                    LEFT JOIN checklists c ON i.checklist_id = c.id AND c.ativo = TRUE
                    LEFT JOIN armazens a ON c.armazem_id = a.id AND a.ativo = TRUE
                    WHERE i.data_inicio BETWEEN :data_inicio AND :data_fim";
    
    $stmt_stats = $db->prepare($query_stats);
    $stmt_stats->bindParam(":data_inicio", $data_inicio);
    $stmt_stats->bindParam(":data_fim", $data_fim);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Taxa de conformidade
    $taxa_conformidade = 0;
    if ($stats['total_itens_verificados'] > 0) {
        $taxa_conformidade = round(($stats['itens_ok'] / $stats['total_itens_verificados']) * 100, 2);
    }
    
    // Inspeções por período
    $query_periodo = "SELECT 
                      DATE(i.data_inicio) as data,
                      COUNT(DISTINCT i.id) as total_inspecoes,
                      COUNT(DISTINCT CASE WHEN ir.status = 'problema' THEN ir.id END) as total_problemas
                      FROM inspecoes i
                      LEFT JOIN inspecao_resultados ir ON i.id = ir.inspecao_id
                      WHERE i.data_inicio BETWEEN :data_inicio AND :data_fim
                      GROUP BY DATE(i.data_inicio)
                      ORDER BY data";
    
    $stmt_periodo = $db->prepare($query_periodo);
    $stmt_periodo->bindParam(":data_inicio", $data_inicio);
    $stmt_periodo->bindParam(":data_fim", $data_fim);
    $stmt_periodo->execute();
    
    $inspecoes_por_dia = array();
    while ($row = $stmt_periodo->fetch(PDO::FETCH_ASSOC)) {
        $inspecoes_por_dia[] = $row;
    }
    
    $relatorio = array(
        "periodo" => array(
            "inicio" => $data_inicio,
            "fim" => $data_fim
        ),
        "estatisticas" => array(
            "total_inspecoes" => intval($stats['total_inspecoes']),
            "inspecoes_concluidas" => intval($stats['inspecoes_concluidas']),
            "inspecoes_andamento" => intval($stats['inspecoes_andamento']),
            "total_itens_verificados" => intval($stats['total_itens_verificados']),
            "itens_ok" => intval($stats['itens_ok']),
            "itens_problema" => intval($stats['itens_problema']),
            "taxa_conformidade" => $taxa_conformidade,
            "checklists_ativos" => intval($stats['total_checklists_ativos']),
            "armazens_ativos" => intval($stats['total_armazens_ativos'])
        ),
        "inspecoes_por_dia" => $inspecoes_por_dia
    );
    
    enviarResposta(true, "Relatório geral gerado com sucesso", $relatorio);
}

function relatorioProblemas($data_inicio, $data_fim) {
    global $db;
    
    // Itens mais problemáticos
    $query = "CALL sp_relatorio_itens_problematicos(:data_inicio, :data_fim)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":data_inicio", $data_inicio);
    $stmt->bindParam(":data_fim", $data_fim);
    $stmt->execute();
    
    $itens_problematicos = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $itens_problematicos[] = array(
            "item_nome" => $row['item_nome'],
            "armazem_nome" => $row['armazem_nome'],
            "total_problemas" => intval($row['total_problemas']),
            "total_ok" => intval($row['total_ok']),
            "total_inspecoes" => intval($row['total_inspecoes']),
            "percentual_problemas" => floatval($row['percentual_problemas'])
        );
    }
    
    // Problemas por categoria
    $query_categorias = "SELECT 
                         ir.observacoes,
                         COUNT(*) as quantidade
                         FROM inspecao_resultados ir
                         INNER JOIN inspecoes i ON ir.inspecao_id = i.id
                         WHERE ir.status = 'problema' 
                         AND i.data_inicio BETWEEN :data_inicio AND :data_fim
                         AND ir.observacoes IS NOT NULL
                         GROUP BY ir.observacoes
                         ORDER BY quantidade DESC
                         LIMIT 10";
    
    $stmt_cat = $db->prepare($query_categorias);
    $stmt_cat->bindParam(":data_inicio", $data_inicio);
    $stmt_cat->bindParam(":data_fim", $data_fim);
    $stmt_cat->execute();
    
    $problemas_frequentes = array();
    while ($row = $stmt_cat->fetch(PDO::FETCH_ASSOC)) {
        $problemas_frequentes[] = array(
            "descricao" => $row['observacoes'],
            "quantidade" => intval($row['quantidade'])
        );
    }
    
    $relatorio = array(
        "periodo" => array(
            "inicio" => $data_inicio,
            "fim" => $data_fim
        ),
        "itens_problematicos" => $itens_problematicos,
        "problemas_frequentes" => $problemas_frequentes
    );
    
    enviarResposta(true, "Relatório de problemas gerado com sucesso", $relatorio);
}

function relatorioFuncionarios($data_inicio, $data_fim) {
    global $db;
    
    $query = "SELECT 
              u.id, u.nome, u.cargo,
              COUNT(DISTINCT i.id) as total_inspecoes,
              COUNT(DISTINCT CASE WHEN i.status = 'concluida' THEN i.id END) as inspecoes_concluidas,
              AVG(TIMESTAMPDIFF(MINUTE, i.data_inicio, i.data_fim)) as tempo_medio_minutos,
              COUNT(DISTINCT ir.id) as total_itens_verificados,
              COUNT(DISTINCT CASE WHEN ir.status = 'ok' THEN ir.id END) as itens_ok,
              COUNT(DISTINCT CASE WHEN ir.status = 'problema' THEN ir.id END) as itens_problema
              FROM usuarios u
              LEFT JOIN inspecoes i ON u.id = i.funcionario_id 
                   AND i.data_inicio BETWEEN :data_inicio AND :data_fim
              LEFT JOIN inspecao_resultados ir ON i.id = ir.inspecao_id
              WHERE u.tipo = 'funcionario' AND u.ativo = TRUE
              GROUP BY u.id
              ORDER BY total_inspecoes DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":data_inicio", $data_inicio);
    $stmt->bindParam(":data_fim", $data_fim);
    $stmt->execute();
    
    $funcionarios = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $taxa_conformidade = 0;
        if ($row['total_itens_verificados'] > 0) {
            $taxa_conformidade = round(($row['itens_ok'] / $row['total_itens_verificados']) * 100, 2);
        }
        
        $funcionarios[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "cargo" => $row['cargo'],
            "total_inspecoes" => intval($row['total_inspecoes']),
            "inspecoes_concluidas" => intval($row['inspecoes_concluidas']),
            "tempo_medio_minutos" => round($row['tempo_medio_minutos'], 0),
            "total_itens_verificados" => intval($row['total_itens_verificados']),
            "itens_ok" => intval($row['itens_ok']),
            "itens_problema" => intval($row['itens_problema']),
            "taxa_conformidade" => $taxa_conformidade
        );
    }
    
    $relatorio = array(
        "periodo" => array(
            "inicio" => $data_inicio,
            "fim" => $data_fim
        ),
        "funcionarios" => $funcionarios
    );
    
    enviarResposta(true, "Relatório de funcionários gerado com sucesso", $relatorio);
}

function relatorioArmazens($data_inicio, $data_fim) {
    global $db;
    
    $query = "SELECT 
              a.id, a.nome, a.codigo, a.localizacao,
              COUNT(DISTINCT i.id) as total_inspecoes,
              COUNT(DISTINCT ir.id) as total_itens_verificados,
              COUNT(DISTINCT CASE WHEN ir.status = 'ok' THEN ir.id END) as itens_ok,
              COUNT(DISTINCT CASE WHEN ir.status = 'problema' THEN ir.id END) as itens_problema,
              COUNT(DISTINCT ii.id) as total_itens_cadastrados
              FROM armazens a
              LEFT JOIN itens_inspecao ii ON a.id = ii.armazem_id AND ii.ativo = TRUE
              LEFT JOIN checklists c ON a.id = c.armazem_id AND c.ativo = TRUE
              LEFT JOIN inspecoes i ON c.id = i.checklist_id 
                   AND i.data_inicio BETWEEN :data_inicio AND :data_fim
              LEFT JOIN inspecao_resultados ir ON i.id = ir.inspecao_id
              WHERE a.ativo = TRUE
              GROUP BY a.id
              ORDER BY a.nome";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":data_inicio", $data_inicio);
    $stmt->bindParam(":data_fim", $data_fim);
    $stmt->execute();
    
    $armazens = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $taxa_conformidade = 0;
        if ($row['total_itens_verificados'] > 0) {
            $taxa_conformidade = round(($row['itens_ok'] / $row['total_itens_verificados']) * 100, 2);
        }
        
        $armazens[] = array(
            "id" => $row['id'],
            "nome" => $row['nome'],
            "codigo" => $row['codigo'],
            "localizacao" => $row['localizacao'],
            "total_inspecoes" => intval($row['total_inspecoes']),
            "total_itens_verificados" => intval($row['total_itens_verificados']),
            "itens_ok" => intval($row['itens_ok']),
            "itens_problema" => intval($row['itens_problema']),
            "total_itens_cadastrados" => intval($row['total_itens_cadastrados']),
            "taxa_conformidade" => $taxa_conformidade
        );
    }
    
    $relatorio = array(
        "periodo" => array(
            "inicio" => $data_inicio,
            "fim" => $data_fim
        ),
        "armazens" => $armazens
    );
    
    enviarResposta(true, "Relatório de armazéns gerado com sucesso", $relatorio);
}