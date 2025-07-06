<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

echo "<h1>💰 Transfer Debug</h1>";

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
    
    echo "<h2>📋 Database Tables Check</h2>";
    
    // Check if required tables exist
    $tables = ['users', 'balances', 'transactions', 'rewards'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<div style='color: #00ff88;'>✅ Table '$table' exists</div>";
        } else {
            echo "<div style='color: #ff6666;'>❌ Table '$table' does NOT exist</div>";
        }
    }
    
    echo "<br><h2>👥 Users & Balances</h2>";
    
    // Check users and their balances
    $stmt = $conn->prepare("
        SELECT u.id, u.username, COALESCE(b.balance, 0) as balance 
        FROM users u 
        LEFT JOIN balances b ON u.id = b.user_id 
        ORDER BY b.balance DESC
    ");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($users as $user) {
        echo "<div>" . $user['username'] . " (ID: " . $user['id'] . "): " . number_format($user['balance']) . " tokens</div>";
    }
    
    echo "<br><h2>💸 Recent Transactions</h2>";
    
    // Check recent transactions
    $stmt = $conn->prepare("
        SELECT t.*, 
               s.username as sender_name, 
               r.username as receiver_name 
        FROM transactions t 
        JOIN users s ON t.sender_id = s.id 
        JOIN users r ON t.receiver_id = r.id 
        ORDER BY t.timestamp DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($transactions)) {
        echo "<div style='color: #ffaa00;'>No transactions found</div>";
    } else {
        foreach ($transactions as $tx) {
            echo "<div style='border: 1px solid #ff0088; padding: 10px; margin: 10px 0;'>";
            echo "<strong>ID:</strong> " . $tx['id'] . "<br>";
            echo "<strong>From:</strong> " . $tx['sender_name'] . "<br>";
            echo "<strong>To:</strong> " . $tx['receiver_name'] . "<br>";
            echo "<strong>Amount:</strong> " . number_format($tx['amount']) . " tokens<br>";
            echo "<strong>Status:</strong> " . $tx['status'] . "<br>";
            echo "<strong>Time:</strong> " . $tx['timestamp'] . "<br>";
            echo "</div>";
        }
    }
    
    echo "<br><h2>🎁 Recent Rewards</h2>";
    
    // Check recent rewards
    $stmt = $conn->prepare("
        SELECT r.*, 
               s.username as sender_name, 
               rc.username as receiver_name,
               t.timestamp 
        FROM rewards r 
        JOIN users s ON r.sender_id = s.id 
        JOIN users rc ON r.receiver_id = rc.id 
        JOIN transactions t ON r.transaction_id = t.id 
        ORDER BY t.timestamp DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $rewards = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($rewards)) {
        echo "<div style='color: #ffaa00;'>No rewards found</div>";
    } else {
        foreach ($rewards as $reward) {
            echo "<div style='border: 1px solid #00ff88; padding: 10px; margin: 10px 0;'>";
            echo "<strong>ID:</strong> " . $reward['id'] . "<br>";
            echo "<strong>From:</strong> " . $reward['sender_name'] . "<br>";
            echo "<strong>To:</strong> " . $reward['receiver_name'] . "<br>";
            echo "<strong>Amount:</strong> " . number_format($reward['amount']) . " tokens<br>";
            echo "<strong>Reason:</strong> " . $reward['reason'] . "<br>";
            echo "<strong>Time:</strong> " . $reward['timestamp'] . "<br>";
            echo "</div>";
        }
    }
    
    echo "<br><h2>🧪 Test Transfer</h2>";
    
    // Get other users for testing
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id != ? LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $other_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($other_users)) {
        echo "<div style='color: #ffaa00;'>No other users found for testing</div>";
    } else {
        echo "<form method='POST' style='margin: 20px 0;'>";
        echo "<select name='receiver_id' required style='padding: 10px; margin: 10px; background: #000; color: #00ff88; border: 1px solid #00ff88;'>";
        echo "<option value=''>Select user to send tokens to...</option>";
        foreach ($other_users as $user) {
            echo "<option value='" . $user['id'] . "'>" . $user['username'] . "</option>";
        }
        echo "</select><br>";
        echo "<input type='number' name='amount' placeholder='Amount' min='1' max='1000' required style='padding: 10px; margin: 10px; background: #000; color: #00ff88; border: 1px solid #00ff88;'><br>";
        echo "<textarea name='reason' placeholder='Reason for transfer' required style='padding: 10px; margin: 10px; background: #000; color: #00ff88; border: 1px solid #00ff88; width: 300px; height: 100px;'></textarea><br>";
        echo "<button type='submit' name='test_transfer' style='background: #ff0088; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Transfer</button>";
        echo "</form>";
    }
    
    // Handle test transfer
    if (isset($_POST['test_transfer'])) {
        $receiver_id = (int)$_POST['receiver_id'];
        $amount = (int)$_POST['amount'];
        $reason = trim($_POST['reason']);
        
        echo "<h3>🔄 Processing Test Transfer...</h3>";
        
        try {
            // Check sender balance
            $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $sender_balance = $stmt->get_result()->fetch_assoc();
            
            if (!$sender_balance || $sender_balance['balance'] < $amount) {
                echo "<div style='color: #ff6666;'>❌ Insufficient balance. You have: " . number_format($sender_balance['balance'] ?? 0) . " tokens</div>";
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                echo "<div style='color: #00ff88;'>✅ Starting transfer of $amount tokens...</div>";
                
                // Deduct from sender
                $stmt = $conn->prepare("UPDATE balances SET balance = balance - ? WHERE user_id = ?");
                $stmt->bind_param("ii", $amount, $user_id);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #00ff88;'>✅ Deducted $amount tokens from sender</div>";
                } else {
                    throw new Exception("Failed to deduct from sender");
                }
                
                // Add to receiver
                $stmt = $conn->prepare("UPDATE balances SET balance = balance + ? WHERE user_id = ?");
                $stmt->bind_param("ii", $amount, $receiver_id);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #00ff88;'>✅ Added $amount tokens to receiver</div>";
                } else {
                    throw new Exception("Failed to add to receiver");
                }
                
                // Record the transaction
                $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, timestamp, status) VALUES (?, ?, ?, NOW(), 'confirmed')");
                $stmt->bind_param("iii", $user_id, $receiver_id, $amount);
                $result = $stmt->execute();
                
                if ($result) {
                    $transaction_id = $conn->insert_id;
                    echo "<div style='color: #00ff88;'>✅ Transaction recorded (ID: $transaction_id)</div>";
                } else {
                    throw new Exception("Failed to record transaction");
                }
                
                // Record reward details
                $stmt = $conn->prepare("INSERT INTO rewards (transaction_id, reason, sender_id, receiver_id, amount) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("isiii", $transaction_id, $reason, $user_id, $receiver_id, $amount);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #00ff88;'>✅ Reward details recorded</div>";
                } else {
                    throw new Exception("Failed to record reward details");
                }
                
                $conn->commit();
                echo "<div style='color: #00ff88;'>✅ Transfer completed successfully!</div>";
                
                echo "<script>setTimeout(() => location.reload(), 3000);</script>";
                
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div style='color: #ff6666;'>❌ Transfer failed: " . $e->getMessage() . "</div>";
        }
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
    <title>Transfer Debug</title>
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
        <p><a href="rewards.php">💰 Rewards Page</a></p>
        <p><a href="dashboard.php">🏠 Dashboard</a></p>
        <p><a href="check_database.php">🔍 Database Check</a></p>
    </div>
</body>
</html>