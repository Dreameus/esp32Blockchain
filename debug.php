<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>ESP32 Blockchain Debug</h2>";

// Test 1: Check if config.php exists and loads
echo "<h3>1. Config File Test</h3>";
if (file_exists('config.php')) {
    echo "<p style='color: #00ff88;'>✓ config.php exists</p>";
    try {
        require_once 'config.php';
        echo "<p style='color: #00ff88;'>✓ config.php loaded successfully</p>";
        echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
        echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
        echo "<p><strong>User:</strong> " . DB_USER . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: #ff6666;'>✗ Error loading config.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: #ff6666;'>✗ config.php not found</p>";
}

// Test 2: Database connection
echo "<h3>2. Database Connection Test</h3>";
try {
    $conn = getDBConnection();
    echo "<p style='color: #00ff88;'>✓ Database connection successful</p>";
    
    // Test 3: Check if tables exist
    echo "<h3>3. Database Tables Test</h3>";
    $tables = ['users', 'balances', 'blocks', 'transactions', 'mining_leaderboard'];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "<p style='color: #00ff88;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: #ff6666;'>✗ Table '$table' missing</p>";
        }
    }
    
    // Test 4: Check users table structure
    echo "<h3>4. Users Table Structure</h3>";
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "<table border='1' style='border-color: #00ff88; color: #00ff88;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 5: Check if admin user exists
    echo "<h3>5. Admin User Test</h3>";
    $stmt = $conn->prepare("SELECT id, username, wallet_address FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        echo "<p style='color: #00ff88;'>✓ Admin user exists (ID: " . $admin['id'] . ")</p>";
        echo "<p><strong>Wallet:</strong> " . $admin['wallet_address'] . "</p>";
        
        // Check admin balance
        $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ?");
        $stmt->bind_param('i', $admin['id']);
        $stmt->execute();
        $balance_result = $stmt->get_result();
        $balance = $balance_result->fetch_assoc()['balance'] ?? 0;
        echo "<p><strong>Admin Balance:</strong> " . number_format($balance) . " ESP32 tokens</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Admin user not found</p>";
    }
    
    // Test 6: Check total users
    echo "<h3>6. User Statistics</h3>";
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $user_count = $result->fetch_assoc()['count'];
    echo "<p><strong>Total Users:</strong> $user_count</p>";
    
    $result = $conn->query("SELECT COUNT(*) as count FROM blocks");
    $block_count = $result->fetch_assoc()['count'];
    echo "<p><strong>Total Blocks:</strong> $block_count</p>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: #ff6666;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 7: PHP Functions
echo "<h3>7. PHP Functions Test</h3>";
if (function_exists('password_hash')) {
    echo "<p style='color: #00ff88;'>✓ password_hash() function available</p>";
} else {
    echo "<p style='color: #ff6666;'>✗ password_hash() function not available</p>";
}

if (function_exists('hash')) {
    echo "<p style='color: #00ff88;'>✓ hash() function available</p>";
} else {
    echo "<p style='color: #ff6666;'>✗ hash() function not available</p>";
}

// Test 8: Session Test
echo "<h3>8. Session Test</h3>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: #00ff88;'>✓ Sessions working</p>";
} else {
    echo "<p style='color: #ff6666;'>✗ Sessions not working</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<p><a href='create_tables.php' style='color: #00ffff;'>Create Database Tables</a></p>";
echo "<p><a href='setup.php' style='color: #00ffff;'>Run Full Setup</a></p>";
echo "<p><a href='register.php' style='color: #00ffff;'>Test Registration</a></p>";
echo "<p><a href='index.php' style='color: #00ffff;'>Back to Home</a></p>";
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

table {
    margin: 10px 0;
    border-collapse: collapse;
}

th, td {
    padding: 8px;
    text-align: left;
}
</style> 