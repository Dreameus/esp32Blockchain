<?php
require_once 'config.php';

echo "<h2>Setting up Newsletter Subscribers Table</h2>";

// Create newsletter_subscribers table
$sql = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: #00ff88;'>✓ Newsletter subscribers table created successfully!</p>";
} else {
    echo "<p style='color: #ff6666;'>✗ Error creating newsletter subscribers table: " . $conn->error . "</p>";
}

echo "<h3>Database Setup Complete!</h3>";
echo "<p><a href='index.php' style='color: #00ffff;'>← Back to Home</a></p>";
?> 