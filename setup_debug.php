<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>ESP32 Blockchain Setup - Debug Mode</h2>";
echo "<p>Starting setup process...</p>";

// Test if config.php exists
if (!file_exists('config.php')) {
    echo "<p style='color: red;'>✗ config.php file not found!</p>";
    exit;
}

echo "<p style='color: green;'>✓ config.php found</p>";

// Include config
try {
    require_once 'config.php';
    echo "<p style='color: green;'>✓ config.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error loading config.php: " . $e->getMessage() . "</p>";
    exit;
}

echo "<p>Database settings:</p>";
echo "<ul>";
echo "<li>Host: " . DB_HOST . "</li>";
echo "<li>User: " . DB_USER . "</li>";
echo "<li>Database: " . DB_NAME . "</li>";
echo "<li>Difficulty: " . DIFFICULTY . "</li>";
echo "<li>Reward: " . REWARD_AMOUNT . "</li>";
echo "</ul>";

try {
    echo "<p>Attempting database connection...</p>";
    $conn = getDBConnection();
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Create admin user if it doesn't exist
    echo "<p>Checking for admin user...</p>";
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "<p>Creating admin user...</p>";
        // Create admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_wallet = generateWalletAddress();
        
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, wallet_address, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param('sss', 'admin', $admin_password, $admin_wallet);
        $stmt->execute();
        
        $admin_id = $conn->insert_id;
        
        // Give admin initial token supply
        $stmt = $conn->prepare("INSERT INTO balances (user_id, balance) VALUES (?, ?)");
        $total_supply_value = TOTAL_SUPPLY;
        $stmt->bind_param('ii', $admin_id, $total_supply_value);
        $stmt->execute();
        
        echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
        echo "<p><strong>Admin Username:</strong> admin</p>";
        echo "<p><strong>Admin Password:</strong> admin123</p>";
        echo "<p><strong>Admin Wallet:</strong> $admin_wallet</p>";
        echo "<p><strong>Initial Balance:</strong> " . number_format(TOTAL_SUPPLY) . " ESP32 tokens</p>";
    } else {
        echo "<p style='color: blue;'>✓ Admin user already exists</p>";
    }
    
    // Check if we have any blocks
    echo "<p>Checking for genesis block...</p>";
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blocks");
    $stmt->execute();
    $result = $stmt->get_result();
    $block_count = $result->fetch_assoc()['count'];
    
    if ($block_count === 0) {
        echo "<p>Creating genesis block...</p>";
        // Create genesis block
        $genesis_hash = str_repeat('0', 64);
        $genesis_data = [
            'index' => 0,
            'timestamp' => time(),
            'previous_hash' => str_repeat('0', 64),
            'nonce' => 0,
            'hash' => $genesis_hash,
            'miner_id' => 1 // admin
        ];
        
        // Insert genesis block
        $stmt = $conn->prepare("INSERT INTO blocks (hash, previous_hash, miner_id, timestamp, nonce, reward, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
        $stmt->bind_param('ssisii', $genesis_hash, $genesis_data['previous_hash'], $genesis_data['miner_id'], $genesis_data['timestamp'], $genesis_data['nonce'], 0);
        $stmt->execute();
        
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
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
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

ul {
    margin-left: 20px;
}
</style> 