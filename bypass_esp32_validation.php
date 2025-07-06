<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

echo "<h1>🔧 ESP32 Validation Bypass</h1>";

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
    
    echo "<h2>📋 Pending Transactions</h2>";
    
    // Check for pending transactions
    $stmt = $conn->prepare("
        SELECT pt.*, 
               s.username as sender_name, 
               r.username as receiver_name 
        FROM pending_transactions pt 
        JOIN users s ON pt.sender_id = s.id 
        JOIN users r ON pt.receiver_id = r.id 
        WHERE pt.status = 'pending' 
        ORDER BY pt.submitted_at ASC
    ");
    $stmt->execute();
    $pending_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($pending_transactions)) {
        echo "<div style='color: #ffaa00;'>No pending transactions found</div>";
    } else {
        echo "<div style='color: #00ff88;'>Found " . count($pending_transactions) . " pending transactions:</div><br>";
        
        foreach ($pending_transactions as $tx) {
            echo "<div style='border: 1px solid #ff0088; padding: 15px; margin: 10px 0;'>";
            echo "<strong>ID:</strong> " . $tx['id'] . "<br>";
            echo "<strong>From:</strong> " . $tx['sender_name'] . "<br>";
            echo "<strong>To:</strong> " . $tx['receiver_name'] . "<br>";
            echo "<strong>Amount:</strong> " . number_format($tx['amount']) . " tokens<br>";
            echo "<strong>Submitted:</strong> " . $tx['submitted_at'] . "<br>";
            
            // Manual confirmation button
            echo "<form method='POST' style='margin-top: 10px;'>";
            echo "<input type='hidden' name='confirm_transaction' value='" . $tx['id'] . "'>";
            echo "<button type='submit' style='background: #00ff88; color: black; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>✅ Confirm Transaction</button>";
            echo "</form>";
            
            echo "</div>";
        }
    }
    
    echo "<h2>📊 Recent Confirmed Transactions</h2>";
    
    // Show recent confirmed transactions
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
    $confirmed_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($confirmed_transactions)) {
        echo "<div style='color: #ffaa00;'>No confirmed transactions found</div>";
    } else {
        foreach ($confirmed_transactions as $tx) {
            echo "<div style='border: 1px solid #00ff88; padding: 10px; margin: 5px 0;'>";
            echo "<strong>ID:</strong> " . $tx['id'] . " | ";
            echo "<strong>From:</strong> " . $tx['sender_name'] . " | ";
            echo "<strong>To:</strong> " . $tx['receiver_name'] . " | ";
            echo "<strong>Amount:</strong> " . number_format($tx['amount']) . " tokens | ";
            echo "<strong>Status:</strong> " . $tx['status'] . " | ";
            echo "<strong>Time:</strong> " . $tx['timestamp'];
            echo "</div>";
        }
    }
    
    // Handle manual transaction confirmation
    if (isset($_POST['confirm_transaction'])) {
        $transaction_id = (int)$_POST['confirm_transaction'];
        
        echo "<h3>🔄 Processing Transaction Confirmation...</h3>";
        
        try {
            // Get transaction details
            $stmt = $conn->prepare("SELECT * FROM pending_transactions WHERE id = ?");
            $stmt->bind_param("i", $transaction_id);
            $stmt->execute();
            $transaction = $stmt->get_result()->fetch_assoc();
            
            if (!$transaction) {
                echo "<div style='color: #ff6666;'>❌ Transaction not found</div>";
            } else {
                // Start transaction
                $conn->begin_transaction();
                
                echo "<div style='color: #00ff88;'>✅ Processing transaction ID: $transaction_id</div>";
                
                $sender_id = $transaction['sender_id'];
                $receiver_id = $transaction['receiver_id'];
                $amount = $transaction['amount'];
                
                // Check sender balance
                $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ?");
                $stmt->bind_param("i", $sender_id);
                $stmt->execute();
                $sender_balance = $stmt->get_result()->fetch_assoc();
                
                if (!$sender_balance || $sender_balance['balance'] < $amount) {
                    throw new Exception("Insufficient balance for sender");
                }
                
                // Deduct from sender
                $stmt = $conn->prepare("UPDATE balances SET balance = balance - ? WHERE user_id = ?");
                $stmt->bind_param("ii", $amount, $sender_id);
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
                
                // Move to confirmed transactions
                $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, timestamp, status) VALUES (?, ?, ?, NOW(), 'confirmed')");
                $stmt->bind_param("iii", $sender_id, $receiver_id, $amount);
                $result = $stmt->execute();
                
                if ($result) {
                    $new_transaction_id = $conn->insert_id;
                    echo "<div style='color: #00ff88;'>✅ Transaction recorded (ID: $new_transaction_id)</div>";
                } else {
                    throw new Exception("Failed to record transaction");
                }
                
                // Update pending transaction status
                $stmt = $conn->prepare("UPDATE pending_transactions SET status = 'confirmed' WHERE id = ?");
                $stmt->bind_param("i", $transaction_id);
                $result = $stmt->execute();
                
                if ($result) {
                    echo "<div style='color: #00ff88;'>✅ Pending transaction marked as confirmed</div>";
                } else {
                    throw new Exception("Failed to update pending transaction");
                }
                
                $conn->commit();
                echo "<div style='color: #00ff88;'>✅ Transaction confirmed successfully!</div>";
                
                echo "<script>setTimeout(() => location.reload(), 3000);</script>";
                
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div style='color: #ff6666;'>❌ Transaction confirmation failed: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<h2>🧪 Test Direct Transfer</h2>";
    
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
        echo "<button type='submit' name='test_direct_transfer' style='background: #ff0088; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Test Direct Transfer (Bypass ESP32)</button>";
        echo "</form>";
    }
    
    // Handle direct transfer test
    if (isset($_POST['test_direct_transfer'])) {
        $receiver_id = (int)$_POST['receiver_id'];
        $amount = (int)$_POST['amount'];
        
        echo "<h3>🔄 Processing Direct Transfer...</h3>";
        
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
                
                echo "<div style='color: #00ff88;'>✅ Starting direct transfer of $amount tokens...</div>";
                
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
                
                // Record the transaction directly (bypass ESP32)
                $stmt = $conn->prepare("INSERT INTO transactions (sender_id, receiver_id, amount, timestamp, status) VALUES (?, ?, ?, NOW(), 'confirmed')");
                $stmt->bind_param("iii", $user_id, $receiver_id, $amount);
                $result = $stmt->execute();
                
                if ($result) {
                    $transaction_id = $conn->insert_id;
                    echo "<div style='color: #00ff88;'>✅ Transaction recorded directly (ID: $transaction_id)</div>";
                } else {
                    throw new Exception("Failed to record transaction");
                }
                
                $conn->commit();
                echo "<div style='color: #00ff88;'>✅ Direct transfer completed successfully!</div>";
                
                echo "<script>setTimeout(() => location.reload(), 3000);</script>";
                
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo "<div style='color: #ff6666;'>❌ Direct transfer failed: " . $e->getMessage() . "</div>";
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
    <title>ESP32 Validation Bypass</title>
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
        <p><a href="debug_transfers.php">🔍 Debug Transfers</a></p>
        <p><a href="dashboard.php">🏠 Dashboard</a></p>
    </div>
</body>
</html> 