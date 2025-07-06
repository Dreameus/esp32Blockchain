<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

echo "<h1>🏆 Contest Rewards Debug</h1>";

try {
    $conn = getDBConnection();
    
    echo "<h2>📋 Contest Status</h2>";
    
    // Check all contests
    $stmt = $conn->prepare("SELECT * FROM contests ORDER BY id DESC");
    $stmt->execute();
    $contests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($contests as $contest) {
        echo "<div style='border: 1px solid #ff0088; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Contest ID:</strong> " . $contest['id'] . "<br>";
        echo "<strong>Name:</strong> " . $contest['name'] . "<br>";
        echo "<strong>Status:</strong> " . $contest['status'] . "<br>";
        echo "<strong>Start:</strong> " . $contest['start_time'] . "<br>";
        echo "<strong>End:</strong> " . $contest['end_time'] . "<br>";
        echo "<strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
        echo "<strong>Has Ended:</strong> " . (strtotime($contest['end_time']) <= time() ? 'YES' : 'NO') . "<br>";
        echo "</div>";
    }
    
    echo "<h2>👥 Participants</h2>";
    
    // Check participants
    $stmt = $conn->prepare("
        SELECT cp.user_id, u.username, cp.clicks 
        FROM contest_participants cp 
        JOIN users u ON cp.user_id = u.id 
        ORDER BY cp.clicks DESC
    ");
    $stmt->execute();
    $participants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($participants)) {
        echo "<div style='color: #ffaa00;'>No participants found</div>";
    } else {
        foreach ($participants as $index => $participant) {
            $rank = $index + 1;
            echo "<div>$rank. " . $participant['username'] . " - " . $participant['clicks'] . " clicks</div>";
        }
    }
    
    echo "<h2>🏅 Winners</h2>";
    
    // Check winners
    $stmt = $conn->prepare("SELECT * FROM contest_winners ORDER BY contest_id DESC, position ASC");
    $stmt->execute();
    $winners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($winners)) {
        echo "<div style='color: #ffaa00;'>No winners recorded yet</div>";
    } else {
        foreach ($winners as $winner) {
            echo "<div>Contest " . $winner['contest_id'] . " - Position " . $winner['position'] . ": " . $winner['prize_amount'] . " tokens</div>";
        }
    }
    
    echo "<h2>💰 User Balances</h2>";
    
    // Check user balances
    $stmt = $conn->prepare("
        SELECT u.username, b.balance 
        FROM users u 
        LEFT JOIN balances b ON u.id = b.user_id 
        ORDER BY b.balance DESC
    ");
    $stmt->execute();
    $balances = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($balances as $balance) {
        echo "<div>" . $balance['username'] . ": " . number_format($balance['balance']) . " ESP32 tokens</div>";
    }
    
    echo "<h2>🔧 Manual Contest End Test</h2>";
    
    // Find contests that should have ended
    $stmt = $conn->prepare("
        SELECT * FROM contests 
        WHERE status = 'active' 
        AND end_time <= NOW()
    ");
    $stmt->execute();
    $ended_contests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($ended_contests)) {
        echo "<div style='color: #ffaa00;'>No contests have ended yet</div>";
    } else {
        foreach ($ended_contests as $contest) {
            echo "<div style='color: #ff6666;'>Contest " . $contest['id'] . " should have ended!</div>";
            
            // Manual reward distribution
            echo "<form method='POST' style='margin: 10px 0;'>";
            echo "<input type='hidden' name='end_contest' value='" . $contest['id'] . "'>";
            echo "<button type='submit' style='background: #ff0088; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>End Contest & Distribute Rewards</button>";
            echo "</form>";
        }
    }
    
    // Handle manual contest ending
    if (isset($_POST['end_contest'])) {
        $contest_id = $_POST['end_contest'];
        echo "<h3>🔄 Processing Contest End...</h3>";
        
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
        
        if (empty($winners)) {
            echo "<div style='color: #ff6666;'>No participants found for this contest</div>";
        } else {
            // Define prize amounts
            $prizes = [500, 300, 200]; // 1st, 2nd, 3rd place
            
            echo "<div style='color: #00ff88;'>🏆 Winners:</div>";
            
            // Distribute prizes
            foreach ($winners as $index => $winner) {
                $prize_amount = $prizes[$index];
                $position = $index + 1;
                
                echo "<div>Position $position: " . $winner['username'] . " - $prize_amount tokens</div>";
                
                // Add to user's balance
                $stmt = $conn->prepare("
                    UPDATE balances 
                    SET balance = balance + ? 
                    WHERE user_id = ?
                ");
                $stmt->bind_param("ii", $prize_amount, $winner['user_id']);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #00ff88;'>✅ Added $prize_amount tokens to " . $winner['username'] . "</div>";
                } else {
                    echo "<div style='color: #ff6666;'>❌ Failed to add tokens to " . $winner['username'] . "</div>";
                }
                
                // Record winner
                $stmt = $conn->prepare("
                    INSERT INTO contest_winners (user_id, contest_id, position, prize_amount, clicks) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("iiiii", $winner['user_id'], $contest_id, $position, $prize_amount, $winner['clicks']);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #00ff88;'>✅ Recorded winner</div>";
                } else {
                    echo "<div style='color: #ff6666;'>❌ Failed to record winner</div>";
                }
            }
            
            // Mark contest as ended
            $stmt = $conn->prepare("UPDATE contests SET status = 'ended' WHERE id = ?");
            $stmt->bind_param("i", $contest_id);
            $result = $stmt->execute();
            
            if ($result) {
                echo "<div style='color: #00ff88;'>✅ Contest marked as ended</div>";
            } else {
                echo "<div style='color: #ff6666;'>❌ Failed to end contest</div>";
            }
        }
        
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<div style='color: #ff6666;'>❌ Database error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contest Rewards Debug</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            padding: 20px;
            line-height: 1.6;
        }
        
        h1, h2, h3 {
            color: #ff0088;
            border-bottom: 1px solid #ff0088;
            padding-bottom: 10px;
            margin-top: 30px;
        }
        
        a {
            color: #ff0088;
            text-decoration: none;
        }
        
        a:hover {
            color: #ff6666;
            text-shadow: 0 0 10px #ff6666;
        }
    </style>
</head>
<body>
    <div style="margin-top: 30px;">
        <h3>🔧 Quick Links</h3>
        <p><a href="contest.php">🎮 Contest Page</a></p>
        <p><a href="create_test_contest.php">🎯 Create Test Contest</a></p>
        <p><a href="dashboard.php">🏠 Dashboard</a></p>
    </div>
</body>
</html> 