<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

echo "<h1>🧪 Click Test</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div style='color: #ff6666;'>❌ User not logged in. Please <a href='login.php'>login</a> first.</div>";
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

echo "<div style='color: #00ff88;'>✅ User logged in: $username (ID: $user_id)</div><br>";

// Handle click action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'click') {
    echo "<h2>🔄 Processing Click...</h2>";
    
    try {
        $conn = getDBConnection();
        echo "✅ Database connected<br>";
        
        // Check if there's an active contest
        $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $contest = $stmt->get_result()->fetch_assoc();
        
        if ($contest) {
            echo "✅ Active contest found: " . $contest['name'] . "<br>";
            
            // Check if user already has a record
            $stmt = $conn->prepare("SELECT * FROM contest_participants WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $participant = $stmt->get_result()->fetch_assoc();
            
            if ($participant) {
                echo "✅ User participation record found. Current clicks: " . $participant['clicks'] . "<br>";
                
                // Update existing record
                $stmt = $conn->prepare("UPDATE contest_participants SET clicks = clicks + 1 WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "✅ Click updated successfully! New total: " . ($participant['clicks'] + 1) . "<br>";
                } else {
                    echo "❌ Error updating clicks: " . $stmt->error . "<br>";
                }
            } else {
                echo "✅ No participation record found. Creating new record...<br>";
                
                // Create new record
                $stmt = $conn->prepare("INSERT INTO contest_participants (user_id, clicks) VALUES (?, 1)");
                $stmt->bind_param("i", $user_id);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "✅ First click recorded successfully!<br>";
                } else {
                    echo "❌ Error creating participation record: " . $stmt->error . "<br>";
                }
            }
        } else {
            echo "❌ No active contest found<br>";
            echo "Please create an active contest first.<br>";
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "<br>";
    }
    
    echo "<br><a href='test_click.php'>🔄 Test Again</a> | <a href='debug_contest.php'>🔍 Debug Report</a><br>";
    exit;
}

// Show current status
try {
    $conn = getDBConnection();
    
    // Check for active contest
    $stmt = $conn->prepare("SELECT * FROM contests WHERE status = 'active' ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $contest = $stmt->get_result()->fetch_assoc();
    
    if ($contest) {
        echo "<div style='color: #00ff88;'>✅ Active contest: " . $contest['name'] . "</div>";
    } else {
        echo "<div style='color: #ff6666;'>❌ No active contest found</div>";
        echo "<div><a href='create_test_contest.php'>Create Test Contest</a></div>";
    }
    
    // Check user participation
    $stmt = $conn->prepare("SELECT * FROM contest_participants WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $participant = $stmt->get_result()->fetch_assoc();
    
    if ($participant) {
        echo "<div style='color: #00ff88;'>✅ Your clicks: " . $participant['clicks'] . "</div>";
    } else {
        echo "<div style='color: #ffaa00;'>⚠️ No participation record yet</div>";
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
    <title>Click Test</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            padding: 20px;
            line-height: 1.6;
        }
        
        .click-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            padding: 20px 40px;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-size: 1.2em;
            font-weight: bold;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        
        .click-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 136, 0.4);
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
    <h2>🎯 Test Click Button</h2>
    
    <form method="POST">
        <input type="hidden" name="action" value="click">
        <button type="submit" class="click-btn">CLICK ME!</button>
    </form>
    
    <div style="margin-top: 30px;">
        <h3>🔧 Quick Links</h3>
        <p><a href="debug_contest.php">🔍 Full Debug Report</a></p>
        <p><a href="create_test_contest.php">🎯 Create Test Contest</a></p>
        <p><a href="contest.php">🎮 Contest Page</a></p>
        <p><a href="dashboard.php">🏠 Dashboard</a></p>
    </div>
</body>
</html> 