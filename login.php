<?php
session_start();

// Se já estiver logado, redireciona para index
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Checklist de Inspeção</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 20px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .login-container h2 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        button {
            width: 100%;
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #2980b9;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .demo-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .demo-info h4 {
            margin-bottom: 10px;
            color: #004085;
        }
        
        .demo-info p {
            margin: 5px 0;
            color: #004085;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login - Sistema de Inspeção</h2>
        
        <?php if (isset($_GET['erro'])): ?>
            <div class="alert alert-error">
                Usuário ou senha inválidos!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout'])): ?>
            <div class="alert alert-error" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
                Logout realizado com sucesso!
            </div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="processar_login.php">
            <div class="form-group">
                <label for="username">Usuário:</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Entrar</button>
        </form>
        
        <div class="demo-info">
            <h4>Usuários de demonstração:</h4>
            <p><strong>Admin:</strong> admin / admin123</p>
            <p><strong>Funcionário:</strong> func / func123</p>
        </div>
    </div>
</body>
</html>