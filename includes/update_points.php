<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION["id"])) {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = $_SESSION["id"];
    $points = isset($data['points']) ? (int)$data['points'] : 0;
    
    // Atualizar pontos do usuário
    $sql = "UPDATE users SET points = points + ? WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $points, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                "success" => true,
                "message" => "Pontos atualizados com sucesso!"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Erro ao atualizar pontos."
            ]);
        }
        mysqli_stmt_close($stmt);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Requisição inválida"
    ]);
}
?>
