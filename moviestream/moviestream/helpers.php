<?php
// Escape output to prevent XSS
function e($s) { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
}

// Redirect to a URL
function redirect($url) { 
    header("Location: $url"); 
    exit; 
}

// Get all rows from database
function db_all(PDO $pdo, string $sql, array $params = []): array { 
    $st = $pdo->prepare($sql); 
    $st->execute($params); 
    return $st->fetchAll(); 
}

// Get one row from database
function db_one(PDO $pdo, string $sql, array $params = []): ?array { 
    $st = $pdo->prepare($sql); 
    $st->execute($params); 
    $row = $st->fetch(); 
    return $row ?: null; 
}

// Format date
function format_date($date) {
    if (!$date) return 'N/A';
    return date('M j, Y', strtotime($date));
}

// Format runtime
function format_runtime($minutes) {
    if (!$minutes) return 'N/A';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        return "{$hours}h {$mins}m";
    }
    return "{$mins}m";
}

// Get rating stars HTML
function get_rating_stars($rating) {
    $stars = '';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 10 - $fullStars - ($halfStar ? 1 : 0);
    
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fas fa-star"></i>';
    }
    
    if ($halfStar) {
        $stars .= '<i class="fas fa-star-half-alt"></i>';
    }
    
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '<i class="far fa-star"></i>';
    }
    
    return $stars;
}

// Check if user is logged in (placeholder for authentication)
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get current user ID (placeholder for authentication)
function get_user_id() {
    return $_SESSION['user_id'] ?? 0;
}
?>