<?php
require_once __DIR__.'/../header.php';

require_login();

$user_id = get_user_id();
$user = db_one($pdo, "SELECT username, email, created_at FROM users WHERE user_id = ?", [$user_id]);

// Get user statistics
$user_stats = db_one($pdo, "
    SELECT COUNT(DISTINCT title_id) as titles_rated,
           COUNT(*) as total_ratings,
           ROUND(AVG(rating), 2) as avg_rating,
           SUM(seconds_watched) as total_watch_time,
           COUNT(DISTINCT title_id) as titles_watched
    FROM (
        SELECT r.title_id, r.rating, NULL as seconds_watched
        FROM ratings r
        WHERE r.user_id = ?
        UNION ALL
        SELECT we.title_id, NULL as rating, we.seconds_watched
        FROM watch_events we
        WHERE we.user_id = ?
    ) combined
", [$user_id, $user_id]);

// Get recent activity
$recent_activity = db_all($pdo, "
    (SELECT 'rating' as type, title_id, rating as value, rated_at as date
     FROM ratings WHERE user_id = ?
     ORDER BY rated_at DESC LIMIT 5)
    UNION ALL
    (SELECT 'watch' as type, title_id, seconds_watched as value, watched_at as date
     FROM watch_events WHERE user_id = ?
     ORDER BY watched_at DESC LIMIT 5)
    ORDER BY date DESC LIMIT 10
", [$user_id, $user_id]);
?>

<div class="page-header">
    <h1 class="page-title">Your Profile</h1>
</div>

<div class="grid" style="grid-template-columns: 1fr 2fr;">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Profile Info</h2>
        </div>
        <div style="text-align: center; padding: 2rem;">
            <div style="width: 100px; height: 100px; background: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; font-size: 2rem; color: white;">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <h3><?= e($user['username']) ?></h3>
            <p><?= e($user['email']) ?></p>
            <p class="note">Member since <?= format_date($user['created_at']) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Your Statistics</h2>
        </div>
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
            <div class="kpi-card">
                <div class="kpi-value"><?= (int)$user_stats['titles_rated'] ?></div>
                <div class="kpi-label">Titles Rated</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= (int)$user_stats['total_ratings'] ?></div>
                <div class="kpi-label">Total Ratings</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= e($user_stats['avg_rating']) ?></div>
                <div class="kpi-label">Avg Rating</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value"><?= round($user_stats['total_watch_time'] / 3600, 1) ?></div>
                <div class="kpi-label">Watch Hours</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Activity</h2>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title</th>
                    <th>Details</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_activity as $activity): ?>
                    <?php
                    $title = db_one($pdo, "SELECT name FROM titles WHERE title_id = ?", [$activity['title_id']]);
                    if (!$title) continue;
                    ?>
                    <tr>
                        <td>
                            <span class="badge <?= $activity['type'] === 'rating' ? 'badge-warning' : 'badge-primary' ?>">
                                <?= e($activity['type']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="title.php?id=<?= $activity['title_id'] ?>">
                                <?= e($title['name']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($activity['type'] === 'rating'): ?>
                                <div class="rating-stars">
                                    <?= get_rating_stars($activity['value']) ?>
                                </div>
                                <?= e($activity['value']) ?>/10
                            <?php else: ?>
                                <?= round($activity['value'] / 60, 1) ?> minutes watched
                            <?php endif; ?>
                        </td>
                        <td><?= format_date($activity['date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__.'/../footer.php'; ?>