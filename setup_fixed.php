<?php
require_once 'config.php';

echo "<h2>ESP32 Blockchain Setup</h2>";

try {
    $conn = getDBConnection();
    
    // Create admin user if it doesn't exist
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Create admin user with direct SQL to avoid bind_param issues
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_wallet = generateWalletAddress();
        $total_supply = TOTAL_SUPPLY;
        
        // Insert admin user
        $sql = "INSERT INTO users (username, password_hash, wallet_address, is_admin) VALUES ('admin', '$admin_password', '$admin_wallet', 1)";
        $conn->query($sql);
        
        $admin_id = $conn->insert_id;
        
        // Give admin initial token supply
        $sql = "INSERT INTO balances (user_id, balance) VALUES ($admin_id, $total_supply)";
        $conn->query($sql);
        
        echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
        echo "<p><strong>Admin Username:</strong> admin</p>";
        echo "<p><strong>Admin Password:</strong> admin123</p>";
        echo "<p><strong>Admin Wallet:</strong> $admin_wallet</p>";
        echo "<p><strong>Initial Balance:</strong> " . number_format($total_supply) . " ESP32 tokens</p>";
    } else {
        echo "<p style='color: blue;'>✓ Admin user already exists</p>";
    }
    
    // Check if we have any blocks
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blocks");
    $stmt->execute();
    $result = $stmt->get_result();
    $block_count = $result->fetch_assoc()['count'];
    
    if ($block_count === 0) {
        // Create genesis block
        $genesis_hash = str_repeat('0', 64);
        $genesis_timestamp = time();
        $genesis_previous_hash = str_repeat('0', 64);
        $genesis_miner_id = 1; // admin
        $genesis_nonce = 0;
        $genesis_reward = 0;
        
        // Insert genesis block with direct SQL
        $sql = "INSERT INTO blocks (hash, previous_hash, miner_id, timestamp, nonce, reward, status) 
                VALUES ('$genesis_hash', '$genesis_previous_hash', $genesis_miner_id, $genesis_timestamp, $genesis_nonce, $genesis_reward, 'confirmed')";
        $conn->query($sql);
        
        echo "<p style='color: green;'>✓ Genesis block created</p>";
    } else {
        echo "<p style='color: blue;'>✓ Genesis block already exists</p>";
    }
    
    // Show system stats
    echo "<h3>System Statistics:</h3>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $user_count = $stmt->get_result()->fetch_assoc()['count'];
    echo "<p><strong>Total Users:</strong> $user_count</p>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blocks");
    $stmt->execute();
    $block_count = $stmt->get_result()->fetch_assoc()['count'];
    echo "<p><strong>Total Blocks:</strong> $block_count</p>";
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions");
    $stmt->execute();
    $tx_count = $stmt->get_result()->fetch_assoc()['count'];
    echo "<p><strong>Total Transactions:</strong> $tx_count</p>";
    
    $stmt = $conn->prepare("SELECT SUM(balance) as total FROM balances");
    $stmt->execute();
    $total_supply = $stmt->get_result()->fetch_assoc()['total'];
    echo "<p><strong>Total Token Supply:</strong> " . number_format($total_supply) . " ESP32 tokens</p>";
    
    echo "<h3>Configuration:</h3>";
    echo "<p><strong>Difficulty:</strong> " . DIFFICULTY . " leading zeros</p>";
    echo "<p><strong>Block Reward:</strong> " . REWARD_AMOUNT . " tokens</p>";
    echo "<p><strong>ESP32 Token:</strong> " . ESP32_TOKEN . "</p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Upload the ESP32 code to your microcontroller</li>";
    echo "<li>Update the WiFi credentials and server URL in the ESP32 code</li>";
    echo "<li>Start the ESP32 and monitor the serial output</li>";
    echo "<li>Visit <a href='index.php'>index.php</a> to access the web interface</li>";
    echo "<li>Login as admin or create a new user account</li>";
    echo "<li>Start mining and sending transactions!</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Setup failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Error details:</strong> " . $e->getTraceAsString() . "</p>";
} finally {
    if (isset($conn)) $conn->close();
}
?>

<style>
body {
    font-family: 'Courier New', monospace;
    background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
    color: #00ff88;
    padding: 20px;
    line-height: 1.6;
}

h2, h3 {
    color: #00ffff;
    text-shadow: 0 0 10px #00ffff;
}

p {
    margin: 10px 0;
}

a {
    color: #00ffff;
    text-decoration: none;
}

a:hover {
    text-shadow: 0 0 10px #00ffff;
}

ol {
    margin-left: 20px;
}

li {
    margin: 5px 0;
}
</style> 