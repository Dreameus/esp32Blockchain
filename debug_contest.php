<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

echo "<h1>🔍 Contest System Debug Report</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div style='color: #ff6666;'>❌ User not logged in. Please <a href='login.php'>login</a> first.</div>";
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

echo "<div style='color: #00ff88;'>✅ User logged in: $username (ID: $user_id)</div><br>";

try {
    $conn = getDBConnection();
    echo "<div style='color: #00ff88;'>✅ Database connection successful</div><br>";
    
    // Check if contest tables exist
    echo "<h2>📋 Database Tables Check</h2>";
    
    $tables = ['contests', 'contest_participants', 'contest_winners'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<div style='color: #00ff88;'>✅ Table '$table' exists</div>";
        } else {
            echo "<div style='color: #ff6666;'>❌ Table '$table' does NOT exist</div>";
        }
    }
    
    echo "<br><h2>🎯 Contest Status</h2>";
    
    // Check for active contests
    $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $active_contest = $stmt->get_result()->fetch_assoc();
    
    if ($active_contest) {
        echo "<div style='color: #00ff88;'>✅ Active contest found:</div>";
        echo "<div style='margin-left: 20px;'>";
        echo "ID: " . $active_contest['id'] . "<br>";
        echo "Name: " . $active_contest['name'] . "<br>";
        echo "Start: " . $active_contest['start_time'] . "<br>";
        echo "End: " . $active_contest['end_time'] . "<br>";
        echo "Prize Pool: " . $active_contest['prize_pool'] . " ESP32 tokens<br>";
        echo "Status: " . $active_contest['status'] . "<br>";
        echo "</div>";
    } else {
        echo "<div style='color: #ff6666;'>❌ No active contest found</div>";
    }
    
    echo "<br><h2>👥 User Participation</h2>";
    
    // Check user's participation
    $stmt = $conn->prepare("SELECT * FROM contest_participants WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_participation = $stmt->get_result()->fetch_assoc();
    
    if ($user_participation) {
        echo "<div style='color: #00ff88;'>✅ User participation record found:</div>";
        echo "<div style='margin-left: 20px;'>";
        echo "Clicks: " . $user_participation['clicks'] . "<br>";
        echo "Created: " . $user_participation['created_at'] . "<br>";
        echo "Updated: " . $user_participation['updated_at'] . "<br>";
        echo "</div>";
    } else {
        echo "<div style='color: #ffaa00;'>⚠️ No participation record for this user</div>";
    }
    
    echo "<br><h2>📊 Overall Statistics</h2>";
    
    // Get total participants
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM contest_participants WHERE clicks > 0");
    $stmt->execute();
    $total_participants = $stmt->get_result()->fetch_assoc()['count'];
    echo "<div>Total participants: $total_participants</div>";
    
    // Get user's rank
    if ($user_participation) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) + 1 as rank 
            FROM contest_participants 
            WHERE clicks > (SELECT COALESCE(clicks, 0) FROM contest_participants WHERE user_id = ?)
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_rank = $stmt->get_result()->fetch_assoc()['rank'];
        echo "<div>Your rank: #$user_rank</div>";
    } else {
        echo "<div>Your rank: Not participating yet</div>";
    }
    
    // Get leaderboard
    $stmt = $conn->prepare("
        SELECT cp.user_id, u.username, cp.clicks 
        FROM contest_participants cp 
        JOIN users u ON cp.user_id = u.id 
        ORDER BY cp.clicks DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<br><h3>🏆 Top 5 Leaderboard</h3>";
    if (empty($leaderboard)) {
        echo "<div style='color: #ffaa00;'>No participants yet</div>";
    } else {
        foreach ($leaderboard as $index => $participant) {
            $rank = $index + 1;
            $is_current_user = ($participant['user_id'] == $user_id) ? " (YOU)" : "";
            echo "<div>$rank. " . $participant['username'] . " - " . $participant['clicks'] . " clicks$is_current_user</div>";
        }
    }
    
    echo "<br><h2>🧪 Test Click Functionality</h2>";
    
    // Test click functionality
    if ($active_contest) {
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<input type='hidden' name='test_click' value='1'>";
        echo "<button type='submit' style='background: #ff0088; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Click</button>";
        echo "</form>";
        
        if (isset($_POST['test_click'])) {
            echo "<div style='color: #00ff88;'>🔄 Processing test click...</div>";
            
            // Simulate click
            if ($user_participation) {
                $stmt = $conn->prepare("UPDATE contest_participants SET clicks = clicks + 1 WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                echo "<div style='color: #00ff88;'>✅ Click recorded! New total: " . ($user_participation['clicks'] + 1) . "</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO contest_participants (user_id, clicks) VALUES (?, 1)");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                echo "<div style='color: #00ff88;'>✅ First click recorded!</div>";
            }
            
            echo "<script>setTimeout(() => location.reload(), 2000);</script>";
        }
    } else {
        echo "<div style='color: #ff6666;'>❌ Cannot test clicks - no active contest</div>";
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
    <title>Contest Debug</title>
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
        
        .debug-section {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #ff0088;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="debug-section">
        <h2>🔧 Quick Actions</h2>
        <p><a href="setup_contest_tables.php">📋 Setup Contest Tables</a></p>
        <p><a href="create_test_contest.php">🎯 Create Test Contest</a></p>
        <p><a href="contest.php">🎮 Go to Contest Page</a></p>
        <p><a href="dashboard.php">🏠 Back to Dashboard</a></p>
    </div>
</body>
</html>