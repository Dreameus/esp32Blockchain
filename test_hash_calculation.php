<?php
require_once 'config.php';

echo "<h2>Hash Calculation Test</h2>";

// Test the same hash calculation as ESP32
function testHashCalculation($index, $timestamp, $previous_hash, $nonce) {
    $blockString = $index . $timestamp . $previous_hash . $nonce;
    $calculatedHash = hash('sha256', $blockString);
    return $calculatedHash;
}

// Test the rejected block
$rejected_hash = "f8bbd4a733c47cd9531273de393f0f56ca00b8d0413a4662f0cc03a801316527";

echo "<h3>Testing Rejected Block Hash</h3>";
echo "<p><strong>Rejected hash:</strong> $rejected_hash</p>";

// Try to find the block data that produces this hash
echo "<h3>Attempting to find block data for rejected hash...</h3>";

$found = false;
$test_nonce = 0;
$max_tests = 1000;

while ($test_nonce < $max_tests && !$found) {
    $test_index = 22; // Block 22 from your log
    $test_timestamp = time() - 10; // Recent timestamp
    $test_previous_hash = "0000000000000000000000000000000000000000000000000000000000000000"; // Genesis block
    
    $test_hash = testHashCalculation($test_index, $test_timestamp, $test_previous_hash, $test_nonce);
    
    if ($test_hash === $rejected_hash) {
        echo "<p style='color: green;'>✅ Found matching block data!</p>";
        echo "<p><strong>Index:</strong> $test_index</p>";
        echo "<p><strong>Timestamp:</strong> $test_timestamp</p>";
        echo "<p><strong>Previous Hash:</strong> $test_previous_hash</p>";
        echo "<p><strong>Nonce:</strong> $test_nonce</p>";
        echo "<p><strong>Calculated Hash:</strong> $test_hash</p>";
        $found = true;
    }
    
    $test_nonce++;
}

if (!$found) {
    echo "<p style='color: red;'>❌ Could not find exact block data for rejected hash</p>";
}

// Test current difficulty
echo "<h3>Current Difficulty Test</h3>";
echo "<p><strong>Server Difficulty:</strong> " . DIFFICULTY . " leading zeros</p>";
echo "<p><strong>ESP32 Difficulty:</strong> 4 leading zeros (hardcoded)</p>";

// Test if rejected hash meets different difficulties
for ($i = 0; $i <= 6; $i++) {
    $meets = true;
    for ($j = 0; $j < $i; $j++) {
        if ($rejected_hash[$j] !== '0') {
            $meets = false;
            break;
        }
    }
    $status = $meets ? "✅ Meets" : "❌ Doesn't meet";
    echo "<p><strong>$i zeros:</strong> $status</p>";
}

// Generate some test hashes
echo "<h3>Test Hash Generation</h3>";
echo "<p>Generating some test hashes with different nonces...</p>";

for ($nonce = 0; $nonce < 10; $nonce++) {
    $test_index = 22;
    $test_timestamp = time();
    $test_previous_hash = "0000000000000000000000000000000000000000000000000000000000000000";
    
    $test_hash = testHashCalculation($test_index, $test_timestamp, $test_previous_hash, $nonce);
    
    $meets_current = true;
    for ($j = 0; $j < DIFFICULTY; $j++) {
        if ($test_hash[$j] !== '0') {
            $meets_current = false;
            break;
        }
    }
    
    $status = $meets_current ? "✅ Valid" : "❌ Invalid";
    echo "<p><strong>Nonce $nonce:</strong> $test_hash $status</p>";
}

echo "<h3>Recommendations</h3>";
echo "<ol>";
echo "<li><strong>Lower the difficulty</strong> - Use 1-2 leading zeros for testing</li>";
echo "<li><strong>Update ESP32 code</strong> - Change DIFFICULTY constant to match server</li>";
echo "<li><strong>Test with known valid hash</strong> - Generate a hash that meets current difficulty</li>";
echo "</ol>";

echo "<p><a href='adjust_difficulty.php' style='color: #00ffff;'>→ Adjust Difficulty</a></p>";
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