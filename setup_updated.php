<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>ESP32 Blockchain Setup</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; background: #1a1a1a; color: #00ff88; padding: 20px; }";
echo "h1, h2 { color: #00ffff; }";
echo ".success { color: #00ff88; background: rgba(0,255,0,0.1); padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".error { color: #ff6666; background: rgba(255,0,0,0.1); padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".info { color: #00ffff; background: rgba(0,255,255,0.1); padding: 10px; border-radius: 5px; margin: 10px 0; }";
echo ".step { background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #00ff88; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🚀 ESP32 Blockchain Setup</h1>";

// Step 1: Check if config.php exists
echo "<div class='step'>";
echo "<h2>Step 1: Checking Configuration</h2>";

if (!file_exists('config.php')) {
    echo "<div class='error'>❌ config.php file not found!</div>";
    echo "<p>Please make sure config.php exists in the same directory.</p>";
    exit;
}

echo "<div class='success'>✅ config.php found</div>";

// Step 2: Load configuration
try {
    require_once 'config.php';
    echo "<div class='success'>✅ Configuration loaded successfully</div>";
    
    echo "<div class='info'>";
    echo "<strong>Database Settings:</strong><br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "User: " . DB_USER . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "Difficulty: " . DIFFICULTY . "<br>";
    echo "Reward: " . REWARD_AMOUNT . " tokens<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error loading config: " . $e->getMessage() . "</div>";
    exit;
}

// Step 3: Test database connection
echo "<div class='step'>";
echo "<h2>Step 2: Testing Database Connection</h2>";

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        echo "<div class='error'>❌ Database connection failed: " . $conn->connect_error . "</div>";
        echo "<p>Please check your database credentials in config.php</p>";
        exit;
    }
    
    echo "<div class='success'>✅ Database connection successful!</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
    exit;
}

// Step 4: Check if tables exist
echo "<div class='step'>";
echo "<h2>Step 3: Checking Database Tables</h2>";

$required_tables = [
    'users' => 'Users table',
    'balances' => 'Balances table', 
    'blocks' => 'Blocks table',
    'pending_blocks' => 'Pending blocks table',
    'transactions' => 'Transactions table',
    'pending_transactions' => 'Pending transactions table',
    'mining_leaderboard' => 'Mining leaderboard table'
];

$missing_tables = [];

foreach ($required_tables as $table => $description) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ $description exists</div>";
    } else {
        echo "<div class='error'>❌ $description missing</div>";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<div class='error'>❌ Missing tables detected!</div>";
    echo "<p>Please run the database schema first. You can use database_schema.sql</p>";
    echo "<p>Missing tables: " . implode(', ', $missing_tables) . "</p>";
    exit;
}

// Step 5: Create admin user
echo "<div class='step'>";
echo "<h2>Step 4: Creating Admin User</h2>";

$stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    try {
        // Create admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_wallet = 'ESP32_' . bin2hex(random_bytes(16));
        
        $stmt = $conn->prepare("INSERT INTO users (username, password_hash, wallet_address, is_admin) VALUES (?, ?, ?, 1)");
        $stmt->bind_param('sss', 'admin', $admin_password, $admin_wallet);
        $stmt->execute();
        
        $admin_id = $conn->insert_id;
        
        // Give admin initial token supply
        $stmt = $conn->prepare("INSERT INTO balances (user_id, balance) VALUES (?, ?)");
        $total_supply_value = TOTAL_SUPPLY;
        $stmt->bind_param('ii', $admin_id, $total_supply_value);
        $stmt->execute();
        
        echo "<div class='success'>✅ Admin user created successfully!</div>";
        echo "<div class='info'>";
        echo "<strong>Admin Login Details:</strong><br>";
        echo "Username: <strong>admin</strong><br>";
        echo "Password: <strong>admin123</strong><br>";
        echo "Wallet: <strong>$admin_wallet</strong><br>";
        echo "Initial Balance: <strong>" . number_format(TOTAL_SUPPLY) . " ESP32 tokens</strong><br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error creating admin user: " . $e->getMessage() . "</div>";
        exit;
    }
} else {
    echo "<div class='info'>✅ Admin user already exists</div>";
}

