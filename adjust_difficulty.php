<?php
require_once 'config.php';

echo "<h2>Mining Difficulty Adjustment Tool</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_difficulty = (int)$_POST['difficulty'];
    
    if ($new_difficulty >= 0 && $new_difficulty <= 8) {
        // Update config.php
        $config_content = file_get_contents('config.php');
        $config_content = preg_replace("/define\('DIFFICULTY', \d+\);/", "define('DIFFICULTY', $new_difficulty);", $config_content);
        file_put_contents('config.php', $config_content);
        
        echo "<p style='color: green;'>✓ Difficulty updated to $new_difficulty leading zeros</p>";
        echo "<p>Please restart your ESP32 for the changes to take effect.</p>";
    } else {
        echo "<p style='color: red;'>✗ Invalid difficulty value. Must be between 0 and 8.</p>";
    }
}

// Get current difficulty
$current_difficulty = DIFFICULTY;

echo "<form method='post'>";
echo "<p><strong>Current Difficulty:</strong> $current_difficulty leading zeros</p>";
echo "<p>Select new difficulty:</p>";
echo "<select name='difficulty'>";
for ($i = 0; $i <= 8; $i++) {
    $selected = ($i == $current_difficulty) ? 'selected' : '';
    echo "<option value='$i' $selected>$i leading zeros</option>";
}
echo "</select>";
echo "<br><br>";
echo "<input type='submit' value='Update Difficulty' style='background: #00ff88; color: #000; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
echo "</form>";

echo "<h3>Difficulty Guide:</h3>";
echo "<ul>";
echo "<li><strong>0 zeros:</strong> Very easy - for testing only</li>";
echo "<li><strong>1 zero:</strong> Easy - good for development</li>";
echo "<li><strong>2 zeros:</strong> Moderate - reasonable for testing</li>";
echo "<li><strong>3 zeros:</strong> Medium - balanced difficulty</li>";
echo "<li><strong>4 zeros:</strong> Hard - current setting</li>";
echo "<li><strong>5+ zeros:</strong> Very hard - production difficulty</li>";
echo "</ul>";

echo "<h3>Test Hash Examples:</h3>";
$test_data = "test_block_data_" . time();
$test_hash = hash('sha256', $test_data);
echo "<p><strong>Test hash:</strong> $test_hash</p>";

for ($i = 0; $i <= 4; $i++) {
    $meets = true;
    for ($j = 0; $j < $i; $j++) {
        if ($test_hash[$j] !== '0') {
            $meets = false;
            break;
        }
    }
    $status = $meets ? "✅ Meets" : "❌ Doesn't meet";
    echo "<p><strong>$i zeros:</strong> $status</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Select a lower difficulty (1-2 zeros recommended for testing)</li>";
echo "<li>Click 'Update Difficulty'</li>";
echo "<li>Restart your ESP32</li>";
echo "<li>Try mining again</li>";
echo "</ol>";
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

ul, ol {
    margin-left: 20px;
}

li {
    margin: 5px 0;
}

select, input[type="submit"] {
    font-family: 'Courier New', monospace;
    background: #1a1a2e;
    color: #00ff88;
    border: 1px solid #00ff88;
    padding: 5px;
    border-radius: 3px;
}

input[type="submit"]:hover {
    background: #00ff88;
    color: #000;
}
</style> 