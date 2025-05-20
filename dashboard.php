<?php
// Definir timezone para Brasil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

require_once "config/database.php";

// Get today's checklist status
$today = date("Y-m-d");
$user_id = $_SESSION["id"];
$checklist_status = [
    'locked' => false,
    'completed' => false,
    'hydration' => false,
    'breathing' => false,
    'triggers' => false
];

$sql = "SELECT completed, hydration, conscious_breathing, trigger_avoidance 
        FROM daily_checklist 
        WHERE user_id = ? AND date = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "is", $user_id, $today);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $completed, $hydration, $breathing, $triggers);
    
    if (mysqli_stmt_fetch($stmt)) {
        $checklist_status = [
            'locked' => $completed,
            'completed' => $completed,
            'hydration' => $hydration,
            'breathing' => $breathing,
            'triggers' => $triggers
        ];
    }
    mysqli_stmt_close($stmt);
}

// Get user's stats from database with a single query
$sql = "SELECT u.points, u.quit_date, u.cigarettes_per_day, u.cost_per_day, 
               IFNULL(dc.completed, 0) as checklist_completed,
               IFNULL(dc.hydration, 0) as hydration,
               IFNULL(dc.conscious_breathing, 0) as breathing,
               IFNULL(dc.trigger_avoidance, 0) as triggers
        FROM users u 
        LEFT JOIN daily_checklist dc ON u.id = dc.user_id 
            AND dc.date = CURRENT_DATE
        WHERE u.id = ?";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["id"]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $db_points, $quit_date_str, $cigarettes_per_day, 
                          $cost_per_day, $checklist_completed, $hydration, 
                          $breathing, $triggers);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Initialize checklist status
    $checklist_status = [
        'locked' => (bool)$checklist_completed,
        'completed' => (bool)$checklist_completed,
        'hydration' => (bool)$hydration,
        'breathing' => (bool)$breathing,
        'triggers' => (bool)$triggers
    ];

    // Calculate time-based points
    $quit_date = new DateTime($quit_date_str);
    $now = new DateTime();
    $interval = $now->diff($quit_date);
    $total_hours = ($interval->days * 24) + $interval->h;
    $time_points = $total_hours * 10;

    // Calculate achievements bonus (10% extra points after 7 days, 20% after 30 days)
    $days_smoke_free = $interval->days;
    $achievement_multiplier = 1.0;
    if ($days_smoke_free >= 30) {
        $achievement_multiplier = 1.2;
    } elseif ($days_smoke_free >= 7) {
        $achievement_multiplier = 1.1;
    }

    // Calculate total points with achievements
    $total_points = ($time_points + $db_points) * $achievement_multiplier;
    
    // Calculate level and progress
    $level = 1;
    $points_needed = 10;
    $accumulated_points = 0;
    $points_for_next_level = $points_needed;
    
    while ($total_points >= ($accumulated_points + $points_needed)) {
        $accumulated_points += $points_needed;
        $level++;
        $points_needed += 5;
    }

    $current_level_points = $total_points - $accumulated_points;
    $progress_percentage = ($current_level_points / $points_needed) * 100;

    // Update session variables
    $_SESSION["points"] = round($total_points);
    $_SESSION["level"] = $level;
    $_SESSION["progress"] = min(100, round($progress_percentage));
    $_SESSION["points_for_next_level"] = $points_needed;
    $_SESSION["current_level_points"] = round($current_level_points);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FumodromoV2</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-left">
                <h1>Olá, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h1>
            </div>
            <div class="header-right">
                <a href="history.php" class="btn-secondary">Histórico</a>
                <a href="includes/logout.php" class="btn-logout">Sair</a>
            </div>
        </header>
        
        <div class="progress-section">
            <div class="lung-animation" id="lungAnimation">
                <div class="counter">
                    <span id="days">0</span> dias<br>
                    <span id="hours">0</span>h <span id="minutes">0</span>m
                </div>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <h3>Economia</h3>
                    <p>R$ <span id="savings">0.00</span></p>
                </div>            <div class="stat-card">
                    <h3>Nível</h3>                    <p>Nível <span id="userLevel"><?php echo $_SESSION["level"]; ?></span></p>
                    <div class="progress-bar">
                        <div class="progress" id="levelProgress"></div>
                    </div>
                    <p class="points-info">
                        <span id="currentPoints">0</span> / <span id="nextLevelPoints">0</span> pontos
                    </p>
                    <p class="total-points">
                        Total acumulado: <span id="totalPoints">0</span> pontos
                    </p>
                </div>
            </div>

            <div class="actions-section">
                <a href="history.php" class="btn-secondary">Ver Histórico de Recaídas</a>
                <button id="relapseBtn" class="btn-danger" onclick="confirmRelapse()">Registrar Recaída</button>
            </div>
        </div>

        <div class="checklist-section">
            <h2>Checklist Diária</h2>
            <?php if ($checklist_status['locked']): ?>
                <div class="checklist-locked-message">
                    Checklist completa! Volte amanhã para ganhar mais pontos.
                </div>
            <?php endif; ?>
            <form id="dailyChecklist">
                <div class="checklist-item">
                    <input type="checkbox" id="hydration" name="hydration" 
                           <?php echo $checklist_status['hydration'] ? 'checked' : ''; ?> 
                           <?php echo $checklist_status['locked'] ? 'disabled' : ''; ?>>
                    <label for="hydration">Hidratar-se com frequência</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="breathing" name="breathing" 
                           <?php echo $checklist_status['breathing'] ? 'checked' : ''; ?>
                           <?php echo $checklist_status['locked'] ? 'disabled' : ''; ?>>
                    <label for="breathing">Praticar respiração consciente</label>
                </div>
                <div class="checklist-item">
                    <input type="checkbox" id="triggers" name="triggers" 
                           <?php echo $checklist_status['triggers'] ? 'checked' : ''; ?>
                           <?php echo $checklist_status['locked'] ? 'disabled' : ''; ?>>
                    <label for="triggers">Evitar situações de gatilho</label>
                </div>
                <button type="submit" class="btn-primary" id="saveChecklist" <?php echo $checklist_status['locked'] ? 'disabled' : ''; ?>>
                    <?php echo $checklist_status['locked'] ? 'Checklist Concluída' : 'Salvar Progresso'; ?>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Initial data
        const quitDate = new Date('<?php echo $_SESSION["quit_date"]; ?>');
        const cigarettesPerDay = <?php echo $_SESSION["cigarettes_per_day"]; ?>;
        const costPerDay = <?php echo $_SESSION["cost_per_day"]; ?>;
        let lastPoints = <?php echo $_SESSION["points"]; ?>;
        let currentLevel = <?php echo $_SESSION["level"]; ?>;

        // Format currency
        const formatter = new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        function updateCounter() {
            const now = new Date();
            const diff = now - quitDate;

            // Update time display with animations
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            animateNumber('days', days);
            animateNumber('hours', hours);
            animateNumber('minutes', minutes);

            // Update savings with animation
            const totalDays = diff / (1000 * 60 * 60 * 24);
            const savings = totalDays * costPerDay;
            const savingsElement = document.getElementById('savings');
            animateCurrency(savingsElement, savings);

            // Calculate points
            const totalHours = Math.floor(diff / (1000 * 60 * 60));
            const timePoints = totalHours * 10;
            const dbPoints = <?php echo $db_points; ?>;
            
            // Achievement multiplier
            let multiplier = 1.0;
            if (days >= 30) multiplier = 1.2;
            else if (days >= 7) multiplier = 1.1;
            
            const totalPoints = Math.round((timePoints + dbPoints) * multiplier);
            
            // Update points and level if changed
            if (totalPoints !== lastPoints) {
                updateLevel(totalPoints);
                lastPoints = totalPoints;
            }
        }

        function animateNumber(elementId, value) {
            const element = document.getElementById(elementId);
            const current = parseInt(element.textContent);
            if (current !== value) {
                animateValue(element, current, value, 500);
            }
        }

        function animateCurrency(element, value) {
            const current = parseFloat(element.textContent.replace(/[^0-9,-]/g, ''));
            if (current !== value) {
                animateValue(element, current, value, 500, (val) => formatter.format(val));
            }
        }

        function animateValue(element, start, end, duration, formatter = String) {
            const range = end - start;
            const startTime = performance.now();
            
            function update(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const value = start + (range * progress);
                element.textContent = formatter(Math.round(value * 100) / 100);
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            requestAnimationFrame(update);
        }

        function updateLevel(points) {
            let level = 1;
            let pointsNeeded = 10;
            let accumulatedPoints = 0;

            while (points >= accumulatedPoints + pointsNeeded) {
                accumulatedPoints += pointsNeeded;
                level++;
                pointsNeeded += 5;
            }

            const currentLevelPoints = points - accumulatedPoints;
            const progress = (currentLevelPoints / pointsNeeded) * 100;

            // Animate level change if needed
            if (level !== currentLevel) {
                showLevelUpAnimation(level);
                currentLevel = level;
            }

            // Update UI with smooth transitions
            document.getElementById('userLevel').textContent = level;
            document.getElementById('levelProgress').style.width = `${progress}%`;
            animateNumber('currentPoints', currentLevelPoints);
            animateNumber('nextLevelPoints', pointsNeeded);
            animateNumber('totalPoints', points);
        }

        function showLevelUpAnimation(newLevel) {
            const levelElement = document.getElementById('userLevel').parentElement;
            levelElement.classList.add('level-up');
            
            // Create level up notification
            const notification = document.createElement('div');
            notification.className = 'level-up-notification';
            notification.textContent = `Parabéns! Você alcançou o nível ${newLevel}!`;
            document.body.appendChild(notification);

            // Remove animation classes after animation
            setTimeout(() => {
                levelElement.classList.remove('level-up');
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        // Handle checklist submission
        document.getElementById('dailyChecklist').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const formData = new FormData(form);
            
            // Disable form during submission
            submitButton.disabled = true;
            
            fetch('includes/save_checklist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.completed && data.firstTimeToday) {
                        showSuccess('Parabéns! Você completou a checklist diária e ganhou 5 pontos!');
                        updateCounter(); // Update points immediately
                    } else if (data.completed) {
                        showInfo('Checklist completa! (Pontos já foram adicionados hoje)');
                    } else {
                        showInfo('Progresso salvo! Complete todos os itens para ganhar 5 pontos.');
                    }

                    // Update UI if checklist is now locked
                    if (data.locked) {
                        form.querySelectorAll('input[type="checkbox"]').forEach(input => {
                            input.disabled = true;
                        });
                        submitButton.disabled = true;
                        submitButton.textContent = 'Checklist Concluída';
                    }
                } else {
                    showError('Erro ao salvar checklist. Tente novamente.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Erro ao salvar checklist. Tente novamente.');
            })
            .finally(() => {
                if (!data?.locked) {
                    submitButton.disabled = false;
                }
            });
        });

        // Initialize counters and set up auto-update
        updateCounter();
        setInterval(updateCounter, 60000);

        // Notification functions
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        const showSuccess = (msg) => showNotification(msg, 'success');
        const showError = (msg) => showNotification(msg, 'error');
        const showInfo = (msg) => showNotification(msg, 'info');

        // Confirm relapse registration
        function confirmRelapse() {
            if (confirm('Tem certeza que deseja registrar uma recaída? Isso reiniciará seu progresso.')) {
                fetch('includes/register_relapse.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showInfo(data.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showError('Erro ao registrar recaída: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Erro ao registrar recaída. Tente novamente.');
                });
            }
        }
    </script>
</body>
</html>
