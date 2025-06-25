<?php

// api/qrcode-print.php
// Arquivo separado para gerar página de impressão dos QR Codes
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';

$usuario_id = verificarAdmin();
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['checklist_id'])) {
    die("ID do checklist é obrigatório");
}

$checklist_id = $_GET['checklist_id'];

// Buscar dados do checklist e seus itens
$query = "SELECT 
          c.nome as checklist_nome,
          a.nome as armazem_nome, a.codigo as armazem_codigo,
          ci.qr_code_data,
          i.nome as item_nome, i.descricao as item_descricao,
          i.criterios_inspecao
          FROM checklists c
          INNER JOIN armazens a ON c.armazem_id = a.id
          INNER JOIN checklist_itens ci ON c.id = ci.checklist_id
          INNER JOIN itens_inspecao i ON ci.item_id = i.id
          WHERE c.id = :checklist_id AND c.ativo = TRUE
          ORDER BY i.nome";

$stmt = $db->prepare($query);
$stmt->bindParam(":checklist_id", $checklist_id);
$stmt->execute();

$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($items) == 0) {
    die("Checklist não encontrado");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Codes - <?php echo htmlspecialchars($items[0]['checklist_nome']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .qr-container {
                page-break-inside: avoid;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            background: white;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .qr-container {
            border: 2px solid #333;
            padding: 20px;
            text-align: center;
            background: white;
        }
        
        .qr-container h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #333;
        }
        
        .qr-container p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        
        .qr-code {
            margin: 15px auto;
            padding: 10px;
            background: white;
        }
        
        .criterios {
            text-align: left;
            font-size: 11px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .print-button {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px auto;
            display: block;
        }
        
        .print-button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="header no-print">
        <h1>QR Codes para Impressão</h1>
        <h2><?php echo htmlspecialchars($items[0]['checklist_nome']); ?></h2>
        <p>Armazém: <?php echo htmlspecialchars($items[0]['armazem_nome']); ?> (<?php echo htmlspecialchars($items[0]['armazem_codigo']); ?>)</p>
        <button class="print-button" onclick="window.print()">Imprimir QR Codes</button>
    </div>
    
    <div class="qr-grid">
        <?php foreach ($items as $index => $item): ?>
        <div class="qr-container">
            <h3><?php echo htmlspecialchars($item['item_nome']); ?></h3>
            <?php if ($item['item_descricao']): ?>
                <p><?php echo htmlspecialchars($item['item_descricao']); ?></p>
            <?php endif; ?>
            
            <div id="qr-<?php echo $index; ?>" class="qr-code"></div>
            
            <?php if ($item['criterios_inspecao']): ?>
                <div class="criterios">
                    <strong>Critérios:</strong><br>
                    <?php echo nl2br(htmlspecialchars($item['criterios_inspecao'])); ?>
                </div>
            <?php endif; ?>
            
            <p style="margin-top: 10px; font-size: 10px; color: #999;">
                Armazém: <?php echo htmlspecialchars($item['armazem_codigo']); ?>
            </p>
        </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        // Gerar todos os QR codes
        <?php foreach ($items as $index => $item): ?>
        new QRCode(document.getElementById("qr-<?php echo $index; ?>"), {
            text: '<?php echo $item['qr_code_data']; ?>',
            width: 200,
            height: 200,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        <?php endforeach; ?>
    </script>
</body>
</html>