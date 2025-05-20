<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    
    // Converter a data de quit_date para o timezone de São Paulo
    $quit_date = new DateTime($_POST["quit_date"], new DateTimeZone('America/Sao_Paulo'));
    $quit_date = $quit_date->format('Y-m-d H:i:s');
    
    $cigarettes_per_day = (int)$_POST["cigarettes_per_day"];
    $cost_per_day = (float)$_POST["cost_per_day"];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION["error"] = "Por favor, preencha todos os campos.";
        header("location: ../register.php");
        exit;
    }
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $_SESSION["error"] = "Este e-mail já está cadastrado.";
            header("location: ../register.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, password, quit_date, cigarettes_per_day, cost_per_day) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssssid", $name, $email, $hashed_password, $quit_date, $cigarettes_per_day, $cost_per_day);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION["success"] = "Cadastro realizado com sucesso! Faça login para continuar.";
            header("location: ../index.php");
            exit;
        } else {
            $_SESSION["error"] = "Erro ao cadastrar. Tente novamente.";
            header("location: ../register.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?>
