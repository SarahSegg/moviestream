<?php
require_once __DIR__.'/../header.php';

require_login();
if (!is_admin()) {
    flash("Access denied. Admin privileges required.", "error");
    redirect('index.php');
}

// Admin dashboard statistics
$stats = db_one($pdo, "
    SELECT 
        (SELECT COUNT(*) FROM titles) as total_titles,
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM ratings) as total_ratings,
        (SELECT COUNT(*) FROM watch_events) as total_watch_events,
        (SELECT ROUND(AVG(rating), 2) FROM ratings) as avg_rating,
        (SELECT SUM(seconds_watched) FROM watch_events) as total_watch_seconds
");

$recent_users = db_all($pdo, "
    SELECT user_id, username, email, created_at
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");

$recent_ratings = db_all($pdo, "
    SELECT r.rating_id, r.rating, r.rated_at, 
           u.username, t.name as title_name
    FROM ratings r
    JOIN users u ON u.user_id = r.user_id
    JOIN titles t ON t.title_id = r.title_id
    ORDER BY r.rated_at DESC 
    LIMIT 5
");
?>

<div class="page-header">
    <h1 class="page-title">Admin Dashboard</h1>
</div>

<div class="grid">
    <div class="kpi-card">
        <div class="kpi-value"><?= (int)$stats['total_titles'] ?></div>
        <div class="kpi-label">Total Titles</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?= (int)$stats['total_users'] ?></div>
        <div class="kpi-label">Total Users</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?= (int)$stats['total_ratings'] ?></div>
        <div class="kpi-label">Total Ratings</div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?= round($stats['total_watch_seconds'] / 3600, 1) ?></div>
        <div class="kpi-label">Watch Hours</div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Users</h2>
            <a href="users.php" class="btn btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_users as $user): ?>
                    <tr>
                        <td><?= e($user['username']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td><?= format_date($user['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Ratings</h2>
            <a href="../users/ratings.php" class="btn btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>