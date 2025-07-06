<?php
require_once 'config.php';

echo "<h2>Adding Rewards Table</h2>";

try {
    $conn = getDBConnection();
    
    // Check if rewards table already exists
    $result = $conn->query("SHOW TABLES LIKE 'rewards'");
    if ($result->num_rows > 0) {
        echo "<p style='color: blue;'>✓ Rewards table already exists</p>";
    } else {
        // Create rewards table
        $sql = "CREATE TABLE rewards (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id INT NOT NULL,
            reason VARCHAR(100) NOT NULL,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            amount BIGINT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES transactions(id),
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        )";
        
        if ($conn->query($sql)) {
            echo "<p style='color: green;'>✓ Rewards table created successfully</p>";
            
            // Add indexes for better performance
            $conn->query("CREATE INDEX idx_rewards_sender ON rewards(sender_id)");
            $conn->query("CREATE INDEX idx_rewards_receiver ON rewards(receiver_id)");
            echo "<p style='color: green;'>✓ Indexes created for rewards table</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to create rewards table: " . $conn->error . "</p>";
        }
    }
    
    // Show current table structure
    echo "<h3>Current Database Tables:</h3>";
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        echo "<p>• " . $row[0] . "</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Visit <a href='rewards.php'>rewards.php</a> to access the ESP32 token rewards system</li>";
    echo "<li>Users can now send ESP32 token rewards to each other with reasons</li>";
    echo "<li>View ESP32 token reward history and statistics</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
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