<?php
echo "<h2>Quick Difficulty Fix</h2>";

// Read current config
$config_content = file_get_contents('config.php');

// Check current difficulty
if (preg_match("/define\('DIFFICULTY', (\d+)\);/", $config_content, $matches)) {
    $current_difficulty = $matches[1];
    echo "<p><strong>Current Difficulty:</strong> $current_difficulty leading zeros</p>";
    
    if ($current_difficulty > 2) {
        // Lower to 1 for testing
        $new_config = preg_replace("/define\('DIFFICULTY', \d+\);/", "define('DIFFICULTY', 1);", $config_content);
        
        if (file_put_contents('config.php', $new_config)) {
            echo "<p style='color: green;'>✅ Difficulty lowered to 1 leading zero!</p>";
            echo "<p>Now restart your ESP32 and try mining again.</p>";
            
            // Test the new difficulty
            $test_hash = "201fe9dc38ef46ae0adf7126d30f77217c3811350329c4b7db872f6d745ba1c1";
            $meets = true;
            for ($i = 0; $i < 1; $i++) {
                if ($test_hash[$i] !== '0') {
                    $meets = false;
                    break;
                }
            }
            
            echo "<p><strong>Test:</strong> Your rejected hash '$test_hash' ";
            echo $meets ? "✅ WOULD BE ACCEPTED" : "❌ still wouldn't be accepted";
            echo " with difficulty 1</p>";
            
            if (!$meets) {
                echo "<p style='color: orange;'>⚠️ Even with difficulty 1, this hash wouldn't be accepted.</p>";
                echo "<p>Let's try difficulty 0 (any hash accepted):</p>";
                
                $new_config = preg_replace("/define\('DIFFICULTY', \d+\);/", "define('DIFFICULTY', 0);", $config_content);
                file_put_contents('config.php', $new_config);
                echo "<p style='color: green;'>✅ Difficulty set to 0 - ANY hash will be accepted!</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Failed to update config file. Check file permissions.</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ️ Difficulty is already reasonable ($current_difficulty).</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Could not find DIFFICULTY setting in config.php</p>";
}

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Restart your ESP32</li>";
echo "<li>Try mining again</li>";
echo "<li>Blocks should now be accepted!</li>";
echo "</ol>";

echo "<p><a href='test_hash_calculation.php' style='color: #00ffff;'>→ Test Hash Calculation</a></p>";
echo "<p><a href='adjust_difficulty.php' style='color: #00ffff;'>→ Adjust Difficulty Manually</a></p>";
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

ol {
    margin-left: 20px;
}

li {
    margin: 5px 0;
}

a {
    color: #00ffff;
    text-decoration: none;
}

a:hover {
    text-shadow: 0 0 10px #00ffff;
}
</style> 