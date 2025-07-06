<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Debug Validation</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff88;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.2);
        }
        
        h1, h2 {
            color: #00ffff;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .debug-section {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .hash-example {
            font-family: monospace;
            font-size: 0.8em;
            color: #00ffff;
            word-break: break-all;
            margin: 10px 0;
            background: rgba(0, 0, 0, 0.3);
            padding: 10px;
            border-radius: 5px;
        }
        
        .success {
            color: #00ff88;
            background: rgba(0, 255, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .error {
            color: #ff6666;
            background: rgba(255, 0, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .warning {
            color: #ffff00;
            background: rgba(255, 255, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        button {
            background: linear-gradient(45deg, #00ff88, #00ffff);
            border: none;
            color: #000;
            padding: 10px 20px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            cursor: pointer;
            margin: 10px;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: #00ffff;
            text-decoration: none;
            padding: 10px 20px;
            border: 1px solid #00ffff;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .debug-output {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #ff0088;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ESP32 Debug Validation</h1>
        
        <div class="debug-section">
            <h2>ESP32 Validation Logic</h2>
            <p>This simulates exactly what the ESP32 does when validating blocks:</p>
            <ul>
                <li><strong>Required Fields:</strong> index, timestamp, previous_hash, nonce, miner_id</li>
                <li><strong>Timestamp Check:</strong> Must be within 1 hour of current time</li>
                <li><strong>Hash Format:</strong> previous_hash must be 64 characters</li>
                <li><strong>Proof of Work:</strong> SHA256(index + timestamp + previous_hash + nonce) must meet difficulty</li>
            </ul>
        </div>
        
        <div class="debug-section">
            <h2>Debug Block Generation</h2>
            <p>Generate a block and see exactly what the ESP32 will check:</p>
            
            <div id="debugResults">
                <p>Click "Generate Debug Block" to see the validation process.</p>
            </div>
            
            <button onclick="generateDebugBlock()">Generate Debug Block</button>
            <button onclick="testWithCurrentTime()">Test with Current Time</button>
            <button onclick="checkPendingBlocks()">Check Pending Blocks</button>
        </div>
        
        <div class="back-link">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        async function sha256(str) {
            const encoder = new TextEncoder();
            const data = encoder.encode(str);
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = Array.from(new Uint8Array(hashBuffer));
            const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
            return hashHex;
        }

        function meetsDifficulty(hash, difficulty) {
            for (let i = 0; i < difficulty; i++) {
                if (hash[i] !== '0') return false;
            }
            return true;
        }

        // Simulate ESP32 validation logic
        async function simulateESP32Validation(blockData) {
            const output = document.getElementById('debugResults');
            let debugLog = "=== ESP32 VALIDATION SIMULATION ===\n\n";
            
            // Check required fields
            debugLog += "1. Checking required fields...\n";
            const requiredFields = ['index', 'timestamp', 'previous_hash', 'nonce', 'miner_id'];
            for (const field of requiredFields) {
                if (blockData.hasOwnProperty(field)) {
                    debugLog += `   ✅ ${field}: ${blockData[field]}\n`;
                } else {
                    debugLog += `   ❌ Missing field: ${field}\n`;
                    return false;
                }
            }
            
            // Validate block index
            debugLog += "\n2. Validating block index...\n";
            if (blockData.index < 0) {
                debugLog += `   ❌ Invalid block index: ${blockData.index}\n`;
                return false;
            }
            debugLog += `   ✅ Block index: ${blockData.index}\n`;
            
            // Validate timestamp (ESP32 uses millis()/1000 as current time)
            debugLog += "\n3. Validating timestamp...\n";
            const currentTime = Math.floor(Date.now() / 1000);
            const timestamp = blockData.timestamp;
            const timeDiff = Math.abs(currentTime - timestamp);
            debugLog += `   Current time: ${currentTime}\n`;
            debugLog += `   Block timestamp: ${timestamp}\n`;
            debugLog += `   Time difference: ${timeDiff} seconds\n`;
            
            if (timestamp > currentTime + 300 || timestamp < currentTime - 3600) {
                debugLog += `   ❌ Timestamp too old/new (must be within 1 hour)\n`;
                return false;
            }
            debugLog += `   ✅ Timestamp is valid\n`;
            
            // Validate previous hash format
            debugLog += "\n4. Validating previous hash format...\n";
            const previousHash = blockData.previous_hash;
            if (previousHash.length !== 64) {
                debugLog += `   ❌ Invalid previous hash length: ${previousHash.length} (should be 64)\n`;
                return false;
            }
            debugLog += `   ✅ Previous hash length: ${previousHash.length}\n`;
            
            // Validate nonce
            debugLog += "\n5. Validating nonce...\n";
            if (blockData.nonce < 0) {
                debugLog += `   ❌ Invalid nonce: ${blockData.nonce}\n`;
                return false;
            }
            debugLog += `   ✅ Nonce: ${blockData.nonce}\n`;
            
            // Validate proof of work
            debugLog += "\n6. Validating proof of work...\n";
            const blockString = blockData.index + blockData.timestamp + blockData.previous_hash + blockData.nonce;
            debugLog += `   Block string: ${blockString}\n`;
            
            const calculatedHash = await sha256(blockString);
            debugLog += `   Calculated hash: ${calculatedHash}\n`;
            
            const difficulty = <?php echo DIFFICULTY; ?>;
            const requiredPattern = '0'.repeat(difficulty);
            debugLog += `   Required pattern: ${requiredPattern}...\n`;
            debugLog += `   Hash starts with: ${calculatedHash.substring(0, difficulty)}\n`;
            
            if (!meetsDifficulty(calculatedHash, difficulty)) {
                debugLog += `   ❌ Hash doesn't meet difficulty requirement\n`;
                return false;
            }
            debugLog += `   ✅ Hash meets difficulty requirement\n`;
            
            debugLog += "\n=== VALIDATION SUCCESSFUL ===\n";
            return true;
        }

        async function generateDebugBlock() {
            const output = document.getElementById('debugResults');
            output.innerHTML = '<p>Generating debug block...</p>';
            
            const difficulty = <?php echo DIFFICULTY; ?>;
            const timestamp = Math.floor(Date.now() / 1000);
            const previousHash = '0000000000000000000000000000000000000000000000000000000000000000';
            const index = 1;
            const minerId = <?php echo $user_id; ?>;
            
            let attempts = 0;
            let found = false;
            let startTime = Date.now();
            
            for (let nonce = 0; nonce < 10000; nonce++) {
                attempts++;
                const blockString = index + timestamp + previousHash + nonce;
                const hash = await sha256(blockString);
                
                if (meetsDifficulty(hash, difficulty)) {
                    found = true;
                    const elapsed = (Date.now() - startTime) / 1000;
                    const hashRate = Math.floor(attempts / elapsed);
                    
                    const blockData = {
                        index: index,
                        timestamp: timestamp,
                        previous_hash: previousHash,
                        nonce: nonce,
                        hash: hash,
                        miner_id: minerId
                    };
                    
                    output.innerHTML = `
                        <div class="success">✅ Valid block found!</div>
                        <p><strong>Block Data:</strong></p>
                        <div class="hash-example">${JSON.stringify(blockData, null, 2)}</div>
                        <p><strong>Hash:</strong> <span class="hash-example">${hash}</span></p>
                        <p><strong>Nonce:</strong> ${nonce}</p>
                        <p><strong>Attempts:</strong> ${attempts.toLocaleString()}</p>
                        <p><strong>Time:</strong> ${elapsed.toFixed(2)} seconds</p>
                        <p><strong>Hash Rate:</strong> ${hashRate.toLocaleString()} H/s</p>
                        <p><strong>Difficulty:</strong> ${difficulty} leading zeros</p>
                        
                        <button onclick="validateBlock(${JSON.stringify(blockData).replace(/"/g, '&quot;')})">Simulate ESP32 Validation</button>
                    `;
                    
                    window.lastBlockData = blockData;
                    break;
                }
                
                if (attempts % 1000 === 0) {
                    output.innerHTML = `<p>Tested ${attempts.toLocaleString()} hashes...</p>`;
                }
            }
            
            if (!found) {
                const elapsed = (Date.now() - startTime) / 1000;
                const hashRate = Math.floor(attempts / elapsed);
                
                output.innerHTML = `
                    <div class="error">❌ No valid block found in ${attempts.toLocaleString()} attempts</div>
                    <p><strong>Time:</strong> ${elapsed.toFixed(2)} seconds</p>
                    <p><strong>Hash Rate:</strong> ${hashRate.toLocaleString()} H/s</p>
                    <p><strong>Difficulty:</strong> ${difficulty} leading zeros</p>
                    <p>Try lowering the difficulty in mining_test.php</p>
                `;
            }
        }

        async function validateBlock(blockData) {
            const output = document.getElementById('debugResults');
            output.innerHTML += '<div class="debug-output" id="validationOutput">Running ESP32 validation simulation...</div>';
            
            const isValid = await simulateESP32Validation(blockData);
            const validationOutput = document.getElementById('validationOutput');
            
            if (isValid) {
                validationOutput.innerHTML += '\n<div class="success">✅ ESP32 would ACCEPT this block!</div>';
            } else {
                validationOutput.innerHTML += '\n<div class="error">❌ ESP32 would REJECT this block!</div>';
            }
        }

        async function testWithCurrentTime() {
            const output = document.getElementById('debugResults');
            const currentTime = Math.floor(Date.now() / 1000);
            
            output.innerHTML = `
                <div class="warning">Current Time Test</div>
                <p><strong>Current Unix timestamp:</strong> ${currentTime}</p>
                <p><strong>ESP32 time window:</strong> ${currentTime - 3600} to ${currentTime + 300}</p>
                <p><strong>Valid range:</strong> ±1 hour from current time</p>
                
                <p>If your block timestamp is outside this range, ESP32 will reject it.</p>
            `;
        }

        async function checkPendingBlocks() {
            const output = document.getElementById('debugResults');
            output.innerHTML = '<p>Checking pending blocks...</p>';
            
            try {
                const response = await fetch('api/get_pending_blocks.php?token=esp32_secret_token_2024');
                const result = await response.json();
                
                if (result.success && result.blocks.length > 0) {
                    output.innerHTML = `
                        <div class="warning">Found ${result.blocks.length} pending blocks</div>
                        <p>These blocks are waiting for ESP32 validation:</p>
                    `;
                    
                    for (const block of result.blocks) {
                        output.innerHTML += `
                            <div class="debug-output">
                                <strong>Block ID:</strong> ${block.id}<br>
                                <strong>Data:</strong> ${JSON.stringify(block.data, null, 2)}
                            </div>
                        `;
                    }
                } else {
                    output.innerHTML = '<div class="success">No pending blocks found</div>';
                }
            } catch (error) {
                output.innerHTML = `<div class="error">Error checking pending blocks: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html> 