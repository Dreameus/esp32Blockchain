<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get mining leaderboard data
try {
    $conn = getDBConnection();
    
    // Get top miners by blocks mined
    $stmt = $conn->prepare("
        SELECT ml.user_id, u.username, ml.blocks_mined, ml.total_reward, b.balance
        FROM mining_leaderboard ml
        JOIN users u ON ml.user_id = u.id
        LEFT JOIN balances b ON ml.user_id = b.user_id
        WHERE ml.blocks_mined > 0
        ORDER BY ml.blocks_mined DESC, ml.total_reward DESC
        LIMIT 50
    ");
    $stmt->execute();
    $top_miners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get top miners by total rewards
    $stmt = $conn->prepare("
        SELECT ml.user_id, u.username, ml.blocks_mined, ml.total_reward, b.balance
        FROM mining_leaderboard ml
        JOIN users u ON ml.user_id = u.id
        LEFT JOIN balances b ON ml.user_id = b.user_id
        WHERE ml.total_reward > 0
        ORDER BY ml.total_reward DESC, ml.blocks_mined DESC
        LIMIT 50
    ");
    $stmt->execute();
    $top_earners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get current user's mining stats
    $stmt = $conn->prepare("
        SELECT ml.blocks_mined, ml.total_reward, b.balance
        FROM mining_leaderboard ml
        LEFT JOIN balances b ON ml.user_id = b.user_id
        WHERE ml.user_id = ?
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user_stats = $stmt->get_result()->fetch_assoc();
    
    if ($user_stats) {
        $user_blocks_mined = $user_stats['blocks_mined'] ?? 0;
        $user_total_reward = $user_stats['total_reward'] ?? 0;
        $user_balance = $user_stats['balance'] ?? 0;
    } else {
        // User has no mining stats yet, get balance from balances table
        $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $balance_result = $stmt->get_result()->fetch_assoc();
        $user_blocks_mined = 0;
        $user_total_reward = 0;
        $user_balance = $balance_result['balance'] ?? 0;
    }
    
    // Get system statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total_miners FROM mining_leaderboard WHERE blocks_mined > 0");
    $stmt->execute();
    $total_miners = $stmt->get_result()->fetch_assoc()['total_miners'];
    
    $stmt = $conn->prepare("SELECT SUM(blocks_mined) as total_blocks FROM mining_leaderboard");
    $stmt->execute();
    $total_blocks = $stmt->get_result()->fetch_assoc()['total_blocks'] ?? 0;
    
    $stmt = $conn->prepare("SELECT SUM(total_reward) as total_rewards FROM mining_leaderboard");
    $stmt->execute();
    $total_rewards = $stmt->get_result()->fetch_assoc()['total_rewards'] ?? 0;
    
    // Get user's rank
    if ($user_blocks_mined > 0) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) + 1 as `rank`
            FROM mining_leaderboard ml1
            WHERE ml1.blocks_mined > (
                SELECT ml2.blocks_mined 
                FROM mining_leaderboard ml2 
                WHERE ml2.user_id = ?
            )
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $user_rank = $stmt->get_result()->fetch_assoc()['rank'] ?? 'N/A';
    } else {
        $user_rank = 'N/A';
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
    $top_miners = [];
    $top_earners = [];
    $user_blocks_mined = 0;
    $user_total_reward = 0;
    $user_balance = 0;
    $total_miners = 0;
    $total_blocks = 0;
    $total_rewards = 0;
    $user_rank = 'N/A';
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Blockchain - Mining Leaderboard</title>
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
            border-bottom: 3px solid #00ff88;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.3);
        }

        .logo {
            font-size: 2em;
            font-weight: bold;
            color: #00ff88;
            text-shadow: 0 0 20px #00ff88;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .balance {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 10px 15px;
            border-radius: 10px;
            text-align: center;
        }

        .balance-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #00ffff;
        }

        .nav-link {
            color: #00ff88;
            text-decoration: none;
            padding: 8px 15px;
            border: 1px solid #00ff88;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: #00ff88;
            color: #000;
        }

        .logout-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 0, 136, 0.4);
        }

        .main-content {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 30px;
            text-shadow: 0 0 30px #00ff88;
            animation: titleGlow 2s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { text-shadow: 0 0 30px #00ff88; }
            to { text-shadow: 0 0 50px #00ff88, 0 0 70px #00ff88; }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff88;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.4);
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #00ffff;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1em;
            color: #00ff88;
        }

        .leaderboard-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .leaderboard-section {
            background: rgba(0, 0, 0, 0.8);
            border: 3px solid #00ff88;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.2);
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
            background: linear-gradient(45deg, #00ff88, #00ffff, #00ff88, #00ffff);
            border-radius: 20px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .section-title {
            font-size: 2em;
            font-weight: bold;
            text-align: center;
            margin-bottom: 25px;
            color: #00ff88;
            text-shadow: 0 0 15px #00ff88;
        }

        .leaderboard-item {
            display: flex;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .leaderboard-item:hover {
            transform: translateX(10px);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .leaderboard-item.current-user {
            background: linear-gradient(45deg, rgba(0, 255, 136, 0.2), rgba(0, 255, 255, 0.1));
            border-color: #00ffff;
        }

        .rank {
            font-size: 1.8em;
            font-weight: bold;
            color: #00ff88;
            min-width: 60px;
            text-align: center;
        }

        .rank.gold { color: #ffd700; }
        .rank.silver { color: #c0c0c0; }
        .rank.bronze { color: #cd7f32; }

        .user-info-mini {
            flex: 1;
            margin-left: 15px;
        }

        .username {
            font-size: 1.2em;
            font-weight: bold;
            color: #00ff88;
        }

        .user-stats {
            font-size: 0.9em;
            color: #888;
            margin-top: 5px;
        }

        .mining-stats {
            text-align: right;
            margin-left: 15px;
        }

        .blocks-mined {
            font-size: 1.4em;
            font-weight: bold;
            color: #00ffff;
        }

        .total-reward {
            font-size: 1.1em;
            color: #ff8800;
            margin-top: 5px;
        }

        .balance-display {
            font-size: 0.9em;
            color: #888;
            margin-top: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
            font-style: italic;
        }

        .error-message {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff6666;
            color: #ff6666;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .leaderboard-container {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .page-title {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ESP32 Blockchain</div>
        <div class="user-info">
            <div class="balance">
                <div class="balance-amount"><?php echo number_format($user_balance); ?></div>
                <div>ESP32 Tokens</div>
            </div>
            <div>Welcome, <?php echo htmlspecialchars($username); ?></div>
            <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
            <a href="contest.php" class="nav-link">🎯 Contest</a>
            <a href="rewards.php" class="nav-link">💰 Rewards</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1 class="page-title">🔨 Mining Leaderboard</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- System Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $total_miners; ?></div>
                <div class="stat-label">Active Miners</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_blocks); ?></div>
                <div class="stat-label">Total Blocks Mined</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($total_rewards); ?></div>
                <div class="stat-label">Total Rewards Distributed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $user_rank; ?></div>
                <div class="stat-label">Your Rank</div>
            </div>
        </div>

        <!-- Your Mining Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $user_blocks_mined; ?></div>
                <div class="stat-label">Your Blocks Mined</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($user_total_reward); ?></div>
                <div class="stat-label">Your Total Rewards</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo DIFFICULTY; ?></div>
                <div class="stat-label">Current Difficulty</div>
                <div style="font-size: 0.8em; color: #00ff88; margin-top: 5px;">
                    <?php echo str_repeat('0', DIFFICULTY); ?>... required
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo REWARD_AMOUNT; ?></div>
                <div class="stat-label">Reward per Block</div>
            </div>
        </div>

        <!-- Leaderboards -->
        <div class="leaderboard-container">
            <!-- Top Miners by Blocks -->
            <div class="leaderboard-section">
                <h2 class="section-title">🏆 Top Miners (Blocks Mined)</h2>
                
                <?php if (empty($top_miners)): ?>
                    <div class="empty-state">
                        No miners have found blocks yet. Start mining to appear on the leaderboard!
                    </div>
                <?php else: ?>
                    <?php foreach ($top_miners as $index => $miner): ?>
                        <div class="leaderboard-item <?php echo ($miner['user_id'] == $user_id) ? 'current-user' : ''; ?>">
                            <div class="rank <?php echo ($index < 3) ? ['gold', 'silver', 'bronze'][$index] : ''; ?>">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="user-info-mini">
                                <div class="username"><?php echo htmlspecialchars($miner['username']); ?></div>
                                <div class="user-stats">
                                    User ID: <?php echo $miner['user_id']; ?>
                                </div>
                            </div>
                            <div class="mining-stats">
                                <div class="blocks-mined"><?php echo number_format($miner['blocks_mined']); ?> blocks</div>
                                <div class="total-reward"><?php echo number_format($miner['total_reward']); ?> tokens</div>
                                <div class="balance-display">Balance: <?php echo number_format($miner['balance'] ?? 0); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Top Earners by Rewards -->
            <div class="leaderboard-section">
                <h2 class="section-title">💰 Top Earners (Total Rewards)</h2>
                
                <?php if (empty($top_earners)): ?>
                    <div class="empty-state">
                        No rewards have been earned yet. Start mining to earn tokens!
                    </div>
                <?php else: ?>
                    <?php foreach ($top_earners as $index => $miner): ?>
                        <div class="leaderboard-item <?php echo ($miner['user_id'] == $user_id) ? 'current-user' : ''; ?>">
                            <div class="rank <?php echo ($index < 3) ? ['gold', 'silver', 'bronze'][$index] : ''; ?>">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="user-info-mini">
                                <div class="username"><?php echo htmlspecialchars($miner['username']); ?></div>
                                <div class="user-stats">
                                    User ID: <?php echo $miner['user_id']; ?>
                                </div>
                            </div>
                            <div class="mining-stats">
                                <div class="blocks-mined"><?php echo number_format($miner['total_reward']); ?> tokens</div>
                                <div class="total-reward"><?php echo number_format($miner['blocks_mined']); ?> blocks</div>
                                <div class="balance-display">Balance: <?php echo number_format($miner['balance'] ?? 0); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Call to Action -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="dashboard.php" class="nav-link" style="font-size: 1.2em; padding: 15px 30px;">
                🚀 Start Mining Now
            </a>
        </div>
    </div>

    <script>
        // Auto-refresh the page every 30 seconds to show updated leaderboard
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 