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

// Handle difficulty adjustment
if (isset($_POST['adjust_difficulty'])) {
    $new_difficulty = (int)$_POST['new_difficulty'];
    if ($new_difficulty >= 0 && $new_difficulty <= 8) {
        // Update config file
        $config_content = file_get_contents('config.php');
        $config_content = preg_replace("/define\('DIFFICULTY', \d+\);/", "define('DIFFICULTY', $new_difficulty);", $config_content);
        file_put_contents('config.php', $config_content);
        $success_message = "Difficulty updated to $new_difficulty! Refresh the page to see changes.";
    }
}

// Get current difficulty
$current_difficulty = DIFFICULTY;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mining Test - ESP32 Blockchain</title>
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
        
        .info-box {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .difficulty-form {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        input[type="number"] {
            padding: 10px;
            border: 2px solid #00ff88;
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.7);
            color: #00ff88;
            font-family: 'Courier New', monospace;
            margin: 0 10px;
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
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4);
        }
        
        .success {
            color: #00ff88;
            background: rgba(0, 255, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .test-results {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #ff0088;
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
        
        .back-link a:hover {
            background: #00ffff;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Mining Test & Configuration</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h2>Current Configuration</h2>
            <p><strong>Current Difficulty:</strong> <?php echo $current_difficulty; ?> leading zeros</p>
            <p><strong>Required Hash Pattern:</strong> <?php echo str_repeat('0', $current_difficulty); ?>...</p>
            <p><strong>Block Reward:</strong> <?php echo REWARD_AMOUNT; ?> ESP32 tokens</p>
            <p><strong>Total Supply:</strong> <?php echo number_format(TOTAL_SUPPLY); ?> ESP32 tokens</p>
        </div>
        
        <div class="difficulty-form">
            <h2>Adjust Mining Difficulty</h2>
            <p>Lower difficulty = easier to find blocks (good for testing)</p>
            <p>Higher difficulty = harder to find blocks (more realistic)</p>
            
            <form method="POST">
                <label>New Difficulty (0-8):</label>
                <input type="number" name="new_difficulty" min="0" max="8" value="<?php echo $current_difficulty; ?>" required>
                <button type="submit" name="adjust_difficulty">Update Difficulty</button>
            </form>
            
            <div style="margin-top: 15px;">
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
        
        <div class="test-results">
            <h2>Mining Test Results</h2>
            <p>Test your current difficulty setting:</p>
            
            <div id="testOutput">
                <p>Click "Test Mining" to see if blocks can be found with current difficulty.</p>
            </div>
            
            <button onclick="testMining()">Test Mining</button>
            <button onclick="clearTest()">Clear Results</button>
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

        async function testMining() {
            const output = document.getElementById('testOutput');
            output.innerHTML = '<p>Testing mining with current difficulty...</p>';
            
            const difficulty = <?php echo $current_difficulty; ?>;
            const timestamp = Math.floor(Date.now() / 1000);
            const previousHash = '0000000000000000000000000000000000000000000000000000000000000000';
            const index = 1;
            
            let attempts = 0;
            let found = false;
            let startTime = Date.now();
            
            output.innerHTML += '<p>Searching for valid hash...</p>';
            
            for (let nonce = 0; nonce < 10000; nonce++) {
                attempts++;
                const blockString = index + timestamp + previousHash + nonce;
                const hash = await sha256(blockString);
                
                if (meetsDifficulty(hash, difficulty)) {
                    found = true;
                    const elapsed = (Date.now() - startTime) / 1000;
                    const hashRate = Math.floor(attempts / elapsed);
                    
                    output.innerHTML = `
                        <div style="color: #00ff88; font-weight: bold;">✅ BLOCK FOUND!</div>
                        <p><strong>Hash:</strong> <span class="hash-example">${hash}</span></p>
                        <p><strong>Nonce:</strong> ${nonce}</p>
                        <p><strong>Attempts:</strong> ${attempts.toLocaleString()}</p>
                        <p><strong>Time:</strong> ${elapsed.toFixed(2)} seconds</p>
                        <p><strong>Hash Rate:</strong> ${hashRate.toLocaleString()} H/s</p>
                        <p><strong>Difficulty:</strong> ${difficulty} leading zeros</p>
                    `;
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
                    <div style="color: #ff6666;">❌ No block found in ${attempts.toLocaleString()} attempts</div>
                    <p><strong>Time:</strong> ${elapsed.toFixed(2)} seconds</p>
                    <p><strong>Hash Rate:</strong> ${hashRate.toLocaleString()} H/s</p>
                    <p><strong>Difficulty:</strong> ${difficulty} leading zeros</p>
                    <p>Try lowering the difficulty for easier testing.</p>
                `;
            }
        }

        function clearTest() {
            document.getElementById('testOutput').innerHTML = '<p>Click "Test Mining" to see if blocks can be found with current difficulty.</p>';
        }
    </script>
</body>
</html> 