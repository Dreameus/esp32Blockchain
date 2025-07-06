<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

// Get contest data
try {
    $conn = getDBConnection();
    
    // Get current contest
    $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $current_contest = $stmt->get_result()->fetch_assoc();
    
    // Get top 20 participants
    $stmt = $conn->prepare("
        SELECT cp.user_id, u.username, cp.clicks 
        FROM contest_participants cp 
        JOIN users u ON cp.user_id = u.id 
        WHERE cp.clicks > 0
        ORDER BY cp.clicks DESC 
        LIMIT 20
    ");
    $stmt->execute();
    $leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get total participants
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contest_participants WHERE clicks > 0");
    $stmt->execute();
    $total_participants = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get total clicks
    $stmt = $conn->prepare("SELECT SUM(clicks) as total FROM contest_participants");
    $stmt->execute();
    $total_clicks = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $conn->close();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Blockchain - Contest Leaderboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            overflow: hidden;
        }

        .header {
            background: rgba(0, 0, 0, 0.9);
            border-bottom: 3px solid #ff0088;
            padding: 30px;
            text-align: center;
            box-shadow: 0 0 30px rgba(255, 0, 136, 0.5);
        }

        .title {
            font-size: 4em;
            font-weight: bold;
            text-shadow: 0 0 30px #ff0088;
            margin-bottom: 10px;
            animation: titleGlow 2s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { text-shadow: 0 0 30px #ff0088; }
            to { text-shadow: 0 0 50px #ff0088, 0 0 70px #ff0088; }
        }

        .contest-info {
            font-size: 1.5em;
            color: #ff6666;
            margin-bottom: 10px;
        }

        .stats {
            display: flex;
            justify-content: center;
            gap: 50px;
            font-size: 1.2em;
        }

        .stat {
            background: rgba(255, 0, 136, 0.1);
            border: 1px solid #ff0088;
            padding: 15px 30px;
            border-radius: 10px;
        }

        .main-content {
            display: flex;
            height: calc(100vh - 200px);
            padding: 30px;
        }

        .leaderboard-section {
            flex: 1;
            background: rgba(0, 0, 0, 0.8);
            border: 3px solid #ff0088;
            border-radius: 20px;
            padding: 30px;
            margin-right: 20px;
            box-shadow: 0 0 40px rgba(255, 0, 136, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .leaderboard-section::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            background: linear-gradient(45deg, #ff0088, #ff6666, #ff0088, #ff6666);
            border-radius: 20px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .section-title {
            font-size: 2.5em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 0 0 20px #ff0088;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #ff6666;
            border-radius: 10px;
            font-size: 1.3em;
            transition: all 0.3s ease;
        }

        .leaderboard-item:hover {
            transform: translateX(10px);
            box-shadow: 0 0 20px rgba(255, 102, 102, 0.3);
        }

        .leaderboard-item.top-3 {
            background: linear-gradient(45deg, rgba(255, 215, 0, 0.2), rgba(255, 215, 0, 0.1));
            border-color: #ffd700;
        }

        .rank {
            font-size: 2em;
            font-weight: bold;
            color: #ff6666;
            min-width: 80px;
            text-align: center;
        }

        .rank.gold { color: #ffd700; }
        .rank.silver { color: #c0c0c0; }
        .rank.bronze { color: #cd7f32; }

        .username {
            flex: 1;
            margin-left: 20px;
            font-size: 1.4em;
            font-weight: bold;
        }

        .clicks {
            font-size: 1.6em;
            font-weight: bold;
            color: #ff0088;
            margin-left: 20px;
        }

        .prize-info {
            flex: 1;
            background: rgba(0, 0, 0, 0.8);
            border: 3px solid #ffd700;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 0 40px rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .prize-info::before {
            content: '';
            position: absolute;
            top: -3px;
            left: -3px;
            right: -3px;
            bottom: -3px;
            background: linear-gradient(45deg, #ffd700, #ffed4e, #ffd700, #ffed4e);
            border-radius: 20px;
            z-index: -1;
            animation: prizeGlow 3s ease-in-out infinite;
        }

        @keyframes prizeGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .prize-item {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid #ffd700;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .prize-position {
            font-size: 2em;
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 10px;
        }

        .prize-amount {
            font-size: 1.8em;
            color: #ffed4e;
            margin-bottom: 5px;
        }

        .prize-label {
            font-size: 1.2em;
            color: #ff6666;
        }

        .timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid #ff0088;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 0 20px rgba(255, 0, 136, 0.3);
        }

        .timer-label {
            font-size: 1.2em;
            color: #ff6666;
            margin-bottom: 10px;
        }

        .timer-value {
            font-size: 2em;
            font-weight: bold;
            color: #ff0088;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: #ff0088;
            border-radius: 50%;
            animation: float 8s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
                height: auto;
                padding: 15px;
            }
            
            .leaderboard-section {
                margin-right: 0;
                margin-bottom: 20px;
                padding: 20px;
            }
            
            .title {
                font-size: 2.5em;
            }
            
            .contest-info {
                font-size: 1.2em;
            }
            
            .stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .leaderboard-item {
                padding: 15px;
                font-size: 1.1em;
            }
            
            .rank {
                font-size: 1.5em;
                min-width: 50px;
            }
            
            .username {
                font-size: 1.2em;
            }
            
            .clicks {
                font-size: 1.3em;
            }
            
            .timer {
                position: static;
                margin: 20px auto;
                max-width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="header">
        <div class="title">🏆 CONTEST LEADERBOARD</div>
        <?php if ($current_contest): ?>
            <div class="contest-info"><?php echo htmlspecialchars($current_contest['name']); ?></div>
            <div class="stats">
                <div class="stat">👥 <?php echo number_format($total_participants); ?> Participants</div>
                <div class="stat">🎯 <?php echo number_format($total_clicks); ?> Total Clicks</div>
                <div class="stat">💰 <?php echo number_format($current_contest['prize_pool']); ?> ESP32 Prize Pool</div>
            </div>
        <?php else: ?>
            <div class="contest-info">No Active Contest</div>
        <?php endif; ?>
    </div>

    <?php if ($current_contest): ?>
        <div class="timer">
            <div class="timer-label">Contest Ends In:</div>
            <div class="timer-value" id="countdown">--:--:--</div>
        </div>
    <?php endif; ?>

    <div class="main-content">
        <div class="leaderboard-section">
            <div class="section-title">🎯 TOP CLICKERS</div>
            <?php if (empty($leaderboard)): ?>
                <div style="text-align: center; padding: 50px; font-size: 1.5em; color: #ff6666;">
                    No participants yet. Be the first to click!
                </div>
            <?php else: ?>
                <?php foreach ($leaderboard as $index => $participant): ?>
                    <div class="leaderboard-item <?php echo ($index < 3) ? 'top-3' : ''; ?>">
                        <div class="rank <?php 
                            echo $index === 0 ? 'gold' : 
                                ($index === 1 ? 'silver' : 
                                ($index === 2 ? 'bronze' : '')); 
                        ?>">
                            #<?php echo $index + 1; ?>
                        </div>
                        <div class="username"><?php echo htmlspecialchars($participant['username']); ?></div>
                        <div class="clicks"><?php echo number_format($participant['clicks']); ?> clicks</div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="prize-info">
            <div class="section-title">🏅 PRIZES</div>
            <div class="prize-item">
                <div class="prize-position">🥇 1st Place</div>
                <div class="prize-amount">500 ESP32</div>
                <div class="prize-label">Tokens</div>
            </div>
            <div class="prize-item">
                <div class="prize-position">🥈 2nd Place</div>
                <div class="prize-amount">300 ESP32</div>
                <div class="prize-label">Tokens</div>
            </div>
            <div class="prize-item">
                <div class="prize-position">🥉 3rd Place</div>
                <div class="prize-amount">200 ESP32</div>
                <div class="prize-label">Tokens</div>
            </div>
            
            <div style="text-align: center; margin-top: 30px; padding: 20px; background: rgba(255, 0, 136, 0.1); border-radius: 10px;">
                <h3 style="color: #ff0088; margin-bottom: 10px;">How to Win</h3>
                <p style="color: #ff6666; line-height: 1.6;">
                    Click the button as many times as you can!<br>
                    The user with the most clicks wins!<br>
                    Contest ends automatically when time runs out.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 100;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 8 + 's';
                particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        createParticles();

        // Countdown timer
        <?php if ($current_contest): ?>
        function updateCountdown() {
            const endTime = new Date('<?php echo $current_contest['end_time']; ?>').getTime();
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance > 0) {
                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('countdown').innerHTML = 
                    (hours < 10 ? '0' : '') + hours + ':' +
                    (minutes < 10 ? '0' : '') + minutes + ':' +
                    (seconds < 10 ? '0' : '') + seconds;
            } else {
                document.getElementById('countdown').innerHTML = 'ENDED';
                // Auto-refresh to show final results
                setTimeout(() => location.reload(), 5000);
            }
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
        <?php endif; ?>

        // Auto-refresh every 5 seconds
        setTimeout(() => {
            location.reload();
        }, 5000);
    </script>
</body>
</html> 