// Step 6: Create genesis block
echo "<div class='step'>";
echo "<h2>Step 5: Creating Genesis Block</h2>";

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM blocks");
$stmt->execute();
$result = $stmt->get_result();
$block_count = $result->fetch_assoc()['count'];

if ($block_count === 0) {
    try {
        // Create genesis block
        $genesis_hash = str_repeat('0', 64);
        $timestamp = time();
        
        $stmt = $conn->prepare("INSERT INTO blocks (hash, previous_hash, miner_id, timestamp, nonce, reward, status) VALUES (?, ?, ?, ?, ?, ?, 'confirmed')");
        $stmt->bind_param('ssisii', $genesis_hash, $genesis_hash, 1, $timestamp, 0, 0);
        $stmt->execute();
        
        echo "<div class='success'>✅ Genesis block created successfully!</div>";
        echo "<div class='info'>";
        echo "Genesis Hash: <strong>$genesis_hash</strong><br>";
        echo "Timestamp: <strong>" . date('Y-m-d H:i:s', $timestamp) . "</strong><br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Error creating genesis block: " . $e->getMessage() . "</div>";
        exit;
    }
} else {
    echo "<div class='info'>✅ Genesis block already exists</div>";
}

// Step 7: Show system statistics
echo "<div class='step'>";
echo "<h2>Step 6: System Statistics</h2>";

try {
    // Count users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $user_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // Count blocks
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blocks");
    $stmt->execute();
    $block_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // Count transactions
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM transactions");
    $stmt->execute();
    $tx_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // Total token supply
    $stmt = $conn->prepare("SELECT SUM(balance) as total FROM balances");
    $stmt->execute();
    $total_supply = $stmt->get_result()->fetch_assoc()['total'];
    
    echo "<div class='info'>";
    echo "<strong>Current System Status:</strong><br>";
    echo "Total Users: <strong>$user_count</strong><br>";
    echo "Total Blocks: <strong>$block_count</strong><br>";
    echo "Total Transactions: <strong>$tx_count</strong><br>";
    echo "Total Token Supply: <strong>" . number_format($total_supply) . " ESP32 tokens</strong><br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error getting statistics: " . $e->getMessage() . "</div>";
}

// Step 8: Configuration summary
echo "<div class='step'>";
echo "<h2>Step 7: Configuration Summary</h2>";

echo "<div class='info'>";
echo "<strong>Blockchain Configuration:</strong><br>";
echo "Mining Difficulty: <strong>" . DIFFICULTY . " leading zeros</strong><br>";
echo "Block Reward: <strong>" . REWARD_AMOUNT . " ESP32 tokens</strong><br>";
echo "Total Supply: <strong>" . number_format(TOTAL_SUPPLY) . " ESP32 tokens</strong><br>";
echo "ESP32 Token: <strong>" . ESP32_TOKEN . "</strong><br>";
echo "</div>";

// Step 9: Next steps
echo "<div class='step'>";
echo "<h2>🎉 Setup Complete! Next Steps:</h2>";

echo "<div class='info'>";
echo "<ol>";
echo "<li><strong>Test the Web Interface:</strong> <a href='index.php' style='color: #00ffff;'>Visit index.php</a></li>";
echo "<li><strong>Login as Admin:</strong> Use admin/admin123</li>";
echo "<li><strong>Create New Users:</strong> Register additional users</li>";
echo "<li><strong>Start Mining:</strong> Try mining on the dashboard</li>";
echo "<li><strong>Send Rewards:</strong> Use the ESP32 rewards system</li>";
echo "<li><strong>Setup ESP32:</strong> Upload esp32_blockchain_validator.ino</li>";
echo "<li><strong>Adjust Difficulty:</strong> Visit adjust_difficulty.php if mining is too hard</li>";
echo "</ol>";
echo "</div>";

echo "<div class='success'>";
echo "<h3>🚀 Your ESP32 Blockchain is Ready!</h3>";
echo "<p>You can now start mining, sending transactions, and using the reward system.</p>";
echo "</div>";

$conn->close();

echo "</body>";
echo "</html>";
?> 