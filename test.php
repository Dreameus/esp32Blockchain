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

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle difficulty adjustment
$success_message = '';
$error_message = '';

if (isset($_POST['adjust_difficulty'])) {
    $new_difficulty = (int)$_POST['new_difficulty'];
    if ($new_difficulty >= 0 && $new_difficulty <= 8) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("UPDATE config SET value = ? WHERE setting = 'difficulty'");
            $stmt->bind_param('i', $new_difficulty);
            $stmt->execute();
            $conn->close();
            $success_message = "Difficulty updated to $new_difficulty leading zeros!";
        } catch (Exception $e) {
            $error_message = "Error updating difficulty: " . $e->getMessage();
        }
    } else {
        $error_message = "Difficulty must be between 0 and 8";
    }
}

// Get current difficulty
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT value FROM config WHERE setting = 'difficulty'");
    $stmt->execute();
    $result = $stmt->get_result();
    $current_difficulty = $result->fetch_assoc()['value'] ?? DIFFICULTY;
    $conn->close();
} catch (Exception $e) {
    $current_difficulty = DIFFICULTY;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Blockchain - Testing Center</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
            color: #00ff88;
            overflow-x: hidden;
        }

        .header {
            background: rgba(0, 0, 0, 0.9);
            border-bottom: 3px solid #00ff88;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.3);
        }

        .logo {
            font-size: 2em;
            font-weight: bold;
            color: #00ff88;
            text-shadow: 0 0 20px #00ff88;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .nav-link {
            color: #00ff88;
            text-decoration: none;
            padding: 8px 15px;
            border: 1px solid #00ff88;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: #00ff88;
            color: #000;
        }

        .logout-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 0, 136, 0.4);
        }

        .main-content {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 30px;
            text-shadow: 0 0 30px #00ff88;
            animation: titleGlow 2s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            from { text-shadow: 0 0 30px #00ff88; }
            to { text-shadow: 0 0 50px #00ff88, 0 0 70px #00ff88; }
        }

        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .test-card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff88;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
            transition: all 0.3s ease;
        }

        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.4);
        }

        .test-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ff88, #00ffff, #ff0088, #00ff88);
            border-radius: 15px;
            z-index: -1;
            animation: borderGlow 3s ease-in-out infinite;
        }

        @keyframes borderGlow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .card-title {
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            text-shadow: 0 0 10px #00ff88;
        }

        .info-box {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #00ff88;
            font-weight: bold;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 5px;
            color: #00ff88;
            font-family: 'Courier New', monospace;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.5);
        }

        .btn {
            background: linear-gradient(45deg, #00ff88, #00ffff);
            border: none;
            color: #000;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            transition: all 0.3s ease;
            margin: 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4);
        }

        .btn-danger {
            background: linear-gradient(45deg, #ff0088, #ff6666);
        }

        .btn-warning {
            background: linear-gradient(45deg, #ff8800, #ffaa00);
        }

        .success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #00ff00;
            color: #00ff88;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #ff6666;
            color: #ff6666;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .test-result {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        .difficulty-guide {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .difficulty-guide ul {
            margin-left: 20px;
        }

        .difficulty-guide li {
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .test-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
            
            .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .page-title {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">ESP32 Blockchain</div>
        <div class="user-info">
            <div>Welcome, <?php echo htmlspecialchars($username); ?></div>
            <a href="dashboard.php" class="nav-link">🏠 Dashboard</a>
            <a href="contest.php" class="nav-link">🎯 Contest</a>
            <a href="mining_leaderboard.php" class="nav-link">🏆 Leaderboard</a>
            <a href="rewards.php" class="nav-link">💰 Rewards</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="main-content">
        <h1 class="page-title">🧪 Testing Center</h1>
        
        <?php if ($success_message): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="test-grid">
            <!-- Mining Configuration Test -->
            <div class="test-card">
                <div class="card-title">🔧 Mining Configuration</div>
                <div class="info-box">
                    <p><strong>Current Difficulty:</strong> <?php echo $current_difficulty; ?> leading zeros</p>
                    <p><strong>Required Hash Pattern:</strong> <?php echo str_repeat('0', $current_difficulty); ?>...</p>
                    <p><strong>Block Reward:</strong> <?php echo REWARD_AMOUNT; ?> ESP32 tokens</p>
                    <p><strong>Total Supply:</strong> <?php echo number_format(TOTAL_SUPPLY); ?> ESP32 tokens</p>
                </div>
                
                <form method="POST">
                    <div class="form-group">
                        <label>New Difficulty (0-8):</label>
                        <input type="number" name="new_difficulty" min="0" max="8" value="<?php echo $current_difficulty; ?>" required>
                    </div>
                    <button type="submit" name="adjust_difficulty" class="btn">Update Difficulty</button>
                </form>
                
                <div class="difficulty-guide">
                    <p><strong>Difficulty Guide:</strong></p>
                    <ul>
                        <li>0: Very easy (1 in 16 chance) - Good for testing</li>
                        <li>1: Easy (1 in 256 chance) - Good for testing</li>
                        <li>2: Moderate (1 in 4,096 chance) - Balanced</li>
                        <li>3: Hard (1 in 65,536 chance) - Realistic</li>
                        <li>4+: Very hard - Production level</li>
                    </ul>
                </div>
            </div>

            <!-- Hash Testing -->
            <div class="test-card">
                <div class="card-title">🔍 Hash Testing</div>
                <div class="info-box">
                    <p>Test SHA256 hashing and difficulty validation</p>
                </div>
                
                <div class="form-group">
                    <label>Test Data:</label>
                    <input type="text" id="testData" value="test123" placeholder="Enter data to hash">
                </div>
                <button onclick="testHash()" class="btn">Test Hash</button>
                <button onclick="testMining()" class="btn">Test Mining</button>
                
                <div id="hashResult" class="test-result" style="display: none;"></div>
            </div>

            <!-- API Testing -->
            <div class="test-card">
                <div class="card-title">🌐 API Testing</div>
                <div class="info-box">
                    <p>Test various API endpoints</p>
                </div>
                
                <button onclick="testGetLatestBlock()" class="btn">Get Latest Block</button>
                <button onclick="testGetDifficulty()" class="btn">Get Difficulty</button>
                <button onclick="testGetPendingBlocks()" class="btn">Get Pending Blocks</button>
                <button onclick="testGetPendingTransactions()" class="btn">Get Pending Transactions</button>
                <button onclick="testGetUsers()" class="btn">Get Users</button>
                
                <div id="apiResult" class="test-result" style="display: none;"></div>
            </div>

            <!-- ESP32 Validation Test -->
            <div class="test-card">
                <div class="card-title">📡 ESP32 Validation</div>
                <div class="info-box">
                    <p>Test ESP32 block validation and communication</p>
                </div>
                
                <div class="form-group">
                    <label>ESP32 Token:</label>
                    <input type="text" id="esp32Token" value="<?php echo ESP32_TOKEN; ?>" readonly>
                </div>
                <button onclick="testESP32Connection()" class="btn">Test ESP32 Connection</button>
                <button onclick="testBlockValidation()" class="btn">Test Block Validation</button>
                <button onclick="testTransactionValidation()" class="btn">Test Transaction Validation</button>
                
                <div id="esp32Result" class="test-result" style="display: none;"></div>
            </div>

            <!-- Database Testing -->
            <div class="test-card">
                <div class="card-title">🗄️ Database Testing</div>
                <div class="info-box">
                    <p>Test database connections and queries</p>
                </div>
                
                <button onclick="testDatabaseConnection()" class="btn">Test Connection</button>
                <button onclick="testTableStructure()" class="btn">Test Tables</button>
                <button onclick="testUserData()" class="btn">Test User Data</button>
                <button onclick="testMiningStats()" class="btn">Test Mining Stats</button>
                
                <div id="dbResult" class="test-result" style="display: none;"></div>
            </div>

            <!-- System Diagnostics -->
            <div class="test-card">
                <div class="card-title">🔬 System Diagnostics</div>
                <div class="info-box">
                    <p>Comprehensive system health check</p>
                </div>
                
                <button onclick="runSystemDiagnostics()" class="btn">Run Diagnostics</button>
                <button onclick="testPerformance()" class="btn">Performance Test</button>
                <button onclick="testSecurity()" class="btn">Security Test</button>
                <button onclick="clearTestResults()" class="btn btn-danger">Clear Results</button>
                
                <div id="diagnosticResult" class="test-result" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        // Hash Testing Functions
        async function testHash() {
            const testData = document.getElementById('testData').value;
            const resultDiv = document.getElementById('hashResult');
            
            try {
                const encoder = new TextEncoder();
                const data = encoder.encode(testData);
                const hashBuffer = await crypto.subtle.digest('SHA-256', data);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                
                const meetsDifficulty = hashHex.startsWith('0'.repeat(<?php echo $current_difficulty; ?>));
                
                resultDiv.innerHTML = `Input: ${testData}\nHash: ${hashHex}\nMeets Difficulty: ${meetsDifficulty}\nDifficulty Required: ${<?php echo $current_difficulty; ?>} leading zeros`;
                resultDiv.style.display = 'block';
            } catch (error) {
                resultDiv.innerHTML = `Error: ${error.message}`;
                resultDiv.style.display = 'block';
            }
        }

        async function testMining() {
            const resultDiv = document.getElementById('hashResult');
            resultDiv.innerHTML = 'Testing mining simulation...\n';
            resultDiv.style.display = 'block';
            
            let hashCount = 0;
            const startTime = Date.now();
            const targetDifficulty = <?php echo $current_difficulty; ?>;
            
            for (let nonce = 0; nonce < 10000; nonce++) {
                const testData = `block${nonce}`;
                const encoder = new TextEncoder();
                const data = encoder.encode(testData);
                const hashBuffer = await crypto.subtle.digest('SHA-256', data);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                hashCount++;
                
                if (hashHex.startsWith('0'.repeat(targetDifficulty))) {
                    const elapsed = (Date.now() - startTime) / 1000;
                    const hashRate = Math.floor(hashCount / elapsed);
                    resultDiv.innerHTML += `\nBlock found!\nHash: ${hashHex}\nNonce: ${nonce}\nHash Rate: ${hashRate} H/s\nTime: ${elapsed.toFixed(2)}s`;
                    return;
                }
                
                if (hashCount % 1000 === 0) {
                    resultDiv.innerHTML = `Testing... ${hashCount} hashes checked\n`;
                }
            }
            
            resultDiv.innerHTML += `\nNo block found in 10,000 attempts`;
        }

        // API Testing Functions
        async function testGetLatestBlock() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = 'Testing get_latest_block.php...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('api/get_latest_block.php');
                const data = await response.json();
                resultDiv.innerHTML += JSON.stringify(data, null, 2);
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testGetDifficulty() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = 'Testing get_difficulty.php...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('api/get_difficulty.php');
                const data = await response.json();
                resultDiv.innerHTML += JSON.stringify(data, null, 2);
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testGetPendingBlocks() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = 'Testing get_pending_blocks.php...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('api/get_pending_blocks.php');
                const data = await response.json();
                resultDiv.innerHTML += JSON.stringify(data, null, 2);
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testGetPendingTransactions() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = 'Testing get_pending_transactions.php...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('api/get_pending_transactions.php');
                const data = await response.json();
                resultDiv.innerHTML += JSON.stringify(data, null, 2);
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testGetUsers() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = 'Testing get_users.php...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('api/get_users.php');
                const data = await response.json();
                resultDiv.innerHTML += JSON.stringify(data, null, 2);
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        // ESP32 Testing Functions
        async function testESP32Connection() {
            const resultDiv = document.getElementById('esp32Result');
            resultDiv.innerHTML = 'Testing ESP32 connection...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('test_esp32_validation.php?action=test_connection');
                const data = await response.text();
                resultDiv.innerHTML += data;
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testBlockValidation() {
            const resultDiv = document.getElementById('esp32Result');
            resultDiv.innerHTML = 'Testing block validation...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('test_esp32_validation.php?action=test_block');
                const data = await response.text();
                resultDiv.innerHTML += data;
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testTransactionValidation() {
            const resultDiv = document.getElementById('esp32Result');
            resultDiv.innerHTML = 'Testing transaction validation...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('test_esp32_validation.php?action=test_transaction');
                const data = await response.text();
                resultDiv.innerHTML += data;
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        // Database Testing Functions
        async function testDatabaseConnection() {
            const resultDiv = document.getElementById('dbResult');
            resultDiv.innerHTML = 'Testing database connection...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('test_db.php?action=connection');
                const data = await response.text();
                resultDiv.innerHTML += data;
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testTableStructure() {
            const resultDiv = document.getElementById('dbResult');
            resultDiv.innerHTML = 'Testing table structure...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('test_db.php?action=tables');
                const data = await response.text();
                resultDiv.innerHTML += data;
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testUserData() {
            const resultDiv = document.getElementById('dbResult');
            resultDiv.innerHTML = 'Testing user data...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('test_db.php?action=users');
                const data = await response.text();
                resultDiv.innerHTML += data;
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        async function testMiningStats() {
            const resultDiv = document.getElementById('dbResult');
            resultDiv.innerHTML = 'Testing mining stats...\n';
            resultDiv.style.display = 'block';
            
            try {
                const response = await fetch('test_db.php?action=mining');
                const data = await response.text();
                resultDiv.innerHTML += data;
            } catch (error) {
                resultDiv.innerHTML += `Error: ${error.message}`;
            }
        }

        // System Diagnostics
        async function runSystemDiagnostics() {
            const resultDiv = document.getElementById('diagnosticResult');
            resultDiv.innerHTML = 'Running system diagnostics...\n';
            resultDiv.style.display = 'block';
            
            const diagnostics = [];
            
            // Test PHP version
            diagnostics.push(`PHP Version: ${navigator.userAgent.includes('PHP') ? 'Available' : 'Not detected'}`);
            
            // Test JavaScript
            diagnostics.push(`JavaScript: Enabled`);
            
            // Test Web Crypto API
            diagnostics.push(`Web Crypto API: ${window.crypto && window.crypto.subtle ? 'Available' : 'Not available'}`);
            
            // Test Fetch API
            diagnostics.push(`Fetch API: ${window.fetch ? 'Available' : 'Not available'}`);
            
            // Test localStorage
            diagnostics.push(`Local Storage: ${window.localStorage ? 'Available' : 'Not available'}`);
            
            // Test sessionStorage
            diagnostics.push(`Session Storage: ${window.sessionStorage ? 'Available' : 'Not available'}`);
            
            // Test WebSocket
            diagnostics.push(`WebSocket: ${window.WebSocket ? 'Available' : 'Not available'}`);
            
            // Test screen resolution
            diagnostics.push(`Screen Resolution: ${screen.width}x${screen.height}`);
            
            // Test user agent
            diagnostics.push(`User Agent: ${navigator.userAgent.substring(0, 100)}...`);
            
            resultDiv.innerHTML += diagnostics.join('\n');
        }

        async function testPerformance() {
            const resultDiv = document.getElementById('diagnosticResult');
            resultDiv.innerHTML = 'Testing performance...\n';
            resultDiv.style.display = 'block';
            
            const startTime = performance.now();
            
            // Test hash performance
            let hashCount = 0;
            const hashStart = performance.now();
            for (let i = 0; i < 1000; i++) {
                const testData = `test${i}`;
                const encoder = new TextEncoder();
                const data = encoder.encode(testData);
                await crypto.subtle.digest('SHA-256', data);
                hashCount++;
            }
            const hashEnd = performance.now();
            const hashRate = Math.floor(hashCount / ((hashEnd - hashStart) / 1000));
            
            const endTime = performance.now();
            const totalTime = endTime - startTime;
            
            resultDiv.innerHTML += `Hash Rate: ${hashRate} H/s\nTotal Test Time: ${totalTime.toFixed(2)}ms`;
        }

        async function testSecurity() {
            const resultDiv = document.getElementById('diagnosticResult');
            resultDiv.innerHTML = 'Testing security...\n';
            resultDiv.style.display = 'block';
            
            const securityChecks = [];
            
            // Check if running on HTTPS
            securityChecks.push(`HTTPS: ${window.location.protocol === 'https:' ? 'Yes' : 'No'}`);
            
            // Check for secure context
            securityChecks.push(`Secure Context: ${window.isSecureContext ? 'Yes' : 'No'}`);
            
            // Check for content security policy
            const csp = document.querySelector('meta[http-equiv="Content-Security-Policy"]');
            securityChecks.push(`CSP Header: ${csp ? 'Present' : 'Not present'}`);
            
            // Check for X-Frame-Options
            securityChecks.push(`X-Frame-Options: Not detectable via JavaScript`);
            
            resultDiv.innerHTML += securityChecks.join('\n');
        }

        function clearTestResults() {
            const resultDivs = ['hashResult', 'apiResult', 'esp32Result', 'dbResult', 'diagnosticResult'];
            resultDivs.forEach(id => {
                const div = document.getElementById(id);
                if (div) {
                    div.style.display = 'none';
                    div.innerHTML = '';
                }
            });
        }
    </script>
</body>
</html> 