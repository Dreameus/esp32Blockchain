<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle click action
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'click') {
    try {
        $conn = getDBConnection();
        
        // Check if there's an active contest
        $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $contest = $stmt->get_result()->fetch_assoc();
        
        if ($contest) {
            // Check if user already has a record
            $stmt = $conn->prepare("SELECT * FROM contest_participants WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $participant = $stmt->get_result()->fetch_assoc();
            
            if ($participant) {
                // Update existing record
                $stmt = $conn->prepare("UPDATE contest_participants SET clicks = clicks + 1 WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $message = "Click recorded! Your total clicks: " . ($participant['clicks'] + 1);
            } else {
                // Create new record
                $stmt = $conn->prepare("INSERT INTO contest_participants (user_id, clicks) VALUES (?, 1)");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $message = "First click recorded! Keep clicking to win!";
            }
            $message_type = 'success';
        } else {
            $message = "No active contest at the moment. Please create a contest first.";
            $message_type = 'error';
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Initialize variables with default values
$current_contest = null;
$user_clicks = 0;
$user_rank = 1;
$leaderboard = [];
$total_participants = 0;

// Get contest data
try {
    $conn = getDBConnection();
    
    // Get current contest
    $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $current_contest = $stmt->get_result()->fetch_assoc();
    
    // Get user's participation data
    $stmt = $conn->prepare("SELECT clicks FROM contest_participants WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_participation = $stmt->get_result()->fetch_assoc();
    $user_clicks = $user_participation ? $user_participation['clicks'] : 0;
    
    // Get user's rank
    $stmt = $conn->prepare("
        SELECT COUNT(*) + 1 as user_rank 
        FROM contest_participants 
        WHERE clicks > (SELECT COALESCE(clicks, 0) FROM contest_participants WHERE user_id = ?)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_rank = $stmt->get_result()->fetch_assoc()['user_rank'];
    
    // Get top 10 leaderboard
    $stmt = $conn->prepare("
        SELECT cp.user_id, u.username, cp.clicks 
        FROM contest_participants cp 
        JOIN users u ON cp.user_id = u.id 
        ORDER BY cp.clicks DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get total participants
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contest_participants WHERE clicks > 0");
    $stmt->execute();
    $total_participants = $stmt->get_result()->fetch_assoc()['count'];
    
    $conn->close();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Check for ended contests and distribute rewards
try {
    $conn = getDBConnection();
    
    // Find contests that have ended but haven't been processed
    $stmt = $conn->prepare("
        SELECT * FROM contests 
        WHERE status = 'active' 
        AND end_time <= NOW() 
        AND id NOT IN (SELECT DISTINCT contest_id FROM contest_winners WHERE contest_id IS NOT NULL)
    ");
    $stmt->execute();
    $ended_contests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($ended_contests as $contest) {
        // Get top 3 participants
        $stmt = $conn->prepare("
            SELECT cp.user_id, u.username, cp.clicks 
            FROM contest_participants cp 
            JOIN users u ON cp.user_id = u.id 
            WHERE cp.clicks > 0
            ORDER BY cp.clicks DESC 
            LIMIT 3
        ");
        $stmt->execute();
        $winners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Define prize amounts
        $prizes = [500, 300, 200]; // 1st, 2nd, 3rd place
        
        // Distribute prizes
        foreach ($winners as $index => $winner) {
            $prize_amount = $prizes[$index];
            
            // Add to user's balance
            $stmt = $conn->prepare("
                UPDATE balances 
                SET balance = balance + ? 
                WHERE user_id = ?
            ");
            $stmt->bind_param("ii", $prize_amount, $winner['user_id']);
            $stmt->execute();
            
            // Record winner
            $stmt = $conn->prepare("
                INSERT INTO contest_winners (user_id, contest_id, position, prize_amount, clicks) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $position = $index + 1;
            $stmt->bind_param("iiiii", $winner['user_id'], $contest['id'], $position, $prize_amount, $winner['clicks']);
            $stmt->execute();
        }
        
        // Mark contest as ended
        $stmt = $conn->prepare("UPDATE contests SET status = 'ended' WHERE id = ?");
        $stmt->bind_param("i", $contest['id']);
        $stmt->execute();
        
        // Clear all participant data to start fresh
        $stmt = $conn->prepare("DELETE FROM contest_participants");
        $stmt->execute();
        
        echo "<script>console.log('Contest ended and participant data cleared');</script>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    // Log error but don't show to user
    error_log("Contest reward distribution error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ESP32 Blockchain - Click Contest</title>
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
            overflow-x: hidden;
        }

        .header {
            background: rgba(0, 0, 0, 0.9);
            border-bottom: 2px solid #ff0088;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 20px rgba(255, 0, 136, 0.3);
        }

        .logo {
            font-size: 2em;
            font-weight: bold;
            text-shadow: 0 0 20px #ff0088;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-badge {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
        }

        .nav-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 136, 0.4);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #ff0088;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 30px rgba(255, 0, 136, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff0088, #ff6666, #ff0088, #ff6666);
            border-radius: 15px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .card-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 0 10px #ff0088;
        }

        .contest-info {
            background: rgba(255, 0, 136, 0.1);
            border: 1px solid #ff0088;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .contest-info h2 {
            color: #ff0088;
            margin-bottom: 15px;
            font-size: 2em;
        }

        .contest-info p {
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .click-btn {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            font-family: 'Courier New', monospace;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px auto;
            display: block;
            text-transform: uppercase;
            letter-spacing: 2px;
            box-shadow: 0 0 30px rgba(255, 0, 136, 0.5);
        }

        .click-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 0 50px rgba(255, 0, 136, 0.8);
        }

        .click-btn:active {
            transform: scale(0.95);
        }

        .click-btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #ff6666;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #ff6666;
        }

        .stat-label {
            font-size: 0.9em;
            color: #ff0088;
            margin-top: 5px;
        }

        .leaderboard {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #ff6666;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }

        .leaderboard-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid rgba(255, 102, 102, 0.3);
        }

        .leaderboard-item:last-child {
            border-bottom: none;
        }

        .leaderboard-item.current-user {
            background: rgba(255, 0, 136, 0.2);
            border-radius: 5px;
        }

        .rank {
            font-weight: bold;
            color: #ff6666;
            min-width: 30px;
        }

        .username {
            flex: 1;
            margin-left: 10px;
        }

        .clicks {
            font-weight: bold;
            color: #ff0088;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }

        .message.success {
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid #00ff00;
            color: #00ff88;
        }

        .message.error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff0000;
            color: #ff6666;
        }

        .prize-info {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid #ffd700;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }

        .prize-info h3 {
            color: #ffd700;
            margin-bottom: 10px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .card {
                padding: 20px;
            }
            
            .click-btn {
                width: 150px;
                height: 150px;
                font-size: 1.2em;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            .card {
                padding: 15px;
            }
            
            .logo {
                font-size: 1.5em;
            }
            
            .click-btn {
                width: 120px;
                height: 120px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">🎯 CLICK CONTEST</div>
        <div class="user-info">
            <div class="user-badge">
                <div>👤 <?php echo htmlspecialchars($username); ?></div>
            </div>
            <a href="dashboard.php" class="nav-btn">Dashboard</a>
            <a href="logout.php" class="nav-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($current_contest): ?>
            <div class="contest-info">
                <h2>🎉 <?php echo htmlspecialchars($current_contest['name']); ?></h2>
                <p><strong>Prize Pool:</strong> <?php echo number_format($current_contest['prize_pool']); ?> ESP32 tokens</p>
                <p><strong>Ends:</strong> <?php echo date('M j, Y g:i A', strtotime($current_contest['end_time'])); ?></p>
                <p><strong>Total Participants:</strong> <?php echo number_format($total_participants); ?></p>
            </div>

            <div class="grid">
                <!-- Click Area -->
                <div class="card">
                    <div class="card-title">Click to Win!</div>
                    <form method="POST" id="clickForm" data-submitted="false">
                        <input type="hidden" name="action" value="click">
                        <button type="submit" class="click-btn" id="clickBtn">
                            CLICK<br>ME!
                        </button>
                    </form>
                    
                    <div class="stats-grid">
                        <div class="stat">
                            <div class="stat-value"><?php echo number_format($user_clicks); ?></div>
                            <div class="stat-label">Your Clicks</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">#<?php echo $user_rank; ?></div>
                            <div class="stat-label">Your Rank</div>
                        </div>
                    </div>
                </div>

                <!-- Leaderboard -->
                <div class="card">
                    <div class="card-title">🏆 Leaderboard</div>
                    <div class="leaderboard">
                        <?php if (empty($leaderboard)): ?>
                            <p style="text-align: center; color: #ff6666;">No participants yet. Be the first to click!</p>
                        <?php else: ?>
                            <?php foreach ($leaderboard as $index => $participant): ?>
                                <div class="leaderboard-item <?php echo ($participant['user_id'] == $user_id) ? 'current-user' : ''; ?>">
                                    <div class="rank">#<?php echo $index + 1; ?></div>
                                    <div class="username"><?php echo htmlspecialchars($participant['username']); ?></div>
                                    <div class="clicks"><?php echo number_format($participant['clicks']); ?> clicks</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="prize-info">
                        <h3>🏅 Prizes</h3>
                        <p><strong>1st Place:</strong> 500 ESP32 tokens</p>
                        <p><strong>2nd Place:</strong> 300 ESP32 tokens</p>
                        <p><strong>3rd Place:</strong> 200 ESP32 tokens</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-title">⏸️ No Active Contest</div>
                <div style="text-align: center; padding: 40px;">
                    <h3 style="color: #ff6666; margin-bottom: 20px;">No contest is currently running</h3>
                    <p style="margin-bottom: 20px;">Check back later for new contests!</p>
                    <a href="dashboard.php" class="nav-btn">Back to Dashboard</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add click animation and form submission
        const clickBtn = document.getElementById('clickBtn');
        const clickForm = document.getElementById('clickForm');
        
        if (clickBtn && clickForm) {
            clickForm.addEventListener('submit', function(e) {
                // Mark form as submitted to prevent auto-refresh
                this.setAttribute('data-submitted', 'true');
                
                // Add visual feedback
                clickBtn.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    clickBtn.style.transform = 'scale(1)';
                }, 100);
                
                // Disable button temporarily to prevent spam
                clickBtn.disabled = true;
                clickBtn.textContent = 'CLICKING...';
                
                // Re-enable after 500ms
                setTimeout(() => {
                    clickBtn.disabled = false;
                    clickBtn.textContent = 'CLICK\nME!';
                }, 500);
            });
        }
        
        // Auto-refresh every 10 seconds (increased to reduce frequency)
        setTimeout(() => {
            // Only refresh if no form was submitted recently
            if (!document.querySelector('form[data-submitted="true"]')) {
                location.reload();
            }
        }, 10000);
    </script>
</body>
</html> 