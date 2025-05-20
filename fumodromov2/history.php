<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Get relapse history with microseconds precision for accurate ordering
// Buscar histórico de recaídas ordenado por data mais recente
$sql = "SELECT 
            relapse_date,
            abstinence_duration,
            level_reached,
            points_lost,
            created_at
        FROM relapses 
        WHERE user_id = ? 
        ORDER BY relapse_date DESC, id DESC";

$relapses = [];
$total_attempts = 0;
$max_abstinence = 0;
$total_duration = 0;

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $relapse_date, $abstinence_duration, $level_reached, $points_lost, $created_at);
    
    while (mysqli_stmt_fetch($stmt)) {
        $relapses[] = [
            'date' => $relapse_date,
            'duration' => $abstinence_duration,
            'level' => $level_reached,
            'points' => $points_lost
        ];
        
        $total_attempts++;
        $max_abstinence = max($max_abstinence, $abstinence_duration);
        $total_duration += $abstinence_duration;
    }
    
    mysqli_stmt_close($stmt);
}

$average_duration = $total_attempts > 0 ? $total_duration / $total_attempts : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Recaídas - FumodromoV2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <h1>Histórico de Recaídas</h1>
            <a href="dashboard.php" class="btn-back">Voltar ao Painel</a>
        </header>

        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total de Tentativas</h3>
                    <p><?php echo $total_attempts; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Maior Tempo sem Fumar</h3>
                    <p><?php echo formatDuration($max_abstinence); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Média entre Recaídas</h3>
                    <p><?php echo formatDuration($average_duration); ?></p>
                </div>
            </div>
        </div>

        <div class="history-section">
            <?php if (empty($relapses)): ?>
                <p class="no-records">Nenhuma recaída registrada. Continue firme!</p>
            <?php else: ?>                <div class="history-list">
                    <?php foreach ($relapses as $relapse): ?>
                        <div class="history-item">
                            <div class="history-header">
                                <div class="history-date">
                                    <i class="event-icon">📅</i>
                                    <strong><?php echo formatDateTime($relapse['date']); ?></strong>
                                </div>
                                <div class="duration-badge">
                                    <i class="time-icon">⏱️</i>
                                    <strong>Tempo sem fumar: </strong><?php echo formatDuration($relapse['duration']); ?>
                                </div>
                            </div>
                            <div class="history-details">
                                <div class="achievement">
                                    <div class="achievement-icon">🏆</div>
                                    <div class="achievement-info">
                                        <p class="achievement-label">Nível Alcançado</p>
                                        <p class="achievement-value">Nível <?php echo $relapse['level']; ?></p>
                                    </div>
                                </div>
                                <div class="points">
                                    <div class="points-icon">✨</div>
                                    <div class="points-info">
                                        <p class="points-label">Pontos Acumulados</p>
                                        <p class="points-value"><?php echo number_format($relapse['points'], 0, ',', '.'); ?> pts</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php    function formatDuration($minutes) {
        if ($minutes < 1) {
            return "Menos de 1 minuto";
        }

        $days = floor($minutes / (24 * 60));
        $hours = floor(($minutes % (24 * 60)) / 60);
        $mins = $minutes % 60;
        
        $parts = [];
        
        // Sempre incluir dias se houver
        if ($days > 0) {
            $parts[] = "$days dia" . ($days > 1 ? "s" : "");
        }
        
        // Sempre incluir horas se houver
        if ($hours > 0) {
            $parts[] = "$hours hora" . ($hours > 1 ? "s" : "");
        }
        
        // Sempre incluir minutos se houver
        if ($mins > 0) {
            $parts[] = "$mins minuto" . ($mins > 1 ? "s" : "");
        }
        
        return implode(', ', $parts);
    }function formatDateTime($date) {
        try {
            // Converter para DateTime mantendo o timezone de Brasília
            $datetime = new DateTime($date, new DateTimeZone('America/Sao_Paulo'));
            return $datetime->format('d/m/Y H:i');
        } catch (Exception $e) {
            error_log("Erro ao formatar data: " . $e->getMessage());
            return "Data inválida";
        }
    }
    ?>
</body>
</html>
