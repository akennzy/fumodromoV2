<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION["id"])) {
    $user_id = $_SESSION["id"];
    $today = date("Y-m-d");
    
    $hydration = isset($_POST["hydration"]) ? 1 : 0;
    $breathing = isset($_POST["breathing"]) ? 1 : 0;
    $triggers = isset($_POST["triggers"]) ? 1 : 0;
    
    // Check if all items are completed
    $completed = ($hydration && $breathing && $triggers) ? 1 : 0;
    
    // Check if entry exists for today
    $sql = "SELECT id FROM daily_checklist WHERE user_id = ? AND date = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "is", $user_id, $today);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            // Update existing entry
            $sql = "UPDATE daily_checklist SET 
                    hydration = ?, 
                    conscious_breathing = ?, 
                    trigger_avoidance = ?,
                    completed = ?
                    WHERE user_id = ? AND date = ?";
            
            if ($update_stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($update_stmt, "iiiiss", $hydration, $breathing, $triggers, $completed, $user_id, $today);
                $success = mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
            }
        } else {
            // Create new entry
            $sql = "INSERT INTO daily_checklist 
                    (user_id, date, hydration, conscious_breathing, trigger_avoidance, completed) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            if ($insert_stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($insert_stmt, "isiiii", $user_id, $today, $hydration, $breathing, $triggers, $completed);
                $success = mysqli_stmt_execute($insert_stmt);
                mysqli_stmt_close($insert_stmt);
            }
        }
        
        mysqli_stmt_close($stmt);
          // Check if checklist was already completed today
        $check_sql = "SELECT completed FROM daily_checklist 
                     WHERE user_id = ? AND date = ?";
        if ($check_stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($check_stmt, "is", $user_id, $today);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if (mysqli_stmt_num_rows($check_stmt) > 0) {
                mysqli_stmt_bind_result($check_stmt, $already_completed);
                mysqli_stmt_fetch($check_stmt);
                
                if ($already_completed) {
                    echo json_encode([
                        "success" => true,
                        "locked" => true,
                        "message" => "Checklist já foi completada hoje! Volte amanhã para ganhar mais pontos."
                    ]);
                    exit;
                }
            }
            mysqli_stmt_close($check_stmt);
        }

        // Add points only if completing for the first time today
        if ($completed && !$was_completed) {
            $sql = "UPDATE users SET points = points + 5 WHERE id = ?";
            if ($points_stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($points_stmt, "i", $user_id);
                mysqli_stmt_execute($points_stmt);
                mysqli_stmt_close($points_stmt);
            }
        }
        
        echo json_encode([
            "success" => $success,
            "completed" => $completed,
            "hydration" => $hydration,
            "breathing" => $breathing,
            "triggers" => $triggers,
            "firstTimeToday" => !$was_completed && $completed,
            "message" => $completed ? 
                (!$was_completed ? "Parabéns! Você completou a checklist e ganhou 5 pontos!" : "Checklist já completada hoje!") 
                : "Continue! Complete todos os itens para ganhar 5 pontos."
        ]);
        exit;
    }
}

echo json_encode(["success" => false]);
?>
