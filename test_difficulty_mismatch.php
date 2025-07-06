<?php
require_once 'config.php';

echo "<h2>Difficulty Mismatch Test</h2>";

// Test the exact same block data that was rejected
$test_index = 36; // Block 36 from your log
$test_timestamp = 1751716159; // From your log
$test_previous_hash = "0000000000000000000000000000000000000000000000000000000000000000";
$test_nonce = 12345; // We'll try to find the actual nonce

echo "<h3>Testing Block 36 Data</h3>";
echo "<p><strong>Index:</strong> $test_index</p>";
echo "<p><strong>Timestamp:</strong> $test_timestamp</p>";
echo "<p><strong>Previous Hash:</strong> $test_previous_hash</p>";

// Try to find the nonce that produces the rejected hash
$rejected_hash = "201fe9dc38ef46ae0adf7126d30f77217c3811350329c4b7db872f6d745ba1c1";
echo "<p><strong>Rejected Hash:</strong> $rejected_hash</p>";

echo "<h3>Searching for matching nonce...</h3>";
$found = false;
$max_search = 10000;

for ($nonce = 0; $nonce < $max_search; $nonce++) {
    $blockString = $test_index . $test_timestamp . $test_previous_hash . $nonce;
    $calculated_hash = hash('sha256', $blockString);
    
    if ($calculated_hash === $rejected_hash) {
        echo "<p style='color: green;'>✅ Found matching nonce: $nonce</p>";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "<p style='color: red;'>❌ Could not find exact nonce in first $max_search attempts</p>";
}

echo "<h3>Difficulty Analysis</h3>";
echo "<p><strong>Server Difficulty:</strong> " . DIFFICULTY . " leading zeros</p>";
echo "<p><strong>ESP32 Difficulty:</strong> 4 leading zeros (hardcoded)</p>";

// Test if the hash meets different difficulties
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

echo "<h3>ESP32 Validation Logic</h3>";
echo "<p>The ESP32 uses this validation code:</p>";
echo "<pre style='background: #1a1a2e; padding: 10px; border: 1px solid #00ff88;'>";
echo "// ESP32 validation logic:
String blockString = String(index) + String(timestamp) + previousHash + String(nonce);
String calculatedHash = sha256(blockString);

if (!meetsDifficulty(calculatedHash)) {
    Serial.println(\"Block doesn't meet difficulty requirement\");
    return false;
}

// ESP32 meetsDifficulty function:
bool meetsDifficulty(const String& hash) {
    for (int i = 0; i < DIFFICULTY; i++) {  // DIFFICULTY = 4 (hardcoded)
        if (hash[i] != '0') return false;
    }
    return true;
}";
echo "</pre>";

echo "<h3>Dashboard Mining Logic</h3>";
echo "<p>The dashboard uses this validation code:</p>";
echo "<pre style='background: #1a1a2e; padding: 10px; border: 1px solid #00ff88;'>";
echo "// Dashboard meetsDifficulty function:
function meetsDifficulty(hash) {
    for (let i = 0; i < " . DIFFICULTY . "; i++) {  // DIFFICULTY = " . DIFFICULTY . " (from server)
        if (hash[i] !== '0') return false;
    }
    return true;
}";
echo "</pre>";

echo "<h3>The Problem</h3>";
echo "<p>✅ <strong>Dashboard finds blocks</strong> with difficulty " . DIFFICULTY . "</p>";
echo "<p>❌ <strong>ESP32 rejects blocks</strong> with difficulty 4</p>";
echo "<p>🔧 <strong>Solution:</strong> Update ESP32 code to use difficulty " . DIFFICULTY . "</p>";

echo "<h3>ESP32 Code Fix</h3>";
echo "<p>Change line 12 in your ESP32 code from:</p>";
echo "<pre style='background: #1a1a2e; padding: 10px; border: 1px solid #00ff88;'>";
echo "const int DIFFICULTY = 4; // Number of leading zeros required";
echo "</pre>";
echo "<p>To:</p>";
echo "<pre style='background: #1a1a2e; padding: 10px; border: 1px solid #00ff88;'>";
echo "const int DIFFICULTY = " . DIFFICULTY . "; // Number of leading zeros required";
echo "</pre>";

echo "<h3>Quick Test</h3>";
echo "<p>Generate a test hash that meets difficulty " . DIFFICULTY . ":</p>";

$test_nonce = 0;
$found_valid = false;
while ($test_nonce < 1000 && !$found_valid) {
    $blockString = $test_index . $test_timestamp . $test_previous_hash . $test_nonce;
    $test_hash = hash('sha256', $blockString);
    
    $meets = true;
    for ($j = 0; $j < DIFFICULTY; $j++) {
        if ($test_hash[$j] !== '0') {
            $meets = false;
            break;
        }
    }
    
    if ($meets) {
        echo "<p style='color: green;'>✅ Valid hash found with nonce $test_nonce: $test_hash</p>";
        $found_valid = true;
    }
    
    $test_nonce++;
}

if (!$found_valid) {
    echo "<p style='color: orange;'>⚠️ No valid hash found in first 1000 nonces</p>";
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

pre {
    font-family: 'Courier New', monospace;
    color: #00ff88;
    overflow-x: auto;
}
</style> 