<?php
require_once __DIR__.'/../includes/db_connect.php';
require_once __DIR__.'/../helpers.php';
require_once __DIR__.'/../includes/auth.php';

header('Content-Type: application/json');

// Enable CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = ['success' => false, 'message' => ''];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest();
            break;
        case 'POST':
            handlePostRequest();
            break;
        default:
            http_response_code(405);
            $response['message'] = 'Method not allowed';
            echo json_encode($response);
            exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Server error: ' . $e->getMessage();
    echo json_encode($response);
}

function handleGetRequest() {
    global $pdo, $response;
    
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $title_id = isset($_GET['title_id']) ? (int)$_GET['title_id'] : 0;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    if ($user_id > 0) {
        // Get watch history for a user
        $history = db_all($pdo, "
            SELECT we.*, t.name as title_name, t.type as title_type
            FROM watch_events we
            JOIN titles t ON t.title_id = we.title_id
            WHERE we.user_id = ?
            ORDER BY we.watched_at DESC
            LIMIT ?
        ", [$user_id, $limit]);
        
        $stats = db_one($pdo, "
            SELECT COUNT(*) as total_events,
                   SUM(seconds_watched) as total_seconds,
                   COUNT(DISTINCT title_id) as unique_titles,
                   SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completions
            FROM watch_events
            WHERE user_id = ?
        ", [$user_id]);
        
        $response['success'] = true;
        $response['history'] = $history;
        $response['stats'] = $stats;
        
    } elseif ($title_id > 0) {
        // Get watch statistics for a title
        $stats = db_one($pdo, "
            SELECT COUNT(*) as total_views,
                   SUM(seconds_watched) as total_seconds,
                   COUNT(DISTINCT user_id) as unique_viewers,
                   SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completions
            FROM watch_events
            WHERE title_id = ?
        ", [$title_id]);
        
        $recent_views = db_all($pdo, "
            SELECT we.*, u.username
            FROM watch_events we
            LEFT JOIN users u ON u.user_id = we.user_id
            WHERE we.title_id = ?
            ORDER BY we.watched_at DESC
            LIMIT 10
        ", [$title_id]);
        
        $response['success'] = true;
        $response['stats'] = $stats;
        $response['recent'] = $recent_views;
        
    } else {
        http_response_code(400);
        $response['message'] = 'user_id or title_id parameter required';
    }
    
    echo json_encode($response);
}

function handlePostRequest() {
    global $pdo, $response;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['user_id', 'title_id', 'seconds_watched'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            $response['message'] = "Missing required field: $field";
            echo json_encode($response);
            return;
        }
    }
    
    $user_id = (int)$input['user_id'];
    $title_id = (int)$input['title_id'];
    $seconds_watched = (int)$input['seconds_watched'];
    $episode_id = isset($input['episode_id']) ? (int)$input['episode_id'] : null;
    $completed = isset($input['completed']) ? (bool)$input['completed'] : false;
    
    if ($seconds_watched <= 0) {
        http_response_code(400);
        $response['message'] = 'seconds_watched must be positive';
        echo json_encode($response);
        return;
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO watch_events (user_id, title_id, episode_id, watched_at, seconds_watched, completed)
        VALUES (?, ?, ?, NOW(), ?, ?)
    ");
    
    $stmt->execute([
        $user_id, 
        $title_id, 
        $episode_id, 
        $seconds_watched, 
        $completed ? 1 : 0
    ]);
    
    $response['success'] = true;
    $response['message'] = 'Watch event recorded successfully';
    $response['watch_id'] = $pdo->lastInsertId();
    
    echo json_encode($response);
}
?>