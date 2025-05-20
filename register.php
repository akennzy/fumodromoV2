<?php
session_start();

// Check if already logged in
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
    <title>Cadastro - FumodromoV2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="register-container">
            <h1>Criar Conta</h1>
            <h2>Comece sua jornada sem tabaco</h2>
            
            <form action="includes/register_handler.php" method="post">
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                  <div class="form-group">
                    <label>Último cigarro (data e hora)</label>
                    <input type="datetime-local" name="quit_date" required value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
                
                <div class="form-group">
                    <label>Quantidade média de cigarros por dia</label>
                    <input type="number" name="cigarettes_per_day" required min="1">
                </div>
                
                <div class="form-group">
                    <label>Gasto médio diário com cigarros (R$)</label>
                    <input type="number" name="cost_per_day" required min="0" step="0.01">
                </div>
                
                <button type="submit" class="btn-primary">Cadastrar</button>
            </form>
            
            <div class="register-link">
                <p>Já tem uma conta? <a href="index.php">Entre aqui</a></p>
            </div>
        </div>
    </div>
</body>
</html>
