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
    <title>API Endpoints Test</title>
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
        
        .test-section {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
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
        
        .api-output {
            background: rgba(0, 0, 0, 0.7);
            border: 1px solid #ff0088;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
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
        <h1>🔧 API Endpoints Test</h1>
        
        <div class="test-section">
            <h2>Configuration</h2>
            <p><strong>ESP32 Token:</strong> <?php echo ESP32_TOKEN; ?></p>
            <p><strong>Server URL:</strong> <?php echo isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']); ?>/api/</p>
            <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
        </div>
        
        <div class="test-section">
            <h2>API Tests</h2>
            <p>Test each API endpoint to ensure they're working correctly:</p>
            
            <div id="testResults">
                <p>Click the test buttons to check each endpoint.</p>
            </div>
            
            <button onclick="testGetPendingBlocks()">Test Get Pending Blocks</button>
            <button onclick="testSubmitBlock()">Test Submit Block</button>
            <button onclick="testConfirmBlock()">Test Confirm Block</button>
            <button onclick="testAllEndpoints()">Test All Endpoints</button>
            <button onclick="testDirectConfirmation()">Test Direct Confirmation</button>
            <button onclick="testBasicConfirm()">Test Basic Confirm</button>
            <button onclick="testDebugConfirm()">Test Debug Confirm</button>
        </div>
        
        <div class="back-link">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
    </div>

    <script>
        async function testGetPendingBlocks() {
            const output = document.getElementById('testResults');
            output.innerHTML = '<p>Testing get_pending_blocks.php...</p>';
            
            try {
                const response = await fetch('api/get_pending_blocks.php?token=<?php echo ESP32_TOKEN; ?>');
                const result = await response.json();
                
                output.innerHTML = `
                    <div class="success">✅ get_pending_blocks.php working</div>
                    <div class="api-output">Response: ${JSON.stringify(result, null, 2)}</div>
                `;
            } catch (error) {
                output.innerHTML = `
                    <div class="error">❌ get_pending_blocks.php failed</div>
                    <div class="api-output">Error: ${error.message}</div>
                `;
            }
        }

        async function testSubmitBlock() {
            const output = document.getElementById('testResults');
            output.innerHTML = '<p>Testing submit_block.php...</p>';
            
            const testBlock = {
                index: 1,
                timestamp: Math.floor(Date.now() / 1000),
                previous_hash: '0000000000000000000000000000000000000000000000000000000000000000',
                nonce: 12345,
                hash: '0000000000000000000000000000000000000000000000000000000000000000'
            };
            
            try {
                const response = await fetch('api/submit_block.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(testBlock)
                });

                const result = await response.json();
                
                output.innerHTML = `
                    <div class="success">✅ submit_block.php working</div>
                    <div class="api-output">Response: ${JSON.stringify(result, null, 2)}</div>
                `;
                
                // Store block ID for confirmation test
                if (result.success) {
                    window.testBlockId = result.block_id;
                }
            } catch (error) {
                output.innerHTML = `
                    <div class="error">❌ submit_block.php failed</div>
                    <div class="api-output">Error: ${error.message}</div>
                `;
            }
        }

        async function testConfirmBlock() {
            const output = document.getElementById('testResults');
            
            if (!window.testBlockId) {
                output.innerHTML = '<div class="warning">⚠️ Submit a block first to test confirmation</div>';
                return;
            }
            
            output.innerHTML = '<p>Testing confirm_block.php...</p>';
            
            const confirmData = {
                block_id: window.testBlockId,
                status: 'confirmed',
                reason: 'Test confirmation'
            };
            
            try {
                const response = await fetch('api/confirm_block.php?token=<?php echo ESP32_TOKEN; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(confirmData)
                });

                const result = await response.json();
                
                if (result.success) {
                    output.innerHTML = `
                        <div class="success">✅ confirm_block.php working</div>
                        <div class="api-output">Response: ${JSON.stringify(result, null, 2)}</div>
                    `;
                } else {
                    output.innerHTML = `
                        <div class="error">❌ confirm_block.php failed</div>
                        <div class="api-output">Response: ${JSON.stringify(result, null, 2)}</div>
                    `;
                }
            } catch (error) {
                output.innerHTML = `
                    <div class="error">❌ confirm_block.php failed</div>
                    <div class="api-output">Error: ${error.message}</div>
                `;
            }
        }

        async function testAllEndpoints() {
            const output = document.getElementById('testResults');
            output.innerHTML = '<p>Testing all endpoints...</p>';
            
            let results = [];
            
            // Test 1: Get pending blocks
            try {
                const response1 = await fetch('api/get_pending_blocks.php?token=<?php echo ESP32_TOKEN; ?>');
                const result1 = await response1.json();
                results.push(`✅ get_pending_blocks.php: ${result1.success ? 'OK' : 'Failed'}`);
                if (result1.success && result1.blocks) {
                    results.push(`   Found ${result1.blocks.length} pending blocks`);
                }
            } catch (error) {
                results.push(`❌ get_pending_blocks.php: ${error.message}`);
            }
            
            // Test 2: Submit block
            const testBlock = {
                index: 1,
                timestamp: Math.floor(Date.now() / 1000),
                previous_hash: '0000000000000000000000000000000000000000000000000000000000000000',
                nonce: 12345,
                hash: '0000000000000000000000000000000000000000000000000000000000000000'
            };
            
            try {
                const response2 = await fetch('api/submit_block.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(testBlock)
                });
                const result2 = await response2.json();
                results.push(`✅ submit_block.php: ${result2.success ? 'OK' : 'Failed'}`);
                
                if (result2.success) {
                    results.push(`   Block ID: ${result2.block_id}`);
                    
                    // Test 3: Confirm block
                    const confirmData = {
                        block_id: result2.block_id,
                        status: 'confirmed',
                        reason: 'Test confirmation'
                    };
                    
                    try {
                        const response3 = await fetch('api/confirm_block.php?token=<?php echo ESP32_TOKEN; ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(confirmData)
                        });
                        const result3 = await response3.json();
                        results.push(`✅ confirm_block.php: ${result3.success ? 'OK' : 'Failed'}`);
                        if (result3.success) {
                            results.push(`   Message: ${result3.message}`);
                            results.push(`   Details: ${result3.details}`);
                        }
                    } catch (error) {
                        results.push(`❌ confirm_block.php: ${error.message}`);
                    }
                }
            } catch (error) {
                results.push(`❌ submit_block.php: ${error.message}`);
            }
            
            output.innerHTML = `
                <div class="success">API Endpoints Test Results:</div>
                <div class="api-output">${results.join('\n')}</div>
            `;
        }

        async function testDirectConfirmation() {
            const output = document.getElementById('testResults');
            output.innerHTML = '<p>Testing direct block confirmation (bypassing ESP32)...</p>';
            
            // First submit a block
            const testBlock = {
                index: 1,
                timestamp: Math.floor(Date.now() / 1000),
                previous_hash: '0000000000000000000000000000000000000000000000000000000000000000',
                nonce: 12345,
                hash: '0000000000000000000000000000000000000000000000000000000000000000'
            };
            
            try {
                // Submit block
                const submitResponse = await fetch('api/submit_block.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(testBlock)
                });
                const submitResult = await submitResponse.json();
                
                if (submitResult.success) {
                    output.innerHTML += `<p>✅ Block submitted with ID: ${submitResult.block_id}</p>`;
                    
                    // Immediately confirm it
                    const confirmData = {
                        block_id: submitResult.block_id,
                        status: 'confirmed',
                        reason: 'Direct test confirmation'
                    };
                    
                    const confirmResponse = await fetch('api/confirm_block.php?token=<?php echo ESP32_TOKEN; ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(confirmData)
                    });
                    const confirmResult = await confirmResponse.json();
                    
                    if (confirmResult.success) {
                        output.innerHTML += `
                            <div class="success">✅ Direct confirmation successful!</div>
                            <div class="api-output">Response: ${JSON.stringify(confirmResult, null, 2)}</div>
                        `;
                    } else {
                        output.innerHTML += `
                            <div class="error">❌ Direct confirmation failed</div>
                            <div class="api-output">Response: ${JSON.stringify(confirmResult, null, 2)}</div>
                        `;
                    }
                } else {
                    output.innerHTML += `
                        <div class="error">❌ Block submission failed</div>
                        <div class="api-output">Response: ${JSON.stringify(submitResult, null, 2)}</div>
                    `;
                }
            } catch (error) {
                output.innerHTML += `
                    <div class="error">❌ Test failed</div>
                    <div class="api-output">Error: ${error.message}</div>
                `;
            }
        }

        async function testBasicConfirm() {
            const output = document.getElementById('testResults');
            output.innerHTML = '<p>Testing basic confirm_block.php...</p>';
            
            try {
                const response = await fetch('api/test_confirm_block.php');
                const result = await response.json();
                
                output.innerHTML = `
                    <div class="success">✅ Basic confirm_block.php working</div>
                    <div class="api-output">Response: ${JSON.stringify(result, null, 2)}</div>
                `;
            } catch (error) {
                output.innerHTML = `
                    <div class="error">❌ Basic confirm_block.php failed</div>
                    <div class="api-output">Error: ${error.message}</div>
                `;
            }
        }

        async function testDebugConfirm() {
            const output = document.getElementById('testResults');
            output.innerHTML = '<p>Testing debug confirm_block.php...</p>';
            
            // First submit a block
            const testBlock = {
                index: 1,
                timestamp: Math.floor(Date.now() / 1000),
                previous_hash: '0000000000000000000000000000000000000000000000000000000000000000',
                nonce: 12345,
                hash: '0000000000000000000000000000000000000000000000000000000000000000'
            };
            
            try {
                // Submit block
                const submitResponse = await fetch('api/submit_block.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(testBlock)
                });
                const submitResult = await submitResponse.json();
                
                if (submitResult.success) {
                    output.innerHTML += `<p>✅ Block submitted with ID: ${submitResult.block_id}</p>`;
                    
                    // Test debug confirmation
                    const confirmData = {
                        block_id: submitResult.block_id,
                        status: 'confirmed',
                        reason: 'Debug test confirmation'
                    };
                    
                    const confirmResponse = await fetch('api/debug_confirm_block.php?token=<?php echo ESP32_TOKEN; ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(confirmData)
                    });
                    
                    const confirmText = await confirmResponse.text();
                    let confirmResult;
                    
                    try {
                        confirmResult = JSON.parse(confirmText);
                    } catch (parseError) {
                        output.innerHTML += `
                            <div class="error">❌ Debug confirm failed - Invalid JSON</div>
                            <div class="api-output">Raw response: ${confirmText}</div>
                        `;
                        return;
                    }
                    
                    if (confirmResult.success) {
                        output.innerHTML += `
                            <div class="success">✅ Debug confirmation successful!</div>
                            <div class="api-output">Response: ${JSON.stringify(confirmResult, null, 2)}</div>
                        `;
                    } else {
                        output.innerHTML += `
                            <div class="error">❌ Debug confirmation failed</div>
                            <div class="api-output">Response: ${JSON.stringify(confirmResult, null, 2)}</div>
                        `;
                    }
                } else {
                    output.innerHTML += `
                        <div class="error">❌ Block submission failed</div>
                        <div class="api-output">Response: ${JSON.stringify(submitResult, null, 2)}</div>
                    `;
                }
            } catch (error) {
                output.innerHTML += `
                    <div class="error">❌ Test failed</div>
                    <div class="api-output">Error: ${error.message}</div>
                `;
            }
        }
    </script>
</body>
</html> 