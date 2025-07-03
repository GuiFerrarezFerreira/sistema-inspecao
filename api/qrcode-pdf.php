<?php
// api/qrcode-pdf.php
require_once '../config/database.php';
require_once '../config/cors.php';
require_once '../helpers/auth.php';

$usuario_id = verificarAdmin();
$database = new Database();
$db = $database->getConnection();

if (!isset($_GET['documento_id'])) {
    die("ID do documento é obrigatório");
}

$documento_id = $_GET['documento_id'];

// Buscar dados do documento
$query = "SELECT d.*, e.nome as empresa_nome
          FROM documentos_pdf d
          LEFT JOIN empresas e ON d.empresa_id = e.id
          WHERE d.id = :documento_id AND d.ativo = TRUE";

$stmt = $db->prepare($query);
$stmt->bindParam(":documento_id", $documento_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    die("Documento não encontrado");
}

$documento = $stmt->fetch(PDO::FETCH_ASSOC);

// URL para download do PDF
$download_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . 
                dirname(dirname($_SERVER['REQUEST_URI'])) . 
                '/api/documentos-pdf.php?download=' . $documento_id;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($documento['nome']); ?></title>
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
        }
        
        body {
            font-family: Arial, sans-serif;
            background: white;
            margin: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .container {
            text-align: center;
            max-width: 600px;
            width: 100%;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .info {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .info p {
            margin: 10px 0;
            font-size: 16px;
            color: #555;
        }
        
        .qr-container {
            border: 3px solid #3498db;
            padding: 30px;
            margin: 20px auto;
            display: inline-block;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .qr-code {
            margin: 0 auto;
        }
        
        .instructions {
            margin-top: 30px;
            padding: 20px;
            background-color: #e3f2fd;
            border-radius: 8px;
        }
        
        .instructions h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .instructions ol {
            text-align: left;
            margin: 0 auto;
            max-width: 400px;
        }
        
        .print-button {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px;
        }
        
        .print-button:hover {
            background: #2980b9;
        }
        
        .url-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            word-break: break-all;
            font-size: 12px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>QR Code para Documento PDF</h1>
        </div>
        
        <div class="info">
            <h2><?php echo htmlspecialchars($documento['nome']); ?></h2>
            <?php if ($documento['descricao']): ?>
                <p><strong>Descrição:</strong> <?php echo htmlspecialchars($documento['descricao']); ?></p>
            <?php endif; ?>
            <?php if ($documento['empresa_nome']): ?>
                <p><strong>Empresa:</strong> <?php echo htmlspecialchars($documento['empresa_nome']); ?></p>
            <?php endif; ?>
            <p><strong>Tamanho:</strong> <?php echo number_format($documento['tamanho'] / 1024, 2); ?> KB</p>
        </div>
        
        <div class="qr-container">
            <div id="qrcode" class="qr-code"></div>
        </div>
        
        <div class="instructions">
            <h3>Como usar este QR Code:</h3>
            <ol>
                <li>Imprima esta página ou salve o QR Code</li>
                <li>Cole o QR Code em um local visível</li>
                <li>Escaneie com qualquer leitor de QR Code</li>
                <li>O PDF será baixado automaticamente</li>
            </ol>
        </div>
        
        <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir QR Code</button>
        
        <div class="url-info no-print">
            <strong>URL do documento:</strong><br>
            <?php echo htmlspecialchars($download_url); ?>
        </div>
    </div>
    
    <script>
        // Gerar QR Code
        new QRCode(document.getElementById("qrcode"), {
            text: '<?php echo $download_url; ?>',
            width: 300,
            height: 300,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>