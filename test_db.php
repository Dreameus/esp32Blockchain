<?php
echo "<h2>Database Connection Test</h2>";

// Test database connection
try {
    $conn = new mysqli('localhost', 'cp93267_block', 'Razmikaren1@', 'cp93267_block');
    
    if ($conn->connect_error) {
        echo "<p style='color: red;'>✗ Database connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";
        
        // Test if tables exist
        $tables = ['users', 'balances', 'blocks', 'pending_blocks', 'transactions', 'pending_transactions', 'mining_leaderboard'];
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>✗ Table '$table' missing</p>";
            }
        }
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #f0f0f0;
}
</style> 