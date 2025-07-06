<?php
require_once 'config.php';

echo "<h2>Creating Email Subscriptions Table</h2>";

try {
    $conn = getDBConnection();
    
    // Create email_subscriptions table
    $sql = "CREATE TABLE IF NOT EXISTS email_subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(100),
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'unsubscribed', 'bounced') DEFAULT 'active',
        last_email_sent TIMESTAMP NULL,
        unsubscribe_token VARCHAR(64) UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: #00ff88;'>✓ Email subscriptions table created successfully!</p>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Error creating email subscriptions table: " . $conn->error . "</p>";
    }
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_email_subscriptions_email ON email_subscriptions(email)",
        "CREATE INDEX IF NOT EXISTS idx_email_subscriptions_status ON email_subscriptions(status)",
        "CREATE INDEX IF NOT EXISTS idx_email_subscriptions_subscribed_at ON email_subscriptions(subscribed_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        if ($conn->query($index_sql) === TRUE) {
            echo "<p style='color: #00ff88;'>✓ Index created successfully!</p>";
        } else {
            echo "<p style='color: #ff6666;'>✗ Error creating index: " . $conn->error . "</p>";
        }
    }
    
    // Check if table was created
    $result = $conn->query("SHOW TABLES LIKE 'email_subscriptions'");
    if ($result->num_rows > 0) {
        echo "<p style='color: #00ff88;'>✓ Email subscriptions table exists and is ready!</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $result = $conn->query("DESCRIBE email_subscriptions");
        echo "<table border='1' style='border-collapse: collapse; width: 100%; background: #2a2a2a; color: #fff;'>";
        echo "<tr style='background: #00ff88; color: #000;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: #ff6666;'>✗ Email subscriptions table was not created!</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<p>Now you can:</p>";
    echo "<ul>";
    echo "<li><a href='subscribe.php' style='color: #00ffff;'>Subscribe to emails</a></li>";
    echo "<li><a href='send_newsletter.php' style='color: #00ffff;'>Send newsletter</a></li>";
    echo "<li><a href='manage_subscriptions.php' style='color: #00ffff;'>Manage subscriptions</a></li>";
    echo "</ul>";
    
    echo "<p><a href='index.php' style='color: #00ffff;'>← Back to Home</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: #ff6666;'>✗ Database setup failed: " . $e->getMessage() . "</p>";
} finally {
    if (isset($conn)) $conn->close();
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    padding: 20px;
    background: #1a1a1a;
    color: #ffffff;
}

h2 {
    color: #00ff88;
    border-bottom: 2px solid #00ff88;
    padding-bottom: 10px;
}

h3 {
    color: #00ffff;
    margin-top: 20px;
}

table {
    margin: 10px 0;
}

th {
    padding: 8px;
}

td {
    padding: 8px;
    border: 1px solid #444;
}

a {
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style> 