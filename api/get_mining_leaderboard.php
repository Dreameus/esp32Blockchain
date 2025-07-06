<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

try {
    $conn = getDBConnection();
    
    // Get top miners by blocks mined
    $stmt = $conn->prepare("
        SELECT ml.user_id, u.username, ml.blocks_mined, ml.total_reward, b.balance
        FROM mining_leaderboard ml
        JOIN users u ON ml.user_id = u.id
        LEFT JOIN balances b ON ml.user_id = b.user_id
        WHERE ml.blocks_mined > 0
        ORDER BY ml.blocks_mined DESC, ml.total_reward DESC
        LIMIT 50
    ");
    $stmt->execute();
    $top_miners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get top miners by total rewards
    $stmt = $conn->prepare("
        SELECT ml.user_id, u.username, ml.blocks_mined, ml.total_reward, b.balance
        FROM mining_leaderboard ml
        JOIN users u ON ml.user_id = u.id
        LEFT JOIN balances b ON ml.user_id = b.user_id
        WHERE ml.total_reward > 0
        ORDER BY ml.total_reward DESC, ml.blocks_mined DESC
        LIMIT 50
    ");
    $stmt->execute();
    $top_earners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get system statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total_miners FROM mining_leaderboard WHERE blocks_mined > 0");
    $stmt->execute();
    $total_miners = $stmt->get_result()->fetch_assoc()['total_miners'];
    
    $stmt = $conn->prepare("SELECT SUM(blocks_mined) as total_blocks FROM mining_leaderboard");
    $stmt->execute();
    $total_blocks = $stmt->get_result()->fetch_assoc()['total_blocks'] ?? 0;
    
    $stmt = $conn->prepare("SELECT SUM(total_reward) as total_rewards FROM mining_leaderboard");
    $stmt->execute();
    $total_rewards = $stmt->get_result()->fetch_assoc()['total_rewards'] ?? 0;
    
    // Get blockchain configuration
    $config = [
        'difficulty' => DIFFICULTY,
        'reward_per_block' => REWARD_AMOUNT,
        'total_supply' => TOTAL_SUPPLY
    ];
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'top_miners_by_blocks' => $top_miners,
            'top_miners_by_rewards' => $top_earners,
            'statistics' => [
                'total_miners' => $total_miners,
                'total_blocks_mined' => $total_blocks,
                'total_rewards_distributed' => $total_rewards
            ],
            'configuration' => $config,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) $conn->close();
}
?> 