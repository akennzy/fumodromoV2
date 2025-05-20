<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();

// Check if user is already logged in
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FumodromoV2 - Seu Parceiro para Parar de Fumar</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1>FumodromoV2</h1>
            <h2>Seu Parceiro para uma Vida sem Tabaco</h2>
            
            <form action="includes/login.php" method="post">
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn-primary">Entrar</button>
            </form>
            
            <div class="register-link">
                <p>Ainda não tem uma conta? <a href="register.php">Cadastre-se aqui</a></p>
            </div>
        </div>
    </div>
</body>
</html>
