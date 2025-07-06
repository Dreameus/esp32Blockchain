<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user data
try {
    $conn = getDBConnection();
    
    // Get user balance
    $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $balance_result = $stmt->get_result();
    $balance = $balance_result->fetch_assoc()['balance'] ?? 0;
    
    // Get user wallet address
    $stmt = $conn->prepare("SELECT wallet_address FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $wallet_result = $stmt->get_result();
    $wallet_data = $wallet_result->fetch_assoc();
    $wallet_address = $wallet_data['wallet_address'] ?? 'ESP32_WALLET_NOT_FOUND';
    
    // Get mining stats
    $stmt = $conn->prepare("SELECT blocks_mined, total_reward FROM mining_leaderboard WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $mining_result = $stmt->get_result();
    $mining_stats = $mining_result->fetch_assoc();
    $blocks_mined = $mining_stats['blocks_mined'] ?? 0;
    $total_reward = $mining_stats['total_reward'] ?? 0;
    
    $conn->close();
    
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
    $balance = 0;
    $wallet_address = 'ESP32_WALLET_ERROR';
    $blocks_mined = 0;
    $total_reward = 0;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ESP32 Blockchain - Dashboard</title>
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

        .balance {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 10px 15px;
            border-radius: 10px;
            text-align: center;
        }

        .balance-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #00ffff;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .card {
                padding: 20px;
            }
            
            .mining-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .tabs {
                flex-direction: column;
            }
            
            .tab {
                padding: 10px 20px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            .card {
                padding: 15px;
            }
            
            .mining-btn {
                padding: 12px 25px;
                font-size: 16px;
            }
            
            .balance-amount {
                font-size: 1.2em;
            }
            
            .logo {
                font-size: 1.5em;
            }
        }

        .card {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff88;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.2);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .card::before {
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

        .mining-controls {
            text-align: center;
        }

        .mining-btn {
            background: linear-gradient(45deg, #00ff88, #00ffff);
            border: none;
            color: #000;
            padding: 15px 40px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 20px;
            min-height: 50px; /* Touch-friendly minimum height */
            touch-action: manipulation; /* Optimize for touch */
        }

        .mining-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 255, 136, 0.4);
        }

        .mining-btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .mining-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }

        .stat {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffff;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #00ffff;
        }

        .stat-label {
            font-size: 0.9em;
            color: #00ff88;
            margin-top: 5px;
        }

        .mining-animation {
            width: 100%;
            height: 200px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 10px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
            display: none;
        }

        .mining-animation.active {
            display: block;
        }

        .mining-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #00ff88;
            border-radius: 50%;
            animation: miningFloat 2s infinite linear;
        }

        @keyframes miningFloat {
            0% {
                transform: translateY(200px) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-10px) translateX(100px);
                opacity: 0;
            }
        }

        .transaction-form {
            display: grid;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            font-weight: bold;
            text-shadow: 0 0 10px #00ff88;
        }

        input[type="text"], input[type="number"] {
            padding: 12px;
            border: 2px solid #00ff88;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #00ff88;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            transition: all 0.3s ease;
            min-height: 44px; /* Touch-friendly minimum height */
            touch-action: manipulation; /* Optimize for touch */
        }

        input[type="text"]:focus, input[type="number"]:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
        }

        /* Prevent zoom on input focus on mobile */
        @media screen and (max-width: 768px) {
            input[type="text"], input[type="number"] {
                font-size: 16px !important;
            }
        }

        .send-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            min-height: 44px; /* Touch-friendly minimum height */
            touch-action: manipulation; /* Optimize for touch */
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 136, 0.4);
        }

        .wallet-info {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            word-break: break-all;
        }

        .wallet-label {
            font-size: 0.9em;
            color: #00ff88;
            margin-bottom: 5px;
        }

        .wallet-address {
            font-family: monospace;
            font-size: 0.8em;
            color: #00ffff;
        }

        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #00ff88;
        }

        .tab {
            background: none;
            border: none;
            color: #00ff88;
            padding: 15px 30px;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            font-weight: bold;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }

        .tab.active {
            color: #00ffff;
            border-bottom-color: #00ffff;
            text-shadow: 0 0 10px #00ffff;
        }

        .tab:hover {
            color: #00ffff;
            text-shadow: 0 0 10px #00ffff;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #00ff88;
            border-radius: 50%;
            animation: float 8s infinite linear;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="header">
        <div class="logo">ESP32 Blockchain</div>
        <div class="user-info">
            <div class="balance">
                <div class="balance-amount"><?php echo number_format($balance); ?></div>
                <div>ESP32 Tokens</div>
            </div>
            <div>Welcome, <?php echo htmlspecialchars($username); ?></div>
            <a href="contest.php" class="nav-link">🎯 Contest</a>
            <a href="rewards.php" class="nav-link">ESP32 Rewards</a>
            <a href="mining_leaderboard.php" class="nav-link">🏆 Leaderboard</a>
            <a href="get_recent_blocks.php" class="nav-link">🔨 Mined Blocks</a>
            <a href="test.php" class="nav-link">🧪 Testing</a>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="grid">
            <!-- Mining Card -->
            <div class="card">
                <div class="card-title">Mining Interface</div>
                <div class="mining-controls">
                    <button id="miningBtn" class="mining-btn">Start Mining</button>
                    <div class="mining-stats">
                        <div class="stat">
                            <div class="stat-value" id="hashRate">0</div>
                            <div class="stat-label">H/s</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value" id="nonce">0</div>
                            <div class="stat-label">Nonce</div>
                        </div>
                    </div>
                    <div class="mining-animation" id="miningAnimation">
                        <!-- Mining particles will be added here -->
                    </div>
                </div>
            </div>

            <!-- Transactions Card -->
            <div class="card">
                <div class="card-title">Send Tokens</div>
                <div class="wallet-info">
                    <div class="wallet-label">Your Wallet Address:</div>
                    <div class="wallet-address"><?php echo htmlspecialchars($wallet_address); ?></div>
                </div>
                <form class="transaction-form" id="transactionForm">
                    <div class="form-group">
                        <label for="receiver">Receiver Wallet Address:</label>
                        <input type="text" id="receiver" name="receiver" required placeholder="ESP32_...">
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount:</label>
                        <input type="number" id="amount" name="amount" required min="1" max="<?php echo $balance; ?>">
                    </div>
                    <button type="submit" class="send-btn">Send Tokens</button>
                </form>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="card" style="margin-top: 30px;">
            <div class="tabs">
                <button class="tab active" onclick="showTab('stats')">Mining Stats</button>
                <button class="tab" onclick="showTab('blocks')">Recent Blocks</button>
                <button class="tab" onclick="showTab('transactions')">Recent Transactions</button>
            </div>

            <div id="stats" class="tab-content active">
                <div class="mining-stats" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat">
                        <div class="stat-value"><?php echo $blocks_mined; ?></div>
                        <div class="stat-label">Blocks Mined</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo number_format($total_reward); ?></div>
                        <div class="stat-label">Total Rewards</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo DIFFICULTY; ?></div>
                        <div class="stat-label">Difficulty</div>
                        <div style="font-size: 0.7em; color: #00ff88; margin-top: 5px;">
                            <?php echo str_repeat('0', DIFFICULTY); ?>... required
                        </div>
                    </div>
                    <div class="stat">
                        <div class="stat-value"><?php echo REWARD_AMOUNT; ?></div>
                        <div class="stat-label">Reward/Block</div>
                    </div>
                </div>
            </div>



            <div id="blocks" class="tab-content">
                <div id="blocksList">Loading blocks...</div>
            </div>

            <div id="transactions" class="tab-content">
                <div id="transactionsList">Loading transactions...</div>
            </div>
        </div>
    </div>

    <script>
        let isMining = false;
        let miningInterval;
        let hashCount = 0;
        let startTime = Date.now();
        let currentNonce = 0;
        let miningWorker = null;
        let useWebWorker = true; // Enable Web Worker for maximum speed

        // Create floating particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 8 + 's';
                particle.style.animationDuration = (Math.random() * 4 + 4) + 's';
                particlesContainer.appendChild(particle);
            }
        }

        // Mining animation
        function createMiningParticles() {
            const animation = document.getElementById('miningAnimation');
            animation.innerHTML = '';
            
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'mining-particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 2 + 's';
                particle.style.animationDuration = (Math.random() * 1 + 1) + 's';
                animation.appendChild(particle);
            }
        }

        // Create Web Worker for ultra-fast mining
        function createMiningWorker() {
            const workerCode = `
                // SHA256 implementation for Web Worker
                async function sha256(str) {
                    const encoder = new TextEncoder();
                    const data = encoder.encode(str);
                    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
                    const hashArray = new Uint8Array(hashBuffer);
                    let hashHex = '';
                    
                    for (let i = 0; i < hashArray.length; i++) {
                        const hex = hashArray[i].toString(16);
                        hashHex += hex.length === 1 ? '0' + hex : hex;
                    }
                    
                    return hashHex;
                }

                function meetsDifficulty(hash, difficulty) {
                    for (let i = 0; i < difficulty; i++) {
                        if (hash[i] !== '0') return false;
                    }
                    return true;
                }

                // Main mining loop
                async function mineBatch(index, timestamp, previousHash, startNonce, batchSize, difficulty) {
                    const results = [];
                    
                    for (let i = 0; i < batchSize; i++) {
                        const nonce = startNonce + i;
                        const blockString = index + timestamp + previousHash + nonce;
                        const hash = await sha256(blockString);
                        
                        if (meetsDifficulty(hash, difficulty)) {
                            results.push({ found: true, hash, nonce });
                            break;
                        }
                        
                        results.push({ found: false, hash, nonce });
                    }
                    
                    return results;
                }

                // Listen for mining requests
                self.onmessage = async function(e) {
                    const { type, data } = e.data;
                    
                    if (type === 'mine') {
                        const results = await mineBatch(
                            data.index, 
                            data.timestamp, 
                            data.previousHash, 
                            data.startNonce, 
                            data.batchSize, 
                            data.difficulty
                        );
                        
                        self.postMessage({ type: 'results', results });
                    }
                };
            `;

            const blob = new Blob([workerCode], { type: 'application/javascript' });
            return new Worker(URL.createObjectURL(blob));
        }

        // Optimized SHA256 function - EXACTLY matches ESP32 implementation
        async function sha256(str) {
            const encoder = new TextEncoder();
            const data = encoder.encode(str);
            const hashBuffer = await crypto.subtle.digest('SHA-256', data);
            const hashArray = new Uint8Array(hashBuffer);
            let hashHex = '';
            
            // Faster hex conversion
            for (let i = 0; i < hashArray.length; i++) {
                const hex = hashArray[i].toString(16);
                hashHex += hex.length === 1 ? '0' + hex : hex;
            }
            
            return hashHex;
        }
        
        // Check if hash meets difficulty - EXACTLY matches ESP32
        function meetsDifficulty(hash) {
            for (let i = 0; i < <?php echo DIFFICULTY; ?>; i++) {
                if (hash[i] !== '0') return false;
            }
            return true;
        }

        // Ultra-fast mining function with Web Worker support
        async function mine() {
            if (!isMining) {
                console.log('Mining stopped, exiting mine() function');
                return;
            }

            try {
                // Get the latest block info from server (only once per session)
                if (!window.blockInfo) {
                    console.log('Fetching latest block info...');
                    const response = await fetch('api/get_latest_block.php');
                    window.blockInfo = await response.json();
                    console.log('Block info received:', window.blockInfo);
                }
                
                const timestamp = Math.floor(Date.now() / 1000);
                const previousHash = window.blockInfo.previous_hash || '0000000000000000000000000000000000000000000000000000000000000000';
                const index = window.blockInfo.index + 1;
                const difficulty = <?php echo DIFFICULTY; ?>;

                // Use Web Worker if available for maximum speed
                if (useWebWorker && !miningWorker) {
                    try {
                        miningWorker = createMiningWorker();
                        miningWorker.onmessage = function(e) {
                            if (e.data.type === 'results') {
                                const results = e.data.results;
                                
                                // Update hash count
                                hashCount += results.length;
                                currentNonce += results.length;
                                
                                // Check for found blocks
                                for (const result of results) {
                                    if (result.found) {
                                        console.log('Block found! Hash:', result.hash, 'Nonce:', result.nonce);
                                        submitBlock(index, timestamp, previousHash, result.nonce, result.hash).then(success => {
                                            if (success) {
                                                currentNonce = 0;
                                                hashCount = 0;
                                                startTime = Date.now();
                                                window.blockInfo = null;
                                            }
                                        });
                                        return;
                                    }
                                }
                                
                                // Update display every 200 hashes
                                if (hashCount % 200 === 0) {
                                    const elapsed = (Date.now() - startTime) / 1000;
                                    const hashRate = Math.floor(hashCount / elapsed);
                                    document.getElementById('hashRate').textContent = hashRate.toLocaleString();
                                    document.getElementById('nonce').textContent = currentNonce.toLocaleString();
                                }
                                
                                // Continue mining
                                if (isMining) {
                                    setTimeout(mine, 1);
                                }
                            }
                        };
                    } catch (error) {
                        console.log('Web Worker not supported, falling back to main thread');
                        useWebWorker = false;
                    }
                }

                if (useWebWorker && miningWorker) {
                    // Use Web Worker for mining
                    const batchSize = 1000; // Larger batch for Web Worker
                    miningWorker.postMessage({
                        type: 'mine',
                        data: {
                            index,
                            timestamp,
                            previousHash,
                            startNonce: currentNonce,
                            batchSize,
                            difficulty
                        }
                    });
                } else {
                    // Fallback to main thread mining
                    const batchSize = 500;
                    const promises = [];
                    
                    for (let i = 0; i < batchSize; i++) {
                        if (!isMining) break;
                        
                        const nonce = currentNonce + i;
                        const blockString = index + timestamp + previousHash + nonce;
                        
                        promises.push(
                            sha256(blockString).then(hash => ({
                                hash,
                                nonce,
                                found: meetsDifficulty(hash)
                            }))
                        );
                    }
                    
                    const results = await Promise.all(promises);
                    hashCount += results.length;
                    currentNonce += results.length;
                    
                    for (const result of results) {
                        if (result.found) {
                            console.log('Block found! Hash:', result.hash, 'Nonce:', result.nonce);
                            const success = await submitBlock(index, timestamp, previousHash, result.nonce, result.hash);
                            if (success) {
                                currentNonce = 0;
                                hashCount = 0;
                                startTime = Date.now();
                                window.blockInfo = null;
                                break;
                            }
                        }
                    }
                    
                    if (hashCount % 100 === 0) {
                        const elapsed = (Date.now() - startTime) / 1000;
                        const hashRate = Math.floor(hashCount / elapsed);
                        document.getElementById('hashRate').textContent = hashRate.toLocaleString();
                        document.getElementById('nonce').textContent = currentNonce.toLocaleString();
                    }

                    if (isMining) {
                        setTimeout(mine, 1);
                    }
                }
            } catch (error) {
                console.error('Mining error:', error);
                if (isMining) {
                    setTimeout(mine, 100);
                }
            }
        }

        // Optimized SHA256 mining function
        async function optimizedMine() {
            if (!isMining) return;

            // Get the latest block info from server
            if (!window.blockInfo) {
                console.log('Fetching latest block info...');
                const response = await fetch('api/get_latest_block.php');
                window.blockInfo = await response.json();
                console.log('Block info received:', window.blockInfo);
            }
            
            const timestamp = Math.floor(Date.now() / 1000);
            const previousHash = window.blockInfo.hash || '0000000000000000000000000000000000000000000000000000000000000000';
            const index = window.blockInfo.index + 1;

            // Process nonces in larger batches for better performance
            for (let i = 0; i < 200; i++) {
                if (!isMining) break;
                
                currentNonce++;
                const blockString = index + timestamp + previousHash + currentNonce;
                
                try {
                    const hash = await sha256(blockString);
                    hashCount++;

                    // Update display every 20 hashes
                    if (hashCount % 20 === 0) {
                        const elapsed = (Date.now() - startTime) / 1000;
                        const hashRate = Math.floor(hashCount / elapsed);
                        document.getElementById('hashRate').textContent = hashRate.toLocaleString();
                        document.getElementById('nonce').textContent = currentNonce.toLocaleString();
                    }

                    if (meetsDifficulty(hash)) {
                        console.log('Block found! Hash:', hash, 'Nonce:', currentNonce);
                        const success = await submitBlock(index, timestamp, previousHash, currentNonce, hash);
                        if (success) {
                            currentNonce = 0;
                            hashCount = 0;
                            startTime = Date.now();
                            window.blockInfo = null; // Force refresh of block info
                        }
                    }
                } catch (error) {
                    console.error('Hash error:', error);
                }
            }

            if (isMining) {
                setTimeout(optimizedMine, 20);
            }
        }

        // Advanced mining function with proper SHA256
        async function advancedMine() {
            if (!isMining) return;

            // Get the latest block info from server
            if (!window.blockInfo) {
                console.log('Fetching latest block info...');
                const response = await fetch('api/get_latest_block.php');
                window.blockInfo = await response.json();
                console.log('Block info received:', window.blockInfo);
            }
            
            const timestamp = Math.floor(Date.now() / 1000);
            const previousHash = window.blockInfo.hash || '0000000000000000000000000000000000000000000000000000000000000000';
            const index = window.blockInfo.index + 1;

            // Process nonces in batches
            for (let i = 0; i < 50; i++) {
                if (!isMining) break;
                
                currentNonce++;
                const blockString = index + timestamp + previousHash + currentNonce;
                
                try {
                    const hash = await sha256(blockString);
                    hashCount++;

                    // Update display every 10 hashes
                    if (hashCount % 10 === 0) {
                        const elapsed = (Date.now() - startTime) / 1000;
                        const hashRate = Math.floor(hashCount / elapsed);
                        document.getElementById('hashRate').textContent = hashRate.toLocaleString();
                        document.getElementById('nonce').textContent = currentNonce.toLocaleString();
                    }

                    if (meetsDifficulty(hash)) {
                        console.log('Block found! Hash:', hash, 'Nonce:', currentNonce);
                        const success = await submitBlock(index, timestamp, previousHash, currentNonce, hash);
                        if (success) {
                            currentNonce = 0;
                            hashCount = 0;
                            startTime = Date.now();
                            window.blockInfo = null; // Force refresh of block info
                        }
                    }
                } catch (error) {
                    console.error('Hash error:', error);
                }
            }

            if (isMining) {
                setTimeout(advancedMine, 50);
            }
        }

        // Submit block to server
        async function submitBlock(index, timestamp, previousHash, nonce, hash) {
            // Validate all inputs
            console.log('Validating block data...');
            console.log('Index:', index, 'Type:', typeof index);
            console.log('Timestamp:', timestamp, 'Type:', typeof timestamp);
            console.log('Previous Hash:', previousHash, 'Type:', typeof previousHash);
            console.log('Nonce:', nonce, 'Type:', typeof nonce);
            console.log('Hash:', hash, 'Type:', typeof hash);
            
            // Check for invalid values
            if (index === undefined || index === null || isNaN(index)) {
                console.error('❌ Invalid index:', index);
                return false;
            }
            if (timestamp === undefined || timestamp === null || isNaN(timestamp)) {
                console.error('❌ Invalid timestamp:', timestamp);
                return false;
            }
            if (!previousHash || typeof previousHash !== 'string') {
                console.error('❌ Invalid previous hash:', previousHash);
                return false;
            }
            if (nonce === undefined || nonce === null || isNaN(nonce)) {
                console.error('❌ Invalid nonce:', nonce);
                return false;
            }
            if (!hash || typeof hash !== 'string') {
                console.error('❌ Invalid hash:', hash);
                return false;
            }
            
            // Show block data before submission
            const blockData = {
                index: parseInt(index),
                timestamp: parseInt(timestamp),
                previous_hash: String(previousHash),
                nonce: parseInt(nonce),
                hash: String(hash)
            };
            
            console.log('=== BLOCK DATA BEFORE SUBMISSION ===');
            console.log('Block Data:', JSON.stringify(blockData, null, 2));
            console.log('Block String (for hash calculation):', index + timestamp + previousHash + nonce);
            console.log('Calculated Hash:', hash);
            console.log('Hash starts with:', hash.substring(0, 4));
            console.log('Current Difficulty:', <?php echo DIFFICULTY; ?>);
            console.log('Meets Difficulty:', hash.substring(0, <?php echo DIFFICULTY; ?>).split('').every(char => char === '0'));
            console.log('=====================================');
            
            try {
                // Use debug endpoint to see what's happening
                const jsonPayload = JSON.stringify(blockData);
                console.log('Sending JSON payload:', jsonPayload);
                console.log('Payload length:', jsonPayload.length);
                
                const response = await fetch('debug_block_submission.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: jsonPayload
                });

                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse response as JSON:', parseError);
                    console.error('Response text:', responseText);
                    return false;
                }
                if (result.success) {
                    console.log('✅ Block submitted successfully');
                    console.log('Server Response:', result);
                    console.log('Debug Info:', result.debug_info);
                    
                    // Check if hashes match
                    if (!result.debug_info.hashes_match) {
                        console.error('❌ HASH MISMATCH! Dashboard and server calculated different hashes!');
                        console.error('Dashboard hash:', hash);
                        console.error('Server hash:', result.debug_info.calculated_hash);
                    }
                    
                    // Reset block info for next block
                    window.blockInfo = null;
                    
                    // Update balance and reload
                    location.reload();
                    return true;
                } else {
                    console.error('❌ Block submission failed:', result.error);
                    return false;
                }
            } catch (error) {
                console.error('❌ Error submitting block:', error);
                return false;
            }
        }

        // Mining button handler
        document.getElementById('miningBtn').addEventListener('click', function() {
            if (!isMining) {
                console.log('Starting mining...');
                isMining = true;
                this.textContent = 'Stop Mining';
                this.style.background = 'linear-gradient(45deg, #ff0088, #ff6666)';
                
                document.getElementById('miningAnimation').classList.add('active');
                createMiningParticles();
                
                startTime = Date.now();
                hashCount = 0;
                currentNonce = 0;
                
                console.log('Mining started, calling mine() function...');
                mine();
            } else {
                console.log('Stopping mining...');
                isMining = false;
                this.textContent = 'Start Mining';
                this.style.background = 'linear-gradient(45deg, #00ff88, #00ffff)';
                
                document.getElementById('miningAnimation').classList.remove('active');
                
                // Clean up Web Worker
                if (miningWorker) {
                    miningWorker.terminate();
                    miningWorker = null;
                }
            }
        });

        // Transaction form handler
        document.getElementById('transactionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const receiver = document.getElementById('receiver').value;
            const amount = parseInt(document.getElementById('amount').value);
            
            try {
                const response = await fetch('api/submit_transaction.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        receiver_address: receiver,
                        amount: amount
                    })
                });

                const result = await response.json();
                if (result.success) {
                    alert('Transaction submitted successfully!');
                    location.reload();
                } else {
                    alert('Transaction failed: ' + result.error);
                }
            } catch (error) {
                alert('Error submitting transaction: ' + error.message);
            }
        });

        // Load recent blocks
        async function loadBlocks() {
            try {
                const response = await fetch('api/get_recent_blocks.php');
                const result = await response.json();
                
                const blocksList = document.getElementById('blocksList');
                
                if (result.success && result.blocks.length > 0) {
                    let html = '<div style="overflow-x: auto;">';
                    html += '<table style="width: 100%; border-collapse: collapse; background: #1a1a1a; border: 1px solid #00ff88;">';
                    html += '<tr style="background: #00ff88; color: #000;">';
                    html += '<th style="padding: 10px; text-align: left;">Block #</th>';
                    html += '<th style="padding: 10px; text-align: left;">Hash</th>';
                    html += '<th style="padding: 10px; text-align: left;">Previous Hash</th>';
                    html += '<th style="padding: 10px; text-align: left;">Miner</th>';
                    html += '<th style="padding: 10px; text-align: left;">Nonce</th>';
                    html += '<th style="padding: 10px; text-align: left;">Difficulty</th>';
                    html += '<th style="padding: 10px; text-align: left;">Reward</th>';
                    html += '<th style="padding: 10px; text-align: left;">Mined At</th>';
                    html += '</tr>';
                    
                    result.blocks.forEach(block => {
                        html += '<tr style="border-bottom: 1px solid #333;">';
                        html += '<td style="padding: 10px; color: #00ffff;">#' + block.id + '</td>';
                        html += '<td style="padding: 10px; font-family: monospace; font-size: 0.8em; color: #fff;">' + block.hash.substring(0, 16) + '...</td>';
                        html += '<td style="padding: 10px; font-family: monospace; font-size: 0.8em; color: #888;">' + block.previous_hash.substring(0, 16) + '...</td>';
                        html += '<td style="padding: 10px; color: #00ff88;">' + block.miner + '</td>';
                        html += '<td style="padding: 10px; color: #ffff00;">' + block.nonce.toLocaleString() + '</td>';
                        html += '<td style="padding: 10px; color: #ff8800;">' + block.difficulty + ' zeros</td>';
                        html += '<td style="padding: 10px; color: #00ffff;">' + block.reward.toLocaleString() + '</td>';
                        html += '<td style="padding: 10px; color: #888; font-size: 0.9em;">' + new Date(block.mined_at).toLocaleString() + '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</table>';
                    html += '</div>';
                    blocksList.innerHTML = html;
                } else {
                    blocksList.innerHTML = '<p style="text-align: center; color: #888; padding: 20px;">No blocks have been mined yet. Start mining to see blocks here!</p>';
                }
            } catch (error) {
                console.error('Error loading blocks:', error);
                document.getElementById('blocksList').innerHTML = '<p style="text-align: center; color: #ff6666; padding: 20px;">Error loading blocks: ' + error.message + '</p>';
            }
        }

        // Tab switching
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            // Load data for specific tabs
            if (tabName === 'blocks') {
                loadBlocks();
            }
        }

        // Initialize
        createParticles();
        
        // Load blocks on page load if blocks tab is active
        if (document.getElementById('blocks').classList.contains('active')) {
            loadBlocks();
        }
    </script>
</body>
</html> 