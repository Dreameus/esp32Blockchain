<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Handle admin actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $conn = getDBConnection();
            
            switch ($_POST['action']) {
                case 'start_contest':
                    // Start new contest
                    $stmt = $conn->prepare("INSERT INTO contests (name, start_time, end_time, prize_pool, status) VALUES (?, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR), ?, 'active')");
                    $contest_name = $_POST['contest_name'] ?? 'Click Contest';
                    $prize_pool = $_POST['prize_pool'] ?? 1000;
                    $stmt->bind_param("si", $contest_name, $prize_pool);
                    $stmt->execute();
                    $message = "Contest started successfully!";
                    $message_type = 'success';
                    break;
                    
                case 'end_contest':
                    // End current contest
                    $stmt = $conn->prepare("UPDATE contests SET status = 'ended', end_time = NOW() WHERE status = 'active'");
                    $stmt->execute();
                    $message = "Contest ended successfully!";
                    $message_type = 'success';
                    break;
                    
                case 'reset_contest':
                    // Reset contest clicks
                    $stmt = $conn->prepare("UPDATE contest_participants SET clicks = 0");
                    $stmt->execute();
                    $message = "Contest clicks reset successfully!";
                    $message_type = 'success';
                    break;
                    
                case 'distribute_prizes':
                    // Distribute prizes to top 3 winners
                    $stmt = $conn->prepare("
                        SELECT cp.user_id, u.username, cp.clicks 
                        FROM contest_participants cp 
                        JOIN users u ON cp.user_id = u.id 
                        WHERE cp.clicks > 0 
                        ORDER BY cp.clicks DESC 
                        LIMIT 3
                    ");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $winners = $result->fetch_all(MYSQLI_ASSOC);
                    
                    if (count($winners) > 0) {
                        $prizes = [500, 300, 200]; // 1st, 2nd, 3rd place
                        
                        for ($i = 0; $i < min(count($winners), 3); $i++) {
                            $winner = $winners[$i];
                            $prize = $prizes[$i];
                            
                            // Add prize to user balance
                            $stmt = $conn->prepare("UPDATE balances SET balance = balance + ? WHERE user_id = ?");
                            $stmt->bind_param("ii", $prize, $winner['user_id']);
                            $stmt->execute();
                            
                            // Record prize distribution
                            $stmt = $conn->prepare("INSERT INTO contest_winners (user_id, contest_id, position, prize_amount, clicks) VALUES (?, 1, ?, ?, ?)");
                            $position = $i + 1;
                            $stmt->bind_param("iiii", $winner['user_id'], $position, $prize, $winner['clicks']);
                            $stmt->execute();
                        }
                        
                        $message = "Prizes distributed to " . count($winners) . " winners!";
                        $message_type = 'success';
                    } else {
                        $message = "No participants found for prize distribution.";
                        $message_type = 'error';
                    }
                    break;
            }
            
            $conn->close();
            
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Get system statistics
try {
    $conn = getDBConnection();
    
    // Get total users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
    $stmt->execute();
    $total_users = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get total blocks
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blocks");
    $stmt->execute();
    $total_blocks = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get total transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions");
    $stmt->execute();
    $total_transactions = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get current contest status
    $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $current_contest = $stmt->get_result()->fetch_assoc();
    
    // Get top contest participants
    $stmt = $conn->prepare("
        SELECT cp.user_id, u.username, cp.clicks 
        FROM contest_participants cp 
        JOIN users u ON cp.user_id = u.id 
        ORDER BY cp.clicks DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $top_participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $conn->close();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ESP32 Blockchain - Admin Panel</title>
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

        .admin-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-badge {
            background: rgba(255, 0, 136, 0.1);
            border: 1px solid #ff0088;
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
        }

        .logout-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
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

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            text-shadow: 0 0 10px #ff0088;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ff0088;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #ff0088;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
            border-color: #ff6666;
            box-shadow: 0 0 20px rgba(255, 102, 102, 0.5);
        }

        .btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            margin: 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 136, 0.4);
        }

        .btn-danger {
            background: linear-gradient(45deg, #ff0000, #ff6666);
        }

        .btn-success {
            background: linear-gradient(45deg, #00ff88, #00ffff);
            color: #000;
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

        .contest-status {
            background: rgba(255, 0, 136, 0.1);
            border: 1px solid #ff0088;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .contest-status.active {
            border-color: #00ff88;
            background: rgba(0, 255, 136, 0.1);
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
            
            .admin-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .card {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
                margin: 5px 0;
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
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ADMIN PANEL</div>
        <div class="admin-info">
            <div class="admin-badge">
                <div>🔐 Admin: <?php echo htmlspecialchars($admin_username); ?></div>
            </div>
            <a href="admin_logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <!-- System Statistics -->
            <div class="card">
                <div class="card-title">System Statistics</div>
                <div class="stats-grid">
                    <div class="stat">
                        <div class="stat-value"><?php echo number_format($total_users); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo number_format($total_blocks); ?></div>
                        <div class="stat-label">Blocks Mined</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo number_format($total_transactions); ?></div>
                        <div class="stat-label">Transactions</div>
                    </div>
                </div>
            </div>

            <!-- Contest Management -->
            <div class="card">
                <div class="card-title">Contest Management</div>
                
                <?php if ($current_contest): ?>
                    <div class="contest-status active">
                        <h3>🎉 Contest Active!</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($current_contest['name']); ?></p>
                        <p><strong>Prize Pool:</strong> <?php echo number_format($current_contest['prize_pool']); ?> ESP32 tokens</p>
                        <p><strong>Ends:</strong> <?php echo date('M j, Y g:i A', strtotime($current_contest['end_time'])); ?></p>
                    </div>
                <?php else: ?>
                    <div class="contest-status">
                        <h3>⏸️ No Active Contest</h3>
                        <p>Start a new contest to engage users!</p>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="action" value="start_contest">
                    <div class="form-group">
                        <label for="contest_name">Contest Name:</label>
                        <input type="text" id="contest_name" name="contest_name" value="Click Contest" required>
                    </div>
                    <div class="form-group">
                        <label for="prize_pool">Prize Pool (ESP32 tokens):</label>
                        <input type="number" id="prize_pool" name="prize_pool" value="1000" min="100" required>
                    </div>
                    <button type="submit" class="btn btn-success">Start Contest</button>
                </form>

                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="end_contest">
                    <button type="submit" class="btn btn-danger">End Contest</button>
                </form>

                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="reset_contest">
                    <button type="submit" class="btn">Reset Clicks</button>
                </form>

                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="distribute_prizes">
                    <button type="submit" class="btn btn-success">Distribute Prizes</button>
                </form>
            </div>
        </div>

        <!-- Contest Leaderboard -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-title">Contest Leaderboard</div>
            <div class="leaderboard">
                <?php if (empty($top_participants)): ?>
                    <p style="text-align: center; color: #ff6666;">No participants yet. Start a contest to see the leaderboard!</p>
                <?php else: ?>
                    <?php foreach ($top_participants as $index => $participant): ?>
                        <div class="leaderboard-item">
                            <div class="rank">#<?php echo $index + 1; ?></div>
                            <div class="username"><?php echo htmlspecialchars($participant['username']); ?></div>
                            <div class="clicks"><?php echo number_format($participant['clicks']); ?> clicks</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-title">Quick Actions</div>
            <div style="text-align: center;">
                <a href="contest.php" class="btn btn-success">View Contest Page</a>
                <a href="leaderboard_display.php" class="btn">View Leaderboard Display</a>
                <a href="index.php" class="btn">Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html> 