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

// Get user balance
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT balance FROM balances WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $balance_result = $stmt->get_result();
    $balance = $balance_result->fetch_assoc()['balance'] ?? 0;
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Blockchain - Rewards</title>
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
            border-bottom: 2px solid #00ff88;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .logo {
            font-size: 2em;
            font-weight: bold;
            text-shadow: 0 0 20px #00ff88;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-link {
            color: #00ff88;
            text-decoration: none;
            padding: 10px 20px;
            border: 1px solid #00ff88;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: #00ff88;
            color: #000;
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.5);
        }

        .balance {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
        }

        .balance-amount {
            font-size: 1.5em;
            font-weight: bold;
            color: #00ffff;
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

        .reward-form {
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

        select, input[type="number"], textarea {
            padding: 12px;
            border: 2px solid #00ff88;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.7);
            color: #00ff88;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        select:focus, input[type="number"]:focus, textarea:focus {
            outline: none;
            border-color: #00ffff;
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .send-reward-btn {
            background: linear-gradient(45deg, #ff0088, #ff6666);
            border: none;
            color: white;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .send-reward-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 0, 136, 0.4);
        }

        .send-reward-btn:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .rewards-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .reward-item {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ffff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .reward-item:hover {
            border-color: #00ff88;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
        }

        .reward-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .reward-amount {
            font-size: 1.2em;
            font-weight: bold;
            color: #00ffff;
        }

        .reward-user {
            color: #00ff88;
            font-weight: bold;
        }

        .reward-reason {
            color: #ccc;
            font-style: italic;
            margin-bottom: 5px;
        }

        .reward-time {
            font-size: 0.8em;
            color: #666;
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

        .loading {
            text-align: center;
            color: #00ffff;
            font-style: italic;
        }

        .error {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff0000;
            color: #ff6666;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success {
            background: rgba(0, 255, 0, 0.2);
            border: 1px solid #00ff00;
            color: #00ff88;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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

        .reward-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
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
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="header">
        <div class="logo">ESP32 Token Rewards</div>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="rewards.php" class="nav-link">Rewards</a>
        </div>
        <div class="balance">
            <div class="balance-amount"><?php echo number_format($balance); ?></div>
            <div>ESP32 Tokens</div>
        </div>
    </div>

    <div class="container">
        <div class="grid">
            <!-- Send Rewards Card -->
            <div class="card">
                <div class="card-title">Send ESP32 Token Reward</div>
                <form class="reward-form" id="rewardForm">
                    <div class="form-group">
                        <label for="receiver">Select User:</label>
                        <select id="receiver" name="receiver" required>
                            <option value="">Choose a user...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (ESP32 Tokens):</label>
                        <input type="number" id="amount" name="amount" required min="1" max="<?php echo $balance; ?>" placeholder="Enter ESP32 token amount">
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason for ESP32 Token Reward:</label>
                        <textarea id="reason" name="reason" required placeholder="e.g., Great mining performance, helpful community member, blockchain contribution, etc."></textarea>
                    </div>
                    <button type="submit" class="send-reward-btn">Send ESP32 Tokens</button>
                </form>
            </div>

            <!-- Rewards History Card -->
            <div class="card">
                <div class="card-title">ESP32 Token Rewards History</div>
                <div class="tabs">
                    <button class="tab active" onclick="showTab('received')">Received</button>
                    <button class="tab" onclick="showTab('sent')">Sent</button>
                </div>
                
                <div id="received" class="tab-content active">
                    <div class="rewards-list" id="receivedRewards">
                        <div class="loading">Loading received rewards...</div>
                    </div>
                </div>
                
                <div id="sent" class="tab-content">
                    <div class="rewards-list" id="sentRewards">
                        <div class="loading">Loading sent rewards...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ESP32 Token Rewards Statistics -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-title">ESP32 Token Rewards Statistics</div>
            <div class="reward-stats">
                <div class="stat">
                    <div class="stat-value" id="totalReceived">0</div>
                    <div class="stat-label">ESP32 Tokens Received</div>
                </div>
                <div class="stat">
                    <div class="stat-value" id="totalSent">0</div>
                    <div class="stat-label">ESP32 Tokens Sent</div>
                </div>
                <div class="stat">
                    <div class="stat-value" id="receivedCount">0</div>
                    <div class="stat-label">Rewards Received</div>
                </div>
                <div class="stat">
                    <div class="stat-value" id="sentCount">0</div>
                    <div class="stat-label">Rewards Sent</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let users = [];
        let rewardsData = {};

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

        // Load users for reward selection
        async function loadUsers() {
            try {
                const response = await fetch('api/get_users.php');
                const data = await response.json();
                
                if (data.success) {
                    users = data.users;
                    const select = document.getElementById('receiver');
                    select.innerHTML = '<option value="">Choose a user...</option>';
                    
                    users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = `${user.username} (${user.wallet_address.substring(0, 10)}...)`;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        // Load rewards history
        async function loadRewards() {
            try {
                const response = await fetch('api/get_rewards.php');
                const data = await response.json();
                
                if (data.success) {
                    rewardsData = data;
                    displayRewards();
                    updateStats();
                }
            } catch (error) {
                console.error('Error loading rewards:', error);
            }
        }

        // Display rewards in the UI
        function displayRewards() {
            // Display received rewards
            const receivedContainer = document.getElementById('receivedRewards');
            if (rewardsData.received_rewards.length === 0) {
                receivedContainer.innerHTML = '<div class="loading">No ESP32 token rewards received yet</div>';
            } else {
                receivedContainer.innerHTML = rewardsData.received_rewards.map(reward => `
                    <div class="reward-item">
                        <div class="reward-header">
                            <span class="reward-amount">+${reward.amount.toLocaleString()} ESP32 Tokens</span>
                            <span class="reward-user">from ${reward.sender_name}</span>
                        </div>
                        <div class="reward-reason">"${reward.reason}"</div>
                        <div class="reward-time">${new Date(reward.timestamp).toLocaleString()}</div>
                    </div>
                `).join('');
            }

            // Display sent rewards
            const sentContainer = document.getElementById('sentRewards');
            if (rewardsData.sent_rewards.length === 0) {
                sentContainer.innerHTML = '<div class="loading">No ESP32 token rewards sent yet</div>';
            } else {
                sentContainer.innerHTML = rewardsData.sent_rewards.map(reward => `
                    <div class="reward-item">
                        <div class="reward-header">
                            <span class="reward-amount">-${reward.amount.toLocaleString()} ESP32 Tokens</span>
                            <span class="reward-user">to ${reward.receiver_name}</span>
                        </div>
                        <div class="reward-reason">"${reward.reason}"</div>
                        <div class="reward-time">${new Date(reward.timestamp).toLocaleString()}</div>
                    </div>
                `).join('');
            }
        }

        // Update statistics
        function updateStats() {
            const totalReceived = rewardsData.received_rewards.reduce((sum, reward) => sum + reward.amount, 0);
            const totalSent = rewardsData.sent_rewards.reduce((sum, reward) => sum + reward.amount, 0);
            
            document.getElementById('totalReceived').textContent = totalReceived.toLocaleString();
            document.getElementById('totalSent').textContent = totalSent.toLocaleString();
            document.getElementById('receivedCount').textContent = rewardsData.received_rewards.length;
            document.getElementById('sentCount').textContent = rewardsData.sent_rewards.length;
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
        }

        // Handle reward form submission
        document.getElementById('rewardForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const receiverId = document.getElementById('receiver').value;
            const amount = parseInt(document.getElementById('amount').value);
            const reason = document.getElementById('reason').value;
            
            if (!receiverId || !amount || !reason) {
                alert('Please fill in all fields');
                return;
            }
            
            const button = document.querySelector('.send-reward-btn');
            button.disabled = true;
                            button.textContent = 'Sending ESP32 Tokens...';
            
            try {
                const response = await fetch('api/send_reward.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        receiver_id: receiverId,
                        amount: amount,
                        reason: reason
                    })
                });

                const result = await response.json();
                if (result.success) {
                    alert(`${result.amount.toLocaleString()} ESP32 tokens sent successfully to ${result.receiver}!`);
                    document.getElementById('rewardForm').reset();
                    loadRewards(); // Refresh rewards list
                    location.reload(); // Refresh page to update balance
                } else {
                    alert('Failed to send ESP32 token reward: ' + result.error);
                }
            } catch (error) {
                alert('Error sending ESP32 token reward: ' + error.message);
            } finally {
                button.disabled = false;
                button.textContent = 'Send ESP32 Tokens';
            }
        });

        // Initialize
        createParticles();
        loadUsers();
        loadRewards();
    </script>
</body>
</html> 