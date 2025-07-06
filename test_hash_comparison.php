<?php
require_once 'config.php';

echo "<h2>Hash Calculation Comparison Test</h2>";

// Test the exact same block data that was rejected
$test_index = 36; // Block 36 from your log
$test_timestamp = 1751716159; // From your log
$test_previous_hash = "0000000000000000000000000000000000000000000000000000000000000000";
$test_nonce = 12345; // We'll try to find the actual nonce

echo "<h3>Testing Block 36 Data</h3>";
echo "<p><strong>Index:</strong> $test_index</p>";
echo "<p><strong>Timestamp:</strong> $test_timestamp</p>";
echo "<p><strong>Previous Hash:</strong> $test_previous_hash</p>";
echo "<p><strong>Nonce:</strong> $test_nonce</p>";

// Test different block string formats
echo "<h3>Testing Different Block String Formats</h3>";

// Format 1: Dashboard format (JavaScript)
$dashboard_block_string = $test_index . $test_timestamp . $test_previous_hash . $test_nonce;
$dashboard_hash = hash('sha256', $dashboard_block_string);

echo "<p><strong>Dashboard Format:</strong></p>";
echo "<p>Block String: <code>$dashboard_block_string</code></p>";
echo "<p>Hash: <code>$dashboard_hash</code></p>";

// Format 2: ESP32 format (Arduino String concatenation)
$esp32_block_string = (string)$test_index . (string)$test_timestamp . $test_previous_hash . (string)$test_nonce;
$esp32_hash = hash('sha256', $esp32_block_string);

echo "<p><strong>ESP32 Format:</strong></p>";
echo "<p>Block String: <code>$esp32_block_string</code></p>";
echo "<p>Hash: <code>$esp32_hash</code></p>";

// Format 3: Try with different data types
$esp32_int_index = (int)$test_index;
$esp32_long_timestamp = (int)$test_timestamp;
$esp32_long_nonce = (int)$test_nonce;

$esp32_alt_block_string = $esp32_int_index . $esp32_long_timestamp . $test_previous_hash . $esp32_long_nonce;
$esp32_alt_hash = hash('sha256', $esp32_alt_block_string);

echo "<p><strong>ESP32 Alternative Format:</strong></p>";
echo "<p>Block String: <code>$esp32_alt_block_string</code></p>";
echo "<p>Hash: <code>$esp32_alt_hash</code></p>";

// Test the rejected hash
$rejected_hash = "201fe9dc38ef46ae0adf7126d30f77217c3811350329c4b7db872f6d745ba1c1";
echo "<p><strong>Rejected Hash:</strong> <code>$rejected_hash</code></p>";

echo "<h3>Hash Comparison Results</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Format</th><th>Block String</th><th>Hash</th><th>Matches Rejected?</th></tr>";
echo "<tr><td>Dashboard</td><td>" . substr($dashboard_block_string, 0, 50) . "...</td><td>$dashboard_hash</td><td>" . ($dashboard_hash === $rejected_hash ? "✅ YES" : "❌ NO") . "</td></tr>";
echo "<tr><td>ESP32</td><td>" . substr($esp32_block_string, 0, 50) . "...</td><td>$esp32_hash</td><td>" . ($esp32_hash === $rejected_hash ? "✅ YES" : "❌ NO") . "</td></tr>";
echo "<tr><td>ESP32 Alt</td><td>" . substr($esp32_alt_block_string, 0, 50) . "...</td><td>$esp32_alt_hash</td><td>" . ($esp32_alt_hash === $rejected_hash ? "✅ YES" : "❌ NO") . "</td></tr>";
echo "</table>";

// Try to find the exact nonce that produces the rejected hash
echo "<h3>Searching for Exact Nonce</h3>";
$found = false;
$max_search = 50000;

for ($nonce = 0; $nonce < $max_search; $nonce++) {
    // Try dashboard format
    $block_string = $test_index . $test_timestamp . $test_previous_hash . $nonce;
    $calculated_hash = hash('sha256', $block_string);
    
    if ($calculated_hash === $rejected_hash) {
        echo "<p style='color: green;'>✅ Found matching nonce with Dashboard format: $nonce</p>";
        $found = true;
        break;
    }
    
    // Try ESP32 format
    $esp32_string = (string)$test_index . (string)$test_timestamp . $test_previous_hash . (string)$nonce;
    $esp32_calculated = hash('sha256', $esp32_string);
    
    if ($esp32_calculated === $rejected_hash) {
        echo "<p style='color: green;'>✅ Found matching nonce with ESP32 format: $nonce</p>";
        $found = true;
        break;
    }
}

if (!$found) {
    echo "<p style='color: red;'>❌ Could not find exact nonce in first $max_search attempts</p>";
}

echo "<h3>Difficulty Analysis</h3>";
echo "<p><strong>Current Server Difficulty:</strong> " . DIFFICULTY . " leading zeros</p>";

// Test if the rejected hash meets different difficulties
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

echo "<h3>Recommendations</h3>";
echo "<ol>";
echo "<li><strong>Check ESP32 serial output</strong> - Look for the exact block string being calculated</li>";
echo "<li><strong>Verify data types</strong> - Make sure ESP32 is using the same data types</li>";
echo "<li><strong>Test with known data</strong> - Use a simple test case with known input/output</li>";
echo "<li><strong>Update ESP32 difficulty</strong> - Change DIFFICULTY from 4 to " . DIFFICULTY . "</li>";
echo "</ol>";

echo "<h3>Quick Test with Simple Data</h3>";
$simple_index = 1;
$simple_timestamp = 1234567890;
$simple_previous = "0000000000000000000000000000000000000000000000000000000000000000";
$simple_nonce = 0;

$simple_dashboard = $simple_index . $simple_timestamp . $simple_previous . $simple_nonce;
$simple_esp32 = (string)$simple_index . (string)$simple_timestamp . $simple_previous . (string)$simple_nonce;

$simple_dashboard_hash = hash('sha256', $simple_dashboard);
$simple_esp32_hash = hash('sha256', $simple_esp32);

echo "<p><strong>Simple Test:</strong></p>";
echo "<p>Dashboard: <code>$simple_dashboard_hash</code></p>";
echo "<p>ESP32: <code>$simple_esp32_hash</code></p>";
echo "<p>Match: " . ($simple_dashboard_hash === $simple_esp32_hash ? "✅ YES" : "❌ NO") . "</p>";
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

table {
    border: 1px solid #00ff88;
    margin: 10px 0;
}

th, td {
    border: 1px solid #00ff88;
    padding: 8px;
    text-align: left;
}

th {
    background: rgba(0, 255, 136, 0.1);
    color: #00ffff;
}

code {
    background: rgba(0, 0, 0, 0.5);
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
}

ol {
    margin-left: 20px;
}

li {
    margin: 5px 0;
}
</style> 