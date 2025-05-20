<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION["error"] = "Por favor, preencha todos os campos.";
        header("location: ../index.php");
        exit;
    }
    
    // Check user credentials
    $sql = "SELECT id, name, email, password, quit_date, cigarettes_per_day, cost_per_day, points, level FROM users WHERE email = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $id, $name, $email, $hashed_password, $quit_date, $cigarettes_per_day, $cost_per_day, $points, $level);
                
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        // Password is correct, start a new session
                        session_start();
                        
                        // Store data in session variables
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["name"] = $name;
                        $_SESSION["email"] = $email;
                        $_SESSION["quit_date"] = $quit_date;
                        $_SESSION["cigarettes_per_day"] = $cigarettes_per_day;
                        $_SESSION["cost_per_day"] = $cost_per_day;
                        $_SESSION["points"] = $points;
                        $_SESSION["level"] = $level;
                        
                        header("location: ../dashboard.php");
                        exit;
                    } else {
                        $_SESSION["error"] = "Senha incorreta.";
                        header("location: ../index.php");
                        exit;
                    }
                }
            } else {
                $_SESSION["error"] = "E-mail não encontrado.";
                header("location: ../index.php");
                exit;
            }
        } else {
            $_SESSION["error"] = "Erro ao processar login. Tente novamente.";
            header("location: ../index.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);
?>
