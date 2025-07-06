<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';

echo "<h1>⏰ Create Short Test Contest</h1>";

try {
    $conn = getDBConnection();
    
    // First, let's check if contest tables exist
    $result = $conn->query("SHOW TABLES LIKE 'contests'");
    if ($result->num_rows === 0) {
        echo "❌ Contests table doesn't exist. Please run setup_contest_tables.php first.<br>";
        exit;
    }
    
    // Check if there's already an active contest
    $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active'");
    $stmt->execute();
    $existing_contest = $stmt->get_result()->fetch_assoc();
    
    if ($existing_contest) {
        echo "✅ Active contest already exists:<br>";
        echo "Name: " . $existing_contest['name'] . "<br>";
        echo "Ends: " . $existing_contest['end_time'] . "<br>";
        echo "Prize Pool: " . $existing_contest['prize_pool'] . " ESP32 tokens<br>";
        
        // Option to end the current contest early
        echo "<br><form method='POST'>";
        echo "<input type='hidden' name='end_current' value='1'>";
        echo "<button type='submit' style='background: #ff6666; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>End Current Contest Early</button>";
        echo "</form>";
        
    } else {
        // Create a new short contest
        $contest_name = "Quick Test Contest";
        $start_time = date('Y-m-d H:i:s');
        $end_time = date('Y-m-d H:i:s', strtotime('+2 minutes')); // Contest runs for only 2 minutes
        $prize_pool = 1000;
        
        $stmt = $conn->prepare("INSERT INTO contests (name, start_time, end_time, prize_pool, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssi", $contest_name, $start_time, $end_time, $prize_pool);
        
        if ($stmt->execute()) {
            echo "✅ New short test contest created successfully!<br>";
            echo "Name: " . $contest_name . "<br>";
            echo "Start Time: " . $start_time . "<br>";
            echo "End Time: " . $end_time . "<br>";
            echo "Prize Pool: " . $prize_pool . " ESP32 tokens<br>";
            echo "<br><div style='color: #ffaa00;'>⏰ This contest will end in 2 minutes!</div>";
        } else {
            echo "❌ Error creating contest: " . $stmt->error . "<br>";
        }
    }
    
    // Handle ending current contest early
    if (isset($_POST['end_current']) && $existing_contest) {
        echo "<h3>🔄 Ending Current Contest Early...</h3>";
        
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
            echo "<div style='color: #ffaa00;'>No participants found. Ending contest without rewards.</div>";
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
                $stmt->bind_param("iiiii", $winner['user_id'], $existing_contest['id'], $position, $prize_amount, $winner['clicks']);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #00ff88;'>✅ Recorded winner</div>";
                } else {
                    echo "<div style='color: #ff6666;'>❌ Failed to record winner</div>";
                }
            }
        }
        
        // Mark contest as ended
        $stmt = $conn->prepare("UPDATE contests SET status = 'ended' WHERE id = ?");
        $stmt->bind_param("i", $existing_contest['id']);
        $result = $stmt->execute();
        
        if ($result) {
            echo "<div style='color: #00ff88;'>✅ Contest marked as ended</div>";
        } else {
            echo "<div style='color: #ff6666;'>❌ Failed to end contest</div>";
        }
        
        // Clear all participant data to start fresh
        $stmt = $conn->prepare("DELETE FROM contest_participants");
        $result = $stmt->execute();
        
        if ($result) {
            echo "<div style='color: #00ff88;'>✅ All participant data cleared - ready for new contest!</div>";
        } else {
            echo "<div style='color: #ff6666;'>❌ Failed to clear participant data</div>";
        }
        
        echo "<script>setTimeout(() => location.reload(), 3000);</script>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Short Contest</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            padding: 20px;
            line-height: 1.6;
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
        <p><a href="debug_contest_rewards.php">🔍 Debug Rewards</a></p>
        <p><a href="dashboard.php">🏠 Dashboard</a></p>
    </div>
</body>
</html> 