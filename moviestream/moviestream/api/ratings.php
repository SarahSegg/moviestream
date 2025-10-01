<?php
require_once __DIR__.'/../includes/db_connect.php';
require_once __DIR__.'/../helpers.php';
require_once __DIR__.'/../includes/auth.php';

header('Content-Type: application/json');

// Enable CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
        case 'PUT':
            handlePutRequest();
            break;
        case 'DELETE':
            handleDeleteRequest();
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
    
    $title_id = isset($_GET['title_id']) ? (int)$_GET['title_id'] : 0;
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($title_id > 0) {
        // Get ratings for a specific title
        $avg_rating = db_one($pdo, "
            SELECT ROUND(AVG(rating), 2) as average, COUNT(*) as count
            FROM ratings WHERE title_id = ?
        ", [$title_id]);
        
        $recent_ratings = db_all($pdo, "
            SELECT r.rating, r.rated_at, u.username
            FROM ratings r
            LEFT JOIN users u ON u.user_id = r.user_id
            WHERE r.title_id = ?
            ORDER BY r.rated_at DESC
            LIMIT 10
        ", [$title_id]);
        
        $response['success'] = true;
        $response['average'] = $avg_rating['average'] ?? 0;
        $response['count'] = $avg_rating['count'] ?? 0;
        $response['recent'] = $recent_ratings;
        
    } elseif ($user_id > 0) {
        // Get ratings by a specific user
        $ratings = db_all($pdo, "
            SELECT r.rating_id, r.title_id, r.rating, r.rated_at, t.name as title_name
            FROM ratings r
            JOIN titles t ON t.title_id = r.title_id
            WHERE r.user_id = ?
            ORDER BY r.rated_at DESC
        ", [$user_id]);
        
        $response['success'] = true;
        $response['ratings'] = $ratings;
        
    } else {
        http_response_code(400);
        $response['message'] = 'title_id or user_id parameter required';
    }
    
    echo json_encode($response);
}

function handlePostRequest() {
    global $pdo, $response;
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id']) || !isset($input['title_id']) || !isset($input['rating'])) {
        http_response_code(400);
        $response['message'] = 'Missing required fields';
        echo json_encode($response);
        return;
    }
    
    $user_id = (int)$input['user_id'];
    $title_id = (int)$input['title_id'];
    $rating = (float)$input['rating'];
    
    if ($rating < 0 || $rating > 10) {
        http_response_code(400);
        $response['message'] = 'Rating must be between 0 and 10';
        echo json_encode($response);
        return;
    }
    
    // Check if rating already exists
    $existing = db_one($pdo, "
        SELECT rating_id FROM ratings 
        WHERE user_id = ? AND title_id = ?
    ", [$user_id, $title_id]);
    
    if ($existing) {
        // Update existing rating
        $stmt = $pdo->prepare("
            UPDATE ratings SET rating = ?, rated_at = NOW()
            WHERE rating_id = ?
        ");
        $stmt->execute([$rating, $existing['rating_id']]);
        $response['action'] = 'updated';
    } else {
        // Insert new rating
        $stmt = $pdo->prepare("
            INSERT INTO ratings (user_id, title_id, rating, rated_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$user_id, $title_id, $rating]);
        $response['action'] = 'created';
    }
    
    $response['success'] = true;
    $response['message'] = 'Rating ' . $response['action'] . ' successfully';
    echo json_encode($response);
}

function handlePutRequest() {
    global $pdo, $response;
    
    parse_str(file_get_contents('php://input'), $input);
    
    if (!isset($input['rating_id']) || !isset($input['rating'])) {
        http_response_code(400);
        $response['message'] = 'Missing required fields';
        echo json_encode($response);
        return;
    }
    
    $rating_id = (int)$input['rating_id'];
    $rating = (float)$input['rating'];
    
    if ($rating < 0 || $rating > 10) {
        http_response_code(400);
        $response['message'] = 'Rating must be between 0 and 10';
        echo json_encode($response);
        return;
    }
    
    $stmt = $pdo->prepare("
        UPDATE ratings SET rating = ?, rated_at = NOW()
        WHERE rating_id = ?
    ");
    $stmt->execute([$rating, $rating_id]);
    
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Rating updated successfully';
    } else {
        http_response_code(404);
        $response['message'] = 'Rating not found';
    }
    
    echo json_encode($response);
}

function handleDeleteRequest() {
    global $pdo, $response;
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['rating_id'])) {
        http_response_code(400);
        $response['message'] = 'rating_id parameter required';
        echo json_encode($response);
        return;
    }
    
    $rating_id = (int)$input['rating_id'];
    
    $stmt = $pdo->prepare("DELETE FROM ratings WHERE rating_id = ?");
    $stmt->execute([$rating_id]);
    
    if ($stmt->rowCount() > 0) {
        $response['success'] = true;
        $response['message'] = 'Rating deleted successfully';
    } else {
        http_response_code(404);
        $response['message'] = 'Rating not found';
    }
    
    echo json_encode($response);
}
?>