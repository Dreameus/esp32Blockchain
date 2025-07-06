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
    <title>ESP32 Validation Test</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
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
        
        .test-section {
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 ESP32 Validation Test</h1>
        
        <div class="test-section">
            <h2>Current Configuration</h2>
            <p><strong>Difficulty:</strong> <?php echo DIFFICULTY; ?> leading zeros</p>
            <p><strong>Required Pattern:</strong> <?php echo str_repeat('0', DIFFICULTY); ?>...</p>
            <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
        </div>
        
        <div class="test-section">
            <h2>ESP32 Validation Requirements</h2>
            <p>The ESP32 expects blocks with these fields:</p>
            <ul>
                <li><strong>index:</strong> Block number (integer)</li>
                <li><strong>timestamp:</strong> Unix timestamp (integer)</li>
                <li><strong>previous_hash:</strong> 64-character hex string</li>
                <li><strong>nonce:</strong> Integer value</li>
                <li><strong>miner_id:</strong> User ID (integer)</li>
            </ul>
        </div>
        
        <div class="test-section">
            <h2>Test Block Generation</h2>
            <p>Generate a test block that should pass ESP32 validation:</p>
            
            <div id="testResults">
                <p>Click "Generate Test Block" to create a valid block.</p>
            </div>
            
            <button onclick="generateTestBlock()">Generate Test Block</button>
            <button onclick="testSubmission()">Test Submit to Server</button>
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

        async function generateTestBlock() {
            const output = document.getElementById('testResults');
            output.innerHTML = '<p>Generating test block...</p>';
            
            const difficulty = <?php echo DIFFICULTY; ?>;
            const timestamp = Math.floor(Date.now() / 1000);
            const previousHash = '0000000000000000000000000000000000000000000000000000000000000000';
            const index = 1;
            const minerId = <?php echo $user_id; ?>;
            
            let attempts = 0;
            let found = false;
            let startTime = Date.now();
            
            for (let nonce = 0; nonce < 50000; nonce++) {
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
                    `;
                    
                    // Store block data for submission test
                    window.testBlockData = blockData;
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

        async function testSubmission() {
            if (!window.testBlockData) {
                alert('Generate a test block first!');
                return;
            }
            
            const output = document.getElementById('testResults');
            output.innerHTML += '<p>Submitting block to server...</p>';
            
            try {
                const response = await fetch('api/submit_block.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(window.testBlockData)
                });

                const result = await response.json();
                if (result.success) {
                    output.innerHTML += `
                        <div class="success">✅ Block submitted successfully!</div>
                        <p><strong>Block ID:</strong> ${result.block_id}</p>
                        <p><strong>Message:</strong> ${result.message}</p>
                    `;
                } else {
                    output.innerHTML += `
                        <div class="error">❌ Block submission failed</div>
                        <p><strong>Error:</strong> ${result.error}</p>
                    `;
                }
            } catch (error) {
                output.innerHTML += `
                    <div class="error">❌ Submission error</div>
                    <p><strong>Error:</strong> ${error.message}</p>
                `;
            }
        }
    </script>
</body>
</html> 