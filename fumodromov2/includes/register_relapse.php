<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION["id"])) {
    $user_id = $_SESSION["id"];
      // Configurar timezone do MySQL para São Paulo
    mysqli_query($conn, "SET time_zone = '-03:00'");    // Registrar a data exata da recaída no fuso horário de Brasília
    $now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
    $relapse_date = $now->format("Y-m-d H:i:s.u");
    
    // Calcular duração da abstinência
    try {
        // Obter data do último cigarro
        $quit_date = new DateTime($_SESSION["quit_date"], new DateTimeZone('America/Sao_Paulo'));
        
        if ($quit_date > $now) {
            throw new Exception("Data de parada no futuro");
        }
        
        // Calcular diferença exata
        $interval = $now->diff($quit_date);
        
        // Converter para minutos
        $abstinence_duration = ($interval->days * 24 * 60) + 
                             ($interval->h * 60) + 
                             $interval->i;
        
    } catch (Exception $e) {
        error_log("Erro ao calcular abstinência: " . $e->getMessage());
        $abstinence_duration = 0;
    }
    
    $level_reached = $_SESSION["level"];
    $points_lost = $_SESSION["points"];
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Insert relapse record
        $sql = "INSERT INTO relapses (user_id, relapse_date, abstinence_duration, level_reached, points_lost) 
                VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "isiii", $user_id, $relapse_date, $abstinence_duration, $level_reached, $points_lost);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Reset user's progress
            $sql = "UPDATE users SET 
                    quit_date = ?,
                    points = 0,
                    level = 1
                    WHERE id = ?";
            
            if ($update_stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($update_stmt, "si", $relapse_date, $user_id);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                
                // Update session variables
                $_SESSION["quit_date"] = $relapse_date;
                $_SESSION["points"] = 0;
                $_SESSION["level"] = 1;
                
                mysqli_commit($conn);
                
                echo json_encode([
                    "success" => true,
                    "message" => "Recaída registrada. Não desista, cada tentativa é um aprendizado!"
                ]);
                exit;
            }
        }
        
        throw new Exception("Erro ao registrar recaída");
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
        exit;
    }
}

echo json_encode([
    "success" => false,
    "message" => "Requisição inválida"
]);
?>
